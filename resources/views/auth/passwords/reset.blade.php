@extends('layouts.auth')

@php
    $authField = 'w-full rounded-xl border-[1.5px] border-[#E2DED8] bg-white py-[13px] pl-11 pr-4 text-[13.5px] leading-normal text-[#1A1E2E] outline-none transition-[border-color,box-shadow] placeholder:text-[#B0B8C4] focus:border-blue-600 focus:ring-[3px] focus:ring-blue-600/10';
    $authFieldErr = 'border-red-500 ring-[3px] ring-red-500/10 focus:border-red-500 focus:ring-red-500/10';
    $authFieldEye = ' pr-11';
    $authBtn = 'flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 py-3.5 text-sm font-bold tracking-wide text-white transition-all hover:-translate-y-px hover:bg-blue-700 hover:shadow-[0_6px_22px_rgba(37,99,235,0.4)] active:translate-y-0';
@endphp

@section('content')
    <div class="mb-8 animate-auth-fade-up opacity-0 [animation-delay:50ms]">
        <h2 class="text-[28px] font-extrabold leading-tight tracking-tight text-[#1A1E2E]">{{ __('Reset Password') }}</h2>
        <p class="mt-2 text-sm text-[#8892A0]">{{ __('Choose a new password for your account.') }}</p>
    </div>

    <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-4">
        @csrf
        @if(config('saas.enabled'))<input type="hidden" name="workspace" value="{{ app(\App\Saas\TenantContext::class)->current()?->slug }}">@endif

        <input type="hidden" name="token" value="{{ $token }}">

        <div class="animate-auth-fade-up opacity-0 [animation-delay:120ms]">
            <label for="email" class="mb-1.5 block text-[11.5px] font-bold uppercase tracking-wide text-[#5A6478]">{{ __('E-Mail Address') }}</label>
            <div class="group relative">
                <svg class="pointer-events-none absolute left-3.5 top-1/2 h-[17px] w-[17px] -translate-y-1/2 text-[#B0B8C4] transition-colors group-focus-within:text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                <input id="email" type="email" name="email" value="{{ $email ?? old('email') }}" required autofocus autocomplete="email"
                    class="{{ $authField }} {{ $errors->has('email') ? $authFieldErr : '' }}"
                    placeholder="{{ __('E-Mail Address') }}">
            </div>
            @if ($errors->has('email'))
                <p class="mt-1.5 text-[11.5px] text-red-500">{{ $errors->first('email') }}</p>
            @endif
        </div>

        <div class="animate-auth-fade-up opacity-0 [animation-delay:190ms]">
            <label for="password" class="mb-1.5 block text-[11.5px] font-bold uppercase tracking-wide text-[#5A6478]">{{ __('Password') }}</label>
            <div class="group relative">
                <svg class="pointer-events-none absolute left-3.5 top-1/2 h-[17px] w-[17px] -translate-y-1/2 text-[#B0B8C4] transition-colors group-focus-within:text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                <input id="password" type="password" name="password" required autocomplete="new-password"
                    class="{{ $authField }}{{ $authFieldEye }} {{ $errors->has('password') ? $authFieldErr : '' }}"
                    placeholder="{{ __('Password') }}">
            </div>
            @if ($errors->has('password'))
                <p class="mt-1.5 text-[11.5px] text-red-500">{{ $errors->first('password') }}</p>
            @endif
        </div>

        <div class="animate-auth-fade-up opacity-0 [animation-delay:190ms]">
            <label for="password-confirm" class="mb-1.5 block text-[11.5px] font-bold uppercase tracking-wide text-[#5A6478]">{{ __('Confirm Password') }}</label>
            <div class="group relative">
                <svg class="pointer-events-none absolute left-3.5 top-1/2 h-[17px] w-[17px] -translate-y-1/2 text-[#B0B8C4] transition-colors group-focus-within:text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                <input id="password-confirm" type="password" name="password_confirmation" required autocomplete="new-password"
                    class="{{ $authField }}{{ $authFieldEye }}"
                    placeholder="{{ __('Confirm Password') }}">
            </div>
        </div>

        <button type="submit" class="{{ $authBtn }} animate-auth-fade-up opacity-0 [animation-delay:260ms]">
            {{ __('Reset Password') }}
        </button>

        <p class="mt-2 animate-auth-fade-up text-center opacity-0 [animation-delay:320ms]">
            <a href="{{ route('login') }}" class="text-[13px] font-semibold text-blue-600 hover:text-blue-700">{{ __('Login') }}</a>
        </p>
    </form>
@endsection
