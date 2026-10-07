@extends('layouts.front')

@php
    $applyTitle = ucwords($job->title);
    $titleHtml = e($applyTitle);
    $parts = preg_split('/\s+at\s+/i', $applyTitle, 2);
    if (count($parts) === 2) {
        $titleHtml = e(trim($parts[0])) . ' <span class="text-white">at</span> <span class="font-serif-recruit italic font-normal text-sky-300">' . e(trim($parts[1])) . '</span>';
    }
    $companyLine = $job->company->show_in_frontend == 'true'
        ? ($job->job_company_id && $job->jobCompany ? $job->jobCompany->company_name : $job->company->company_name)
        : '';
@endphp

@section('body-class', 'apply-page-bare')

@push('header-css')
    <link rel="stylesheet" href="{{ asset('assets/plugins/datepicker/datepicker3.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/node_modules_files/switchery/dist/switchery.min.css') }}">
@endpush

@section('content')

{{-- ── Hide navbar & footer ── --}}
<style>
    nav.fr-navbar        { display: none !important; }
    footer.fr-site-footer { display: none !important; }
    .main-content        { padding-top: 0 !important; margin-top: 0 !important; }

    .required:after { content: " *"; color: #ef4444; }
    .has-error input, .has-error select, .has-error textarea {
        border-color: #ef4444 !important;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.08);
    }
</style>

<section class="fr-mini-hero relative px-6 pt-10 pb-12">
    <div class="max-w-6xl mx-auto relative z-10">

        <nav class="fr-breadcrumb flex flex-wrap items-center mb-6">
            <a href="{{ tenant_route('jobs.jobOpenings') }}">@lang('modules.front.jobOpenings')</a>
            <span class="sep">›</span>
            <a href="{{ tenant_route('jobs.jobDetail', [$job->slug, isset($location) && $location ? $location->id : null]) }}" class="cur hover:!text-white">{{ ucwords($job->title) }}</a>
            <span class="sep">›</span>
            <span class="cur">@lang('modules.front.applicationForm')</span>
        </nav>

        <div class="flex flex-wrap items-center gap-0 mb-8 overflow-x-auto pb-2">
            <div class="flex items-center gap-2 shrink-0">
                <div class="fr-step-dot done">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                </div>
                <span class="text-[12px] font-semibold text-white ml-1">@lang('modules.jobs.jobDescription')</span>
            </div>
            <div class="fr-step-line done"></div>
            <div class="flex items-center gap-2 shrink-0">
                <div class="fr-step-dot active"><span>2</span></div>
                <span class="text-[12px] font-semibold text-white ml-1">@lang('modules.front.applicationForm')</span>
            </div>
            <div class="fr-step-line"></div>
            <div class="flex items-center gap-2 shrink-0">
                <div class="fr-step-dot pending"><span>3</span></div>
                <span class="text-[12px] font-semibold text-white/45 ml-1">@lang('modules.front.confirmationStep')</span>
            </div>
        </div>

        <h1 class="text-white font-bold leading-tight tracking-[-0.02em] text-[clamp(22px,3vw,36px)]">
            @lang('modules.front.applyForJob'): <span class="font-serif-recruit italic font-normal text-sky-300">{!! $titleHtml !!}</span>
        </h1>
    </div>
</section>

@php
    $gender = [
        'male'   => __('modules.front.male'),
        'female' => __('modules.front.female'),
        'others' => __('modules.front.others'),
    ];
@endphp

<div id="apply-form-area" class="max-w-6xl mx-auto py-10">
    <form id="createForm" method="POST">
        @csrf
        <input type="hidden" name="job_id"     value="{{ $job->id }}">
        <input type="hidden" name="location_id" @if(isset($location->id)) value="{{ $location->id }}" @else value="" @endif>

        <div class="flex flex-col lg:flex-row gap-8">
            <div class="flex-1 min-w-0 space-y-4">

                {{-- Personal Information --}}
                <div class="fr-form-card">
                    <div class="flex items-center gap-3 mb-6 pb-5 border-b border-[#F0EEE9]">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center bg-[#EFF6FF]">
                            <svg class="w-[18px] h-[18px] text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-[16px] tracking-[-0.01em] text-[#1A1A2A] required">@lang('modules.front.personalInformation')</h3>
                            <p class="text-[12px] text-[#8892A0]">@lang('modules.front.personalInfoSubtitle')</p>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <input class="fr-form-input" type="text" name="full_name" placeholder="@lang('modules.front.fullName')"
                                value="@if ($user) {{ $user->name }} @endif">
                        </div>
                        <div>
                            <input class="fr-form-input" type="email" name="email" placeholder="@lang('modules.front.email')"
                                value="@if ($user) {{ $user->email }} @endif">
                        </div>
                        <div>
                            <input class="fr-form-input" type="tel" name="phone" placeholder="@lang('modules.front.phone')">
                        </div>

                        @if ($job->required_columns['address'])
                        <div>
                            <input class="fr-form-input" type="text" name="address" placeholder="@lang('app.address')" autocomplete="none">
                        </div>
                        @endif

                        @if ($job->required_columns['gender'])
                            <label class="block text-sm font-semibold text-[#1A1A2A] required mb-2">@lang('modules.front.gender')</label>
                            <div class="flex flex-wrap gap-4">
                                @foreach ($gender as $key => $value)
                                    <label class="inline-flex items-center gap-2 cursor-pointer text-[#3D4A5C] text-sm">
                                        <input class="rounded border-[#D1D9E8] text-blue-600 focus:ring-blue-500" type="radio" name="gender" id="{{ $key }}" value="{{ $key }}">
                                        <span>{{ ucfirst($value) }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @endif

                        @if ($job->required_columns['dob'])
                        <div>
                            <input class="fr-form-input dob" type="text" name="dob" placeholder="@lang('modules.front.dob')" autocomplete="none">
                        </div>
                        @endif

                        @if ($job->required_columns['country'])
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <select class="fr-form-input countries" name="country" id="countryId">
                                <option value="0">@lang('modules.front.selectCountry')</option>
                            </select>
                            <select class="fr-form-input states" name="state" id="stateId">
                                <option value="0">@lang('modules.front.selectState')</option>
                            </select>
                            <input class="fr-form-input" type="text"   name="city"     id="cityId"  placeholder="@lang('modules.front.selectCity')">
                            <input class="fr-form-input" type="number" name="zip_code" id="zipCode" placeholder="@lang('modules.front.zipCode')">
                        </div>
                        @endif

                        @if ($job->section_visibility['profile_image'] == 'yes')
                        <div class="pt-2">
                            <p class="text-sm font-semibold text-[#1A1A2A] mb-2">@lang('modules.front.photo')</p>
                            <img src="@if ($user) {{ $user->avatar }} @endif" alt="" class="max-h-24 rounded-lg mb-2">
                            <input type="hidden" name="linkedinPhoto" value="@if ($user) {{ $user->avatar }} @endif">
                            @if ($user)
                                <input type="hidden" name="apply_type" value="linkedin">
                            @endif
                            <input class="block w-full text-sm text-[#8892A0] file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-[#EFF6FF] file:text-blue-700 hover:file:bg-blue-100 select-file"
                                   accept=".png,.jpg,.jpeg" type="file" name="photo">
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Resume --}}
                @if ($job->section_visibility['resume'] == 'yes')
                <div class="fr-form-card">
                    <div class="flex items-center gap-3 mb-6 pb-5 border-b border-[#F0EEE9]">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center bg-[#FFF7ED]">
                            <svg class="w-[18px] h-[18px] text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-[16px] tracking-[-0.01em] text-[#1A1A2A] required">@lang('modules.front.resume')</h3>
                            <p class="text-[12px] text-[#8892A0]">@lang('modules.front.resumeFileType')</p>
                        </div>
                    </div>
                    <input class="select-file text-sm w-full" accept=".png,.jpg,.jpeg,.pdf,.doc,.docx,.xls,.xlsx,.rtf" type="file" name="resume">
                </div>
                @endif

                {{-- Questions --}}
                @if (count($jobQuestion) > 0)
                <div class="fr-form-card">
                    <h3 class="font-bold text-[16px] text-[#1A1A2A] mb-4 pb-3 border-b border-[#F0EEE9]">@lang('modules.front.additionalDetails')</h3>
                    @forelse($jobQuestion as $question)
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-[#1A1A2A] mb-2" for="answer[{{ $question->id }}]">{{ $question->question }}</label>
                            @if ($question->type == 'text')
                                <input class="fr-form-input" type="text" id="answer[{{ $question->id }}]" name="answer[{{ $question->id }}]" placeholder="@lang('modules.front.yourAnswer')">
                            @elseif ($question->type == 'radio')
                                <div class="flex flex-wrap gap-4 mt-1">
                                    @foreach(explode(',', $question->answer_type) as $option)
                                        <label class="inline-flex items-center gap-2 cursor-pointer text-[#3D4A5C] text-sm">
                                            <input class="rounded border-[#D1D9E8] text-blue-600 focus:ring-blue-500"
                                                   type="radio"
                                                   name="answer[{{ $question->id }}]"
                                                   value="{{ trim($option) }}">
                                            <span>{{ trim($option) }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            @else
                                <input type="hidden" name="answer[{{ $question->id }}]">
                                <input class="select-file text-sm w-full" accept=".png,.jpg,.jpeg,.pdf,.doc,.docx,.xls,.xlsx,.rtf"
                                       type="file" id="answer[{{ $question->id }}]" name="answer[{{ $question->id }}]">
                                <p class="text-xs text-[#8892A0] mt-1">@lang('modules.front.resumeFileType')</p>
                            @endif
                        </div>
                    @empty
                    @endforelse
                </div>
                @endif

                {{-- Cover Letter --}}
                @if ($job->section_visibility['cover_letter'] == 'yes')
                <div class="fr-form-card">
                    <div class="flex items-center gap-3 mb-6 pb-5 border-b border-[#F0EEE9]">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center bg-[#F5F3FF]">
                            <svg class="w-[18px] h-[18px] text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-[16px] text-[#1A1A2A]">@lang('modules.front.coverLetter')</h3>
                        </div>
                    </div>
                    <textarea class="fr-form-input" name="cover_letter" rows="5" placeholder="@lang('modules.front.coverLetter')"></textarea>
                </div>
                @endif

                {{-- Terms --}}
                @if ($job->section_visibility['terms_and_conditions'] == 'yes')
                <div class="fr-form-card">
                    <div class="flex items-center gap-3 mb-6 pb-5 border-b border-[#F0EEE9]">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center bg-rose-50">
                            <svg class="w-[18px] h-[18px] text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-[16px] text-[#1A1A2A] required">@lang('modules.front.legalTerm')</h3>
                        </div>
                    </div>
                    <div class="fr-terms-box legal-term mb-5">
                        {!! $applicationSetting->legal_term !!}
                    </div>
                    <div class="switchery-demo">
                        <input type="checkbox" class="js-switch clearfix" id="agree_term" value="yes" data-size="small" name="term_agreement" data-color="#2563eb" />
                        <label for="agree_term" class="ml-2 text-sm font-medium text-[#3D4A5C]"><b>@lang('modules.front.agreeWithTerm')</b></label>
                    </div>
                </div>
                @endif

                {{-- Captcha --}}
                @if ($credentials->job_apply_page == 'active' && $credentials->status == 'active')
                <div id="captcha_container" class="fr-form-card !py-6"></div>
                @endif
                <input type="hidden" name="recaptcha" id="recaptcha">

                {{-- Submit --}}
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-4 pt-2">
                    <button class="fr-btn-lg w-full sm:w-auto flex items-center justify-center gap-2" id="save-form" type="button">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        @lang('modules.front.submitApplication')
                    </button>
                    <a href="{{ tenant_route('jobs.jobDetail', [$job->slug, isset($location) && $location ? $location->id : null]) }}"
                       class="text-center sm:text-left text-[13px] font-semibold text-[#8892A0] py-3">← @lang('app.back')</a>
                </div>

            </div>

            {{-- Sidebar --}}
            <aside class="lg:w-64 shrink-0">
                <div class="sticky top-24 space-y-4">
                    <div class="fr-sidebar-card">
                        <p class="fr-sidebar-label mb-4">@lang('modules.jobApplication.appliedFor')</p>
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 bg-[#EFF6FF]">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            </div>
                            <div>
                                <p class="font-bold text-[14px] leading-snug text-[#1A1A2A]">{{ ucwords($job->title) }}</p>
                                <div class="flex flex-wrap gap-1.5 mt-2">
                                    @if($job->show_job_type && $job->jobType)
                                        <span class="fr-badge text-[10.5px] py-0.5 bg-[#EFF6FF] text-[#1D4ED8]">{{ $job->jobType->job_type }}</span>
                                    @endif
                                    @if(isset($location->location))
                                        <span class="fr-badge text-[10.5px] py-0.5 bg-[#EFF6FF] text-[#1D4ED8]">{{ ucwords($location->location) }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="fr-sidebar-card">
                        <p class="fr-sidebar-label mb-3">@lang('modules.front.tipsHeading')</p>
                        <ul class="flex flex-col gap-3 text-[12.5px] text-[#5A6478]">
                            <li class="flex items-start gap-2.5">
                                <span class="w-4 h-4 rounded-full flex items-center justify-center shrink-0 mt-0.5 bg-emerald-50 text-emerald-600 text-[10px]">✓</span>
                                @lang('modules.front.tipPdf')
                            </li>
                            <li class="flex items-start gap-2.5">
                                <span class="w-4 h-4 rounded-full flex items-center justify-center shrink-0 mt-0.5 bg-emerald-50 text-emerald-600 text-[10px]">✓</span>
                                @lang('modules.front.tipCover')
                            </li>
                            <li class="flex items-start gap-2.5">
                                <span class="w-4 h-4 rounded-full flex items-center justify-center shrink-0 mt-0.5 bg-emerald-50 text-emerald-600 text-[10px]">✓</span>
                                @lang('modules.front.tipContact')
                            </li>
                        </ul>
                    </div>
                </div>
            </aside>
        </div>
    </form>
</div>

@endsection

@push('footer-script')
    <script src="{{ asset('assets/plugins/datepicker/bootstrap-datepicker.js') }}"></script>
    <script src="{{ asset('assets/node_modules_files/switchery/dist/switchery.min.js') }}"></script>

    @if ($credentials->v2_status == 'active' && $credentials->status == 'active')
    <script src="https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit" async defer></script>
    <script>
        var gcv3;
        var onloadCallback = function() {
            gcv3 = grecaptcha.render('captcha_container', {
                'sitekey': '{{ $credentials->v2_site_key }}',
                'theme': 'light',
                'callback': function(response) {
                    if (response) { $('#recaptcha').val(response); }
                },
            });
        };
    </script>
    @endif

    @if ($credentials->v3_status == 'active' && $credentials->status == 'active')
    <script src="https://www.google.com/recaptcha/api.js?render={{ $credentials->v3_site_key }}"></script>
    <script>
        grecaptcha.ready(function() {
            grecaptcha.execute('{{ $credentials->v3_site_key }}', { action: 'contact' })
                .then(function(token) {
                    if (token) { document.getElementById('recaptcha').value = token; }
                });
        });
    </script>
    @endif

    <script>
        $('.js-switch').each(function() {
            new Switchery($(this)[0], $(this).data());
        });
    </script>

    <script>
        const fetchCountryState = "{{ tenant_route('jobs.fetchCountryState') }}";
        const csrfToken         = "{{ csrf_token() }}";
        const selectCountry     = "@lang('modules.front.selectCountry')";
        const selectState       = "@lang('modules.front.selectState')";
        const selectCity        = "@lang('modules.front.selectCity')";
        const pleaseWait        = "@lang('modules.front.pleaseWait')";
        let country = "";
        let state   = "";
    </script>
    <script src="{{ asset('front/assets/js/location.js') }}?v={{ filemtime(public_path('front/assets/js/location.js')) }}"></script>

    <script>
        $('.dob').datepicker({
            autoclose: true,
            format: 'yyyy-mm-dd',
            endDate: (new Date()).toDateString(),
        });

        // ── Email check state ────────────────────────────────────
       // ── Email check state ────────────────────────────────────────
        var emailCheckTimer  = null;
        var lastCheckedEmail = '';

        $('[name="email"]').on('blur', function() {
            checkEmailExists($(this).val());
        }).on('input', function() {
            clearTimeout(emailCheckTimer);
            var val = $(this).val();
            emailCheckTimer = setTimeout(function() {
                checkEmailExists(val);
            }, 700);
        });

        function checkEmailExists(email) {
            email = $.trim(email);
            if (!email || email === lastCheckedEmail) return;
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return;

            lastCheckedEmail = email;
            $('#email-check-result').remove();

            $.ajax({
                url:  '{{ tenant_route('jobs.checkApplicantEmail') }}',
                type: 'POST',
                data: { _token: '{{ csrf_token() }}', email: email, job_id: {{ $job->id }} },
                success: function(res) {
                    if (!res.exists) return; // new email — do nothing

                    // Show simple already-registered message
                   
                }
            });
        }

    // ── Submit ───────────────────────────────────────────────
        $('#save-form').click(function() {
            $.easyAjax({
                url:       '{{ tenant_route('jobs.saveApplication') }}',
                container: '#createForm',
                type:      'POST',
                file:      true,
                redirect:  true,
                success: function(response) {
                    if (response.status == 'success') {
                        var successMsg =
                            '<div class="fr-form-card border-emerald-200 bg-emerald-50/80 text-emerald-900 p-8 text-center rounded-2xl" role="alert">' +
                                '<div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 bg-emerald-100 text-emerald-600 text-2xl">✓</div>' +
                                '<p class="text-lg font-semibold mb-2">' + (response.msg || '') + '</p>' +
                                '<a class="fr-btn-lg inline-block mt-4" href="{{ tenant_route('jobs.jobOpenings') }}">@lang('app.view') @lang('modules.front.jobOpenings')</a>' +
                            '</div>';
                        $('#apply-form-area').html(successMsg);
                    }
                },
                error: function(response) { handleFails(response); }
            });
        });

        // Clear message when email changes
        $('[name="email"]').on('input', function() {
            if ($.trim($(this).val()) !== lastCheckedEmail) {
                $('#email-check-result').remove();
            }
        });

        function handleFails(response) {
            var errors = null;
            if (response && response.responseJSON && typeof response.responseJSON.errors != 'undefined') {
                errors = response.responseJSON.errors;
            } else if (response && typeof response.errors != 'undefined') {
                errors = response.errors;
            }
            if (!errors) return;

            var keys = Object.keys(errors);
            $('#createForm').find('.invalid-feedback, .help-block').remove();
            $('#createForm').find('.is-invalid').removeClass('is-invalid');
            $('#createForm').find('.has-error').removeClass('has-error');

            for (var i = 0; i < keys.length; i++) {
                var key      = keys[i].replace('.', '\\.');
                var formarray = keys[i];
                if (formarray.indexOf('.') > 0) {
                    var arr = formarray.split('.');
                    key = arr[0] + '[' + arr[1] + ']';
                }
                var ele = $('#createForm').find("[name='" + key + "']");
                var grp = ele.closest('.form-group');
                if (grp.length === 0) grp = ele.closest('div');
                $(grp).find('.help-block').remove();

                var helpBlockContainer = $(grp).find('.wysihtml5-toolbar').length
                    ? $(grp)
                    : $(grp).find('div:first');
                if ($(ele).is(':radio')) helpBlockContainer = $(grp);
                if (helpBlockContainer.length == 0) helpBlockContainer = $(grp);

                helpBlockContainer.append(
                    '<div class="text-red-600 text-sm mt-1">' + errors[keys[i]] + '</div>'
                );
                $(grp).addClass('has-error');
                $(ele).addClass('border-red-500');
            }

            if (keys.length > 0) {
                var first = $("[name='" + keys[0] + "']");
                if (first.length > 0) first[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    </script>
@endpush