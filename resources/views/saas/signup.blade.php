@extends('saas.layout')
@section('title', 'Create your ATS workspace | AssistMyHR')
@section('content')
<div class="card narrow"><h1>Create your ATS workspace</h1><p class="muted">Your company gets a separate workspace for jobs, candidates and your team. Start with a trial.</p>
<form method="post" action="{{ route('register') }}">@csrf
<label for="company_name">Company name</label><input id="company_name" name="company_name" value="{{ old('company_name') }}" required maxlength="150">
<label for="slug">Workspace name</label><input id="slug" name="slug" value="{{ old('slug') }}" pattern="[a-z0-9][a-z0-9-]{1,49}" placeholder="your-company" required maxlength="50"><p class="muted">Lowercase letters, numbers and hyphens. Your login link will be /saas/workspace/your-company.</p>
<label for="name">Your name</label><input id="name" name="name" value="{{ old('name') }}" required maxlength="150">
<label for="email">Work email</label><input id="email" name="email" type="email" value="{{ old('email') }}" required>
<label for="password">Password</label><input id="password" name="password" type="password" required minlength="12" autocomplete="new-password"><p class="muted">Use at least 12 characters.</p>
<label for="password_confirmation">Confirm password</label><input id="password_confirmation" name="password_confirmation" type="password" required minlength="12" autocomplete="new-password">
<button>Create workspace</button></form></div>
@endsection
