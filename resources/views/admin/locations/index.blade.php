@extends('layouts.app')

@push('head-script')
    @if(in_array('add_locations', $userPermissions))
        <link href="{{ asset('assets/node_modules_files/select2/dist/css/select2.min.css') }}" rel="stylesheet">
    @endif
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
    <style>
        @keyframes loc-map-pulse {
            0%, 100% { opacity: 0.6; transform: scale(1); }
            50% { opacity: 0; transform: scale(2); }
        }
        .loc-map-dot-ring { animation: loc-map-pulse 2s infinite; }
    </style>
@endpush

@php
    $locTotal = $locations->count();
    $locIconBgs = ['#EFF6FF', '#ECFDF5', '#FFF7ED', '#F5F3FF', '#FFF1F2', '#F0FDFA', '#FEF3C7', '#EDE9FE'];
    $locEmojis = ['🏙️', '🌆', '🏛️', '🌊', '💎', '🎓', '🏰', '🗺️'];
    $mapDots = $locations->take(8)->values();
    $mapColors = ['#34D399', '#60A5FA', '#F9A8D4', '#FBBF24', '#A78BFA', '#FB7185', '#2DD4BF', '#F472B6'];
    $breakdownMax = max((int) $locationCountryBreakdown->max('locations'), 1);
    $flagHints = [
        'india' => '🇮🇳', 'united states' => '🇺🇸', 'usa' => '🇺🇸', 'u.s.a.' => '🇺🇸',
        'united arab emirates' => '🇦🇪', 'uae' => '🇦🇪', 'united kingdom' => '🇬🇧', 'uk' => '🇬🇧',
        'singapore' => '🇸🇬', 'australia' => '🇦🇺', 'canada' => '🇨🇦', 'germany' => '🇩🇪',
        'france' => '🇫🇷', 'spain' => '🇪🇸', 'italy' => '🇮🇹', 'netherlands' => '🇳🇱', 'brazil' => '🇧🇷',
    ];
@endphp

@section('page-title-html')
    <span class="text-[22px] font-bold tracking-[-0.025em] text-[#1A1E2E]">{{ __('modules.locationsPage.titleJob') }}
        <span class="jc-cat-serif">{{ __('modules.locationsPage.titleAccent') }}</span>
    </span>
@endsection

@section('page-subtitle')
    {{ __('modules.locationsPage.subtitle') }}
@endsection

@if(in_array('add_locations', $userPermissions))
@section('create-button')
    <button type="button" onclick="window.locModalOpenCreate()"
            class="inline-flex items-center gap-2 rounded-xl bg-[#2563EB] px-5 py-2.5 text-[13px] font-semibold text-white shadow-sm transition hover:bg-[#1d4ed8] focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
        </svg>
        @lang('app.createNew')
    </button>
@endsection
@endif

