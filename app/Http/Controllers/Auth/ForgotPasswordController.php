<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\ThemeSetting;
use App\Traits\SmtpSettings;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Exception\RfcComplianceException;

class ForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */

    use SendsPasswordResetEmails, SmtpSettings {
        sendResetLinkEmail as private sendResetLinkThroughBroker;
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware('guest');
    }

    public function showLinkRequestForm()
    {
        return view('auth.passwords.email', [
            'setting' => $this->companySetting,
            'frontTheme' => ThemeSetting::first(),
        ]);
    }

    public function sendResetLinkEmail(Request $request)
    {
        try {
            if (!config('saas.enabled')) {
                $this->setMailConfigs();

                return $this->sendResetLinkThroughBroker($request);
            }

            // Delivery uses platform SMTP. The broker and generated link still use the current workspace.
            $previous = config()->getMany(['mail.driver', 'mail.default']);
            config(['mail.driver' => null, 'mail.default' => 'platform']);
            try {
                return $this->sendResetLinkThroughBroker($request);
            } finally {
                config($previous);
            }
        } catch (TransportExceptionInterface|RfcComplianceException|\InvalidArgumentException $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'email' => __('We could not send a reset link right now. Please try again shortly.'),
            ]);
        }
    }
}
