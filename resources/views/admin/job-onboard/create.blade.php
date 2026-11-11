@extends('layouts.app')

@push('head-script')
    @include('admin.partials.settings-design-styles')
    <link rel="stylesheet" href="{{ asset('assets/node_modules_files/bootstrap-material-datetimepicker/css/bootstrap-material-datetimepicker.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/node_modules_files/multiselect/css/multi-select.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/node_modules_files/switchery/dist/switchery.min.css') }}">
    <style>
        #createSchedule .form-control {
            min-height: 42px;
        }
        #createSchedule .select-file {
            padding: 6px !important;
            height: 42px;
        }
        .onboard-muted {
            color: #8892A0;
            font-size: 12px;
        }
        .onboard-file-row {
            display: flex;
            align-items: end;
            gap: 12px;
            flex-wrap: nowrap;
        }
        .onboard-file-row .file-name-col {
            flex: 1 1 50%;
            min-width: 0;
        }
        .onboard-file-row .file-input-col {
            flex: 1 1 50%;
            min-width: 0;
        }
        .onboard-file-row .file-action-col {
            flex: 0 0 auto;
        }
    </style>
@endpush

@section('page-title-html')
    <span class="text-[22px] font-bold tracking-tight text-[#1A1E2E]">@lang('app.job') <span class="font-['Instrument_Serif',serif] text-[20px] font-normal italic text-[#2563EB]">@lang('app.onBoard')</span></span>
@endsection

@section('page-subtitle')
    @lang('modules.interviewSchedule.candidate') · @lang('app.department') · @lang('app.designation')
@endsection

