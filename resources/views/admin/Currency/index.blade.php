@extends('layouts.app')

@push('head-script')
    @include('admin.partials.settings-design-styles')
    <style>
        @keyframes cur-fu { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
        .cur-a1 { opacity: 0; animation: cur-fu 0.4s ease 0.04s forwards; }
        .cur-a2 { opacity: 0; animation: cur-fu 0.4s ease 0.09s forwards; }
        .cur-a3 { opacity: 0; animation: cur-fu 0.4s ease 0.14s forwards; }
        .cur-def-thumb { transition: left 0.2s; }
        .cur-cb:checked + .cur-cb-box { background: #2563eb; border-color: #2563eb; }
        .cur-cb:checked + .cur-cb-box svg { opacity: 1; }
    </style>
@endpush

@php
    $canManage = in_array('manage_settings', $userPermissions);
    $currencyTotal = $currencies->count();
    $defaultId = (int) ($global->currency_id ?? 0);
    $defaultCurrency = $defaultId ? $currencies->firstWhere('id', $defaultId) : null;
    $defaultCode = $defaultCurrency ? strtoupper($defaultCurrency->currency_code) : '—';
    $curSymStyles = [
        'bg-blue-50 text-blue-600',
        'bg-purple-50 text-purple-600',
        'bg-emerald-50 text-emerald-600',
        'bg-orange-50 text-orange-600',
        'bg-rose-50 text-rose-600',
        'bg-teal-50 text-teal-600',
    ];
    $curOpenEdit = null;
    if (request('open') === 'edit' && request()->filled('id')) {
        $curOpenEdit = $currencies->firstWhere('id', (int) request('id'));
    }
    $footerYear = date('Y');
@endphp

@section('page-title-html')
    <span class="text-[22px] font-bold tracking-[-0.025em] text-[#1A1E2E]">{{ __('modules.currencySettingsPage.titleMain') }}
        <span class="jc-cat-serif">{{ __('modules.currencySettingsPage.titleAccent') }}</span>
    </span>
@endsection

@section('page-subtitle')
    {{ __('modules.currencySettingsPage.subtitle') }}
@endsection

@if($canManage)
@section('create-button')
    <div class="flex items-center gap-2">
        <button type="button" id="cur-ai-generate-btn"
                class="inline-flex items-center gap-2 rounded-xl border border-[#2563EB]/30 bg-[#EFF6FF] px-4 py-2.5 text-[13px] font-semibold text-[#2563EB] transition hover:bg-[#DBEAFE]">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
            @lang('app.aiGenerate')
        </button>
        <button type="button" onclick="window.curModalOpenCreate()"
                class="inline-flex items-center gap-2 rounded-xl bg-[#2563EB] px-5 py-2.5 text-[13px] font-semibold text-white shadow-sm transition hover:bg-[#1d4ed8] hover:shadow-lg hover:shadow-blue-500/25 hover:-translate-y-0.5 active:translate-y-0 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
            </svg>
            @lang('app.createNew')
        </button>
    </div>
@endsection
@endif

@section('content')
<div class="mx-auto flex  flex-col gap-6 pb-10">
    {{-- Stats --}}
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 cur-a1">
        <div class="flex items-center gap-2.5 rounded-xl border border-[#E8E6E1] bg-white px-3 py-2.5">
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#EFF6FF]">
                <svg class="h-4 w-4" fill="none" stroke="#2563EB" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-[10px] font-bold uppercase tracking-widest text-[#8892A0]">{{ __('modules.currencySettingsPage.statTotal') }}</p>
                <p class="text-[18px] font-extrabold tracking-tight text-[#1A1E2E]" id="cur-stat-total">{{ $currencyTotal }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2.5 rounded-xl border border-[#E8E6E1] bg-white px-3 py-2.5">
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#ECFDF5]">
                <svg class="h-4 w-4" fill="none" stroke="#059669" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-[10px] font-bold uppercase tracking-widest text-[#8892A0]">{{ __('modules.currencySettingsPage.statDefault') }}</p>
                <p class="text-[18px] font-extrabold tracking-tight text-[#1A1E2E]" id="cur-stat-default">{{ $defaultCode }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2.5 rounded-xl border border-[#E8E6E1] bg-white px-3 py-2.5">
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#F5F3FF]">
                <svg class="h-4 w-4" fill="none" stroke="#7C3AED" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064"/>
                </svg>
            </div>
            <div>
                <p class="text-[10px] font-bold uppercase tracking-widest text-[#8892A0]">{{ __('modules.currencySettingsPage.statRegions') }}</p>
                <p class="text-[18px] font-extrabold tracking-tight text-[#1A1E2E]" id="cur-stat-regions">{{ $currencyTotal }}</p>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="jc-table-card cur-a2">
        <div class="jc-table-toolbar">
            <div class="jc-t-search">
                <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="#9CA3AF" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="search" id="cur-table-search" placeholder="{{ __('modules.currencySettingsPage.searchPlaceholder') }}" autocomplete="off">
            </div>
            <p class="text-[12px] text-[#8892A0]">
                {{ __('modules.currencySettingsPage.showing') }}
                <span class="font-semibold text-[#1A1E2E]" id="cur-show-count">{{ $currencyTotal }}</span>
                {{ __('modules.currencySettingsPage.of') }}
                <span class="font-semibold text-[#1A1E2E]" id="cur-total-num">{{ $currencyTotal }}</span>
                {{ __('modules.currencySettingsPage.currencies') }}
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="jc-cat-table">
                <thead>
                <tr>
                    <th class="w-14">#</th>
                    <th>@lang('modules.currency.currencyName')</th>
                    <th>@lang('modules.currency.currencySymbol')</th>
                    <th>@lang('modules.currency.currencyCode')</th>
                    <th>{{ __('modules.currencySettingsPage.columnDefault') }}</th>
                    <th class="jc-th-right" style="padding-right:20px;">@lang('app.action')</th>
                </tr>
                </thead>
                <tbody id="cur-tbody">
                @forelse ($currencies as $item)
                    @php
                        $isDefault = $defaultId === (int) $item->id;
                        $symClass = $curSymStyles[$item->id % count($curSymStyles)];
                        $searchBlob = strtolower($item->currency_name.' '.$item->currency_code.' '.$item->currency_symbol);
                    @endphp
                    <tr class="cur-row transition-colors {{ $isDefault ? 'bg-blue-50/40' : '' }}"
                        data-id="{{ $item->id }}"
                        data-default="{{ $isDefault ? '1' : '0' }}"
                        data-search="{{ e($searchBlob) }}">
                        <td class="px-5 py-3.5">
                            @if($canManage)
                                <label class="flex cursor-pointer">
                                    <input type="checkbox" class="cur-cb sr-only" value="{{ $item->id }}" @if($isDefault) disabled @endif>
                                    <span class="cur-cb-box flex h-4 w-4 shrink-0 items-center justify-center rounded border-2 border-gray-300 transition-all peer-disabled:cursor-not-allowed peer-disabled:opacity-40">
                                        <svg class="h-2.5 w-2.5 text-white opacity-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    </span>
                                </label>
                            @else
                                <span class="cur-td-num text-[12.5px] font-medium text-[#8892A0]">{{ $loop->iteration }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3.5">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-[18px] font-bold {{ $symClass }}">{{ $item->currency_symbol }}</div>
                                <div>
                                    <p class="text-[13.5px] font-semibold text-[#1A1E2E]">{{ $item->currency_name }}</p>
                                    @if($isDefault)
                                        <span class="mt-0.5 inline-block rounded-full bg-[#2563EB]/10 px-2 py-0.5 text-[10px] font-bold text-[#2563EB]">{{ __('modules.currencySettingsPage.defaultBadge') }}</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3.5">
                            <span class="text-[20px] font-bold text-slate-600">{{ $item->currency_symbol }}</span>
                        </td>
                        <td class="px-4 py-3.5">
                            <span class="rounded-lg bg-gray-100 px-2.5 py-1 text-[13px] font-bold tracking-widest text-slate-600">{{ strtoupper($item->currency_code) }}</span>
                        </td>
                        <td class="px-4 py-3.5">
                            @if($canManage)
                                @if($isDefault)
                                    <span class="inline-flex cursor-default items-center rounded-lg bg-[#2563EB] px-3 py-1.5 text-[11.5px] font-semibold text-white">{{ __('modules.currencySettingsPage.defaultApplied') }}</span>
                                @else
                                    <button type="button" class="cur-set-default rounded-lg bg-gray-100 px-3 py-1.5 text-[11.5px] font-semibold text-[#8892A0] transition-all hover:bg-[#EFF6FF] hover:text-[#2563EB]"
                                            data-id="{{ $item->id }}">{{ __('modules.currencySettingsPage.setDefault') }}</button>
                                @endif
                            @else
                                <span class="text-[12.5px] text-[#8892A0]">—</span>
                            @endif
                        </td>
                        <td class="jc-td-right px-5 py-3.5" style="padding-right:20px;">
                            @if($canManage)
                                <div class="flex items-center justify-end gap-1.5">
                                    <button type="button" class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#EFF6FF] transition-colors hover:bg-[#DBEAFE] cur-open-edit"
                                            title="@lang('app.edit')"
                                            data-id="{{ $item->id }}"
                                            data-name="{{ e($item->currency_name) }}"
                                            data-symbol="{{ e($item->currency_symbol) }}"
                                            data-code="{{ e(strtoupper($item->currency_code)) }}"
                                            data-default="{{ $isDefault ? '1' : '0' }}">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="#2563EB" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    @if(! $isDefault)
                                        <button type="button" class="flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 transition-colors hover:bg-red-100 cur-open-del"
                                                title="@lang('app.delete')"
                                                data-row-id="{{ $item->id }}"
                                                data-name="{{ e($item->currency_name) }}">
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="#EF4444" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    @else
                                        <div class="h-8 w-8"></div>
                                    @endif
                                </div>
                            @else
                                <span class="text-[12.5px] text-[#8892A0]">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr id="cur-row-initial-empty">
                        <td colspan="6">
                            <div class="flex flex-col items-center py-12 text-center">
                                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-[#F1F3F7]">
                                    <svg class="h-6 w-6 text-[#C4CBD4]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <p class="text-[14px] font-semibold text-[#5A6478]">@lang('modules.datatables.emptyTable')</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
                <tr id="cur-filter-empty" class="hidden">
                    <td colspan="6">
                        <div class="flex flex-col items-center py-12 text-center">
                            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-[#F1F3F7]">
                                <svg class="h-6 w-6 text-[#C4CBD4]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <p class="text-[14px] font-semibold text-[#5A6478]">{{ __('modules.currencySettingsPage.emptyFilter') }}</p>
                            <p class="mt-1 text-[12.5px] text-[#B0B8C4]">{{ __('modules.currencySettingsPage.emptyFilterHint') }}</p>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-[#F0EEE9] px-5 py-3.5">
            <p class="text-[12px] text-[#8892A0]">{{ __('modules.currencySettingsPage.footerCredit', ['year' => $footerYear]) }}</p>
            @if($canManage)
                <div id="cur-bulk-bar" class="hidden items-center gap-2">
                    <span class="text-[12.5px] font-semibold text-[#8892A0]" id="cur-sel-count">0 {{ __('modules.currencySettingsPage.bulkSelected') }}</span>
                    <button type="button" id="cur-bulk-delete" class="rounded-lg bg-red-50 px-3 py-1.5 text-[12px] font-semibold text-red-500 transition-colors hover:bg-red-100">{{ __('modules.currencySettingsPage.bulkDelete') }}</button>
                </div>
            @endif
        </div>
    </div>
</div>

@if($canManage)
{{-- Add / Edit modal --}}
<div id="cur-modal-form" class="jc-modal-bg" role="dialog" aria-modal="true" aria-labelledby="cur-modal-title" onclick="window.curBackdropCloseForm(event)">
    <div class="jc-modal" style="width:460px;max-width:94vw;" onclick="event.stopPropagation()">
        <div class="jc-modal-hd">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#EFF6FF]">
                    <svg class="h-[18px] w-[18px]" fill="none" stroke="#2563EB" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 id="cur-modal-title" class="text-[15.5px] font-bold text-[#1A1E2E]">{{ __('modules.currencySettingsPage.modalAddTitle') }}</h3>
                    <p id="cur-modal-sub" class="mt-0.5 text-[11.5px] text-[#8892A0]">{{ __('modules.currencySettingsPage.modalAddSub') }}</p>
                </div>
            </div>
            <button type="button" onclick="window.curModalCloseForm()" class="flex h-8 w-8 items-center justify-center rounded-lg text-[#8892A0] transition hover:bg-gray-100" aria-label="@lang('app.close')">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form class="ajax-form" method="POST" id="cur-currency-form" onsubmit="return false;">
            <div id="alert"></div>
            @csrf
            <input type="hidden" name="_method" id="cur-form-method" value="POST">
            <input type="hidden" name="set_as_default" id="cur-set-default-input" value="0">
            <div class="px-6 py-5">
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="jc-field-label" for="cur-in-name">@lang('modules.currency.currencyName') <span class="text-red-400">*</span></label>
                        <div class="relative">
                            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <input type="text" name="currency_name" id="cur-in-name" autocomplete="off"
                                   class="jc-field-input pl-9" placeholder="e.g. Indian Rupee">
                        </div>
                    </div>
                    <div>
                        <label class="jc-field-label" for="cur-in-symbol">@lang('modules.currency.currencySymbol') <span class="text-red-400">*</span></label>
                        <input type="text" name="currency_symbol" id="cur-in-symbol" maxlength="8"
                               class="jc-field-input text-center text-lg font-bold" placeholder="e.g. ₹">
                    </div>
                    <div>
                        <label class="jc-field-label" for="cur-in-code">@lang('modules.currency.currencyCode') <span class="text-red-400">*</span></label>
                        <input type="text" name="currency_code" id="cur-in-code" maxlength="10"
                               class="jc-field-input text-center text-[13.5px] font-bold tracking-widest uppercase" placeholder="e.g. INR">
                    </div>
                </div>

                <div class="mt-4 flex items-center justify-between rounded-xl bg-[#F5F6FA] px-4 py-3">
                    <div>
                        <p class="text-[13px] font-semibold text-[#1A1E2E]">{{ __('modules.currencySettingsPage.setAsDefaultLabel') }}</p>
                        <p class="mt-0.5 text-[11.5px] text-[#8892A0]">{{ __('modules.currencySettingsPage.setAsDefaultHint') }}</p>
                    </div>
                    <button type="button" id="cur-def-toggle" class="relative h-[23px] w-[42px] shrink-0 cursor-pointer rounded-full border-none bg-[#D1D5DB] transition-colors duration-200" style="padding:0;" aria-pressed="false">
                        <span id="cur-def-thumb" class="cur-def-thumb absolute left-[3px] top-[3px] h-[17px] w-[17px] rounded-full bg-white shadow-sm"></span>
                    </button>
                </div>

                <div class="mt-4">
                    <p class="mb-2 text-[11px] font-bold uppercase tracking-widest text-[#8892A0]">{{ __('modules.currencySettingsPage.quickSelect') }}</p>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" class="cur-quick inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-[#F5F6FA] px-3 py-1.5 text-[12px] font-semibold text-slate-600 transition-all hover:border-[#2563EB] hover:bg-[#EFF6FF] hover:text-[#2563EB]" data-name="US Dollar" data-symbol="$" data-code="USD"><span class="text-base">$</span> USD</button>
                        <button type="button" class="cur-quick inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-[#F5F6FA] px-3 py-1.5 text-[12px] font-semibold text-slate-600 transition-all hover:border-[#2563EB] hover:bg-[#EFF6FF] hover:text-[#2563EB]" data-name="British Pound" data-symbol="£" data-code="GBP"><span class="text-base">£</span> GBP</button>
                        <button type="button" class="cur-quick inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-[#F5F6FA] px-3 py-1.5 text-[12px] font-semibold text-slate-600 transition-all hover:border-[#2563EB] hover:bg-[#EFF6FF] hover:text-[#2563EB]" data-name="Euro" data-symbol="€" data-code="EUR"><span class="text-base">€</span> EUR</button>
                        <button type="button" class="cur-quick inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-[#F5F6FA] px-3 py-1.5 text-[12px] font-semibold text-slate-600 transition-all hover:border-[#2563EB] hover:bg-[#EFF6FF] hover:text-[#2563EB]" data-name="Indian Rupee" data-symbol="₹" data-code="INR"><span class="text-base">₹</span> INR</button>
                        <button type="button" class="cur-quick inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-[#F5F6FA] px-3 py-1.5 text-[12px] font-semibold text-slate-600 transition-all hover:border-[#2563EB] hover:bg-[#EFF6FF] hover:text-[#2563EB]" data-name="UAE Dirham" data-symbol="د.إ" data-code="AED"><span class="text-base">د.إ</span> AED</button>
                        <button type="button" class="cur-quick inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-[#F5F6FA] px-3 py-1.5 text-[12px] font-semibold text-slate-600 transition-all hover:border-[#2563EB] hover:bg-[#EFF6FF] hover:text-[#2563EB]" data-name="Japanese Yen" data-symbol="¥" data-code="JPY"><span class="text-base">¥</span> JPY</button>
                    </div>
                </div>
            </div>
            <div class="jc-modal-ft">
                <button type="button" class="jc-btn-cancel" onclick="window.curModalCloseForm()">@lang('app.cancel')</button>
                <button type="button" id="cur-save-btn" class="jc-btn-save">
                    <span id="cur-save-label">{{ __('modules.currencySettingsPage.saveCurrency') }}</span>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Delete modal --}}
<div id="cur-modal-delete" class="jc-modal-bg" role="dialog" aria-modal="true" onclick="window.curBackdropCloseDel(event)">
    <div class="jc-modal" style="width:380px;max-width:92vw;" onclick="event.stopPropagation()">
        <div class="jc-modal-hd">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#FFF1F2]">
                    <svg class="h-[18px] w-[18px]" fill="none" stroke="#EF4444" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-[15px] font-bold text-[#1A1E2E]">{{ __('modules.currencySettingsPage.deleteTitle') }}</h3>
                    <p class="text-[11.5px] text-[#8892A0]">{{ __('modules.currencySettingsPage.deleteCannotUndo') }}</p>
                </div>
            </div>
            <button type="button" onclick="window.curModalCloseDel()" class="flex h-8 w-8 items-center justify-center rounded-lg text-[#8892A0] hover:bg-gray-100" aria-label="@lang('app.close')">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="px-6 py-5">
            <p class="text-[13.5px] leading-relaxed text-[#5A6478]" id="cur-del-body"></p>
        </div>
        <div class="jc-modal-ft">
            <button type="button" class="jc-btn-cancel" onclick="window.curModalCloseDel()">@lang('app.cancel')</button>
            <button type="button" id="cur-del-confirm" class="jc-btn-danger">@lang('app.delete')</button>
        </div>
    </div>
</div>

{{-- AI Generate by Country modal --}}
<div id="cur-modal-ai-region" class="jc-modal-bg" role="dialog" aria-modal="true" aria-labelledby="cur-modal-ai-region-title" onclick="window.curBackdropCloseAiRegion(event)">
    <div class="jc-modal" style="width:540px;max-width:94vw;" onclick="event.stopPropagation()">
        <div class="jc-modal-hd">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#EFF6FF]">
                    <svg class="h-[18px] w-[18px]" fill="none" stroke="#2563EB" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 7h8m0 0v8m0-8l-8 8-4-4-2 2" />
                    </svg>
                </div>
                <div>
                    <h3 id="cur-modal-ai-region-title" class="text-[15.5px] font-bold text-[#1A1E2E]">Generate Currencies with AI</h3>
                    <p id="cur-modal-ai-region-sub" class="mt-0.5 text-[11.5px] text-[#8892A0]">Select one or more countries and generate real currencies used there.</p>
                </div>
            </div>
            <button type="button" onclick="window.curModalCloseAiRegion()" class="flex h-8 w-8 items-center justify-center rounded-lg text-[#8892A0] transition hover:bg-gray-100" aria-label="@lang('app.close')">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="px-6 py-4">
            <div class="mb-4">
                <label class="jc-field-label" for="cur-ai-country-select">Countries <span class="text-red-400">*</span></label>
                <div class="mt-1 flex items-start gap-2">
                    <select id="cur-ai-country-select" class="jc-field-input flex-1" multiple size="8">
                        <option disabled selected>Select country</option>
                        @foreach(($countryListForAiGenerator ?? []) as $countryName)
                            <option value="{{ $countryName }}">{{ $countryName }}</option>
                        @endforeach
                    </select>
                    <button type="button" id="cur-ai-country-clear" class="rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-[11px] font-semibold text-slate-700 transition hover:border-[#2563EB] hover:text-[#2563EB]">Clear</button>
                </div>
                <p class="mt-1 text-[11.5px] text-[#8892A0]">Hold Cmd/Ctrl to select multiple countries.</p>
            </div>
        </div>

        <div class="jc-modal-ft">
            <button type="button" class="jc-btn-cancel" onclick="window.curModalCloseAiRegion()">@lang('app.cancel')</button>
            <button type="button" id="cur-ai-region-confirm" class="jc-btn-save">Generate</button>
        </div>
    </div>
</div>

@endif
@endsection

@push('footer-script')
@if($canManage)
<script>
    (function () {
        var curStoreUrl = @json(route('admin.currency-settings.store'));
        var curAiGenerateUrl = @json(route('admin.currency-settings.ai-generate'));
        var curUpdateTpl = @json(route('admin.currency-settings.update', ['currency_setting' => '__ID__']));
        var curSetDefaultUrl = @json(route('admin.currency-settings.set-default'));
        var curBulkDestroyUrl = @json(route('admin.currency-settings.bulk-destroy'));
        var curDelTpl = @json(route('admin.currency-settings.destroy', ['currency_setting' => '__ID__']));
        var curDelHtml = {!! json_encode(__('modules.currencySettingsPage.deleteConfirmHtml')) !!};
        var curEditSub = @json(__('modules.currencySettingsPage.modalEditSubPrefix'));
        var token = @json(csrf_token());

        window.curBackdropCloseForm = function (e) {
            if (e.target.id === 'cur-modal-form') window.curModalCloseForm();
        };
        window.curBackdropCloseDel = function (e) {
            if (e.target.id === 'cur-modal-delete') window.curModalCloseDel();
        };

        function curResetDefaultToggle(on) {
            var btn = document.getElementById('cur-def-toggle');
            var thumb = document.getElementById('cur-def-thumb');
            var inp = document.getElementById('cur-set-default-input');
            if (!btn || !thumb || !inp) return;
            inp.value = on ? '1' : '0';
            btn.style.backgroundColor = on ? '#2563EB' : '#D1D5DB';
            btn.setAttribute('aria-pressed', on ? 'true' : 'false');
            thumb.style.left = on ? '22px' : '3px';
        }

        window.curModalOpenCreate = function () {
            $('#cur-currency-form').find('.has-error').removeClass('has-error');
            $('#cur-currency-form').find('.help-block').remove();
            $('#cur-currency-form').find('input, select, textarea').removeClass('border-red-500');
            $('#cur-currency-form').find('#alert').html('');
            $('#cur-form-method').val('POST');
            $('#cur-currency-form').removeData('cur-update-url');
            $('#cur-modal-title').text(@json(__('modules.currencySettingsPage.modalAddTitle')));
            $('#cur-modal-sub').text(@json(__('modules.currencySettingsPage.modalAddSub')));
            $('#cur-save-label').text(@json(__('modules.currencySettingsPage.saveCurrency')));
            $('#cur-in-name').val('');
            $('#cur-in-symbol').val('');
            $('#cur-in-code').val('');
            curResetDefaultToggle(false);
            $('#cur-modal-form').addClass('is-open');
            $('body').addClass('overflow-hidden');
            setTimeout(function () { $('#cur-in-name').focus(); }, 80);
        };

        window.curModalOpenEdit = function (id, name, symbol, code, isDefault) {
            $('#cur-currency-form').find('.has-error').removeClass('has-error');
            $('#cur-currency-form').find('.help-block').remove();
            $('#cur-currency-form').find('input, select, textarea').removeClass('border-red-500');
            $('#cur-currency-form').find('#alert').html('');
            var url = curUpdateTpl.replace('__ID__', id);
            $('#cur-form-method').val('PUT');
            $('#cur-currency-form').data('cur-update-url', url);
            $('#cur-modal-title').text(@json(__('modules.currencySettingsPage.modalEditTitle')));
            $('#cur-modal-sub').text(curEditSub + ' "' + name + '"');
            $('#cur-save-label').text(@json(__('modules.currencySettingsPage.updateCurrency')));
            $('#cur-in-name').val(name);
            $('#cur-in-symbol').val(symbol);
            $('#cur-in-code').val(code);
            curResetDefaultToggle(!!isDefault);
            $('#cur-modal-form').addClass('is-open');
            $('body').addClass('overflow-hidden');
            $('#cur-in-name').focus();
        };

        window.curModalCloseForm = function () {
            $('#cur-modal-form').removeClass('is-open');
            $('body').removeClass('overflow-hidden');
        };

        window.curModalCloseDel = function () {
            $('#cur-modal-delete').removeClass('is-open');
            $('body').removeClass('overflow-hidden');
        };

        window.curBackdropCloseAiRegion = function (e) {
            if (e.target.id === 'cur-modal-ai-region') window.curModalCloseAiRegion();
        };

        window.curModalOpenAiRegion = function () {
            $('#cur-ai-country-select').val([]);
            $('#cur-modal-ai-region').addClass('is-open');
            $('body').addClass('overflow-hidden');
            setTimeout(function () { $('#cur-ai-country-select').focus(); }, 80);
        };

        window.curModalCloseAiRegion = function () {
            $('#cur-modal-ai-region').removeClass('is-open');
            $('body').removeClass('overflow-hidden');
        };

        $('#cur-def-toggle').on('click', function () {
            var on = $('#cur-set-default-input').val() !== '1';
            curResetDefaultToggle(on);
        });

        $(document).on('click', '.cur-quick', function (e) {
            e.preventDefault();
            var $btn = $(this);
            // Use .attr('data-*'): jQuery .data('name') reads the native button.name property, not data-name.
            $('#cur-in-name').val($btn.attr('data-name') || '');
            $('#cur-in-symbol').val($btn.attr('data-symbol') || '');
            $('#cur-in-code').val($btn.attr('data-code') || '');
            $('#cur-in-code').trigger('input');
        });

        $('#cur-in-code').on('input', function () {
            this.value = this.value.toUpperCase();
        });

        $('#cur-save-btn').on('click', function () {
            var url = $('#cur-currency-form').data('cur-update-url');
            var isEdit = $('#cur-form-method').val() === 'PUT';
            $.easyAjax({
                url: isEdit ? url : curStoreUrl,
                container: '#cur-currency-form',
                type: 'POST',
                redirect: true,
                data: $('#cur-currency-form').serialize()
            });
        });

        $('#cur-ai-generate-btn').on('click', function () {
            if ($('#cur-modal-form').hasClass('is-open')) window.curModalCloseForm();
            if ($('#cur-modal-delete').hasClass('is-open')) window.curModalCloseDel();
            window.curModalOpenAiRegion();
        });

        $('#cur-ai-region-confirm').on('click', function () {
            // Read directly from selected options to avoid plugin/.val() inconsistencies.
            var countries = $('#cur-ai-country-select option:selected').map(function () {
                return ($(this).val() || '').toString().trim();
            }).get().filter(function (c) { return c !== ''; });

            if (!countries.length) {
                if (typeof $.toast !== 'undefined') {
                    $.toast({ text: 'Please select at least one country.', position: 'top-right', loaderBg: '#EF4444', icon: 'error', hideAfter: 3500 });
                }
                return;
            }

            var payload = { _token: token };
            countries.forEach(function (country, idx) {
                payload['country_names[' + idx + ']'] = country;
            });

            $.easyAjax({
                url: curAiGenerateUrl,
                type: 'POST',
                redirect: false,
                data: payload,
                success: function (response) {
                    if (response.status === 'success') {
                        window.location.reload();
                    }
                }
            });
        });

        $('#cur-ai-country-clear').on('click', function () {
            $('#cur-ai-country-select option').prop('selected', false);
            $('#cur-ai-country-select').trigger('change');
        });

        $(document).on('click', '.cur-open-edit', function () {
            var $t = $(this);
            window.curModalOpenEdit(
                $t.data('id'),
                $t.data('name'),
                $t.data('symbol'),
                $t.data('code'),
                $t.data('default') == 1
            );
        });

        $(document).on('click', '.cur-set-default', function () {
            var id = $(this).data('id');
            $.easyAjax({
                type: 'POST',
                url: curSetDefaultUrl,
                data: { _token: token, currency_id: id },
                success: function () {
                    window.location.reload();
                }
            });
        });

        $(document).on('click', '.cur-open-del', function () {
            var id = $(this).data('row-id');
            var name = $(this).data('name');
            var safe = $('<div>').text(name).html();
            var html = curDelHtml.split(':name').join(safe);
            $('#cur-del-body').html(html);
            $('#cur-modal-delete').data('del-id', id).addClass('is-open');
            $('body').addClass('overflow-hidden');
        });

        $('#cur-del-confirm').on('click', function () {
            var id = $('#cur-modal-delete').data('del-id');
            if (!id) return;
            var url = curDelTpl.replace('__ID__', id);
            $.easyAjax({
                type: 'POST',
                url: url,
                data: { _token: token, _method: 'DELETE' },
                success: function (response) {
                    if (response.status == 'success') {
                        $.unblockUI();
                        window.curModalCloseDel();
                        window.location.reload();
                    }
                }
            });
        });

        function curSelectedIds() {
            var ids = [];
            $('.cur-cb:checked').each(function () {
                ids.push($(this).val());
            });
            return ids;
        }

        function curUpdateBulkBar() {
            var n = curSelectedIds().length;
            var $bar = $('#cur-bulk-bar');
            if (n > 0) {
                $bar.removeClass('hidden').css('display', 'flex');
                $('#cur-sel-count').text(n + ' ' + @json(__('modules.currencySettingsPage.bulkSelected')));
            } else {
                $bar.addClass('hidden');
            }
        }

        $(document).on('change', '.cur-cb', function () {
            var $tr = $(this).closest('tr');
            if ($(this).is(':checked')) {
                $tr.addClass('bg-blue-50/50');
            } else {
                $tr.removeClass('bg-blue-50/50');
            }
            curUpdateBulkBar();
        });

        $('#cur-bulk-delete').on('click', function () {
            var ids = curSelectedIds();
            if (!ids.length) return;
            $.easyAjax({
                type: 'POST',
                url: curBulkDestroyUrl,
                data: { _token: token, ids: ids },
                success: function () {
                    window.location.reload();
                }
            });
        });

        function curApplyFilter() {
            var q = ($('#cur-table-search').val() || '').toLowerCase().trim();
            var visible = 0;
            var totalRows = $('#cur-tbody tr.cur-row').length;
            $('#cur-tbody tr.cur-row').each(function () {
                var blob = ($(this).data('search') || '').toString().toLowerCase();
                var match = !q || blob.indexOf(q) !== -1;
                $(this).toggle(match);
                if (match) visible++;
            });
            $('#cur-show-count').text(visible);
            $('#cur-total-num').text(totalRows);
            var hasRows = totalRows > 0;
            $('#cur-filter-empty').toggleClass('hidden', !(hasRows && visible === 0));
        }

        $('#cur-table-search').on('input', curApplyFilter);

        @if(request('open') === 'create')
        $(function () { window.curModalOpenCreate(); });
        @endif

        @if($curOpenEdit)
        $(function () {
            window.curModalOpenEdit(
                {{ (int) $curOpenEdit->id }},
                @json($curOpenEdit->currency_name),
                @json($curOpenEdit->currency_symbol),
                @json(strtoupper($curOpenEdit->currency_code)),
                {{ $defaultId === (int) $curOpenEdit->id ? 'true' : 'false' }}
            );
        });
        @endif

        $(document).on('keydown', function (e) {
            if (e.key === 'Escape') {
                if ($('#cur-modal-form').hasClass('is-open')) window.curModalCloseForm();
                if ($('#cur-modal-delete').hasClass('is-open')) window.curModalCloseDel();
                if ($('#cur-modal-ai-region').hasClass('is-open')) window.curModalCloseAiRegion();
            }
        });
    })();
</script>
@endif
@endpush
