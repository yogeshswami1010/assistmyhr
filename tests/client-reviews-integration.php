<?php
// Uses an isolated SQLite database and a fake mailer. Never connects to SMTP or ATS data.
$root = dirname(__DIR__);
$autoload = $argv[1] ?? $root.'/vendor/autoload.php';
if (!is_file($autoload)) { fwrite(STDERR, "Provide a Composer vendor/autoload.php path.\n"); exit(1); }
$loader = require $autoload;
$loader->setPsr4('App\\', $root.'/app');
require_once $root.'/app/Services/ClientReviewContent.php';
require_once $root.'/app/Services/CandidateClientReviewService.php';
require_once $root.'/app/CandidateClientReview.php';
require_once $root.'/app/CandidateClientReviewMessage.php';
require_once $root.'/app/Exceptions/Handler.php';
require_once $root.'/app/Http/Controllers/Admin/CandidateClientReviewController.php';

use Illuminate\Foundation\Application;
use Illuminate\Config\Repository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Facade;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\CandidateClientReview;
use App\CandidateClientReviewMessage;
use App\Services\ClientReviewContent;
use App\Services\CandidateClientReviewService;

$temporary = sys_get_temp_dir().'/ats-client-review-test-'.bin2hex(random_bytes(6));
mkdir($temporary, 0700, true);
$app = new Application($root);
$app->instance('config', new Repository([
    'app' => ['key' => 'client-review-test-key-only', 'url' => 'https://ats.example.test', 'timezone' => 'Asia/Calcutta', 'debug' => false],
    'database' => ['default' => 'sqlite', 'connections' => ['sqlite' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true]]],
    'view' => ['paths' => [$root.'/resources/views'], 'compiled' => $temporary],
    'filesystems' => ['default' => 'local', 'disks' => ['local' => ['driver' => 'local', 'root' => $temporary.'/files', 'throw' => true]]],
    'mail' => ['ai_search_smtp' => ['from' => ['address' => 'hr@example.test']]],
    'session' => ['driver' => 'array'],
]));
Facade::setFacadeApplication($app);
$app->instance(Illuminate\Contracts\Debug\ExceptionHandler::class, new class implements Illuminate\Contracts\Debug\ExceptionHandler {
    public function report(Throwable $e) {}
    public function shouldReport(Throwable $e) { return true; }
    public function render($request, Throwable $e) { throw $e; }
    public function renderForConsole($output, Throwable $e) { throw $e; }
});
$app->register(Illuminate\Events\EventServiceProvider::class);
$app->register(Illuminate\Database\DatabaseServiceProvider::class);
$app->register(Illuminate\Filesystem\FilesystemServiceProvider::class);
$app->register(Illuminate\View\ViewServiceProvider::class);
$app->register(Illuminate\Routing\RoutingServiceProvider::class);
$app->register(Illuminate\Translation\TranslationServiceProvider::class);
$app->register(Illuminate\Validation\ValidationServiceProvider::class);
$app->register(Illuminate\Session\SessionServiceProvider::class);
$app->boot();
$app->instance('request', Request::create('https://ats.example.test'));
$app['view']->share('errors', new Illuminate\Support\ViewErrorBag());
(new Illuminate\Foundation\Providers\FoundationServiceProvider($app))->registerRequestSignatureValidation();
$app['router']->aliasMiddleware('signed', Illuminate\Routing\Middleware\ValidateSignature::class);
$app['router']->aliasMiddleware('bindings', Illuminate\Routing\Middleware\SubstituteBindings::class);
$app['router']->middleware(['signed', 'bindings'])->prefix('candidate-review')->name('client-reviews.')->group(function ($router) {
    $router->get('{review:public_id}', [App\Http\Controllers\ClientCandidateReviewController::class, 'show'])->name('show');
    $router->get('{review:public_id}/cv', [App\Http\Controllers\ClientCandidateReviewController::class, 'resume'])->name('resume');
    $router->post('{review:public_id}/reply', [App\Http\Controllers\ClientCandidateReviewController::class, 'reply'])->name('reply');
});
$app['router']->get('admin/job-applications/table-view', fn () => 'ATS')->name('admin.job-applications.table');
$app['router']->getRoutes()->refreshNameLookups();
$app['url']->setKeyResolver(fn () => 'client-review-test-key-only');
Schema::create('users', function (Blueprint $table) {
    $table->increments('id'); $table->string('name'); $table->string('email');
    $table->text('email_signature')->nullable(); $table->text('email_signature_html')->nullable(); $table->string('email_signature_image')->nullable(); $table->timestamps();
});
Schema::create('job_applications', function (Blueprint $table) {
    $table->increments('id'); $table->string('full_name'); $table->unsignedInteger('job_id')->nullable(); $table->timestamp('deleted_at')->nullable(); $table->timestamp('moved_to_trash_at')->nullable(); $table->timestamps();
});
Schema::create('jobs', function (Blueprint $table) { $table->increments('id'); $table->string('title'); });
Schema::create('documents', function (Blueprint $table) {
    $table->increments('id'); $table->unsignedInteger('documentable_id'); $table->string('documentable_type');
    $table->string('name'); $table->string('hashname'); $table->string('original_name'); $table->timestamps();
});
$migration = require $root.'/database/migrations/2026_10_05_000001_create_candidate_client_reviews.php';
$migration->up();
$skipMigration = require $root.'/database/migrations/2026_10_05_000002_add_notification_skip_to_client_reviews.php';
$skipMigration->up();
$clientMessageMigration = require $root.'/database/migrations/2026_10_05_000003_add_client_message_to_candidate_client_reviews.php';
$clientMessageMigration->up();
DB::table('users')->insert(['id' => 1, 'name' => 'Team Member', 'email' => 'team@example.test', 'email_signature_html' => '<strong>Team signature</strong>']);
DB::table('jobs')->insert(['id' => 1, 'title' => 'Developer']);
DB::table('job_applications')->insert(['id' => 42, 'full_name' => 'Sample Candidate', 'job_id' => 1]);
DB::table('job_applications')->insert(['id' => 43, 'full_name' => 'Another Candidate', 'job_id' => 1]);
DB::table('documents')->insert(['documentable_id' => 43, 'documentable_type' => App\JobApplication::class, 'name' => 'Resume', 'hashname' => 'shared.pdf', 'original_name' => 'Candidate CV.pdf']);
Illuminate\Support\Facades\Storage::put('documents/43/shared.pdf', "%PDF-1.4\nTest CV only");
Request::macro('validate', function ($rules) { return app('validator')->make($this->all(), $rules)->validate(); });
function check($ok, $description) { if (!$ok) throw new RuntimeException($description); }
function rejected(callable $fn, string $class) { try { $fn(); } catch (Throwable $e) { check($e instanceof $class, $e::class.': '.$e->getMessage()); return; } throw new RuntimeException('Expected rejection'); }

