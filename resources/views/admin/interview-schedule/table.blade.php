@extends('layouts.app')

@push('head-script')
    <link rel="stylesheet" href="{{ asset('assets/node_modules_files/select2/dist/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/node_modules_files/bootstrap-material-datetimepicker/css/bootstrap-material-datetimepicker.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/node_modules_files/bootstrap-datepicker/bootstrap-datepicker.min.css') }}">

    <style>
        .datepicker {
            z-index: 9999 !important;
        }

        #interview-table-page .select2-container--default .select2-selection--single,
        #interview-table-page .select2-selection .select2-selection--single {
            width: 100%;
        }

        #interview-table-page .select2-search {
            width: 100%;
        }

        #interview-table-page .select2.select2-container {
            width: 100% !important;
        }

        #interview-table-page .select2-search__field {
            width: 100% !important;
            display: block;
            padding: .375rem .75rem !important;
            font-size: 1rem;
            line-height: 1.5;
            color: #495057;
            background-color: #fff;
            background-clip: padding-box;
            border: 1px solid #ced4da;
            transition: border-color .15s ease-in-out,box-shadow .15s ease-in-out;
        }

        #interview-table-page #ticket-filters {
            display: none;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #f8fafc;
        }

        /* Keep native checkbox clickable even if global styles interfere */
        #interview-table-page #example-select-all,
        #interview-table-page .schedule-row-checkbox {
            appearance: auto !important;
            -webkit-appearance: checkbox !important;
            opacity: 1 !important;
            pointer-events: auto !important;
            cursor: pointer;
            width: 16px;
            height: 16px;
            position: relative;
            z-index: 2;
        }
    </style>
@endpush

@section('page-title-html')
    <span class="text-[22px] font-bold tracking-tight text-[#1A1E2E]">@lang('menu.interviewSchedule')</span>
@endsection

@section('page-subtitle')
    @lang('modules.interviewSchedule.tableView')
@endsection

@section('create-button')
    <div class="flex items-center gap-2">
        <a href="javascript:;" id="toggle-filter"
           class="toggle-filter inline-flex items-center gap-2 rounded-xl border border-[#DC2626] bg-white px-4 py-2.5 text-[13px] font-semibold text-[#DC2626] shadow-sm transition hover:bg-[#FEF2F2] focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
            <i class="fa fa-sliders"></i> @lang('app.filterResults')
        </a>
        <a href="{{ route('admin.interview-schedule.index') }}"
           class="inline-flex items-center gap-2 rounded-xl border border-[#16A34A] bg-white px-4 py-2.5 text-[13px] font-semibold text-[#15803d] shadow-sm transition hover:bg-[#f0fdf4] focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
            <i class="fa fa-calendar"></i> @lang('modules.interviewSchedule.calendarView')
        </a>
    </div>
@endsection

