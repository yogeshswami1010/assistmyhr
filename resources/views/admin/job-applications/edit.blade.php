@extends('layouts.app')

@push('head-script')
    <link rel="stylesheet" href="{{ asset('assets/plugins/datepicker/datepicker3.css') }}">
@endpush

@section('content')
    @php
        $gender = [
            'male' => __('modules.front.male'),
            'female' => __('modules.front.female'),
            'others' => __('modules.front.others')
        ];
    @endphp
    <div class="flex flex-col">
        <div class="w-full">
            <div class="bg-white rounded-lg shadow-md">
                <div class="p-6">
                    <h4 class="text-xl font-semibold text-gray-900 mb-6">@lang('app.edit')</h4>

                    <form class="ajax-form" method="POST" id="editForm">
                        @csrf
                        @method('PUT')
                        <input type="hidden" id="resume_text_for_ai" value="" autocomplete="off">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
                            <div class="md:col-span-4">
                                <h5 class="text-lg font-semibold text-gray-900">@lang('modules.front.personalInformation')</h5>
                            </div>

                            <div class="md:col-span-8">

                                <div class="form-group">
                                    <input type="hidden" value="" name="job_id" id="job_id">
                                    <input type="hidden" value="" name="location_id" id="location_id">
                                    <label class="control-label">@lang('menu.jobs')</label>
                                    
                                    <select name="job_job_location_id" id="job_job_location_id" onchange="getQuestions(this.value)"
                                        class="select2 form-control">
                                        @foreach ($locations as $location)
                                            @php
                                                $isSelected = ($application->location_id == $location->location_id && $application->job_id == $location->job_id);
                                                $title = ucwords($location->job->title);
                                                $locationName = $location->location->location;
                                            @endphp
                                            <option value= {{ $location->id }} {{ $isSelected ? 'selected' : '' }}>
                                                {{ $title }}({{$locationName}})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="control-label">@lang('app.name')</label>
                                    <input class="form-control" type="text" value="{{ $application->full_name }}" name="full_name" placeholder="@lang('app.name')">
                                </div>

                                <div class="form-group">
                                    <label class="control-label">@lang('app.email')</label>
                                    <input class="form-control" type="email" name="email" value="{{ $application->email }}"
                                           placeholder="@lang('app.email')">
                                </div>

                                <div class="form-group">
                                    <label class="control-label">@lang('app.phone')</label>
                                    <input class="form-control" type="tel" name="phone" value="{{ $application->phone }}"
                                           placeholder="@lang('app.phone')">
                                </div>

                                <div class="form-group">
                                    <label class="control-label">@lang('app.address')</label>
                                    <textarea class="form-control" name="address" rows="4" cols="50"
                                           placeholder="@lang('app.address')">{{ $application->address }}</textarea>
                                </div>

                                <div id="show-columns">
                                    @include('admin.job-applications.required-columns', ['job' => $application->job, 'application' => $application, 'gender' => $gender])
                                </div>
                            </div>
                        </div>
                        <div id="show-sections">
                            @include('admin.job-applications.required-sections', ['section_visibility' => $jobs[0]->section_visibility, 'application' => $application])
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-6 border-t border-gray-200 pt-6">
                            @if(count($jobQuestion) > 0)
                                <div class="md:col-span-4" id="questionBoxTitle">
                                    <h5 class="text-lg font-semibold text-gray-900">@lang('modules.front.additionalDetails')</h5>
                                </div>

                                <div class="md:col-span-8" id="questionBox">

                                </div>
                            @endif
                            <div class="md:col-span-4">
                                <h5 class="text-lg font-semibold text-gray-900">@lang('app.status')</h5>
                            </div>

                            <div class="md:col-span-8">
                                <div class="form-group">
                                    <select name="status_id" id="status_id" class="select2 form-control">
                                        @foreach($statuses as $status)
                                            <option
                                                    @if($application->status_id == $status->id) selected @endif
                                                    value="{{ $status->id }}">{{ ucwords($status->status) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mt-6">
                            <button type="button" id="save-form" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 inline-flex items-center">
                                <i class="fa fa-check mr-2"></i> 
                                @lang('app.save')
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('footer-script')
    <script src="{{ asset('assets/plugins/datepicker/bootstrap-datepicker.js') }}"></script>
    <script src="{{ asset('assets/node_modules_files/select2/dist/js/select2.full.min.js') }}"></script>
    <script>
        const fetchCountryState = "{{ tenant_route('jobs.fetchCountryState') }}";
        const csrfToken = "{{ csrf_token() }}";
        const selectCountry = "@lang('modules.front.selectCountry')";
        const selectState = "@lang('modules.front.selectState')";
        const selectCity = "@lang('modules.front.selectCity')";
        const pleaseWait = "@lang('app.aiGenerating')";
        const aiGenerateCoverLetterUrl = "{{ route('admin.job-applications.ai-generate-cover-letter') }}";
        const aiParseResumeUrl = "{{ route('admin.job-applications.ai-parse-resume') }}";

        let country = "{{ $application->country }}";
        let state = "{{ $application->state }}";
    </script>
    <script src="{{ asset('front/assets/js/location.js') }}"></script>
    <script>
        var datepicker = $('.dob').datepicker({
            autoclose: true,
            format: 'yyyy-mm-dd',
            endDate: (new Date()).toDateString(),
        });

        @if ($application->dob)
            datepicker.datepicker('setDate', new Date('{{ $application->dob }}'))
        @endif
        
        $('.select2').select2({
            width: '100%'
        });

        $('#save-form').click(function () {

            $.easyAjax({
                url: '{{route('admin.job-applications.update', $application->id)}}',
                container: '#editForm',
                type: "POST",
                redirect: true,
                file:true,
                error: function (response) {
                    handleFails(response);
                }
            })
        });

        var val = $('#job_job_location_id').val(); // get Current Selected Job
        if (val != '' && typeof val !== 'undefined') {
            getQuestions(val); // get Questions by question on page load
        }

        // get Questions on change Job
        function getQuestions(id) {
            var url = "{{ route('admin.job-applications.question', [':id', $application->id]) }}";
            url = url.replace(':id', id);

            $.easyAjax({
                type: 'GET',
                url: url,
                container: '#editForm',
                success: function (response) {
                    $('#job_id').val(response.jobJobLocation.job_id)
                    $('#location_id').val(response.jobJobLocation.location_id)
                    if (response.status == "success") {
                        if (response.count > 0) { // Question Found for selected job
                            $('#questionBox').removeClass('hidden');
                            $('#questionBoxTitle').removeClass('hidden');
                            $('#questionBox').html(response.view);
                        } else { // Question Not Found for selected job
                            $('#questionBox').addClass('hidden');
                            $('#questionBoxTitle').addClass('hidden');
                        }
                        $('#show-columns').html(response.requiredColumnsView);
                        $('#show-sections').html(response.requiredSectionsView);
                        if(response.requiredColumnsView !== '') {
                            var datepicker = $('.dob').datepicker({
                                autoclose: true,
                                format: 'yyyy-mm-dd',
                                endDate: (new Date()).toDateString(),
                            });
                            if (response.application.dob !== null) {
                                $('.dob').datepicker('setDate', new Date(response.application.dob));
                            }

                            $('.select2').select2({
                                width: '100%'
                            });

                            country = response.application.country;
                            state = response.application.state;

                            var loc = new locationInfo()
                            loc.getCountries()
                        }
                    }
                }
            });
        }

        // AI generate cover letter + (optionally) fill other personal details.
        $(document).on('click', '.ai-generate-cover-letter', function() {
            var $btn = $(this);
            var jobId = $('#job_id').val();
            var locationId = $('#location_id').val();

            if (typeof jobId === 'undefined' || jobId === '') {
                alert('Please select job and location first.');
                return;
            }

            var $form = $('#editForm');
            var payload = {
                _token: csrfToken,
                job_id: jobId,
                location_id: (locationId && locationId !== '') ? locationId : null,
                full_name: $form.find('input[name="full_name"]').val(),
                email: $form.find('input[name="email"]').val(),
                phone: $form.find('input[name="phone"]').val(),
                address: $form.find('textarea[name="address"]').val(),
                cover_letter: $form.find('textarea[name="cover_letter"]').val(),
                resume_text: $('#resume_text_for_ai').val() || ''
            };

            var prevHtml = $btn.html();
            $btn.prop('disabled', true).html(pleaseWait);

            $.easyAjax({
                url: aiGenerateCoverLetterUrl,
                container: '#editForm',
                type: "POST",
                redirect: false,
                data: payload,
                success: function(res) {
                    try {
                        var data = res || {};
                        var $fullName = $form.find('input[name="full_name"]');
                        if ($fullName.val().trim() === '' && data.full_name) $fullName.val(data.full_name);

                        var $email = $form.find('input[name="email"]');
                        if ($email.val().trim() === '' && data.email) $email.val(data.email);

                        var $phone = $form.find('input[name="phone"]');
                        if ($phone.val().trim() === '' && data.phone) $phone.val(data.phone);

                        var $address = $form.find('textarea[name="address"]');
                        if ($address.val().trim() === '' && data.address) $address.val(data.address);

                        var $coverLetter = $form.find('textarea[name="cover_letter"]');
                        if ($coverLetter.val().trim() === '' && data.cover_letter) {
                            $coverLetter.val(data.cover_letter);
                        }
                    } catch (e) {
                        console.error(e);
                    } finally {
                        $btn.prop('disabled', false).html(prevHtml);
                    }
                },
                error: function(response) {
                    $btn.prop('disabled', false).html(prevHtml);
                    handleFails(response);
                }
            });
        });

        function applyResumeAiFields(data, $form) {
            if (!data || typeof data !== 'object') return;
            var $fullName = $form.find('input[name="full_name"]');
            if ($fullName.length && String($fullName.val()).trim() === '' && data.full_name) {
                $fullName.val(data.full_name);
            }
            var $email = $form.find('input[name="email"]');
            if ($email.length && String($email.val()).trim() === '' && data.email) {
                $email.val(data.email);
            }
            var $phone = $form.find('input[name="phone"]');
            if ($phone.length && String($phone.val()).trim() === '' && data.phone) {
                $phone.val(data.phone);
            }
            var $address = $form.find('textarea[name="address"]');
            if ($address.length && String($address.val()).trim() === '' && data.address) {
                $address.val(data.address);
            }
            var $city = $form.find('input[name="city"]');
            if ($city.length && String($city.val()).trim() === '' && data.city) {
                $city.val(data.city);
            }
            var $zipCode = $form.find('input[name="zip_code"]');
            if ($zipCode.length && String($zipCode.val()).trim() === '' && data.zip_code) {
                $zipCode.val(data.zip_code);
            }
            var $coverLetter = $form.find('textarea[name="cover_letter"]');
            if ($coverLetter.length && String($coverLetter.val()).trim() === '' && data.cover_letter) {
                $coverLetter.val(data.cover_letter);
            }
            if ($('#countryId').length && data.country) {
                country = data.country;
                state = data.state || '';
                var loc = new locationInfo();
                loc.getCountries();
            }
            if (data.resume_text) {
                $('#resume_text_for_ai').val(data.resume_text);
            }
        }

        $(document).on('change', '#editForm input[name="resume"]', function() {
            var input = this;
            if (!input.files || !input.files.length) return;

            var fd = new FormData();
            fd.append('_token', csrfToken);
            fd.append('resume', input.files[0]);

            $.ajax({
                url: aiParseResumeUrl,
                type: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                dataType: 'json',
                beforeSend: function() {
                    if (typeof $.easyBlockUI === 'function') {
                        $.easyBlockUI('#editForm');
                    }
                },
                complete: function() {
                    if (typeof $.easyUnblockUI === 'function') {
                        $.easyUnblockUI('#editForm');
                    }
                },
                success: function(response) {
                    if (response && response.status === 'success') {
                        applyResumeAiFields(response, $('#editForm'));
                        if (response.message && typeof $.toast === 'function') {
                            $.toast({
                                text: response.message,
                                position: 'top-right',
                                loaderBg: '#ff6849',
                                icon: 'success',
                                hideAfter: 3500,
                            });
                        }
                    } else if (response && response.status === 'fail' && response.message) {
                        if (typeof $.toast === 'function') {
                            $.toast({
                                text: response.message,
                                position: 'top-right',
                                loaderBg: '#ff6849',
                                icon: 'error',
                                hideAfter: 5000,
                            });
                        }
                    }
                },
                error: function(xhr) {
                    var msg = 'Resume parsing failed.';
                    try {
                        var j = xhr.responseJSON;
                        if (j && j.message) msg = j.message;
                    } catch (e) {}
                    if (typeof $.toast === 'function') {
                        $.toast({
                            text: msg,
                            position: 'top-right',
                            loaderBg: '#ff6849',
                            icon: 'error',
                            hideAfter: 5000,
                        });
                    }
                }
            });
        });

        function handleFails(response) {

            if (typeof response.responseJSON.errors != "undefined") {
                var keys = Object.keys(response.responseJSON.errors);
                $('#editForm').find(".has-error").find(".help-block").remove();
                $('#editForm').find(".has-error").removeClass("has-error");

                for (var i = 0; i < keys.length; i++) {
                    // Escape dot that comes with error in array fields
                    var key = keys[i].replace(".", '\\.');
                    var formarray = keys[i];

                    // If the response has form array
                    if(formarray.indexOf('.') >0){
                        var array = formarray.split('.');
                        response.responseJSON.errors[keys[i]] = response.responseJSON.errors[keys[i]];
                        key = array[0]+'['+array[1]+']';
                    }

                    var ele = $('#editForm').find("[name='" + key + "']");

                    var grp = ele.closest(".form-group");
                    $(grp).find(".help-block").remove();

                    //check if wysihtml5 editor exist
                    var wys = $(grp).find(".wysihtml5-toolbar").length;

                    if(wys > 0){
                        var helpBlockContainer = $(grp);
                    }
                    else{
                        var helpBlockContainer = $(grp).find("div:first");
                    }
                    if($(ele).is(':radio')){
                        helpBlockContainer = $(grp);
                    }

                    if (helpBlockContainer.length == 0) {
                        helpBlockContainer = $(grp);
                    }

                    helpBlockContainer.append('<div class="help-block">' + response.responseJSON.errors[keys[i]] + '</div>');
                    $(grp).addClass("has-error");
                }

                if (keys.length > 0) {
                    var element = $("[name='" + keys[0] + "']");
                    if (element.length > 0) {
                        $("html, body").animate({scrollTop: element.offset().top - 150}, 200);
                    }
                }
            }
        }
    </script>
@endpush