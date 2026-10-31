<?php

use Illuminate\Support\Facades\Auth;

// Included by saas-integration.php. All databases, sessions, mail and caches are isolated.
$verification = new App\Http\Controllers\Saas\VerificationController;
$verificationUser = App\User::findOrFail(1);
check(!$verificationUser->hasVerifiedEmail(), 'Registration never marks an unconfirmed email verified');
$verificationUrl = tenant_route('verification.notice');
$verificationMail = (new Illuminate\Auth\Notifications\VerifyEmail)->toMail($verificationUser);
check($verificationMail->mailer === 'platform', 'Verification uses platform mail rather than a client mailbox');
$verificationLink = $verificationMail->actionUrl;
check(str_contains($verificationLink, 'workspace='.$gamma->slug), 'Signed email verification link includes its workspace');
$context->reset();

foreach (['/admin/dashboard', '/admin/jobs', '/account/subscription', '/saas-files/'.$gamma->slug.'/resume.txt'] as $path) {
    $response = $middleware->handle(middlewareRequest($path.'?workspace='.$gamma->slug, $session), fn() => throw new RuntimeException('Unverified user reached '.$path));
    check($response->isRedirect($verificationUrl), 'Unverified session cannot access '.$path);
    check(str_contains($response->headers->get('Cache-Control'), 'no-store') && $context->current() === null, 'Verification redirect is private and restores tenant context');
}
foreach (['GET', 'POST', 'PUT', 'DELETE'] as $method) {
    $request = middlewareRequest('/admin/jobs?workspace='.$gamma->slug, $session);
    $request->setMethod($method);
    $request->headers->set('Accept', 'application/json');
    $response = $middleware->handle($request, fn() => throw new RuntimeException('Unverified AJAX request reached a controller'));
    check($response->getStatusCode() === 403 && $response->getData(true)['verification_url'] === $verificationUrl, 'Unverified '.$method.' JSON requests are rejected before ATS reads or writes');
}

$response = $middleware->handle(middlewareRequest('/email/verify?workspace='.$gamma->slug, $session), function($request) use ($verification) {
    $html = $verification->show($request)->render();
    check(str_contains($html, 'gamma@example.test') && str_contains($html, 'Resend verification email') && str_contains($html, 'Sign out'), 'Pending verification page shows recipient, resend and sign-out controls');
    return response($html);
});
check($response->isOk(), 'Verification notice remains reachable while ATS is locked');
foreach (['/logout', '/password/reset'] as $path) {
    check($middleware->handle(middlewareRequest($path.'?workspace='.$gamma->slug, $session), fn() => response('Allowed'))->isOk(), 'Unverified user can reach '.$path);
}

$context->activate($gamma);
$login = (new ReflectionClass(App\Http\Controllers\Auth\LoginController::class))->newInstanceWithoutConstructor();
check((new ReflectionMethod($login, 'redirectTo'))->invoke($login) === $verificationUrl, 'Signing in again defaults to verification instead of the ATS');
$request = middlewareRequest('/login?workspace='.$gamma->slug, $session);
check((new App\Http\Middleware\RedirectIfAuthenticated)->handle($request, fn() => throw new RuntimeException('Unverified user reached guest login'))->isRedirect($verificationUrl), 'Authenticated unverified users are sent to the verification notice');

