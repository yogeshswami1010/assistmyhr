@extends('layouts.marketing-page')
@section('title', 'Verify your email | AssistMyHR')
@section('description', 'Verify your email address to access your AssistMyHR recruitment workspace.')
@section('body-class', 'marketing-verify')
@push('styles')
<meta name="robots" content="noindex, nofollow">
<link rel="stylesheet" href="{{ asset('marketing/verification.css') }}?v=1">
@endpush
@section('content')
<section class="verification-page" aria-labelledby="verification-title">
    <div class="verification-intro"><span class="verification-eyebrow">YOUR WORKSPACE IS ALMOST READY</span><h1 id="verification-title">One last step.<br><span>A world of better hiring.</span></h1><p>Verify your email to bring your candidates, jobs, and hiring team together in one connected workspace.</p><div class="verification-assurance"><span aria-hidden="true">✓</span> Secure access to your recruitment workspace</div></div>
    <div class="verification-card">
        <div class="verification-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="5" width="18" height="14" rx="3"/><path d="m4 7 8 6 8-6"/></svg></div>
        <span class="verification-eyebrow">CHECK YOUR INBOX</span>
        <h2>Verify your email</h2>
        <p>We sent a verification link to</p>
        <strong class="verification-email">{{ auth()->user()->email }}</strong>
        @if(session('status'))
            <div class="verification-alert verification-alert--success" role="status">{{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div class="verification-alert verification-alert--error" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
        @endif
        <ol class="verification-steps"><li><span>1</span><div>Open your AssistMyHR email<small>Check your spam folder if you don’t see it.</small></div></li><li><span>2</span><div>Select <strong>Verify email address</strong><small>Your link is valid for 60 minutes.</small></div></li><li><span>3</span><div>Get started with your workspace<small>Email verification is required before you can access the ATS.</small></div></li></ol>
        <form method="post" action="{{ tenant_route('verification.send') }}">@csrf<button class="button verification-resend" type="submit">Resend verification email <span aria-hidden="true">→</span></button></form>
        <form method="post" action="{{ tenant_route('logout') }}">@csrf<button type="submit" class="verification-signout">Sign out</button></form>
        <p class="verification-help">Need help? <a href="mailto:support@assistmyhr.com">Contact support</a></p>
    </div>
</section>
@endsection
