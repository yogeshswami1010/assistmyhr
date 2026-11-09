<?php

namespace App\Http\Controllers\Auth;

use App\Http\Middleware\RedirectIfAuthenticated;
use App\Saas\PlatformSetting;
use App\Saas\Tenant;
use App\Saas\TenantContext;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class GoogleRegistrationController extends Controller
{
    public function start(Request $request)
    {
        abort_unless(config('saas.enabled') && PlatformSetting::valueFor('signup_enabled', '0') === '1', 403);
        return $this->redirectToGoogle($request, 'register');
    }

    public function startLogin(Request $request)
    {
        return $this->redirectToGoogle($request, 'login');
    }

    private function redirectToGoogle(Request $request, string $returnRoute)
    {
        $request->session()->forget(['google_login_pending', 'google_login_workspace', 'google_pending_link', 'google_signup_pending', 'google_signup_profile']);

        foreach (['client_id', 'client_secret', 'redirect'] as $setting) {
            if (blank(config('services.google.'.$setting))) {
                Log::warning('Google authentication configuration is incomplete.', ['setting' => $setting]);
                return redirect()->route($returnRoute)->withErrors(['google' => 'Google authentication is currently unavailable. Use your email and password.']);
            }
        }

        try {
            $response = Socialite::driver('google')->redirect();
            $request->session()->put($returnRoute === 'login' ? 'google_login_pending' : 'google_signup_pending', true);
            if ($returnRoute === 'login' && is_string($request->query('workspace'))) {
                $request->session()->put('google_login_workspace', $request->query('workspace'));
            }
            return $response;
        } catch (\Throwable $exception) {
            return $this->failed($exception, $returnRoute);
        }
    }

    public function callback(Request $request)
    {
        $login = (bool) $request->session()->pull('google_login_pending');
        if ($login && !config('saas.enabled')) {
            return app(RedirectIfAuthenticated::class)->handle($request, fn ($request) => app(LoginController::class)->handleGoogleCallback($request));
        }

        if (!$login && !$request->session()->pull('google_signup_pending')) {
            return redirect()->route('login')->withErrors(['google' => 'Your Google session expired. Please start again.']);
        }

        if (!$login) {
            abort_unless(config('saas.enabled') && PlatformSetting::valueFor('signup_enabled', '0') === '1', 403);
        }

        $returnRoute = $login ? 'login' : 'register';
        try {
            $profile = Socialite::driver('google')->user();
            $email = strtolower(trim((string) $profile->getEmail()));
            $subject = (string) $profile->getId();
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || ($profile->user['email_verified'] ?? false) !== true || $subject === '' || strlen($subject) > 255) {
                return redirect()->route($returnRoute)->withErrors(['google' => 'Please use a Google account with a verified email address.']);
            }

            if ($login) {
                $googleOwnsEmail = str_ends_with($email, '@gmail.com') || filled($profile->user['hd'] ?? null);
                return $this->signIn($request, $email, $subject, $googleOwnsEmail);
            }

            $request->session()->put('google_signup_profile', [
                'name' => mb_substr((string) $profile->getName(), 0, 150),
                'email' => $email,
                'subject' => $subject,
            ]);

            return redirect()->route('register');
        } catch (\Throwable $exception) {
            return $this->failed($exception, $returnRoute);
        }
    }

    private function signIn(Request $request, string $email, string $subject, bool $googleOwnsEmail)
    {
        $workspace = $request->session()->pull('google_login_workspace');
        $workspaces = Tenant::whereIn('status', ['active', 'suspended'])
            ->whereRaw('LOWER(owner_email) = ?', [$email])
            ->when($workspace !== null, fn ($query) => $query->where('slug', $workspace));
        $tenant = (clone $workspaces)->where('google_subject', $subject)->first();
        if (!$tenant) {
            $matches = $workspaces->limit(2)->get();
            if ($matches->count() !== 1 || $matches->first()->google_subject !== null) {
                return redirect()->route('login')->withErrors(['google' => 'Google could not match this account to a workspace. Use your company sign-in link or sign in with your password.']);
            }
            $tenant = $matches->first();
        }

        app(TenantContext::class)->activate($tenant);
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();
        if (!$user) {
            return redirect()->route('login', ['workspace' => $tenant->slug])->withErrors(['google' => 'Sign in using your workspace email and password.']);
        }

        if ($tenant->google_subject === null) {
            if (!$googleOwnsEmail || !$user->hasVerifiedEmail()) {
                $request->session()->put('google_pending_link', [
                    'tenant_id' => $tenant->id, 'user_id' => $user->id, 'email' => $email,
                    'subject' => $subject, 'expires_at' => now()->addMinutes(10)->timestamp,
                ]);
                return redirect()->route('login', ['workspace' => $tenant->slug])
                    ->withInput(['email' => $email])
                    ->withErrors(['google' => 'Sign in once with your workspace password to connect your Google account.']);
            }
            if (!$tenant->linkGoogleSubject($subject)) {
                return redirect()->route('login', ['workspace' => $tenant->slug])->withErrors(['google' => 'This Google account could not be linked. Sign in with your password.']);
            }
        }

        $request->session()->forget(['support_access', 'user', 'storage_setting', 'url.intended', 'password_hash_web', 'auth.password_confirmed_at']);
        $request->session()->put('saas_tenant_id', $tenant->id);
        Auth::guard('web')->login($user, true);
        $request->session()->regenerate();

        if (!$user->hasVerifiedEmail()) {
            return redirect(tenant_route('verification.notice'));
        }

        return redirect(tenant_route($tenant->hasAccess() ? 'admin.dashboard' : 'saas.subscription'));
    }

    private function failed(\Throwable $exception, string $returnRoute)
    {
        Log::warning('Google authentication failed.', ['exception_type' => get_class($exception), 'flow' => $returnRoute]);

        return redirect()->route($returnRoute)->withErrors(['google' => 'Google authentication could not be completed. Try again or use your email and password.']);
    }
}
