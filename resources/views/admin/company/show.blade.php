@extends('layouts.app')

@section('hide-ra-page-header')

@push('head-script')
    <link rel="stylesheet" href="{{ asset('assets/node_modules_files/dropify/dist/css/dropify.min.css') }}">
    <style>
        @keyframes raCoFu { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes raCoMIn { from { opacity: 0; transform: scale(0.94) translateY(10px); } to { opacity: 1; transform: scale(1) translateY(0); } }
        .ra-co-a1 { opacity: 0; animation: raCoFu 0.4s ease 0.04s forwards; }
        .ra-co-a2 { opacity: 0; animation: raCoFu 0.4s ease 0.09s forwards; }
        .ra-co-a3 { opacity: 0; animation: raCoFu 0.4s ease 0.14s forwards; }
        .ra-co-a4 { opacity: 0; animation: raCoFu 0.4s ease 0.19s forwards; }
        .ra-co-menter { animation: raCoMIn 0.28s cubic-bezier(0.22, 1, 0.36, 1) forwards; }
        .ra-co-sel {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='7' fill='none'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%239CA3AF' stroke-width='1.6' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
        }
    </style>
@endpush

@php
    $webRaw = trim((string) ($company->website ?? ''));
    $webHref = $webRaw !== '' ? (preg_match('#^https?://#i', $webRaw) ? $webRaw : 'https://' . $webRaw) : '';
    $webLabel = $webRaw !== '' ? preg_replace('#^https?://#i', '', $webRaw) : '';
@endphp

@section('page-title-html')
<div>
    <h1 class="text-[22px] font-extrabold tracking-tight text-[#0F1F3D]">
        @lang('menu.companies')
        <span class="text-[20px] font-normal italic text-[#2563EB]" style="font-family:'Instrument Serif',serif;">Profile</span>
    </h1>
   
</div>
@endsection

@section('page-subtitle')
<p class="mt-1 text-[12.5px] text-[#8892A0]">@lang('modules.company.profileHeroSubtitle')</p>
@endsection

@if(in_array('edit_company', $userPermissions))
@section('create-button')
<div class="flex flex-wrap items-center gap-3">
    @if ($user->cans('edit_company'))
        <button type="button" onclick="window.raCompanyOpenEdit && window.raCompanyOpenEdit()" class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-[#E8E6E1] bg-white px-4 py-2.5 text-[13px] font-semibold text-slate-600 transition-all hover:border-[#2563EB] hover:bg-gray-50 hover:text-[#2563EB]">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            @lang('app.edit') @lang('menu.profile')
        </button>
    @endif
    <a href="{{ route('admin.company.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-[#E8E6E1] bg-[#F8F7F4] px-4 py-2.5 text-[13px] font-semibold text-[#5A6478] transition hover:border-[#2563EB] hover:text-[#2563EB]">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        @lang('app.back')
    </a>
</div>
@endsection
@endif

@section('content')
<div class="mx-auto  pb-8">
   

    {{-- Hero --}}
    <div class="mb-5 overflow-hidden rounded-2xl border border-[#E8E6E1] bg-white ra-co-a2">
        <div class="relative h-36" style="background:linear-gradient(135deg,#0F1F3D 0%,#162849 50%,#1B3560 100%);">
            <div class="absolute inset-0" style="background-image:radial-gradient(circle,rgba(255,255,255,0.05) 1px,transparent 1px);background-size:24px 24px;"></div>
            <div class="absolute right-0 top-0 bottom-0 w-64" style="background:radial-gradient(ellipse at right,rgba(37,99,235,0.18),transparent 70%);"></div>

            <div class="absolute left-6 -bottom-10">
                <div class="relative">
                    <button type="button" class="relative flex h-20 w-20 cursor-pointer items-center justify-center overflow-hidden rounded-2xl border-4 border-white bg-[#0F1F3D] shadow-xl transition-opacity hover:opacity-90 {{ $user->cans('edit_company') ? '' : 'cursor-default' }}" @if($user->cans('edit_company')) onclick="document.getElementById('companyShowLogoFile')?.click()" @endif aria-label="@lang('modules.accountSettings.companyLogo')">
                        @if ($company->logo)
                            <img src="{{ $company->logo_url }}" alt="" class="h-full w-full object-cover" id="companyShowLogoPreview" />
                        @else
                            <span id="companyShowLogoPlaceholder" class="flex h-full w-full items-center justify-center">
                                <svg class="h-8 w-8 text-white/40" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            </span>
                            <img src="" alt="" class="hidden h-full w-full object-cover" id="companyShowLogoPreview" />
                        @endif
                    </button>
                    @if ($user->cans('edit_company'))
                        <button type="button" class="absolute -bottom-1 -right-1 flex h-6 w-6 cursor-pointer items-center justify-center rounded-full bg-[#2563EB] transition-colors hover:bg-blue-700" onclick="document.getElementById('companyShowLogoFile')?.click()" aria-label="@lang('app.edit') @lang('modules.accountSettings.companyLogo')">
                            <svg class="h-3 w-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </button>
                    @endif
                </div>
            </div>

            <div class="absolute right-6 top-4 flex items-center gap-2 rounded-xl border border-white/15 bg-white/10 px-3 py-1.5 backdrop-blur-sm">
                <svg class="h-3.5 w-3.5 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span class="text-[11.5px] font-semibold text-white/80">@lang('modules.company.registeredOn') {{ $company->created_at->format('d M, Y') }}</span>
            </div>
        </div>

        <div class="flex flex-col gap-6 px-6 pb-5 pt-14 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0">
                <h2 class="text-[20px] font-extrabold tracking-tight text-[#0F1F3D]">{{ ucwords($company->company_name) }}</h2>
                <div class="mt-1.5 flex flex-wrap items-center gap-2">
                    @if ($company->status === 'active')
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-[12px] font-semibold text-emerald-700">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500" aria-hidden="true"></span>@lang('app.active')
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-2.5 py-1 text-[12px] font-semibold text-rose-700">
                            <span class="h-1.5 w-1.5 rounded-full bg-rose-500" aria-hidden="true"></span>@lang('app.inactive')
                        </span>
                    @endif
                    @if ($webHref !== '')
                        <span class="text-[12px] text-[#8892A0]" aria-hidden="true">·</span>
                        <a href="{{ $webHref }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 text-[12.5px] font-medium text-[#2563EB] hover:underline">
                            <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                            {{ $webLabel }}
                        </a>
                    @endif
                </div>
            </div>
            <div class="flex flex-shrink-0 flex-wrap items-center gap-3">
                <div class="rounded-xl bg-[#EEF0F5] px-4 py-2 text-center">
                    <p class="text-[18px] font-extrabold leading-none text-[#0F1F3D]">{{ number_format($companyJobsCount) }}</p>
                    <p class="mt-0.5 text-[10.5px] font-semibold text-[#8892A0]">@lang('menu.jobs')</p>
                </div>
                <div class="rounded-xl bg-[#EEF0F5] px-4 py-2 text-center">
                    <p class="text-[18px] font-extrabold leading-none text-[#0F1F3D]">{{ number_format($companyApplicationsCount) }}</p>
                    <p class="mt-0.5 text-[10.5px] font-semibold text-[#8892A0]">@lang('menu.jobApplications')</p>
                </div>
                <div class="rounded-xl bg-[#EEF0F5] px-4 py-2 text-center">
                    <p class="text-[18px] font-extrabold leading-none text-[#0F1F3D]">{{ number_format($companyHiredCount) }}</p>
                    <p class="mt-0.5 text-[10.5px] font-semibold text-[#8892A0]">@lang('modules.jobApplicationStatus.hired')</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Info grid --}}
    <div class="mb-5 grid grid-cols-1 gap-5 md:grid-cols-2 ra-co-a3">
        <div class="rounded-2xl border border-[#E8E6E1] bg-white p-5">
            <div class="mb-4 flex items-center gap-2.5">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-blue-50">
                    <svg class="h-4 w-4 text-[#2563EB]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </div>
                <h3 class="text-[13.5px] font-bold text-[#0F1F3D]">@lang('modules.company.contactInformation')</h3>
            </div>
            <div class="flex flex-col gap-4">
                <div class="flex items-center gap-3 rounded-xl bg-[#EEF0F5] p-3">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white shadow-sm">
                        <svg class="h-4 w-4 text-[#2563EB]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10.5px] font-bold uppercase tracking-widest text-[#8892A0]">@lang('modules.accountSettings.companyEmail')</p>
                        <p class="truncate text-[13.5px] font-semibold text-[#0F1F3D]">{{ $company->company_email }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 rounded-xl bg-[#EEF0F5] p-3">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white shadow-sm">
                        <svg class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10.5px] font-bold uppercase tracking-widest text-[#8892A0]">@lang('modules.accountSettings.companyPhone')</p>
                        <p class="text-[13.5px] font-semibold text-[#0F1F3D]">{{ $company->company_phone ?: '—' }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 rounded-xl bg-[#EEF0F5] p-3">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white shadow-sm">
                        <svg class="h-4 w-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10.5px] font-bold uppercase tracking-widest text-[#8892A0]">@lang('modules.accountSettings.companyWebsite')</p>
                        @if ($webHref !== '')
                            <a href="{{ $webHref }}" target="_blank" rel="noopener noreferrer" class="break-all text-[13.5px] font-semibold text-[#2563EB] hover:underline">{{ $company->website }}</a>
                        @else
                            <p class="text-[13.5px] font-semibold text-[#8892A0]">—</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-[#E8E6E1] bg-white p-5">
            <div class="mb-4 flex items-center gap-2.5">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-orange-50">
                    <svg class="h-4 w-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <h3 class="text-[13.5px] font-bold text-[#0F1F3D]">@lang('menu.locations') &amp; @lang('app.details')</h3>
            </div>
            <div class="flex flex-col gap-4">
                <div class="flex items-start gap-3 rounded-xl bg-[#EEF0F5] p-3">
                    <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white shadow-sm">
                        <svg class="h-4 w-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10.5px] font-bold uppercase tracking-widest text-[#8892A0]">@lang('modules.accountSettings.companyAddress')</p>
                        <div class="text-[13.5px] font-semibold leading-snug text-[#0F1F3D] prose prose-sm max-w-none">{!! $company->address ? $company->address : '—' !!}</div>
                    </div>
                </div>
                <div class="flex items-center gap-3 rounded-xl bg-[#EEF0F5] p-3">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white shadow-sm">
                        <svg class="h-4 w-4 text-[#0F1F3D]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10.5px] font-bold uppercase tracking-widest text-[#8892A0]">@lang('modules.accountSettings.companyName')</p>
                        <p class="text-[13.5px] font-semibold text-[#0F1F3D]">{{ ucwords($company->company_name) }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 rounded-xl bg-[#EEF0F5] p-3">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white shadow-sm">
                        <svg class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-[10.5px] font-bold uppercase tracking-widest text-[#8892A0]">@lang('app.status')</p>
                        @if ($company->status === 'active')
                            <span class="inline-flex items-center gap-1.5 text-[12.5px] font-bold text-emerald-700">
                                <span class="h-2 w-2 rounded-full bg-emerald-500" aria-hidden="true"></span>@lang('app.active')
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 text-[12.5px] font-bold text-rose-700">
                                <span class="h-2 w-2 rounded-full bg-rose-500" aria-hidden="true"></span>@lang('app.inactive')
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick actions --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 ra-co-a4">
        @if ($user->cans('edit_company'))
            <button type="button" onclick="window.raCompanyOpenEdit && window.raCompanyOpenEdit()" class="group flex cursor-pointer flex-col items-center gap-2 rounded-2xl border border-[#E8E6E1] bg-white p-4 transition-all hover:border-[#2563EB] hover:bg-blue-50/30">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 transition-colors group-hover:bg-[#2563EB]">
                    <svg class="h-5 w-5 text-[#2563EB] transition-colors group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </div>
                <span class="text-center text-[12.5px] font-semibold text-slate-600 transition-colors group-hover:text-[#2563EB]">@lang('app.edit') @lang('menu.profile')</span>
            </button>
        @endif
        @if ($user->cans('view_jobs'))
            <a href="{{ route('admin.jobs.index', ['company' => $company->id]) }}" class="group flex flex-col items-center gap-2 rounded-2xl border border-[#E8E6E1] bg-white p-4 transition-all hover:border-emerald-400 hover:bg-emerald-50/30">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50">
                    <svg class="h-5 w-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </div>
                <span class="text-center text-[12.5px] font-semibold text-slate-600">@lang('menu.jobs')</span>
            </a>
        @endif
        @if ($user->cans('view_job_applications'))
            <a href="{{ route('admin.job-applications.index') }}" class="group flex flex-col items-center gap-2 rounded-2xl border border-[#E8E6E1] bg-white p-4 transition-all hover:border-purple-400 hover:bg-purple-50/30">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-50">
                    <svg class="h-5 w-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <span class="text-center text-[12.5px] font-semibold text-slate-600">@lang('menu.jobApplications')</span>
            </a>
        @endif
        <a href="{{ route('admin.report.index') }}" class="group flex flex-col items-center gap-2 rounded-2xl border border-[#E8E6E1] bg-white p-4 transition-all hover:border-orange-400 hover:bg-orange-50/30">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-orange-50">
                <svg class="h-5 w-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <span class="text-center text-[12.5px] font-semibold text-slate-600">@lang('app.reports')</span>
        </a>
    </div>
</div>

@if ($user->cans('edit_company'))
<div id="companyShowEditBg" onclick="window.raCompanyCloseEdit && window.raCompanyCloseEdit(event)" class="fixed inset-0 z-[60] hidden items-center justify-center bg-[#0F1F3D]/50 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="companyShowEditTitle">
    <div class="ra-co-menter max-h-[95vh] w-[540px] max-w-[95vw] overflow-hidden rounded-2xl bg-white shadow-2xl" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-5">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50">
                    <svg class="h-[18px] w-[18px] text-[#2563EB]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
                <div>
                    <h3 id="companyShowEditTitle" class="text-[15.5px] font-bold text-[#0F1F3D]">@lang('app.edit') @lang('menu.companies')</h3>
                    <p class="mt-0.5 text-[11.5px] text-[#8892A0]">@lang('modules.company.editModalSubtitle')</p>
                </div>
            </div>
            <button type="button" onclick="window.raCompanyCloseEdit && window.raCompanyCloseEdit()" class="flex h-8 w-8 cursor-pointer items-center justify-center rounded-lg border-0 bg-transparent text-[#8892A0] transition-colors hover:bg-gray-100" aria-label="@lang('app.close')">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form id="companyShowEditForm" class="ajax-form">
            @csrf
            @method('PUT')
            <div class="max-h-[70vh] overflow-y-auto px-6 py-5">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="company_show_name" class="mb-1.5 block text-[11.5px] font-bold uppercase tracking-wide text-slate-500">@lang('modules.accountSettings.companyName') <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            <input type="text" name="company_name" id="company_show_name" value="{{ $company->company_name }}" required class="w-full rounded-xl border border-gray-200 py-2.5 pl-9 pr-3.5 text-[13px] text-[#0F1F3D] outline-none transition-all focus:border-[#2563EB] focus:ring-2 focus:ring-[#2563EB]/10" />
                        </div>
                    </div>
                    <div>
                        <label for="company_show_email" class="mb-1.5 block text-[11.5px] font-bold uppercase tracking-wide text-slate-500">@lang('modules.accountSettings.companyEmail') <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            <input type="email" name="company_email" id="company_show_email" value="{{ $company->company_email }}" required class="w-full rounded-xl border border-gray-200 py-2.5 pl-9 pr-3.5 text-[13px] text-[#0F1F3D] outline-none transition-all focus:border-[#2563EB] focus:ring-2 focus:ring-[#2563EB]/10" />
                        </div>
                    </div>
                    <div>
                        <label for="company_show_phone" class="mb-1.5 block text-[11.5px] font-bold uppercase tracking-wide text-slate-500">@lang('modules.accountSettings.companyPhone')</label>
                        <div class="relative">
                            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            <input type="tel" name="company_phone" id="company_show_phone" value="{{ $company->company_phone }}" class="w-full rounded-xl border border-gray-200 py-2.5 pl-9 pr-3.5 text-[13px] text-[#0F1F3D] outline-none transition-all focus:border-[#2563EB] focus:ring-2 focus:ring-[#2563EB]/10" />
                        </div>
                    </div>
                    <div>
                        <label for="company_show_website" class="mb-1.5 block text-[11.5px] font-bold uppercase tracking-wide text-slate-500">@lang('modules.accountSettings.companyWebsite')</label>
                        <div class="relative">
                            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                            <input type="text" name="website" id="company_show_website" value="{{ $company->website }}" class="w-full rounded-xl border border-gray-200 py-2.5 pl-9 pr-3.5 text-[13px] text-[#0F1F3D] outline-none transition-all focus:border-[#2563EB] focus:ring-2 focus:ring-[#2563EB]/10" />
                        </div>
                    </div>
                    <div>
                        <label for="company_show_status" class="mb-1.5 block text-[11.5px] font-bold uppercase tracking-wide text-slate-500">@lang('app.status')</label>
                        <select name="status" id="company_show_status" class="ra-co-sel w-full cursor-pointer rounded-xl border border-gray-200 bg-white py-2.5 pl-3.5 pr-9 text-[13px] text-[#0F1F3D] outline-none transition-all focus:border-[#2563EB] focus:ring-2 focus:ring-[#2563EB]/10">
                            <option value="active" @selected($company->status == 'active')>@lang('app.active')</option>
                            <option value="inactive" @selected($company->status == 'inactive')>@lang('app.inactive')</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="company_show_address" class="mb-1.5 block text-[11.5px] font-bold uppercase tracking-wide text-slate-500">@lang('modules.accountSettings.companyAddress')</label>
                        <textarea name="address" id="company_show_address" rows="3" class="w-full resize-none rounded-xl border border-gray-200 px-3.5 py-2.5 text-[13px] text-[#0F1F3D] outline-none transition-all focus:border-[#2563EB] focus:ring-2 focus:ring-[#2563EB]/10">{{ $company->address }}</textarea>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="company_show_frontend" class="mb-1.5 block text-[11.5px] font-bold uppercase tracking-wide text-slate-500">@lang('modules.company.showFrontend')</label>
                        <select name="show_in_frontend" id="company_show_frontend" class="ra-co-sel w-full cursor-pointer rounded-xl border border-gray-200 bg-white py-2.5 pl-3.5 pr-9 text-[13px] text-[#0F1F3D] outline-none transition-all focus:border-[#2563EB] focus:ring-2 focus:ring-[#2563EB]/10">
                            <option value="true" @selected($company->show_in_frontend == 'true')>@lang('app.yes')</option>
                            <option value="false" @selected($company->show_in_frontend == 'false')>@lang('app.no')</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-[11.5px] font-bold uppercase tracking-wide text-slate-500">@lang('modules.accountSettings.companyLogo')</label>
                        <div class="rounded-xl bg-[#F8F7F4] p-3">
                            <input type="file" id="companyShowLogoFile" accept="image/*" class="hidden" />
                            <input type="file" id="companyShowLogoDropify" name="logo" class="dropify" data-max-file-size="2M"
                                @if (is_null($company->logo)) data-default-file="{{ asset('logo-not-found.png') }}"
                                @else data-default-file="{{ asset_url_local_s3('company-logo/'.$company->logo) }}"
                                @endif />
                        </div>
                        <p class="mt-1 text-[11px] text-[#8892A0]">@lang('app.dragDrop')</p>
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-end gap-3 border-t border-gray-100 px-6 py-4">
                <button type="button" onclick="window.raCompanyCloseEdit && window.raCompanyCloseEdit()" class="cursor-pointer rounded-xl border-0 bg-gray-100 px-5 py-2.5 text-[13px] font-semibold text-slate-600 transition-colors hover:bg-gray-200">@lang('app.cancel')</button>
                <button type="button" id="companyShowSaveBtn" class="inline-flex cursor-pointer items-center gap-2 rounded-xl border-0 bg-[#2563EB] px-6 py-2.5 text-[13px] font-bold text-white transition-all hover:bg-blue-700 hover:shadow-lg hover:shadow-blue-500/30">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    @lang('app.save')
                </button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection

@push('footer-script')
@if ($user->cans('edit_company'))
<script src="{{ asset('assets/node_modules_files/dropify/dist/js/dropify.min.js') }}"></script>
<script>
(function () {
    var bg = document.getElementById('companyShowEditBg');
    if (!bg) return;

    function openEdit() {
        bg.classList.remove('hidden');
        bg.classList.add('flex');
        var box = bg.querySelector('.ra-co-menter');
        if (box) {
            box.classList.remove('ra-co-menter');
            void box.offsetWidth;
            box.classList.add('ra-co-menter');
        }
        setTimeout(function () {
            var el = document.getElementById('company_show_name');
            if (el) el.focus();
        }, 80);
    }

    function closeEdit(e) {
        if (e && e.target !== bg) return;
        bg.classList.add('hidden');
        bg.classList.remove('flex');
    }

    window.raCompanyOpenEdit = openEdit;
    window.raCompanyCloseEdit = closeEdit;

    document.addEventListener('keydown', function (ev) {
        if (ev.key === 'Escape' && bg.classList.contains('flex')) closeEdit();
    });

    $('.dropify').dropify({
        messages: {
            default: @json(__('app.dragDrop')),
            replace: @json(__('app.dragDropReplace')),
            remove: @json(__('app.remove')),
            error: @json(__('app.largeFile'))
        }
    });

    var logoFile = document.getElementById('companyShowLogoFile');
    var dropifyInput = document.getElementById('companyShowLogoDropify');

    function syncLogoToDropify() {
        if (!logoFile || !logoFile.files || !logoFile.files[0] || !dropifyInput) return;
        try {
            var dr = $(dropifyInput).data('dropify');
            if (dr && dr.clearElement) dr.clearElement();
        } catch (err) {}
        var dt = new DataTransfer();
        dt.items.add(logoFile.files[0]);
        dropifyInput.files = dt.files;
        $(dropifyInput).trigger('change');
    }

    if (logoFile) {
        logoFile.addEventListener('change', function () {
            syncLogoToDropify();
            var f = logoFile.files && logoFile.files[0];
            var prev = document.getElementById('companyShowLogoPreview');
            var ph = document.getElementById('companyShowLogoPlaceholder');
            if (f && prev) {
                var r = new FileReader();
                r.onload = function (e) {
                    prev.src = e.target.result;
                    prev.classList.remove('hidden');
                    if (ph) ph.classList.add('hidden');
                };
                r.readAsDataURL(f);
            }
        });
    }

    $('#companyShowSaveBtn').on('click', function () {
        syncLogoToDropify();
        $.easyAjax({
            url: @json(route('admin.company.update', $company->id)),
            container: '#companyShowEditForm',
            type: 'POST',
            redirect: true,
            file: true
        });
    });
})();
</script>
@endif
@endpush