$html = ClientReviewContent::decode(base64_encode('<p><b>Candidate</b> <i>summary</i></p><ul><li>Qualified</li></ul><font face="Georgia" size="4">Details</font><script>bad()</script><img src="https://tracking.test/pixel"><a href="javascript:bad()" onclick="bad()">Read</a>'));
check(str_contains($html, '<b>Candidate</b>') && str_contains($html, '<li>Qualified</li>') && str_contains($html, 'Georgia'), 'Retain formatting and lists');
check(!str_contains($html, '<script') && !str_contains($html, '<img') && !str_contains($html, 'javascript:') && !str_contains($html, 'onclick'), 'Remove executable content and tracking images');
rejected(fn () => ClientReviewContent::decode('%%%'), Illuminate\Validation\ValidationException::class);
rejected(fn () => ClientReviewContent::decode(base64_encode('<p>&nbsp;</p>')), Illuminate\Validation\ValidationException::class);

function invitation(int $candidate, string $email): CandidateClientReview {
    return CandidateClientReview::create([
        'public_id' => (string) Str::uuid(), 'job_application_id' => $candidate, 'user_id' => 1,
        'client_email' => $email, 'subject' => 'Candidate review', 'candidate_name' => 'Sample Candidate', 'job_title' => 'Developer',
        'resume_hashname' => 'shared.pdf', 'resume_original_name' => 'Candidate CV.pdf',
        'body_html' => '<p><b>Our introduction</b></p>', 'expires_at' => now()->addDays(30),
    ]);
}
$review = invitation(42, 'client@example.test');
$other = invitation(43, 'other@example.test');
check($review->fresh()->client_message === null, 'Reviews without a client message remain compatible');
$outgoing = $review->messages()->create(['submission_id' => $review->public_id, 'user_id' => 1, 'direction' => 'outbound', 'body_html' => $review->body_html, 'body_text' => 'Our introduction']);
$sender = App\User::findOrFail(1);
class FakeReviewMailer {
    public array $sent = [];
    public bool $fail = false;
    public function html($html, $callback) {
        if ($this->fail) throw new RuntimeException('Test transport unavailable');
        $mail = new class {
            public array $addresses = [];
            public function to($email) { $this->addresses['to'] = $email; return $this; }
            public function replyTo($email, $name = null) { $this->addresses['reply'] = $email; return $this; }
            public function subject($subject) { $this->addresses['subject'] = $subject; return $this; }
        };
        $callback($mail); $this->sent[] = ['html' => $html] + $mail->addresses;
    }
}
$transport = new FakeReviewMailer();
$service = new class($transport) extends CandidateClientReviewService {
    public function __construct(private FakeReviewMailer $transport) {}
    protected function mailer() { return $this->transport; }
};
$service->send($review, $outgoing, $sender);
$review->refresh();
check($review->isAvailable(), 'Successful delivery enables the review');
check($outgoing->fresh()->mail_status === 'sent' && count($transport->sent) === 1, 'Store delivery status');
check($transport->sent[0]['to'] === 'client@example.test' && str_contains($transport->sent[0]['html'], '<b>Our introduction</b>') && str_contains($transport->sent[0]['html'], 'View candidate &amp; give review'), 'Send rich text with review button to client');
$service->send($review, $outgoing, $sender);
check(count($transport->sent) === 1, 'A repeated send request must not duplicate the email');
$url = $service->url($review);
check(URL::hasValidSignature(Request::create($url)), 'Generate a valid signed private link');
check(!URL::hasValidSignature(Request::create(str_replace($review->public_id, $other->public_id, $url))), 'Another client cannot access another review by changing the URL');
check(URL::hasValidSignature(Request::create($service->url($review, 'resume'))), 'The CV has its own valid signed route');
$controller = new App\Http\Controllers\ClientCandidateReviewController();
$page = $controller->show($review, $service)->getContent();
check(str_contains($page, 'Candidate CV') && str_contains($page, 'Send review'), 'Existing links still show the CV and feedback form');
check(!str_contains($page, 'Our introduction') && !str_contains($page, 'Message from the recruitment team') && !str_contains($page, '<h2>Client message</h2>'), 'Remove the recruitment introduction and hide blank client-message cards');
check(!str_contains($page, 'other@example.test') && !str_contains($page, 'Applicant Notes'), 'Do not expose other clients or internal profile tabs');

