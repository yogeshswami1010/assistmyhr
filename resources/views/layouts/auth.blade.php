<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full" style="--main-color: {{ e($frontTheme->primary_color) }}">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <link rel="icon" href="{{ $companySetting->favicon_url }}"  />
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="{{ asset('favicon/ms-icon-144x144.png') }}">
    <meta name="theme-color" content="#ffffff">

    <title>{{ $setting->company_name ?? config('app.name') }}</title>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-full font-jakarta antialiased">
    <div class="flex min-h-screen">

        <!-- Left -->
        <div class="relative hidden min-h-screen flex-1 flex-col justify-between overflow-hidden bg-[linear-gradient(160deg,#0F1F3D_0%,#162849_55%,#1B3560_100%)] px-14 py-12 before:pointer-events-none before:absolute before:inset-0 before:bg-[radial-gradient(ellipse_60%_60%_at_80%_30%,rgba(37,99,235,0.22)_0%,transparent_65%),radial-gradient(ellipse_40%_50%_at_10%_80%,rgba(5,150,105,0.12)_0%,transparent_60%)] after:pointer-events-none after:absolute after:inset-0 after:bg-[radial-gradient(circle,rgba(255,255,255,0.045)_1px,transparent_1px)] after:[background-size:28px_28px] lg:flex">
            <div class="relative z-10" style="
                    display: none;
                ">
                <div class="flex animate-auth-slide-in items-center gap-3 opacity-0 [animation-delay:100ms]">
                    <img src="{{ $setting->logo_url }}" alt="" class="max-h-8 " />
                    <span class="text-xl font-extrabold tracking-tight text-white">{{ $setting->company_name }}</span>
                </div>
            </div>

            <div class="relative z-10 max-w-md">
                <div class="mb-3 animate-auth-slide-in opacity-0 [animation-delay:200ms]">
                    <span class="inline-flex items-center gap-2 rounded-full bg-white/[0.08] px-3 py-1.5 text-[11px] font-bold uppercase tracking-widest text-white/55">
                        <span class="inline-block h-1.5 w-1.5 rounded-full bg-green-400"></span>
                        {{ __('Admin Portal') }}
                    </span>
                </div>
                <h1 class="mb-4 animate-auth-slide-in text-[clamp(32px,3.5vw,48px)] font-extrabold leading-[1.1] tracking-tight text-white opacity-0 [animation-delay:200ms]">
                    {{ __('Manage your') }}<br>
                    <span class="font-instrument text-[clamp(32px,3.5vw,48px)] font-normal italic text-blue-300">{{ __('recruitment') }}</span><br>
                    {{ __('pipeline') }}
                </h1>
                <p class="animate-auth-slide-in text-[15px] leading-relaxed text-white/50 opacity-0 [animation-delay:300ms]">
                    {{ __('Everything you need to hire great talent — job postings, candidates, interviews, and reports in one place.') }}
                </p>

                <div class="mt-8 grid grid-cols-3 gap-3 animate-auth-slide-in opacity-0 [animation-delay:400ms]">
                    <div class="rounded-[14px] border border-white/10 bg-white/[0.07] px-[18px] py-4 backdrop-blur-md">
                        <p class="text-[22px] font-extrabold tracking-tight text-white">20+</p>
                        <p class="mt-0.5 text-[11.5px] text-white/45">{{ __('Applications') }}</p>
                    </div>
                    <div class="rounded-[14px] border border-white/10 bg-white/[0.07] px-[18px] py-4 backdrop-blur-md">
                        <p class="text-[22px] font-extrabold tracking-tight text-white">4</p>
                        <p class="mt-0.5 text-[11.5px] text-white/45">{{ __('Hired this month') }}</p>
                    </div>
                    <div class="rounded-[14px] border border-white/10 bg-white/[0.07] px-[18px] py-4 backdrop-blur-md">
                        <p class="text-[22px] font-extrabold tracking-tight text-white">7</p>
                        <p class="mt-0.5 text-[11.5px] text-white/45">{{ __('Interviews today') }}</p>
                    </div>
                </div>
            </div>

            <div class="relative z-10 flex flex-wrap items-center gap-3 animate-auth-slide-in opacity-0 [animation-delay:400ms]">
                <div class="flex items-center gap-2.5 rounded-[10px] border border-white/[0.14] bg-white/10 px-3.5 py-2.5 backdrop-blur-md">
                    <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-400/20">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="#34D399" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold text-white">{{ __('Secure Login') }}</p>
                        <p class="text-[10px] text-white/40">{{ __('256-bit SSL encryption') }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2.5 rounded-[10px] border border-white/[0.14] bg-white/10 px-3.5 py-2.5 backdrop-blur-md">
                    <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-300/20">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="#93C5FD" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold text-white">{{ __('Instant Access') }}</p>
                        <p class="text-[10px] text-white/40">{{ __('All features unlocked') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right -->
        <div class="relative flex w-full flex-col justify-center overflow-y-auto bg-white px-10 py-12 lg:min-w-[480px] lg:w-[480px]">
            <div class="mb-10 flex animate-auth-fade-up items-center gap-2.5 opacity-0 [animation-delay:50ms] lg:hidden">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-[#2563EB]">
                    @if (!empty($setting->logo_url))
                        <img src="{{ $setting->logo_url }}" alt="" class="max-h-7 max-w-[1.75rem] object-contain brightness-0 invert" />
                    @else
                        <svg width="16" height="16" viewBox="0 0 14 14" fill="none"><path d="M2 2h4.5a3.5 3.5 0 010 7H2V2z" fill="#fff"/><circle cx="10" cy="11.5" r="2" fill="#fff" opacity="0.6"/></svg>
                    @endif
                </div>
                <span class="text-lg font-extrabold tracking-tight text-[#0F1F3D]">{{ $setting->company_name }}</span>
            </div>

            @yield('content')
        </div>
    </div>

    <script>
        document.getElementById('to-recover')?.addEventListener('click', function (e) {
            e.preventDefault();
            var loginForm = document.getElementById('loginform');
            var recoverForm = document.getElementById('recoverform');
            if (loginForm && recoverForm) {
                loginForm.classList.add('hidden');
                recoverForm.classList.remove('hidden');
            }
        });
        document.getElementById('back-to-login')?.addEventListener('click', function (e) {
            e.preventDefault();
            var loginForm = document.getElementById('loginform');
            var recoverForm = document.getElementById('recoverform');
            if (loginForm && recoverForm) {
                recoverForm.classList.add('hidden');
                loginForm.classList.remove('hidden');
            }
        });
    </script>
    @stack('scripts')
</body>

</html>
