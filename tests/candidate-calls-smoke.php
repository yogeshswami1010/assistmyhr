<?php
// Isolated SQLite/fake providers. No production database, calls, or AI requests.
$loader = require ($argv[1] ?? __DIR__.'/../vendor/autoload.php');
$loader->setPsr4('App\\', __DIR__.'/../app');

use App\CandidateCall;
use App\Http\Controllers\Admin\CandidateCallController;
use App\Services\CandidateCallService;
use Illuminate\Config\Repository;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

$app = new Application(dirname(__DIR__));
$app->instance('config', new Repository([
    'candidate_calls' => require __DIR__.'/../config/candidate_calls.php',
    'services' => ['deepseek' => ['key'=>'fake-deepseek-key', 'model'=>'existing-ats-model']],
]));
Facade::setFacadeApplication($app);
$db = new Capsule($app);
$db->addConnection(['driver'=>'sqlite', 'database'=>':memory:', 'prefix'=>'']);
$db->setAsGlobal(); $db->bootEloquent();
$app->instance('db', $db->getDatabaseManager());
$app->bind('db.schema', fn () => $db->schema());
$app->instance(\Illuminate\Contracts\Routing\ResponseFactory::class, new \Illuminate\Routing\ResponseFactory(Mockery::mock(\Illuminate\Contracts\View\Factory::class), Mockery::mock(\Illuminate\Routing\Redirector::class)));
$validator = new \Illuminate\Validation\Factory(new \Illuminate\Translation\Translator(new \Illuminate\Translation\ArrayLoader(), 'en'));
Request::macro('validate', function ($rules) use ($validator) { return $validator->make($this->all(), $rules)->validate(); });
\Illuminate\Support\Facades\Log::swap(Mockery::mock(\Psr\Log\LoggerInterface::class)->shouldIgnoreMissing());
$db->schema()->create('job_applications', function (Blueprint $table) { $table->increments('id'); $table->string('phone'); $table->timestamp('moved_to_trash_at')->nullable(); $table->softDeletes(); });
$db->schema()->create('sms_settings', function (Blueprint $table) { $table->increments('id'); $table->string('telnyx_api_key'); });
$db->table('sms_settings')->insert(['telnyx_api_key'=>'fake-telnyx-key']);
$db->schema()->create('company_settings', function (Blueprint $table) { $table->increments('id'); $table->timestamps(); });
(require __DIR__.'/../database/migrations/2026_09_28_000002_add_voice_settings_to_company_settings.php')->up();
(require __DIR__.'/../database/migrations/2026_09_29_000004_add_telnyx_webrtc_credential_id_to_company_settings.php')->up();
$db->table('company_settings')->insert(['id'=>1]);
$app['config']->set('candidate_calls.enabled', true);
$app['config']->set('candidate_calls.credential_id', 'legacy-credential');
$app['config']->set('candidate_calls.from_number', '+14165550199');
check(CandidateCallService::voiceSettings()['credential_id'] === 'legacy-credential', 'Existing environment settings work until account settings are saved');
$db->table('company_settings')->where('id', 1)->update(['candidate_calls_enabled'=>false]);
check(!CandidateCallService::voiceSettings()['enabled'], 'Account disable overrides an enabled environment setting');
$db->table('company_settings')->where('id', 1)->update(['candidate_calls_enabled'=>true, 'telnyx_voice_credential_id'=>'saved-credential', 'telnyx_voice_from_number'=>'+14165550188']);
check(CandidateCallService::voiceSettings()['credential_id'] === 'saved-credential' && CandidateCallService::voiceSettings()['from_number'] === '+14165550188', 'Saved account voice values override environment values');
$settingsRules = (new \App\Http\Requests\Company\UpdateCompany())->rules();
$validSettings = ['company_name'=>'Test', 'company_email'=>'test@example.test', 'candidate_calls_enabled'=>'1', 'telnyx_voice_credential_id'=>'saved-credential', 'telnyx_voice_from_number'=>'+14165550188'];
check($validator->make($validSettings, $settingsRules)->passes(), 'Account settings accept a valid credential and international number');
check($validator->make(array_replace($validSettings, ['telnyx_voice_credential_id'=>'', 'telnyx_voice_from_number'=>'555']), $settingsRules)->fails(), 'Enabled calling rejects missing credential and invalid number');
check($validator->make(array_replace($validSettings, ['candidate_calls_enabled'=>'0', 'telnyx_voice_credential_id'=>null, 'telnyx_voice_from_number'=>null]), $settingsRules)->passes(), 'Calling can be disabled without credentials');
(require __DIR__.'/../database/migrations/2026_09_28_000001_create_candidate_calls_table.php')->up();
$db->table('job_applications')->insert(['id'=>1, 'phone'=>'4165550101']);
$db->table('job_applications')->insert(['id'=>2, 'phone'=>'4165550102', 'moved_to_trash_at'=>'2026-01-01']);
class CallTestController extends CandidateCallController {
    public function __construct(int $userId = 1, bool $allowed = true) {
        $this->user = new class($userId, $allowed) {
            public function __construct(public int $id, private bool $allowed) {}
            public function cans($permission) { return $this->allowed; }
        };
    }
}
function check($value, $label) { if (!$value) throw new RuntimeException($label); echo 'PASS: '.$label.PHP_EOL; }
function rejects($fn, $code, $label) {
    try { $fn(); } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) { check($e->getStatusCode() === $code, $label); return; }
    catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) { check($code === 404, $label); return; }
    throw new RuntimeException($label);
}
$service = new class extends CandidateCallService {
    public function session(): array { return ['token'=>'test-token', 'from'=>'+14165550100']; }
};
$c = new CallTestController();
$phones = new \App\Services\TelnyxSmsService();
rejects(fn () => (new CallTestController(1, false))->start(1, $service, $phones), 403, 'Unauthorized caller blocked before token issuance');
rejects(fn () => $c->start(2, $service, $phones), 404, 'Trashed candidates cannot be called');
$started = $c->start(1, $service, $phones)->getData(true);
$call = CandidateCall::findOrFail($started['id']);
check($call->phone === '+14165550101' && $call->user_id === 1, 'Call linked to candidate and recruiter');
rejects(fn () => (new CallTestController(2))->finish(Request::create('/', 'POST', ['duration_seconds'=>1]), 1, $call->id), 404, 'Another recruiter cannot modify this call');
$c->finish(Request::create('/', 'POST', ['duration_seconds'=>15]), 1, $call->id);
$c->finish(Request::create('/', 'POST', ['duration_seconds'=>99]), 1, $call->id);
check($call->fresh()->status === 'not_recorded' && $call->fresh()->duration_seconds === 15, 'No recording is honest and duplicate finish is idempotent');

