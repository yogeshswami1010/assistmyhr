<?php
// Reuse the isolated OAuth/import suite. No real Microsoft credentials or requests.
require __DIR__.'/saas-outlook-replies.php';

use App\CandidateEmailMessage;
use App\CandidateReplyMailbox;
use App\Saas\AuditLog;
use App\Saas\PlatformSetting;
use App\Services\MicrosoftReplyAppSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

Carbon\Carbon::setTestNow('2026-10-06 12:00:00');
$checks = 0;
$appSettings = new MicrosoftReplyAppSettings;
$platform = new App\Http\Controllers\Saas\PlatformController;
Auth::guard('platform')->login($admin);
$alphaAppId = '11111111-1111-4111-8111-111111111111';
$gammaAppId = '22222222-2222-4222-8222-222222222222';
$changedAppId = '33333333-3333-4333-8333-333333333333';
$makeAppRequest = function (array $data) use ($makeRequest, $alpha) {
    $request = $makeRequest('POST', '/superadmin/tenants/'.$alpha->id.'/microsoft-inbox', $data + ['client_tab' => 'outlook']);
    $request->headers->set('referer', 'https://ats.example.test/superadmin/tenants/'.$alpha->id);
    return $request;
};
config(['services.microsoft_reply.client_id' => null, 'services.microsoft_reply.client_secret' => null]);
$request = $makeAppRequest(['microsoft_client_id' => $alphaAppId]);
rejected(fn () => $platform->saveClientMicrosoftInbox($request, $alpha), 'Initial client application setup requires its secret');
check(!PlatformSetting::where('key', 'microsoft_reply.'.$alpha->id)->exists(), 'Invalid setup does not save partial Microsoft credentials');
$request = $makeAppRequest(['microsoft_client_id' => 'not-a-microsoft-app-id', 'microsoft_client_secret' => 'do-not-flash-this-secret']);
try { $platform->saveClientMicrosoftInbox($request, $alpha); throw new RuntimeException('Invalid app ID accepted'); }
catch (Illuminate\Validation\ValidationException $e) {
    $handler = new App\Exceptions\Handler($app);
    $response = $handler->render($request, $e);
    $oldInput = $response->getSession()->getOldInput();
    check(!array_key_exists('microsoft_client_secret', $oldInput) && ($oldInput['microsoft_client_id'] ?? '') === 'not-a-microsoft-app-id', 'Validation can retain the app ID while excluding the secret from flashed input');
}
$request = $makeAppRequest(['microsoft_client_id' => ' '.$alphaAppId.' ', 'microsoft_client_secret' => ' ALPHA-APP-SECRET ']);
$saved = $platform->saveClientMicrosoftInbox($request, $alpha);
check($saved->getSession()->get('client_tab') === 'outlook' && str_contains($saved->getTargetUrl(), '/superadmin/tenants/'.$alpha->id), 'Saving returns Super Admin to the client Outlook tab');
$raw = PlatformSetting::valueFor('microsoft_reply.'.$alpha->id);
check(!str_contains($raw, 'ALPHA-APP-SECRET'), 'Client Microsoft secret is encrypted in the central settings store');
check($appSettings->forTenant($alpha) === ['client_id' => $alphaAppId, 'client_secret' => 'ALPHA-APP-SECRET'], 'Saved client credentials resolve correctly after trimming pasted whitespace');
$metadata = $appSettings->metadata($alpha);
check($metadata['has_secret'] && $metadata['available'] && !array_key_exists('client_secret', $metadata), 'View metadata never contains the Microsoft client secret');
check(!str_contains(AuditLog::where('action', 'email.microsoft_app_updated')->get()->toJson(), 'ALPHA-APP-SECRET'), 'Outlook settings audit records contain no secret values');
$context->activate($alpha);
check($inbox->configured(), 'Client Microsoft app works without environment credentials');
check(!CandidateReplyMailbox::exists() && CandidateEmailMessage::where('direction', 'inbound')->count() === 1, 'Changing from the old app invalidates its connection while preserving email conversations');
parse_str(parse_url($inbox->authorizationUrl('state', 'verifier'), PHP_URL_QUERY), $query);
check($query['client_id'] === $alphaAppId, 'Outlook sign-in uses the current client application ID');
$viewData = $platform->tenant($alpha)->getData();
$html = view('saas.platform-microsoft-inbox', $viewData)->render();
check(str_contains($html, 'Save Microsoft app settings') && str_contains($html, $alphaAppId) && !str_contains($html, 'ALPHA-APP-SECRET'), 'Super Admin form shows the saved app ID and an empty secret field');
$context->activate($alpha);
$request = $makeAppRequest(['microsoft_client_id' => $alphaAppId, 'microsoft_client_secret' => '']);
$platform->saveClientMicrosoftInbox($request, $alpha);
check($appSettings->forTenant($alpha)['client_secret'] === 'ALPHA-APP-SECRET', 'A blank secret preserves the saved client secret');
$request = $makeAppRequest(['microsoft_client_id' => $alphaAppId]);
$platform->saveClientMicrosoftInbox($request, $alpha);
check($appSettings->forTenant($alpha)['client_secret'] === 'ALPHA-APP-SECRET', 'Omitting the optional saved secret also preserves it');
$context->activate($gamma);
check(!$inbox->configured(), 'A client without settings cannot use another client’s Microsoft credentials');
$context->activate($alpha);
$start(); $pending = $outlookSession->get('microsoft_reply_oauth');
fakeMicrosoft([
    $tokenUrl => Http::response(['access_token' => 'CLIENT-ALPHA-TOKEN', 'refresh_token' => 'CLIENT-ALPHA-REFRESH', 'expires_in' => 3600]),
    'https://graph.microsoft.com/v1.0/me?*' => Http::response(['mail' => 'alpha@example.test']),
    'https://graph.microsoft.com/v1.0/me/mailFolders/inbox/messages?*' => Http::response(['value' => []]),
]);
$callback(['state' => $pending['state'], 'code' => 'client-app-code']);
check(CandidateReplyMailbox::exists(), 'Client can authorize Outlook using the app saved by Super Admin');
Http::assertSent(fn ($r) => $r->url() === $tokenUrl && $r['client_id'] === $alphaAppId && $r['client_secret'] === 'ALPHA-APP-SECRET');
$start(); $stale = $outlookSession->get('microsoft_reply_oauth');
$platform->saveClientMicrosoftInbox($makeAppRequest(['microsoft_client_id' => $alphaAppId, 'microsoft_client_secret' => 'ROTATED-ALPHA-SECRET']), $alpha);
check(CandidateReplyMailbox::exists(), 'Rotating a secret for the same app keeps the authorized mailbox');
fakeMicrosoft(['*' => Http::response([], 500)]);
$rejectedResponse = $callback(['state' => $stale['state'], 'code' => 'stale-app-code']);
check(str_contains($rejectedResponse->getSession()->get('errors')->first('outlook'), 'settings changed'), 'An in-progress authorization cannot reuse changed app credentials');
Http::assertNothingSent();
CandidateReplyMailbox::find(1)->update(['token_expires_at' => now()->subMinute()]);
fakeMicrosoft([
    $tokenUrl => Http::response(['access_token' => 'RENEWED-CLIENT-TOKEN', 'refresh_token' => 'RENEWED-CLIENT-REFRESH', 'expires_in' => 3600]),
    'https://graph.microsoft.com/*' => Http::response(['value' => [], '@odata.deltaLink' => $deltaUrl]),
]);
$inbox->sync(CandidateReplyMailbox::find(1));
Http::assertSent(fn ($r) => $r->url() === $tokenUrl && $r['client_id'] === $alphaAppId && $r['client_secret'] === 'ROTATED-ALPHA-SECRET');
check(CandidateReplyMailbox::find(1)->access_token === 'RENEWED-CLIENT-TOKEN', 'Scheduled token renewal uses the current saved client app secret');
$lock = Cache::lock('candidate-outlook-inbox', 300); $lock->get();
rejected(fn () => $platform->saveClientMicrosoftInbox($makeAppRequest(['microsoft_client_id' => $changedAppId, 'microsoft_client_secret' => 'NEW-SECRET']), $alpha), 'Super Admin cannot race an active Outlook sync for this client');
check($appSettings->forTenant($alpha)['client_id'] === $alphaAppId, 'A busy sync preserves the saved credentials');
$gammaRequest = $makeAppRequest(['microsoft_client_id' => $gammaAppId, 'microsoft_client_secret' => 'GAMMA-APP-SECRET']);
$platform->saveClientMicrosoftInbox($gammaRequest, $gamma);
check($appSettings->forTenant($gamma)['client_secret'] === 'GAMMA-APP-SECRET', 'Another client’s configuration can save while the first client is syncing');
$lock->release();
$context->activate($gamma);
parse_str(parse_url($inbox->authorizationUrl('state', 'verifier'), PHP_URL_QUERY), $query);
check($query['client_id'] === $gammaAppId, 'Switching workspace resolves the correct client application without cached credentials');
PlatformSetting::updateOrCreate(['key' => 'microsoft_reply.'.$gamma->id], ['value' => json_encode(['client_id' => $gammaAppId, 'client_secret' => 'invalid-ciphertext'])]);
config(['services.microsoft_reply.client_id' => 'ENV-DEFAULT-APP', 'services.microsoft_reply.client_secret' => 'ENV-DEFAULT-SECRET']);
check(!$inbox->configured(), 'A broken client override never silently falls back to a different application');
$context->activate($alpha);
rejected(fn () => $platform->saveClientMicrosoftInbox($makeAppRequest(['microsoft_client_id' => $changedAppId, 'microsoft_client_secret' => '']), $alpha), 'Changing the application ID requires a matching new secret');
check(CandidateReplyMailbox::exists(), 'Rejected app changes retain the working client mailbox');
$platform->saveClientMicrosoftInbox($makeAppRequest(['microsoft_client_id' => $changedAppId, 'microsoft_client_secret' => 'NEW-APP-SECRET']), $alpha);
check(!CandidateReplyMailbox::exists() && CandidateEmailMessage::where('direction', 'inbound')->count() === 1, 'A valid application change requires reconnecting without deleting conversation history');
Auth::guard('platform')->logout();
try { $platform->saveClientMicrosoftInbox($makeAppRequest(['microsoft_client_id' => $alphaAppId, 'microsoft_client_secret' => 'FORBIDDEN']), $alpha); throw new RuntimeException('Client changed Microsoft app settings'); }
catch (Symfony\Component\HttpKernel\Exception\HttpException $e) { check($e->getStatusCode() === 403, 'Normal clients cannot save Super Admin Microsoft app credentials'); }
Auth::guard('platform')->login($admin);
$route = $app['router']->getRoutes()->getByName('superadmin.tenants.microsoft-inbox');
check(in_array('auth:platform', $route->gatherMiddleware(), true) && in_array('POST', $route->methods(), true), 'The settings route requires platform authentication and a state-changing POST');
$platform->saveSettings($makeAppRequest(['signup_enabled' => 1, 'trial_days' => 14, 'trial_plan_id' => $plan->id]));
$audit = AuditLog::where('action', 'platform.settings_changed')->latest('id')->firstOrFail();
check(!str_contains(json_encode($audit->before), 'microsoft_reply.') && !str_contains(json_encode($audit->before), 'client_secret'), 'Unrelated platform settings audits do not copy credential records');
$context->reset();
check($inbox->configured(), 'Existing environment configuration remains a compatible default for clients without saved settings');
Carbon\Carbon::setTestNow();
echo "Client Microsoft app settings checks passed: {$checks}\n";
