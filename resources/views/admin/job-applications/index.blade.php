@extends('layouts.app')

@php
    $jaDefaultStart = now()->subDays(30)->format('Y-m-d');
    $jaDefaultEnd   = now()->format('Y-m-d');
    $jaStagesJson   = $boardColumns->map(fn($c) => [
        'id'    => $c->id,
        'slug'  => $c->status,
        'color' => $c->color ?? '#2563eb',
        'label' => ucfirst($c->status),
    ])->values()->toJson();

@endphp

@push('head-script')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Plus+Jakarta+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/node_modules_files/html5-editor/bootstrap-wysihtml5.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/node_modules_files/bootstrap-datepicker/bootstrap-datepicker.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/node_modules_files/multiselect/css/multi-select.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/iCheck/all.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    @include('admin.job-applications.partials.select2-filter-skin')

    <style>
        .mb-20 { margin-bottom: 20px; }
        .datepicker { z-index: 9999 !important; }
        .ja-board-scope { font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif; }

        /* ── Stage tabs ── */
        .ja-stage-tab {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 13px 16px;
            border: none;
            background: none;
            cursor: pointer;
            font-family: inherit;
            font-size: 13.5px;
            font-weight: 600;
            color: #64748b;
            border-bottom: 2.5px solid transparent;
            white-space: nowrap;
            transition: color .15s;
        }
        .ja-stage-tab:hover { color: #1A1E2E; }
        .ja-stage-tab.active { color: var(--tab-color, #2563eb); border-bottom-color: var(--tab-color, #2563eb); }
        .ja-stage-tab.active .ja-stage-badge { background: var(--tab-color, #2563eb); color: #fff; }
        .ja-stage-badge {
            background: #F1F3F7;
            color: #64748b;
            font-size: 11px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 20px;
            min-width: 20px;
            text-align: center;
            transition: background .15s, color .15s;
        }
        .ja-ko-tab { margin-left: auto; border-left: 1px solid #E8E6E1; padding-left: 4px; }
        .ja-ko-tab .ja-stage-tab { color: #94a3b8; }
        .ja-ko-tab .ja-stage-tab:hover { color: #dc2626; }
        .ja-ko-tab .ja-stage-tab.active { color: #dc2626; border-bottom-color: #dc2626; }
        .ja-ko-tab .ja-stage-tab.active .ja-stage-badge { background: #dc2626; color: #fff; }
        .ja-ko-tab .ja-stage-badge { background: #fee2e2; color: #dc2626; }

        /* ── Filter bar ── */
        .ja-filter-btn-active {
            border-color: #2563eb !important;
            background: #eff6ff !important;
            color: #2563eb !important;
        }
        .ja-filter-active-count {
            font-size: 11px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 999px;
            background: #2563eb;
            color: #fff;
            display: none;
        }
        .ja-filter-active-count.show { display: inline-flex; }
        .hidden { display: none !important; visibility: hidden !important; }

        /* ── Table row actions ── */
        #myTable {
            table-layout: fixed;
        }
        #myTable th:nth-child(1),
        #myTable td:nth-child(1) { width: 6% !important; }
        #myTable th:nth-child(2),
        #myTable td:nth-child(2) { width: 29% !important; }
        #myTable th:nth-child(3),
        #myTable td:nth-child(3) { width: 19% !important; }
        #myTable th:nth-child(4),
        #myTable td:nth-child(4) { width: 18% !important; }
        #myTable th:nth-child(5),
        #myTable td:nth-child(5) {
            width: 12% !important;
            white-space: nowrap;
        }
        #myTable th:nth-child(6),
        #myTable td:nth-child(6) { width: 16% !important; }

        .ja-row-actions { display: flex; gap: 5px; justify-content: flex-end; align-items: center; }
        .ja-act-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 5px 10px;
            border: 1.5px solid #E2DED8;
            border-radius: 8px;
            background: #fff;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
            color: #5A6478;
            font-family: inherit;
            transition: background .12s, border-color .12s, color .12s;
        }
        .ja-act-btn:hover { background: #F1F3F7; border-color: #C4CBD4; }
        .ja-act-btn.move { background: #2563eb; color: #fff; border-color: #2563eb; }
        .ja-act-btn.move:hover { background: #1d4ed8; }
        .ja-act-btn.reject { color: #dc2626; border-color: #fecaca; background: #fef2f2; }
        .ja-act-btn.reject:hover { background: #fee2e2; }
        .ja-act-btn.restore { color: #0369a1; border-color: #bae6fd; background: #e0f2fe; }
        .ja-act-btn.restore:hover { background: #bae6fd; }
        .ja-act-btn.icon-only { padding: 5px 8px; }

        /* ── Bulk bar ── */
        #ja-bulk-bar {
            position: fixed;
            bottom: 24px;
            left: 50%;
            transform: translateX(-50%);
            background: #0f172a;
            color: #fff;
            border-radius: 14px;
            padding: 10px 16px;
            display: none;
            align-items: center;
            gap: 12px;
            box-shadow: 0 12px 40px rgba(15,23,42,.35);
            z-index: 999;
            white-space: nowrap;
            min-width: 400px;
        }
        #ja-bulk-bar.show { display: flex; }
        .ja-bulk-cnt {
            background: #2563eb;
            width: 28px;
            height: 28px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 13px;
            flex-shrink: 0;
        }
        .ja-bulk-sep { width: 1px; height: 24px; background: #334155; flex-shrink: 0; }
        .ja-bulk-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border: none;
            border-radius: 9px;
            font-family: inherit;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }
        .ja-bulk-btn.blue { background: #2563eb; color: #fff; }
        .ja-bulk-btn.blue:hover { background: #1d4ed8; }
        .ja-bulk-btn.red { background: #dc2626; color: #fff; }
        .ja-bulk-btn.red:hover { background: #b91c1c; }
        .ja-bulk-btn.teal { background: #0ea5e9; color: #fff; }
        .ja-bulk-btn.clear { background: none; color: #94a3b8; font-size: 13px; }
        .ja-bulk-btn.clear:hover { color: #fff; }

        /* ── KO banner ── */
        #ja-ko-banner {
            margin: 12px 0 0;
            padding: 10px 16px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 10px;
            font-size: 12.5px;
            color: #991b1b;
            display: none;
            align-items: center;
            gap: 8px;
        }
        #ja-ko-banner.show { display: flex; }

        /* ── Checkbox ── */
        .ja-chk {
            width: 18px;
            height: 18px;
            border-radius: 4px;
            border: 1.5px solid #C4CBD4;
            background: #fff;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: all .12s;
        }
        .ja-chk.on { background: #2563eb; border-color: #2563eb; }
        .ja-chk input { display: none; }
        .ja-move-wrap { display: inline-flex; }
        .ja-move-drop button:hover { background: #F1F3F7 !important; }

        /* ── Draggable stage tabs ── */
        .ja-stage-tab[draggable="true"] { cursor: grab; }
        .ja-stage-tab[draggable="true"]:active { cursor: grabbing; }
        .ja-stage-tab.dragging { opacity: 0.35; background: #F1F3F7; }
        .ja-stage-tab.drag-over {
            background: #EFF6FF;
            border-bottom-color: #2563eb !important;
            box-shadow: inset 3px 0 0 #2563eb;
        }
        #ja-stage-tabs-draggable { display: contents; }
    </style>
@endpush

@if(in_array("add_job_applications", $userPermissions))
@section('create-button')
    <a href="{{ route('admin.job-applications.create') }}" class="inline-flex shrink-0 items-center gap-2 rounded-xl bg-[#2563EB] px-5 py-2.5 text-[13px] font-semibold text-white shadow-sm transition hover:bg-[#1d4ed8] focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
        @lang('app.createNew')
    </a>
@endsection
@endif

@section('content')
@include('admin.partials.candidate-communications')
<div class="ja-board-scope -mx-4 -mt-2 flex min-h-[calc(100dvh-9.5rem)] flex-col bg-[#EEF0F5] sm:-mx-6">
    <div class="flex min-h-0 flex-1 flex-col overflow-hidden px-5 pb-5 pt-5 sm:px-6">

        {{-- ── Top toolbar ── --}}
        <div class="mb-0 flex flex-shrink-0 flex-wrap items-center gap-2.5">
            {{-- View toggle --}}
            <div class="inline-flex gap-0.5 rounded-[10px] bg-[#F1F3F7] p-0.5">
                <span class="inline-flex items-center gap-1.5 rounded-lg bg-white px-3.5 py-1.5 text-[12.5px] font-semibold text-[#1A1E2E] shadow-[0_1px_4px_rgba(0,0,0,0.08)]">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                    @lang('app.tableView')
                </span>
            </div>

            {{-- Export --}}
            <button type="button" onclick="exportJobApplication()" class="inline-flex items-center gap-1.5 rounded-[9px] border-[1.5px] border-[#E2DED8] bg-white px-3.5 py-[7px] text-[12.5px] font-semibold text-[#5A6478] transition hover:border-[#2563EB] hover:bg-[#EFF6FF] hover:text-[#2563EB]">
                <i class="fa fa-upload"></i>
                @lang('menu.export')
            </button>
        </div>

        {{-- ── Filter bar (collapsible) ── --}}
        <div id="ja-table-filter-bar" class="border-b border-[#E8E6E1] bg-white mt-3">
            <div class="px-5 sm:px-6">
                <div class="flex items-center justify-between gap-2 pb-3 pt-0.5">
                    <h4 class="text-[13px] font-bold text-[#1A1E2E]">@lang('app.filterBy')</h4>
                   
                </div>
                <form id="filter-form" class="flex flex-wrap items-end gap-3.5 pb-3">

                    <div class="flex min-w-[160px] flex-1 flex-col gap-1 sm:max-w-[220px]">
                        <label class="text-[10.5px] font-bold uppercase tracking-[0.08em] text-[#8892A0]">@lang('app.status')</label>
                        <select class="select2 w-full" name="status" id="status">
                            <option value="all">@lang('modules.jobApplication.allStatus')</option>
                            @forelse($boardColumns as $col)
                                <option value="{{ $col->id }}">{{ ucfirst($col->status) }}</option>
                            @empty
                            @endforelse
                        </select>
                    </div>

                    <div class="flex min-w-[160px] flex-1 flex-col gap-1 sm:max-w-[220px]">
                        <label class="text-[10.5px] font-bold uppercase tracking-[0.08em] text-[#8892A0]">@lang('app.company')</label>
                        <select class="select2 w-full" name="company" id="company">
                            <option value="all">@lang('modules.jobApplication.allCompany')</option>
                            @forelse($companies as $company)
                                <option title="{{ ucfirst($company->company_name) }}" value="{{ $company->id }}">{{ ucfirst($company->company_name) }}</option>
                            @empty
                            @endforelse
                        </select>
                    </div>
                    <div class="flex min-w-[160px] flex-1 flex-col gap-1 sm:max-w-[220px]">
                        <label class="text-[10.5px] font-bold uppercase tracking-[0.08em] text-[#8892A0]">@lang('menu.locations')</label>
                        <select class="select2 w-full" name="location" id="location">
                            <option value="all">@lang('modules.jobApplication.allLocation')</option>
                            @forelse($locations as $location)
                                <option value="{{ $location->id }}">{{ ucfirst($location->location) }}</option>
                            @empty
                            @endforelse
                        </select>
                    </div>
                    <div class="flex min-w-[160px] flex-1 flex-col gap-1 sm:max-w-[220px]">
                        <label class="text-[10.5px] font-bold uppercase tracking-[0.08em] text-[#8892A0]">@lang('menu.jobs')</label>
                        <select class="select2 w-full" name="jobs" id="jobs">
                            <option value="all">@lang('modules.jobApplication.allJobs')</option>
                            @forelse($jobs as $job)
                                <option title="{{ ucfirst($job->title) }}" value="{{ $job->id }}">{{ ucfirst($job->title) }}</option>
                            @empty
                            @endforelse
                        </select>
                    </div>

                   

                    <div class="flex min-w-[160px] flex-1 flex-col gap-1 sm:max-w-[240px]">
                        <label class="text-[10.5px] font-bold uppercase tracking-[0.08em] text-[#8892A0]">@lang('modules.jobApplication.allQuestion')</label>
                        <select class="select2 w-full" name="question" id="questions">
                            <option value="all">@lang('modules.jobApplication.allQuestion')</option>
                            @forelse($questions as $question)
                                <option value="{{ $question->id }}">{{ ucfirst($question->question) }}</option>
                            @empty
                            @endforelse
                        </select>
                    </div>

                    <div class="hidden flex min-w-[160px] flex-1 flex-col gap-1 sm:max-w-[220px]" id="question_value">
                        <label class="text-[10.5px] font-bold uppercase tracking-[0.08em] text-[#8892A0]">@lang('app.filterBy')</label>
                        <input type="text" class="form-control rounded-[10px] border-[1.5px] border-[#E2DED8] bg-[#F8F7F4] text-[13px]" name="question_value" id="question-value" placeholder="">
                    </div>

                    <div class="flex w-full flex-wrap items-center gap-2.5 pt-1">
                        <button type="button" id="reset-filters" class="inline-flex items-center gap-2 rounded-[9px] border-[1.5px] border-[#E2DED8] bg-white px-4 py-2.5 text-[13px] font-semibold text-[#8892A0] transition hover:border-[#EF4444] hover:text-[#EF4444] focus:outline-none">
                            <i class="fa fa-refresh"></i> @lang('app.reset')
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ── Stage tabs ── --}}
        <div class="flex bg-white border-b border-[#E8E6E1] overflow-x-auto mt-3 rounded-t-[12px]" id="ja-stage-tabs-row">

            {{-- All Applicants tab --}}
            <button
                class="ja-stage-tab active"
                id="ja-tab-all"
                data-stage-id="all"
                onclick="jaStageTabAll()"
                style="--tab-color:#2563eb;">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/></svg>
                All Applicants
                <span class="ja-stage-badge" id="ja-tab-count-all">0</span>
            </button>

            <span id="ja-stage-tabs-draggable">
            {{-- pipeline stage tabs injected here by JS when a job is selected --}}
            </span>

            {{-- Knockouts tab --}}
            <div class="ja-ko-tab flex items-center">
                <button class="ja-stage-tab" id="ja-ko-tab-btn" onclick="jaKOTab()">
                    <i class="fa fa-user-times"></i>
                    Knockouts
                    <span class="ja-stage-badge" id="ja-ko-tab-count">0</span>
                </button>
            </div>
        </div>

        {{-- KO banner --}}
        <div id="ja-ko-banner">
            <i class="fa fa-info-circle"></i>
            These applicants were knocked out by screening rules or manually. They are hidden from the main pipeline. You can restore anyone back to the queue.
        </div>

        {{-- ── Table ── --}}
        <div class="jc-table-card table-wrapper ra-dt-wrap w-full overflow-hidden rounded-b-[12px] border border-t-0 border-[#E8E6E1] bg-white shadow-sm">
            <table id="myTable" class="jc-cat-table display w-full" style="width:100%">
             <thead>
                <tr>
                    <th style="width:40px;"><div class="ja-chk" id="ja-chk-all" onclick="jaToggleAll()" title="Select all"></div></th>
                    <th>@lang('modules.jobApplication.applicantName')</th>
                    <th>@lang('menu.jobs')</th>
                    <th>@lang('menu.locations')</th>
                    <th>@lang('app.status')</th>
                    <th class="jc-th-right" style="padding-right:16px;">@lang('app.action')</th>
                    <th style="display:none;"></th>  {{-- hidden created_at column --}}
                </tr>
                </thead>
            </table>
        </div>

    </div>
</div>

{{-- ── Bulk action bar ── --}}
<div id="ja-bulk-bar">
    <div class="ja-bulk-cnt" id="ja-bulk-cnt">0</div>
    <span style="font-size:13.5px;font-weight:600;">selected</span>
    <div class="ja-bulk-sep"></div>
    <div id="ja-bulk-actions"></div>
    <div class="ja-bulk-sep"></div>
    <button class="ja-bulk-btn clear" onclick="jaClearSelection()">
        <i class="fa fa-times"></i> Clear
    </button>
</div>

@include('admin.application-setting.modal')

<div class="hidden fixed inset-0 z-[210] flex items-center justify-center overflow-y-auto p-4" id="scheduleDetailModal" role="dialog" aria-labelledby="modelHeading" aria-hidden="true">
    <div class="fixed inset-0 bg-[rgba(15,31,61,0.5)] backdrop-blur-[3px] transition-opacity" onclick="$('#scheduleDetailModal').addClass('hidden')"></div>
    <div class="relative z-10 mx-auto w-full max-w-[min(94vw,640px)]">
        <div class="max-h-[min(90vh,720px)] overflow-hidden rounded-[20px] bg-white shadow-2xl" id="modal-data-application">
            <div class="flex items-start justify-between gap-3 border-b border-[#F0EEE9] px-6 pb-4 pt-5">
                <div class="min-w-0">
                    <h3 class="text-[15.5px] font-bold leading-snug text-[#1A1E2E]" id="modelHeading"></h3>
                </div>
                <button type="button" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-[#8892A0] transition hover:bg-[#F1F3F7]" onclick="$('#scheduleDetailModal').addClass('hidden')">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="modal-body max-h-[min(60vh,420px)] overflow-y-auto p-6 text-[13px] text-[#1A1E2E]">Loading...</div>
            <div class="flex items-center justify-end gap-2.5 border-t border-[#F0EEE9] px-6 py-3.5">
                <button type="button" class="rounded-[9px] bg-[#F1F3F7] px-5 py-2.5 text-[13px] font-semibold text-[#5A6478]" onclick="$('#scheduleDetailModal').addClass('hidden')">@lang('app.close')</button>
                <button type="button" id="job-application-modal-save" class="rounded-[9px] bg-[#2563EB] px-6 py-2.5 text-[13px] font-bold text-white">@lang('app.save')</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('footer-script')
    <script src="{{ asset('assets/node_modules_files/select2/dist/js/select2.full.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('assets/node_modules_files/bootstrap-select/bootstrap-select.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('assets/node_modules_files/moment/moment.js') }}" type="text/javascript"></script>
    <script src="{{ asset('assets/node_modules_files/multiselect/js/jquery.multi-select.js') }}"></script>
    <script src="{{ asset('assets/plugins/iCheck/icheck.min.js') }}"></script>
    <script src="{{ asset('assets/node_modules_files/bootstrap-datepicker/bootstrap-datepicker.min.js') }}" type="text/javascript"></script>

    <script>
    // Seeded with global statuses so slug lookups (e.g. "rejected") work
    // even before a specific job is selected in the filter.
    var jaStages = [
        @foreach($boardColumns as $col)
        { id: {{ $col->id }}, slug: '{{ $col->status }}', label: '{{ ucfirst($col->status) }}', color: '{{ $col->color ?? '#6B7280' }}' },
        @endforeach
    ];

    // ── State ────────────────────────────────────────────────────
    var jaActiveStageId  = 'all';   // 'all' | stage id
    var jaStatusNameFilter = new URLSearchParams(window.location.search).get('status_name') || '';
    var jaShowKO         = false;
    var jaSelectedIds    = new Set();
    window.ccSelection = function () { return Array.from(jaSelectedIds).map(function(id) { return {type:'application', id:Number(id)}; }); };
    var table;
    var jaFilterStorageKey = 'job-application-filters-v1-{{ auth()->id() }}';

    function jaReadSavedFilters() {
        try { return JSON.parse(sessionStorage.getItem(jaFilterStorageKey)) || {}; }
        catch (e) { return {}; }
    }

    function jaSaveFilters() {
        try {
            sessionStorage.setItem(jaFilterStorageKey, JSON.stringify({
                status: $('#status').val() || 'all',
                company: $('#company').val() || 'all',
                location: $('#location').val() || 'all',
                jobs: $('#jobs').val() || 'all',
                questions: $('#questions').val() || 'all',
                question_value: $('#question-value').val() || '',
                knockout: !!jaShowKO
            }));
        } catch (e) {}
    }

    // ── Prev/Next: ordered ID list (populated in drawCallback) ───
    var jaApplicantIds = [];

    /**
     * Rebuilds jaApplicantIds from the currently visible DataTable rows,
     * in the exact visual order they appear on screen.
     * Called automatically after every draw (filter / sort / paginate).
     */
    function jaRebuildIds() {
        jaApplicantIds = [];
        // Each row has a .ja-row-chk with data-id — use that as the source of truth
        document.querySelectorAll('#myTable tbody .ja-row-chk[data-id]').forEach(function (el) {
            var id = parseInt(el.getAttribute('data-id'));
            if (id) jaApplicantIds.push(id);
        });
    }

    // ── Helpers ──────────────────────────────────────────────────
    function jaNextStage(currentSlug) {
        var order = jaStages.map(function(s){ return s.slug; });
        var idx = order.indexOf(currentSlug);
        if (idx === -1 || idx >= order.length - 1) return null;
        var nextSlug = order[idx + 1];
        return jaStages.find(function(s){ return s.slug === nextSlug; }) || null;
    }

    // ── Tab switching ────────────────────────────────────────────
    function jaStageTab(stageId, color, slug) {
        jaStatusNameFilter = '';
        jaShowKO = false;
        jaActiveStageId = stageId;
        jaSelectedIds = new Set();

        document.querySelectorAll('.ja-stage-tab').forEach(function(el){
            el.classList.remove('active');
        });
        var btn = document.querySelector('[data-stage-id="' + stageId + '"]');
        if (btn) { btn.classList.add('active'); btn.style.setProperty('--tab-color', color); }

        document.getElementById('ja-ko-tab-btn').classList.remove('active');
        document.getElementById('ja-ko-banner').classList.remove('show');

        $('#status').val(stageId).trigger('change.select2');
        jaSaveFilters();
        tableLoad('filter');
        jaRenderBulkBar();
        jaTableSyncFilterBadge();
    }

    function jaStageTabAll() {
        jaStatusNameFilter = '';
        jaShowKO = false;
        jaActiveStageId = 'all';
        jaSelectedIds = new Set();

        document.querySelectorAll('.ja-stage-tab').forEach(function(el) {
            el.classList.remove('active');
        });
        var btn = document.getElementById('ja-tab-all');
        if (btn) { btn.classList.add('active'); btn.style.setProperty('--tab-color', '#2563eb'); }

        document.getElementById('ja-ko-tab-btn').classList.remove('active');
        document.getElementById('ja-ko-banner').classList.remove('show');

        $('#status').val('all').trigger('change.select2');
        jaSaveFilters();
        tableLoad('filter');
        jaRenderBulkBar();
        jaTableSyncFilterBadge();
    }

    function jaKOTab() {
        jaStatusNameFilter = '';
        jaShowKO = true;
        jaActiveStageId = 'all';
        jaSelectedIds = new Set();

        document.querySelectorAll('.ja-stage-tab').forEach(function(el){
            el.classList.remove('active');
        });
        document.getElementById('ja-ko-tab-btn').classList.add('active');
        document.getElementById('ja-ko-banner').classList.add('show');

        $('#status').val('all').trigger('change.select2');
        jaSaveFilters();
        tableLoad('knockout');
        jaRenderBulkBar();
    }

    // ── Selection ────────────────────────────────────────────────
    function jaToggleRow(id, el) {
        if (jaSelectedIds.has(id)) {
            jaSelectedIds.delete(id);
            el.classList.remove('on');
            el.innerHTML = '';
        } else {
            jaSelectedIds.add(id);
            el.classList.add('on');
            el.innerHTML = '<i class="fa fa-check" style="font-size:10px;color:#fff"></i>';
        }
        jaRenderBulkBar();
        jaUpdateAllChk();
    }

    function jaToggleAll() {
        var allChk = document.getElementById('ja-chk-all');
        var rowChks = document.querySelectorAll('.ja-row-chk');
        var allOn = allChk.classList.contains('on');
        rowChks.forEach(function(el){
            var id = parseInt(el.dataset.id);
            if (allOn) {
                jaSelectedIds.delete(id);
                el.classList.remove('on');
                el.innerHTML = '';
            } else {
                jaSelectedIds.add(id);
                el.classList.add('on');
                el.innerHTML = '<i class="fa fa-check" style="font-size:10px;color:#fff"></i>';
            }
        });
        if (allOn) { allChk.classList.remove('on'); allChk.innerHTML = ''; }
        else { allChk.classList.add('on'); allChk.innerHTML = '<i class="fa fa-check" style="font-size:10px;color:#fff"></i>'; }
        jaRenderBulkBar();
    }

    function jaUpdateAllChk() {
        var rowChks = document.querySelectorAll('.ja-row-chk');
        var total = rowChks.length;
        var selCount = 0;
        rowChks.forEach(function(el){ if (el.classList.contains('on')) selCount++; });
        var allChk = document.getElementById('ja-chk-all');
        if (total > 0 && selCount === total) {
            allChk.classList.add('on');
            allChk.innerHTML = '<i class="fa fa-check" style="font-size:10px;color:#fff"></i>';
        } else {
            allChk.classList.remove('on');
            allChk.innerHTML = '';
        }
    }

    function jaClearSelection() {
        jaSelectedIds = new Set();
        document.querySelectorAll('.ja-row-chk').forEach(function(el){
            el.classList.remove('on');
            el.innerHTML = '';
        });
        var allChk = document.getElementById('ja-chk-all');
        allChk.classList.remove('on');
        allChk.innerHTML = '';
        jaRenderBulkBar();
    }

    // ── Bulk action bar ──────────────────────────────────────────
    function jaRenderBulkBar() {
        if (window.ccRefreshSelection) window.ccRefreshSelection();
        var n = jaSelectedIds.size;
        var bar = document.getElementById('ja-bulk-bar');
        document.getElementById('ja-bulk-cnt').textContent = n;
        if (!n) { bar.classList.remove('show'); return; }
        bar.classList.add('show');

        var actEl = document.getElementById('ja-bulk-actions');
        var html = '';

        if (jaShowKO) {
            html = '<button class="ja-bulk-btn teal" onclick="jaBulkRestore()"><i class="fa fa-undo"></i> Restore to queue</button>';
        } else {
            var currentStage = jaStages.find(function(s){ return s.id == jaActiveStageId; });
            if (currentStage) {
                var next = jaNextStage(currentStage.slug);
                if (next) {
                    html += '<button class="ja-bulk-btn blue" onclick="jaBulkMove(' + next.id + ')"><i class="fa fa-arrow-right"></i> Move to ' + next.label + '</button> ';
                }
                if (currentStage.slug !== 'rejected' && currentStage.slug !== 'hired') {
                    var rejStage = jaStages.find(function(s){ return s.slug === 'rejected'; });
                    if (rejStage) {
                        html += '<button class="ja-bulk-btn red" onclick="jaBulkMove(' + rejStage.id + ')"><i class="fa fa-times"></i> Reject all</button>';
                    }
                }
            } else {
                var rejStage = jaStages.find(function(s){ return s.slug === 'rejected'; });
                if (rejStage) {
                    html += '<button class="ja-bulk-btn red" onclick="jaBulkMove(' + rejStage.id + ')"><i class="fa fa-times"></i> Reject all</button>';
                }
            }
        }
        actEl.innerHTML = html;
    }

    // ── Bulk actions (AJAX) ──────────────────────────────────────
    function jaBulkMove(toStatusId) {
        if (!jaSelectedIds.size) return;
        var ids = Array.from(jaSelectedIds);
        $.easyAjax({
            url: '{{ route("admin.job-applications.bulk-status-update") }}',
            type: 'POST',
            data: { _token: '{{ csrf_token() }}', ids: ids, status_id: toStatusId },
            success: function(res) {
                if (res.status === 'success') {
                    jaClearSelection();
                    table.draw(false);
                    jaLoadTabCounts();
                }
            }
        });
    }

    function jaBulkRestore() {
        if (!jaSelectedIds.size) return;
        var ids = Array.from(jaSelectedIds);
        $.easyAjax({
            url: '{{ route("admin.job-applications.bulk-restore-knockout") }}',
            type: 'POST',
            data: { _token: '{{ csrf_token() }}', ids: ids },
            success: function(res) {
                if (res.status === 'success') {
                    jaClearSelection();
                    table.draw(false);
                    jaLoadTabCounts();
                }
            }
        });
    }

    function jaMoveOne(appId, toStatusId) {
        $.easyAjax({
            url: '{{ route("admin.job-applications.bulk-status-update") }}',
            type: 'POST',
            data: { _token: '{{ csrf_token() }}', ids: [appId], status_id: toStatusId },
            success: function(res) {
                if (res.status === 'success') {
                    jaSelectedIds.delete(appId);
                    table.draw(false);
                    jaLoadTabCounts();
                    jaRenderBulkBar();
                }
            }
        });
    }

    function jaToggleDrop(id, btnEl) {
        document.querySelectorAll('.ja-move-drop').forEach(function(el) {
            if (el.id !== id) el.style.display = 'none';
        });
        var el = document.getElementById(id);
        if (!el) return;

        var isHidden = el.style.display === 'none';

        if (isHidden) {
            var appId = Number(el.dataset.appId);
            var stages = JSON.parse(el.dataset.stages || '[]');
            el.replaceChildren();
            stages.forEach(function(stage) {
                var button = document.createElement('button');
                button.type = 'button';
                button.style.cssText = 'display:flex;align-items:center;gap:8px;width:100%;padding:8px 14px;border:none;background:none;font-size:13px;font-weight:500;color:#1A1E2E;cursor:pointer;font-family:inherit;text-align:left;';
                button.addEventListener('mouseenter', function() { button.style.background = '#F1F3F7'; });
                button.addEventListener('mouseleave', function() { button.style.background = 'none'; });
                button.addEventListener('click', function() {
                    jaMoveOne(appId, stage.id);
                    jaCloseDrop(id);
                });
                var dot = document.createElement('span');
                dot.style.cssText = 'width:8px;height:8px;border-radius:50%;flex-shrink:0;display:inline-block;';
                dot.style.backgroundColor = stage.color || '#6B7280';
                button.append(dot, stage.label);
                el.appendChild(button);
            });
            if (!stages.length) {
                el.innerHTML = '<div style="padding:10px 14px;color:#8892A0;font-size:12px;">No other stages available</div>';
            }

            el.style.display = 'block';

            if (btnEl) {
                var rect = btnEl.getBoundingClientRect();
                el.style.top   = (rect.bottom + 4) + 'px';
                el.style.left  = Math.max(0, rect.right - 160) + 'px';
                el.style.right = 'auto';
            }
        } else {
            el.style.display = 'none';
        }
    }

    function jaCloseDrop(id) {
        var el = document.getElementById(id);
        if (el) el.style.display = 'none';
    }

    document.addEventListener('click', function(e) {
        if (!e.target.closest('.ja-move-wrap')) {
            document.querySelectorAll('.ja-move-drop').forEach(function(el) {
                el.style.display = 'none';
            });
        }
    });

    function jaMoveOneBySlug(appId, toSlug) {
        var stage = jaStages.find(function(s){ return s.slug === toSlug; });
        if (!stage) { alert('Stage not found: ' + toSlug); return; }
        jaMoveOne(appId, stage.id);
    }

    function jaRestoreOne(appId) {
        $.easyAjax({
            url: '{{ route("admin.job-applications.bulk-restore-knockout") }}',
            type: 'POST',
            data: { _token: '{{ csrf_token() }}', ids: [appId] },
            success: function(res) {
                if (res.status === 'success') {
                    jaSelectedIds.delete(appId);
                    table.draw(false);
                    jaLoadTabCounts();
                    jaRenderBulkBar();
                }
            }
        });
    }

    // ── Tab counts ───────────────────────────────────────────────
    function jaLoadTabCounts() {
        // Read values directly from the DOM elements (not Select2 cache)
        var company        = $('#company').val()        || 'all';
        var jobs           = $('#jobs').val()           || 'all';
        var location       = $('#location').val()       || 'all';
        var questions      = $('#questions').val()      || 'all';
        var question_value = $('#question-value').val() || '';

        console.log('jaLoadTabCounts sending:', { company, jobs, location, questions, question_value });

        $.ajax({
            url: '{{ route("admin.job-applications.stage-counts") }}',
            type: 'GET',
            data: {
                company:        company,
                jobs:           jobs,
                location:       location,
                questions:      questions,
                question_value: question_value
            },
            success: function(res) {
                console.log('stage-counts response:', res);

                var payload = res.data ? res.data : res;
                var counts  = payload.counts;
                var koCount = payload.ko_count;

                if (!counts) { console.warn('No counts key in response'); return; }

                // Zero ALL stage badges first
                jaStages.forEach(function(s) {
                    $('#ja-tab-count-' + s.id).text(0);
                });

                var total = 0;
                $.each(counts, function(stageId, cnt) {
                    var n = parseInt(cnt) || 0;
                    $('#ja-tab-count-' + stageId).text(n);
                    total += n;
                });

                $('#ja-tab-count-all').text(total);
                $('#ja-ko-tab-count').text(koCount || 0);
            },
            error: function(xhr) {
                console.error('stage-counts failed:', xhr.status, xhr.responseText);
            }
        });
    }

    function tableLoad(type) {
        var status         = $('#status').val();
        var jobs           = $('#jobs').val();
        var questions      = $('#questions').val();
        var location       = $('#location').val();
        var company        = $('#company').val();
        var question_value = $('#question-value').val();
        var knockout       = jaShowKO ? 1 : 0;

        // Destroy existing instance cleanly
        if ($.fn.DataTable.isDataTable('#myTable')) {
            $('#myTable').DataTable().destroy();
        }

    // Rebuild thead to avoid column width calculation error
    $('#myTable').html(
        '<thead><tr>' +
            '<th style="width:40px;"><div class="ja-chk" id="ja-chk-all" onclick="jaToggleAll()" title="Select all"></div></th>' +
            '<th>{{ __("modules.jobApplication.applicantName") }}</th>' +
            '<th>{{ __("menu.jobs") }}</th>' +
            '<th>{{ __("menu.locations") }}</th>' +
            '<th>{{ __("app.status") }}</th>' +
            '<th style="padding-right:16px;">{{ __("app.action") }}</th>' +
            '<th style="display:none;"></th>' +
        '</tr></thead>' +
        '<tbody></tbody>'
    );

    table = $('#myTable').DataTable({
        responsive: false,
        autoWidth: false,
        processing: false,
        serverSide: true,
        ajax: {
            url: '{!! route("admin.job-applications.data") !!}',
            data: function(d) {
                return $.extend({}, d, {
                    status:         status,
                    status_name:    jaStatusNameFilter,
                    location:       location,
                    jobs:           jobs,
                    questions:      questions,
                    question_value: question_value,
                    company:        company,
                    knockout:       knockout
                });
            }
        },
        language: languageOptions(),
        stripeClasses: [],
        dom: '<"jc-table-toolbar"lf>rt<"jc-table-toolbar jc-table-toolbar--footer"ip>',
        drawCallback: function() {
            if (typeof $.fn.tooltip === 'function') {
                $('[data-toggle="tooltip"]').tooltip();
            }
            document.querySelectorAll('.ja-row-chk').forEach(function(box) { if (jaSelectedIds.has(Number(box.dataset.id))) { box.classList.add('on'); box.innerHTML = '<i class="fa fa-check" style="font-size:10px;color:#fff"></i>'; } });
            jaUpdateAllChk();
            jaRebuildIds();
        },
        order: [[6, 'desc']],
        columns: [
            {
                data: null,
                orderable: false,
                searchable: false,
                width: '40px',
                render: function(data, type, row) {
                    return '<div class="ja-chk ja-row-chk" data-id="' + row.id + '" onclick="jaToggleRow(' + row.id + ', this)"></div>';
                }
            },
            { data: 'full_name',   name: 'full_name',  width: '29%' },
            { data: 'title',       name: 'job_id',     width: '19%' },
            { data: 'location_id', name: 'location_id', width: '18%' },
            {
                data: 'status',
                name: 'status_id',
                width: '12%',
                render: function(data, type, row) {
                    if (jaShowKO) {
                        return '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11.5px] font-semibold text-white" style="background:#dc2626;">'
                            + '<i class="fa fa-user-times" style="font-size:10px;margin-right:3px;"></i>Knockout'
                            + '</span>';
                    }
                    return data;
                }
            },
            { data: 'action',     name: 'action',     width: '16%', searchable: false, className: 'jc-td-right' },
            { data: 'created_at', name: 'created_at',  visible: false, searchable: false, orderable: true, defaultContent: '' }
        ]
    });
}

    function jaTableSyncFilterBadge() {
        var n = 0;
        if (($('#status').val()    || 'all') !== 'all') n++;
        if (($('#company').val()   || 'all') !== 'all') n++;
        if (($('#jobs').val()      || 'all') !== 'all') n++;
        if (($('#location').val()  || 'all') !== 'all') n++;
        if (($('#questions').val() || 'all') !== 'all') n++;
        var $b = $('#ja-table-filter-active-count');
        $b.text(n).toggleClass('show', n > 0);
    }

    // ── Filter toggle ────────────────────────────────────────────
    $('.toggle-filter').on('click', function() {
        var $bar = $('#ja-table-filter-bar');
        $bar.toggleClass('ja-filter-open');
        $('#toggle-filter').toggleClass('ja-filter-btn-active', $bar.hasClass('ja-filter-open'));
    });

    // ── Question value toggle ────────────────────────────────────
    $('#question_value').addClass('hidden');
    $('#questions').on('change', function() {
        if ($(this).val() === 'all') {
            $('#question_value').addClass('hidden');
            $('#question-value').val('');
        } else {
            $('#question_value').removeClass('hidden');
        }
    });

    // Auto-apply on any filter change (select or text input)
    var jaFilterDebounce;
    function jaApplyFilters() {
        clearTimeout(jaFilterDebounce);
        jaFilterDebounce = setTimeout(function() {
            jaSaveFilters();
            jaSelectedIds = new Set();
            jaRenderBulkBar();
            tableLoad('filter');
            jaTableSyncFilterBadge();
            jaLoadTabCounts();
        }, 250);
    }

    $('#filter-form').on('change', 'select', function() {
        if (this.id === 'status') jaStatusNameFilter = '';
        jaApplyFilters();
    });

    $('#question-value').on('input', function() {
        jaApplyFilters();
    });
    $('#reset-filters').on('click', function() {
        try { sessionStorage.removeItem(jaFilterStorageKey); } catch (e) {}
        window.location.href = '{{ route('admin.job-applications.table') }}';
    });

    // ── Show detail sidebar ──────────────────────────────────────
    // jaRebuildIds() is called here too so the counter is correct
    // even if somehow drawCallback hasn't fired yet.
    $('#myTable').off('click.jaProfile', '.show-detail').on('click.jaProfile', '.show-detail', function() {
        jaOpenCandidateProfile($(this).data('row-id'));
    });

    function jaOpenCandidateProfile(id, initialTab) {
        var $sidebar  = $('#right-sidebar');
        var $backdrop = $('#right-sidebar-backdrop');
        $sidebar.removeClass('translate-x-full').addClass('translate-x-0');
        $backdrop.removeClass('hidden').css({ display: 'block', visibility: 'visible' });

        // Ensure ID list is fresh before opening the panel
        jaRebuildIds();

        if (typeof window.jaDisposeApplicantProfile === 'function') window.jaDisposeApplicantProfile();
        var url = "{{ route('admin.job-applications.show',':id') }}".replace(':id', id);
        var requestId = (window._jaDirectProfileRequestId || 0) + 1;
        window._jaDirectProfileRequestId = requestId;
        if (window._jaDirectProfileXhr && window._jaDirectProfileXhr.readyState !== 4) {
            window._jaDirectProfileXhr.abort();
        }
        window._jaDirectProfileXhr = $.ajax({
            type: 'GET', url: url,
            success: function(response) {
                if (requestId === window._jaDirectProfileRequestId && response.status === 'success') {
                    $('#right-sidebar').removeClass('translate-x-full').addClass('translate-x-0');
                    $('#right-sidebar-backdrop').removeClass('hidden').css('display', 'block');
                    $('#right-sidebar-content').html(response.view);
                    if (window.initCandidateCalls) window.initCandidateCalls(document.getElementById('candidate-calls'));
                    if (initialTab) {
                        $(function () {
                            if (requestId !== window._jaDirectProfileRequestId) return;
                            var targetTab = document.querySelector('#right-sidebar-content .ja-tab[data-tab="' + initialTab + '"]');
                            if (targetTab) targetTab.click();
                        });
                    }
                }
            }
        });
    }

    var requestedReviewCandidate = new URLSearchParams(window.location.search).get('review_candidate');
    if (requestedReviewCandidate && /^[1-9]\d*$/.test(requestedReviewCandidate)) {
        jaOpenCandidateProfile(Number(requestedReviewCandidate), 'client-reviews');
    }

    // ── Delete / archive ─────────────────────────────────────────
    $('body').on('click', '.sa-params,.delete-document', function() {
        var id = $(this).data('row-id');
        var deleteDocClassPresent = $(this).hasClass('delete-document');
        var saParamsClassPresent  = $(this).hasClass('sa-params');

        swal({
            title: "@lang('errors.areYouSure')",
            text: "@lang('errors.deleteWarning')",
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#DD6B55',
            confirmButtonText: "@lang('app.delete')",
            cancelButtonText: "@lang('app.cancel')",
            closeOnConfirm: true,
            closeOnCancel: true
        }, function(isConfirm) {
            if (isConfirm) {
                var url = '';
                if (deleteDocClassPresent) url = "{{ route('admin.documents.destroy',':id') }}";
                if (saParamsClassPresent)  url = "{{ route('admin.job-applications.destroy',':id') }}";
                url = url.replace(':id', id);
                $.easyAjax({
                    type: 'POST', url: url,
                    data: { _token: "{{ csrf_token() }}", _method: 'DELETE' },
                    success: function(response) {
                        if (response.status === 'success') {
                            $.unblockUI();
                            if (deleteDocClassPresent && typeof docTable !== 'undefined') docTable.draw(false);
                            if (saParamsClassPresent  && typeof table      !== 'undefined') table.draw(false);
                            jaLoadTabCounts();
                        }
                    }
                });
            }
        });
    });

    // ── Export ───────────────────────────────────────────────────
    function exportJobApplication() {
        var startDate = 0, endDate = 0;
        var status   = $('#status').val();
        var jobs     = $('#jobs').val();
        var location = $('#location').val();
        var url = '{{ route('admin.job-applications.export', [':status',':location',':startDate',':endDate',':jobs']) }}';
        url = url.replace(':status', status).replace(':location', location)
                 .replace(':startDate', startDate).replace(':endDate', endDate)
                 .replace(':jobs', jobs);
        window.location.href = url;
    }

    // ── Create schedule ──────────────────────────────────────────
    function createSchedule(id) {
        var url = "{{ route('admin.job-applications.create-schedule',':id') }}".replace(':id', id);
        $('#modelHeading').html('Schedule');
        $('#job-application-modal-save').addClass('save-schedule');
        $.ajaxModal('#scheduleDetailModal', url);
    }

    // The schedule form is loaded into a modal after this page is ready, so
    // bind its Save action through the document instead of the modal fragment.
    $(document).off('click.jobApplicationSchedule', '.save-schedule')
        .on('click.jobApplicationSchedule', '.save-schedule', function (event) {
            event.preventDefault();
            $.easyAjax({
                url: '{{ route('admin.job-applications.store-schedule') }}',
                container: '#scheduleDetailModal',
                type: 'POST',
                data: $('#createSchedule').serialize(),
                redirect: false,
                disableButton: true,
                buttonSelector: '.save-schedule',
                success: function (response) {
                    if (response.status === 'success') window.location.reload();
                }
            });
        });

    // ── Mail settings ────────────────────────────────────────────
    $(document).on('click', '.mail_setting', function() {
        $.ajax({
            url: "{{ route('admin.application-setting.create') }}",
            success: function(data) {
                var data1 = eval(data.mail_setting);
                var options = '';
                $.each(data1, function(name, status) {
                    var checked = status.status == true ? 'checked' : '';
                    options += '<input type="checkbox" ' + checked + ' style="text-align:center;margin:6px 15px 13px 0;" name="checkBoardColumn[]" id="checkbox-' + name + '" value="' + name + '" />';
                    options += '<label for="checkbox-' + name + '" style="text-align:center;margin:6px 15px 13px 0;">' + status.name + '</label>';
                });
                $('#assetNameMenu').html(options);
                $('#legal_term').val(data.legal_term);
                $('#ModalLoginForm').removeClass('hidden');
            }
        });
        return false;
    });

    // ── Company → Location → Jobs cascade ────────────────────────

    function jaRefreshLocations(companyId, callback) {
        var saved = jaReadSavedFilters();
        var desiredLocation = String(saved.company || 'all') === String(companyId || 'all')
            ? String(saved.location || 'all')
            : 'all';
        $.ajax({
            url: "{{ route('admin.job-applications.get-locations') }}",
            type: 'GET',
            data: { companyId: companyId },
            dataType: 'json',
            success: function(data) {
                // Destroy Select2 if initialized
                try {
                    if ($('#location').data('select2')) {
                        $('#location').select2('destroy');
                    }
                } catch(e) {}

                // Update HTML
                $('#location').html(data.locations);

                // Re-init Select2
                $('#location').select2({ width: '100%' });

                // Keep the restored location only when it is valid for this
                // company; otherwise reset the dependent filter.
                if (!$('#location option').filter(function () { return String(this.value) === desiredLocation; }).length) {
                    desiredLocation = 'all';
                }
                $('#location').val(desiredLocation).trigger('change.select2');

                if (typeof callback === 'function') callback();
            },
            error: function(xhr) {
                console.error('jaRefreshLocations failed:', xhr.status, xhr.responseText);
                if (typeof callback === 'function') callback();
            }
        });
    }

    function jaRefreshJobs(companyId, locationId, callback) {
        var saved = jaReadSavedFilters();
        var sameCompany = String(saved.company || 'all') === String(companyId || 'all');
        var sameLocation = String(saved.location || 'all') === String(locationId || 'all');
        var desiredJob = sameCompany && sameLocation ? String(saved.jobs || 'all') : 'all';
        $.ajax({
            url: "{{ route('admin.job-applications.get-jobs') }}",
            type: 'GET',
            data: { companyId: companyId, locationId: locationId },
            dataType: 'json',
            success: function(data) {
                try {
                    if ($('#jobs').data('select2')) {
                        $('#jobs').select2('destroy');
                    }
                } catch(e) {}

                $('#jobs').html(data.jobs);
                $('#jobs').select2({ width: '100%' });
                // Do not lose the saved job when rebuilding its options for
                // the restored location. Invalid combinations fall back to All.
                if (!$('#jobs option').filter(function () { return String(this.value) === desiredJob; }).length) {
                    desiredJob = 'all';
                }
                $('#jobs').val(desiredJob).trigger(typeof callback === 'function' ? 'change.select2' : 'change');

                if (typeof callback === 'function') {
                    jaLoadJobStatuses(desiredJob, callback);
                }
            },
            error: function(xhr) {
                console.error('jaRefreshJobs failed:', xhr.status, xhr.responseText);
                if (typeof callback === 'function') callback();
            }
        });
    }

    // Company changes → filter locations, then filter jobs
    $('#company').on('change', function() {
        var company_id = $(this).val();
        jaRefreshLocations(company_id, function() {
            jaRefreshJobs(company_id, $('#location').val());
        });
    });

    // Location changes → filter jobs
    $('#location').on('change', function() {
        var company_id  = $('#company').val();
        var location_id = $(this).val();
        jaRefreshJobs(company_id, location_id);
    });
    // ── Select2 init ─────────────────────────────────────────────
    $('#filter-form select.select2').not('#skill').select2({ width: '100%' });

    // ── Init — no job selected, show all applicants ──────────────
    // Read status from URL
const params = new URLSearchParams(window.location.search);
const savedFilters = jaReadSavedFilters();
const statusFromUrl = params.get('status');
const jobFromUrl = params.get('jobs');

// Direct links from ATS Overview must override stale saved location/company
// filters so the selected job and only that job are loaded.
if (jobFromUrl) {
    savedFilters.company = 'all';
    savedFilters.location = 'all';
    savedFilters.jobs = jobFromUrl;
    savedFilters.questions = 'all';
    savedFilters.question_value = '';
    savedFilters.knockout = false;
    savedFilters.status = statusFromUrl || 'all';
}
const restoredStatus = statusFromUrl || savedFilters.status || 'all';

function jaRestoreSelect(selector, value) {
    var wanted = value == null ? 'all' : String(value);
    var $select = $(selector);
    $select.find('option').each(function() {
        if (String(this.value) === wanted) {
            $select.val(wanted).trigger('change.select2');
            return false;
        }
    });
}

jaRestoreSelect('#company', savedFilters.company);
jaRestoreSelect('#location', savedFilters.location);
jaRestoreSelect('#jobs', savedFilters.jobs);
jaRestoreSelect('#questions', savedFilters.questions);
$('#question-value').val(savedFilters.question_value || '');
if (($('#questions').val() || 'all') !== 'all') {
    $('#question_value').removeClass('hidden');
}

jaShowKO = !statusFromUrl && savedFilters.knockout === true;
jaActiveStageId = restoredStatus;
jaRestoreSelect('#status', restoredStatus);

if (jaShowKO) {
    document.getElementById('ja-ko-tab-btn').classList.add('active');
    document.getElementById('ja-ko-banner').classList.add('show');
} else if (restoredStatus === 'all') {
    document.getElementById('ja-tab-all').classList.add('active');
} else {
    setTimeout(function () {
        var btn = document.querySelector('[data-stage-id="' + restoredStatus + '"]');
        if (btn) btn.classList.add('active');
    }, 300);
}

function jaFinishInitialFilterLoad() {
    tableLoad('load');
    jaLoadTabCounts();
    jaTableSyncFilterBadge();
}

// Rebuild dependent dropdown options on restoration, exactly as if the user
// had selected Company → Location → Job manually. This removes unrelated jobs
// from the Jobs dropdown instead of merely restoring its selected value.
if ((savedFilters.company || 'all') !== 'all') {
    jaRefreshLocations(savedFilters.company, function () {
        jaRefreshJobs(savedFilters.company, $('#location').val(), jaFinishInitialFilterLoad);
    });
} else if ((savedFilters.location || 'all') !== 'all') {
    jaRefreshJobs('all', savedFilters.location, jaFinishInitialFilterLoad);
} else if (jobFromUrl) {
    jaLoadJobStatuses(jobFromUrl, jaFinishInitialFilterLoad);
} else {
    jaFinishInitialFilterLoad();
}
 


    // ── Draggable stage tab reordering ──────────────────────────
    // currentJobId tracks which job's order is active so each job
    // gets its own localStorage key (ja-tab-order-{jobId}).
    window.jaCurrentJobId = 'global';

    (function () {
        var dragSrc = null;

        function lsKey() { return 'ja-tab-order-' + window.jaCurrentJobId; }

        function storedOrder() {
            try { return JSON.parse(localStorage.getItem(lsKey())) || null; }
            catch (e) { return null; }
        }

        window.jaPersistOrder = function () {
            var row = document.getElementById('ja-stage-tabs-row');
            if (!row) return;
            var ids = [];
            row.querySelectorAll('.ja-stage-tab[draggable="true"]').forEach(function (el) {
                ids.push(parseInt(el.getAttribute('data-stage-id'), 10));
            });
            localStorage.setItem(lsKey(), JSON.stringify(ids));
            var reordered = [];
            ids.forEach(function (id) {
                var s = jaStages.find(function (x) { return x.id === id; });
                if (s) reordered.push(s);
            });
            jaStages.forEach(function (s) {
                if (ids.indexOf(s.id) === -1) reordered.push(s);
            });
            jaStages = reordered;
        };

        // Called after jaRebuildStageTabs to apply the saved order for the current job
        window.jaApplyStoredOrder = function () {
            var saved = storedOrder();
            if (!saved || !saved.length) return;
            var wrap = document.getElementById('ja-stage-tabs-draggable');
            if (!wrap) return;
            var tabMap = {};
            wrap.querySelectorAll('.ja-stage-tab[draggable="true"]').forEach(function (el) {
                tabMap[parseInt(el.getAttribute('data-stage-id'), 10)] = el;
            });
            // Only apply if every saved id still exists (guard against status deletions)
            var allPresent = saved.every(function (id) { return !!tabMap[id]; });
            if (!allPresent) return;
            saved.forEach(function (id) { wrap.appendChild(tabMap[id]); });
            var reordered = [];
            saved.forEach(function (id) {
                var s = jaStages.find(function (x) { return x.id === id; });
                if (s) reordered.push(s);
            });
            jaStages.forEach(function (s) {
                if (saved.indexOf(s.id) === -1) reordered.push(s);
            });
            jaStages = reordered;
        };

        function initDragTabs() {
            // Attach to the real flex row div — display:contents span has no hit-test area
            var row = document.getElementById('ja-stage-tabs-row');
            if (!row) return;

            row.addEventListener('dragstart', function (e) {
                var tab = e.target.closest('.ja-stage-tab[draggable="true"]');
                if (!tab) return;
                dragSrc = tab;
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', tab.getAttribute('data-stage-id'));
                // Delay adding class so ghost image captures normal state
                setTimeout(function () { tab.classList.add('dragging'); }, 0);
            });

            row.addEventListener('dragend', function () {
                row.querySelectorAll('.ja-stage-tab').forEach(function (t) {
                    t.classList.remove('dragging', 'drag-over');
                });
                dragSrc = null;
            });

            row.addEventListener('dragover', function (e) {
                if (!dragSrc) return;
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                var tab = e.target.closest('.ja-stage-tab[draggable="true"]');
                row.querySelectorAll('.ja-stage-tab').forEach(function (t) {
                    t.classList.remove('drag-over');
                });
                if (tab && tab !== dragSrc) tab.classList.add('drag-over');
            });

            row.addEventListener('drop', function (e) {
                e.preventDefault();
                if (!dragSrc) return;
                var target = e.target.closest('.ja-stage-tab[draggable="true"]');
                row.querySelectorAll('.ja-stage-tab').forEach(function (t) {
                    t.classList.remove('drag-over');
                });
                if (!target || target === dragSrc) return;
                var rect  = target.getBoundingClientRect();
                var after = e.clientX > rect.left + rect.width / 2;
                if (after) { target.after(dragSrc); } else { target.before(dragSrc); }
                window.jaPersistOrder();
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initDragTabs);
        } else {
            initDragTabs();
        }
    })();

    // ── Build/rebuild pipeline stage tab buttons ─────────────────
    function jaRebuildStageTabs(statuses) {
        var wrap = document.getElementById('ja-stage-tabs-draggable');
        if (!wrap) return;

        // Clear existing dynamic tabs
        wrap.innerHTML = '';

        // Update global jaStages
        jaStages = statuses.map(function (s) {
            return { id: s.id, slug: s.slug, label: s.label, color: s.color || '#2563eb' };
        });

        if (!statuses.length) return;

        statuses.forEach(function (s) {
            var btn = document.createElement('button');
            btn.className = 'ja-stage-tab';
            btn.setAttribute('draggable', 'true');
            btn.setAttribute('data-stage-id', s.id);
            btn.setAttribute('data-stage-color', s.color || '#2563eb');
            btn.setAttribute('data-stage-slug', s.slug);
            btn.style.setProperty('--tab-color', s.color || '#2563eb');
            btn.innerHTML = s.label
                + ' <span class="ja-stage-badge" id="ja-tab-count-' + s.id + '">0</span>';
            btn.addEventListener('click', function () {
                jaStageTab(s.id, s.color || '#2563eb', s.slug);
            });
            wrap.appendChild(btn);
        });

        // Restore the saved drag order for this job (must run after tabs are in DOM)
        if (typeof window.jaApplyStoredOrder === 'function') {
            window.jaApplyStoredOrder();
        }

        jaLoadTabCounts();
    }

    // ── Load job statuses → rebuild both status dropdown + tab bar ─
    function jaLoadJobStatuses(jobId, callback) {
        if (!jobId || jobId === 'all' || parseInt(jobId, 10) === 0) {
            // No job selected: clear stage tabs, reset status dropdown to global
            jaRebuildStageTabs([]);
            jaStageTabAll();
            // Restore default status options
            var defaultHtml = '<option value="all">@lang("modules.jobApplication.allStatus")</option>';
            @foreach($boardColumns as $col)
            defaultHtml += '<option value="{{ $col->id }}">{{ ucfirst($col->status) }}</option>';
            @endforeach
            $('#status').select2('destroy').html(defaultHtml).select2({ width: '100%' });
            if (typeof callback === 'function') callback();
            return;
        }

        // Set the current job id so localStorage key is per-job
        window.jaCurrentJobId = parseInt(jobId, 10);

        $.ajax({
            url: '{{ route("admin.job-applications.job-statuses") }}',
            type: 'GET',
            data: { job_id: window.jaCurrentJobId },
            success: function (res) {
                var payload  = res.data ? res.data : res;
                var statuses = payload.statuses || [];

                // Rebuild status filter dropdown
                var html = '<option value="all">@lang("modules.jobApplication.allStatus")</option>';
                statuses.forEach(function (s) {
                    html += '<option value="' + s.id + '">' + s.label + '</option>';
                });
                $('#status').select2('destroy').html(html).select2({ width: '100%' });

                // Rebuild stage tab bar (jaApplyStoredOrder is called inside)
                jaRebuildStageTabs(statuses);

                if (typeof callback === 'function') {
                    var saved = jaReadSavedFilters();
                    var desiredStatus = String(saved.status || 'all');
                    var hasDesiredStatus = statuses.some(function (status) {
                        return String(status.id) === desiredStatus;
                    });
                    if (!hasDesiredStatus) desiredStatus = 'all';
                    $('#status').val(desiredStatus).trigger('change.select2');
                    document.querySelectorAll('.ja-stage-tab').forEach(function (tab) { tab.classList.remove('active'); });
                    var restoredTab = desiredStatus === 'all'
                        ? document.getElementById('ja-tab-all')
                        : document.querySelector('[data-stage-id="' + desiredStatus + '"]');
                    if (restoredTab) restoredTab.classList.add('active');
                    callback();
                    return;
                }

                // Auto-activate first tab respecting the saved order
                var firstTab = document.querySelector('#ja-stage-tabs-draggable .ja-stage-tab[draggable="true"]');
                if (firstTab) {
                    jaStageTab(
                        parseInt(firstTab.getAttribute('data-stage-id'), 10),
                        firstTab.getAttribute('data-stage-color') || '#2563eb',
                        firstTab.getAttribute('data-stage-slug')
                    );
                }
            },
            error: function () {
                if (typeof callback === 'function') callback();
            }
        });
    }

    // Use event delegation so the handler survives Select2 destroy/recreate (company filter)
    $(document).on('change', '#jobs', function () {
        jaLoadJobStatuses($(this).val());
    });

    // ── Auto-open applicant sidebar from notification link ──
    (function () {
        var params = new URLSearchParams(window.location.search);
        var openId = params.get('open');
        var openTab = params.get('tab') || 'notes';
        if (!openId) return;

        function tryOpenSidebar() {
            var url = "{{ route('admin.job-applications.show', ':id') }}".replace(':id', openId);
            var $sidebar  = $('#right-sidebar');
            var $backdrop = $('#right-sidebar-backdrop');

            $sidebar.removeClass('translate-x-full').addClass('translate-x-0');
            $backdrop.removeClass('hidden').css({ display: 'block', visibility: 'visible' });

            $.easyAjax({
                type: 'GET', url: url,
                success: function (response) {
                    if (response.status === 'success') {
                        $('#right-sidebar-content').html(response.view);
                        if (window.initCandidateCalls) window.initCandidateCalls(document.getElementById('candidate-calls'));
                        // Notification links can open Notes or SMS directly.
                        setTimeout(function () {
                            var requestedTab = document.querySelector('.ja-tab[data-tab="' + openTab + '"]');
                            if (requestedTab) requestedTab.click();
                        }, 400);

                        // Highlight the row if visible
                        var $chk = $('.ja-row-chk[data-id="' + openId + '"]');
                        if ($chk.length) {
                            var $tr = $chk.closest('tr');
                            $tr.css('background', '#EFF6FF');
                            setTimeout(function () { $tr.css('background', ''); }, 2500);
                            $tr[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                    }
                }
            });
        }

        // Hook into DataTable's draw event — fires reliably after data loads
        // Also set a fallback timeout in case table variable isn't ready yet
        var hooked = false;
        var fallbackTimer = setTimeout(function () {
            if (!hooked) tryOpenSidebar();
        }, 2500);

        // Poll until `table` variable is ready, then hook draw event
        var pollTimer = setInterval(function () {
            if (typeof table !== 'undefined' && table) {
                clearInterval(pollTimer);
                hooked = true;
                clearTimeout(fallbackTimer);

                // If table already drew, open immediately
                // Otherwise wait for first draw
                var drawn = false;
                table.on('draw', function () {
                    if (!drawn) {
                        drawn = true;
                        setTimeout(tryOpenSidebar, 200);
                    }
                });

                // Safety: if draw already fired before we hooked
                setTimeout(function () {
                    if (!drawn) {
                        drawn = true;
                        tryOpenSidebar();
                    }
                }, 1500);
            }
        }, 100);

    })();
    </script>
@endpush
