<?php

namespace App\Http\Controllers\Auth;

use App\Http\Middleware\RedirectIfAuthenticated;
use App\Saas\PlatformSetting;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class GoogleRegistrationController extends Controller
{
    public function start(Request $request)
    {
        abort_unless(config('saas.enabled') && PlatformSetting::valueFor('signup_enabled', '0') === '1', 403);
        $request->session()->forget(['google_login_pending', 'google_signup_pending', 'google_signup_profile']);

        foreach (['client_id', 'client_secret', 'redirect'] as $setting) {
            if (blank(config('services.google.'.$setting))) {
                Log::warning('Google signup configuration is incomplete.', ['setting' => $setting]);
                return redirect()->route('register')->withErrors(['google' => 'Google signup is currently unavailable. You can create an account using your email.']);
            }
        }

        try {
            $response = Socialite::driver('google')->redirect();
            $request->session()->put('google_signup_pending', true);
            return $response;
        } catch (\Throwable $exception) {
            return $this->failed($exception);
        }
    }

    public function callback(Request $request)
    {
        if ($request->session()->pull('google_login_pending')) {
            return app(RedirectIfAuthenticated::class)->handle($request, fn ($request) => app(LoginController::class)->handleGoogleCallback($request));
        }

        if (!$request->session()->pull('google_signup_pending')) {
            return redirect()->route('register')->withErrors(['google' => 'Your Google signup session expired. Please start again.']);
        }

        abort_unless(config('saas.enabled') && PlatformSetting::valueFor('signup_enabled', '0') === '1', 403);

        try {
            $profile = Socialite::driver('google')->user();
            $email = strtolower(trim((string) $profile->getEmail()));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || ($profile->user['email_verified'] ?? false) !== true) {
                return redirect()->route('register')->withErrors(['google' => 'Please use a Google account with a verified email address.']);
            }

            $request->session()->put('google_signup_profile', [
                'name' => mb_substr((string) $profile->getName(), 0, 150),
                'email' => $email,
            ]);

            return redirect()->route('register');
        } catch (\Throwable $exception) {
            return $this->failed($exception);
        }
    }

    private function failed(\Throwable $exception)
    {
        Log::warning('Google signup failed.', ['exception_type' => get_class($exception)]);

        return redirect()->route('register')->withErrors(['google' => 'Google signup could not be completed. Try again or register using your email.']);
    }
}