@section('content')
    <div class="mx-auto pb-10">
        <form id="createSchedule" class="ajax-form space-y-4" method="post">
            @csrf
            <input type="hidden" name="candidate_id" value="{{ $application->id }}">

            <div class="mb-4 overflow-hidden rounded-[18px] border border-[#E8E6E1] bg-white">
                <div class="flex items-center gap-3 border-b border-[#F0EEE9] px-6 py-4">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-[10px] bg-[#EFF6FF]">
                        <i class="fa fa-user text-[#2563EB]"></i>
                    </div>
                    <div>
                        <h2 class="text-[15px] font-bold text-[#1A1E2E]">@lang('modules.interviewSchedule.candidate') @lang('app.details')</h2>
                        <p class="text-[12px] text-[#8892A0]">@lang('app.name') · @lang('app.phone') · @lang('app.email')</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-2 md:gap-5">
                    <div>
                        <label class="bs-set-lbl">@lang('modules.interviewSchedule.candidate') @lang('app.name')</label>
                        <p class="font-medium text-[#1A1E2E]">{{ $application->full_name }}</p>
                    </div>
                    <div>
                        <label class="bs-set-lbl">@lang('modules.interviewSchedule.candidate') @lang('app.phone')</label>
                        <p class="font-medium text-[#1A1E2E]">{{ $application->phone }}</p>
                    </div>
                    <div>
                        <label class="bs-set-lbl">@lang('modules.interviewSchedule.candidate') @lang('app.email')</label>
                        <p class="font-medium text-[#1A1E2E]">{{ $application->email }}</p>
                    </div>
                    <div>
                        <label class="bs-set-lbl">@lang('app.job') @lang('app.location')</label>
                        <p class="font-medium text-[#1A1E2E]">{{ ucwords(optional($application->location)->location ?? 'N/A') }}</p>
                    </div>
                </div>
            </div>

            <div class="mb-4 overflow-hidden rounded-[18px] border border-[#E8E6E1] bg-white">
                <div class="flex items-center gap-3 border-b border-[#F0EEE9] px-6 py-4">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-[10px] bg-[#F5F3FF]">
                        <i class="fa fa-briefcase text-[#7C3AED]"></i>
                    </div>
                    <div>
                        <h2 class="text-[15px] font-bold text-[#1A1E2E]">@lang('app.job') @lang('app.details')</h2>
                        <p class="text-[12px] text-[#8892A0]">@lang('app.department') · @lang('app.designation') · @lang('app.salaryOfferedPerMonth')</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-2 md:gap-5">
                    <div>
                        <label class="bs-set-lbl">@lang('app.department')</label>
                        <div class="mb-2">
                            <a href="javascript:;" title="@lang('app.add') @lang('app.department')" id="addDepartment" class="btn btn-sm btn-info btn-outline">
                                <i class="fa fa-plus"></i> @lang('app.add')
                            </a>
                        </div>
                        <select class="m-b-10 form-control" name="department" id="department" data-placeholder="@lang('app.choose') @lang('app.department')">
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}">{{ ucwords($department->name) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="bs-set-lbl">@lang('app.designation')</label>
                        <div class="mb-2">
                            <a href="javascript:;" title="@lang('app.add') @lang('app.designation')" id="addDesignation" class="btn btn-sm btn-outline btn-info">
                                <i class="fa fa-plus"></i> @lang('app.add')
                            </a>
                        </div>
                        <select class="m-b-10 form-control" name="designation" id="designation" data-placeholder="@lang('app.choose') @lang('app.designation')">
                            @foreach($designations as $designation)
                                <option value="{{ $designation->id }}">{{ ucwords($designation->name) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="currency_id" class="bs-set-lbl">@lang('app.currency')</label>
                        <select name="currency_id" id="currency_id" class="form-control">
                            @foreach ($currencies as $item)
                                <option @if ($item->id == $global->currency_id) selected @endif value="{{ $item->id }}">
                                    {{ $item->currency_code.' ('.$item->currency_symbol.')' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="bs-set-lbl required">@lang('app.salaryOfferedPerMonth')</label>
                        <input type="number" class="form-control" min="0" name="salary" value="">
                    </div>
                    <div>
                        <label class="bs-set-lbl required">@lang('app.joinDate')</label>
                        <input type="text" class="form-control datepicker" name="join_date" id="join_date" value="">
                    </div>
                    <div>
                        <label class="bs-set-lbl required">@lang('app.lastDate')</label>
                        <input type="text" class="form-control datepicker" name="last_date" id="last_date" value="">
                    </div>
                    <div>
                        <label class="bs-set-lbl">@lang('app.employeeWorkStatus')</label>
                        <select class="m-b-10 form-control" name="employee_status">
                            <option value="part_time">@lang('app.partTime')</option>
                            <option value="full_time">@lang('app.fullTime')</option>
                            <option value="on_contract">@lang('app.onContract')</option>
                        </select>
                    </div>
                    <div>
                        <label class="bs-set-lbl">@lang('app.reportTo')</label>
                        <select class="select2 m-b-10 form-control" name="report_to" data-placeholder="@lang('app.reportTo')">
                            @foreach($users as $emp)
                                <option value="{{ $emp->id }}">{{ ucwords($emp->name) }} @if($emp->id == $user->id) (@lang('app.you')) @endif</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="bs-set-lbl">@lang('app.sendOfferEmail')</label>
                        <div class="switchery-demo mt-2">
                            <input id="nexmo_status" name="send_email" type="checkbox" value="yes" class="js-switch change-language-setting" data-color="#99d683" data-size="small"/>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-4 overflow-hidden rounded-[18px] border border-[#E8E6E1] bg-white">
                <div class="flex items-center gap-3 border-b border-[#F0EEE9] px-6 py-4">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-[10px] bg-[#ECFDF5]">
                        <i class="fa fa-paperclip text-[#059669]"></i>
                    </div>
                    <div>
                        <h2 class="text-[15px] font-bold text-[#1A1E2E]">@lang('app.offer') @lang('app.files')</h2>
                        <p class="text-[12px] text-[#8892A0]">@lang('app.addMore') @lang('app.files')</p>
                    </div>
                </div>
                <div class="space-y-4 p-6">
                    <div id="fileRows" class="space-y-3">
                        <div id="addMoreBox1" class="onboard-file-row file-row">
                            <div class="file-name-col">
                                <input type="text" name="name[]" class="form-control file-input" placeholder="@lang('app.file') @lang('app.name')">
                            </div>
                            <div class="file-input-col">
                                <input class="form-control select-file file-input" accept=".png,.jpg,.jpeg,.pdf,.doc,.docx,.xls,.xlsx,.rtf" type="file" name="file[]">
                            </div>
                            <div class="file-action-col"></div>
                        </div>
                    </div>
                    <div id="insertBefore"></div>
                    <button type="button" id="plusButton" class="btn btn-sm btn-info">
                        @lang('app.addMore') <i class="fa fa-plus"></i>
                    </button>
                </div>
            </div>

            @if (count($questions) > 0)
                <div class="mb-4 overflow-hidden rounded-[18px] border border-[#E8E6E1] bg-white">
                    <div class="flex items-center gap-3 border-b border-[#F0EEE9] px-6 py-4">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-[10px] bg-[#FFF7ED]">
                            <i class="fa fa-question-circle text-[#F97316]"></i>
                        </div>
                        <div>
                            <h2 class="text-[15px] font-bold text-[#1A1E2E]">@lang('modules.front.questions')</h2>
                            <p class="text-[12px] text-[#8892A0]">@lang('app.optional')</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 gap-3 p-6 md:grid-cols-2">
                        @foreach($questions as $question)
                            <label class="flex cursor-pointer items-start gap-2 rounded-[11px] border border-[#F0EEE9] bg-[#FAFAF8] px-4 py-3 transition hover:border-[#E2DED8]">
                                <input type="checkbox" value="{{ $question->id }}" name="question[]" class="mt-1">
                                <span class="text-[13px] text-[#3D4A5C]">
                                    {{ ucfirst($question->question) }} @if ($question->required == 'yes') <span class="onboard-muted">(@lang('app.required'))</span> @endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <button type="button" class="btn btn-success save-onboard"><i class="fa fa-check"></i> @lang('app.save')</button>
            </div>
        </form>
    </div>

    {{-- Ajax modal --}}
    <div class="fixed inset-0 z-50 hidden overflow-y-auto" id="addDepartmentModal" role="dialog" aria-labelledby="job-aux-modal-title" aria-modal="true" aria-hidden="true">
        <div class="flex min-h-full w-full items-center justify-center p-4 sm:p-6">
            <div class="fixed inset-0 z-0 bg-black/50 transition-opacity" aria-hidden="true" onclick="closeOnboardAuxModal()"></div>
            <div class="relative z-10 flex max-h-[min(90vh,calc(100dvh-2rem))] w-full max-w-4xl flex-col overflow-hidden rounded-xl bg-white shadow-xl ring-1 ring-black/5" id="modal-data-application">
                <div class="flex shrink-0 items-center justify-between border-b border-slate-200 px-6 py-4">
                    <h4 class="text-lg font-semibold tracking-tight text-slate-900" id="job-aux-modal-title"><i class="icon-plus" aria-hidden="true"></i> <span id="modelHeading">@lang('app.department')</span></h4>
                    <button type="button" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2" onclick="closeOnboardAuxModal()" aria-label="@lang('app.close')"><i class="fa fa-times text-lg" aria-hidden="true"></i></button>
                </div>
                <div class="modal-data-shell min-h-0 flex-1">
                    <div id="onboardAuxModalContent" class="max-h-[min(70vh,560px)] min-h-[12rem] overflow-y-auto p-6 text-left text-sm text-slate-600">Loading...</div>
                </div>
                <div class="flex shrink-0 items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4">
                    <button type="button" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2" onclick="closeOnboardAuxModal()">@lang('app.close')</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('footer-script')
    <script src="{{ asset('assets/node_modules_files/moment/moment.js') }}" type="text/javascript"></script>
    <script src="{{ asset('assets/node_modules_files/multiselect/js/jquery.multi-select.js') }}"></script>
    <script src="{{ asset('assets/node_modules_files/bootstrap-select/bootstrap-select.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('assets/node_modules_files/bootstrap-material-datetimepicker/js/bootstrap-material-datetimepicker.js') }}" type="text/javascript"></script>
    <script src="{{ asset('assets/node_modules_files/switchery/dist/switchery.min.js') }}"></script>
    <script src="{{ asset('assets/node_modules_files/select2/dist/js/select2.full.min.js') }}" type="text/javascript"></script>

    <script>
        // Bootstrap modal fallback for legacy partial scripts loaded in this page.
        // Department/Designation create partials call $('#addDepartmentModal').modal('hide').
        if (window.jQuery && !$.fn.modal) {
            $.fn.modal = function (action) {
                return this.each(function () {
                    var $el = $(this);
                    if (action === 'hide') {
                        $el.addClass('hidden').css({
                            display: '',
                            visibility: '',
                            opacity: ''
                        });
                    } else if (action === 'show') {
                        $el.removeClass('hidden').css({
                            display: '',
                            visibility: 'visible',
                            opacity: '1'
                        });
                    }
                });
            };
        }

        function closeOnboardAuxModal() {
            $('#addDepartmentModal').addClass('hidden').css({
                display: '',
                visibility: '',
                opacity: ''
            });
        }

        function openOnboardAuxModal(url, heading) {
            $('#modelHeading').html(heading);
            $('#onboardAuxModalContent').html('Loading...');
            $('#addDepartmentModal').removeClass('hidden').css({
                display: '',
                visibility: 'visible',
                opacity: '1'
            });

            $.ajax({
                url: url,
                type: 'GET',
                success: function (response) {
                    var content = '';
                    if (typeof response === 'string') {
                        content = response;
                    } else if (response && response.view) {
                        content = response.view;
                    } else if (response && response.html) {
                        content = response.html;
                    } else {
                        content = '';
                    }

                    $('#onboardAuxModalContent').html(content || '<div class="text-red-600">Unable to load content.</div>');

                    // Loaded department/designation partials include their own modal header/footer.
                    // Hide inner chrome to avoid double-header/double-footer inside Tailwind shell.
                    var $content = $('#onboardAuxModalContent');
                    $content.children('div').first().hide();
                    $content.children('div').last().hide();

                    // Rewire legacy bootstrap dismiss buttons to this page modal closer.
                    $content.find('[data-dismiss="modal"]').each(function () {
                        $(this).removeAttr('data-dismiss');
                        $(this).on('click', function (e) {
                            e.preventDefault();
                            closeOnboardAuxModal();
                        });
                    });

                    $(document).trigger('ajaxPageLoad');
                },
                error: function () {
                    $('#onboardAuxModalContent').html('<div class="text-red-600">Error loading content. Please try again.</div>');
                }
            });
        }

        $(document).on('click', '.save-onboard', function () {
            $.easyAjax({
                url: @json(tenant_route('admin.job-onboard.store')),
                container: '#createSchedule',
                type: 'POST',
                file: true
            });
        });

        $(document).on('click', '#addDepartment', function (e) {
            e.preventDefault();
            var url = "{{ route('admin.departments.create') }}";
            openOnboardAuxModal(url, "@lang('app.department')");
        });

        $(document).on('click', '#addDesignation', function (e) {
            e.preventDefault();
            var url = "{{ route('admin.designations.create') }}";
            openOnboardAuxModal(url, "@lang('app.designation')");
        });

        var rowCounter = 1;
        $(document).on('click', '#plusButton', function () {
            rowCounter++;
            var rowHtml = '' +
                '<div id="addMoreBox' + rowCounter + '" class="onboard-file-row file-row mt-3">' +
                    '<div class="file-name-col">' +
                        '<input type="text" name="name[]" class="form-control file-input" placeholder="@lang('app.file') @lang('app.name')">' +
                    '</div>' +
                    '<div class="file-input-col">' +
                        '<input class="form-control select-file file-input" accept=".png,.jpg,.jpeg,.pdf,.doc,.docx,.xls,.xlsx,.rtf" type="file" name="file[]">' +
                    '</div>' +
                    '<div class="file-action-col">' +
                        '<button type="button" class="btn btn-sm btn-danger remove-file-row" data-index="' + rowCounter + '"><i class="fa fa-times"></i></button>' +
                    '</div>' +
                '</div>';

            $('#fileRows').append(rowHtml);
        });

        $('body').on('click', '.remove-file-row', function () {
            var index = $(this).data('index');
            $('#addMoreBox' + index).remove();
        });

        if ($.fn.select2) {
            $('.select2').select2({
                width: '100%',
                language: {
                    noResults: function () {
                        return "{{ __('messages.noRecordFound') }}";
                    }
                }
            });
        }

        if (typeof Switchery !== 'undefined') {
            $('.js-switch').each(function () {
                new Switchery($(this)[0], $(this).data());
            });
        }

        var joinDate = "{{ $application->created_at->format('Y-m-d') }}";
        if ($.fn.bootstrapMaterialDatePicker) {
            $('#join_date').bootstrapMaterialDatePicker({
                minDate: new Date(joinDate),
                time: false,
                clearButton: true,
            }).on('change', function () {
                var value = $('#join_date').val();
                $('#last_date').val('');
                $('#last_date').bootstrapMaterialDatePicker({
                    minDate: new Date(value),
                    time: false,
                    clearButton: true,
                });
            });

            $('#last_date').bootstrapMaterialDatePicker({
                minDate: new Date(joinDate),
                time: false,
                clearButton: true,
            });
        }
    </script>
@endpush

