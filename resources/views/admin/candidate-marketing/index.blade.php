@extends('layouts.app')

@push('head-script')
    @include('admin.job-applications.partials.select2-filter-skin')
    @include('admin.job-applications.partials.application-list-style')
    <style>
        .cm-list { font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif; }
        .cm-list .cm-label-filter { width: 100%; height: 38px; border: 1px solid #d6dce5; border-radius: 8px; padding: 0 12px; font-size: 13px; }
        .cm-list .ja-row-actions { display: flex; justify-content: flex-end; }
        .cm-list .ja-act-btn { display: inline-flex; align-items: center; border: 1px solid var(--ja-line); background: #fff; color: #475569; }
        .cm-list .ja-act-btn:hover { background: #eff6ff; color: var(--ja-blue); }
        .cm-list .ja-act-btn.reject:hover { background: #fef2f2; color: #b91c1c; }
    </style>
@endpush

@section('content')
@include('admin.partials.candidate-communications')
<div class="ja-board-scope ja-applications cm-list">
    <div class="ja-app-toolbar">
        <div class="ja-app-search">
            <i class="fa fa-search" aria-hidden="true"></i>
            <input type="search" id="cm-search" aria-label="{{ __('Search candidates') }}" placeholder="{{ __('Search candidates') }}" autocomplete="off">
        </div>
    </div>

    <div id="ja-table-filter-bar" class="ja-filters">
        <div class="ja-filter-form" role="group" aria-label="{{ __('Candidate filters') }}">
            <div class="ja-filter-field">
                <select class="select2 w-full" id="cm-filter-company" aria-label="{{ __('Company') }}">
                    <option value="all">{{ __('Company') }}</option>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}">{{ ucwords($company->company_name) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="ja-filter-field">
                <select class="select2 w-full" id="cm-filter-jobs" aria-label="{{ __('Job') }}">
                    <option value="all">{{ __('Job') }}</option>
                    @foreach($jobs as $job)
                        <option value="{{ $job->id }}">{{ ucfirst($job->title) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="ja-filter-field">
                <input type="search" class="cm-label-filter" id="cm-filter-label"
                    aria-label="{{ __('Marketing label') }}" placeholder="{{ __('Marketing label') }}">
            </div>
            <button type="button" id="reset-filters" class="inline-flex h-[38px] shrink-0 items-center gap-2 px-4 text-[13px] font-semibold text-slate-600 hover:text-blue-700 focus-visible:ring-2 focus-visible:ring-blue-500">
                <i class="fa fa-refresh" aria-hidden="true"></i> @lang('app.reset')
            </button>
        </div>
    </div>

    <div class="ja-results ra-dt-wrap">
        <table id="myTable" class="jc-cat-table display w-full">
            <thead>
                <tr>
                    <th><input type="checkbox" class="cc-all" aria-label="{{ __('Select all candidates on this page') }}"></th>
                    <th>{{ __('Candidate name') }}</th>
                    <th>{{ __('Applied for') }}</th>
                    <th>{{ __('Marketing label') }}</th>
                    <th>@lang('app.status')</th>
                    <th>{{ __('Added on') }}</th>
                    <th>{{ __('Actions') }}</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
@endsection

@push('footer-script')
<script src="{{ asset('assets/node_modules_files/select2/dist/js/select2.full.min.js') }}"></script>
<script>
$(function () {
    $('#ja-table-filter-bar .select2').select2({width: '100%'});

    var cmTable = $('#myTable').DataTable({
        processing: false,
        serverSide: true,
        autoWidth: false,
        stripeClasses: [],
        dom: '<"ja-table-overflow"rt><"ja-table-footer"lip>',
        language: $.extend(true, languageOptions(), {
            lengthMenu: @json(__('Rows per page')) + ' _MENU_',
            paginate: {previous: '‹', next: '›'},
            aria: {paginate: {previous: @json(__('Previous page')), next: @json(__('Next page'))}}
        }),
        infoCallback: function (settings, start, end, max, total) {
            return total ? start + '–' + end + ' ' + @json(__('of')) + ' ' + total : @json(__('0 results'));
        },
        ajax: {
            url: "{{ route('admin.candidate-marketing.data') }}",
            data: function (d) {
                d.jobs = $('#cm-filter-jobs').val();
                d.company = $('#cm-filter-company').val();
                d.search_label = $('#cm-filter-label').val();
            }
        },
        columns: [
            {
                data: null,
                width: '40px',
                orderable: false,
                searchable: false,
                render: function (data, type, row, meta) {
                    return '<input type="checkbox" class="cc-select" data-type="application" value="' + Number(row.id) + '" aria-label="Select candidate">';
                }
            },
            { data: 'full_name', name: 'full_name' },
            { data: 'title', name: 'job.title' },
            { data: 'marketing_label', name: 'marketing_label' },
            { data: 'status', name: 'status.status' },
            { data: 'marketing_added_at', name: 'marketing_added_at' },
            { data: 'action', orderable: false, searchable: false },
        ]
    });

    $('#cm-filter-jobs, #cm-filter-company').on('change', function () { cmTable.draw(); });
    var searchTimer;
    $('#cm-search, #cm-filter-label').on('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () {
            cmTable.search($('#cm-search').val()).draw();
        }, 250);
    });
    $('#reset-filters').on('click', function () {
        clearTimeout(searchTimer);
        $('#cm-filter-jobs, #cm-filter-company').val('all').trigger('change.select2');
        $('#cm-search, #cm-filter-label').val('');
        cmTable.search('').draw();
    });

    // Reuse the same right-sidebar applicant profile panel that
    // job-applications/index.blade.php uses (#right-sidebar / .show-detail).
    $('#myTable').on('click', '.show-detail', function () {
        var $sidebar  = $('#right-sidebar');
        var $backdrop = $('#right-sidebar-backdrop');
        $sidebar.removeClass('translate-x-full').addClass('translate-x-0');
        $backdrop.removeClass('hidden');

        var id  = $(this).data('row-id');
        var url = "{{ route('admin.job-applications.show', ':id') }}".replace(':id', id);

        $.easyAjax({
            type: 'GET', url: url,
            success: function (response) {
                if (response.status === 'success') {
                    $sidebar.removeClass('translate-x-full').addClass('translate-x-0');
                    $backdrop.removeClass('hidden').css('display', 'block');
                    $('#right-sidebar-content').html(response.view);
                }
            }
        });
    });

    window.cmRemoveFromMarketing = function (id) {
        swal({
            title: "@lang('errors.areYouSure')",
            text: "Remove this applicant from Candidate Marketing?",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Remove",
            cancelButtonText: "@lang('app.cancel')",
            closeOnConfirm: true,
            closeOnCancel: true
        }, function (isConfirm) {
            if (isConfirm) {
                $.easyAjax({
                    type: 'POST',
                    url: "{{ route('admin.candidate-marketing.remove', ':id') }}".replace(':id', id),
                    data: { _token: '{{ csrf_token() }}' },
                    success: function (response) {
                        if (response.status === 'success') cmTable.draw(false);
                    }
                });
            }
        });
    };
});
</script>
@endpush
