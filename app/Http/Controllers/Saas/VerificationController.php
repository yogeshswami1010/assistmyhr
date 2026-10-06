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
        if (!$request->user()->hasVerifiedEmail()) { $request->user()->sendEmailVerificationNotification(); }
        return back()->with('status', 'Verification email sent.');
    }
}
