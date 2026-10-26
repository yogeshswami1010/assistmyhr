<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Create your AssistMyHR workspace and bring your hiring team together.">
    <meta name="theme-color" content="#ffffff">
    <title>@yield('title', 'Create your workspace | AssistMyHR')</title>
    <link rel="icon" href="{{ asset('favicon/assistmyhr.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @include('marketing.styles')
    <noscript><style>.site-header nav{display:flex}.header-menu-toggle,.footer-top{display:none}</style></noscript>
</head>
<body class="marketing-auth marketing-register is-light">
    @include('marketing.header')
    <main class="registration-main">
        <div class="registration-inner">
            <nav class="registration-breadcrumb" aria-label="Breadcrumb"><a href="{{ url('/') }}">Home</a><span aria-hidden="true">/</span><span aria-current="page">Create workspace</span></nav>
            @if(session('status'))
                <div class="registration-notice" role="status">{{ session('status') }}</div>
            @endif
            @if($errors->any())
                <div class="registration-notice registration-error" role="alert">
                    Please check the highlighted fields below.
                    @foreach($errors->getMessages() as $field => $messages)
                        @if(!in_array($field, ['company_name', 'slug', 'name', 'email', 'password', 'password_confirmation']))
                            @foreach($messages as $message)
                                <div>{{ $message }}</div>
                            @endforeach
                        @endif
                    @endforeach
                </div>
            @endif
            @yield('content')
        </div>
    </main>
    @include('marketing.footer')
    @stack('scripts')
</body>
</html>
