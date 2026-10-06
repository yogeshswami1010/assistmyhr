<?php

namespace App\Http\Controllers\Auth;

use App\User;
use App\ThemeSetting;
use GuzzleHttp\Client;
use App\CompanySetting;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

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

    protected function redirectTo()
    {
        return 'admin/dashboard';
    }

    public function logout(Request $request)
    {
        $this->guard()->logout();

        $request->session()->invalidate();

        return redirect(route('login'));
    }
}
