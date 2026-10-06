@extends('layouts.auth')
@section('content')
<form method="post" action="{{ route('superadmin.login') }}" class="flex flex-col gap-4">
@csrf
<div class="mb-2"><h2 class="text-[28px] font-extrabold leading-tight tracking-tight text-[#1A1E2E]">Super admin login</h2><p class="mt-2 text-sm text-[#8892A0]">Sign in to manage clients, plans and subscriptions.</p></div>
@if($errors->any())<div class="rounded-xl border border-rose-100 bg-rose-50 px-4 py-3 text-[13px] font-semibold text-red-600" role="alert">{{ $errors->first() }}</div>@endif
<div><label for="platform_email" class="mb-1.5 block text-[11.5px] font-bold uppercase tracking-wide text-[#5A6478]">Email address</label><input id="platform_email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="w-full rounded-xl border-[1.5px] border-[#E2DED8] bg-white py-[13px] px-4 text-[13.5px] text-[#1A1E2E] outline-none focus:border-blue-600 focus:ring-[3px] focus:ring-blue-600/10"></div>
<div><label for="platform_password" class="mb-1.5 block text-[11.5px] font-bold uppercase tracking-wide text-[#5A6478]">Password</label><input id="platform_password" name="password" type="password" required autocomplete="current-password" class="w-full rounded-xl border-[1.5px] border-[#E2DED8] bg-white py-[13px] px-4 text-[13.5px] text-[#1A1E2E] outline-none focus:border-blue-600 focus:ring-[3px] focus:ring-blue-600/10"></div>
<button class="flex w-full items-center justify-center rounded-xl bg-blue-600 py-3.5 text-sm font-bold tracking-wide text-white transition-all hover:-translate-y-px hover:bg-blue-700">Sign in</button>
<a href="{{ route('login') }}" class="text-center text-[13px] font-semibold text-blue-600">Client account login</a>
</form>
@endsection
