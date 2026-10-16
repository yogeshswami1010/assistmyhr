@extends('layouts.app')

@push('head-script')
    <style>
        .jobs-action-btn { width: 30px; height: 30px; border-radius: 8px; border: none; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: transform 0.15s, box-shadow 0.15s; position: relative; }
        .jobs-action-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 10px rgba(0,0,0,0.10); }
        .jobs-action-btn::after {
            content: attr(data-tip); position: absolute; bottom: calc(100% + 6px); left: 50%; transform: translateX(-50%);
            background: #1A1E2E; color: #fff; font-size: 11px; font-weight: 600; padding: 4px 9px; border-radius: 6px;
            white-space: nowrap; pointer-events: none; opacity: 0; transition: opacity 0.15s; z-index: 20;
        }
        .jobs-action-btn::before {
            content: ''; position: absolute; bottom: calc(100% + 2px); left: 50%; transform: translateX(-50%);
            border: 4px solid transparent; border-top-color: #1A1E2E; pointer-events: none; opacity: 0; transition: opacity 0.15s;
        }
        .jobs-action-btn:hover::after, .jobs-action-btn:hover::before { opacity: 1; }
        .jobs-cb-wrap input[type=checkbox] { position: absolute; opacity: 0; width: 0; height: 0; }
        .jobs-cb-box { width: 16px; height: 16px; border: 2px solid #D1D9E8; border-radius: 4px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.15s; flex-shrink: 0; }
        .jobs-cb-wrap input:checked ~ .jobs-cb-box { background: #2563EB; border-color: #2563EB; }
        .jobs-cb-wrap input:checked ~ .jobs-cb-box .jobs-cb-chk { display: block; }
        .jobs-cb-chk { display: none; }
    </style>
@endpush

@php
    $canEditJobs = $user->cans('edit_jobs');
    $canDeleteJobs = $user->cans('delete_jobs');
    $canAddJobs = $user->cans('add_jobs');
@endphp

@section('page-title-html')
    <span class="text-[22px] font-bold tracking-tight text-[#1A1E2E]">@lang('menu.jobs')</span>
@endsection

@section('page-subtitle')
    @lang('modules.dashboard.totalJobs') · @lang('app.status') · @lang('menu.locations') — @lang('modules.jobApplication.boardPipelineHint')
@endsection

@if(in_array('add_jobs', $userPermissions))
    @section('create-button')
        <div class="flex flex-wrap items-center gap-3">
            @if (in_array('view_question', $userPermissions))
                <a href="{{ route('admin.questions.index') }}" class="inline-flex items-center gap-1.5 rounded-lg border-[1.5px] border-[#E2DED8] bg-white px-3.5 py-2 text-[12.5px] font-semibold text-[#5A6478] transition hover:border-[#2563EB] hover:bg-[#EFF6FF] hover:text-[#2563EB]">
                    <i class="fa fa-question-circle text-sm"></i> @lang('menu.customQuestion')
                </a>
            @endif
            @if ($canAddJobs)
                <a href="{{ route('admin.jobs.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-[#2563EB] px-5 py-2.5 text-[13px] font-semibold text-white shadow-sm transition hover:bg-[#1d4ed8]">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    @lang('app.createNew')
                </a>
            @endif
        </div>
    @endsection
@endif

@section('content')
<div class="mx-auto  pb-8 job-page-listing">

    {{-- KPI cards (compact — shared scale with dashboard .rd-sc) --}}
    <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div class="relative cursor-default overflow-hidden rounded-xl bg-[#0F1F3D] px-3.5 py-3 text-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
            <div class="pointer-events-none absolute -right-3 -top-3 h-12 w-12 rounded-full bg-white opacity-[0.07]"></div>
            <div class="flex items-center justify-between">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-white/10">
                    <i class="icon-badge text-base text-white/80"></i>
                </div>
                <span class="rounded-full bg-white/[0.08] px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide text-white/45">@lang('modules.jobCategoriesPage.statTotal')</span>
            </div>
            <p class="mt-1.5 text-[22px] font-extrabold leading-none tracking-tight" id="statTotal">{{ number_format($totalJobs) }}</p>
            <p class="mt-0.5 text-[10px] font-semibold uppercase tracking-wide text-white/55">@lang('modules.dashboard.totalJobs')</p>
        </div>
        <div class="relative cursor-default overflow-hidden rounded-xl bg-[#059669] px-3.5 py-3 text-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
            <div class="pointer-events-none absolute -right-3 -top-3 h-12 w-12 rounded-full bg-white opacity-[0.07]"></div>
            <div class="flex items-center justify-between">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-white/10">
                    <svg class="h-4 w-4" fill="none" stroke="rgba(255,255,255,0.75)" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <span class="rounded-full bg-white/[0.14] px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide text-white/55">@lang('app.active')</span>
            </div>
            <p class="mt-1.5 text-[22px] font-extrabold leading-none tracking-tight" id="statActive">{{ number_format($activeJobs) }}</p>
            <p class="mt-0.5 text-[10px] font-semibold uppercase tracking-wide text-white/55">@lang('modules.dashboard.activeJobs')</p>
        </div>
        <div class="relative cursor-default overflow-hidden rounded-xl border border-[#F0EEE9] bg-white px-3.5 py-3 text-[#1A1E2E] shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
            <div class="pointer-events-none absolute -right-3 -top-3 h-12 w-12 rounded-full bg-[#EF4444] opacity-[0.07]"></div>
            <div class="flex items-center justify-between">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#FFF1F2]">
                    <svg class="h-4 w-4" fill="none" stroke="#EF4444" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <span class="rounded-full bg-[#FFF1F2] px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide text-[#EF4444]">@lang('app.inactive')</span>
            </div>
            <p class="mt-1.5 text-[22px] font-extrabold leading-none tracking-tight text-[#1A1E2E]" id="statInactive">{{ number_format($inactiveJobs) }}</p>
            <p class="mt-0.5 text-[10px] font-semibold uppercase tracking-wide text-[#8892A0]">@lang('modules.dashboard.inactiveJobs')</p>
        </div>
        <div class="relative cursor-default overflow-hidden rounded-xl bg-[#2563EB] px-3.5 py-3 text-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
            <div class="pointer-events-none absolute -right-3 -top-3 h-12 w-12 rounded-full bg-white opacity-[0.07]"></div>
            <div class="flex items-center justify-between">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-white/10">
                    <svg class="h-4 w-4" fill="none" stroke="rgba(255,255,255,0.75)" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <span class="rounded-full bg-white/[0.14] px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide text-white/55">@lang('modules.dashboard.totalOpenings')</span>
            </div>
            <p class="mt-1.5 text-[22px] font-extrabold leading-none tracking-tight" id="statOpenings">{{ number_format($totalOpenings) }}</p>
            <p class="mt-0.5 text-[10px] font-semibold uppercase tracking-wide text-white/55">@lang('modules.dashboard.totalOpenings')</p>
        </div>
    </div>

    {{-- Table card --}}
    <div class="overflow-hidden rounded-[18px] border border-[#E8E6E1] bg-white">
        <div class="flex flex-wrap items-center gap-2.5 border-b border-[#F0EEE9] px-5 py-3.5">
            <div class="flex items-center gap-2 rounded-[10px] border-[1.5px] border-transparent bg-[#F5F6FA] px-3 py-2 transition focus-within:border-[#2563EB] focus-within:bg-white">
                <svg class="h-3.5 w-3.5 shrink-0 text-[#9CA3AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" id="jobsTableSearch" placeholder="{{ rtrim(__('modules.datatables.search'), ': ') }}…" class="w-[min(100vw-8rem,190px)] border-0 bg-transparent font-[inherit] text-[13px] text-[#1A1E2E] outline-none placeholder:text-[#B0B8C4]" autocomplete="off" />
            </div>
            <select id="jobsFilterCompany" class="rounded-lg border-[1.5px] border-[#E2DED8] bg-white py-2 pl-3 pr-9 text-[12.5px] font-[inherit] text-[#5A6478] outline-none focus:border-[#2563EB]" style="background-image:url('data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2712%27 height=%277%27 fill=%27none%27%3E%3Cpath d=%27M1 1l5 5 5-5%27 stroke=%27%239CA3AF%27 stroke-width=%271.6%27 stroke-linecap=%27round%27 stroke-linejoin=%27round%27/%3E%3C/svg%3E');background-repeat:no-repeat;background-position:right 10px center;appearance:none;cursor:pointer;">
                <option value="">@lang('app.filter') @lang('app.company'): @lang('app.viewAll')</option>
                @foreach ($companies as $item)
                    <option value="{{ $item->id }}">{{ ucwords($item->company_name) }}</option>
                @endforeach
            </select>
            <select id="jobsFilterStatus" class="rounded-lg border-[1.5px] border-[#E2DED8] bg-white py-2 pl-3 pr-9 text-[12.5px] font-[inherit] text-[#5A6478] outline-none focus:border-[#2563EB]" style="background-image:url('data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2712%27 height=%277%27 fill=%27none%27%3E%3Cpath d=%27M1 1l5 5 5-5%27 stroke=%27%239CA3AF%27 stroke-width=%271.6%27 stroke-linecap=%27round%27 stroke-linejoin=%27round%27/%3E%3C/svg%3E');background-repeat:no-repeat;background-position:right 10px center;appearance:none;cursor:pointer;">
                <option value="">@lang('app.filter') @lang('app.status'): @lang('app.viewAll')</option>
                <option value="active">@lang('app.active')</option>
                <option value="inactive">@lang('app.inactive')</option>
                <option value="expired">@lang('app.expired')</option>
            </select>
            <select id="jobsFilterLocation" class="rounded-lg border-[1.5px] border-[#E2DED8] bg-white py-2 pl-3 pr-9 text-[12.5px] font-[inherit] text-[#5A6478] outline-none focus:border-[#2563EB]" style="background-image:url('data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2712%27 height=%277%27 fill=%27none%27%3E%3Cpath d=%27M1 1l5 5 5-5%27 stroke=%27%239CA3AF%27 stroke-width=%271.6%27 stroke-linecap=%27round%27 stroke-linejoin=%27round%27/%3E%3C/svg%3E');background-repeat:no-repeat;background-position:right 10px center;appearance:none;cursor:pointer;">
                <option value="">@lang('app.filter') @lang('menu.locations'): @lang('app.viewAll')</option>
                @foreach ($locations as $location)
                    <option value="{{ $location->id }}">{{ ucwords($location->location) }}</option>
                @endforeach
            </select>
            <div class="ml-auto flex items-center gap-1 text-[12px] text-[#8892A0]">
                @lang('modules.jobCategoriesPage.showing')
                <span class="mx-1 font-semibold text-[#1A1E2E]" id="jobsShowCount">0</span>
                @lang('modules.jobCategoriesPage.of')
                <span class="mx-1 font-semibold text-[#1A1E2E]" id="jobsTotalCount">0</span>
            </div>
        </div>

      

        <div class="overflow-x-auto">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-[#F8F7F4]">
                        <th class="whitespace-nowrap py-2.5 pl-[18px] pr-4 text-left text-[10.5px] font-bold uppercase tracking-wide text-[#8892A0]" style="width:46px;">
                            <label class="jobs-cb-wrap relative flex cursor-pointer items-center">
                                <input type="checkbox" id="jobsMasterCb" />
                                <span class="jobs-cb-box"><svg class="jobs-cb-chk h-2.5 w-2.5" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></span>
                            </label>
                        </th>
                        <th class="w-12 whitespace-nowrap px-4 py-2.5 text-left text-[10.5px] font-bold uppercase tracking-wide text-[#8892A0]">#</th>
                        <th class="whitespace-nowrap px-4 py-2.5 text-left text-[10.5px] font-bold uppercase tracking-wide text-[#8892A0]">@lang('modules.jobs.jobTitle')</th>
                        <th class="whitespace-nowrap px-4 py-2.5 text-left text-[10.5px] font-bold uppercase tracking-wide text-[#8892A0]">@lang('app.company')</th>
                        <th class="whitespace-nowrap px-4 py-2.5 text-left text-[10.5px] font-bold uppercase tracking-wide text-[#8892A0]">@lang('modules.dashboard.totalOpenings')</th>
                        <th class="whitespace-nowrap px-4 py-2.5 text-left text-[10.5px] font-bold uppercase tracking-wide text-[#8892A0]">@lang('menu.locations')</th>
                        <th class="whitespace-nowrap px-4 py-2.5 text-left text-[10.5px] font-bold uppercase tracking-wide text-[#8892A0]">@lang('app.startDate')</th>
                        <th class="whitespace-nowrap px-4 py-2.5 text-left text-[10.5px] font-bold uppercase tracking-wide text-[#8892A0]">@lang('app.endDate')</th>
                        <th class="whitespace-nowrap px-4 py-2.5 text-left text-[10.5px] font-bold uppercase tracking-wide text-[#8892A0]">@lang('app.status')</th>
                        <th class="whitespace-nowrap py-2.5 pl-4 pr-[18px] text-right text-[10.5px] font-bold uppercase tracking-wide text-[#8892A0]">@lang('app.action')</th>
                    </tr>
                </thead>
                <tbody id="jobsTableBody" class="text-[13px] text-[#3D4A5C]"></tbody>
            </table>
            <div id="jobsEmptyState" class="hidden py-12 text-center">
                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-[#F1F3F7]">
                    <svg class="h-6 w-6 text-[#C4CBD4]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </div>
                <p class="text-[14px] font-semibold text-[#5A6478]">@lang('messages.notFound')</p>
                <p class="mt-1 text-[12px] text-[#B0B8C4]">@lang('app.filter') · @lang('app.createNew')</p>
            </div>
        </div>

        <div class="flex flex-col gap-3 border-t border-[#F0EEE9] px-5 py-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-[12px] text-[#8892A0]"></p>
            <div id="jobsBulkBar" class="hidden flex flex-wrap items-center gap-2">
                <span class="text-[12.5px] font-semibold text-[#5A6478]" id="jobsSelCount">0</span>
                @if ($canDeleteJobs)
                    <button type="button" id="jobsBulkDeleteBtn" class="rounded-lg bg-[#FFF1F2] px-3 py-1.5 text-[12px] font-semibold text-[#EF4444] transition hover:bg-[#FFE4E6]">@lang('app.delete')</button>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Delete confirmation --}}
<div id="jobsDelModal" class="fixed inset-0 z-[200] hidden items-center justify-center bg-[rgba(15,31,61,0.5)] p-4 backdrop-blur-[3px]" role="dialog" aria-modal="true" aria-labelledby="jobsDelTitle">
    <div class="max-h-[90vh] w-full max-w-[380px] overflow-hidden rounded-[20px] bg-white shadow-xl" onclick="event.stopPropagation()">
        <div class="flex items-start justify-between border-b border-[#F0EEE9] px-6 py-5">
            <div class="flex items-start gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#FFF1F2]">
                    <svg class="h-[18px] w-[18px]" fill="none" stroke="#EF4444" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </div>
                <div>
                    <h3 id="jobsDelTitle" class="text-[15px] font-bold text-[#1A1E2E]">@lang('app.delete')</h3>
                    <p class="text-[11.5px] text-[#8892A0]">Jobs moved to Trash can be restored.</p>
                </div>
            </div>
            <button type="button" class="rounded-lg p-2 text-[#8892A0] hover:bg-gray-100" id="jobsDelClose" aria-label="@lang('app.close')">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="px-6 py-5">
            <p class="text-[13.5px] leading-relaxed text-[#5A6478]" id="jobsDelMessage"></p>
        </div>
        <div class="flex justify-end gap-2.5 border-t border-[#F0EEE9] px-6 py-3.5">
            <button type="button" id="jobsDelCancel" class="rounded-lg border-0 bg-[#F1F3F7] px-5 py-2.5 text-[13px] font-semibold text-[#5A6478] hover:bg-[#E5E7ED]">@lang('app.cancel')</button>
            <button type="button" id="jobsDelConfirm" class="rounded-lg border-0 bg-[#EF4444] px-5 py-2.5 text-[13px] font-semibold text-white hover:bg-[#DC2626]">Move to Trash</button>
        </div>
    </div>
</div>

@include('admin.jobs.refresh')
@endsection

@php
    $toggleUrlTpl = str_replace('999999999', '__ID__', route('admin.jobs.toggleStatus', ['job' => 999999999]));
@endphp

@push('footer-script')
<script>
(function () {
    const JOBS = @json($jobsPayload);
    const PERM = {
        edit: @json($canEditJobs),
        delete: @json($canDeleteJobs),
        add: @json($canAddJobs),
    };
    const CSRF = @json(csrf_token());
    const ROUTES = {
        toggleTpl: @json($toggleUrlTpl),
        bulkDestroy: @json(route('admin.jobs.bulkDestroy')),
    };
    const I18N = {
        copied: @json(__('messages.copiedToClipboard')),
        deleteN: @json(__('errors.areYouSure')),
        selectedSuffix: @json(__('messages.itemsSelected')),
        active: @json(__('app.active')),
        inactive: @json(__('app.inactive')),
        expired: @json(__('app.expired')),
        edit: @json(__('app.edit')),
        copyUrl: @json(__('app.copyUrl')),
        duplicate: @json(__('app.duplicate')),
        status: @json(__('app.status')),
        refresh: @json(__('app.refresh')),
        delete: @json(__('app.delete')),
    };

    const CAT_PALETTE = [
        { bg: '#EFF6FF', tc: '#1D4ED8' },
        { bg: '#FFF7ED', tc: '#C2410C' },
        { bg: '#ECFDF5', tc: '#065F46' },
        { bg: '#F5F3FF', tc: '#5B21B6' },
        { bg: '#F0FDFA', tc: '#0D9488' },
        { bg: '#F1F3F7', tc: '#5A6478' },
    ];

    function catColors(cat) {
        let h = 0;
        const s = String(cat || '');
        for (let i = 0; i < s.length; i++) h = ((h << 5) - h) + s.charCodeAt(i);
        return CAT_PALETTE[Math.abs(h) % CAT_PALETTE.length];
    }

    function esc(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    let selected = new Set();
    let deleteMode = 'single';
    let deleteTargetIds = [];

    function getFiltered() {
        const q = (document.getElementById('jobsTableSearch')?.value || '').toLowerCase().trim();
        const company = document.getElementById('jobsFilterCompany')?.value || '';
        const st = document.getElementById('jobsFilterStatus')?.value || '';
        const loc = document.getElementById('jobsFilterLocation')?.value || '';

        return JOBS.filter(function (j) {
            const hay = (j.title + ' ' + j.company + ' ' + j.locationsStr + ' ' + j.category + ' ' + j.jobType).toLowerCase();
            if (q && hay.indexOf(q) === -1) return false;
            if (company && String(j.companyId) !== String(company)) return false;
            if (st && j.status !== st) return false;
            if (loc && j.locationIds.indexOf(parseInt(loc, 10)) === -1) return false;
            return true;
        });
    }

    function statusPill(status) {
        if (status === 'active') return '<span class="inline-flex items-center gap-1 rounded-full bg-[#ECFDF5] px-2.5 py-1 text-[11.5px] font-bold text-[#059669]">' + esc(I18N.active) + '</span>';
        if (status === 'inactive') return '<span class="inline-flex items-center gap-1 rounded-full bg-[#F1F3F7] px-2.5 py-1 text-[11.5px] font-bold text-[#8892A0]">' + esc(I18N.inactive) + '</span>';
        if (status === 'expired') return '<span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11.5px] font-bold text-white" style="background:#FF8C00;">' + esc(I18N.expired) + '</span>';
        return '<span class="inline-flex items-center gap-1 rounded-full bg-[#FFF7ED] px-2.5 py-1 text-[11.5px] font-bold text-[#F97316]">' + esc(status) + '</span>';
    }

    function updateKpisFromJobs() {
        document.getElementById('statTotal').textContent = JOBS.length.toLocaleString();
        document.getElementById('statActive').textContent = JOBS.filter(function (j) { return j.status === 'active'; }).length.toLocaleString();
        document.getElementById('statInactive').textContent = JOBS.filter(function (j) { return j.status === 'inactive'; }).length.toLocaleString();
        var openings = 0;
        JOBS.forEach(function (j) { openings += j.openings || 0; });
        document.getElementById('statOpenings').textContent = openings.toLocaleString();
    }

    function renderTable() {
        const data = getFiltered();
        const tbody = document.getElementById('jobsTableBody');
        const empty = document.getElementById('jobsEmptyState');
        document.getElementById('jobsShowCount').textContent = data.length;
        document.getElementById('jobsTotalCount').textContent = JOBS.length;
        updateKpisFromJobs();

        if (!data.length) {
            tbody.innerHTML = '';
            empty.classList.remove('hidden');
            document.getElementById('jobsBulkBar').classList.add('hidden');
            return;
        }
        empty.classList.add('hidden');

        let html = '';
        data.forEach(function (j, i) {
            const c = catColors(j.category);
            const sel = selected.has(j.id);
            const rowSel = sel ? ' bg-[#FAFBFD]' : '';
            html += '<tr class="border-t border-[#F0EEE9] transition-colors hover:bg-[#FAFBFD]' + rowSel + '" data-job-id="' + j.id + '">';
            html += '<td class="py-3 pl-[18px] pr-4 align-middle">';
            html += '<label class="jobs-cb-wrap relative flex cursor-pointer items-center">';
            html += '<input type="checkbox" class="jobs-row-cb" data-id="' + j.id + '"' + (sel ? ' checked' : '') + ' />';
            html += '<span class="jobs-cb-box"><svg class="jobs-cb-chk h-2.5 w-2.5" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></span>';
            html += '</label></td>';
            html += '<td class="px-4 py-3 align-middle text-[12.5px] font-semibold text-[#C4CBD4]">' + String(i + 1).padStart(2, '0') + '</td>';
            html += '<td class="px-4 py-3 align-middle">'
                + '<a href="' + esc(j.editUrl) + '" class="text-[13.5px] font-bold leading-snug text-[#1A1E2E] hover:text-[#2563EB] transition-colors">' + esc(j.title) + '</a>';
            html += '<div class="mt-1 flex flex-wrap items-center gap-2">';
            html += '<span class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[11.5px] font-semibold" style="background:' + c.bg + ';color:' + c.tc + '">' + esc(j.category) + '</span>';
            html += '<span class="text-[11px] font-semibold text-[#B0B8C4]">' + esc(j.jobType) + '</span></div></td>';
            html += '<td class="px-4 py-3 align-middle">';
            html += '<div class="text-[11.5px] font-semibold text-[#3D4A5C]">' + esc(j.company) + '</div>';
            if (j.companyLocation) {
                html += '<div class="mt-0.5 flex items-center gap-1 text-[11px] text-[#B0B8C4]">';
                html += '<svg class="h-3 w-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>';
                html += esc(j.companyLocation);
                html += '</div>';
            }
            html += '</td>';
            html += '<td class="px-4 py-3 align-middle"><span class="inline-flex items-center gap-1 font-bold text-[#1A1E2E]">';
            html += '<svg class="h-3.5 w-3.5 text-[#2563EB]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>';
            html += j.openings + '</span></td>';
            html += '<td class="px-4 py-3 align-middle"><div class="inline-flex items-center gap-1 text-[12px] font-semibold text-[#5A6478]">';
            html += '<svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>';
            html += esc(j.locationsStr || '—') + '</div></td>';
            html += '<td class="px-4 py-3 align-middle text-[12.5px] text-[#8892A0]">' + esc(j.start) + '</td>';
            html += '<td class="px-4 py-3 align-middle text-[12.5px] text-[#8892A0]">' + esc(j.end) + '</td>';
            html += '<td class="px-4 py-3 align-middle">' + statusPill(j.status) + '</td>';
            html += '<td class="py-3 pl-4 pr-[18px] text-right align-middle"><div class="flex items-center justify-end gap-1.5">';

            if (PERM.edit) {
                html += '<a href="' + esc(j.editUrl) + '" class="jobs-action-btn bg-[#EFF6FF] hover:bg-[#DBEAFE]" data-tip="' + esc(I18N.edit) + '"><svg class="h-3.5 w-3.5" fill="none" stroke="#2563EB" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></a>';
            }
            html += '<button type="button" class="jobs-action-btn bg-[#F5F3FF] hover:bg-[#EDE9FE] jobs-copy-url" data-url="' + esc(j.copyUrl) + '" data-tip="' + esc(I18N.copyUrl) + '"><svg class="h-3.5 w-3.5" fill="none" stroke="#7C3AED" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg></button>';
            if (PERM.add) {
                html += '<a href="' + esc(j.duplicateUrl) + '" class="jobs-action-btn bg-[#ECFDF5] hover:bg-[#D1FAE5]" data-tip="' + esc(I18N.duplicate) + '"><svg class="h-3.5 w-3.5" fill="none" stroke="#059669" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg></a>';
            }
            if (PERM.edit && j.status !== 'expired') {
                html += '<button type="button" class="jobs-action-btn bg-[#FFF7ED] hover:bg-[#FEF3C7] jobs-toggle-status" data-id="' + j.id + '" data-tip="' + esc(I18N.status) + '"><svg class="h-3.5 w-3.5" fill="none" stroke="#F97316" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"/></svg></button>';
            }
            if (PERM.edit && j.status === 'expired') {
                html += '<button type="button" class="jobs-action-btn bg-[#FFF7ED] hover:bg-[#FEF3C7] expire_modal" data-row-id="' + j.id + '" data-tip="' + esc(I18N.refresh) + '"><svg class="h-3.5 w-3.5" fill="none" stroke="#F97316" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg></button>';
            }
            if (PERM.delete) {
                html += '<button type="button" class="jobs-action-btn bg-[#FFF1F2] hover:bg-[#FFE4E6] jobs-delete-one" data-id="' + j.id + '" data-title="' + esc(j.title) + '" data-tip="' + esc(I18N.delete) + '"><svg class="h-3.5 w-3.5" fill="none" stroke="#EF4444" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>';
            }
            html += '</div></td></tr>';
        });
        tbody.innerHTML = html;

        const bulk = document.getElementById('jobsBulkBar');
        if (selected.size > 0) {
            bulk.classList.remove('hidden');
            document.getElementById('jobsSelCount').textContent = selected.size + ' ' + I18N.selectedSuffix;
        } else {
            bulk.classList.add('hidden');
        }

        syncMasterCheckbox(data);
    }

    function syncMasterCheckbox(data) {
        const master = document.getElementById('jobsMasterCb');
        if (!master) return;
        const ids = data.map(function (d) { return d.id; });
        const allSel = ids.length && ids.every(function (id) { return selected.has(id); });
        master.checked = allSel;
    }

    document.getElementById('jobsTableSearch')?.addEventListener('input', renderTable);
    document.getElementById('jobsFilterCompany')?.addEventListener('change', renderTable);
    document.getElementById('jobsFilterStatus')?.addEventListener('change', renderTable);
    document.getElementById('jobsFilterLocation')?.addEventListener('change', renderTable);

    document.getElementById('jobsMasterCb')?.addEventListener('change', function () {
        const data = getFiltered();
        if (this.checked) {
            data.forEach(function (j) { selected.add(j.id); });
        } else {
            selected.clear();
        }
        renderTable();
    });

    document.getElementById('jobsTableBody')?.addEventListener('change', function (e) {
        if (!e.target.classList.contains('jobs-row-cb')) return;
        const id = parseInt(e.target.getAttribute('data-id'), 10);
        if (e.target.checked) selected.add(id); else selected.delete(id);
        renderTable();
    });

    document.getElementById('jobsTableBody')?.addEventListener('click', function (e) {
        const copyBtn = e.target.closest('.jobs-copy-url');
        if (copyBtn) {
            const url = copyBtn.getAttribute('data-url');
            if (navigator.clipboard && url) {
                navigator.clipboard.writeText(url).then(function () {
                    if (typeof $.showToastr === 'function') $.showToastr(I18N.copied, 'success');
                });
            } else {
                const $temp = $('<input>');
                $('body').append($temp);
                $temp.val(url).select();
                document.execCommand('copy');
                $temp.remove();
                if (typeof $.showToastr === 'function') $.showToastr(I18N.copied, 'success');
            }
            return;
        }
        const delBtn = e.target.closest('.jobs-delete-one');
        if (delBtn && PERM.delete) {
            deleteMode = 'single';
            deleteTargetIds = [parseInt(delBtn.getAttribute('data-id'), 10)];
            document.getElementById('jobsDelMessage').textContent = 'Move this job to Trash? Applicants and their records will be preserved. You can restore the job from Trash.';
            document.getElementById('jobsDelModal').classList.remove('hidden');
            document.getElementById('jobsDelModal').classList.add('flex');
            return;
        }
        const toggleBtn = e.target.closest('.jobs-toggle-status');
        if (toggleBtn && PERM.edit) {
            const id = parseInt(toggleBtn.getAttribute('data-id'), 10);
            const url = ROUTES.toggleTpl.replace('__ID__', id);
            $.easyAjax({
                url: url,
                type: 'POST',
                data: { _token: CSRF },
                success: function (res) {
                    if (res.status === 'success' && res.new_status) {
                        const job = JOBS.find(function (j) { return j.id === id; });
                        if (job) job.status = res.new_status;
                        if (typeof $.showToastr === 'function') $.showToastr(res.message, 'success');
                        renderTable();
                    }
                }
            });
        }
    });

    function closeDelModal() {
        const m = document.getElementById('jobsDelModal');
        m.classList.add('hidden');
        m.classList.remove('flex');
    }

    document.getElementById('jobsDelClose')?.addEventListener('click', closeDelModal);
    document.getElementById('jobsDelCancel')?.addEventListener('click', closeDelModal);
    document.getElementById('jobsDelModal')?.addEventListener('click', function (e) {
        if (e.target === this) closeDelModal();
    });

    document.getElementById('jobsDelConfirm')?.addEventListener('click', function () {
        if (deleteMode === 'bulk') {
            $.easyAjax({
                url: ROUTES.bulkDestroy,
                type: 'POST',
                data: { _token: CSRF, ids: deleteTargetIds },
                success: function (res) {
                    if (res.status === 'success') {
                        if (typeof $.showToastr === 'function') $.showToastr(res.message, 'success');
                        closeDelModal();
                        window.location.reload();
                    }
                }
            });
            return;
        }
        const id = deleteTargetIds[0];
        const job = JOBS.find(function (j) { return j.id === id; });
        if (!job) { closeDelModal(); return; }
        $.easyAjax({
            url: job.destroyUrl,
            type: 'POST',
            data: { _token: CSRF, _method: 'DELETE' },
            success: function (res) {
                if (res.status === 'success') {
                    if (typeof $.showToastr === 'function') $.showToastr(res.message, 'success');
                    closeDelModal();
                    window.location.reload();
                }
            }
        });
    });

    document.getElementById('jobsBulkDeleteBtn')?.addEventListener('click', function () {
        if (!PERM.delete || selected.size === 0) return;
        deleteMode = 'bulk';
        deleteTargetIds = Array.from(selected);
        document.getElementById('jobsDelMessage').textContent = 'Move ' + deleteTargetIds.length + ' jobs to Trash? Applicants will be preserved, and the jobs can be restored.';
        document.getElementById('jobsDelModal').classList.remove('hidden');
        document.getElementById('jobsDelModal').classList.add('flex');
    });

    $(document).on('click', '.expire_modal', function () {
        var id = $(this).data('row-id');
        var url = "{{ route('admin.jobs.show', ':id') }}".replace(':id', id);
        $.easyAjax({
            type: 'POST',
            url: url,
            data: { _token: CSRF, _method: 'GET' },
            success: function (response) {
                var sd = response.start_date ? String(response.start_date).split('T')[0] : '';
                var ed = response.end_date ? String(response.end_date).split('T')[0] : '';
                $('#date-start').val(sd);
                $('#date-end').val(ed);
                $('#hidden_id').val(response.id);
            }
        });
        $('#myModal').removeClass('hidden');
    });

    (function () {
        var p = new URLSearchParams(window.location.search);
        var cid = p.get('company');
        var sel = document.getElementById('jobsFilterCompany');
        if (cid && sel) {
            sel.value = cid;
        }
    })();

    renderTable();
})();
</script>
@endpush