$disk = Mockery::mock();
$disk->shouldReceive('readStream')->andReturnUsing(function () { $stream = fopen('php://temp', 'r+'); fwrite($stream, 'fake audio'); rewind($stream); return $stream; });
$disk->shouldReceive('delete')->with('calls/test.webm')->once()->andReturn(true);
$storage = Mockery::mock();
$storage->shouldReceive('disk')->with('candidate_call_audio')->andReturn($disk);
Storage::swap($storage);
$call->update(['status'=>'pending', 'audio_path'=>'calls/test.webm']);
$processor = new CandidateCallService();
check($processor->aiKey() === 'fake-deepseek-key', 'Summaries reuse the existing ATS DeepSeek key');
Http::swap(new \Illuminate\Http\Client\Factory());
Http::preventStrayRequests();
$transcriptionRequests = 0;
Http::fake([
    'api.telnyx.com/v2/ai/audio/transcriptions'=>function ($request) use (&$transcriptionRequests) {
        check((bool) preg_match('/name="model"[^\r\n]*\r\n(?:[^\r\n]+\r\n)*\r\nnvidia\/parakeet-v3\r\n/', $request->body()), 'Transcription explicitly requests NVIDIA Parakeet');
        return ++$transcriptionRequests === 1 ? Http::response(['error'=>'temporary'], 503) : Http::response(['text'=>'Candidate available Monday.']);
    },
    'api.deepseek.com/chat/completions'=>Http::sequence()->push(['error'=>'temporary'], 503)->push(['choices'=>[['message'=>['content'=>'Available Monday.']]]]),
]);
check($c->process(1, $call->id, $processor)->getStatusCode() === 502, 'Transcription failure is retryable');
check(!$call->fresh()->transcript && $call->fresh()->audio_path === 'calls/test.webm', 'Audio preserved when Telnyx transcription fails');
Http::assertNotSent(fn ($request) => str_contains($request->url(), 'deepseek.com'));
check($c->process(1, $call->id, $processor)->getStatusCode() === 502, 'AI failure reported without losing saved recording');
check($call->fresh()->transcript === 'Candidate available Monday.' && $call->fresh()->status === 'failed', 'Transcript retained for summary retry');
check($c->process(1, $call->id, $processor)->getStatusCode() === 200, 'Retry completes');
check($call->fresh()->summary === 'Available Monday.' && !$call->fresh()->audio_path, 'Summary persisted and private audio removed');
$c->process(1, $call->id, $processor);
Http::assertSentCount(4);
Http::assertSent(fn ($request) => $request->url() === 'https://api.telnyx.com/v2/ai/audio/transcriptions'
    && $request->hasHeader('Authorization', 'Bearer fake-telnyx-key'));
Http::assertSent(fn ($request) => $request->url() === 'https://api.deepseek.com/chat/completions'
    && $request->hasHeader('Authorization', 'Bearer fake-deepseek-key') && $request['model'] === 'existing-ats-model'
    && $request['messages'][1]['content'] === 'Candidate available Monday.');
Http::assertNotSent(fn ($request) => str_contains($request->url(), 'openai.com'));
check(true, 'Telnyx handles audio; DeepSeek handles text with its existing model; no OpenAI requests');
check(true, 'Completed retries do not call AI again');
$call->refresh()->update(['status'=>'processing', 'audio_path'=>'calls/test.webm']);
check($c->process(1, $call->id, $processor)->getStatusCode() === 409, 'Concurrent processing blocked');
Http::fake([
    'api.telnyx.com/v2/telephony_credentials'=>Http::response(['data'=>['id'=>'generated-webrtc-credential']], 201),
    'api.telnyx.com/v2/telephony_credentials/generated-webrtc-credential/token'=>Http::response('voice-token'),
]);
$app['config']->set('services.deepseek.key', null);
$voiceSession = $processor->session();
check($voiceSession['token'] === 'voice-token', 'Calling works without an AI key; only summaries require AI');
check($voiceSession['token'] === 'voice-token' && $voiceSession['from'] === '+14165550188', 'New voice sessions use saved account settings');
check($db->table('company_settings')->value('telnyx_webrtc_credential_id') === 'generated-webrtc-credential', 'Provisioned WebRTC credential ID is stored server-side');
Http::assertSent(fn ($request) => $request->url() === 'https://api.telnyx.com/v2/telephony_credentials'
    && $request['connection_id'] === 'saved-credential' && $request['name'] === 'ATS Browser Calling'
    && $request->hasHeader('Authorization', 'Bearer fake-telnyx-key'));
Http::assertSent(fn ($request) => $request->url() === 'https://api.telnyx.com/v2/telephony_credentials/generated-webrtc-credential/token'
    && $request->hasHeader('Authorization', 'Bearer fake-telnyx-key'));
Mockery::close();
