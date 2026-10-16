<?php

namespace App\Http\Controllers\Saas;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class VerificationController extends Controller
{
    public function show(Request $request)
    {
        return redirect(tenant_route('admin.dashboard'));
    }

    public function verify(Request $request, string $id, string $hash)
    {
        $user = $request->user();
        abort_unless((string) $user->id === $id && hash_equals(sha1($user->getEmailForVerification()), $hash), 403);
        if (!$user->hasVerifiedEmail()) { $user->markEmailAsVerified(); }
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
            return redirect(tenant_route('admin.dashboard'))->withErrors([
                'delivery' => 'Email failed to send. Please try again later or contact the platform administrator.',
            ]);
        }
        try {
            $request->user()->sendEmailVerificationNotification();
        } catch (\Throwable $exception) {
            report($exception);
            return redirect(tenant_route('admin.dashboard'))->withErrors([
                'delivery' => 'Email failed to send. Please try again later or contact the platform administrator.',
            ]);
        }
        return redirect(tenant_route('admin.dashboard'))->with('status', 'Verification email sent.');
    }
}
