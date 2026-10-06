@php
    $isEdit = $isEdit ?? false;
    $selectedLocationIds = $isEdit ? $jobLocationData : (isset($jobLocation) ? $jobLocation : []);
    $required_columns = [
        'gender' => false,
        'dob' => false,
        'country' => false,
        'address' => false,
    ];
    $requiredColumns = [
        'gender' => __('modules.front.gender'),
        'dob' => __('modules.front.dob'),
        'country' => __('modules.front.country'),
        'address' => __('app.address'),
    ];
    $section_visibility = [
        'profile_image' => 'yes',
        'resume' => 'yes',
        'cover_letter' => 'yes',
        'terms_and_conditions' => 'yes',
    ];
    $sectionVisibility = [
        'profile_image' => __('modules.jobs.profileImage'),
        'resume' => __('modules.jobs.resume'),
        'cover_letter' => __('modules.jobs.coverLetter'),
        'terms_and_conditions' => __('modules.jobs.termsAndConditions'),
    ];
    $startDateVal = $job && $job->start_date
        ? $job->start_date->format('Y-m-d')
        : \Carbon\Carbon::now()->format('Y-m-d');
    $endDateVal = $job && $job->end_date
        ? $job->end_date->format('Y-m-d')
        : \Carbon\Carbon::now()->addMonth()->format('Y-m-d');
@endphp

