@extends('layouts.auth')

@php
    $authField = 'w-full rounded-xl border-[1.5px] border-[#E2DED8] bg-white py-[13px] pl-11 pr-4 text-[13.5px] leading-normal text-[#1A1E2E] outline-none transition-[border-color,box-shadow] placeholder:text-[#B0B8C4] focus:border-blue-600 focus:ring-[3px] focus:ring-blue-600/10';
    $authFieldErr = 'border-red-500 ring-[3px] ring-red-500/10 focus:border-red-500 focus:ring-red-500/10';
    $authFieldEye = ' pr-11';
    $authBtn = 'flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 py-3.5 text-sm font-bold tracking-wide text-white transition-all hover:-translate-y-px hover:bg-blue-700 hover:shadow-[0_6px_22px_rgba(37,99,235,0.4)] active:translate-y-0';
    $authSecondary = 'flex w-full items-center justify-center gap-2 rounded-xl border-[1.5px] border-[#E2DED8] bg-white py-[13px] text-[13.5px] font-semibold text-[#1A1E2E] no-underline transition-[border-color,box-shadow] hover:border-blue-600 hover:shadow-[0_4px_14px_rgba(37,99,235,0.12)]';
@endphp

@section('content')
    <form action="{{ route('login') }}" id="loginform" method="post" class="flex flex-col gap-4">
        @csrf
        @if(config('saas.enabled'))
        <label class="text-sm">Company workspace</label>
        <input name="workspace" value="{{ old('workspace', app(\App\Saas\TenantContext::class)->current()?->slug) }}" required pattern="[a-z0-9][a-z0-9-]{1,49}" class="{{ $authField }}" placeholder="your-company">
        <a href="{{ route('register') }}" class="text-sm text-blue-600">Create a company account</a>
        @endif

        <div class="mb-2 animate-auth-fade-up opacity-0 [animation-delay:50ms]">
            <h2 class="text-[28px] font-extrabold leading-tight tracking-tight text-[#1A1E2E]">
                {{ __('Welcome back') }}
            </h2>
            <p class="mt-2 text-sm text-[#8892A0]">{{ __('Sign in to your admin account to continue.') }}</p>
        </div>

        @if ($errors->any())
            <div class="flex animate-auth-fade-up items-center gap-2.5 rounded-xl border border-rose-100 bg-rose-50 px-4 py-3 opacity-0 [animation-delay:120ms]" role="alert">
                <svg class="h-4 w-4 shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p class="text-[13px] font-semibold text-red-600">{{ $errors->first() }}</p>
            </div>
        @endif

        <div class="animate-auth-fade-up opacity-0 [animation-delay:120ms]">
            <label for="login_email" class="mb-1.5 block text-[11.5px] font-bold uppercase tracking-wide text-[#5A6478]">{{ __('E-Mail Address') }}</label>
            <div class="group relative">
                <svg class="pointer-events-none absolute left-3.5 top-1/2 h-[17px] w-[17px] -translate-y-1/2 text-[#B0B8C4] transition-colors group-focus-within:text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                <input type="email" name="email" id="login_email" value="{{ old('email') }}"
                    class="{{ $authField }} {{ $errors->has('email') ? $authFieldErr : '' }}"
                    placeholder="{{ __('E-Mail Address') }}" required autofocus autocomplete="username">
            </div>
        </div>

        <div class="animate-auth-fade-up opacity-0 [animation-delay:190ms]">
            <label for="password" class="mb-1.5 block text-[11.5px] font-bold uppercase tracking-wide text-[#5A6478]">{{ __('Password') }}</label>
            <div class="group relative">
                <svg class="pointer-events-none absolute left-3.5 top-1/2 h-[17px] w-[17px] -translate-y-1/2 text-[#B0B8C4] transition-colors group-focus-within:text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                <input id="password" type="password" name="password" required autocomplete="current-password"
                    class="{{ $authField }}{{ $authFieldEye }} {{ $errors->has('password') ? $authFieldErr : '' }}"
                    placeholder="{{ __('Password') }}">
                <button type="button" class="absolute right-3.5 top-1/2 flex -translate-y-1/2 border-0 bg-transparent p-0 text-[#B0B8C4] transition-colors hover:text-slate-600 focus:outline-none" id="toggle-password" aria-label="{{ __('Show password') }}">
                    <svg id="eyeIcon" width="17" height="17" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                </button>
            </div>
        </div>

        @if ($credentials->login_page == 'active' && $credentials->status == 'active')
            <div class="animate-auth-fade-up opacity-0 [animation-delay:190ms]">
                <input type="hidden" name="recaptcha" id="recaptcha" value="">
                <div id="captcha_container"></div>
            </div>
        @endif

        <div class="flex animate-auth-fade-up flex-wrap items-center justify-between gap-3 opacity-0 [animation-delay:190ms]">
            <div class="flex items-center gap-2.5">
                <input type="checkbox" name="remember" id="remember" value="1" class="sr-only" {{ old('remember') ? 'checked' : '' }}>
                <button type="button" class="group relative h-5 w-9 shrink-0 cursor-pointer appearance-none rounded-full border-0 bg-slate-200 p-0 transition-colors data-[on=true]:bg-blue-600" id="rememberToggle" data-on="{{ old('remember') ? 'true' : 'false' }}" role="switch" aria-checked="{{ old('remember') ? 'true' : 'false' }}" tabindex="0" aria-labelledby="remember-label">
                    <span class="pointer-events-none absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform duration-200 group-data-[on=true]:translate-x-4"></span>
                </button>
                <label for="remember" id="remember-label" class="cursor-pointer select-none text-[13px] font-medium text-[#5A6478]">{{ __('Remember Me') }}</label>
            </div>
            <a href="#" id="to-recover" class="shrink-0 text-[13px] font-semibold text-blue-600 hover:text-blue-700">{{ __('I forgot my password') }}</a>
        </div>

        <button type="submit" id="login-submit-btn" class="{{ $authBtn }} animate-auth-fade-up opacity-0 [animation-delay:260ms]">
            <svg id="login-submit-spinner" class="hidden h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"></path>
            </svg>
            <span id="login-submit-text">{{ __('Login') }}</span>
            <svg id="login-submit-arrow" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
        </button>

        <div class="my-2 flex animate-auth-fade-up items-center gap-3 text-xs font-semibold text-[#C4CBD4] opacity-0 before:h-px before:flex-1 before:bg-[#E8E6E1] before:content-[''] after:h-px after:flex-1 after:bg-[#E8E6E1] after:content-[''] [animation-delay:260ms]">{{ __('OR') }}</div>

        <!-- <a href="{{ tenant_route('jobs.jobOpenings') }}" class="{{ $authSecondary }} animate-auth-fade-up opacity-0 [animation-delay:320ms]">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
            {{ __('messages.VisitJobOpening') }}
        </a> -->

        <!-- <p class="mt-6 animate-auth-fade-up text-center text-xs text-[#C4CBD4] opacity-0 [animation-delay:320ms]">
            © {{ date('Y') }} {{ $setting->company_name }}
        </p> -->
    </form>

    <form class="hidden flex flex-col gap-4" method="post" id="recoverform" action="{{ route('password.email') }}">
        @csrf
        @if(config('saas.enabled'))<label>Company workspace</label><input name="workspace" required value="{{ app(\App\Saas\TenantContext::class)->current()?->slug }}" class="{{ $authField }}">@endif

        <div class="mb-2 animate-auth-fade-up opacity-0 [animation-delay:50ms]">
            <h2 class="text-[28px] font-extrabold leading-tight tracking-tight text-[#1A1E2E]">@lang('app.recoverPassword')</h2>
            <p class="mt-2 text-sm text-[#8892A0]">@lang('app.enterEmailInstruction')</p>
        </div>

        @if (session('status'))
            <div class="flex items-center gap-2.5 rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3" role="status">
                <p class="text-[13px] font-semibold text-emerald-800">{{ session('status') }}</p>
            </div>
        @endif

        <div>
            <label for="recover_email" class="mb-1.5 block text-[11.5px] font-bold uppercase tracking-wide text-[#5A6478]">{{ __('E-Mail Address') }}</label>
            <div class="group relative">
                <svg class="pointer-events-none absolute left-3.5 top-1/2 h-[17px] w-[17px] -translate-y-1/2 text-[#B0B8C4] transition-colors group-focus-within:text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                <input type="email" id="recover_email" name="email" required
                    class="{{ $authField }} {{ $errors->has('email') ? $authFieldErr : '' }}"
                    placeholder="{{ __('E-Mail Address') }}" value="{{ old('email') }}" autocomplete="email">
            </div>
            @if ($errors->has('email'))
                <p class="mt-1.5 text-[11.5px] text-red-500">{{ $errors->first('email') }}</p>
            @endif
        </div>

        <button type="submit" class="{{ $authBtn }}">
            @lang('app.sendPasswordLink')
        </button>

        <p class="text-center">
            <a href="#" id="back-to-login" class="text-[13px] font-semibold text-[#8892A0] hover:text-[#5A6478]">{{ __('← Back to Login') }}</a>
        </p>
    </form>
