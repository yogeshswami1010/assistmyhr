@extends('saas.layout')
@section('title', 'Verify your email | AssistMyHR')
@section('content')
<div class="card narrow">
    <span class="badge">One last step</span>
    <h1 style="margin-top:20px">Verify your email</h1>
    <p>We sent a verification link to <strong style="overflow-wrap:anywhere">{{ auth()->user()->email }}</strong>.</p>
    <p>Open the email and select <strong>Verify email address</strong> to unlock your ATS workspace. Email verification is required before you can access the ATS.</p>
    <p class="muted">The link expires after 60 minutes. Check your spam folder, or request a new email below.</p>
    <form method="post" action="{{ tenant_route('verification.send') }}">
        @csrf
        <button type="submit">Resend verification email</button>
    </form>
    <form method="post" action="{{ tenant_route('logout') }}">
        @csrf
        <button type="submit" class="secondary">Sign out</button>
    </form>
    <p class="muted">Need help? <a href="mailto:support@assistmyhr.com">Contact support</a>.</p>
</div>
@endsection
