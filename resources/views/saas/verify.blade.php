@extends('saas.layout')
@section('content')
<div class="card narrow"><h1>Verify your email</h1><p>Open the verification email sent to {{ auth()->user()->email }} to start using your ATS.</p><form method="post" action="{{ tenant_route('verification.send') }}">@csrf<button>Resend verification email</button></form><p class="muted">If the email does not arrive, contact the platform administrator.</p><form method="post" action="{{ tenant_route('logout') }}">@csrf<button class="secondary">Sign out</button></form></div>
@endsection