@endsection

@push('scripts')
@if ($credentials->v2_status == 'active' && $credentials->status == 'active')
    <script src="https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit" async defer></script>
    <script>
        var gcv3;
        var onloadCallback = function() {
            gcv3 = grecaptcha.render('captcha_container', {
                'sitekey': '{{ $credentials->v2_site_key }}',
                'theme': 'light',
                'callback': function(response) {
                    if (response) {
                        document.getElementById('recaptcha').value = response;
                    }
                },
            });
        };
    </script>
@endif
@if ($credentials->v3_status == 'active' && $credentials->status == 'active')
    <script src="https://www.google.com/recaptcha/api.js?render={{ $credentials->v3_site_key }}"></script>
    <script>
        grecaptcha.ready(function() {
            grecaptcha.execute('{{ $credentials->v3_site_key }}', {
                action: 'contact'
            }).then(function(token) {
                var el = document.getElementById('recaptcha');
                if (token && el) {
                    el.value = token;
                }
            });
        });
    </script>
@endif
<script>
(function () {
    var loginForm = document.getElementById('loginform');
    var loginBtn = document.getElementById('login-submit-btn');
    var loginSpinner = document.getElementById('login-submit-spinner');
    var loginArrow = document.getElementById('login-submit-arrow');
    var loginText = document.getElementById('login-submit-text');
    if (loginForm && loginBtn && loginSpinner && loginArrow && loginText) {
        loginForm.addEventListener('submit', function () {
            if (loginBtn.disabled) return;
            loginBtn.disabled = true;
            loginBtn.setAttribute('aria-busy', 'true');
            loginBtn.classList.add('cursor-not-allowed', 'opacity-90');
            loginSpinner.classList.remove('hidden');
            loginArrow.classList.add('hidden');
            loginText.textContent = "{{ __('Signing in...') }}";
        });
    }
})();

