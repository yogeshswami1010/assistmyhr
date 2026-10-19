@if(!($platformLayout ?? false))
@section('candidate-communication-popup')
    @include('admin.partials.candidate-communications', ['showCommunicationToolbar' => false])
@endsection
@endif
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- Favicon icon -->
    <link rel="icon" href="{{$companySetting->favicon_url}}"  />
    <link rel="manifest" href="{{ asset('favicon/manifest.json') }}">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="{{ asset('favicon/ms-icon-144x144.png') }}">
    <meta name="theme-color" content="#ffffff">

    <title>@lang('app.adminPanel'){{ isset($pageTitle) && $pageTitle !== '' ? ' | '.$pageTitle : '' }}</title>
    <!-- Tell the browser to be responsive to screen width -->
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">

    <!-- Vite CSS -->
    @vite(['resources/css/app.css'])

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Simple line icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/simple-line-icons/2.4.1/css/simple-line-icons.css">

    <!-- Themify icons -->
    <link rel="stylesheet" href="{{ asset('assets/icons/themify-icons/themify-icons.css') }}">
    <!-- Ionicons -->
    <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">

    <link href="{{ asset('froiden-helper/helper.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/node_modules_files/toast-master/css/jquery.toast.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/node_modules_files/sweetalert/sweetalert.css') }}" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('assets/plugins/datatables/jquery.dataTables.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/node_modules_files/Magnific-Popup-master/dist/magnific-popup.css') }}">

    @stack('head-script')

    <link rel='stylesheet prefetch'
          href='//cdnjs.cloudflare.com/ajax/libs/flag-icon-css/0.8.2/css/flag-icon.min.css'>

    <link href="{{ asset('css/custom.css') }}" rel="stylesheet">
    
    <!-- Alpine.js for dropdown functionality -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script>if (window.pdfjsLib) pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';</script>
    
    <style>
        :root {
            --main-color: {{ isset($adminTheme) ? $adminTheme->primary_color : '#2563eb' }};
        }
        pre {
            background: #fff;
            border-radius: 0;
        }

        .ra-scroll { scrollbar-width: auto; scrollbar-color: #111 #E8E6E1; }
        .ra-scroll::-webkit-scrollbar { width: 12px; height: 12px; }
        .ra-scroll::-webkit-scrollbar-track { background: #E8E6E1; }
        .ra-scroll::-webkit-scrollbar-thumb { background: #111; border: 2px solid #E8E6E1; border-radius: 10px; }
        .ra-scroll::-webkit-scrollbar-thumb:hover { background: #000; }

        .btn-group-xs > .btn, .btn-xs {
            padding  : .25rem .4rem;
            font-size  : .875rem;
            line-height  : .5;
            border-radius : .2rem;
        }

        .btn-circle {
            width: 30px;
            height: 30px;
            padding: 6px 0;
            border-radius: 15px;
            text-align: center;
            font-size: 12px;
            line-height: 1.428571429;
        }
        .text-truncate-notify{
            white-space: pre-wrap !important;
        }

        .image-container {
            display: flex;
            align-items: center;
        }

        .image-container .image {
            display: inline-block;
            position: relative;
            width: 32px;
            height: 32px;
            overflow: hidden;
            border-radius: 50%;
            margin-right: 10px;
        }

        .image-container .image img {
            width: auto;
            height: 100%;
        }

        #top-notification-dropdown>a {
            position: relative;
        }

        #top-notification-dropdown>a span {
            position: absolute;
            right: 10%;
            top: 10%;
        }

        #top-notification-dropdown>a span.badge {
            padding: 2px 5px;
        }

        .scrollable {
            max-height: 250px;
            overflow-y: scroll;
        }

        {!! !config('saas.enabled') && isset($adminTheme) ? $adminTheme->admin_custom_css : '' !!}
    </style>

</head>
<body class="ra-admin-body">
<div id="ra-app" class="ra-app" style="--ra-accent: {{ isset($adminTheme) ? $adminTheme->primary_color : '#2563eb' }};">
@if($platformLayout ?? false)
    @include('saas.platform-sidebar')
@else
@auth
    @include('sections.left-sidebar')
@endauth
@endif

<div class="ra-main">
    @if($platformLayout ?? false)
    @include('saas.platform-topbar')
@else
    <header class="ra-topbar">
        <div class="flex items-center gap-2 min-w-0">
            @auth
                <button type="button" class="p-1.5 rounded-lg hover:bg-gray-100 transition-colors shrink-0" data-ra-sidebar-toggle aria-label="@lang('app.openMenu')">
                    <svg width="17" height="17" fill="none" stroke="#8892A0" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
            @endauth
            @auth
                <a href="{{ route('admin.dashboard') }}" class="text-[#8892A0] hover:text-[#1A1E2E] shrink-0" aria-label="@lang('menu.dashboard')">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                </a>
            @else
                <a href="{{ url('/') }}" class="text-[#8892A0] hover:text-[#1A1E2E] shrink-0" aria-label="@lang('app.home')">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                </a>
            @endauth
            <span class="ra-bc-sep shrink-0" aria-hidden="true">›</span>
            <span class="ra-bc on truncate">{{ $pageTitle ?? '' }}</span>
        </div>
        <div class="flex items-center gap-2.5 shrink-0">
            @auth
                <!-- <div class="relative hidden sm:block">
                    <label class="sr-only" for="ra-top-search">@lang('app.searchAnything')</label>
                    <input type="search" id="ra-top-search" placeholder="@lang('app.searchAnything')" class="text-[12.5px] bg-[#EEF0F5] rounded-xl pl-8 pr-4 py-2 outline-none border-0 w-[180px] text-[#5A6478]" autocomplete="off">
                    <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 pointer-events-none" fill="none" stroke="#9CA3AF" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div> -->

                <div class="relative" id="top-notification-dropdown" x-data="{ open: false }">
                    <button type="button" @click="open = !open" class="relative p-2 rounded-xl hover:bg-gray-100 transition-colors" aria-expanded="false" :aria-expanded="open">
                        <svg class="w-[18px] h-[18px]" fill="none" stroke="#8892A0" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        <span id="top-notification-unread-count" class="{{ count($user->unreadNotifications) > 0 ? '' : 'hidden' }} absolute -right-1 -top-1 min-w-[17px] rounded-full bg-red-500 px-1 py-0.5 text-center text-[9px] font-bold leading-none text-white">{{ count($user->unreadNotifications) }}</span>
                    </button>
                    <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-lg z-50 border border-[#E8E6E1]" style="display: none;">
                        <div class="max-h-96 overflow-y-auto">
                            @foreach ($user->unreadNotifications as $notification)
                                @include('notifications.'.snake_case(class_basename($notification->type)))
                            @endforeach
                        </div>
                        <div class="border-t border-[#F0EEE9]">
                            @if(count($user->unreadNotifications) > 0)
                                <a id="mark-notification-read" href="javascript:void(0);" class="block px-4 py-3 text-sm text-center text-[#3D4A5C] hover:bg-[#EEF0F5]">@lang('app.markNotificationRead') <i class="fa fa-check"></i></a>
                            @else
                                <a href="javascript:void(0);" class="block px-4 py-3 text-sm text-center text-[#3D4A5C] hover:bg-[#EEF0F5]">@lang('messages.notificationNotFound')</a>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="relative flex items-center gap-2 pl-3 border-l border-[#ECEAE5]" x-data="{ openUser: false }">
                    <button type="button" @click="openUser = !openUser" class="flex items-center gap-2 rounded-xl hover:bg-gray-50 py-1 pr-1 pl-0.5">
                        <div class="ra-avatar">
                            @if(!empty($user->profile_image_url))
                                <img src="{{ $user->profile_image_url }}" alt="">
                            @else
                                {{ strtoupper(mb_substr($user->name, 0, 1, 'UTF-8')) }}
                            @endif
                        </div>
                        <div class="hidden sm:block text-left">
                            <p class="text-[12.5px] font-semibold text-[#1A1E2E] leading-tight">{{ ucwords($user->name) }}</p>
                            <p class="text-[10.5px] text-[#8892A0] leading-tight">
                                @if($user->is_superadmin)
                                    @lang('app.superAdmin')
                                @else
                                    @lang('app.adminUser')
                                @endif
                            </p>
                        </div>
                        <svg class="w-3 h-3 text-[#9CA3AF] shrink-0 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="openUser" @click.away="openUser = false" x-transition class="absolute right-0 top-full mt-2 w-52 bg-white rounded-xl shadow-lg border border-[#E8E6E1] py-1 z-50" style="display: none;">
                        <a href="@if(!$user->is_superadmin){{ route('admin.profile.index') }}@else{{ route('superadmin.profile.index') }}@endif" class="block px-4 py-2.5 text-[12.5px] font-medium text-[#3D4A5C] hover:bg-[#EEF0F5]">@lang('menu.myProfile')</a>
                        <a href="{{ route('logout') }}" class="block px-4 py-2.5 text-[12.5px] font-medium text-[#3D4A5C] hover:bg-[#EEF0F5]" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">@lang('app.logout')</a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                            {{ csrf_field() }}
                        </form>
                    </div>
                </div>
            @endauth
        </div>
    </header>
@endif

    <div class="ra-scroll">
        @include('sections.breadcrumb')
        @yield('content')
        <footer class="mt-8 pt-6 border-t border-[#E8E6E1] text-[12.5px] text-[#8892A0]">
            &copy; {{ \Carbon\Carbon::today()->year }} @lang('app.by') {{ $companyName ?? config('app.name') }}
        </footer>
    </div>

    @if(!($platformLayout ?? false))
    @auth
        @include('sections.sticky-notes-sidebar')
        {{--sticky note modal--}}
        <div id="responsive-modal" class="hidden fixed inset-0 z-[220] overflow-y-auto" tabindex="-1" role="dialog" aria-labelledby="sticky-modal-title" aria-modal="true" aria-hidden="true">
            <div class="flex min-h-full items-center justify-center p-4 sm:p-6">
                <div class="fixed inset-0 bg-black/50 transition-opacity" aria-hidden="true" onclick="$('#responsive-modal').addClass('hidden').css({display:'',visibility:'',opacity:''})"></div>
                <div class="relative z-10 w-full max-w-lg overflow-hidden rounded-xl bg-white shadow-xl">
                    <div class="p-6 !p-0 overflow-y-auto max-h-[min(560px,calc(100vh-3rem))] min-h-[10rem] text-left">
                        <p class="px-6 py-8 text-center text-sm text-slate-500">Loading …</p>
                    </div>
                </div>
            </div>
        </div>
        {{--sticky note modal ends--}}
    @endauth
    @endif

</div>
{{-- /.ra-main --}}
@if($platformLayout ?? false)
</div>
@include('saas.platform-sidebar-script')
@else

    {{--Ajax Modal (lg) — Tailwind-centered overlay --}}
    <div class="hidden fixed inset-0 z-50 overflow-y-auto" id="application-lg-modal" role="dialog" aria-labelledby="myModalLabel" aria-modal="true" aria-hidden="true">
        <div class="flex min-h-full w-full items-center justify-center p-4 sm:p-6">
            <div class="fixed inset-0 z-0 bg-black/50 transition-opacity" aria-hidden="true" onclick="$('#application-lg-modal').addClass('hidden').css({display:'',visibility:'',opacity:''})"></div>
            <div class="relative z-10 flex w-full max-w-4xl max-h-[min(90vh,calc(100dvh-2rem))] flex-col overflow-hidden rounded-xl bg-white shadow-xl ring-1 ring-black/5" id="modal-data-application">
                <div class="flex shrink-0 items-center justify-between border-b border-slate-200 px-6 py-4">
                    <h3 class="text-lg font-semibold tracking-tight text-slate-900" id="modelHeading"></h3>
                    <button type="button" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2" onclick="$('#application-lg-modal').addClass('hidden').css({display:'',visibility:'',opacity:''})" aria-label="@lang('app.close')">
                        <i class="fa fa-times text-lg"></i>
                    </button>
                </div>
                <div class="min-h-0 flex-1 overflow-y-auto p-6">
                    Loading...
                </div>
                <div class="flex shrink-0 items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4">
                    <button type="button" class="inline-flex items-center justify-center rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2" onclick="$('#application-lg-modal').addClass('hidden').css({display:'',visibility:'',opacity:''})"><i class="fa fa-times mr-1.5"></i> @lang('app.cancel')</button>
                    <button type="button" class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"><i class="fa fa-check mr-1.5"></i> @lang('app.save')</button>
                </div>
            </div>
        </div>
    </div>

    {{--Ajax Modal (md) — Tailwind-centered overlay --}}
    <div class="hidden fixed inset-0 z-50 overflow-y-auto" id="application-md-modal" role="dialog" aria-labelledby="myModalLabel" aria-modal="true" aria-hidden="true">
        <div class="flex min-h-full w-full items-center justify-center p-4 sm:p-6">
            <div class="fixed inset-0 z-0 bg-black/50 transition-opacity" aria-hidden="true" onclick="$('#application-md-modal').addClass('hidden').css({display:'',visibility:'',opacity:''})"></div>
            <div class="relative z-10 flex w-full max-w-2xl max-h-[min(90vh,calc(100dvh-2rem))] flex-col overflow-hidden rounded-2xl border border-[#E8E6E1] bg-white shadow-2xl" id="modal-data-application">
                <div class="flex shrink-0 items-center justify-between border-b border-[#F0EEE9] px-6 py-5">
                    <h3 class="text-[15.5px] font-bold tracking-tight text-[#1A1E2E]" id="modelHeading"></h3>
                    <button type="button" class="flex h-8 w-8 items-center justify-center rounded-lg text-[#8892A0] transition hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-slate-300" onclick="$('#application-md-modal').addClass('hidden').css({display:'',visibility:'',opacity:''})" aria-label="@lang('app.close')">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
                    Loading...
                </div>
                <div class="flex shrink-0 items-center justify-end gap-3 border-t border-[#F0EEE9] bg-white px-6 py-4">
                    <button type="button" class="inline-flex items-center justify-center rounded-xl bg-[#F1F3F7] px-5 py-2.5 text-[13px] font-semibold text-[#5A6478] transition hover:bg-[#E8EBF0] focus:outline-none focus:ring-2 focus:ring-slate-300 focus:ring-offset-2" onclick="$('#application-md-modal').addClass('hidden').css({display:'',visibility:'',opacity:''})">@lang('app.cancel')</button>
                    <button type="button" id="application-md-modal-save-btn" class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#2563EB] px-6 py-2.5 text-[13px] font-bold text-white shadow-sm transition hover:bg-[#1d4ed8] focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        @lang('app.save')
                    </button>
                </div>
            </div>
        </div>
    </div>


    @yield('candidate-communication-popup')
    @include('sections.right-sidebar')
    <div id="internal-message-popup-backdrop" class="fixed inset-0 z-[249] hidden bg-[rgba(15,23,42,0.52)] backdrop-blur-[2px]" aria-hidden="true"></div>
    <div id="internal-message-popup" class="fixed z-[250] hidden w-[min(480px,calc(100vw-24px))] overflow-hidden border border-white/20 bg-white" style="left:50%;top:50%;transform:translate(-50%,-50%);border-radius:24px;box-shadow:0 30px 80px rgba(15,23,42,.28),0 8px 24px rgba(15,23,42,.12);" role="dialog" aria-modal="true" aria-live="assertive" aria-label="New internal message">
        <div class="relative overflow-hidden px-5 pb-5 pt-4 text-white" style="background:linear-gradient(135deg,#13294B 0%,#1E3A6D 55%,#2563EB 140%);">
            <div class="pointer-events-none absolute -right-10 -top-14 h-36 w-36 rounded-full border border-white/10 bg-white/5"></div>
            <div class="pointer-events-none absolute -bottom-16 right-20 h-28 w-28 rounded-full bg-blue-400/10 blur-xl"></div>
            <div class="relative flex items-start justify-between gap-4">
                <div class="flex min-w-0 items-center gap-3.5">
                    <div class="relative shrink-0">
                        <div id="internal-message-popup-avatar" class="flex h-11 w-11 items-center justify-center rounded-2xl border border-white/20 bg-white/15 text-[15px] font-bold uppercase text-white shadow-inner">T</div>
                        <span class="absolute -bottom-0.5 -right-0.5 h-3 w-3 rounded-full border-2 border-[#1A3562] bg-emerald-400"></span>
                    </div>
                    <div class="min-w-0">
                        <div class="mb-1 flex items-center gap-2">
                            <span class="inline-flex rounded-full bg-blue-400/20 px-2 py-0.5 text-[9px] font-bold uppercase tracking-[0.14em] text-blue-100">Internal message</span>
                            <span class="text-[10px] text-white/45">Just now</span>
                        </div>
                        <h3 id="internal-message-popup-sender" class="truncate text-[17px] font-bold tracking-tight text-white"></h3>
                    </div>
                </div>
                <button type="button" id="internal-message-popup-close" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-white/10 bg-white/10 text-white/70 transition hover:bg-white/20 hover:text-white" aria-label="Close message popup">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>

        <div class="bg-[#F7F8FB] px-5 py-5">
            <p class="mb-2 text-[10px] font-bold uppercase tracking-[0.12em] text-[#8A94A6]">Message</p>
            <div class="relative rounded-2xl border border-[#E3E7EE] bg-white px-4 py-3.5 shadow-sm">
                <span class="absolute -top-2 left-5 h-4 w-4 rotate-45 border-l border-t border-[#E3E7EE] bg-white"></span>
                <p id="internal-message-popup-text" class="relative max-h-36 overflow-y-auto whitespace-pre-wrap break-words text-[13.5px] leading-6 text-[#334155]"></p>
            </div>
        </div>

        <div class="border-t border-[#EBEEF3] bg-white px-5 pb-5 pt-4">
            <label for="internal-message-popup-reply" class="mb-2 block text-[11px] font-bold text-[#475569]">Your reply</label>
            <textarea id="internal-message-popup-reply" rows="3" maxlength="5000" placeholder="Write a reply to this message..." class="block min-h-[88px] w-full resize-none rounded-2xl border border-[#D8DEE8] bg-[#FBFCFE] px-4 py-3 text-[13px] leading-5 text-[#1E293B] outline-none transition placeholder:text-[#A3ACBA] focus:border-[#2563EB] focus:bg-white focus:ring-4 focus:ring-blue-100/70"></textarea>
            <div class="mt-3 flex items-center justify-between gap-3">
                <p id="internal-message-popup-status" class="hidden text-[11px] font-semibold text-emerald-600">Reply sent.</p>
                <p class="text-[10.5px] text-[#9AA4B2]">Private to your ATS team</p>
                <button type="button" id="internal-message-popup-send" class="ml-auto inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-[#2563EB] px-4 text-[12.5px] font-bold text-white shadow-lg shadow-blue-500/20 transition hover:-translate-y-0.5 hover:bg-[#1D4ED8] hover:shadow-blue-500/30 disabled:translate-y-0 disabled:cursor-not-allowed disabled:opacity-60" aria-label="Send reply">
                    <span>Send Reply</span>
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-6-6l6 6-6 6"/></svg>
                </button>
            </div>
        </div>
    </div>
</div>
<!-- ./ra-app -->

<!-- Load jQuery first (CDN + local fallback) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    // Fallback to local jQuery if CDN is blocked/unavailable.
    if (!window.jQuery) {
        document.write('<script src="{{ asset('assets/node_modules_files/jquery-asColorPicker-master/libs/jquery.min.js') }}"><\/script>');
    }
</script>
<script>
    // Ensure jQuery aliases exist only when jQuery is present.
    if (window.jQuery) {
        window.$ = window.jQuery;
    }
</script>

{{-- helper.js ends with })(jQuery); must run immediately after jQuery is on window --}}
<script src="{{ asset('froiden-helper/helper.js') }}"></script>

<!-- DataTables -->
<script src="{{ asset('assets/plugins/datatables/jquery.dataTables.js') }}"></script>

<script src="{{ asset('assets/node_modules_files/sweetalert/sweetalert.min.js') }}"></script>
<script src="{{ asset('assets/node_modules_files/toast-master/js/jquery.toast.js') }}"></script>
<script src="{{ asset('js/cbpFWTabs.js') }}"></script>
<script defer src="{{ asset('js/candidate-calls.js') }}?v={{ filemtime(public_path('js/candidate-calls.js')) }}"></script>
@if(file_exists(public_path('assets/plugins/icheck/icheck.min.js')))
<script src="{{ asset('assets/plugins/icheck/icheck.min.js') }}"></script>
@endif
@if(file_exists(public_path('assets/plugins/icheck/icheck.init.js')))
<script src="{{ asset('assets/plugins/icheck/icheck.init.js') }}"></script>
@endif
<script src="{{ asset('assets/node_modules_files/Magnific-Popup-master/dist/jquery.magnific-popup.min.js') }}"></script>
<script src="{{ asset('assets/node_modules_files/Magnific-Popup-master/dist/jquery.magnific-popup-init.js') }}"></script>

<!-- Vite handles additional dependencies via app.js (lodash, axios) - loaded after jQuery -->
@vite(['resources/js/app.js'])

<script>
    $('body').on('click', '.right-side-toggle', function (e) {
        e.preventDefault();
        e.stopPropagation();
        if (window.raCloseRightSidebar) window.raCloseRightSidebar();
    });
    
    // Close sidebar when clicking on backdrop
    $(document).on('click', '#right-sidebar-backdrop', function (e) {
        e.preventDefault();
        e.stopPropagation();
        if (window.raCloseRightSidebar) window.raCloseRightSidebar();
    });

    window.raCloseRightSidebar = function () {
        var $sidebar = $("#right-sidebar");
        var $backdrop = $("#right-sidebar-backdrop");
        if ($sidebar.length === 0) return;
        $sidebar.removeClass('translate-x-0').addClass('translate-x-full');
        // Clear the overlay even when a profile opener added an inline
        // display:block style. The hidden utility alone cannot override that
        // inline style in every compiled CSS build.
        $backdrop.addClass('hidden').css('display', 'none');
    };

    window.raCloseStickyNotesSidebar = function () {
        var $panel = $('#sticky-notes-sidebar');
        var $bd = $('#sticky-notes-backdrop');
        if (!$panel.length) return;
        $panel.addClass('translate-x-full').removeClass('translate-x-0').attr('aria-hidden', 'true');
        $bd.addClass('hidden').attr('aria-hidden', 'true');
        $('#sticky-notes-trigger, #sticky-notes-trigger-mobile').attr('aria-expanded', 'false');
        $(document).off('keydown.raStickyNotes');
    };

    window.raOpenStickyNotesSidebar = function () {
        var $panel = $('#sticky-notes-sidebar');
        var $bd = $('#sticky-notes-backdrop');
        if (!$panel.length) return;
        if (window.raCloseRightSidebar) window.raCloseRightSidebar();
        $panel.removeClass('translate-x-full').addClass('translate-x-0').attr('aria-hidden', 'false');
        $bd.removeClass('hidden').attr('aria-hidden', 'false');
        $('#sticky-notes-trigger, #sticky-notes-trigger-mobile').attr('aria-expanded', 'true');
        $(document).off('keydown.raStickyNotes').on('keydown.raStickyNotes', function (e) {
            if (e.key === 'Escape') window.raCloseStickyNotesSidebar();
        });
    };

    window.raToggleStickyNotesSidebar = function () {
        var $panel = $('#sticky-notes-sidebar');
        if (!$panel.length) return;
        if ($panel.hasClass('translate-x-0')) {
            window.raCloseStickyNotesSidebar();
        } else {
            window.raOpenStickyNotesSidebar();
        }
    };

    $(document).on('click', '#sticky-notes-trigger, #sticky-notes-trigger-mobile', function (e) {
        e.preventDefault();
        window.raToggleStickyNotesSidebar();
    });
    $(document).on('click', '#sticky-notes-backdrop, #sticky-notes-close', function (e) {
        e.preventDefault();
        window.raCloseStickyNotesSidebar();
    });

    $(function () {
        if (typeof $.fn.selectpicker === 'function') {
            $('.selectpicker').selectpicker({
                style: 'btn-info',
                size: 4
            });
        }
    });

    function languageOptions() {
        return {
            processing:     "@lang('modules.datatables.processing')",
            search:         "@lang('modules.datatables.search')",
            lengthMenu:    "@lang('modules.datatables.lengthMenu')",
            info:           "@lang('modules.datatables.info')",
            infoEmpty:      "@lang('modules.datatables.infoEmpty')",
            infoFiltered:   "@lang('modules.datatables.infoFiltered')",
            infoPostFix:    "@lang('modules.datatables.infoPostFix')",
            loadingRecords: "@lang('modules.datatables.loadingRecords')",
            zeroRecords:    "@lang('modules.datatables.zeroRecords')",
            emptyTable:     "@lang('modules.datatables.emptyTable')",
            paginate: {
                first:      "@lang('modules.datatables.paginate.first')",
                previous:   "@lang('modules.datatables.paginate.previous')",
                next:       "@lang('modules.datatables.paginate.next')",
                last:       "@lang('modules.datatables.paginate.last')",
            },
            aria: {
                sortAscending:  "@lang('modules.datatables.aria.sortAscending')",
                sortDescending: "@lang('modules.datatables.aria.sortDescending')",
            },
        }
    }

    $('.language-switcher').change(function () {
        var lang = $(this).val();
        $.easyAjax({
            url: '{{ route("admin.language-settings.change-language") }}',
            data: {'lang': lang},
            success: function (data) {
                if (data.status == 'success') {
                    window.location.reload();
                }
            }
        });
    });

    $('#mark-notification-read').click(function () {
        var token = '{{ csrf_token() }}';
        $.easyAjax({
            type: 'POST',
            url: '{{ route("mark-notification-read") }}',
            data: {'_token': token},
            success: function (data) {
                if (data.status == 'success') {
                    $('.top-notifications').remove();
                    $('#top-notification-dropdown .notify').remove();
                    window.location.reload();
                }
            }
        });
    });

    // $('body').on('click', '.view-notification', function(event) {
    $(document).on('click', '.read-notification', function (event) {
            event.preventDefault();
            var id = $(this).data('notification-id');
            var dataUrl = $(this).data('link');

            $.easyAjax({
                url: "{{ route('mark_single_notification_read') }}",
                type: "POST",
                data: {
                    '_token': "{{ csrf_token() }}",
                    'id': id
                },
                success: function() {
                    if (typeof dataUrl !== 'undefined') {
                        window.location = dataUrl;
                    }
                }
            });
        });

    function addOrEditStickyNote(id)
    {
        var url = '';
        var method = 'POST';
        if(id === undefined || id == "" || id == null) {
            url =  '{{ route('admin.sticky-note.store') }}'
        } else{

            url = "{{ route('admin.sticky-note.update',':id') }}";
            url = url.replace(':id', id);
            var stickyID = $('#stickyID').val();
            method = 'PUT'
        }

        var noteText = $('#notetext').val();
        var stickyColor = $('#stickyColor').val();
        $.easyAjax({
            url: url,
            container: '#responsive-modal',
            type: method,
            data:{'notetext':noteText,'stickyColor':stickyColor,'_token':'{{ csrf_token() }}'},
            success: function (response) {
                $("#responsive-modal").addClass('hidden').css({display: '', visibility: '', opacity: ''});
                getNoteData();
            }
        });
    }

    // FOR SHOWING FEEDBACK DETAIL IN MODEL
    function showCreateNoteModal(){
        if (window.raCloseStickyNotesSidebar) window.raCloseStickyNotesSidebar();
        var url = '{{ route('admin.sticky-note.create') }}';
        $.ajaxModal('#responsive-modal', url);

        return false;
    }

    // FOR SHOWING FEEDBACK DETAIL IN MODEL
    function showEditNoteModal(id){
        if (window.raCloseStickyNotesSidebar) window.raCloseStickyNotesSidebar();
        var url = '{{ route('admin.sticky-note.edit',':id') }}';
        url  = url.replace(':id',id);
        $.ajaxModal('#responsive-modal', url);
        return false;
    }
    function selectColor(id){
        $('[data-sticky-swatch]').removeClass('ring-2 ring-offset-2 ring-slate-800').attr('aria-pressed', 'false');
        $('#' + id).addClass('ring-2 ring-offset-2 ring-slate-800').attr('aria-pressed', 'true');
        $('#stickyColor').val(id);
    }

    function deleteSticky(id){

        swal({
            title: "Are you sure?",
            text: "You will not be able to recover the deleted Sticky Note!",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Yes, delete it!",
            cancelButtonText: "No, cancel please!",
            closeOnConfirm: true,
            closeOnCancel: true
        }, function(isConfirm){
            if (isConfirm) {

                var url = "{{ route('admin.sticky-note.destroy',':id') }}";
                url = url.replace(':id', id);

                var token = "{{ csrf_token() }}";

                $.easyAjax({
                    type: 'POST',
                    url: url,
                    data: {'_token': token, '_method': 'DELETE'},
                    success: function (response) {
                        $('#stickyBox_'+id).fadeOut();
                        $("#responsive-modal").addClass('hidden').css({display: '', visibility: '', opacity: ''});
                        getNoteData();
                    }
                });
            }
        });
    }


    //getting all chat data according to user
    function getNoteData(){

        var url = "{{ route('admin.sticky-note.index') }}";

        $.easyAjax({
            type: 'GET',
            url: url,
            messagePosition: '',
            data:  {},
            container: ".noteBox",
            success: function (response) {
                $('#sticky-note-list').html(response.stickyNotes);
                var c = response.count;
                $('#sticky-note-count').text(c);
                $('#sticky-note-count-badge').text(c);
                $('#sticky-note-count-badge-mobile').text(c);
            }
        });
    }

    // search input implementation
    function search($input, doneTypingInterval, type) {
        var $anchor = $input.siblings('a');
        var typingTimer, fn;

        if (type == 'data') {
            fn = loadData;
        }
        if (type == 'table') {
            fn = redrawTable;                    
        }

        $input.on('keyup', function (e) {
            if ($(this).val() !== '' || ($(this).val().length >= 0 && e.key === 'Backspace')) {
                clearTimeout(typingTimer);
                typingTimer = setTimeout(() => {
                    fn();
                }, doneTypingInterval);
            }

            $(this).val() !== '' ? $anchor.removeClass('hidden') : $anchor.addClass('hidden');
        })

        $input.on('keydown', function () {
            clearTimeout(typingTimer);
        });

        $anchor.click(function(e) {
            $(this).siblings('input').val('');
            fn();
            $anchor.addClass('hidden');
            $(this).siblings('input').focus();
        })
    }

    window.raStickyNotesRefreshLayout = function () {};
    $('body').on('click', '.toggle-password', function() {
        var $selector = $(this).parent().find('input.form-control');
        $(this).toggleClass("fa-eye fa-eye-slash");
        var $type = $selector.attr("type") === "password" ? "text" : "password";
        $selector.attr("type", $type);
    });

    window.raToggleSidebar = function () {
        var app = document.getElementById('ra-app');
        if (!app) return;
        if (window.matchMedia('(max-width: 1023px)').matches) {
            app.classList.toggle('ra-mobile-nav-open');
            return;
        }
        app.classList.toggle('ra-sidebar-mini');
        var mini = app.classList.contains('ra-sidebar-mini');
        localStorage.setItem('raSidebarMini', mini ? '1' : '0');
        var ico = document.getElementById('ra-collapse-ico');
        if (ico) {
            ico.innerHTML = mini
                ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/>'
                : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>';
        }
        var btn = document.getElementById('ra-collapse-btn');
        if (btn) {
            btn.setAttribute('aria-expanded', mini ? 'false' : 'true');
            btn.setAttribute('aria-label', mini ? 'Expand sidebar' : 'Collapse sidebar');
            btn.title = mini ? 'Expand sidebar' : 'Collapse sidebar';
        }
    };

    $(document).ready(function () {
        var app = document.getElementById('ra-app');
        if (app && localStorage.getItem('raSidebarMini') === '1') {
            app.classList.add('ra-sidebar-mini');
            var ico = document.getElementById('ra-collapse-ico');
            if (ico) {
                ico.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/>';
            }
            var btn = document.getElementById('ra-collapse-btn');
            if (btn) {
                btn.setAttribute('aria-expanded', 'false');
                btn.setAttribute('aria-label', 'Expand sidebar');
                btn.title = 'Expand sidebar';
            }
        }
    });

    document.addEventListener('click', function (e) {
        var app = document.getElementById('ra-app');
        if (!app || !app.classList.contains('ra-mobile-nav-open')) return;
        var sb = document.getElementById('ra-sidebar');
        var t = e.target;
        if (sb && !sb.contains(t) && !(t.closest && t.closest('[data-ra-sidebar-toggle]'))) {
            app.classList.remove('ra-mobile-nav-open');
        }
    });

    $(document).on('click', '[data-ra-sidebar-toggle]', function (e) {
        e.preventDefault();
        e.stopPropagation();
        if (window.raToggleSidebar) window.raToggleSidebar();
    });

</script>

<script>
(function () {
    var syncUrl = @json(route('admin.ats-sync-state'));
    var presenceHeartbeatUrl = @json(route('admin.ats-presence-heartbeat'));
    var internalMessageReplyUrl = @json(route('admin.internal-messages.store'));
    // Automatic record refresh is intentionally disabled: it can overwrite another user's unsaved work.
    // Presence, notifications, and internal-message polling remain active below.
    var contentAutoRefreshEnabled = false;
    var lastSignature = null;
    var pendingRefresh = false;
    var refreshRunning = false;
    var internalPopupBaselineTime = null;
    var internalPopupLastNotificationId = null;

    function updatePresenceDots(onlineUserIds) {
        var online = {};
        (onlineUserIds || []).forEach(function (id) { online[String(id)] = true; });
        $('[data-ats-presence-user]').each(function () {
            var isOnline = !!online[String($(this).data('ats-presence-user'))];
            $(this)
                .toggleClass('bg-emerald-500', isOnline)
                .toggleClass('bg-red-500', !isOnline)
                .attr('title', isOnline ? 'Online' : 'Offline')
                .attr('aria-label', isOnline ? 'Online' : 'Offline');
        });
        $('[data-ats-presence-label]').each(function () {
            var isOnline = !!online[String($(this).data('ats-presence-label'))];
            $(this)
                .text(isOnline ? 'Online' : 'Offline')
                .toggleClass('text-emerald-600', isOnline)
                .toggleClass('text-red-500', !isOnline);
        });
        $('[data-ats-online-member]').each(function () {
            var isOnline = !!online[String($(this).data('ats-online-member'))];
            $(this).toggleClass('hidden', !isOnline);
        });
        var onlineCount = $('[data-ats-online-member]:not(.hidden)').length;
        $('#dashboard-online-count').text(onlineCount);
        $('#dashboard-online-empty').toggleClass('hidden', onlineCount > 0);
    }

    function showInternalMessagePopup(notice) {
        if (!notice || !notice.id) return;
        if (sessionStorage.getItem('dismissedInternalMessageNotification') === String(notice.id)) return;

        var $popup = $('#internal-message-popup');
        if (!$popup.length) return;
        if (!$popup.hasClass('hidden') && String($popup.attr('data-notification-id')) === String(notice.id)) return;
        $popup
            .attr('data-notification-id', notice.id)
            .attr('data-sender-id', notice.sender_id)
            .removeClass('hidden');
        $('#internal-message-popup-backdrop').removeClass('hidden');
        $('#internal-message-popup-sender').text(notice.sender_name || 'Team member');
        $('#internal-message-popup-avatar').text(String(notice.sender_name || 'T').trim().charAt(0).toUpperCase());
        $('#internal-message-popup-text').text(notice.message_text || '');
        $('#internal-message-popup-reply').val('');
        $('#internal-message-popup-status').addClass('hidden').text('Reply sent.');
    }

    $(document).on('click', '#internal-message-popup-close', function () {
        var notificationId = $('#internal-message-popup').attr('data-notification-id');
        if (notificationId) sessionStorage.setItem('dismissedInternalMessageNotification', String(notificationId));
        $('#internal-message-popup').addClass('hidden');
        $('#internal-message-popup-backdrop').addClass('hidden');
    });

    $(document).on('click', '#internal-message-popup-send', function () {
        var $button = $(this);
        var $popup = $('#internal-message-popup');
        var body = $.trim($('#internal-message-popup-reply').val());
        var recipientId = Number($popup.attr('data-sender-id'));
        if (!body || !recipientId || $button.prop('disabled')) return;

        $button.prop('disabled', true);
        $.ajax({
            url: internalMessageReplyUrl,
            type: 'POST',
            data: { _token: @json(csrf_token()), recipient_id: recipientId, body: body },
            global: false
        }).done(function () {
            $('#internal-message-popup-reply').val('');
            $('#internal-message-popup-status').removeClass('hidden text-red-500').addClass('text-emerald-600').text('Reply sent.');
        }).fail(function (xhr) {
            var message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Reply could not be sent.';
            $('#internal-message-popup-status').removeClass('hidden').removeClass('text-emerald-600').addClass('text-red-500').text(message);
        }).always(function () {
            $button.prop('disabled', false);
        });
    });
    function sendPresenceHeartbeat() {
        $.ajax({
            url: presenceHeartbeatUrl,
            type: 'POST',
            data: { _token: @json(csrf_token()) },
            global: false,
            timeout: 5000
        }).done(function (response) {
            updatePresenceDots(response.online_user_ids || []);
            var notificationCount = Number(response.unread_notification_count || 0);
            $('#top-notification-unread-count')
                .text(notificationCount)
                .toggleClass('hidden', notificationCount < 1);

            var internalUnreadCount = Number(response.unread_internal_message_count || 0);
            $('#internal-messages-sidebar-count')
                .text(internalUnreadCount)
                .toggleClass('hidden', internalUnreadCount < 1);

            var notice = response.latest_internal_notification;
            if (internalPopupBaselineTime === null) {
                internalPopupBaselineTime = Number(response.server_time || 0);
                internalPopupLastNotificationId = notice ? String(notice.id) : null;
            } else if (notice
                && String(notice.id) !== internalPopupLastNotificationId
                && Number(notice.created_at_timestamp || 0) >= internalPopupBaselineTime) {
                internalPopupLastNotificationId = String(notice.id);
                internalPopupBaselineTime = Math.max(internalPopupBaselineTime, Number(notice.created_at_timestamp || 0));
                var activeRecipient = Number($('[data-internal-active-recipient]').data('internal-active-recipient') || 0);
                if (activeRecipient !== Number(notice.sender_id)) showInternalMessagePopup(notice);
            }
            if (notice && !$('#top-notification-dropdown [data-internal-notification-id="' + notice.id + '"]').length) {
                var escapeNotice = function (value) { return $('<div>').text(value == null ? '' : String(value)).html(); };
                var conversationUrl = @json(route('admin.internal-messages.index')) + '?recipient=' + encodeURIComponent(notice.sender_id);
                var item = '<a href="javascript:;" data-link="' + escapeNotice(conversationUrl) + '" class="read-notification block" data-notification-id="' + escapeNotice(notice.id) + '" data-internal-notification-id="' + escapeNotice(notice.id) + '">'
                    + '<div class="flex items-start gap-3 border-b border-[#F0EEE9] px-4 py-3 transition hover:bg-[#F8F7F4]">'
                    + '<div style="width:32px;height:32px;border-radius:50%;background:#EFF6FF;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="fa fa-comments-o" style="color:#2563EB;font-size:13px;"></i></div>'
                    + '<div style="flex:1;min-width:0;"><div style="font-size:12.5px;font-weight:600;color:#1A1E2E;margin-bottom:2px;">' + escapeNotice(notice.sender_name) + ' sent you a message</div>'
                    + '<div style="font-size:12px;color:#5A6478;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:220px;">“' + escapeNotice(notice.message_text) + '”</div>'
                    + '<div style="font-size:10.5px;color:#B0B8C4;margin-top:3px;">' + escapeNotice(notice.time) + '</div></div>'
                    + '<span style="width:7px;height:7px;border-radius:50%;background:#2563EB;flex-shrink:0;margin-top:4px;"></span></div></a>';
                $('#top-notification-dropdown .max-h-96').prepend(item);
            }
        });
    }
    function userIsEditing() {
        var active = document.activeElement;
        var editable = active && (/^(INPUT|TEXTAREA|SELECT)$/.test(active.tagName) || active.isContentEditable);
        return editable || $('.modal:visible, .swal-overlay:visible, .sweet-alert:visible').length > 0;
    }

    function reloadOpenApplicantProfile(done) {
        var $profile = $('#right-sidebar-content .ja-two-col-wrap[data-ats-sync-url]');
        var $sidebar = $('#right-sidebar');
        if (!$profile.length || !$sidebar.hasClass('translate-x-0')) {
            done();
            return;
        }

        var activeTab = $profile.find('.ja-tab.active').data('tab') || null;
        $.ajax({ url: $profile.data('ats-sync-url'), type: 'GET', cache: false })
            .done(function (response) {
                if (response && response.status === 'success' && response.view) {
                    $('#right-sidebar-content').html(response.view);
                    if (activeTab) {
                        $('#right-sidebar-content .ja-tab[data-tab="' + activeTab + '"]').trigger('click');
                    }
                }
            }).always(done);
    }

    function applyRemoteChanges() {
        if (!contentAutoRefreshEnabled || !pendingRefresh || refreshRunning || userIsEditing()) return;
        refreshRunning = true;
        pendingRefresh = false;

        var hasDataTable = false;
        if ($.fn.dataTable) {
            var tables = $.fn.dataTable.tables({ visible: true, api: true });
            hasDataTable = !!(tables && tables.context && tables.context.length);
            if (hasDataTable) tables.ajax.reload(null, false);
        }

        reloadOpenApplicantProfile(function () {
            refreshRunning = false;
            // Never reload the whole page for another team member's update.
            // Tables and the open applicant profile are refreshed in place above;
            // static pages can opt into targeted updates through this event.
            document.dispatchEvent(new CustomEvent('ats:remote-change', {
                detail: { dataTableRefreshed: hasDataTable }
            }));
        });
    }

    function checkForChanges() {
        if (!contentAutoRefreshEnabled || document.hidden || refreshRunning) return;
        $.ajax({ url: syncUrl, type: 'GET', cache: false, global: false, timeout: 5000 })
            .done(function (response) {
                if (!response || !response.signature) return;
                if (lastSignature === null) {
                    lastSignature = response.signature;
                    return;
                }
                if (lastSignature !== response.signature) {
                    lastSignature = response.signature;
                    pendingRefresh = true;
                }
                applyRemoteChanges();
            });
    }

    if (contentAutoRefreshEnabled) setInterval(checkForChanges, 7000);
    setInterval(sendPresenceHeartbeat, 5000);
    setTimeout(sendPresenceHeartbeat, 300);
    if (contentAutoRefreshEnabled) setInterval(applyRemoteChanges, 1500);
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) {
            if (contentAutoRefreshEnabled) checkForChanges();
            sendPresenceHeartbeat();
        }
    });
    if (contentAutoRefreshEnabled) setTimeout(checkForChanges, 1200);
})();
</script>
@endif
@stack('footer-script')

</body>
</html>
