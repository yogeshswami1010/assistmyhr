<?php

namespace App\Http\Controllers\Saas;

use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class VerificationController extends Controller
{
    public function show(Request $request)
    {
        return $request->user()->hasVerifiedEmail()
            ? redirect(tenant_route('admin.dashboard'))
            : view('saas.verify');
    }

    public function verify(Request $request, string $id, string $hash)
    {
        $user = $request->user();
        abort_unless((string) $user->id === $id && hash_equals(sha1($user->getEmailForVerification()), $hash), 403);
        if (!$user->hasVerifiedEmail() && $user->markEmailAsVerified()) {
            event(new Verified($user));
        }
        return redirect(tenant_route('admin.dashboard'))->with('status', 'Your email address has been verified.');
    }

    public function resend(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect(tenant_route('admin.dashboard'));
        }
        $mailer = config('mail.mailers.platform', []);
        if (($mailer['transport'] ?? null) === 'smtp'
            && (blank($mailer['username'] ?? null) || blank($mailer['password'] ?? null))) {
            return redirect(tenant_route('verification.notice'))->withErrors([
                'delivery' => 'Email failed to send. Please try again later or contact the platform administrator.',
            ]);
        }
        try {
            $request->user()->sendEmailVerificationNotification();
        } catch (\Throwable $exception) {
            report($exception);
            return redirect(tenant_route('verification.notice'))->withErrors([
                'delivery' => 'Email failed to send. Please try again later or contact the platform administrator.',
            ]);
        }
        return redirect(tenant_route('verification.notice'))->with('status', 'Verification email sent. Check your inbox and spam folder.');
    }
}