$mailer = config('mail.mailers.platform');
config(['mail.mailers.platform' => ['transport' => 'smtp', 'username' => '', 'password' => '']]);
check($verification->resend($request)->isRedirect($verificationUrl) && $session->get('errors')->has('delivery'), 'Unavailable SMTP keeps the owner on verification with a delivery error');
check(!App\User::findOrFail(1)->hasVerifiedEmail(), 'Mail delivery failure never grants ATS access');
config(['mail.mailers.platform' => $mailer]);
$notificationFake = Illuminate\Support\Facades\Notification::getFacadeRoot();
$mailFailureHandler = Mockery::mock(Illuminate\Contracts\Debug\ExceptionHandler::class);
$mailFailureHandler->shouldReceive('report')->twice();
$app->instance(Illuminate\Contracts\Debug\ExceptionHandler::class, $mailFailureHandler);
$failedNotifications = new class extends Illuminate\Support\Testing\Fakes\NotificationFake {
    public function send($notifiables, $notification) { throw new RuntimeException('Simulated SMTP failure'); }
};
Illuminate\Support\Facades\Notification::swap($failedNotifications);
check($verification->resend($request)->isRedirect($verificationUrl) && $session->get('errors')->has('delivery'), 'SMTP exception keeps the user on the locked verification screen');
Illuminate\Support\Facades\Notification::swap($notificationFake);
$app->forgetInstance(Illuminate\Contracts\Debug\ExceptionHandler::class);
$session->forget('errors');
$context->reset();

// Dispatch the real routes so auth, signed URL checks and rate limits are exercised.
(new Illuminate\Foundation\Providers\FoundationServiceProvider($app))->registerRequestSignatureValidation();
$app['router']->aliasMiddleware('auth', Illuminate\Auth\Middleware\Authenticate::class);
$app['router']->aliasMiddleware('signed', Illuminate\Routing\Middleware\ValidateSignature::class);
$app['router']->aliasMiddleware('throttle', Illuminate\Routing\Middleware\ThrottleRequests::class);
$verificationHttp = function($url, $method = 'GET') use ($middleware, $session, $app) {
    if (str_starts_with($url, 'https://ats.example.test')) $url = substr($url, strlen('https://ats.example.test'));
    $request = middlewareRequest($url, $session);
    $request->setMethod($method);
    return $middleware->handle($request, fn($request) => $app['router']->dispatch($request));
};
$verificationRejected = function($url, $status, $message, $method = 'GET') use ($verificationHttp) {
    try { $verificationHttp($url, $method); }
    catch (Symfony\Component\HttpKernel\Exception\HttpException $exception) {
        check($exception->getStatusCode() === $status, $message);
        return;
    }
    throw new RuntimeException($message);
};
$resendUrl = '/email/verification-notification?workspace='.$gamma->slug;
for ($attempt = 0; $attempt < 6; $attempt++) {
    check($verificationHttp($resendUrl, 'POST')->isRedirect($verificationUrl), 'Resending email returns to mandatory verification');
}
$verificationRejected($resendUrl, 429, 'Repeated verification email requests are rate limited', 'POST');
$context->activate($gamma);
check(Illuminate\Support\Facades\Notification::sent(App\User::find(1), Illuminate\Auth\Notifications\VerifyEmail::class)->count() === 7, 'Only permitted resend requests send a notification');
$context->reset();

$verificationRejected(str_replace('signature=', 'signature=invalid', $verificationLink), 403, 'Tampered verification signature is rejected');
Carbon\Carbon::setTestNow(now()->addMinutes(61));
$verificationRejected($verificationLink, 403, 'Expired verification link is rejected');
Carbon\Carbon::setTestNow('2026-10-06 12:00:00');
$context->activate($gamma);
$wrongIdLink = Illuminate\Support\Facades\URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), tenant_parameters(['id' => 2, 'hash' => sha1('gamma@example.test')]));
$wrongHashLink = Illuminate\Support\Facades\URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), tenant_parameters(['id' => 1, 'hash' => sha1('someone-else@example.test')]));
$context->reset();
$verificationRejected($wrongIdLink, 403, 'Signed link for another user ID cannot verify the owner');
$verificationRejected($wrongHashLink, 403, 'Signed link for another email cannot verify the owner');

