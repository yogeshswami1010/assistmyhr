<?php

namespace App\Http\Middleware;

use App\Saas\ApiKey;
use App\Saas\PlatformSetting;
use App\Saas\Tenant;
use App\Saas\TenantContext;
use App\Services\TelnyxSmsService;
use App\Services\TelnyxWebhookSignature;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InitializeSaasTenant
{
    public function handle($request, Closure $next)
    {
        if (!config('saas.enabled')) {
            abort_if($request->is('superadmin*', 'saas-files/*', 'account/subscription*', 'register', 'pricing', 'email/verify*', 'email/verification-notification'), 404);
            return $next($request);
        }
        abort_if($request->is('admin/settings/security-setting', 'admin/settings/security-setting/*'), 404);
        abort_if($request->is('admin/settings/theme-settings', 'admin/settings/theme-settings/*'), 404);
        abort_if($request->is('admin/temp-staffing', 'admin/temp-staffing/*'), 404);
        abort_if($request->isMethod('GET') && $request->is('admin/settings/application-setting'), 404);
        $context = app(TenantContext::class);
        $context->reset();
        if ($request->is('superadmin*', 'register', 'pricing', 'saas/workspace/*', 'ats-app/*') || $request->path() === '/') {
            try { return $this->privateResponse($next($request)); } finally { $context->reset(); }
        }
        $api = $request->is('api/jobs', 'api/jobs/*');
        $supportAccess = false;
        if ($request->is('telnyx-webhook')) {
            $tenant = $this->smsWebhookTenant($request);
            if (!($tenant instanceof Tenant)) return $this->privateResponse($tenant);
        } elseif ($api) {
            $token = $request->bearerToken();
            $tenant = $token ? ApiKey::where('token_hash', hash('sha256', $token))->first()?->tenant : null;
            if (!$tenant || !$tenant->hasAccess()) { return response()->json(['status' => false, 'message' => 'Invalid key or unavailable subscription.'], 401); }
        } else {
            // Resolve the registered company before controllers read tenant-specific settings.
            if ($request->is('login') && $request->isMethod('POST') && is_string($request->input('email'))) {
                $email = strtolower(trim($request->input('email')));
                $request->merge(['email' => $email]);
                $matches = Tenant::whereRaw('LOWER(owner_email) = ?', [$email])->whereNotIn('status', ['provisioning', 'failed'])->limit(2)->get();
                if ($matches->count() === 1) {
                    $request->merge(['workspace' => $matches->first()->slug]);
                } elseif ($matches->count() > 1) {
                    // Existing duplicate owners can still sign in through their company link.
                    $selected = $request->input('workspace');
                    if (!$matches->contains('slug', $selected)) {
                        throw \Illuminate\Validation\ValidationException::withMessages(['email' => 'Please use your company sign-in link to access this account.']);
                    }
                }
            }
            $slug = $request->is('saas-files/*') ? $request->segment(2) : $request->input('workspace');
            if ($slug !== null && (!is_string($slug) || !preg_match('/\A[a-z0-9][a-z0-9-]{1,49}\z/', $slug))) { if ($request->is('login') && $request->isMethod('POST')) { return $this->failedLogin($request); } abort(404); }
            $tenant = $slug !== null ? Tenant::where('slug', $slug)->first() : Tenant::find($request->session()->get('saas_tenant_id'));
            $tenant ??= $slug === null ? Tenant::where('slug', 'main')->first() : null;
            if (!$tenant || in_array($tenant->status, ['provisioning', 'failed'], true)) {
                if ($request->is('login') && $request->isMethod('POST')) { return $this->failedLogin($request); }
                abort(404);
            }
            $guard = Auth::guard('web');
            if ($access = $request->session()->get('support_access')) {
                $supportAccess = (int) ($access['admin_id'] ?? 0) === (int) Auth::guard('platform')->id()
                    && Auth::guard('platform')->check()
                    && (int) ($access['tenant_id'] ?? 0) === (int) $tenant->id
                    && (int) ($access['expires_at'] ?? 0) > now()->timestamp;
                if (!$supportAccess) {
                    $request->session()->forget([$guard->getName(), 'support_access', 'saas_tenant_id', 'user', 'storage_setting']);
                    $request->session()->regenerate();
                    return redirect('/superadmin');
                }
            }
            $recaller = $guard->getRecallerName();
            // Retire the old shared cookie. TenantSessionGuard only recalls the
            // selected client's cookie after context activation below.
            $request->cookies->remove($recaller);
            Cookie::queue(Cookie::forget($recaller));
            if ($request->session()->get('saas_tenant_id') !== $tenant->id) {
                $request->session()->forget([$guard->getName(), 'user', 'storage_setting', 'password_hash_web', 'url.intended', 'auth.password_confirmed_at']);
                // Select the database before controllers run, preserving the form's CSRF token.
                // Authentication rotates the token after CSRF validation succeeds.
                $request->session()->migrate(true);
                $request->session()->put('saas_tenant_id', $tenant->id);
            }
            if (!$supportAccess && $tenant->status === 'suspended' && !$request->is('login', 'logout', 'account/subscription*', 'email/*', 'password/*')) {
                return $request->expectsJson() ? response()->json(['message' => 'This workspace is suspended.'], 403) : redirect('/account/subscription');
            }
            if (!$supportAccess && !$tenant->hasAccess() && !$request->is('login', 'logout', 'account/subscription*', 'email/*', 'password/*')) {
                return $request->is('admin*') ? redirect('/account/subscription') : response('This workspace subscription is unavailable.', 410);
            }
        }
        $context->activate($tenant);
        try {
            // Enforce verification before any ATS controller or write can run,
            // including remembered logins and direct AJAX/download requests.
            // Machine integrations authenticate independently of browser users.
            if (!$api && !$supportAccess && !$request->is('telnyx-webhook', 'login', 'logout', 'email/verify', 'email/verify/*', 'email/verification-notification', 'password/*')
                && ($user = Auth::guard('web')->user()) && !$user->hasVerifiedEmail()) {
                $verificationUrl = tenant_route('verification.notice');
                return $this->privateResponse($request->expectsJson()
                    ? response()->json(['message' => 'Verify your email address before accessing the ATS.', 'verification_url' => $verificationUrl], 403)
                    : redirect($verificationUrl));
            }
            if ($request->is('admin/settings/language-settings*', 'admin/settings/update-application*') && !$request->isMethod('GET')) { abort(403, 'Shared application files are managed by the platform administrator.'); }
            if (!$request->isMethodSafe()) {
                // Serialize tenant writes so concurrent signups/imports cannot bypass model quotas.
                return DB::connection('saas_landlord')->transaction(function () use ($tenant, $request, $next, $supportAccess) {
                    $locked = Tenant::whereKey($tenant->id)->lockForUpdate()->firstOrFail();
                    if (!$supportAccess && !$request->is('login', 'logout', 'account/subscription*', 'email/*', 'password/*')) {
                        abort_unless($locked->hasAccess(), 403, 'This subscription is unavailable.');
                    }
                    return $this->privateResponse($next($request));
                });
            }
            return $this->privateResponse($next($request));
        } finally { $context->reset(); }
    }

    private function smsWebhookTenant(Request $request): Tenant|\Symfony\Component\HttpFoundation\Response
    {
        // Telnyx has no browser session. Authenticate the raw payload before
        // resolving the workspace from the number that received this SMS.
        if (!TelnyxWebhookSignature::verify($request, (string) PlatformSetting::valueFor('telnyx_public_key'))) {
            Log::warning('Rejected an unsigned or invalid Telnyx webhook.');
            return response('Invalid signature', 401);
        }
        if ($request->input('data.event_type') !== 'message.received') return response('OK');

        $phones = app(TelnyxSmsService::class);
        try { $recipient = $phones->normalizePhone((string) $request->input('data.payload.to.0.phone_number')); }
        catch (\RuntimeException $e) { return response('Invalid recipient', 422); }

        $matches = [];
        foreach (PlatformSetting::where('key', 'like', 'telephony.%')->get(['key', 'value']) as $setting) {
            if (!preg_match('/\Atelephony\.([0-9]+)\z/', $setting->key, $id)) continue;
            $data = json_decode($setting->value, true);
            if (!is_array($data) || empty($data['sms_enabled'])) continue;
            try { $number = $phones->normalizePhone((string) ($data['sms_number'] ?? '')); }
            catch (\RuntimeException $e) { continue; }
            if ($number === $recipient) $matches[] = (int) $id[1];
        }
        if (count($matches) !== 1) {
            Log::notice('Telnyx webhook has no unique SMS recipient.', ['event_id' => $request->input('data.id'), 'matches' => count($matches)]);
            return response('Recipient is unavailable', 403);
        }
        $tenant = Tenant::find($matches[0]);
        if (!$tenant || !$tenant->hasAccess()) return response('Recipient is unavailable', 403);
        $workspace = $request->query('workspace');
        if ($workspace !== null && $workspace !== $tenant->slug) return response('Recipient workspace does not match', 403);

        return $tenant;
    }

    private function failedLogin($request)
    {
        $message = 'The email address or password is incorrect.';
        $response = $request->expectsJson()
            ? response()->json(['message' => $message, 'errors' => ['email' => [$message]]], 422)
            : redirect()->route('login')->withErrors(['email' => $message])->withInput($request->only('email'));
        return $this->privateResponse($response);
    }
    private function privateResponse($response)
    {
        $response->headers->set('Cache-Control', 'private, no-store');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        return $response;
    }
}
