<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'AssistMyHR')</title>
    <meta name="description" content="@yield('description', 'A connected workspace for your jobs, candidates and team.')">
    <meta name="theme-color" content="#ffffff">
    <link rel="icon" href="{{ asset('favicon/assistmyhr.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @include('marketing.styles')
    @stack('styles')
    <noscript><style>.site-header nav{display:flex}.header-menu-toggle,.footer-top,.pricing-switch,.pricing-selection{display:none}</style></noscript>
</head>
<body class="marketing-auth is-light @yield('body-class')">
    @include('marketing.header')
    <main>@yield('content')</main>
    @include('marketing.footer')
    @stack('scripts')
</body>
</html>