$incoming = $review->messages()->firstOrCreate(['submission_id' => (string) Str::uuid()], ['direction' => 'inbound', 'body_text' => 'Please arrange an interview.', 'mail_status' => 'received']);
$same = $review->messages()->firstOrCreate(['submission_id' => $incoming->submission_id], ['direction' => 'inbound', 'body_text' => 'Repeat', 'mail_status' => 'received']);
check($same->id === $incoming->id, 'Repeated submissions are idempotent');
check(CandidateClientReviewMessage::whereHas('review', fn ($q) => $q->where('job_application_id', 42))->where('direction', 'inbound')->whereNull('read_at')->count() === 1, 'Client reply belongs to the correct candidate and appears unread');
$transport->fail = true;
rejected(fn () => $service->notify($review, $incoming), RuntimeException::class);
check($incoming->fresh()->notification_sent_at === null && $incoming->fresh()->body_text === 'Please arrange an interview.', 'SMTP failure must not lose client feedback');
$transport->fail = false;
$service->notify($review, $incoming);
$service->notify($review, $incoming);
check(count($transport->sent) === 2 && $incoming->fresh()->notification_sent_at !== null, 'Retry notification once without duplicate mail');
check($transport->sent[1]['to'] === 'hr@example.test' && str_contains($transport->sent[1]['html'], 'Please arrange an interview.'), 'Notify only the ATS SMTP mailbox, including notification retries');
check($transport->sent[1]['reply'] === 'client@example.test', 'The ATS mailbox can reply directly to the client');
$conversation = view('admin.job-applications.partials.client-review-conversation', ['reviews' => collect([$review->load('messages.user')]), 'canEdit' => true, 'applicationId' => 42])->render();
check(str_contains($conversation, 'Please arrange an interview.') && str_contains($conversation, 'Team Member') && str_contains($conversation, 'Reply to client'), 'Render both sides of the conversation in ATS');
$reply = $review->messages()->create(['submission_id' => (string) Str::uuid(), 'user_id' => 1, 'direction' => 'outbound', 'body_html' => '<p>Interview scheduled</p>', 'body_text' => 'Interview scheduled']);
$service->send($review, $reply, $sender);
check(count($transport->sent) === 3, 'Staff can reply within the same conversation');

