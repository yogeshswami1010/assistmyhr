<?php

// Reuse isolated SQLite workspaces. Mail is fake; no live inboxes or databases are touched.
require __DIR__.'/saas-integration.php';

use App\Mail\WebsiteEnquiryReceived;
use App\Saas\WebsiteEnquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

Carbon\Carbon::setTestNow('2026-10-27 12:00:00');
$checks = 0;
$session->flush();
$session->regenerateToken();
$app->instance('website-test-csrf', new class($app, $app['encrypter']) extends App\Http\Middleware\VerifyCsrfToken {
    protected function runningUnitTests() { return false; }
});
$app['router']->aliasMiddleware('website-csrf', 'website-test-csrf');
$app['router']->group(['middleware' => 'website-csrf'], $root.'/routes/website.php');
$app['router']->getRoutes()->getByName('superadmin.enquiries.show')->middleware(Illuminate\Routing\Middleware\SubstituteBindings::class);
$app['router']->getRoutes()->refreshNameLookups();
$notificationMail = Mail::fake();
$enquiryPayload = ['kind' => 'demo', 'name' => 'Demo Tester', 'email' => 'DEMO@example.test', 'phone' => '+1 416 555 0100', 'company' => 'Test company', 'topic' => 'Live demo', 'message' => "Please show us the ATS.\nWe have five recruiters.", 'website' => ''];
$enquiryHttp = function($payload = null, $token = true, $ip = '192.0.2.1', $path = '/website/enquiries', $method = 'POST') use ($app, $session, $middleware) {
    $request = Request::create('https://ats.example.test'.$path, $method, [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json', 'REMOTE_ADDR' => $ip], $payload === null ? null : json_encode($payload));
    $request->setLaravelSession($session);
    $request->setUserResolver(fn($guard = null) => Auth::guard($guard)->user());
    if ($token) $request->headers->set('X-CSRF-TOKEN', $token === true ? $session->token() : $token);
    $app->instance('request', $request);
    $app['url']->setRequest($request);
    try {
        return $middleware->handle($request, fn($request) => $app['router']->dispatch($request));
    } catch (Illuminate\Session\TokenMismatchException $exception) {
        return response()->json(['message' => 'CSRF token mismatch.'], 419);
    } catch (Illuminate\Validation\ValidationException $exception) {
        return response()->json(['message' => $exception->getMessage(), 'errors' => $exception->errors()], 422);
    } catch (Illuminate\Http\Exceptions\HttpResponseException $exception) {
        return $exception->getResponse();
    }
};

$response = $enquiryHttp(null, false, '192.0.2.1', '/', 'GET');
check($response->isOk() && str_contains($response->getContent(), 'name="csrf-token" content="'.$session->token().'"'), 'Homepage injects its live session token into the form page');
check(str_contains($response->headers->get('Cache-Control'), 'no-store'), 'Homepage with a session token is never publicly cached');
foreach ([false, 'incorrect-token'] as $token) {
    check($enquiryHttp($enquiryPayload, $token)->getStatusCode() === 419, 'Public enquiry requires a valid CSRF token');
}
check(WebsiteEnquiry::count() === 0, 'Rejected CSRF requests do not store an enquiry');
Mail::assertNothingSent();

$response = $enquiryHttp($enquiryPayload);
check($response->isOk() && $response->getData(true)['success'], 'Demo form submission succeeds');
$demoEnquiry = WebsiteEnquiry::firstOrFail();
check($demoEnquiry->kind === 'demo' && $demoEnquiry->email === 'demo@example.test' && $demoEnquiry->topic === 'Live demo', 'Demo request preserves its type and normalizes email');
check($demoEnquiry->message === $enquiryPayload['message'] && $demoEnquiry->company === 'Test company', 'Full message and contact information are saved');
check($demoEnquiry->notification_status === 'sent' && $demoEnquiry->notified_at !== null && $demoEnquiry->read_at === null, 'Saved enquiry tracks notification success and starts unread');
Mail::assertSent(WebsiteEnquiryReceived::class, fn($mail) => $mail->hasTo('support@assistmyhr.com') && $mail->mailer === 'platform' && $mail->enquiry->id === $demoEnquiry->id);
check(config('mail.driver') === 'log', 'Sending restores the legacy mail driver');
$builtMail = (new WebsiteEnquiryReceived($demoEnquiry))->build();
check($builtMail->replyTo[0]['address'] === 'demo@example.test' && str_contains($builtMail->subject, 'demo request'), 'Demo notification uses a fixed subject and the visitor as Reply-To');
$emailBody = view('email.website-enquiry', ['enquiry' => $demoEnquiry])->render();
check(str_contains($emailBody, 'Test company') && str_contains($emailBody, route('superadmin.enquiries.show', $demoEnquiry)), 'Support email includes request details and a protected dashboard link');

$contactPayload = array_replace($enquiryPayload, ['kind' => 'contact', 'name' => '<img src=x onerror=alert(1)>', 'email' => 'contact@example.test', 'topic' => 'Pricing', 'message' => '<script>alert("enquiry")</script>', 'to' => 'attacker@example.test', 'notification_status' => 'sent', 'read_at' => '2026-01-01']);
check($enquiryHttp($contactPayload)->isOk(), 'Contact form submission succeeds');
$contactEnquiry = WebsiteEnquiry::latest('id')->firstOrFail();
check($contactEnquiry->kind === 'contact' && $contactEnquiry->topic === 'Pricing' && $contactEnquiry->read_at === null, 'Contact type is saved and client input cannot mark requests read');
Mail::assertSent(WebsiteEnquiryReceived::class, fn($mail) => $mail->enquiry->id === $contactEnquiry->id && $mail->hasTo('support@assistmyhr.com') && !$mail->hasTo('attacker@example.test'));

$invalidPayloads = [
    ['email' => "demo@example.test\r\nBcc: attacker@example.test"], ['email' => 'invalid'], ['kind' => 'admin'], ['kind' => null],
    ['topic' => 'Unsupported'], ['kind' => 'demo', 'topic' => 'Pricing'], ['website' => 'spam.example'],
    ['name' => str_repeat('a', 151)], ['name' => []], ['message' => ''], ['message' => str_repeat('a', 10001)],
];
foreach ($invalidPayloads as $index => $override) {
    check($enquiryHttp(array_replace($enquiryPayload, $override), true, '192.0.2.'.($index + 10))->getStatusCode() === 422, 'Malformed enquiry is rejected #'.$index);
}
check($enquiryHttp(array_replace($enquiryPayload, ['message' => str_repeat('x', 20001)]), true, '192.0.2.50')->getStatusCode() === 413, 'Oversized JSON payload is rejected');
check(WebsiteEnquiry::count() === 2, 'Rejected input never creates entries or sends notifications');
Mail::assertSentCount(2);

// A logged-in, unverified or suspended ATS client must still be able to contact support.
$context->activate($gamma);
App\User::whereKey(1)->update(['email_verified_at' => null]);
Auth::guard('web')->login(App\User::findOrFail(1));
$session->put('saas_tenant_id', $gamma->id);
App\Saas\Tenant::whereKey($gamma->id)->update(['status' => 'suspended']);
$context->reset();
check($enquiryHttp(array_replace($enquiryPayload, ['email' => 'locked@example.test']), true, '192.0.2.60')->isOk(), 'Public enquiry works for an unverified suspended workspace');
check($context->current() === null, 'Public submission never activates a tenant');
$context->activate($gamma);
check(!Schema::hasTable('saas_website_enquiries'), 'Enquiries are not copied into a tenant database');
check(WebsiteEnquiry::count() === 3, 'Enquiry model stays on the central connection inside tenant context');
App\User::findOrFail(1)->markEmailAsVerified();
App\Saas\Tenant::whereKey($gamma->id)->update(['status' => 'active']);
$context->reset();
$session->flush(); $session->regenerateToken();

$mailHandler = Mockery::mock(Illuminate\Contracts\Debug\ExceptionHandler::class);
$mailHandler->shouldReceive('report')->once();
$app->instance(Illuminate\Contracts\Debug\ExceptionHandler::class, $mailHandler);
Mail::swap(new class($notificationMail->manager) extends Illuminate\Support\Testing\Fakes\MailFake {
    public function send($view, array $data = [], $callback = null) { throw new RuntimeException('Simulated SMTP failure'); }
});
$response = $enquiryHttp(array_replace($enquiryPayload, ['email' => 'mail-failure@example.test']), true, '192.0.2.61');
check($response->isOk() && $response->getData(true)['success'], 'SMTP failure still acknowledges the saved request');
$failedEnquiry = WebsiteEnquiry::latest('id')->firstOrFail();
check($failedEnquiry->email === 'mail-failure@example.test' && $failedEnquiry->notification_status === 'failed' && $failedEnquiry->notified_at === null, 'Failed email leaves the complete enquiry available to super admin');
check(config('mail.driver') === 'log', 'Failed email also restores platform mail configuration');
$mailHandler->mockery_verify();
$app->forgetInstance(Illuminate\Contracts\Debug\ExceptionHandler::class);
Mail::swap($notificationMail);

for ($attempt = 0; $attempt < 3; $attempt++) check($enquiryHttp($enquiryPayload, true, '192.0.2.70')->isOk(), 'Allowed enquiry attempt is accepted');
$savedCount = WebsiteEnquiry::count();
$response = $enquiryHttp($enquiryPayload, true, '192.0.2.70');
check($response->getStatusCode() === 429 && $response->headers->has('Retry-After') && WebsiteEnquiry::count() === $savedCount, 'Fourth request from the same IP is throttled without a saved entry');

Auth::guard('platform')->logout();
foreach (['/superadmin/enquiries', '/superadmin/enquiries/'.$contactEnquiry->id] as $path) {
    try { $enquiryHttp(null, true, '192.0.2.80', $path, 'GET'); throw new RuntimeException('Guest read the enquiries inbox'); }
    catch (Illuminate\Auth\AuthenticationException $exception) { check(in_array('platform', $exception->guards(), true), 'Enquiries require the separate super admin guard'); }
}
check($contactEnquiry->fresh()->read_at === null, 'Unauthorized views cannot mark an enquiry read');
Auth::guard('platform')->login($admin->fresh());
$response = $enquiryHttp(null, true, '192.0.2.80', '/superadmin/enquiries', 'GET');
check($response->isOk() && str_contains($response->getContent(), 'Demo request') && str_contains($response->getContent(), 'Contact enquiry'), 'Super admin inbox lists both form types');
check(!str_contains($response->getContent(), '<img src=x onerror=alert(1)>') && str_contains($response->getContent(), '&lt;img'), 'Inbox escapes visitor-supplied names');
$response = $enquiryHttp(null, true, '192.0.2.80', '/superadmin/enquiries/'.$contactEnquiry->id, 'GET');
check($response->isOk() && str_contains($response->getContent(), '&lt;script&gt;') && !str_contains($response->getContent(), '<script>alert("enquiry")</script>'), 'Enquiry details escape message HTML');
check($contactEnquiry->fresh()->read_at !== null, 'Opening enquiry details marks it read');
$response = $enquiryHttp(null, true, '192.0.2.80', '/superadmin/enquiries?kind=contact&unread=1', 'GET');
check(!str_contains($response->getContent(), 'contact@example.test'), 'Unread filter excludes a message already opened');
$response = $enquiryHttp(null, true, '192.0.2.80', '/superadmin/enquiries/'.$failedEnquiry->id, 'GET');
check(str_contains($response->getContent(), 'Email not sent') && str_contains($response->getContent(), 'request is saved'), 'Admin sees failed notification status beside saved request details');
$response = $enquiryHttp(null, true, '192.0.2.80', '/superadmin', 'GET');
check(str_contains($response->getContent(), 'Recent demo requests and contact messages') && str_contains($response->getContent(), 'Open enquiries inbox'), 'Super admin dashboard includes recent website submissions');
check(str_contains($response->headers->get('Cache-Control'), 'no-store'), 'Private enquiry pages are not publicly cached');

// Rerunning the central migration from tenant maintenance must preserve the inbox.
$context->activate($gamma);
(require $root.'/database/migrations/2026_10_27_000001_create_website_enquiries.php')->up();
check(!Schema::hasTable('saas_website_enquiries') && WebsiteEnquiry::count() === $savedCount, 'Migration is idempotent and remains central when tenant migrations run');
$context->reset();
config(['saas.enabled' => false]);
check($enquiryHttp(null, false, '192.0.2.90', '/', 'GET')->isOk(), 'Public homepage still renders its session token outside SaaS mode');
check($enquiryHttp(array_replace($enquiryPayload, ['email' => 'standalone@example.test']), true, '192.0.2.90')->isOk(), 'Public enquiries also work outside SaaS mode');
config(['saas.enabled' => true]);
Carbon\Carbon::setTestNow();
echo "Website enquiry checks passed: $checks\n";
