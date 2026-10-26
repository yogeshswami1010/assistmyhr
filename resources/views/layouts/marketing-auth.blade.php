<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" style="--main-color: {{ e($frontTheme->primary_color) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Sign in to your AssistMyHR recruitment workspace.">
    <link rel="icon" href="{{ $companySetting->favicon_url }}">
    @include('partials.ats-app-head')
    <meta name="theme-color" content="#ffffff">
    <title>{{ __('Log in') }} · {{ $setting->company_name ?? config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('marketing.styles')
    @include('marketing.login-styles')
    <noscript><style>.site-header nav{display:flex}.header-menu-toggle,.footer-top{display:none}</style></noscript>
</head>
<body class="marketing-auth marketing-login is-light font-jakarta antialiased">
    @include('marketing.header')
    <main class="customer-auth-main">
        <div class="customer-auth-shell">
            <div class="customer-auth-content">
                <div class="customer-auth-topline">
                    @include('marketing.login-brand')
                    <a href="mailto:support@assistmyhr.com">{{ __('Need help?') }}</a>
                </div>
                <div class="customer-auth-form-area">
                    @if(config('saas.enabled') && app(\App\Saas\TenantContext::class)->current())
                        <div class="customer-auth-workspace">{{ __('Signing in to your company workspace') }}<strong>{{ $setting->company_name }}</strong></div>
                    @endif
                    @yield('content')
                </div>
                <a class="customer-auth-privacy" href="{{ route('privacy-policy') }}">{{ __('Privacy policy') }}</a>
            </div>
            <aside class="customer-auth-story" aria-labelledby="auth-story-title">
                @include('marketing.login-image')
                <div class="customer-auth-story-copy">
                    <span class="customer-auth-eyebrow">{{ __('HIRING IS HUMAN') }}</span>
                    <h2 id="auth-story-title">{{ __('Let’s keep it') }}<br>{{ __('that way.') }}</h2>
                    <span class="customer-auth-rule" aria-hidden="true"></span>
                    <p>{{ __('Bring your team and talent together.') }}</p>
                </div>
            </aside>
        </div>
    </main>
    @include('marketing.footer')
    <script>
        document.getElementById('to-recover')?.addEventListener('click', function (event) {
            event.preventDefault();
            const loginForm = document.getElementById('loginform');
            const recoverForm = document.getElementById('recoverform');
            if (loginForm && recoverForm) {
                loginForm.classList.add('hidden');
                recoverForm.classList.remove('hidden');
                document.getElementById('recover_email')?.focus();
            }
        });
        document.getElementById('back-to-login')?.addEventListener('click', function (event) {
            event.preventDefault();
            const loginForm = document.getElementById('loginform');
            const recoverForm = document.getElementById('recoverform');
            if (loginForm && recoverForm) {
                recoverForm.classList.add('hidden');
                loginForm.classList.remove('hidden');
                document.getElementById('login_email')?.focus();
            }
        });
    </script>
    @stack('scripts')
</body>
</html>
