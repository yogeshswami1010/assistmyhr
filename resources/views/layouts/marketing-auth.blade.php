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
    <noscript><style>.site-header nav{display:flex}.header-menu-toggle,.footer-top{display:none}</style></noscript>
</head>
<body class="marketing-auth marketing-login is-light font-jakarta antialiased">
    @include('marketing.header')
    <main class="customer-auth-main">
        <div class="customer-auth-shell">
            <aside class="customer-auth-story" aria-labelledby="auth-story-title">
                <span class="customer-auth-eyebrow">{{ __('YOUR CONNECTED WORKSPACE') }}</span>
                <h1 id="auth-story-title">{{ __('Your team.') }}<br> <span>{{ __('Your next great hire.') }}</span></h1>
                <p>{{ __('Bring your jobs, candidates and conversations together. Pick up where you left off, and keep your hiring moving.') }}</p>
                <ul class="customer-auth-benefits">
                    <li>{{ __('A clearer candidate pipeline') }}</li>
                    <li>{{ __('AI candidate search and recruitment reporting') }}</li>
                    <li>{{ __('Your team, on the same page') }}</li>
                </ul>
                <a href="{{ url('/') }}#request-demo">{{ __('New to AssistMyHR? See it in a demo') }} <span aria-hidden="true">↗</span></a>
            </aside>
            <div class="customer-auth-content">
                @if(config('saas.enabled') && app(\App\Saas\TenantContext::class)->current())
                    <div class="customer-auth-workspace">{{ __('Signing in to your company workspace') }}<strong>{{ $setting->company_name }}</strong></div>
                @endif
                @yield('content')
            </div>
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
