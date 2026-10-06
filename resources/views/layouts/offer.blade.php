<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="keywords" content="">

    <title>{{ $pageTitle }}</title>

    <style>
        :root {
            --main-color: {{ $frontTheme->primary_color }};
        }

        {!! config('saas.enabled') ? '' : $frontTheme->front_custom_css !!}
    </style>

    <!-- Vite CSS & JS -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Styles -->
    <link href="{{ asset('froiden-helper/helper.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/node_modules_files/toast-master/css/jquery.toast.css') }}" rel="stylesheet">
    <link href="{{ asset('front/assets/css/custom.css') }}" rel="stylesheet">

    <!-- Favicons -->
    <link rel="icon" href="{{$companySetting->favicon_url}}"  />
    <link rel="manifest" href="{{ asset('favicon/manifest.json') }}">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="{{ asset('favicon/ms-icon-144x144.png') }}">
    <meta name="theme-color" content="#ffffff">
    @stack('head-style')
</head>

<body>


<!-- Topbar -->
<nav class="bg-gray-900 text-white">
    <div class="container mx-auto px-4">
        <div class="flex items-center justify-between py-4">
            <a class="flex items-center" href="{{ url('/') }}">
                <img src="{{ $global->logo_url }}" class="h-8" alt="home" />
            </a>
        </div>
    </div>
</nav>
<!-- END Topbar -->



<!-- Header -->
<header class="relative bg-cover bg-center bg-no-repeat py-20" style="background-image: url({{ $frontTheme->background_image_url }})">
    <div class="absolute inset-0 bg-black bg-opacity-80"></div>
</header>
<!-- END Header -->




<!-- Main container -->
<main class="main-content">

    @yield('content')

</main>
<!-- END Main container -->






<!-- Footer -->
<footer class="bg-gray-100 py-8">
    <div class="container mx-auto px-4">
        <div class="text-center">
            <p class="text-gray-600">
                &copy; {{ \Carbon\Carbon::today()->year }} @lang('app.by') {{ $companyName }}
            </p>
        </div>
    </div>
</footer>
<!-- END Footer -->



<!-- Scripts -->
<script src="{{ asset('front/assets/js/core.min.js') }}"></script>
<script src="{{ asset('front/assets/js/thesaas.min.js') }}"></script>
<script src="{{ asset('front/assets/js/script.js') }}"></script>
<script src="{{ asset('froiden-helper/helper.js') }}"></script>
<script src="{{ asset('assets/node_modules_files/toast-master/js/jquery.toast.js') }}"></script>

@stack('footer-script')

</body>
</html>
