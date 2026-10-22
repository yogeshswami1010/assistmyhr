<?php

use App\Saas\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// Included by saas-integration.php: exercise real guards and encrypted browser cookies.
$appCookies = $app['cookie'];
$appLogin = (new ReflectionClass(App\Http\Controllers\Auth\LoginController::class))->newInstanceWithoutConstructor();
$appAttempt = new ReflectionMethod($appLogin, 'attemptLogin');
$appClearCookies = function () use ($appCookies) {
    foreach ($appCookies->getQueuedCookies() as $cookie) $appCookies->unqueue($cookie->getName(), $cookie->getPath());
};
$appHttp = function ($workspace, $cookies, $next, $method = 'GET', $path = '/admin/dashboard') use ($app, $session, $middleware, $appClearCookies) {
    $appClearCookies();
    $request = Request::create('https://ats.example.test'.$path.'?workspace='.$workspace, $method, [], $cookies);
    $request->setLaravelSession($session);
    $app->instance('request', $request); $app['url']->setRequest($request);
    return (new App\Http\Middleware\EncryptCookies($app['encrypter']))->handle($request, function ($request) use ($middleware, $next, $app) {
        return (new Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse($app['cookie']))->handle($request, function ($request) use ($middleware, $next) {
            return $middleware->handle($request, $next);
        });
    });
};
$appSignIn = function ($tenant, $remember) use ($appHttp, $appLogin, $appAttempt, $data) {
    return $appHttp($tenant->slug, [], function ($request) use ($tenant, $remember, $appLogin, $appAttempt, $data) {
        $request->merge(['email' => $tenant->owner_email, 'password' => $data['password'], 'remember' => $remember]);
        check($appAttempt->invoke($appLogin, $request), 'Real login controller accepts client credentials');
        check(Auth::guard('web') instanceof App\Saas\TenantSessionGuard, 'Client guard scopes remembered sign-ins');
        check(config('auth.guards.web.remember') === 43200, 'Remembered sign-in lasts 30 days');
        check(Auth::guard('web')->getName() === 'login_web_'.sha1(Illuminate\Auth\SessionGuard::class), 'Existing session keys remain compatible');
        return response('Signed in');
    }, 'POST', '/login');
};
$appRememberedCookie = function ($response, $tenant) {
    $cookies = array_values(array_filter($response->headers->getCookies(), fn ($cookie) => str_ends_with($cookie->getName(), '_'.$tenant->uuid) && $cookie->getExpiresTime() > now()->timestamp));
    check(count($cookies) === 1, 'Exactly one persistent cookie belongs to the selected client');
    check($cookies[0]->isHttpOnly(), 'Persistent cookie is HTTP-only');
    check($cookies[0]->isSecure(), 'HTTPS remembered sign-in uses a secure cookie');
    check($cookies[0]->getExpiresTime() >= now()->timestamp + 29 * 86400, 'Browser receives a persistent 30-day cookie');
    return [$cookies[0]->getName() => $cookies[0]->getValue()];
};
$appAuthenticated = function ($tenant) {
    return function () use ($tenant) {
        check(app(TenantContext::class)->current()->id === $tenant->id, 'App icon selects its own workspace');
        check(Auth::guard('web')->check() && Auth::guard('web')->viaRemember(), 'Expired session restores from remembered sign-in');
        check(Auth::guard('web')->user()->email === $tenant->owner_email, 'Reopened app restores the correct client user');
        return response('ATS');
    };
};
$appGuest = function () { check(!Auth::guard('web')->check(), 'Unrelated or revoked cookie cannot authenticate'); return response('Sign in', 401); };
$session->flush();
$alphaAppCookie = $appRememberedCookie($appSignIn($alpha, true), $alpha);
$context->activate($alpha);
$alphaRememberToken = App\User::findOrFail(1)->getRememberToken();
check(!str_contains(reset($alphaAppCookie), $alphaRememberToken), 'Browser cookie is encrypted');
$context->reset();
$session->flush();
$appHttp($alpha->slug, $alphaAppCookie, $appAuthenticated($alpha));
check($context->current() === null, 'Remembered request restores the original database afterward');
$session->flush();
$appHttp($gamma->slug, $alphaAppCookie, $appGuest);