@section('content')
    <div class="flex flex-col gap-6">
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="flex items-center gap-2.5 rounded-xl border border-[#E8E6E1] bg-white px-3 py-2.5">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#EFF6FF]">
                    <svg class="h-4 w-4" fill="none" stroke="#2563EB" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-widest text-[#8892A0]">{{ __('modules.locationsPage.statTotalCities') }}</p>
                    <p class="text-[18px] font-extrabold tracking-tight text-[#1A1E2E]">{{ number_format($locationStatTotalCities) }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2.5 rounded-xl border border-[#E8E6E1] bg-white px-3 py-2.5">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#F5F3FF]">
                    <svg class="h-4 w-4" fill="none" stroke="#7C3AED" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/>
                    </svg>
                </div>
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-widest text-[#8892A0]">{{ __('modules.locationsPage.statCountries') }}</p>
                    <p class="text-[18px] font-extrabold tracking-tight text-[#1A1E2E]">{{ number_format($locationStatCountries) }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2.5 rounded-xl border border-[#E8E6E1] bg-white px-3 py-2.5">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#ECFDF5]">
                    <svg class="h-4 w-4" fill="none" stroke="#059669" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-widest text-[#8892A0]">{{ __('modules.locationsPage.statActiveJobs') }}</p>
                    <p class="text-[18px] font-extrabold tracking-tight text-[#1A1E2E]">{{ number_format($locationStatActiveJobs) }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2.5 rounded-xl border border-[#E8E6E1] bg-white px-3 py-2.5">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#FFF7ED]">
                    <svg class="h-4 w-4" fill="none" stroke="#F97316" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-widest text-[#8892A0]">{{ __('modules.locationsPage.statCandidates') }}</p>
                    <p class="text-[18px] font-extrabold tracking-tight text-[#1A1E2E]">{{ number_format($locationStatCandidates) }}</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-5 xl:grid-cols-[1fr_340px]">
            <div class="jc-table-card min-w-0">
                <div class="jc-table-toolbar">
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="jc-t-search">
                            <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="#9CA3AF" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <input type="search" id="loc-table-search" placeholder="{{ __('modules.locationsPage.searchPlaceholder') }}" autocomplete="off">
                        </div>
                        <select id="loc-country-filter" class="cursor-pointer rounded-xl border border-[#E2DED8] bg-white px-3 py-2 text-[12.5px] font-medium text-[#5A6478] outline-none focus:border-[#2563EB]">
                            <option value="">{{ __('modules.locationsPage.filterAllCountries') }}</option>
                            @foreach($locationCountriesForFilter as $c)
                                <option value="{{ $c['id'] }}">{{ ucwords($c['name']) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-center gap-2 text-[12px] text-[#8892A0]">
                        <span>{{ __('modules.locationsPage.showing') }}</span>
                        <span class="font-semibold text-[#1A1E2E]" id="loc-show-count">{{ $locTotal }}</span>
                        <span>{{ __('modules.locationsPage.of') }}</span>
                        <span class="font-semibold text-[#1A1E2E]" id="loc-total-num">{{ $locTotal }}</span>
                        <span>{{ __('modules.locationsPage.locations') }}</span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="jc-cat-table">
                        <thead>
                        <tr>
                            <th style="width:56px;">#</th>
                            <th>{{ __('modules.locationsPage.columnLocation') }}</th>
                            <th>{{ __('modules.locationsPage.columnCountry') }}</th>
                            <th>{{ __('modules.locationsPage.columnOpenJobs') }}</th>
                            <th>{{ __('modules.locationsPage.columnAdded') }}</th>
                            <th class="jc-th-right" style="padding-right:20px;">@lang('app.action')</th>
                        </tr>
                        </thead>
                        <tbody id="loc-tbody">
                        @forelse($locations as $location)
                            @php
                                $cname = $location->country ? strtolower($location->country->country_name) : '';
                                $flag = $flagHints[$cname] ?? '🌍';
                                $hi = crc32($location->location) % count($locIconBgs);
                                $searchBlob = strtolower($location->location.' '.($location->country ? $location->country->country_name : ''));
                            @endphp
                            <tr class="loc-row"
                                data-country-id="{{ $location->country_id ?? '' }}"
                                data-search="{{ e($searchBlob) }}">
                                <td class="jc-td-num text-[12.5px] font-semibold text-[#C4CBD4]">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</td>
                                <td>
                                    <div class="flex items-center gap-2.5">
                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-base" style="background: {{ $locIconBgs[$hi] }};">
                                            {{ $locEmojis[$hi % count($locEmojis)] }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-[13.5px] font-bold text-[#1A1E2E]">{{ ucwords($location->location) }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <span class="text-lg leading-none">{{ $flag }}</span>
                                        <span class="text-[13px] font-semibold text-[#3D4A5C]">{{ $location->country ? ucwords($location->country->country_name) : '—' }}</span>
                                    </div>
                                </td>
                                <td>
                                    @if((int) $location->open_jobs_count > 0)
                                        <span class="inline-flex items-center gap-1 rounded-md bg-[#ECFDF5] px-2.5 py-1 text-[12px] font-semibold text-[#065F46]">{{ (int) $location->open_jobs_count }}</span>
                                    @else
                                        <span class="text-[12.5px] font-semibold text-[#C4CBD4]">0</span>
                                    @endif
                                </td>
                                <td class="text-[13px] text-[#5A6478]">{{ $location->created_at ? $location->created_at->format('M j, Y') : '—' }}</td>
                                <td class="jc-td-right">
                                    <div class="inline-flex items-center justify-end gap-1.5">
                                        @if(in_array('edit_locations', $userPermissions))
                                            <a href="{{ route('admin.locations.edit', $location->id) }}"
                                               class="jc-btn-edit inline-flex h-[30px] w-[30px] items-center justify-center rounded-lg transition"
                                               title="@lang('app.edit')" aria-label="@lang('app.edit')">
                                                <i class="fa fa-pencil text-[12px]" aria-hidden="true"></i>
                                            </a>
                                        @endif
                                        @if(in_array('delete_locations', $userPermissions))
                                            <a href="javascript:;"
                                               class="jc-btn-del sa-params inline-flex h-[30px] w-[30px] items-center justify-center rounded-lg transition"
                                               data-row-id="{{ $location->id }}"
                                               title="@lang('app.delete')" aria-label="@lang('app.delete')">
                                                <i class="fa fa-times text-[12px]" aria-hidden="true"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr id="loc-native-empty">
                                <td colspan="6" class="py-12 text-center">
                                    <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-[#F1F3F7]">
                                        <svg class="h-6 w-6" fill="none" stroke="#C4CBD4" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </div>
                                    <p class="text-[13.5px] font-semibold text-[#5A6478]">@lang('modules.emptyTable')</p>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                    <div id="loc-filter-empty" class="{{ $locTotal ? 'hidden' : '' }} py-12 text-center">
                        <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-[#F1F3F7]">
                            <svg class="h-6 w-6" fill="none" stroke="#C4CBD4" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <p class="text-[13.5px] font-semibold text-[#5A6478]">{{ __('modules.locationsPage.emptyFilter') }}</p>
                        <p class="mt-1 text-[12px] text-[#B0B8C4]">{{ __('modules.locationsPage.emptyFilterHint') }}</p>
                    </div>
                </div>
            </div>

            <div class="flex min-w-0 flex-col gap-4">
                <!-- <div class="relative min-h-[260px] overflow-hidden rounded-[18px] bg-gradient-to-br from-[#0F1F3D] via-[#162849] to-[#1B3560]">
                    <div class="pointer-events-none absolute inset-0 opacity-40" style="background-image: radial-gradient(circle, rgba(255,255,255,0.04) 1px, transparent 1px); background-size: 22px 22px;"></div>
                    <div class="relative z-10 flex h-full min-h-[260px] flex-col justify-between p-5">
                        <div>
                            <p class="mb-1 text-[10px] font-bold uppercase tracking-widest text-white/35">{{ __('modules.locationsPage.mapTitle') }}</p>
                            <h3 class="text-[15px] font-bold text-white">{{ __('modules.locationsPage.mapHeading') }}</h3>
                        </div>
                        <div class="relative flex flex-1 items-center justify-center py-4" style="min-height: 150px;">
                            <svg class="pointer-events-none absolute opacity-15" viewBox="0 0 200 200" width="180" height="160" aria-hidden="true">
                                <path d="M100 20 L140 30 L160 55 L155 80 L170 100 L160 130 L140 150 L120 170 L100 180 L80 170 L60 150 L40 130 L30 100 L45 80 L40 55 L60 30 Z" fill="none" stroke="rgba(255,255,255,0.6)" stroke-width="1.5"/>
                            </svg>
                            <div class="relative h-[160px] w-[180px]">
                                @foreach($mapDots as $idx => $dotLoc)
                                    @php
                                        $angle = ($idx / max($mapDots->count(), 1)) * 2 * M_PI;
                                        $radius = 28 + (abs(crc32($dotLoc->location)) % 22);
                                        $leftPct = 50 + cos($angle) * ($radius / 1.8);
                                        $topPct = 50 + sin($angle) * ($radius / 2.2);
                                        $dotColor = $mapColors[$idx % count($mapColors)];
                                    @endphp
                                    <div class="absolute z-10" style="left: {{ $leftPct }}%; top: {{ $topPct }}%; transform: translate(-50%, -50%);" title="{{ e(ucwords($dotLoc->location)) }}">
                                        <div class="relative h-3 w-3 cursor-default rounded-full" style="background: {{ $dotColor }};">
                                            <div class="loc-map-dot-ring pointer-events-none absolute rounded-full" style="inset: -4px; background: {{ $dotColor }}33;"></div>
                                        </div>
                                    </div>
                                    <div class="pointer-events-none absolute z-20 rounded-md border border-white/15 bg-white/10 px-2 py-0.5 text-[11px] font-bold text-white backdrop-blur-sm"
                                         style="left: {{ min(88, $leftPct + 6) }}%; top: {{ max(8, $topPct - 12) }}%; transform: translate(-50%, -50%);">
                                        {{ \Illuminate\Support\Str::limit(ucwords($dotLoc->location), 12) }}
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div> -->

                <div class="rounded-2xl border border-[#E8E6E1] bg-white p-5">
                    <p class="mb-4 text-[10.5px] font-bold uppercase tracking-[0.12em] text-[#8892A0]">{{ __('modules.locationsPage.byCountry') }}</p>
                    <div class="flex flex-col gap-3">
                        @forelse($locationCountryBreakdown as $row)
                            <div>
                                <div class="mb-1 flex items-center justify-between gap-2 text-[12.5px]">
                                    <span class="truncate font-semibold text-[#1A1E2E]">{{ ucwords($row['name']) }}</span>
                                    <span class="shrink-0 text-[11px] font-medium text-[#8892A0]">{{ $row['locations'] }} {{ __('modules.locationsPage.citiesLabel') }}</span>
                                </div>
                                <div class="h-2 overflow-hidden rounded-full bg-[#F1F3F7]">
                                    <div class="h-full rounded-full bg-[#2563EB]/90 transition-all" style="width: {{ min(100, round(100 * $row['locations'] / $breakdownMax)) }}%"></div>
                                </div>
                                <p class="mt-1 text-[11px] text-[#B0B8C4]">{{ $row['open_jobs'] }} {{ __('modules.locationsPage.openJobsShort') }}</p>
                            </div>
                        @empty
                            <p class="text-[12.5px] text-[#8892A0]">@lang('modules.emptyTable')</p>
                        @endforelse
                    </div>
                </div>

                @if(in_array('add_locations', $userPermissions))
                    <div class="rounded-2xl border border-[#E8E6E1] bg-white p-5">
                        <p class="mb-3 text-[10.5px] font-bold uppercase tracking-[0.12em] text-[#8892A0]">{{ __('modules.locationsPage.quickAddTitle') }}</p>
                        <button type="button" onclick="window.locModalOpenCreate()"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#2563EB] px-4 py-2.5 text-[13px] font-semibold text-white transition hover:bg-[#1d4ed8]">
                            @lang('app.createNew')
                        </button>
                        <p class="mt-2 text-[11px] text-[#B0B8C4]">{{ __('modules.locationsPage.quickAddHint') }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if(in_array('add_locations', $userPermissions))
        <div id="loc-modal-create" class="jc-modal-bg" role="dialog" aria-modal="true" aria-labelledby="loc-create-title" onclick="window.locBackdropCloseCreate(event)">
            <div class="jc-modal" style="max-width:480px;width:100%;" onclick="event.stopPropagation()">
                <div class="jc-modal-hd">
                    <div>
                        <h3 id="loc-create-title" class="text-[16px] font-bold tracking-[-0.01em] text-[#1A1E2E]">{{ __('modules.locationsPage.modalCreateTitle') }}</h3>
                        <p class="mt-0.5 text-[12px] text-[#8892A0]">{{ __('Enter the city, region or location for your jobs.') }}</p>
                    </div>
                    <button type="button" onclick="window.locModalCloseCreate()" class="flex h-8 w-8 items-center justify-center rounded-lg text-[#8892A0] transition hover:bg-gray-100" aria-label="@lang('app.close')">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form class="ajax-form" method="POST" id="loc-form-create" onsubmit="return false;">
                    @csrf
                    <div id="loc-create-alert"></div>
                    <div class="jc-modal-body space-y-4">
                        <div class="form-group">
                            <label class="jc-field-label">@lang('menu.locations') <span class="text-red-400">*</span></label>
                            <p class="mb-2 text-[11px] text-[#B0B8C4]">{{ __('For example: Jaipur or Remote.') }}</p>
                            <div id="loc-education-fields" class="space-y-2">
                                <div class="flex overflow-hidden rounded-lg border border-[#E2DED8] transition focus-within:border-[#2563EB] focus-within:ring-2 focus-within:ring-[#2563EB]/10">
                                    <input type="text" name="location" required maxlength="255" autocomplete="off"
                                           class="min-w-0 flex-1 border-0 bg-transparent px-3 py-2.5 text-[13.5px] text-[#1A1E2E] outline-none placeholder:text-[#C4CBD4]"
                                           placeholder="@lang('menu.locations') @lang('app.name')">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="jc-modal-ft">
                        <button type="button" class="jc-btn-cancel" onclick="window.locModalCloseCreate()">@lang('app.cancel')</button>
                        <button type="button" id="loc-save-create" class="jc-btn-save">{{ __('modules.locationsPage.saveLocations') }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection

@push('footer-script')
    @if(in_array('add_locations', $userPermissions))
        <script src="{{ asset('assets/node_modules_files/select2/dist/js/select2.full.min.js') }}" type="text/javascript"></script>
    @endif
    <script>
        @if(in_array('add_locations', $userPermissions))
        (function () {

            window.locBackdropCloseCreate = function (e) {
                if (e.target.id === 'loc-modal-create') {
                    window.locModalCloseCreate();
                }
            };

            window.locModalOpenCreate = function () {
                var $form = $('#loc-form-create');
                $form.find('.has-error').removeClass('has-error');
                $form.find('.help-block').remove();
                $form.find('input, select').removeClass('border-red-500');
                $('#loc-create-alert').html('');
                $('.loc-extra-location').remove();
                $form.find('input[name="location"]').first().val('');
                $('#loc-modal-create').addClass('is-open');
                $('body').addClass('overflow-hidden');
                $form.find('input[name="location"]').first().focus();
            };

            window.locModalCloseCreate = function () {
                $('#loc-modal-create').removeClass('is-open');
                $('body').removeClass('overflow-hidden');
            };
            $('#loc-save-create').on('click', function () {
                $.easyAjax({
                    url: '{{ route('admin.locations.store') }}',
                    container: '#loc-form-create',
                    type: 'POST',
                    redirect: true,
                    data: $('#loc-form-create').serialize(),
                });
            });

            @if(request('open') === 'create')
            $(function () {
                window.locModalOpenCreate();
            });
            @endif

            $(document).on('keydown', function (e) {
                if (e.key === 'Escape' && $('#loc-modal-create').hasClass('is-open')) {
                    window.locModalCloseCreate();
                }
            });
        })();
        @endif

        (function () {
            function locRowCount() {
                return $('#loc-tbody tr.loc-row').length;
            }

            function locRenumberVisible() {
                var n = 0;
                $('#loc-tbody tr.loc-row:visible').each(function () {
                    n++;
                    $(this).find('td.jc-td-num').first().text(String(n).padStart(2, '0'));
                });
            }

            function locApplyFilter() {
                var q = ($('#loc-table-search').val() || '').toLowerCase().trim();
                var cid = ($('#loc-country-filter').val() || '').toString();
                var visible = 0;
                $('#loc-tbody tr.loc-row').each(function () {
                    var $r = $(this);
                    var blob = ($r.data('search') || '').toString().toLowerCase();
                    var textMatch = !q || blob.indexOf(q) !== -1;
                    var countryMatch = !cid || String($r.data('country-id')) === cid;
                    var match = textMatch && countryMatch;
                    $r.toggle(match);
                    if (match) visible++;
                });
                $('#loc-show-count').text(visible);
                locRenumberVisible();
                var hasRows = locRowCount() > 0;
                $('#loc-filter-empty').toggleClass('hidden', !(hasRows && visible === 0));
            }

            $('#loc-table-search').on('input', locApplyFilter);
            $('#loc-country-filter').on('change', locApplyFilter);
        })();

        $('body').on('click', '.sa-params', function () {
            var id = $(this).data('row-id');
            swal({
                title: "@lang('errors.areYouSure')",
                text: "@lang('errors.deleteWarning')",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "@lang('app.delete')",
                cancelButtonText: "@lang('app.cancel')",
                closeOnConfirm: true,
                closeOnCancel: true
            }, function (isConfirm) {
                if (isConfirm) {
                    var url = "{{ route('admin.locations.destroy', ':id') }}";
                    url = url.replace(':id', id);
                    var token = "{{ csrf_token() }}";

                    $.easyAjax({
                        type: 'POST',
                        url: url,
                        data: {'_token': token, '_method': 'DELETE'},
                        success: function (response) {
                            if (response.status == "success") {
                                $.unblockUI();
                                window.location.reload();
                            }
                        }
                    });
                }
            });
        });
    </script>
@endpush