<div class="job-form-page pb-10 text-[#0F1F3D]">
    @if (count($locations) == 0)
        <div class="mb-4 rounded-2xl border border-red-200 bg-red-50 p-5 text-red-900 shadow-sm">
            <h4 class="mb-2 flex items-center gap-2 text-base font-semibold">
                <i class="fa fa-triangle-exclamation text-red-600" aria-hidden="true"></i> Job Location Empty!
            </h4>
            <p class="text-sm leading-relaxed text-red-800/90">
                You do not have any Location created. You need to create the Job location first to add the job.
                <a href="{{ route('admin.locations.create') }}"
                    class="ml-2 inline-flex items-center gap-1.5 rounded-xl bg-[#0F1F3D] px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-[#1B3560]">
                    <i class="fa fa-plus-circle" aria-hidden="true"></i> @lang('app.createNew') @lang('menu.locations')
                </a>
            </p>
        </div>
    @elseif(count($categories) == 0)
        <div class="mb-4 rounded-2xl border border-red-200 bg-red-50 p-5 text-red-900 shadow-sm">
            <h4 class="mb-2 flex items-center gap-2 text-base font-semibold">
                <i class="fa fa-triangle-exclamation text-red-600" aria-hidden="true"></i> Job Category Empty!
            </h4>
            <p class="text-sm leading-relaxed text-red-800/90">
                You do not have any Job Category created. You need to create a category first to add the job.
                <a href="{{ route('admin.job-categories.create') }}"
                    class="ml-2 inline-flex items-center gap-1.5 rounded-xl bg-[#0F1F3D] px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-[#1B3560]">
                    <i class="fa fa-plus-circle" aria-hidden="true"></i> @lang('app.createNew') @lang('menu.jobCategories')
                </a>
            </p>
        </div>
    @else
      

        <div class="flex flex-col gap-5 xl:flex-row">
            <div class="min-w-0 flex flex-1 flex-col gap-4">
                <form class="ajax-form flex flex-col gap-4" method="POST" id="createForm">
                    @csrf
                    @if ($isEdit)
                        @method('PUT')
                    @endif
                    {{-- Basic Information --}}
                    <div class="overflow-hidden rounded-2xl border border-[#E8E6E1] bg-white">
                        <div class="flex items-center gap-3 border-b border-gray-100 px-5 py-4">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50">
                                <svg class="h-[18px] w-[18px] text-[#2563EB]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-[15px] font-bold text-[#0F1F3D]">@lang('app.basic') @lang('app.information')</h3>
                                <p class="mt-0.5 text-[12px] text-[#8892A0]">@lang('modules.jobs.jobTitle') · @lang('app.company')</p>
                            </div>
                        </div>
                        <div class="flex flex-col gap-4 p-5">
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                               <div class="form-group mb-0">
                                    <label class="mb-1.5 block text-[11.5px] font-bold uppercase tracking-wide text-slate-500">
                                        @lang('Company') <span class="text-red-500">*</span>
                                    </label>

                                    {{-- Existing Company Dropdown --}}
                                    <select
                                        name="company"
                                        id="company"
                                        class="job-form-sel form-control w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-[13px] text-[#0F1F3D] outline-none transition-all focus:border-[#2563EB] focus:ring-2 focus:ring-[#2563EB]/10"
                                        onchange="toggleNewCompanyField(this)"
                                    >
                                        <option value="">@lang('Select Company')</option>

                                        {{-- Add New Option FIRST --}}
                                        <option value="new">@lang('Add New Company')</option>

                                        {{-- Existing companies --}}
                                        @foreach ($companies as $comp)
                                            <option 
                                                value="{{ $comp->id }}"
                                                @if ($job && $job->company_id == $comp->id) selected @endif
                                            >
                                                {{ ucwords($comp->company_name) }}
                                            </option>
                                        @endforeach
                                    </select>

                                    {{-- New Company Text Field --}}
                                    <div id="new-company-wrapper" class="mt-3 hidden">
                                        <input
                                            type="text"
                                            id="company_new"
                                            name="company_new"
                                            placeholder="{{ __('Add New Company') }}"
                                            class="form-control w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-[13px] text-[#0F1F3D] placeholder:text-gray-300 outline-none transition-all focus:border-[#2563EB] focus:ring-2 focus:ring-[#2563EB]/10"
                                            value="{{ old('company_new') }}"
                                        >
                                    </div>
                                </div>

                                <script>
                                    function toggleNewCompanyField(select) {
                                        const wrapper = document.getElementById('new-company-wrapper');

                                        if (select.value === 'new') {
                                            wrapper.classList.remove('hidden');
                                        } else {
                                            wrapper.classList.add('hidden');
                                        }
                                    }

                                    document.addEventListener('DOMContentLoaded', function () {
                                        const companySelect = document.getElementById('company');

                                        if (companySelect.value === 'new') {
                                            document.getElementById('new-company-wrapper')
                                                .classList.remove('hidden');
                                        }
                                    });
                                </script>

                                <div class="form-group mb-0">
                                    <label class="mb-1.5 block text-[11.5px] font-bold uppercase tracking-wide text-slate-500">
                                        @lang('Company Location')
                                    </label>
                                    <input
                                        type="text"
                                        id="company_location"
                                        name="company_location"
                                        placeholder="{{ __('e.g. New York, USA') }}"
                                        class="form-control w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-[13px] text-[#0F1F3D] placeholder:text-gray-300 outline-none transition-all focus:border-[#2563EB] focus:ring-2 focus:ring-[#2563EB]/10"
                                        value="{{ old('company_location', $job->company_location ?? '') }}"
                                    >
                                </div>
                              <div class="form-group mb-0">
                                <label class="mb-1.5 block text-[11.5px] font-bold uppercase tracking-wide text-slate-500">
                                    @lang('menu.jobCategories') <span class="text-red-500">*</span>
                                </label>

                                {{-- Job Category Dropdown --}}
                                <select
                                    name="category_id"
                                    id="category_id"
                                    class="job-form-sel form-control w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-[13px] text-[#0F1F3D] outline-none transition-all focus:border-[#2563EB] focus:ring-2 focus:ring-[#2563EB]/10"
                                    onchange="toggleNewCategoryField(this)"
                                >
                                    <option value="">@lang('Choose job Categories')</option>

                                    {{-- Add New Category FIRST --}}
                                    <option value="new">@lang('Add New Category')</option>

                                    {{-- Existing categories --}}
                                    @foreach ($categories as $category)
                                        <option 
                                            value="{{ $category->id }}"
                                            @if ($job && $job->category_id == $category->id) selected @endif
                                        >
                                            {{ ucfirst($category->name) }}
                                        </option>
                                    @endforeach
                                </select>

                                {{-- New Category Field --}}
                                <div id="new-category-wrapper" class="mt-3 hidden">
                                    <input
                                        type="text"
                                        id="category_new"
                                        name="category_new"
                                        placeholder="{{ __('Add New Category') }}"
                                        class="form-control w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-[13px] text-[#0F1F3D] placeholder:text-gray-300 outline-none transition-all focus:border-[#2563EB] focus:ring-2 focus:ring-[#2563EB]/10"
                                        value="{{ old('category_new') }}"
                                    >
                                </div>
                            </div>

                            <script>
                                function toggleNewCategoryField(select) {
                                    const wrapper = document.getElementById('new-category-wrapper');

                                    if (select.value === 'new') {
                                        wrapper.classList.remove('hidden');
                                    } else {
                                        wrapper.classList.add('hidden');
                                    }
                                }

                                document.addEventListener('DOMContentLoaded', function () {
                                    const categorySelect = document.getElementById('category_id');

                                    if (categorySelect.value === 'new') {
                                        document.getElementById('new-category-wrapper')
                                            .classList.remove('hidden');
                                    }
                                });
                            </script>
                            </div>
                            <div class="form-group mb-0">
                                <div class="mb-1.5 flex items-center justify-between gap-2">
                                    <label for="title" class="block text-[11.5px] font-bold uppercase tracking-wide text-slate-500">@lang('modules.jobs.jobTitle') <span class="text-red-500">*</span></label>
                                    <button
                                        type="button"
                                        id="ai-generate-job-content-btn"
                                        class="inline-flex items-center gap-1.5 rounded-lg border border-[#2563EB]/25 bg-[#EFF6FF] px-2.5 py-1.5 text-[11px] font-semibold text-[#2563EB] transition hover:bg-[#DBEAFE] disabled:cursor-not-allowed disabled:opacity-50"
                                        data-url="{{ route('admin.jobs.ai-generate-content') }}"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3l1.912 5.813a2 2 0 001.268 1.268L21 12l-5.82 1.919a2 2 0 00-1.261 1.261L12 21l-1.919-5.82a2 2 0 00-1.261-1.261L3 12l5.813-1.919a2 2 0 001.268-1.268L12 3z"/></svg>
                                        <span id="ai-generate-job-content-label">@lang('app.aiGenerate')</span>
                                    </button>
                                </div>
                                <input type="text" class="form-control w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-[13px] text-[#0F1F3D] placeholder:text-gray-300 outline-none transition-all focus:border-[#2563EB] focus:ring-2 focus:ring-[#2563EB]/10" name="title" id="title" value="{{ $job ? $job->title : '' }}">
                            </div>
                            <div class="form-group mb-0">
                                <label for="job_code" class="mb-1.5 block text-[11.5px] font-bold uppercase tracking-wide text-slate-500">Job Code</label>
                                <input type="text" class="form-control w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-[13px] text-[#0F1F3D] placeholder:text-gray-300 outline-none transition-all focus:border-[#2563EB] focus:ring-2 focus:ring-[#2563EB]/10" name="job_code" id="job_code" maxlength="100" value="{{ $job ? $job->job_code : '' }}" placeholder="e.g. CSR-2026-001">
                            </div>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div class="form-group mb-0 flex min-h-0 flex-col">
                                    <label for="job_type" class="mb-1.5 block text-[11.5px] font-bold uppercase tracking-wide text-slate-500">@lang('modules.jobs.jobType')</label>
                                    <select name="job_type_id" id="job_type" class="job-form-sel form-control w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-[13px] text-[#0F1F3D] outline-none transition-all focus:border-[#2563EB] focus:ring-2 focus:ring-[#2563EB]/10">
                                        <option value="">—</option>
                                        @foreach ($jobTypes as $jobType)
                                            <option @if ($job && $job->job_type_id == $jobType->id) selected @endif value="{{ $jobType->id }}">{{ $jobType->job_type }}</option>
                                        @endforeach
                                    </select>
                                    <div class="mt-2.5 flex items-stretch gap-2.5">
                                        <button type="button" title="@lang('app.add') @lang('modules.jobs.jobType')" id="addJobType" class="inline-flex h-11 w-11 shrink-0 items-center justify-center self-start rounded-xl bg-[#2563EB] text-sm text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500/30" aria-label="@lang('app.add') @lang('modules.jobs.jobType')"><i class="fa fa-plus" aria-hidden="true"></i></button>
                                        <label class="flex min-h-11 flex-1 cursor-pointer items-center gap-2.5 rounded-xl border border-gray-100 bg-[#F8F7F4]/40 px-3 py-2 text-left text-[12px] font-medium leading-snug text-slate-600 transition hover:border-gray-200">
                                            <input @if ($job && $job->show_job_type) checked @endif type="checkbox" value="yes" name="show_job_type" class="flat-red shrink-0 rounded border-gray-300 text-primary focus:ring-primary">
                                            <span class="min-w-0 flex-1">@lang('modules.jobs.showJobType')</span>
                                        </label>
                                    </div>
                                </div>
                                <div class="form-group mb-0">
                                    <label for="total_positions" class="mb-1.5 block text-[11.5px] font-bold uppercase tracking-wide text-slate-500">@lang('modules.jobs.totalPositions') <span class="text-red-500">*</span></label>
                                    <div class="flex items-center overflow-hidden rounded-xl border border-gray-200 transition-all focus-within:border-[#2563EB] focus-within:ring-2 focus-within:ring-[#2563EB]/10">
                                        <button type="button" class="job-pos-minus flex h-11 w-10 shrink-0 items-center justify-center border-0 bg-[#F8F7F4] text-xl font-bold text-[#8892A0] transition-colors hover:bg-gray-100 hover:text-[#2563EB]" aria-label="-1">−</button>
                                        <input type="number" name="total_positions" id="total_positions" min="1" max="999" value="{{ $job ? $job->total_positions : 1 }}" class="h-11 w-full flex-1 border-0 bg-white text-center text-[14px] font-bold text-[#0F1F3D] outline-none">
                                        <button type="button" class="job-pos-plus flex h-11 w-10 shrink-0 items-center justify-center border-0 bg-[#F8F7F4] text-xl font-bold text-[#8892A0] transition-colors hover:bg-gray-100 hover:text-[#2563EB]" aria-label="+1">+</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

             
                   {{-- Job Description & Requirement Combined --}}
                    <div class="overflow-hidden rounded-2xl border border-[#E8E6E1] bg-white">

                        {{-- Header --}}
                        <div class="flex items-center gap-3 border-b border-gray-100 px-5 py-4">

                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-purple-50">

                                <svg class="h-[18px] w-[18px] text-purple-600"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                    aria-hidden="true">

                                    <path stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.8"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>

                                </svg>

                            </div>

                            <div>

                                <h3 class="text-[15px] font-bold text-[#0F1F3D]">
                                    Job Description & Requirements
                                </h3>

                                <p class="mt-0.5 text-[12px] text-[#8892A0]">
                                    Add complete job details, responsibilities, qualifications and requirements
                                </p>

                            </div>

                        </div>

                        {{-- Content --}}
                        <div class="p-5">

                            @php

                                $combinedContent = old(
                                    'job_description',

                                    ($job->job_description ?? '') .

                                    (
                                        !empty($job->job_requirement)

                                        ? '

                                        <hr>

                                        <h3><strong>Requirements</strong></h3>

                                        ' . $job->job_requirement

                                        : ''
                                    )
                                );

                            @endphp

                           @include('admin.jobs.partials.job-rich-field', [
                                'name' => 'job_description',
                                'textareaId' => 'job_description',
                                'editorId' => 'job_description_editor',
                                'label' => __('modules.jobs.jobDescription'),
                                'placeholder' => __('modules.jobs.jobRichDescriptionPlaceholder'),
                                'required' => true,
                                'showLinkButton' => true,
                                'value' => old('job_description', $job->job_description ?? ''),
                            ])

                            {{-- Hidden field for backend compatibility --}}
                            <input
                                type="hidden"
                                name="job_requirement"
                                id="job_requirement_hidden"
                            >

                        </div>

                    </div>

                    <script>

                    document.addEventListener('DOMContentLoaded', function () {

                        const saveBtn = document.getElementById('save-form');

                        if (saveBtn) {

                            saveBtn.addEventListener('click', function () {

                                let content = '';

                                // Quill editor
                                if (typeof quillJobDescription !== 'undefined') {

                                    content = quillJobDescription.root.innerHTML;

                                }

                                // Fallback
                                else {

                                    const textarea = document.getElementById('job_description');

                                    if (textarea) {

                                        content = textarea.value;

                                    }

                                }

                                // Save same content in requirement field
                                document.getElementById('job_requirement_hidden').value = content;

                            });

                        }

                    });

                    </script>

                    {{-- Job details — overflow-visible so portaled dropdowns (Select2) are not clipped --}}
                    <div class="overflow-visible rounded-2xl border border-[#E8E6E1] bg-white">
                        <div class="flex items-center gap-3 border-b border-gray-100 px-5 py-4">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-orange-50">
                                <svg class="h-[18px] w-[18px] text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                            </div>
                            <div>
                                <h3 class="text-[15px] font-bold text-[#0F1F3D]">@lang('modules.jobs.formJobDetails')</h3>
                                <p class="mt-0.5 text-[12px] text-[#8892A0]">@lang('menu.locations'), @lang('menu.skills'), @lang('app.dates')</p>
                            </div>
                        </div>
                        <div class="flex flex-col gap-4 p-5">
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                               <div class="form-group mb-0">
                                    <label class="mb-1.5 block text-[11.5px] font-bold uppercase tracking-wide text-slate-500">
                                        @lang('Locations')
                                    </label>

                                    {{-- Location Dropdown --}}
                                    <select 
                                        name="location_id[]" 
                                        id="location_id" 
                                        multiple="multiple"
                                        class="form-control select2"
                                        onchange="toggleNewLocationField(this)"
                                    >

                                        {{-- Add New Location FIRST --}}
                                        <option value="new">@lang('Add New Location')</option>

                                        {{-- Existing Locations --}}
                                        @foreach ($locations as $location)
                                            <option 
                                                value="{{ $location->id }}"
                                                @if (in_array($location->id, $selectedLocationIds)) selected @endif
                                            >
                                                {{ ucfirst($location->location) . ($location->country ? ' (' . $location->country->country_code . ')' : '') }}
                                            </option>
                                        @endforeach
                                    </select>

                                    {{-- New Location Field --}}
                                    <div id="new-location-wrapper" class="mt-3 hidden">
                                        <input
                                            type="text"
                                            id="location_new"
                                            name="location_new"
                                            placeholder="{{ __('Add New Location') }}"
                                            class="form-control w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-[13px] text-[#0F1F3D] placeholder:text-gray-300 outline-none transition-all focus:border-[#2563EB] focus:ring-2 focus:ring-[#2563EB]/10"
                                            value="{{ old('location_new') }}"
                                        >
                                    </div>
                                </div>

                                <script>
                                    function toggleNewLocationField(select) {
                                        const wrapper = document.getElementById('new-location-wrapper');

                                        let values = $(select).val();

                                        if (values && values.includes('new')) {
                                            wrapper.classList.remove('hidden');
                                        } else {
                                            wrapper.classList.add('hidden');
                                        }
                                    }

                                    document.addEventListener('DOMContentLoaded', function () {
                                        let values = $('#location_id').val();

                                        if (values && values.includes('new')) {
                                            document.getElementById('new-location-wrapper')
                                                .classList.remove('hidden');
                                        }
                                    });
                                </script>
                                <div class="form-group mb-0">
                                    <label for="status" class="mb-1.5 block text-[11.5px] font-bold uppercase tracking-wide text-slate-500">
                                        @lang('app.status')
                                    </label>
                                    <select name="status" id="status"
                                        class="job-form-sel form-control w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-[13px] text-[#0F1F3D] outline-none transition-all focus:border-[#2563EB] focus:ring-2 focus:ring-[#2563EB]/10">
                                        <option value="active" {{ $job && $job->status == 'active' ? 'selected' : '' }}>
                                            Active
                                        </option>
                                        <option value="ats" {{ $job && $job->status == 'ats' ? 'selected' : '' }}>
                                            ATS Only
                                        </option>
                                        <option value="inactive" {{ $job && $job->status == 'inactive' ? 'selected' : '' }}>
                                            Inactive
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group mb-0">
                                <label for="job_skills" class="mb-1.5 block text-[11.5px] font-bold uppercase tracking-wide text-slate-500">@lang('menu.skills')</label>
                                <select class="form-control select2 select2-multiple w-full" id="job_skills" multiple="multiple" data-placeholder="@lang('app.add') @lang('menu.skills')" name="skill_id[]" style="width: 100%;">
                                    @if ($job)
                                        @foreach ($skills as $skill)
                                            <option
                                                @foreach ($job->skills as $jskill)
                                                    @if ($skill->id == $jskill->skill_id) selected @endif
                                                @endforeach
                                                value="{{ $skill->id }}">{{ ucwords($skill->name) }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div class="form-group mb-0">
                                    <label for="date-start" class="mb-1.5 block text-[11.5px] font-bold uppercase tracking-wide text-slate-500">@lang('app.startDate')</label>
                                    <input type="text" class="form-control w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-[13px] text-[#0F1F3D] outline-none transition-all focus:border-[#2563EB] focus:ring-2 focus:ring-[#2563EB]/10" id="date-start" value="{{ $startDateVal }}" name="start_date">
                                </div>
                                <div class="form-group mb-0">
                                    <label for="date-end" class="mb-1.5 block text-[11.5px] font-bold uppercase tracking-wide text-slate-500">@lang('app.endDate')</label>
                                    <input type="text" class="form-control w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-[13px] text-[#0F1F3D] outline-none transition-all focus:border-[#2563EB] focus:ring-2 focus:ring-[#2563EB]/10" id="date-end" name="end_date" value="{{ $endDateVal }}">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div class="form-group mb-0 flex min-h-0 flex-col">
                                    <label for="work_experience" class="mb-1.5 block text-[11.5px] font-bold uppercase tracking-wide text-slate-500">@lang('modules.jobs.workExperience')</label>
                                    <select name="work_experience_id" id="work_experience" class="job-form-sel form-control w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-[13px] text-[#0F1F3D] outline-none transition-all focus:border-[#2563EB] focus:ring-2 focus:ring-[#2563EB]/10">
                                        <option value="">—</option>
                                        @foreach ($workExperiences as $workExperience)
                                            <option @if ($job && $job->work_experience_id == $workExperience->id) selected @endif value="{{ $workExperience->id }}">{{ $workExperience->work_experience }}</option>
                                        @endforeach
                                    </select>
                                    <div class="mt-2.5 flex items-stretch gap-2.5">
                                        <button type="button" title="@lang('app.add') @lang('modules.jobs.workExperience')" id="addWorkExperience" class="inline-flex h-11 w-11 shrink-0 items-center justify-center self-start rounded-xl bg-[#2563EB] text-sm text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500/30" aria-label="@lang('app.add') @lang('modules.jobs.workExperience')"><i class="fa fa-plus" aria-hidden="true"></i></button>
                                        <label class="flex min-h-11 flex-1 cursor-pointer items-center gap-2.5 rounded-xl border border-gray-100 bg-[#F8F7F4]/40 px-3 py-2 text-left text-[12px] font-medium leading-snug text-slate-600 transition hover:border-gray-200">
                                            <input @if ($job && $job->show_work_experience) checked @endif type="checkbox" value="yes" name="show_work_experience" class="flat-red shrink-0 rounded border-gray-300 text-primary focus:ring-primary">
                                            <span class="min-w-0 flex-1">@lang('modules.jobs.showWorkExperience')</span>
                                        </label>
                                    </div>
                                </div>
                                <div class="form-group mb-0 flex min-h-0 flex-col">
                                    <label for="paytype" class="mb-1.5 block text-[11.5px] font-bold uppercase tracking-wide text-slate-500">@lang('modules.jobs.showPayBy')</label>
                                    <select name="pay_type" id="paytype" class="job-form-sel form-control w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-[13px] text-[#0F1F3D] outline-none transition-all focus:border-[#2563EB] focus:ring-2 focus:ring-[#2563EB]/10">
                                        <option value="">—</option>
                                        <option @if ($job && $job->pay_type == 'Range') selected @endif value="Range">@lang('modules.jobs.range')</option>
                                        <option @if ($job && $job->pay_type == 'Starting') selected @endif value="Starting">@lang('modules.jobs.startingSalary')</option>
                                        <option @if ($job && $job->pay_type == 'Maximum') selected @endif value="Maximum">@lang('modules.jobs.maximumSalary')</option>
                                        <option @if ($job && $job->pay_type == 'Exact Amount') selected @endif value="Exact Amount">@lang('modules.jobs.exactSalary')</option>
                                    </select>
                                    <label class="mt-2.5 flex min-h-11 cursor-pointer items-center gap-2.5 rounded-xl border border-gray-100 bg-[#F8F7F4]/40 px-3 py-2 text-left text-[12px] font-medium leading-snug text-slate-600 transition hover:border-gray-200">
                                        <input @if ($job && $job->show_salary) checked @endif type="checkbox" value="yes" name="show_salary" class="flat-red shrink-0 rounded border-gray-300 text-primary focus:ring-primary">
                                        <span class="min-w-0 flex-1">@lang('modules.jobs.showSalary')</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="hidden rounded-2xl border border-dashed border-[#E8E6E1] bg-white p-5" id="amount_field">
                    <style>
                        .job-salary-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:16px; }
                        .job-salary-grid > div { min-width:0; }
                        .job-salary-grid input, .job-salary-grid select { width:100%; min-width:0; }
                        @media(max-width:1023px) { .job-salary-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
                        @media(max-width:575px) { .job-salary-grid { grid-template-columns:1fr; } }
                    </style>
                    {{-- Salary details --}}
                    <div class="job-salary-grid">
                    <div class="form-group mb-0">
                        <label for="salary_currency_id" class="mb-1.5 block text-[11.5px] font-bold uppercase tracking-wide text-slate-500">
                            currency <span class="text-red-500">*</span>
                        </label>
                        <select
                            name="currency_id"
                            id="salary_currency_id"
                            class="job-form-sel form-control"
                        >
                            <option value="">-- Select Currency --</option>

                            @foreach($currencySettings as $currency)
                                <option
                                    value="{{ $currency->id }}"
                                    data-symbol="{{ $currency->currency_symbol }}"
                                    {{ old('currency_id',
                                        isset($job) && $job ? $job->currency_id : $global->currency_id
                                    ) == $currency->id ? 'selected' : '' }}
                                >
                                    {{ strtoupper($currency->currency_code) }}
                                    - {{ $currency->currency_name }}
                                    ({{ $currency->currency_symbol }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group pay_according mb-0 hidden" id="payaccording">
                        <label for="pay_according" class="mb-1.5 block text-[11.5px] font-bold uppercase tracking-wide text-slate-500">@lang('modules.jobs.rate')</label>
                        <select name="pay_according" id="pay_according" class="job-form-sel form-control w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-[13px] text-[#0F1F3D] outline-none transition-all focus:border-[#2563EB] focus:ring-2 focus:ring-[#2563EB]/10">
                            <option value="">—</option>
                            <option @if ($job && $job->pay_according == 'Hour') selected @endif value="Hour">@lang('modules.jobs.hour')</option>
                            <option @if ($job && $job->pay_according == 'Day') selected @endif value="Day">@lang('modules.jobs.day')</option>
                            <option @if ($job && $job->pay_according == 'Week') selected @endif value="Week">@lang('modules.jobs.week')</option>
                            <option @if ($job && $job->pay_according == 'Month') selected @endif value="Month">@lang('modules.jobs.month')</option>
                            <option @if ($job && $job->pay_according == 'Year') selected @endif value="Year">@lang('modules.jobs.year')</option>
                        </select>
                    </div>
                        <div class="form-group mb-0" id="start_amt">
                            <label for="startingSalary" class="mb-1.5 block text-[11.5px] font-bold uppercase tracking-wide text-slate-500">
                                @lang('modules.jobs.startingSalary')
                            </label>
                            <div class="relative">
                                <span id="salary-symbol-start" class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-[13px] font-bold text-slate-400"></span>
                                <input
                                    class="form-control rounded-xl border border-gray-200 py-2.5 text-[13px] pl-8 pr-3.5"
                                    type="number"
                                    id="startingSalary"
                                    name="starting_salary"
                                    value="{{ $job ? $job->starting_salary : null }}"
                                >
                            </div>
                        </div>
                        <div class="form-group mb-0 hidden" id="end_amt">
                            <label for="maximunSalary" class="mb-1.5 block text-[11.5px] font-bold uppercase tracking-wide text-slate-500">
                                @lang('modules.jobs.maximumSalary')
                            </label>
                            <div class="relative">
                                <span id="salary-symbol-end" class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-[13px] font-bold text-slate-400"></span>
                                <input
                                    class="form-control rounded-xl border border-gray-200 py-2.5 text-[13px] pl-8 pr-3.5"
                                    type="number"
                                    id="maximunSalary"
                                    name="maximum_salary"
                                    value="{{ $job ? $job->maximum_salary : null }}"
                                >
                            </div>
                        </div>
                    </div>
                </div>



                    {{-- ── HOMEPAGE VISIBILITY ── --}}

@if (count($questions) > 0)

<div class="overflow-hidden rounded-2xl border border-[#E8E6E1] bg-white job-page-question-wrapper">

    {{-- Header --}}
    <div class="border-b border-gray-100 px-5 py-4 flex items-center justify-between">
        <div>
            <h3 class="text-[15px] font-bold text-[#0F1F3D]">
                @lang('modules.front.questions')
            </h3>
            <p class="mt-0.5 text-[12px] text-[#8892A0]">
                Select questions for this job
            </p>
        </div>
        <a href="{{ route('admin.questions.create') }}"
           target="_blank"
           class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-3 py-2 text-xs font-medium text-white hover:bg-blue-700 transition">
            <i class="fa fa-plus"></i>
            Add Question
        </a>
    </div>

    {{-- Search bar --}}
    <div class="px-5 pt-4 pb-2">
        <div class="relative">
            <i class="fa fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
            <input
                type="text"
                id="question-search"
                placeholder="Search questions..."
                class="w-full rounded-xl border border-gray-200 bg-white pl-9 pr-4 py-2.5 text-[13px] text-[#0F1F3D] placeholder:text-gray-300 outline-none transition-all focus:border-[#2563EB] focus:ring-2 focus:ring-[#2563EB]/10"
                oninput="filterQuestions()"
            >
        </div>
        <div class="mt-2 flex items-center justify-between">
            <span id="question-count-label" class="text-[11px] text-gray-400"></span>
            <span id="question-selected-label" class="text-[11px] font-semibold text-blue-600"></span>
        </div>
    </div>

    {{-- Questions list --}}
    <div id="question-list" class="space-y-3 px-5 pb-4">
        @foreach ($questions as $question)
            @php $isResumeQuestion = strtolower(trim($question->question)) === 'upload resume'; @endphp
            <label
                data-question-text="{{ strtolower($question->question) }}"
                class="question-item flex items-start justify-between gap-4 rounded-xl border border-gray-200 bg-white p-4 cursor-pointer hover:border-blue-300 hover:bg-blue-50/30 transition {{ $isResumeQuestion ? 'border-blue-300 bg-blue-50/40' : '' }}">

                <div class="flex items-start gap-3 flex-1">
                    <div class="pt-0.5">
                        <input type="checkbox"
                               name="question[]"
                               value="{{ $question->id }}"
                               class="question-checkbox h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                               @if($isResumeQuestion || in_array($question->id, $jobQuestion ?? [])) checked @endif
                               @if($isResumeQuestion) onclick="return false;" @endif
                               onchange="updateSelectedCount()">
                    </div>
                    <div class="flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h4 class="text-sm font-semibold text-gray-800">
                                {{ $question->question }}
                            </h4>
                            <span class="rounded bg-blue-100 px-2 py-0.5 text-[10px] font-medium text-blue-700">
                                {{ ucfirst($question->type) }}
                            </span>
                            @if($question->required == 'yes')
                                <span class="rounded bg-red-100 px-2 py-0.5 text-[10px] font-medium text-red-700">Required</span>
                            @endif
                            @if($isResumeQuestion)
                                <span class="rounded bg-blue-100 px-2 py-0.5 text-[10px] font-medium text-blue-700">Always required</span>
                            @endif
                            @if($question->is_knockout)
                                <span class="rounded bg-amber-100 px-2 py-0.5 text-[10px] font-medium text-amber-700">Knockout</span>
                            @endif

                            {{-- Job usage count badge --}}
                            @php $usedInJobs = $jobUsageCounts[$question->id] ?? 0; @endphp
                            <span class="rounded px-2 py-0.5 text-[10px] font-medium inline-flex items-center gap-1
                                {{ $usedInJobs > 0 ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-400' }}">
                                <i class="fa fa-briefcase text-[9px]"></i>
                                {{ $usedInJobs > 0 ? 'Used in ' . $usedInJobs . ' job' . ($usedInJobs > 1 ? 's' : '') : 'Not used yet' }}
                            </span>
                        </div>
                        @if($question->type == 'radio' && !empty($question->answer_type))
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach(explode(',', $question->answer_type) as $option)
                                    @php $option = trim($option); $isKnockout = $question->knockout_answer == $option; @endphp
                                    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px]
                                        {{ $isKnockout ? 'bg-red-100 text-red-700 border border-red-200' : 'bg-gray-100 text-gray-700 border border-gray-200' }}">
                                        {{ $option }}
                                        @if($isKnockout)<i class="fa fa-ban text-[9px]"></i>@endif
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <a href="{{ route('admin.questions.edit', $question->id) }}"
                   target="_blank"
                   onclick="event.stopPropagation();"
                   class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-blue-200 bg-blue-50 text-blue-600 hover:bg-blue-100 transition"
                   title="Edit Question">
                    <i class="fa fa-pencil"></i>
                </a>
            </label>
        @endforeach
    </div>

    {{-- Pagination --}}
    <div id="question-pagination" class="flex items-center justify-between border-t border-gray-100 px-5 py-3">
        <button type="button"
                id="q-prev-btn"
                onclick="changePage(-1)"
                class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-2 text-[12px] font-medium text-gray-600 transition hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed">
            <i class="fa fa-chevron-left text-[10px]"></i> Previous
        </button>
        <span id="q-page-info" class="text-[12px] text-gray-500"></span>
        <button type="button"
                id="q-next-btn"
                onclick="changePage(1)"
                class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-2 text-[12px] font-medium text-gray-600 transition hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed">
            Next <i class="fa fa-chevron-right text-[10px]"></i>
        </button>
    </div>

</div>

<script>
(function () {
    const PER_PAGE = 5;
    let currentPage = 1;
    let filtered = [];

    function allItems() {
        return Array.from(document.querySelectorAll('.question-item'));
    }

    function getFilteredItems() {
        const q = (document.getElementById('question-search').value || '').toLowerCase().trim();
        return allItems().filter(el =>
            !q || el.dataset.questionText.includes(q)
        );
    }

    function render() {
        const items = getFilteredItems();
        filtered = items;
        const total = items.length;
        const totalPages = Math.max(1, Math.ceil(total / PER_PAGE));
        if (currentPage > totalPages) currentPage = totalPages;

        const start = (currentPage - 1) * PER_PAGE;
        const end   = start + PER_PAGE;

        allItems().forEach(el => el.style.display = 'none');
        items.forEach((el, i) => {
            el.style.display = (i >= start && i < end) ? 'flex' : 'none';
        });

        document.getElementById('q-page-info').textContent =
            total === 0
                ? 'No results'
                : `Page ${currentPage} of ${totalPages}  (${total} question${total !== 1 ? 's' : ''})`;

        document.getElementById('question-count-label').textContent =
            total < allItems().length ? `Showing ${total} of ${allItems().length} questions` : `${total} question${total !== 1 ? 's' : ''}`;

        document.getElementById('q-prev-btn').disabled = currentPage <= 1;
        document.getElementById('q-next-btn').disabled = currentPage >= totalPages;

        updateSelectedCount();
    }

    window.filterQuestions = function () {
        currentPage = 1;
        render();
    };

    window.changePage = function (dir) {
        const totalPages = Math.max(1, Math.ceil(getFilteredItems().length / PER_PAGE));
        currentPage = Math.min(Math.max(1, currentPage + dir), totalPages);
        render();
    };

    window.updateSelectedCount = function () {
        const count = document.querySelectorAll('.question-checkbox:checked').length;
        const el = document.getElementById('question-selected-label');
        el.textContent = count > 0 ? `${count} selected` : '';
    };

    document.addEventListener('DOMContentLoaded', render);
})();
</script>

@endif

                    <div @if(!$isEdit) hidden style="display:none" @endif class="overflow-hidden rounded-2xl border border-[#E8E6E1] bg-white">
                        <div class="border-b border-gray-100 px-5 py-4">
                            <h3 class="text-[15px] font-bold text-[#0F1F3D]">@lang('app.askApplicantsFor')</h3>
                        </div>
                        <div class="p-5">
                            <div class="flex flex-wrap gap-3">
                                @if ($job)
                                    @foreach ($job->required_columns as $key => $value)
                                        <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-gray-100 bg-[#F8F7F4]/40 px-3 py-2 text-[13px] font-medium text-slate-600 transition hover:border-gray-200">
                                            <input @if ($value) checked @endif type="checkbox" value="{{ $key }}" name="{{ $key }}" class="flat-red rounded border-gray-300 text-primary focus:ring-primary">
                                            {{ ucfirst(__($requiredColumns[$key])) }}
                                        </label>
                                    @endforeach
                                @else
                                    @foreach ($required_columns as $key => $value)
                                        <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-gray-100 bg-[#F8F7F4]/40 px-3 py-2 text-[13px] font-medium text-slate-600 transition hover:border-gray-200">
                                            <input @if ($value) checked @endif type="checkbox" value="{{ $key }}" name="{{ $key }}" class="flat-red rounded border-gray-300 text-primary focus:ring-primary">
                                            {{ ucfirst(__($requiredColumns[$key])) }}
                                        </label>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- <div class="overflow-hidden rounded-2xl border border-[#E8E6E1] bg-white">
                        <div class="border-b border-gray-100 px-5 py-4">
                            <h3 class="text-[15px] font-bold text-[#0F1F3D]">@lang('modules.jobs.sectionVisibility')</h3>
                        </div>
                        <div class="p-5">
                            <div class="flex flex-wrap gap-3">
                                @if ($job)
                                    @foreach ($job->section_visibility as $key => $value)
                                        <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-gray-100 bg-[#F8F7F4]/40 px-3 py-2 text-[13px] font-medium text-slate-600 transition hover:border-gray-200">
                                            <input @if ($value == 'yes') checked @endif type="checkbox" value="yes" name="{{ $key }}" class="flat-red rounded border-gray-300 text-primary focus:ring-primary">
                                            {{ ucfirst(__($sectionVisibility[$key])) }}
                                        </label>
                                    @endforeach
                                @else
                                    @foreach ($section_visibility as $key => $value)
                                        <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-gray-100 bg-[#F8F7F4]/40 px-3 py-2 text-[13px] font-medium text-slate-600 transition hover:border-gray-200">
                                            <input type="checkbox" value="yes" name="{{ $key }}" class="flat-red rounded border-gray-300 text-primary focus:ring-primary">
                                            {{ ucfirst(__($sectionVisibility[$key])) }}
                                        </label>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    </div> -->

                    {{-- SEO --}}
                    <div class="overflow-hidden rounded-2xl border border-[#E8E6E1] bg-white">
                        <div class="flex items-center gap-3 border-b border-gray-100 px-5 py-4">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-50">
                                <svg class="h-[18px] w-[18px] text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-[15px] font-bold text-[#0F1F3D]">@lang('modules.jobs.metaTitle')</h3>
                                <p class="mt-0.5 text-[12px] text-[#8892A0]">@lang('modules.jobs.metaDescription')</p>
                            </div>
                        </div>
                        <div class="flex flex-col gap-4 p-5">
                            <div class="form-group mb-0">
                                <label for="meta-title" class="mb-1.5 block text-[11.5px] font-bold uppercase tracking-wide text-slate-500">@lang('modules.jobs.metaTitle')</label>
                                <input type="text" id="meta-title" class="form-control w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-[13px] text-[#0F1F3D] outline-none transition-all focus:border-[#2563EB] focus:ring-2 focus:ring-[#2563EB]/10" name="meta_title" value="{{ $job ? data_get($job->meta_details, 'title', '') : '' }}">
                            </div>
                            <div class="form-group mb-0">
                                <label for="meta-description" class="mb-1.5 block text-[11.5px] font-bold uppercase tracking-wide text-slate-500">@lang('modules.jobs.metaDescription')</label>
                                <textarea id="meta-description" class="form-control min-h-[5.5rem] w-full resize-none rounded-xl border border-gray-200 px-3.5 py-2.5 text-[13px] text-[#0F1F3D] outline-none transition-all focus:border-[#2563EB] focus:ring-2 focus:ring-[#2563EB]/10" name="meta_description" rows="3">{{ $job ? data_get($job->meta_details, 'description', '') : '' }}</textarea>
                            </div>
                        </div>
                    </div>

                </form>
            </div>

            {{-- Sidebar preview + tips --}}
            <div class="flex w-full shrink-0 flex-col gap-4 xl:w-[260px] xl:sticky xl:top-4 xl:self-start">
                <div class="overflow-hidden rounded-2xl border border-[#E8E6E1] bg-white">
                    <div class="bg-gradient-to-br from-[#0F1F3D] to-[#1B3560] px-4 py-4">
                        <p class="mb-1 text-[10px] font-bold uppercase tracking-widest text-white/30">@lang('app.preview')</p>
                        <h4 id="job-preview-title" class="text-[15px] font-bold leading-snug text-white">{{ $job ? $job->title : __('modules.jobs.jobTitle') }}</h4>
                        <p id="job-preview-company" class="mt-1 text-[12px] text-white/50">—</p>
                        <div class="mt-3 flex flex-wrap gap-1.5">
                            <span id="job-preview-type" class="rounded-full bg-white/10 px-2.5 py-1 text-[10.5px] font-bold text-white/70">—</span>
                            <span id="job-preview-category" class="rounded-full bg-[#2563EB]/30 px-2.5 py-1 text-[10.5px] font-bold text-blue-200">—</span>
                        </div>
                    </div>
                    <div class="flex flex-col gap-3 p-4">
                        <div class="flex items-center gap-2 text-[12.5px] text-slate-500">
                            <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span id="job-preview-location">—</span>
                        </div>
                        <div class="flex items-center gap-2 text-[12.5px] text-slate-500">
                            <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span id="job-preview-openings">—</span>
                        </div>
                        <div class="flex items-center gap-2 text-[12.5px] text-slate-500">
                            <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span id="job-preview-dates">—</span>
                        </div>
                        <div class="flex items-center justify-between border-t border-gray-100 pt-3">
                            <span id="job-preview-status" class="rounded-full bg-emerald-50 px-2.5 py-1 text-[11.5px] font-semibold text-emerald-600">@lang('app.active')</span>
                            <span id="job-preview-skills" class="ml-2 truncate text-[11.5px] text-gray-400">—</span>
                        </div>
                    </div>
                </div>

                {{-- ── Pipeline Statuses (sticky sidebar card) ── --}}
                <div class="rounded-2xl border border-[#E8E6E1] bg-white">
                    <div class="flex items-center justify-between gap-2 border-b border-[#F0EEE9] px-4 py-3">
                        <div class="flex items-center gap-2">
                            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7"/></svg>
                            </div>
                            <p class="text-[12.5px] font-bold text-[#0F1F3D]">Pipeline Statuses</p>
                        </div>
                        <button type="button" id="add-job-status-btn"
                            class="inline-flex items-center gap-1 rounded-lg bg-[#2563EB] px-2.5 py-1.5 text-[11px] font-semibold text-white shadow-sm transition hover:bg-[#1d4ed8] focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                            Add Status
                        </button>
                    </div>

                    {{-- Status rows --}}
                    <div id="job-status-list" class="flex flex-col gap-0 divide-y divide-[#F5F4F0] px-3 py-2 min-h-[150px] max-h-[340px] overflow-y-auto">
                        @forelse($jobStatuses ?? collect() as $idx => $st)
                        @php
                            $isAppliedStatus = mb_strtolower(trim($st->status)) === 'applied';
                        @endphp
                        <div class="job-status-row flex items-center gap-2 py-2" data-index="{{ $idx }}" data-fixed="{{ $isAppliedStatus ? '1' : '0' }}">
                            <span class="{{ $isAppliedStatus ? 'cursor-not-allowed text-[#D8DCE3]' : 'js-drag-handle cursor-grab text-[#C4CBD4] hover:text-[#8892A0]' }} touch-none select-none" title="{{ $isAppliedStatus ? 'Applied is always the first stage' : 'Drag to reorder' }}">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/></svg>
                            </span>
                            <input type="hidden" name="job_status_id[]" value="{{ $st->id }}">
                            <input type="color" name="job_status_color[]" value="{{ $st->color ?? '#2563EB' }}"
                                class="h-6 w-6 shrink-0 rounded border-0 p-0 shadow-none {{ $isAppliedStatus ? 'cursor-not-allowed opacity-70' : 'cursor-pointer' }}" style="padding:1px;" @disabled($isAppliedStatus)>
                            <input type="text" name="job_status_name[]" value="{{ $st->status }}" placeholder="Stage name"
                                class="min-w-0 flex-1 rounded-lg border border-[#E2DED8] px-2.5 py-1.5 text-[12px] text-[#1A1E2E] focus:outline-none {{ $isAppliedStatus ? 'cursor-not-allowed bg-[#F1F3F7] font-semibold' : 'bg-[#FAFAF8] focus:border-blue-400 focus:ring-1 focus:ring-blue-400/30' }}" @readonly($isAppliedStatus)>
                            @if($isAppliedStatus)
                            <span class="rounded-md bg-blue-50 px-1.5 py-1 text-[9px] font-bold uppercase tracking-wide text-blue-600">Fixed</span>
                            @endif
                            @if(!$isAppliedStatus)
                            <button type="button" class="js-remove-status flex h-6 w-6 shrink-0 items-center justify-center rounded-lg text-[#C4CBD4] transition hover:bg-red-50 hover:text-red-500">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                            @endif
                        </div>
                        @empty
                        <div id="js-status-empty" class="py-5 text-center">
                            <p class="text-[12px] text-[#8892A0]">No pipeline statuses yet.</p>
                        </div>
                        @endforelse
                    </div>

                    <p class="border-t border-[#F5F4F0] px-4 py-2.5 text-[10.5px] text-[#B0B8C4]">
                        Drag to reorder &middot; statuses assigned to applicants cannot be removed
                    </p>
                </div>

                {{-- Form actions sit directly below the pipeline on create and edit. --}}
                <div class="flex w-full flex-col gap-2 sm:flex-row xl:flex-col">
                    <button type="button" id="save-form" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#2563EB] px-6 py-3 text-[13.5px] font-bold text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-blue-700 hover:shadow-lg hover:shadow-blue-500/25 focus:outline-none focus:ring-2 focus:ring-blue-500/30 active:translate-y-0">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        @lang('app.save')
                    </button>
                    <a href="{{ route('admin.jobs.index') }}" class="inline-flex w-full items-center justify-center rounded-xl border border-[#E2DED8] bg-white px-6 py-3 text-[13.5px] font-semibold text-[#8892A0] transition hover:bg-gray-100 hover:text-slate-700">@lang('app.cancel')</a>
                </div>

            </div>
        </div>

        {{-- Ajax modal --}}
        <div class="fixed inset-0 z-50 hidden overflow-y-auto" id="addDepartmentModal" role="dialog" aria-labelledby="job-aux-modal-title" aria-modal="true" aria-hidden="true">
            <div class="flex min-h-full w-full items-center justify-center p-4 sm:p-6">
                <div class="fixed inset-0 z-0 bg-black/50 backdrop-blur-[2px] transition-opacity" aria-hidden="true" onclick="typeof closeJobAuxModal==='function'&&closeJobAuxModal()"></div>
                <div class="relative z-10 flex max-h-[min(90vh,calc(100dvh-2rem))] w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-[#E8E6E1] bg-white shadow-2xl shadow-[#0F1F3D]/10">
                    <div class="flex shrink-0 items-center justify-between gap-4 border-b border-gray-100 bg-[#FAFAF8] px-5 py-4">
                        <div class="flex min-w-0 items-center gap-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-[#2563EB]" aria-hidden="true">
                                <i class="fa fa-plus text-sm" aria-hidden="true"></i>
                            </div>
                            <h4 class="truncate text-[15px] font-bold tracking-tight text-[#0F1F3D]" id="job-aux-modal-title">@lang('app.department')</h4>
                        </div>
                        <button type="button" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-[#8892A0] transition hover:bg-white hover:text-[#0F1F3D] focus:outline-none focus:ring-2 focus:ring-[#2563EB]/20" onclick="typeof closeJobAuxModal==='function'&&closeJobAuxModal()" aria-label="@lang('app.close')"><i class="fa fa-times text-base" aria-hidden="true"></i></button>
                    </div>
                    <div class="modal-data-shell min-h-0 flex-1">
                        <div class="max-h-[min(70vh,520px)] min-h-[10rem] overflow-y-auto p-6 text-left text-[13px] text-[#0F1F3D]">@lang('app.loading')</div>
                    </div>
                    <div class="flex shrink-0 justify-end border-t border-gray-100 bg-[#FAFAF8] px-5 py-3">
                        <button type="button" class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-[13px] font-semibold text-[#64748B] shadow-sm transition hover:border-gray-300 hover:bg-white hover:text-[#0F1F3D] focus:outline-none focus:ring-2 focus:ring-[#2563EB]/15" onclick="typeof closeJobAuxModal==='function'&&closeJobAuxModal()">@lang('app.close')</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
<script>
function handleDualInput(inputEl, key) {
    const hasText = inputEl.value.trim().length > 0;
    const sel = document.getElementById(
        key === 'location' ? 'location_id' :
        key === 'category' ? 'category_id' : 'company'
    );
    if (!sel) return;

    sel.style.opacity = hasText ? '0.4' : '1';
    sel.style.pointerEvents = hasText ? 'none' : '';

    if (hasText) {
        // Clear any existing selection
        Array.from(sel.options).forEach(o => o.selected = false);
        sel.value = '';
        // Notify Select2 if used
        if (typeof $ !== 'undefined' && $(sel).data('select2')) {
            $(sel).val(null).trigger('change');
        }
    }
}
$(document).ready(function () {

    function updateCurrencySymbol() {
        let symbol = $('#salary_currency_id option:selected').data('symbol') || '';

        $('#salary-symbol-start').text(symbol);
        $('#salary-symbol-end').text(symbol);
    }

    updateCurrencySymbol();

    $('#salary_currency_id').on('change', function () {
        updateCurrencySymbol();
    });

});
function handleDualSelect(selEl, key) {
    const inputEl = document.getElementById(
        key === 'location' ? 'location_new' :
        key === 'category' ? 'category_new' : 'company_new'
    );
    if (!inputEl) return;

    const hasSelection = Array.from(selEl.options).some(o => o.selected && o.value);
    inputEl.style.opacity = hasSelection ? '0.4' : '1';
    inputEl.disabled = hasSelection;
    if (hasSelection) inputEl.value = '';
}

// Wire up Select2 changes (since Select2 bypasses native onchange for multi-selects)
$(document).ready(function () {
    $('#location_id').on('change', function () {
        handleDualSelect(this, 'location');
    });
    $('#category_id').on('change', function () {
        handleDualSelect(this, 'category');
    });
    $('#company').on('change', function () {
        handleDualSelect(this, 'company');
    });
});



    function updateSalarySymbol() {
        var sel = document.getElementById('salary_currency_id');
        if (!sel) return;
        var opt = sel.options[sel.selectedIndex];
        var sym = opt ? (opt.getAttribute('data-symbol') || '') : '';
        var els = ['salary-symbol-start', 'salary-symbol-end'];
        els.forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.textContent = sym;
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var sel = document.getElementById('salary_currency_id');
        if (sel) {
            sel.addEventListener('change', updateSalarySymbol);
            updateSalarySymbol();
        }
    });

</script>

<script>
// ── Pipeline status rows (Add / Remove / Drag-to-reorder) ────────
(function () {
    var palette = ['#2563EB','#10B981','#F59E0B','#EF4444','#8B5CF6','#EC4899','#06B6D4','#64748B'];
    var paletteIdx = 0;

    function syncEmpty() {
        var list  = document.getElementById('job-status-list');
        var empty = document.getElementById('js-status-empty');
        if (!list) return;
        var rows = list.querySelectorAll('.job-status-row');
        if (empty) empty.style.display = rows.length ? 'none' : '';
    }

    function makeRow(name, color) {
        name  = name  || '';
        color = color || palette[paletteIdx++ % palette.length];

        var div = document.createElement('div');
        div.className = 'job-status-row flex items-center gap-2 py-2';

        div.innerHTML =
            '<span class="js-drag-handle cursor-grab text-[#C4CBD4] hover:text-[#8892A0] touch-none select-none">' +
                '<svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/></svg>' +
            '</span>' +
            '<input type="hidden" name="job_status_id[]" value="">' +
            '<input type="color" name="job_status_color[]" value="' + color + '" ' +
                'class="h-6 w-6 shrink-0 cursor-pointer rounded border-0 p-0 shadow-none" style="padding:1px;">' +
            '<input type="text" name="job_status_name[]" value="' + name + '" placeholder="Stage name" ' +
                'class="min-w-0 flex-1 rounded-lg border border-[#E2DED8] bg-[#FAFAF8] px-2.5 py-1.5 text-[12px] text-[#1A1E2E] focus:border-blue-400 focus:outline-none focus:ring-1 focus:ring-blue-400/30">' +
            '<button type="button" class="js-remove-status flex h-6 w-6 shrink-0 items-center justify-center rounded-lg text-[#C4CBD4] transition hover:bg-red-50 hover:text-red-500">' +
                '<svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>' +
            '</button>';

        return div;
    }

    // Add button
    var addBtn = document.getElementById('add-job-status-btn');
    if (addBtn) {
        addBtn.addEventListener('click', function () {
            var list = document.getElementById('job-status-list');
            if (!list) return;
            var row = makeRow();
            list.appendChild(row);
            syncEmpty();
            // Focus the new text input
            var inp = row.querySelector('input[type="text"]');
            if (inp) inp.focus();
        });
    }

    // Remove button (delegated)
    var list = document.getElementById('job-status-list');
    if (list) {
        list.addEventListener('click', function (e) {
            var btn = e.target.closest('.js-remove-status');
            if (!btn) return;
            var row = btn.closest('.job-status-row');
            if (row.dataset.fixed === '1') return;
            row.remove();
            syncEmpty();
        });
    }

    syncEmpty();

    // ── Drag-to-reorder via drag handle ──────────────────────────
    var dragSrc = null;

    document.addEventListener('dragstart', function (e) {
        var row = e.target.closest('.job-status-row');
        if (!row) return;
        if (row.dataset.fixed === '1') { e.preventDefault(); return; }
        // Only allow drag when originating from the handle
        if (!e.target.closest('.js-drag-handle')) { e.preventDefault(); return; }
        dragSrc = row;
        setTimeout(function () { row.style.opacity = '0.4'; }, 0);
        e.dataTransfer.effectAllowed = 'move';
    });

    document.addEventListener('dragend', function (e) {
        var row = e.target.closest('.job-status-row');
        if (row) row.style.opacity = '';
        document.querySelectorAll('.job-status-row').forEach(function (r) {
            r.style.borderTop = '';
        });
        dragSrc = null;
    });

    document.addEventListener('dragover', function (e) {
        if (!dragSrc) return;
        var target = e.target.closest('.job-status-row');
        if (!target || target === dragSrc || target.dataset.fixed === '1') return;
        e.preventDefault();
        // Visual indicator
        document.querySelectorAll('.job-status-row').forEach(function (r) { r.style.borderTop = ''; });
        var rect  = target.getBoundingClientRect();
        var after = e.clientY > rect.top + rect.height / 2;
        if (!after) target.style.borderTop = '2px solid #2563EB';
    });

    document.addEventListener('drop', function (e) {
        if (!dragSrc) return;
        var target = e.target.closest('.job-status-row');
        if (!target || target === dragSrc || target.dataset.fixed === '1') return;
        e.preventDefault();
        document.querySelectorAll('.job-status-row').forEach(function (r) { r.style.borderTop = ''; });
        var rect  = target.getBoundingClientRect();
        var after = e.clientY > rect.top + rect.height / 2;
        if (after) { target.after(dragSrc); } else { target.before(dragSrc); }
    });

    // Make existing rows draggable
    document.querySelectorAll('.job-status-row').forEach(function (row) {
        row.setAttribute('draggable', row.dataset.fixed === '1' ? 'false' : 'true');
    });

    // MutationObserver so newly added rows also get draggable
    if (list) {
        new MutationObserver(function (mutations) {
            mutations.forEach(function (m) {
                m.addedNodes.forEach(function (node) {
                    if (node.classList && node.classList.contains('job-status-row')) {
                        node.setAttribute('draggable', 'true');
                    }
                });
            });
        }).observe(list, { childList: true });
    }
})();

// ── Inject sidebar pipeline inputs into the form before submit ───
// The #save-form button submits via $.easyAjax using $('#createForm').serialize(),
// so we inject hidden inputs into #createForm before that serialize() call runs.
// We hook #save-form click with a capturing listener (runs before jQuery handlers).
(function () {
    var form = document.getElementById('createForm');
    if (!form) return;

    function injectStatusInputs() {
        form.querySelectorAll('.js-pipeline-hidden').forEach(function (el) { el.remove(); });

        var list = document.getElementById('job-status-list');
        if (!list) return;

        list.querySelectorAll('.job-status-row').forEach(function (row) {
            var nameEl  = row.querySelector('input[name="job_status_name[]"]');
            var colorEl = row.querySelector('input[name="job_status_color[]"]');
            var idEl    = row.querySelector('input[name="job_status_id[]"]');

            var name  = nameEl  ? nameEl.value.trim()  : '';
            var color = colorEl ? colorEl.value        : '#2563EB';
            var id    = idEl    ? idEl.value           : '';

            if (!name) return;

            function hi(n, v) {
                var el = document.createElement('input');
                el.type = 'hidden';
                el.name = n;
                el.value = v;
                el.className = 'js-pipeline-hidden';
                form.appendChild(el);
            }

            hi('job_status_name[]',  name);
            hi('job_status_color[]', color);
            hi('job_status_id[]',    id);
        });
    }

    // Use capture phase so this fires BEFORE jQuery's bubble-phase click handler
    // that calls $('#createForm').serialize() inside the $.easyAjax call.
    document.addEventListener('click', function (e) {
        var btn = e.target && e.target.closest ? e.target.closest('#save-form') : null;
        if (btn) { injectStatusInputs(); }
    }, true); // true = capture phase

    // Also cover native form submit fallback
    form.addEventListener('submit', injectStatusInputs);
})();
</script>