// Even duplicated IDs/tokens in different client databases cannot turn a renamed
// encrypted cookie into another client's remembered session.
$context->activate($gamma);
App\User::whereKey(1)->update(['remember_token' => $alphaRememberToken]);
$gammaAppCookieName = Auth::guard('web')->getRecallerName();
$context->reset();
$session->flush();
$appHttp($gamma->slug, [$gammaAppCookieName => reset($alphaAppCookie)], $appGuest);
$session->flush();
$gammaAppCookie = $appRememberedCookie($appSignIn($gamma, true), $gamma);
check(key($alphaAppCookie) !== key($gammaAppCookie), 'Each client has its own cookie name');
$allAppCookies = $alphaAppCookie + $gammaAppCookie;
$session->flush();
$appHttp($gamma->slug, $allAppCookies, $appAuthenticated($gamma));
$appHttp($alpha->slug, $allAppCookies, $appAuthenticated($alpha));
$appHttp($gamma->slug, $allAppCookies, $appAuthenticated($gamma));
$session->flush();
$noRememberResponse = $appSignIn($alpha, false);
check(!array_filter($noRememberResponse->headers->getCookies(), fn ($cookie) => str_ends_with($cookie->getName(), '_'.$alpha->uuid) && $cookie->getExpiresTime() > now()->timestamp), 'Turning off remembered sign-in never issues a persistent cookie');
$session->flush();
$appHttp($alpha->slug, [], $appGuest);
$session->flush();
$logoutResponse = $appHttp($alpha->slug, $alphaAppCookie, fn ($request) => $appLogin->logout($request), 'POST', '/logout');
check($logoutResponse->isRedirect(), 'Logout redirects back to sign-in');
check(!array_filter($logoutResponse->headers->getCookies(), fn ($cookie) => str_ends_with($cookie->getName(), '_'.$alpha->uuid) && $cookie->getExpiresTime() > now()->timestamp), 'Logout expires the client remember cookie');
$session->flush();
$appHttp($alpha->slug, $alphaAppCookie, $appGuest);
$session->flush();
$appHttp($gamma->slug, $gammaAppCookie, $appAuthenticated($gamma));
$session->flush();
App\Saas\Tenant::whereKey($alpha->id)->update(['status' => 'suspended']);
$suspendedAppResponse = $appHttp($alpha->slug, $alphaAppCookie, fn () => throw new RuntimeException('Suspended app reached ATS'));
check($suspendedAppResponse->isRedirect(), 'Installed apps cannot bypass workspace suspension');
App\Saas\Tenant::whereKey($alpha->id)->update(['status' => 'active']);

$appManifest = new App\Http\Controllers\Saas\AppInstallationController;
$alphaManifest = $appManifest->manifest($alpha->slug)->getData(true);
$gammaManifest = $appManifest->manifest($gamma->slug)->getData(true);
check($alphaManifest['id'] !== $gammaManifest['id'], 'Chrome distinguishes each client installation');
check($alphaManifest['start_url'] === route('admin.dashboard', ['workspace' => $alpha->slug]), 'App icon launches its own ATS dashboard');
check($gammaManifest['name'] === $gamma->name.' | AssistMyHR ATS', 'App installation shows the client company name');
check($alphaManifest['display'] === 'standalone', 'App opens in its own window');
check($alphaManifest['scope'] === 'https://ats.example.test/', 'Login and dashboard remain inside the app scope');
check(!str_contains(json_encode($alphaManifest), $alphaRememberToken), 'Manifest never contains authentication credentials');
foreach ([192, 512] as $size) {
    $dimensions = getimagesize($root.'/public/pwa/icon-'.$size.'.png');
    check($dimensions[0] === $size && $dimensions[1] === $size, 'Install icon has its declared dimensions');
}
$session->flush();
$manifestHttpResponse = $appHttp($alpha->slug, [], fn () => $appManifest->manifest($alpha->slug), 'GET', '/ats-app/'.$alpha->slug.'/manifest.webmanifest');
check($manifestHttpResponse->getStatusCode() === 200 && $context->current() === null, 'Chrome can fetch manifest without a client session');
check(str_contains($manifestHttpResponse->headers->get('Content-Type'), 'application/manifest+json'), 'Manifest has the web app media type');
check(str_contains($manifestHttpResponse->headers->get('Cache-Control'), 'no-store'), 'Manifest response does not share client data through caches');
$context->activate($alpha);
$appHead = $app['view']->make('partials.ats-app-head')->render();
check(str_contains($appHead, route('saas.app.manifest', ['slug' => $alpha->slug])), 'Client layouts link to their own manifest');
if (isset($argv[2])) file_put_contents($argv[2], json_encode(['manifest' => $alphaManifest, 'head' => $appHead], JSON_THROW_ON_ERROR));
check(!str_contains($app['view']->make('partials.ats-app-head', ['platformLayout' => true])->render(), 'rel="manifest"'), 'Super admin pages do not become client app installations');
$context->reset();
$appClearCookies();