(function () {
    var pwd = document.getElementById('password');
    var btn = document.getElementById('toggle-password');
    var icon = document.getElementById('eyeIcon');
    if (!pwd || !btn || !icon) return;
    var visible = false;
    var eyeOpen = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>';
    var eyeShut = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>';
    btn.addEventListener('click', function () {
        visible = !visible;
        pwd.type = visible ? 'text' : 'password';
        icon.innerHTML = visible ? eyeShut : eyeOpen;
        btn.classList.toggle('text-blue-600', visible);
        btn.classList.toggle('text-[#B0B8C4]', !visible);
    });
})();

(function () {
    var cb = document.getElementById('remember');
    var toggle = document.getElementById('rememberToggle');
    if (!cb || !toggle) return;
    function sync() {
        var on = cb.checked;
        toggle.dataset.on = on ? 'true' : 'false';
        toggle.setAttribute('aria-checked', on ? 'true' : 'false');
    }
    cb.addEventListener('change', sync);
    toggle.addEventListener('click', function (e) {
        e.preventDefault();
        cb.checked = !cb.checked;
        cb.dispatchEvent(new Event('change'));
    });
    toggle.addEventListener('keydown', function (e) {
        if (e.key === ' ' || e.key === 'Enter') {
            e.preventDefault();
            cb.checked = !cb.checked;
            cb.dispatchEvent(new Event('change'));
        }
    });
    sync();
})();
</script>
@endpush
