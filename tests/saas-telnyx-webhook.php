<?php
// Reuse isolated SQLite workspaces; no live SMS, SMTP or Telnyx requests.
require __DIR__.'/saas-integration.php';

use App\ApplicantSmsMessage;
use App\Saas\PlatformSetting;
use App\Services\TelnyxSmsService;
use Illuminate\Support\Facades\DB;

Carbon\Carbon::setTestNow('2026-10-06 12:00:00');
$checks = 0;
Illuminate\Support\Facades\Log::swap(Mockery::mock(Psr\Log\LoggerInterface::class)->shouldIgnoreMissing());
$signingKeys = sodium_crypto_sign_keypair();
$publicKey = base64_encode(sodium_crypto_sign_publickey($signingKeys));
PlatformSetting::where('key','telnyx_public_key')->update(['value'=>$publicKey]);
PlatformSetting::where('key','telephony.'.$gamma->id)->update(['value'=>json_encode(['sms_enabled'=>1,'sms_number'=>'+14165550199'])]);
$context->activate($alpha);
DB::table('job_applications')->update(['phone'=>'+14165559999']);
foreach ([[1,'+14165551234'],[2,'+14165550002']] as [$applicationId,$sender]) {
    ApplicantSmsMessage::create(['job_application_id'=>$applicationId,'user_id'=>1,'direction'=>'outbound',
        'from_number'=>$sender,'to_number'=>'+14165559999','message'=>'Original message','status'=>'sent']);
}
$context->activate($gamma);
DB::table('smtp_settings')->update(['mail_username'=>'gamma@example.test','mail_password'=>'GAMMA-SMTP']);
DB::table('job_applications')->insert([['id'=>1,'full_name'=>'Gamma candidate','phone'=>'+14165559999'],['id'=>2,'full_name'=>'Gamma direct contact','phone'=>'+14165559998']]);
ApplicantSmsMessage::create(['job_application_id'=>1,'user_id'=>1,'direction'=>'outbound',
    'from_number'=>'+14165550199','to_number'=>'+14165559999','message'=>'Other client conversation','status'=>'sent']);
$context->reset();
$app['router']->get('admin/job-applications/table',fn()=>null)->name('admin.job-applications.table');
$app['router']->getRoutes()->refreshNameLookups();
$webhookSession = new Illuminate\Session\Store('sms-test',new Illuminate\Session\ArraySessionHandler(120));
$webhookSession->start();$webhookSession->put('saas_tenant_id',$main->id);
class IsolatedTelnyxWebhookController extends App\Http\Controllers\TelnyxWebhookController { public function __construct() {} }
$webhookController = new IsolatedTelnyxWebhookController;
$makeWebhook = function(string $messageId, string $to = '+14165551234', string $path = '/telnyx-webhook', string $from = '+14165559999', int $age = 0, string $event = 'message.received') use ($signingKeys,$webhookSession,$app) {
    $body = json_encode(['data'=>['id'=>'event-'.$messageId,'event_type'=>$event,'payload'=>['id'=>$messageId,
        'from'=>['phone_number'=>$from],'to'=>[['phone_number'=>$to]],'text'=>'Hello, I am available tomorrow.']]]);
    $request = Illuminate\Http\Request::create('https://ats.example.test'.$path,'POST',[],[],[],['CONTENT_TYPE'=>'application/json'],$body);
    $timestamp = (string)(time()-$age);
    $request->headers->set('telnyx-timestamp',$timestamp);
    $request->headers->set('telnyx-signature-ed25519',base64_encode(sodium_crypto_sign_detached($timestamp.'|'.$body,sodium_crypto_sign_secretkey($signingKeys))));
    $request->setLaravelSession($webhookSession);
    $app->instance('request',$request);$app['url']->setRequest($request);
    return $request;
};
$receive = fn($request)=>$middleware->handle($request,fn($request)=>$webhookController($request,new TelnyxSmsService));
$invalid = $makeWebhook('unsigned');$invalid->headers->remove('telnyx-signature-ed25519');
check($receive($invalid)->getStatusCode()===401&&$context->current()===null,'Unsigned SMS is rejected before tenant activation');
$invalid = $makeWebhook('short-signature');$invalid->headers->set('telnyx-signature-ed25519',base64_encode('short'));
check($receive($invalid)->getStatusCode()===401,'Malformed signatures return 401 rather than a Sodium exception');
$invalid = $makeWebhook('tampered');$invalid->headers->set('telnyx-signature-ed25519',base64_encode(str_repeat('x',64)));
check($receive($invalid)->getStatusCode()===401,'Incorrect signatures cannot route a message');
check($receive($makeWebhook('stale',age:600))->getStatusCode()===401,'Stale signed requests are rejected');
check($receive($makeWebhook('unknown-number',to:'+14165550003'))->getStatusCode()===403,'Unassigned receiving numbers never fall back to main');
check($receive($makeWebhook('wrong-workspace',path:'/telnyx-webhook?workspace=client-gamma'))->getStatusCode()===403,'Explicit workspace must match the receiving number');
check($receive($makeWebhook('outbound-event',event:'message.finalized'))->getStatusCode()===200,'Signed delivery events are acknowledged without creating incoming SMS');
check($receive($makeWebhook('alpha-first'))->getStatusCode()===200,'Shared webhook accepts a correctly signed client reply');
check($context->current()===null&&$webhookSession->get('saas_tenant_id')===$main->id,'Webhooks restore global context and preserve browser session selection');
$context->activate($alpha);
$reply = ApplicantSmsMessage::where('telnyx_message_id','alpha-first')->firstOrFail();
check($reply->job_application_id===1&&$reply->direction==='inbound'&&$reply->message==='Hello, I am available tomorrow.','Reply belongs to the matching sender pair rather than a newer conversation using another number');
$context->activate($gamma);
check(!ApplicantSmsMessage::where('telnyx_message_id','alpha-first')->exists(),'Another workspace with the same candidate phone cannot see this reply');
$context->reset();
check($receive($makeWebhook('alpha-first'))->getStatusCode()===200,'Provider retries are acknowledged');
$context->activate($alpha);check(ApplicantSmsMessage::where('telnyx_message_id','alpha-first')->count()===1,'Provider retries do not duplicate incoming SMS');$context->reset();
check($receive($makeWebhook('alpha-second',path:'/telnyx-webhook?workspace=alpha'))->getStatusCode()===200,'Existing workspace-specific webhook URLs still work');
check($receive($makeWebhook('gamma-first',to:'+14165550199'))->getStatusCode()===200,'Receiving number automatically selects the other client');
check($receive($makeWebhook('gamma-direct',to:'+14165550199',from:'+14165559998'))->getStatusCode()===200,'Direct candidate messages without previous outbound SMS are accepted');
$context->activate($gamma);
check(ApplicantSmsMessage::where('telnyx_message_id','gamma-direct')->value('job_application_id')===2,'Direct incoming SMS matches the candidate in its own workspace');
$context->reset();
PlatformSetting::create(['key'=>'telephony.999999','value'=>json_encode(['sms_enabled'=>1,'sms_number'=>'+14165551234'])]);
check($receive($makeWebhook('ambiguous'))->getStatusCode()===403,'Duplicate number assignments fail safely instead of selecting an arbitrary client');
PlatformSetting::where('key','telephony.999999')->delete();
$subscription=$alpha->fresh()->subscription;$savedExpiry=$subscription->expires_at;
$subscription->update(['expires_at'=>now()->subMinute()]);
check($receive($makeWebhook('expired'))->getStatusCode()===403,'Expired clients cannot bypass subscription checks through a webhook');
$subscription->update(['expires_at'=>$savedExpiry]);
PlatformSetting::where('key','telephony.'.$gamma->id)->update(['value'=>json_encode(['sms_enabled'=>0,'sms_number'=>'+14165550199'])]);
check($receive($makeWebhook('disabled',to:'+14165550199'))->getStatusCode()===403,'Clients with SMS disabled do not receive routed messages');
PlatformSetting::where('key','telnyx_public_key')->update(['value'=>'']);
check($receive($makeWebhook('no-public-key'))->getStatusCode()===401,'An API key alone cannot authenticate an incoming webhook');
PlatformSetting::where('key','telnyx_public_key')->update(['value'=>$publicKey]);
$context->activate($main);check(ApplicantSmsMessage::count()===0,'Main account receives none of the other clients messages');$context->reset();

