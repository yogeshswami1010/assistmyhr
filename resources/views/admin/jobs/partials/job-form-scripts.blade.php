@php
    $isEdit = $isEdit ?? false;
    $jobFormSaveUrl = $isEdit ? route('admin.jobs.update', $job->id) : route('admin.jobs.store');
@endphp
@push('footer-script')
    <script src="{{ asset('assets/node_modules_files/select2/dist/js/select2.full.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('assets/node_modules_files/moment/moment.js') }}" type="text/javascript"></script>
    <script src="{{ asset('assets/node_modules_files/bootstrap-material-datetimepicker/js/bootstrap-material-datetimepicker.js') }}" type="text/javascript"></script>

    <script>
        (function () {
            if (!$('#createForm').length) {
                return;
            }

            window.closeJobAuxModal = function () {
                var $m = $('#addDepartmentModal');
                if ($m.length) {
                    $m.addClass('hidden').css({ display: '', visibility: '', opacity: '' });
                }
            };

            var jobFormSaveUrl = @json($jobFormSaveUrl);
            var txtJobTitle = @json(__('modules.jobs.jobTitle'));
            var txtActive = @json(__('app.active'));
            var txtInactive = @json(__('app.inactive'));
            var txtOpenings = @json(__('modules.front.openings'));
            var linkUrlPrompt = @json(__('modules.jobs.linkUrlPrompt'));
            var aiGenerateIdleLabel = @json(__('app.aiGenerate'));
            var aiGenerateLoadingLabel = @json(__('app.aiGenerating'));
            var aiGeneratePrerequisiteMsg = @json('Please enter job title and select job category first.');

            function jobRichSyncField(editorId, textareaId) {
                var $ed = $('#' + editorId);
                var $ta = $('#' + textareaId);
                if ($ed.length && $ta.length) {
                    $ta.val($ed.html());
                }
            }

            function jobRichSyncAll() {
                jobRichSyncField('job_description_editor', 'job_description');
                jobRichSyncField('job_requirement_editor', 'job_requirement');
            }

            function jobRichInitEditor(editorId, textareaId) {
                var $ta = $('#' + textareaId);
                var $ed = $('#' + editorId);
                if (!$ta.length || !$ed.length) {
                    return;
                }
                var html = $ta.val() || '';
                if (html.trim() === '') {
                    $ed.empty();
                } else {
                    $ed.html(html);
                }
            }

            $(document).on('click', '.job-rte-btn', function (e) {
                e.preventDefault();
                var $ed = $(this).closest('.job-rich-field').find('.job-rich-editor-editor');
                var el = $ed[0];
                if (!el) {
                    return;
                }
                el.focus();
                var cmd = $(this).data('cmd');
                if (cmd === 'createLink') {
                    var url = prompt(linkUrlPrompt);
                    if (url) {
                        document.execCommand('createLink', false, url);
                    }
                } else if (cmd) {
                    document.execCommand(cmd, false, null);
                }
                jobRichSyncAll();
            });

            $('#job_description_editor, #job_requirement_editor').on('input', function () {
                jobRichSyncAll();
            });

            if (typeof $.fn.iCheck === 'function') {
                $('input[type="checkbox"].flat-red').iCheck({
                    checkboxClass: 'icheckbox_flat-blue',
                });
            }

            var jobFormSelect2Opts = {
                width: '100%',
                dropdownCssClass: 'job-form-select2-dd',
            };

            function initJobFormSelect2() {
                $('#createForm select.select2').each(function () {
                    var $s = $(this);
                    if ($s.data('select2')) {
                        $s.select2('destroy');
                    }
                });
                $('#createForm select.select2').select2(jobFormSelect2Opts);
            }

            function reinitJobSkillsSelect2() {
                var $js = $('#job_skills');
                if ($js.data('select2')) {
                    $js.select2('destroy');
                }
                $js.select2(jobFormSelect2Opts);
            }

            function syncEditorWithHtml(editorId, textareaId, html) {
                var $ed = $('#' + editorId);
                var $ta = $('#' + textareaId);
                if (!$ed.length || !$ta.length) {
                    return;
                }
                $ed.html(html || '');
                $ta.val(html || '');
            }

            function updateAiGenerateButtonVisibility() {
                var hasTitle = ($('#title').val() || '').trim().length > 0;
                var hasCategory = ($('#category_id').val() || '').trim().length > 0;
                var $btn = $('#ai-generate-job-content-btn');
                if (!$btn.length) {
                    return;
                }
                $btn.toggleClass('opacity-50', !(hasTitle && hasCategory));
            }

            function applyGeneratedSkills(skillNames) {
                var wanted = (skillNames || []).map(function (name) {
                    return (name || '').toString().trim().toLowerCase();
                }).filter(Boolean);

                if (!wanted.length) {
                    return;
                }

                var selectedValues = [];
                $('#job_skills option').each(function () {
                    var text = ($(this).text() || '').trim().toLowerCase();
                    if (wanted.indexOf(text) !== -1) {
                        selectedValues.push($(this).val());
                    }
                });

                $('#job_skills').val(selectedValues).trigger('change');
            }

            initJobFormSelect2();

            // Bind after jQuery and Select2 load; the form markup is rendered before them.
            $('#location_id').on('change.jobLocation', function () {
                toggleNewLocationField(this);
            });
            toggleNewLocationField(document.getElementById('location_id'));

            if (typeof $.fn.bootstrapMaterialDatePicker === 'function') {
                $('#date-end').bootstrapMaterialDatePicker({ weekStart: 0, time: false });
                $('#date-start').bootstrapMaterialDatePicker({ weekStart: 0, time: false }).on('change', function (e, date) {
                    $('#date-end').bootstrapMaterialDatePicker('setMinDate', date);
                    @if (! $isEdit)
                        $('#date-end').val(moment(date).add(1, 'month').format('YYYY-MM-DD'));
                    @endif
                    jobFormPreviewRefresh();
                });
                $('#date-end').on('change', function () {
                    jobFormPreviewRefresh();
                });
            }

            $('#category_id').change(function () {
                var id = $(this).val();
                var url = "{{ route('admin.job-categories.getSkills', ':id') }}".replace(':id', id);
                var selectedQuestions = $('#job-questions-box input[name="question[]"]:checked').map(function () {
                    return $(this).val();
                }).get();
                $.easyAjax({
                    url: url,
                    data: {
                        selected_questions: selectedQuestions
                    },
                    success: function (response) {
                        $('#job_skills').html(response.data);
                        if (typeof response.questions_html !== 'undefined') {
                            $('#job-questions-box').html(response.questions_html);
                            if (typeof $.fn.iCheck === 'function') {
                                $('#job-questions-box input[type="checkbox"].flat-red').iCheck({
                                    checkboxClass: 'icheckbox_flat-blue',
                                });
                            }
                        }
                        reinitJobSkillsSelect2();
                        jobFormPreviewRefresh();
                        updateAiGenerateButtonVisibility();
                    },
                });
            });

            function setPayTypeLabels() {
                switch ($('#paytype').val()) {
                    case 'Starting':
                        $('#start_amt label').html("{{ __('modules.jobs.startingSalary') }}");
                        break;
                    case 'Maximum':
                        $('#start_amt label').html("{{ __('modules.jobs.maximumSalary') }}");
                        break;
                    case 'Exact Amount':
                        $('#start_amt label').html("{{ __('modules.jobs.exactSalary') }}");
                        break;
                    default:
                        $('#start_amt label').html("{{ __('modules.jobs.startingSalary') }}");
                }
            }

            function applyPayTypeVisibility() {
                var pt = $('#paytype').val();
                if (!pt) {
                    $('#amount_field, #payaccording').addClass('hidden');
                    return;
                }
                if (pt != 'Range') {
                    $('#amount_field, #payaccording').removeClass('hidden');
                    $('#end_amt').addClass('hidden');
                    setPayTypeLabels();
                } else {
                    $('#start_amt label').html("{{ __('modules.jobs.startingSalary') }}");
                    $('#amount_field, #end_amt, #payaccording').removeClass('hidden');
                }
            }

            $('#amount_field, #payaccording').addClass('hidden');
            @if ($job && $job->pay_type && $job->pay_type != 'Range')
                $('#amount_field, #payaccording').removeClass('hidden');
                $('#end_amt').addClass('hidden');
                setPayTypeLabels();
            @elseif ($job && $job->pay_type == 'Range')
                $('#amount_field, #end_amt, #payaccording').removeClass('hidden');
            @endif

            $('#paytype').on('change', function () {
                applyPayTypeVisibility();
            });

            function jobFormPreviewRefresh() {
                var title = ($('#title').val() || '').trim() || txtJobTitle;
                $('#job-preview-title').text(title);

                var companyTxt = $('#company option:selected').text().trim();
                $('#job-preview-company').text(companyTxt && companyTxt !== '—' ? companyTxt : '—');

                var jt = $('#job_type option:selected').text().trim();
                $('#job-preview-type').text(jt && jt !== '—' ? jt : '—');

                var catId = $('#category_id').val();
                $('#job-preview-category').text(catId ? $('#category_id option:selected').text().trim() : '—');

                var locs = [];
                $('#location_id option:selected').each(function () {
                    locs.push($(this).text().trim());
                });
                $('#job-preview-location').text(locs.length ? locs.join(', ') : '—');

                var n = parseInt($('#total_positions').val(), 10) || 0;
                $('#job-preview-openings').text(n > 0 ? (n + ' ' + txtOpenings) : '—');

                var ds = ($('#date-start').val() || '').trim();
                var de = ($('#date-end').val() || '').trim();
                $('#job-preview-dates').text(ds && de ? (ds + ' – ' + de) : (ds || de || '—'));

                var st = $('#status').val();
                var $badge = $('#job-preview-status');
                if (st === 'inactive') {
                    $badge.removeClass('bg-emerald-50 text-emerald-600').addClass('bg-slate-100 text-slate-500');
                    $badge.text(txtInactive);
                } else {
                    $badge.removeClass('bg-slate-100 text-slate-500').addClass('bg-emerald-50 text-emerald-600');
                    $badge.text(txtActive);
                }

                var skills = $('#job_skills option:selected').map(function () {
                    return $(this).text().trim();
                }).get().filter(Boolean);
                $('#job-preview-skills').text(skills.length ? skills.join(' · ') : '—');
            }

            $('#title, #company, #category_id, #job_type, #total_positions, #status').on('change input', jobFormPreviewRefresh);
            $('#location_id, #job_skills').on('change', jobFormPreviewRefresh);
            $('#title, #category_id').on('change input', updateAiGenerateButtonVisibility);

            $('.job-pos-minus').on('click', function () {
                var $i = $('#total_positions');
                var v = Math.max(1, Math.min(999, (parseInt($i.val(), 10) || 1) - 1));
                $i.val(v).trigger('change');
            });
            $('.job-pos-plus').on('click', function () {
                var $i = $('#total_positions');
                var v = Math.max(1, Math.min(999, (parseInt($i.val(), 10) || 1) + 1));
                $i.val(v).trigger('change');
            });

            $(document).ready(function () {
                jobRichInitEditor('job_description_editor', 'job_description');
                jobRichInitEditor('job_requirement_editor', 'job_requirement');
                jobRichSyncAll();

                jobFormPreviewRefresh();
                updateAiGenerateButtonVisibility();

                $('#addJobType').click(function () {
                    var url = "{{ route('admin.job-type.create') }}";
                    $('#job-aux-modal-title').text(@json(__('modules.jobs.jobType')));
                    $.ajaxModal('#addDepartmentModal', url);
                });

                $('#addWorkExperience').click(function () {
                    var url = "{{ route('admin.work-experience.create') }}";
                    $('#job-aux-modal-title').text(@json(__('modules.jobs.workExperience')));
                    $.ajaxModal('#addDepartmentModal', url);
                });
            });

            $('#save-form').click(function () {
                jobRichSyncAll();
                var serialized = $('#createForm').serializeArray();
                var requestPayload = {};
                serialized.forEach(function (item) {
                    if (Object.prototype.hasOwnProperty.call(requestPayload, item.name)) {
                        if (!Array.isArray(requestPayload[item.name])) {
                            requestPayload[item.name] = [requestPayload[item.name]];
                        }
                        requestPayload[item.name].push(item.value);
                    } else {
                        requestPayload[item.name] = item.value;
                    }
                });
                console.log('save-form request payload:', requestPayload);
                $.easyAjax({
                    url: jobFormSaveUrl,
                    container: '#createForm',
                    type: 'POST',
                    redirect: true,
                    data: $('#createForm').serialize(),
                });
            });

            $('#ai-generate-job-content-btn').on('click', function () {
                var $btn = $(this);
                var title = ($('#title').val() || '').trim();
                var categoryId = $('#category_id').val();
                var $label = $('#ai-generate-job-content-label');

                function resetAiGenerateButton() {
                    $btn.prop('disabled', false);
                    $label.text(aiGenerateIdleLabel);
                }

                if (!title || !categoryId) {
                    toastr.error(aiGeneratePrerequisiteMsg);
                    return;
                }

                $btn.prop('disabled', true);
                $label.text(aiGenerateLoadingLabel);

                // Safety fallback in case any callback is skipped by transport/runtime errors.
                var aiBtnSafetyTimeout = setTimeout(function () {
                    resetAiGenerateButton();
                }, 20000);

                $.easyAjax({
                    url: $btn.data('url'),
                    container: '#createForm',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        title: title,
                        category_id: categoryId
                    },
                    success: function (response) {
                        try {
                            var payload = null;
                            if (response && response.data && typeof response.data === 'object') {
                                payload = response.data;
                            } else if (response && typeof response === 'object') {
                                payload = response;
                            }

                            if (!payload || (!payload.description_html && !payload.job_requirement_html)) {
                                toastr.error((response && response.message) ? response.message : 'AI generation failed.');
                                return;
                            }

                            syncEditorWithHtml('job_description_editor', 'job_description', payload.description_html || '');
                            syncEditorWithHtml(
                                'job_requirement_editor',
                                'job_requirement',
                                payload.job_requirement_html || ''
                            );
                            applyGeneratedSkills(payload.skills || []);
                            if (typeof payload.meta_title !== 'undefined') {
                                $('#meta-title').val(payload.meta_title || '');
                            }
                            if (typeof payload.meta_description !== 'undefined') {
                                $('#meta-description').val(payload.meta_description || '');
                            }
                            jobFormPreviewRefresh();
                        } finally {
                            clearTimeout(aiBtnSafetyTimeout);
                            resetAiGenerateButton();
                        }
                    },
                    error: function () {
                        clearTimeout(aiBtnSafetyTimeout);
                        resetAiGenerateButton();
                    }
                });
            });
        })();
    </script>
@endpush
