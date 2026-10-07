<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport"            content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description"         content="{{ !empty($metaDescription) ? $metaDescription : '' }}">

    <meta property="og:url"          content="{{ !empty($pageUrl) ? $pageUrl : '' }}" />
    <meta property="og:type"         content="website" />
    <meta property="og:title"        content="{{ !empty($metaTitle) ? $metaTitle : '' }}" />
    <meta property="og:description"  content="{{ !empty($metaDescription) ? $metaDescription : '' }}" />
    <meta property="og:image"        content="{{ !empty($metaImage) ? $metaImage : '' }}" />
    <meta property="og:image:width"  content="600" />
    <meta property="og:image:height" content="600" />

    <meta itemprop="name"            content="{{ !empty($metaTitle) ? $metaTitle : '' }}">
    <meta itemprop="description"     content="{{ !empty($metaDescription) ? $metaDescription : '' }}">
    <meta itemprop="image"           content="{{ !empty($metaImage) ? $metaImage : '' }}">

    <meta property="title"            content="{{!empty($metaTitle) ? $metaTitle : ''}}">
    <meta property="description"     content="{{ !empty($metaDescription) ? $metaDescription : '' }}">

    <title>{{ $pageTitle }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">

    <style>
        :root {
            --main-color: {{ $frontTheme->primary_color }};
        }

        {!! config('saas.enabled') ? '' : $frontTheme->front_custom_css !!}
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <link href="{{ asset('froiden-helper/helper.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/node_modules_files/toast-master/css/jquery.toast.css') }}" rel="stylesheet">

    @stack('header-css')
    <link rel='stylesheet prefetch'
    href='//cdnjs.cloudflare.com/ajax/libs/flag-icon-css/0.8.2/css/flag-icon.min.css'>
    <link href="{{ asset('assets/node_modules_files/sweetalert/sweetalert.css') }}" rel="stylesheet">

    <link rel="icon" href="{{$companySetting->favicon_url}}"  />
    <link rel="manifest" href="{{ asset('favicon/manifest.json') }}">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="{{ asset('favicon/ms-icon-144x144.png') }}">
    <meta name="theme-color" content="#F8F7F4">
    @stack('style')
</head>

<body class="front-site min-h-screen bg-[#F8F7F4] text-[#1A1A2A]"> 

<nav class="fr-navbar" aria-label="Company navigation">
    <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between gap-4">
        <a class="flex items-center gap-2.5 shrink-0" href="{{ tenant_route('jobs.jobOpenings') }}">
            <img src="{{ $global->logo_url }}" alt="{{ $companyName ?? $global->company_name }}" class="h-8" style="max-width:220px;object-fit:contain;@if(empty($global->logo)) background:#fff;border-radius:6px;padding:4px 8px; @endif">
            
        </a>

        <div class="flex items-center gap-2 sm:gap-3 flex-wrap justify-end">
            @if($global->front_language == 1)
                <!-- <div class="relative language-drop" x-data="{ open: false }">
                    <button type="button" @click="open = !open" class="flex items-center gap-1.5 text-sm font-medium px-3 py-2 rounded-lg text-white/60 hover:text-white/90">
                        <span class="text-base leading-none"><i class="flag-icon @if($language->language_code == 'en') flag-icon-us @else  flag-icon-{{ $language->language_code }} @endif"></i></span>
                        <span class="hidden sm:inline">{{ $language->language_name }}</span>
                        <svg width="10" height="6" fill="none" viewBox="0 0 10 6" class="shrink-0"><path d="M1 1l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                    <div x-show="open" @click.away="open = false" x-transition x-cloak
                         class="absolute right-0 top-full mt-2 w-44 rounded-xl shadow-2xl border py-1.5 z-50 bg-[#162849] border-white/10">
                        @forelse ($languageSettings as $lang)
                            <a class="dropdown-item w-full flex items-center gap-3 px-4 py-2.5 text-sm text-white/70 hover:text-white hover:bg-white/5 cursor-pointer"
                               data-lang-code="{{ $lang->language_code }}"
                               href="javascript:;">
                                <span class="flag-icon @if($lang->language_code == 'en') flag-icon-us @elseif($lang->language_code == 'ar') flag-icon-ae @elseif($lang->language_code == 'es-ar') flag-icon-ar @else flag-icon-{{ $lang->language_code }} @endif"></span>
                                {{ $lang->language_name }}
                            </a>
                        @empty
                        @endforelse
                    </div>
                </div> -->
            @endif

            @if($global->job_alert_status == 1)
                @if( isset($alertId) && !is_null($alertId))
                    <button class="fr-btn-nav bg-red-600 hover:bg-red-700 text-xs sm:text-[13px] px-3 sm:px-5 disable-job-alert" type="button">@lang('modules.front.disableJobAlert')</button>
                @else
                    <button type="button" class="fr-btn-nav text-xs sm:text-[13px] px-3 sm:px-5 whitespace-nowrap" id="job-alert">@lang('modules.front.jobAlert')</button>
                @endif
            @endif
        </div>
    </div>
</nav>

<main class="main-content">
    @yield('content')
</main>

<div class="modal hidden fixed inset-0 z-50 overflow-y-auto" id="addJobAlert" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-backdrop fixed inset-0 bg-black/60 cursor-pointer"></div>
    <div class="flex items-center justify-center min-h-screen px-4 py-8">
        <div class="modal-dialog relative w-full max-w-3xl" id="modal-data-application">
            <div class="modal-content relative bg-white rounded-2xl shadow-xl border border-[#E8E6E1] overflow-hidden">
                <div class="modal-header flex items-center justify-between px-6 py-4 border-b border-[#F0EEE9]">
                    <h4 class="modal-title text-lg font-semibold text-[#1A1A2A]"><i class="icon-plus"></i> @lang('modules.front.jobAlert')</h4>
                    <button type="button" class="close text-gray-400 hover:text-gray-600 transition-colors" data-dismiss="modal" aria-hidden="true">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
                <div class="modal-body p-6 overflow-y-auto text-[#3D4A5C] max-h-[75vh]">
                    Loading...
                </div>
            </div>
        </div>
    </div>
</div>

<footer class="fr-site-footer" style="
    display: none;
">
    <div class="max-w-6xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4 px-6">
        
        <nav class="flex flex-wrap justify-center gap-4 sm:gap-6">
            @forelse($customPages as $customPage)
                @php
                    $ftHref = ! empty($customPage->external_url) ? $customPage->external_url : tenant_route('jobs.custom-page', $customPage->slug);
                    $ftTarget = ! empty($customPage->external_url) ? ($customPage->link_target ?? '_self') : '_self';
                @endphp
                <a class="text-[13px] font-medium text-[#8892A0] hover:text-[#1A1A2A] transition-colors" href="{{ $ftHref }}" @if($ftTarget === '_blank') target="_blank" rel="noopener noreferrer" @endif>{{ $customPage->name }}</a>
            @empty
            @endforelse
        </nav>
        <p class="text-[12px] text-[#B0B8C4] text-center sm:text-right">&copy; {{ \Carbon\Carbon::today()->year }} @lang('app.by') {{ $companyName }}</p>
    </div>
</footer>

<script src="{{ asset('front/assets/js/core.min.js') }}"></script>
<script src="{{ asset('front/assets/js/script_new.js') }}"></script>
<script src="{{ asset('froiden-helper/helper.js') }}"></script>
<script src="{{ asset('assets/node_modules_files/toast-master/js/jquery.toast.js') }}"></script>
<script src="{{ asset('assets/node_modules_files/sweetalert/sweetalert.min.js') }}"></script>
<script>
    setActiveClassToLanguage();
    $('.language-drop .dropdown-item').click(function () {
        let code = $(this).data('lang-code');

        let url = '{{ tenant_route('jobs.changeLanguage', ':code') }}';
        url = url.replace(':code', code);

        if (!$(this).hasClass('active')) {
            $.easyAjax({
                url: url,
                type: 'POST',
                container: 'body',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function (response) {
                    if (response.status == 'success') {
                        location.reload();
                        setActiveClassToLanguage();
                    }
                }
            })
        }
    })
    @if(isset($alertId) && !is_null($alertId))
    $('body').on('click', '.disable-job-alert', function(event){
       var id = {{$alertId}};

       swal({
           title: "@lang('errors.areYouSure')",
           text: "@lang('errors.warnigJobAlert')",
           type: "warning",
           showCancelButton: true,
           confirmButtonColor: "#DD6B55",
           confirmButtonText: "@lang('app.disable')",
           cancelButtonText: "@lang('app.cancel')",
           closeOnConfirm: true,
           closeOnCancel: true
       }, function(isConfirm){
           if (isConfirm) {

               var url = "{{ tenant_route('jobs.disableJobAlert',':id') }}";
               url = url.replace(':id', id);

               var token = "{{ csrf_token() }}";

               $.easyAjax({
                   type: 'POST',
                   url: url,
                   data: {'_token': token},
                   success: function (response) {
                       if (response.status == "success") {
                           if (typeof table !== 'undefined' && table._fnDraw) {
                               table._fnDraw();
                           }
                           location.reload();
                       }
                   }
               });
           }
       });
   });
@endif
    function setActiveClassToLanguage() {
        if ('{{ \Cookie::has('language_code') }}') {
            $('.language-drop .dropdown-item').filter(function () {
                return $(this).data('lang-code') === '{{ \Cookie::get('language_code') }}'
            }).addClass('active font-semibold text-sky-300');
        }
        else {
            $('.language-drop .dropdown-item').filter(function () {
                return $(this).data('lang-code') === '{{ $global->locale }}'
            }).addClass('active font-semibold text-sky-300');
        }
    }
    $(document).ready(function () {
        $('#job-alert').click(function() {
            var url = "{{ tenant_route('jobs.jobAlert') }}";
            $('.modal-title').html("<i class='icon-plus'></i> @lang('modules.front.jobAlert')");
            $('#addJobAlert').removeClass('hidden');
            $.ajaxModal('#addJobAlert', url);
        });

        $('body').on('click', '[data-dismiss="modal"]', function() {
            $(this).closest('.modal').addClass('hidden');
        });

        $('body').on('click', '.modal-backdrop', function() {
            $(this).closest('.modal').addClass('hidden');
        });
    });
</script>
@stack('footer-script')

</body>
</html>
