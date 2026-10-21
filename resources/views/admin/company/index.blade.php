@extends('layouts.app')

@push('head-script')
    <style>
        .company-filter-tile { transition: transform 0.2s, box-shadow 0.2s; }
        .company-filter-tile:hover .company-filter-tile-inner { transform: translateY(-2px); }
        .company-filter-tile--active .company-filter-tile-inner { box-shadow: 0 0 0 2px #2563EB, 0 0 0 4px #F8F7F4; }
        .company-filter-tile--active.dark .company-filter-tile-inner { box-shadow: 0 0 0 2px rgba(255,255,255,0.85), 0 0 0 4px #F8F7F4; }
    </style>
@endpush

@section('page-title-html')
    <span class="text-[22px] font-bold tracking-tight text-[#1A1E2E]">@lang('menu.companies')</span>
@endsection

@section('page-subtitle')
    @lang('modules.accountSettings.companyLogo') · @lang('modules.accountSettings.companyEmail') · @lang('app.status') — @lang('modules.dashboard.totalCompanies')
@endsection

@if(in_array('add_company', $userPermissions))
@section('create-button')
    <a href="{{ route('admin.company.create') }}"
       class="inline-flex items-center gap-2 rounded-xl bg-[#2563EB] px-5 py-2.5 text-[13px] font-semibold text-white shadow-sm transition hover:bg-[#1d4ed8] focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
        </svg>
        @lang('app.createNew')
    </a>
@endsection
@endif

@section('content')
<div class="mx-auto  pb-8">
    @if(session('company_created'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700" role="status">
            {{ session('company_created') }}
        </div>
    @endif
    <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
        <a href="javascript:;" class="changeSatus company-filter-tile company-filter-tile--active block rounded-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-[#2563EB] focus-visible:ring-offset-2" data-status="" aria-pressed="true">
            <div class="company-filter-tile-inner dark relative cursor-pointer overflow-hidden rounded-xl bg-[#0F1F3D] px-3.5 py-3 text-white shadow-sm transition">
                <div class="pointer-events-none absolute -right-3 -top-3 h-12 w-12 rounded-full bg-white opacity-[0.07]"></div>
                <div class="flex items-center justify-between">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-white/10">
                        <i class="icon-badge text-base text-white/80"></i>
                    </div>
                    <span class="rounded-full bg-white/[0.08] px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide text-white/55">@lang('app.viewAll')</span>
                </div>
                <p class="mt-1.5 text-[22px] font-extrabold leading-none tracking-tight">{{ number_format($totalCompanies) }}</p>
                <p class="mt-0.5 text-[10px] font-semibold uppercase tracking-wide text-white/55">@lang('modules.dashboard.totalCompanies')</p>
            </div>
        </a>
        <a href="javascript:;" class="changeSatus company-filter-tile block rounded-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-[#2563EB] focus-visible:ring-offset-2" data-status="active" aria-pressed="false">
            <div class="company-filter-tile-inner relative cursor-pointer overflow-hidden rounded-xl bg-[#059669] px-3.5 py-3 text-white shadow-sm transition">
                <div class="pointer-events-none absolute -right-3 -top-3 h-12 w-12 rounded-full bg-white opacity-[0.07]"></div>
                <div class="flex items-center justify-between">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-white/10">
                        <svg class="h-4 w-4" fill="none" stroke="rgba(255,255,255,0.75)" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <span class="rounded-full bg-white/[0.14] px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide text-white/55">@lang('app.active')</span>
                </div>
                <p class="mt-1.5 text-[22px] font-extrabold leading-none tracking-tight">{{ number_format($activeCompanies) }}</p>
                <p class="mt-0.5 text-[10px] font-semibold uppercase tracking-wide text-white/55">@lang('modules.dashboard.activeCompanies')</p>
            </div>
        </a>
        <a href="javascript:;" class="changeSatus company-filter-tile block rounded-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-[#2563EB] focus-visible:ring-offset-2" data-status="inactive" aria-pressed="false">
            <div class="company-filter-tile-inner relative cursor-pointer overflow-hidden rounded-xl border border-[#F0EEE9] bg-white px-3.5 py-3 text-[#1A1E2E] shadow-sm transition">
                <div class="pointer-events-none absolute -right-3 -top-3 h-12 w-12 rounded-full bg-[#EF4444] opacity-[0.07]"></div>
                <div class="flex items-center justify-between">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#FFF1F2]">
                        <svg class="h-4 w-4" fill="none" stroke="#EF4444" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <span class="rounded-full bg-[#FFF1F2] px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide text-[#EF4444]">@lang('app.inactive')</span>
                </div>
                <p class="mt-1.5 text-[22px] font-extrabold leading-none tracking-tight">{{ number_format($inactiveCompanies) }}</p>
                <p class="mt-0.5 text-[10px] font-semibold uppercase tracking-wide text-[#8892A0]">@lang('modules.dashboard.inactiveCompanies')</p>
            </div>
        </a>
    </div>

    <div class="jc-table-card ra-dt-wrap overflow-hidden">
        <table id="myTable" class="jc-cat-table display w-full" style="width:100%">
            <thead>
            <tr>
                <th style="width:72px;">#</th>
                <th>@lang('modules.accountSettings.companyLogo')</th>
                <th>@lang('menu.companies')</th>
                <th>@lang('modules.accountSettings.companyEmail')</th>
                <th>@lang('app.status')</th>
                <th class="jc-th-right" style="padding-right:20px;">@lang('app.action')</th>
            </tr>
            </thead>
        </table>
    </div>
</div>
@endsection

@push('footer-script')
<script>
    var status = '';

    function syncCompanyFilterTiles() {
        var $tiles = $('.company-filter-tile');
        $tiles.removeClass('company-filter-tile--active dark').attr('aria-pressed', 'false');
        $tiles.each(function () {
            var s = $(this).attr('data-status');
            if (s === undefined || s === null) {
                s = '';
            }
            var match = (s === status) || (s === '' && status === '');
            if (!match) {
                return;
            }
            var isDark = (status === '' || status === 'active');
            $(this).addClass('company-filter-tile--active' + (isDark ? ' dark' : '')).attr('aria-pressed', 'true');
        });
    }

    var table = $('#myTable').DataTable({
        responsive: false,
        serverSide: true,
        ajax: {
            url: '{!! route('admin.company.data') !!}',
            data: function (d) {
                return $.extend({}, d, { filter_status: status });
            }
        },
        language: languageOptions(),
        stripeClasses: [],
        dom: '<"jc-table-toolbar"lf>rt<"jc-table-toolbar jc-table-toolbar--footer"ip>',
        columns: [
            { data: 'DT_Row_Index', name: 'DT_Row_Index', orderable: false, searchable: false },
            { data: 'logo', name: 'logo' },
            { data: 'company_name', name: 'company_name' },
            { data: 'company_email', name: 'company_email' },
            { data: 'status', name: 'status' },
            { data: 'action', name: 'action', width: '20%', className: 'jc-td-right' }
        ]
    });

    syncCompanyFilterTiles();

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
                var url = "{{ route('admin.company.destroy', ':id') }}";
                url = url.replace(':id', id);
                var token = "{{ csrf_token() }}";
                $.easyAjax({
                    type: 'POST',
                    url: url,
                    data: { '_token': token, '_method': 'DELETE' },
                    success: function (response) {
                        if (response.status == "success") {
                            $.unblockUI();
                            table.draw(false);
                        }
                    }
                });
            }
        });
    });

    $('body').on('click', '.changeSatus', function () {
        status = $(this).data('status');
        if (typeof status === 'undefined') {
            status = '';
        }
        syncCompanyFilterTiles();
        table.draw(false);
    });
</script>
@endpush
