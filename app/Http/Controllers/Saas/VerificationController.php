<?php

namespace App\Http\Controllers\Saas;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class VerificationController extends Controller
{
    public function show(Request $request)
    {
        return $request->user()->hasVerifiedEmail() ? redirect('/admin') : view('saas.verify');
    }

    public function verify(Request $request, string $id, string $hash)
    {
        $user = $request->user();
        abort_unless((string) $user->id === $id && hash_equals(sha1($user->getEmailForVerification()), $hash), 403);
        if (!$user->hasVerifiedEmail()) { $user->markEmailAsVerified(); }
        return redirect('/admin');
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
                'delivery' => 'Verification email is temporarily unavailable. Please contact the platform administrator to configure the platform email service.',
            ]);
        }
        try {
            $request->user()->sendEmailVerificationNotification();
        } catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $exception) {
            report($exception);
            return redirect(tenant_route('verification.notice'))->withErrors([
                'delivery' => 'We could not send your verification email. Please contact the platform administrator or try again later.',
            ]);
        }
        return redirect(tenant_route('verification.notice'))->with('status', 'Verification email sent.');
    }
}