$notifications = [];
$notifier=Mockery::mock(Illuminate\Contracts\Notifications\Dispatcher::class);
$notifier->shouldReceive('send')->times(3)->andReturnUsing(function($user,$notification) use (&$notifications,$context) {
    $notifications[]=['workspace'=>$context->current()?->slug,'username'=>config('mail.username'),'url'=>$notification->toMail($user)->actionUrl];
});
$app->instance(Illuminate\Contracts\Notifications\Dispatcher::class,$notifier);
$app->terminate();
check(count($notifications)===3,'Only fresh replies notify the recruiter who sent the matching conversation');
check($notifications[0]['workspace']==='alpha'&&$notifications[0]['username']==='alpha@example.test'&&str_contains($notifications[0]['url'],'workspace=alpha'),'Deferred Alpha notification restores its own SMTP and profile URL');
check($notifications[2]['workspace']===$gamma->slug&&$notifications[2]['username']==='gamma@example.test'&&str_contains($notifications[2]['url'],'workspace='.$gamma->slug),'Deferred other-client notification never uses Alpha or platform settings');
check($context->current()===null&&DB::getDefaultConnection()==='sqlite','Deferred notifications clean up their tenant context');
// The same signature verifier must preserve standalone ATS behavior.
config(['saas.enabled'=>false]);
DB::table('sms_settings')->updateOrInsert(['id'=>1],['telnyx_public_key'=>$publicKey]);
DB::table('job_applications')->where('id',1)->update(['phone'=>'+14165559997']);
check($receive($makeWebhook('standalone',from:'+14165559997'))->getStatusCode()===200,'Standalone ATS still accepts signed incoming SMS without SaaS assignments');
check(ApplicantSmsMessage::where('telnyx_message_id','standalone')->value('job_application_id')===1,'Standalone candidate phone matching is preserved');
DB::table('sms_settings')->where('id',1)->update(['telnyx_public_key'=>bin2hex(sodium_crypto_sign_publickey($signingKeys))]);
check($receive($makeWebhook('standalone-hex',from:'+14165559997'))->getStatusCode()===200,'Hex-encoded Telnyx public keys remain supported');
$invalid=$makeWebhook('standalone-unsigned',from:'+14165559997');$invalid->headers->remove('telnyx-signature-ed25519');
check($receive($invalid)->getStatusCode()===401,'Standalone ATS also rejects unsigned incoming messages');
Mockery::close();Carbon\Carbon::setTestNow();
echo "Telnyx webhook checks passed: $checks\n";
