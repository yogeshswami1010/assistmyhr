@extends('saas.layout')
@section('content')
<div class="card narrow"><h1>Super admin login</h1><p class="muted">Manage client accounts, plans and subscriptions.</p><form method="post" action="{{ route('superadmin.login') }}">@csrf<label>Email</label><input name="email" type="email" value="{{ old('email') }}" required autocomplete="username"><label>Password</label><input name="password" type="password" required autocomplete="current-password"><button>Sign in</button></form></div>
@endsection
