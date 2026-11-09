<?php

namespace App\Http\Controllers\Auth;

use App\User;
use App\ThemeSetting;
use GuzzleHttp\Client;
use App\CompanySetting;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Saas\TenantContext;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/admin/dashboard';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware('guest')->except('logout');
    }

    public function showLoginForm()
    {
        $setting = CompanySetting::first();
        $frontTheme = ThemeSetting::first();
        return view('auth.login', compact('setting', 'frontTheme'));
    }

    public function validateGoogleReCaptcha($googleReCaptchaResponse)
    {
        $client = new Client();
        $response = $client->post(
            'https://www.google.com/recaptcha/api/siteverify',
            [
                'form_params' => [
                    'secret' => $this->credentials->v2_secret_key,
                    'response' => $googleReCaptchaResponse,
                    'remoteip' => $_SERVER['REMOTE_ADDR']
                ]
            ]
        );

        $body = json_decode((string)$response->getBody());

        return $body->success;
    }

    protected function validateLogin(\Illuminate\Http\Request $request)
    {

        $rules = [
            $this->username() => 'required|string',
            'password' => 'required|string'
        ];
        $google_captcha = $this->credentials;

        if ($google_captcha->status == 'active' && $google_captcha->login_page == 'active') {
            $rules['recaptcha'] = 'required';
        }

        // User type from email/username
        $user = User::where($this->username(), $request->{$this->username()})->first();

        if ($google_captcha->status == 'active' && $google_captcha->v2_status == 'active' && (is_null($user) || ($user && !$user->hasRole('admin')))) {
            $rules['g-recaptcha-response'] = 'required';
        }

        $this->validate($request, $rules);
    }

    public function googleRecaptchaMessage()
    {
        throw ValidationException::withMessages([
            'g-recaptcha-response' => [trans('app.recaptchaFailed')],
        ]);
    }

    public function login(Request $request)
    {
        $this->validateLogin($request);

        // User type from email/username
        $user = User::where($this->username(), $request->{$this->username()})->first();
        // Check google recaptcha if setting is enabled
        if ($this->credentials->status == 'active' && $this->credentials->v2_status == 'active' && (is_null($user) || ($user && !$user->hasRole('admin'))))
        {
            // Checking is google recaptcha is valid
            $gReCaptchaResponseInput = 'g-recaptcha-response';
            $gReCaptchaResponse = $request->{$gReCaptchaResponseInput};
            $validateRecaptcha = $this->validateGoogleReCaptcha($gReCaptchaResponse);
            
            if (!$validateRecaptcha)
            {
                return $this->googleRecaptchaMessage();
            }
        }

        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.

        if (method_exists($this, 'hasTooManyLoginAttempts') && $this->hasTooManyLoginAttempts($request))
        {
            $this->fireLockoutEvent($request);
            /* @phpstan-ignore-next-line */
            return $this->sendLockoutResponse($request);
        }

        if ($this->attemptLogin($request)) {
            return $this->sendLoginResponse($request);
        }

        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        $this->incrementLoginAttempts($request);

        return $this->sendFailedLoginResponse($request);
    }

    protected function sendFailedLoginResponse(Request $request)
    {
        $message = 'The email address or password is incorrect.';
        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'errors' => ['email' => [$message]]], 422);
        }
        return redirect()->route('login')->withErrors(['email' => $message])->withInput($request->only('email'));
    }
    protected function redirectTo()
    {
        if (config('saas.enabled') && !$this->guard()->user()->hasVerifiedEmail()) {
            return tenant_route('verification.notice');
        }
        return 'admin/dashboard';
    }

    protected function attemptLogin(Request $request)
    {
        return $this->guard()->attempt($this->credentials($request), $request->boolean('remember'));
    }

    protected function authenticated(Request $request, $user)
    {
        if (!config('saas.enabled')) {
            return;
        }

        $link = $request->session()->pull('google_pending_link');
        $tenant = app(TenantContext::class)->current();
        if ($link && $tenant && $tenant->id === $link['tenant_id'] && $user->id === $link['user_id']
            && strtolower($user->email) === $link['email'] && now()->timestamp < $link['expires_at']) {
            $tenant->linkGoogleSubject($link['subject']);
        }
    }

    public function logout(Request $request)
    {
        $this->guard()->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect(tenant_route('login'));
    }

/**
 * Handle Google login.
 */
public function handleGoogleCallback(Request $request)
{
    try {

        $googleUser = Socialite::driver('google')->user();

        $email = strtolower(trim((string) $googleUser->getEmail()));
        $googleId = $googleUser->getId();

        if (!$email || !$googleId) {
            return redirect()->route('login')
                ->withErrors([
                    'email' => 'Google account details could not be retrieved.'
                ]);
        }

        // Verify Google's email verification status.
        $googleData = $googleUser->user ?? [];

        if (empty($googleData['email_verified'])) {
            return redirect()->route('login')
                ->withErrors([
                    'email' => 'Please use a verified Google account.'
                ]);
        }

        // First check whether this Google account is already linked.
        $user = User::where('google_id', $googleId)->first();

        // If not linked, check whether an account already exists with this email.
        if (!$user) {
            $user = User::where('email', $email)->first();
        }

        if (!$user) {

            // New Google user: continue through SaaS registration.
            // Do not create a user directly here.
            $request->session()->put('google_registration', [
                'google_id' => $googleId,
                'name' => $googleUser->getName(),
                'email' => $email,
                'image' => $googleUser->getAvatar(),
            ]);

            return redirect()->route('register');

        }

        // Save Google ID on the existing user.
        if ($user->google_id && $user->google_id !== $googleId) {
            return redirect()->route('login')
                ->withErrors([
                    'email' => 'This email is linked to another Google account.'
                ]);
        }

        $user->google_id = $googleId;

        if (!$user->email_verified_at) {
            $user->email_verified_at = now();
        }

        if (!$user->image && $googleUser->getAvatar()) {
            $user->image = $googleUser->getAvatar();
        }

        $user->save();

        // Login existing user.
        Auth::guard('web')->login($user, true);

        $request->session()->regenerate();

        return redirect()->intended($this->redirectTo());

    } catch (\Throwable $e) {

        Log::error('Google Authentication Error: ' . $e->getMessage());

        return redirect()->route('login')
            ->withErrors([
                'email' => 'Google authentication failed. Please try again.'
            ]);
    }
}
}