@section('content')
    @include('admin.interview-schedule.google-calendar-connection')
    <div id="interview-table-page" class="mx-auto pb-8">
        <div class="jc-table-card ra-dt-wrap overflow-hidden p-6">
            <div class="mb-5 flex justify-end">
                <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center sm:justify-end">
                    <label class="text-xs font-semibold uppercase tracking-wider text-slate-500 sm:mr-1">@lang('app.status')</label>
                    <select name="statusMultiple" id="statusMultiple" class="h-11 min-w-[250px] rounded-xl border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                        <option value="selectStatus">@lang('modules.interviewSchedule.selectStatus')</option>
                        <option value="rejected">@lang('app.rejected')</option>
                        <option value="hired">@lang('app.hired')</option>
                        <option value="pending">@lang('app.pending')</option>
                        <option value="canceled">@lang('app.canceled')</option>
                    </select>
                    <button type="button" id="changeMultipleStatus"
                            class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-[#16A34A] px-5 text-[13px] font-semibold text-white shadow-sm transition hover:bg-[#15803d] focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                        <i class="fa fa-check"></i>
                        @lang('app.apply')
                    </button>
                </div>
            </div>

            <div class="mb-5 p-4" id="ticket-filters">
                <div class="mb-4 flex items-center justify-between">
                    <h4 class="m-0 text-sm font-semibold text-slate-700">@lang('app.filterBy')</h4>
                    <a href="javascript:;" class="toggle-filter text-slate-400 transition hover:text-slate-700"><i class="fa fa-times-circle-o"></i></a>
                </div>

                <form action="" class="row" id="filter-form">
                    <div class="col-md-4">
                        <div class="example">
                            <div class="input-daterange input-group" id="date-range">
                                <input type="text" class="form-control" id="start-date" placeholder="Show Results From" value="" />
                                <span class="input-group-addon bg-info b-0 text-white p-1">@lang('app.to')</span>
                                <input type="text" class="form-control" id="end-date" placeholder="Show Results To" value="" />
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <select class="form-control select2" name="applicationID" id="applicationID" data-style="form-control">
                                <option value="all">@lang('modules.interviewSchedule.allCandidates')</option>
                                @forelse($candidates as $candidate)
                                    <option value="{{$candidate->id}}">{{ ucfirst($candidate->full_name) }}</option>
                                @empty
                                @endforelse
                            </select>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <select class="form-control select2" name="status" id="status" data-style="form-control">
                                <option value="all">@lang('modules.interviewSchedule.allStatus')</option>
                                <option value="pending">@lang('app.pending')</option>
                                <option value="rejected">@lang('app.rejected')</option>
                                <option value="hired">@lang('app.hired')</option>
                                <option value="canceled">@lang('app.canceled')</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group flex items-center gap-2">
                            <button type="button" id="apply-filters" class="btn btn-sm btn-success"><i class="fa fa-check"></i> @lang('app.apply')</button>
                            <button type="button" id="reset-filters" class="btn btn-sm btn-dark "><i class="fa fa-refresh"></i> @lang('app.reset')</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table id="myTable" class="jc-cat-table display w-full min-w-full" style="width:100%">
                    <thead>
                    <tr>
                        <th style="width:48px;"><input name="select_all" value="1" id="example-select-all" type="checkbox" /></th>
                        <th>@lang('app.candidate')</th>
                        <th>@lang('menu.interviewDate')</th>
                        <th>@lang('menu.status')</th>
                        <th class="jc-th-right">@lang('app.action')</th>
                    </tr>
                    </thead>
                </table>
            </div>
                </div>
    </div>
    {{-- Detail modal --}}
    <div class="hidden fixed inset-0 z-[200] overflow-y-auto" id="scheduleDetailModal" role="dialog" aria-modal="true" aria-labelledby="scheduleDetailModalHeading">
        <div class="flex min-h-full items-center justify-center px-4 py-8">
            <div class="fixed inset-0 bg-[#0F1F3D]/50 backdrop-blur-[3px]" data-int-modal-close="detail" aria-hidden="true"></div>
            <div class="relative flex max-h-[min(90vh,880px)] w-full max-w-4xl flex-col overflow-hidden rounded-[20px] border border-[#E8E6E1] bg-white shadow-xl">
                <div class="flex items-center justify-between border-b border-[#F0EEE9] px-6 py-4">
                    <h3 id="scheduleDetailModalHeading" class="text-[16px] font-bold tracking-[-0.01em] text-[#1A1E2E]"></h3>
                    <button type="button" class="flex h-8 w-8 items-center justify-center rounded-lg text-[#8892A0] transition hover:bg-gray-100" data-int-modal-close="detail" aria-label="@lang('app.close')">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="min-h-0 flex-1 overflow-y-auto p-6" id="scheduleDetailModalBody">
                    <p class="text-[13px] text-[#8892A0]">Loading...</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Edit modal --}}
    <div class="hidden fixed inset-0 z-[200] overflow-y-auto" id="scheduleEditModal" role="dialog" aria-modal="true" aria-labelledby="scheduleEditModalHeading">
        <div class="flex min-h-full items-center justify-center px-4 py-8">
            <div class="fixed inset-0 bg-[#0F1F3D]/50 backdrop-blur-[3px]" data-int-modal-close="edit" aria-hidden="true"></div>
            <div class="relative flex max-h-[min(90vh,880px)] w-full max-w-4xl flex-col overflow-hidden rounded-[20px] border border-[#E8E6E1] bg-white shadow-xl">
                <div class="flex items-center justify-between border-b border-[#F0EEE9] px-6 py-4">
                    <h3 id="scheduleEditModalHeading" class="text-[16px] font-bold tracking-[-0.01em] text-[#1A1E2E]"></h3>
                    <button type="button" class="flex h-8 w-8 items-center justify-center rounded-lg text-[#8892A0] transition hover:bg-gray-100" data-int-modal-close="edit" aria-label="@lang('app.close')">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="min-h-0 flex-1 overflow-y-auto p-6" id="scheduleEditModalBody">
                    <p class="text-[13px] text-[#8892A0]">Loading...</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('footer-script')
    <script src="{{ asset('assets/node_modules_files/select2/dist/js/select2.full.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('assets/node_modules_files/bootstrap-select/bootstrap-select.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('assets/node_modules_files/moment/moment.js') }}" type="text/javascript"></script>
    <script src="{{ asset('assets/node_modules_files/bootstrap-datepicker/bootstrap-datepicker.min.js') }}" type="text/javascript"></script>


    <script>
        $(".select2").select2({
            formatNoMatches: function () {
                return "{{ __('messages.noRecordFound') }}";
            }
        });
        $('#start-date').datepicker({
            format: 'yyyy-mm-dd'
        })
        $('#end-date').datepicker({
            format: 'yyyy-mm-dd'
        })

        var table = $('#myTable').DataTable({
            responsive: false,
            processing: true,
            serverSide: true,
            destroy: true,
            stateSave: true,
            ajax: {
                'url': '{!! route('admin.interview-schedule.data') !!}',
                'data': function(d) {
                    return $.extend({}, d, {
                        startDate: $('#start-date').val(),
                        endDate: $('#end-date').val(),
                        status: $('#status').val(),
                        applicationID: $('#applicationID').val(),
                    });
                }
            },
            language: languageOptions(),
            stripeClasses: [],
            dom: '<"jc-table-toolbar"lf>rt<"jc-table-toolbar jc-table-toolbar--footer"ip>',
            drawCallback: function () {
                if ($.fn.tooltip) {
                    $('[data-toggle="tooltip"]').tooltip();
                }
                $('#myTable tbody .schedule-row-checkbox').prop('disabled', false);
                // Keep bulk selection predictable after redraw/filter/paginate.
                $('#example-select-all').prop('checked', false).prop('indeterminate', false);
            },
            columns: [
                { data: 'checkbox', name: 'checkbox', orderable: false, searchable: false},
                { data: 'full_name', name: 'full_name' },
                { data: 'schedule_date', name: 'schedule_date' },
                { data: 'status', name: 'status' },
                { data: 'action', name: 'action', className: 'jc-td-right' }
            ],
            order: [[ 1, 'asc' ]]
        });

        // Handle click on "Select all" control
        $('#example-select-all').on('click', function(){
            // Check/uncheck only current table page checkboxes.
            var checked = this.checked;
            $('#myTable tbody .schedule-row-checkbox').prop('checked', checked);
        });

        // Keep header checkbox state in sync with row checkbox selection.
        $('#myTable tbody').on('change', '.schedule-row-checkbox', function () {
            var $rows = $('#myTable tbody .schedule-row-checkbox');
            var checkedCount = $rows.filter(':checked').length;
            var totalCount = $rows.length;
            $('#example-select-all')
                .prop('checked', totalCount > 0 && checkedCount === totalCount)
                .prop('indeterminate', checkedCount > 0 && checkedCount < totalCount);
        });

        // Force reliable toggle on checkbox click (theme-safe fallback).
        $('#myTable tbody').on('click', '.schedule-row-checkbox', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var $checkbox = $(this);
            $checkbox.prop('checked', !$checkbox.prop('checked')).trigger('change');
        });

        // Label click fallback.
        $('#myTable tbody').on('click', 'label[for^="schedule-row-"]', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var targetId = $(this).attr('for');
            var $checkbox = $('#' + targetId);
            if ($checkbox.length) {
                $checkbox.prop('checked', !$checkbox.prop('checked')).trigger('change');
            }
        });

        // Fallback: click anywhere in first cell toggles row checkbox.
        $('#myTable tbody').on('click', 'td:first-child', function (e) {
            if ($(e.target).is('a, button, i, svg, path')) {
                return;
            }
            var $checkbox = $(this).find('.schedule-row-checkbox').first();
            if (!$checkbox.length) {
                return;
            }
            $checkbox.prop('checked', !$checkbox.prop('checked')).trigger('change');
        });

        // Employee Response on schedule
        $('#changeMultipleStatus').on('click', function(){
            var msg;
            var status = $('#statusMultiple').val();
            var selectedArray = [];
            $('#myTable tbody .schedule-row-checkbox:checked').each(function(){
                selectedArray.push($(this).val());
            });
            if(selectedArray.length > 0){
                if (status !== 'selectStatus') {
                    swal({
                        title: "@lang('errors.askForCandidateEmail')",
                        text: msg,
                        type: "info",
                        showCancelButton: true,
                        confirmButtonColor: "#0c19dd",
                        confirmButtonText: "@lang('app.yes')",
                        cancelButtonText: "@lang('app.no')",
                        closeOnConfirm: true,
                        closeOnCancel: true
                    }, function (isConfirm) {
                        if (isConfirm) {
                            changeMultipleStatus(status , 'yes')
                        }
                        else{
                            changeMultipleStatus(status , 'no')
                        }
                    });
                }else{
                    $.showToastr('Please select any one status', "error" );
                }
            }else{
                $.showToastr('Please check atleast one checkbox', "error" );
                // alert('Please check atleast one checkbox');
            }
        });

        function changeMultipleStatus(status, mailToCandidate){
            var selectedArray = [];
            $('#myTable tbody .schedule-row-checkbox:checked').each(function(){
                selectedArray.push($(this).val());
            });

            var token = "{{ csrf_token() }}";
            var url = "{{ route('admin.interview-schedule.change-status-multiple') }}";
            $.easyAjax({
                url: url,
                type: "POST",
                data: {'_token': token, "id": selectedArray, "status": status,'mailToCandidate': mailToCandidate},
                container: '#myTable',
                success: function (response) {
                    $.unblockUI();
                    table.draw(false);
                    $('#example-select-all').prop('checked', false);
                }
            });
        }

        function openInterviewModal(modalSelector) {
            $(modalSelector).removeClass('hidden');
            $('body').addClass('overflow-hidden');
        }

        function closeInterviewModal(modalSelector, bodySelector) {
            $(modalSelector).addClass('hidden');
            if (bodySelector) {
                $(bodySelector).html('<p class="text-[13px] text-[#8892A0]">Loading...</p>');
            }
            $('body').removeClass('overflow-hidden modal-open');
            $('.modal-backdrop').remove();
        }

        window.closeInterviewScheduleDetailModal = function () {
            closeInterviewModal('#scheduleDetailModal', '#scheduleDetailModalBody');
        };

        window.closeInterviewScheduleEditModal = function () {
            closeInterviewModal('#scheduleEditModal', '#scheduleEditModalBody');
        };

        var intGenericError = 'Something went wrong';

        function loadInterviewModal(modalSelector, bodySelector, headingSelector, title, url) {
            $(headingSelector).text(title);
            openInterviewModal(modalSelector);
            $(bodySelector).html('<p class="text-[13px] text-[#8892A0]">Loading...</p>');

            $.ajax({
                url: url,
                type: 'GET',
                dataType: 'html',
                success: function (response) {
                    if (typeof response === 'string') {
                        $(bodySelector).html(response);
                        return;
                    }

                    if (response && response.view) {
                        $(bodySelector).html(response.view);
                        return;
                    }

                    $(bodySelector).html('<p class="text-danger">' + intGenericError + '.</p>');
                },
                error: function () {
                    $(bodySelector).html('<p class="text-danger">' + intGenericError + '.</p>');
                }
            });
        }

        $('body').on('click', '[data-int-modal-close="detail"]', function () {
            window.closeInterviewScheduleDetailModal();
        });

        $('body').on('click', '[data-int-modal-close="edit"]', function () {
            window.closeInterviewScheduleEditModal();
        });

        $(document).on('keydown', function (e) {
            if (e.key === 'Escape') {
                window.closeInterviewScheduleDetailModal();
                window.closeInterviewScheduleEditModal();
            }
        });

        // Edit Data
        $('body').on('click', '.edit-data', function(){
            var id = $(this).data('row-id');
            var url = "{{ route('admin.interview-schedule.edit',':id') }}";
            url = url.replace(':id', id);
            loadInterviewModal('#scheduleEditModal', '#scheduleEditModalBody', '#scheduleEditModalHeading', "@lang('app.edit')", url);
        });
        // View Data
        $('body').on('click', '.view-data', function(){
            var id = $(this).data('row-id');
            var url = "{{ route('admin.interview-schedule.show',':id') }}?table=yes";
            url = url.replace(':id', id);
            loadInterviewModal('#scheduleDetailModal', '#scheduleDetailModalBody', '#scheduleDetailModalHeading', "@lang('modules.interviewSchedule.interviewSchedule')", url);
        });

        // Delete Data
        $('body').on('click', '.sa-params', function(){
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
            }, function(isConfirm){
                if (isConfirm) {
                    var url = "{{ route('admin.interview-schedule.destroy',':id') }}";
                    url = url.replace(':id', id);
                    var token = "{{ csrf_token() }}";
                    $.easyAjax({
                        type: 'POST',
                        url: url,
                        data: {'_token': token, '_method': 'DELETE'},
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

        // Filte Toggle
        $('.toggle-filter').click(function () {
            $('#ticket-filters').toggle('slide');
        })

        // Apply Filter
        $('#apply-filters').click(function () {
            table.draw(false);
        });

        // Reset Filters
        $('#reset-filters').click(function () {
            $('#filter-form')[0].reset();
            $('#status').val('all');
            $('#status').select2();
            $('#applicationID').val('all');
            $('#applicationID').select2();
            table.draw(false);
        })

        $('body').on('click', '.cancel-meeting', function(){
            var id = $(this).data('meeting-id');
            swal({
                title: "@lang('errors.areYouSure')",
                text: "@lang('errors.cancelWarning')",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "@lang('app.yes')",
                cancelButtonText: "@lang('app.no')",
                closeOnConfirm: true,
                closeOnCancel: true
            }, function(isConfirm){
                if (isConfirm) {

                    var url = "{{ route('admin.zoom-meeting.cancelMeeting',':id') }}";
                    url = url.replace(':id', id);
                    var token = "{{ csrf_token() }}";
                    $.easyAjax({
                        type: 'POST',
                        url: url,
                        data: {'_token': token, 'id': id},
                        success: function (response) {
                            if (response.status == "success") {
                                window.location.reload();
                            }
                        }
                    });
                }
            });
        });

    </script>
@endpush