$context->activate($gamma);
check(!App\User::findOrFail(1)->hasVerifiedEmail(), 'Rejected links leave the owner unverified');
$context->activate($alpha);
App\User::whereKey(1)->update(['email_verified_at' => null]);
$session->put('saas_tenant_id', $alpha->id);
Auth::guard('web')->login(App\User::findOrFail(1));
$context->reset();
$verificationRejected(str_replace('workspace='.$gamma->slug, 'workspace=alpha', $verificationLink), 403, 'Verification link cannot be moved to another workspace');
$context->activate($alpha);
check(!App\User::findOrFail(1)->hasVerifiedEmail(), 'Cross-workspace verification never updates another owner');
App\User::findOrFail(1)->markEmailAsVerified();
$context->activate($gamma);
$session->put('saas_tenant_id', $gamma->id);
Auth::guard('web')->login(App\User::findOrFail(1));
$context->reset();

$verifiedEvents = 0;
$app['events']->listen(Illuminate\Auth\Events\Verified::class, function() use (&$verifiedEvents) { $verifiedEvents++; });
check(str_contains($verificationHttp($verificationLink)->headers->get('Location'), '/admin/dashboard?workspace='.$gamma->slug), 'Valid signed verification unlocks the correct workspace dashboard');
$verificationHttp($verificationLink);
check($verifiedEvents === 1, 'Verification is idempotent and emits the verified event once');
$response = $middleware->handle(middlewareRequest('/admin/dashboard?workspace='.$gamma->slug, $session), fn() => response('Verified ATS'));
check($response->getContent() === 'Verified ATS', 'Verified owner can enter the ATS immediately');
$context->activate($gamma);
check(App\User::findOrFail(1)->hasVerifiedEmail(), 'Verification is persisted in the owner workspace');
check(str_contains($verification->show(middlewareRequest('/email/verify?workspace='.$gamma->slug, $session))->getTargetUrl(), '/admin/dashboard'), 'Already verified owner is redirected out of the notice');
$notificationCount = Illuminate\Support\Facades\Notification::sent(App\User::find(1), Illuminate\Auth\Notifications\VerifyEmail::class)->count();
$verification->resend(middlewareRequest($resendUrl, $session));
check(Illuminate\Support\Facades\Notification::sent(App\User::find(1), Illuminate\Auth\Notifications\VerifyEmail::class)->count() === $notificationCount, 'Already verified users do not receive redundant verification emails');
$context->reset();

// Initial mail failure still creates an unverified workspace, never an ATS session with access.
Illuminate\Support\Facades\Notification::swap($failedNotifications);
$app->instance(Illuminate\Contracts\Debug\ExceptionHandler::class, $mailFailureHandler);
$failedSignupRequest = middlewareRequest('/register', $session);
$failedSignupRequest->setMethod('POST');
$failedSignupRequest->merge(array_replace($data, ['company_name' => 'Mail Failure', 'email' => 'mail-failure@example.test', 'password_confirmation' => $data['password']]));
$failedSignupResponse = $middleware->handle($failedSignupRequest, fn($request) => $signup->store($request));
$failedSignupTenant = App\Saas\Tenant::where('owner_email', 'mail-failure@example.test')->firstOrFail();
check(str_contains($failedSignupResponse->headers->get('Location'), '/email/verify?workspace='.$failedSignupTenant->slug), 'Signup mail failure still redirects to mandatory verification');
check(str_contains($session->get('status'), 'could not be sent'), 'Signup mail failure provides a readable retry message');
$blockedSignupResponse = $middleware->handle(middlewareRequest('/admin/dashboard?workspace='.$failedSignupTenant->slug, $session), fn() => throw new RuntimeException('Signup mail failure granted ATS access'));
check($blockedSignupResponse->isRedirect(), 'Failed initial delivery does not bypass verification');
Illuminate\Support\Facades\Notification::swap($notificationFake);
$mailFailureHandler->mockery_verify();
$app->forgetInstance(Illuminate\Contracts\Debug\ExceptionHandler::class);
$session->forget(['status', 'errors']);
$context->reset();