// Exercise the actual admin and client submission controllers with Laravel validation.
$admin = (new ReflectionClass(App\Http\Controllers\Admin\CandidateClientReviewController::class))->newInstanceWithoutConstructor();
$actor = new class {
    public int $id = 1;
    public bool $allowed = true;
    public function cans($permission) { return $this->allowed; }
};
(new ReflectionProperty(App\Http\Controllers\Admin\AdminBaseController::class, 'user'))->setValue($admin, $actor);
$clientInstructions = "Please review José’s experience.\nConfirm availability <script>alert('x')</script>";
$sendData = ['client_email' => 'Second.Client@example.test', 'subject' => 'Please review', 'message_payload' => base64_encode('<p><b>Qualified candidate</b></p>'), 'client_message' => $clientInstructions, 'submission_id' => (string) Str::uuid()];
$sendRequest = Request::create('https://ats.example.test/admin/send', 'POST', $sendData);
$result = $admin->send($sendRequest, 43, $service)->getData(true);
$sentReview = CandidateClientReview::findOrFail($result['review_id']);
check($sentReview->client_email === 'second.client@example.test' && $sentReview->job_application_id === 43 && $sentReview->resume_hashname === 'shared.pdf', 'Admin send stores the correct client, candidate and shared CV');
check($sentReview->client_message === $clientInstructions, 'Store the client message separately from the email body');
$clientPage = $controller->show($sentReview, $service)->getContent();
check(strpos($clientPage, '<h2>Client message</h2>') > strpos($clientPage, '<aside>') && strpos($clientPage, '<h2>Client message</h2>') < strpos($clientPage, '<h2>Reply with your review</h2>'), 'Client instructions appear above the reply section in the right column');
check(str_contains($clientPage, htmlspecialchars($clientInstructions, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) && !str_contains($clientPage, '<script>'), 'Render client instructions safely with Unicode and line breaks intact');
check(!str_contains($clientPage, 'Message from the recruitment team') && !str_contains($clientPage, 'Qualified candidate'), 'The invitation email body is not displayed as a recruitment message on the review page');
check(str_contains(end($transport->sent)['html'], 'Qualified candidate') && !str_contains(end($transport->sent)['html'], 'Confirm availability'), 'The invitation email retains its own message rather than the private-page instructions');
$admin->send($sendRequest, 43, $service);
check(count($transport->sent) === 4, 'Double-clicking the send endpoint cannot send the invitation twice');
$actor->allowed = false;
rejected(fn () => $admin->send($sendRequest, 43, $service), Symfony\Component\HttpKernel\Exception\HttpException::class);
$actor->allowed = true;
rejected(fn () => $admin->send(Request::create('/send', 'POST', $sendData + ['unused' => true]), 42, $service), Illuminate\Validation\ValidationException::class);
rejected(fn () => $admin->send(Request::create('/send', 'POST', array_replace($sendData, ['client_email' => 'invalid'])), 43, $service), Illuminate\Validation\ValidationException::class);
rejected(fn () => $admin->send(Request::create('/send', 'POST', array_replace($sendData, ['client_message' => str_repeat('x', 10001)])), 43, $service), Illuminate\Validation\ValidationException::class);
rejected(fn () => $admin->send(Request::create('/send', 'POST', array_replace($sendData, ['client_message' => ['invalid']])), 43, $service), Illuminate\Validation\ValidationException::class);
$clientData = ['message' => 'Client approved this candidate.', 'submission_id' => (string) Str::uuid()];
$clientRequest = Request::create($service->url($sentReview, 'reply'), 'POST', $clientData);
$controller->reply($clientRequest, $sentReview, $service);
$controller->reply($clientRequest, $sentReview, $service);
check($sentReview->messages()->where('direction', 'inbound')->count() === 1 && count($transport->sent) === 5, 'Submitting feedback twice stores and emails it once');
check(end($transport->sent)['to'] === 'hr@example.test', 'Send review emails only the ATS mailbox rather than the inviting member or client');
$loaded = $admin->index(43)->getData(true);
check(str_contains($loaded['view'], 'Client approved this candidate.'), 'Submitted feedback appears in the actual ATS conversation endpoint');
check($admin->unread(43)->getData(true)['unread'] === 0, 'Reading the conversation clears its unread badge');
$crossRequest = Request::create('/reply', 'POST', ['message_payload' => base64_encode('Wrong profile'), 'submission_id' => (string) Str::uuid()]);
rejected(fn () => $admin->reply($crossRequest, 42, $sentReview, $service), Symfony\Component\HttpKernel\Exception\HttpException::class);
$transport->fail = true;
$failed = $admin->send(Request::create('/send', 'POST', array_replace($sendData, ['submission_id' => (string) Str::uuid(), 'client_email' => 'failed@example.test'])), 43, $service);
check($failed->getStatusCode() === 422 && $failed->getData(true)['saved'], 'Failed email remains saved and is reported honestly');
$failedReview = CandidateClientReview::where('client_email', 'failed@example.test')->firstOrFail();
$failedMessage = $failedReview->messages()->firstOrFail();
check($failedMessage->mail_status === 'failed' && !$failedReview->isAvailable(), 'Unsent invitations do not expose the CV');
$transport->fail = false;
$admin->retry(43, $failedReview, $failedMessage->id, $service);
$admin->retry(43, $failedReview, $failedMessage->id, $service);
check($failedReview->fresh()->isAvailable() && count($transport->sent) === 6, 'Retrying delivery activates the review once');
$cv = $controller->resume($sentReview);
check(str_contains($cv->headers->get('Content-Disposition'), 'inline;') && $cv->headers->get('X-Robots-Tag') === 'noindex, nofollow, noarchive', 'Serve the shared CV privately');
$admin->revoke(43, $sentReview);
rejected(fn () => $controller->resume($sentReview->fresh()), Symfony\Component\HttpKernel\Exception\HttpException::class);
$review->update(['revoked_at' => now()]);
check(!$review->fresh()->isAvailable(), 'Revocation blocks the private page and CV');
rejected(fn () => $controller->show($review->fresh(), $service), Symfony\Component\HttpKernel\Exception\HttpException::class);
$review->update(['revoked_at' => null, 'expires_at' => now()->subMinute()]);
check(!$review->fresh()->isAvailable() && !URL::hasValidSignature(Request::create($service->url($review->fresh()))), 'Expired links cannot be used');
$review->update(['expires_at' => now()->addDay()]);
DB::table('job_applications')->where('id', 42)->update(['moved_to_trash_at' => now()]);
check(!$review->fresh()->isAvailable(), 'Trashing the candidate removes external access');

// A client may use the same email as the inviting team member during testing.
// Their portal feedback must never be delivered back to the client address.
$selfReview = invitation(43, 'TEAM@EXAMPLE.TEST');
$selfReview->update(['sent_at' => now()]);
$beforeSelfReply = count($transport->sent);
$selfRequest = Request::create($service->url($selfReview, 'reply'), 'POST', [
    'message' => 'My feedback should go to ATS only.', 'submission_id' => (string) Str::uuid(),
]);
$controller->reply($selfRequest, $selfReview, $service);
check(count($transport->sent) === $beforeSelfReply + 1 && end($transport->sent)['to'] === 'hr@example.test', 'Matching client and team emails must route the notification to the ATS mailbox');
check(strtolower(end($transport->sent)['to']) !== strtolower($selfReview->client_email), 'Client must not receive their own submitted review');
$selfMessage = $selfReview->messages()->where('direction', 'inbound')->firstOrFail();
rejected(fn () => $service->send($selfReview, $selfMessage, $sender), LogicException::class);
check(count($transport->sent) === $beforeSelfReply + 1, 'Incoming feedback must never enter the outbound client email flow');

$app['config']->set('mail.ai_search_smtp.from.address', 'team@example.test');
$beforeSkipped = count($transport->sent);
$skipRequest = Request::create($service->url($selfReview, 'reply'), 'POST', [
    'message' => 'Save this in ATS without emailing me.', 'submission_id' => (string) Str::uuid(),
]);
$controller->reply($skipRequest, $selfReview, $service);
$skipped = $selfReview->messages()->where('submission_id', $skipRequest->input('submission_id'))->firstOrFail();
check(count($transport->sent) === $beforeSkipped, 'No email is sent when the ATS mailbox matches the client');
check($skipped->notification_sent_at === null && $skipped->notification_skipped_at !== null && $skipped->body_text === 'Save this in ATS without emailing me.', 'Record an honest skip while preserving the feedback');
$service->notify($selfReview, $skipped);
check(count($transport->sent) === $beforeSkipped, 'Notification retries cannot send the client a copy');
check(!CandidateClientReviewMessage::whereKey($skipped->id)->whereNull('notification_sent_at')->whereNull('notification_skipped_at')->exists(), 'Scheduled notification retries exclude skipped messages');
$app['config']->set('mail.ai_search_smtp.from.address', 'hr@example.test');

// A missing or invalid ATS mailbox must not route feedback to another address.
foreach ([null, 'not-an-email'] as $mailbox) {
    $app['config']->set('mail.ai_search_smtp.from.address', $mailbox);
    $beforeMissing = count($transport->sent);
    $missingRequest = Request::create($service->url($failedReview, 'reply'), 'POST', [
        'message' => 'Preserve feedback when the ATS mailbox is unavailable.', 'submission_id' => (string) Str::uuid(),
    ]);
    $controller->reply($missingRequest, $failedReview, $service);
    $missingMessage = $failedReview->messages()->where('submission_id', $missingRequest->input('submission_id'))->firstOrFail();
    check(count($transport->sent) === $beforeMissing && $missingMessage->notification_skipped_at !== null, 'Do not fall back to the valid team member email when the ATS mailbox is unavailable');
    check(str_contains($admin->index(43)->getData(true)['view'], $missingMessage->body_text), 'Feedback still appears in the ATS conversation without an email recipient');
}
$app['config']->set('mail.ai_search_smtp.from.address', 'hr@example.test');

// Exercise the real routes and exception renderer, including signature middleware.
// The unavailable page must replace debug output without bypassing access checks.
$app->instance(CandidateClientReviewService::class, $service);
$app['config']->set('app.debug', true);
$handler = new App\Exceptions\Handler($app);
$clientResponse = function (string $url, string $method = 'GET', array $data = []) use ($app, $handler) {
    $request = Request::create($url, $method, $data);
    $app->instance('request', $request);
    try { return $app['router']->dispatch($request); }
    catch (Throwable $exception) { return $handler->render($request, $exception); }
};
$friendly = function ($response, int $status) {
    check($response->getStatusCode() === $status, 'Keep the unavailable link HTTP status');
    $body = $response->getContent();
    check(str_contains($body, 'This review link is no longer available') && str_contains($body, 'contact the recruitment team for a new link'), 'Render the friendly unavailable page');
    check(!str_contains($body, 'Sample Candidate') && !str_contains($body, 'Please arrange an interview.') && !str_contains($body, 'HttpException') && !str_contains($body, 'Stack trace'), 'Do not leak candidate details or debug output');
    check(str_contains($response->headers->get('Cache-Control'), 'no-store') && $response->headers->get('X-Robots-Tag') === 'noindex, nofollow, noarchive', 'Unavailable links stay private and uncached');
};
$blockedReview = invitation(43, 'review-client@example.test');
$blockedReview->update(['sent_at' => now()]);
$blockedUrl = $service->url($blockedReview);
$blockedCvUrl = $service->url($blockedReview, 'resume');
$blockedReplyUrl = $service->url($blockedReview, 'reply');
$activeResponse = $clientResponse($blockedUrl);
check($activeResponse->getStatusCode() === 200, 'Valid review links still show the candidate: '.$activeResponse->getStatusCode());
$blockedReview->update(['revoked_at' => now()]);
$friendly($clientResponse($blockedUrl), 410);
$friendly($clientResponse($blockedCvUrl), 410);
$beforeBlocked = $blockedReview->messages()->count();
$friendly($clientResponse($blockedReplyUrl, 'POST', ['message' => 'Too late', 'submission_id' => (string) Str::uuid()]), 410);
check($blockedReview->messages()->count() === $beforeBlocked, 'Revoked links cannot submit feedback');
$blockedReview->update(['revoked_at' => null, 'expires_at' => now()->subMinute()]);
$friendly($clientResponse($blockedUrl), 410); // An originally valid signature, now expired in the database.
$friendly($clientResponse($service->url($blockedReview->fresh())), 403); // Expired signed URL.
$friendly($clientResponse(str_replace('signature=', 'signature=invalid', $blockedUrl)), 403);
$missingUrl = URL::temporarySignedRoute('client-reviews.show', now()->addDay(), ['review' => (string) Str::uuid()]);
$friendly($clientResponse($missingUrl), 404);
$adminRequest = Request::create('https://ats.example.test/admin/job-applications/table-view', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
$adminRequest->setRouteResolver(fn () => $app['router']->getRoutes()->match($adminRequest));
$unrelated = $handler->render($adminRequest, new Symfony\Component\HttpKernel\Exception\HttpException(410, 'Unrelated admin error'));
check($unrelated->getStatusCode() === 410 && str_contains($unrelated->getContent(), 'Unrelated admin error') && !str_contains($unrelated->getContent(), 'contact the recruitment team'), 'Do not replace unrelated ATS errors with the review page');
$app['config']->set('app.debug', false);

// Compile every new Blade template with the installed framework, then lint PHP output.
foreach (array_merge(glob($root.'/resources/views/client-reviews/*.blade.php'), glob($root.'/resources/views/email/client-review*.blade.php'), glob($root.'/resources/views/admin/job-applications/partials/client-review*.blade.php'), [$root.'/resources/views/admin/job-applications/show.blade.php', $root.'/resources/views/admin/job-applications/index.blade.php']) as $path) {
    $app['blade.compiler']->compile($path);
    $compiled = $app['blade.compiler']->getCompiledPath($path);
    exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($compiled), $output, $status);
    check($status === 0, 'Blade must compile: '.$path);
}
$clientMessageMigration->down();
check(!Schema::hasColumn('candidate_client_reviews', 'client_message'), 'The client-message migration rolls back cleanly');
$skipMigration->down();
$migration->down();
check(!Schema::hasTable('candidate_client_reviews'), 'Migration rollback handles the foreign-key order');
echo "PASS: migration, rich text, signed access, CV page, conversation scoping, email delivery, notification retry, friendly revoked/expired/invalid links, and debug-mode privacy\n";
