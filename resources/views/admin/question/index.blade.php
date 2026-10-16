@extends('layouts.app')

@if(in_array("add_question", $userPermissions))
@section('create-button')
    <a href="{{ route('admin.questions.create') }}" class="px-4 py-2 bg-gray-800 text-white rounded hover:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 text-sm ml-4"><i class="fa fa-plus-circle"></i> @lang('app.createNew')</a>
@endsection
@endif

@section('content')

    <div class="flex flex-col gap-4">
        @if(in_array("view_jobs", $userPermissions))
            <a href="{{ route('admin.jobs.index') }}" class="inline-flex w-fit items-center gap-2 rounded-xl bg-[#2563EB] px-4 py-2 text-[12.5px] font-semibold text-white shadow-sm transition hover:bg-[#1d4ed8]">
                <i class="icon-badge"></i> @lang('menu.jobs')
            </a>
        @endif
        <div class="jc-table-card ra-dt-wrap overflow-hidden">
            <table id="myTable" class="jc-cat-table display w-full" style="width:100%">
                <thead>
                <tr>
                    <th style="width:72px;">#</th>
                    <th>@lang('app.question')</th>
                    <th>@lang('menu.jobCategories')</th>
                    <th>@lang('app.required')</th>
                    <th class="jc-th-right" style="padding-right:20px;">@lang('app.action')</th>
                </tr>
                </thead>
            </table>
        </div>
    </div>

    <div id="ai-question-modal" class="fixed inset-0 z-[1000] hidden items-center justify-center bg-black/40 p-4">
        <div class="w-full max-w-2xl rounded-lg bg-white shadow-xl">
            <div class="border-b px-5 py-4 flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-900">Generate Questions with AI</h3>
                <button type="button" id="close-ai-question-modal" class="text-gray-500 hover:text-gray-700">&times;</button>
            </div>
            <form id="ai-question-form" class="px-5 py-4">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group md:col-span-2">
                        <label class="block text-sm font-medium mb-1">Job Title <span class="text-red-600">*</span></label>
                        <input type="text" name="job_title" class="form-control" placeholder="e.g. DevOps Engineer">
                    </div>
                    <div class="form-group md:col-span-2">
                        <label class="block text-sm font-medium mb-1">Category <span class="text-red-600">*</span></label>
                        <select name="category_id" class="form-control select2">
                            <option value="">Select category</option>
                            @foreach(($categories ?? []) as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group md:col-span-2">
                        <label class="block text-sm font-medium mb-1">Skills (comma separated)</label>
                        <textarea name="skills" rows="3" class="form-control" placeholder="AWS, Kubernetes, Terraform, Linux"></textarea>
                    </div>
                </div>
            </form>
            <div class="border-t px-5 py-4 flex items-center justify-end gap-3">
                <button type="button" id="cancel-ai-question-modal" class="px-4 py-2 rounded border border-gray-300 text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="button" id="generate-ai-questions-btn" class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">Generate & Save</button>
            </div>
        </div>
    </div>
@endsection

@push('footer-script')
    <script>
        var aiGenerateQuestionsUrl = @json(route('admin.questions.ai-generate'));
        var table = $('#myTable').DataTable({
            responsive: false,
            serverSide: true,
            ajax: '{!! route('admin.questions.data') !!}',
            language: languageOptions(),
            stripeClasses: [],
            dom: '<"jc-table-toolbar"lf>rt<"jc-table-toolbar jc-table-toolbar--footer"ip>',

            drawCallback: function () {
                if ($.fn.tooltip) {
                    $('[data-toggle="tooltip"]').tooltip();
                }
            }, // ← comma required here

            columns: [
                { data: 'DT_Row_Index', orderable: false, searchable: false },
                { data: 'question', name: 'question' },
                { data: 'category', name: 'category.name', orderable: false },
                { data: 'required', name: 'required' },
                { data: 'action', name: 'action', width: '20%', className: 'jc-td-right' }
            ]
        });

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

                    var url = "{{ route('admin.questions.destroy',':id') }}";
                    url = url.replace(':id', id);

                    var token = "{{ csrf_token() }}";

                    $.easyAjax({
                        type: 'POST',
                        url: url,
                        data: {'_token': token, '_method': 'DELETE'},
                        success: function (response) {
                            if (response.status == "success") {
                                $.unblockUI();
//                                    swal("Deleted!", response.message, "success");
                                table.draw(false);
                            }
                        }
                    });
                }
            });
        });

        function openAiQuestionModal() {
            $('#ai-question-modal').removeClass('hidden').addClass('flex');
            $('#ai-question-form select.select2').select2({ width: '100%' });
        }

        function closeAiQuestionModal() {
            $('#ai-question-modal').addClass('hidden').removeClass('flex');
        }

        $('#open-ai-question-modal').on('click', function () {
            openAiQuestionModal();
        });
        $('#close-ai-question-modal, #cancel-ai-question-modal').on('click', function () {
            closeAiQuestionModal();
        });
        $('#ai-question-modal').on('click', function (e) {
            if (e.target.id === 'ai-question-modal') {
                closeAiQuestionModal();
            }
        });

        $('#generate-ai-questions-btn').on('click', function () {
            var $btn = $(this);
            var payload = $('#ai-question-form').serialize();
            $btn.prop('disabled', true).text('Generating...');

            $.easyAjax({
                url: aiGenerateQuestionsUrl,
                type: 'POST',
                container: '#ai-question-form',
                data: payload,
                success: function (response) {
                    if (response.status === 'success') {
                        $('#ai-question-form')[0].reset();
                        $('#ai-question-form select[name="category_id"]').val('').trigger('change');
                        closeAiQuestionModal();
                        table.ajax.reload(null, false);
                    }
                },
                complete: function () {
                    $btn.prop('disabled', false).text('Generate & Save');
                }
            });
        });

    </script>
@endpush
