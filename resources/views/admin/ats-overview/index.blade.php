@extends('layouts.app')

@section('page-title-html')
    ATS <em>Overview</em>
@endsection

@section('page-subtitle')
    Live applicant totals and pipeline activity for every active job.
@endsection

@section('content')
    <div class="mb-5 grid gap-4 sm:grid-cols-2">
        <div class="rounded-2xl border border-[#E3E7EE] bg-white p-5 shadow-sm">
            <p class="text-[12px] font-semibold uppercase tracking-[0.08em] text-[#8892A0]">Active Jobs</p>
            <p id="ats-visible-job-count" class="mt-2 text-3xl font-bold tracking-tight text-[#1A1E2E]">{{ number_format($activeJobCount) }}</p>
        </div>
        <div class="rounded-2xl border border-[#E3E7EE] bg-white p-5 shadow-sm">
            <p class="text-[12px] font-semibold uppercase tracking-[0.08em] text-[#8892A0]">Applicants in Active Jobs</p>
            <p id="ats-visible-applicant-count" class="mt-2 text-3xl font-bold tracking-tight text-[#1A1E2E]">{{ number_format($activeApplicantCount) }}</p>
        </div>
    </div>

    <div class="mb-5 rounded-2xl border border-[#E3E7EE] bg-white p-4 shadow-sm">
        <div class="mb-3 flex items-center justify-between">
            <div>
                <h2 class="text-[14px] font-bold text-[#1A1E2E]">Filter Active Jobs</h2>
                <p class="mt-0.5 text-[11.5px] text-[#8892A0]">Use one or more filters to narrow the overview.</p>
            </div>
            <button type="button" id="ats-filter-reset" class="inline-flex items-center gap-1.5 rounded-lg border border-[#DDE3EC] bg-white px-3 py-2 text-[11.5px] font-semibold text-[#697386] transition hover:border-[#EF4444] hover:text-[#EF4444]">
                <i class="fa fa-refresh text-[10px]"></i> Reset
            </button>
        </div>
        <div class="grid gap-3 md:grid-cols-3">
            <div>
                <label for="ats-filter-job" class="mb-1.5 block text-[10.5px] font-bold uppercase tracking-[0.07em] text-[#8892A0]">Job</label>
                <select id="ats-filter-job" class="w-full rounded-xl border border-[#DDE3EC] bg-[#F8F9FB] px-3 py-2.5 text-[12.5px] font-medium text-[#3D4A5C] outline-none focus:border-[#2563EB]">
                    <option value="">All Jobs</option>
                    @foreach($jobs->sortBy('title') as $filterJob)
                        <option value="{{ $filterJob->id }}">{{ $filterJob->title }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="ats-filter-company" class="mb-1.5 block text-[10.5px] font-bold uppercase tracking-[0.07em] text-[#8892A0]">Company Name</label>
                <select id="ats-filter-company" class="w-full rounded-xl border border-[#DDE3EC] bg-[#F8F9FB] px-3 py-2.5 text-[12.5px] font-medium text-[#3D4A5C] outline-none focus:border-[#2563EB]">
                    <option value="">All Companies</option>
                    @foreach($filterCompanies as $filterCompany)
                        <option value="{{ $filterCompany->id }}">{{ $filterCompany->company_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="ats-filter-location" class="mb-1.5 block text-[10.5px] font-bold uppercase tracking-[0.07em] text-[#8892A0]">Location</label>
                <select id="ats-filter-location" class="w-full rounded-xl border border-[#DDE3EC] bg-[#F8F9FB] px-3 py-2.5 text-[12.5px] font-medium text-[#3D4A5C] outline-none focus:border-[#2563EB]">
                    <option value="">All Locations</option>
                    @foreach($filterLocations as $filterLocation)
                        <option value="{{ $filterLocation->id }}">{{ $filterLocation->location }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-[#E3E7EE] bg-white shadow-sm">
        <div class="border-b border-[#E9ECF1] px-5 py-4">
            <h2 class="text-[16px] font-bold text-[#1A1E2E]">Active Job Pipeline</h2>
            <p class="mt-1 text-[12.5px] text-[#697386]">Click a job to view its applicants. Hover over the applicant total or status button to see the complete pipeline.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[920px] border-collapse text-left">
                <thead>
                    <tr class="bg-[#F8F9FB] text-[11px] font-bold uppercase tracking-[0.07em] text-[#7D8796]">
                        <th class="w-[30%] px-5 py-3.5">Job Title</th>
                        <th class="w-[22%] px-5 py-3.5">Company Name</th>
                        <th class="w-[18%] px-5 py-3.5">Job Location</th>
                        <th class="w-[15%] px-5 py-3.5 text-center">Total Applicants</th>
                        <th class="w-[15%] px-5 py-3.5 text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($jobs as $job)
                        @php($applicationUrl = route('admin.job-applications.table', ['jobs' => $job->id, 'from' => 'ats-overview']))
                        <tr class="ats-job-row border-t border-[#E9ECF1] transition-colors hover:bg-[#FAFBFD]"
                            data-job-id="{{ $job->id }}"
                            data-company-id="{{ $job->company_id }}"
                            data-applicant-count="{{ $job->applicant_count }}"
                            data-location-ids="|{{ $job->overview_location_ids->implode('|') }}|">
                            <td class="px-5 py-4">
                                <a href="{{ $applicationUrl }}" class="group inline-flex max-w-full items-center gap-2">
                                    <span class="truncate text-[14px] font-bold text-[#1A1E2E] group-hover:text-[#2563EB]">{{ $job->title }}</span>
                                    <span class="text-lg leading-none text-[#A0A8B5] group-hover:text-[#2563EB]">&rsaquo;</span>
                                </a>
                            </td>
                            <td class="px-5 py-4 text-[13px] font-medium text-[#4D586B]">
                                {{ optional($job->company)->company_name ?: 'No company' }}
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap gap-1.5">
                                    @forelse($job->overview_locations as $overviewLocation)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-[#F1F3F7] px-2.5 py-1 text-[11px] font-semibold text-[#5A6478]">
                                            <i class="fa fa-map-marker text-[10px] text-[#8892A0]"></i>
                                            {{ $overviewLocation }}
                                        </span>
                                    @empty
                                        <span class="text-[12px] text-[#A0A8B5]">No location</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <div class="ats-status-hover relative inline-flex">
                                    <a href="{{ $applicationUrl }}" class="inline-flex min-w-[54px] items-center justify-center rounded-xl bg-[#EEF4FF] px-4 py-2 text-[18px] font-bold text-[#2563EB] hover:bg-[#E2ECFF]">
                                        {{ number_format($job->applicant_count) }}
                                    </a>
                                    @include('admin.ats-overview.status-tooltip', ['statuses' => $job->statuses])
                                </div>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <div class="ats-status-hover relative inline-flex">
                                    <button type="button" class="inline-flex items-center gap-2 rounded-xl border border-[#DDE3EC] bg-white px-3.5 py-2 text-[12px] font-semibold text-[#4D586B] shadow-sm hover:border-[#BFC9D8] hover:text-[#2563EB]">
                                        View Status
                                        <span class="text-[#9AA4B2]">&#9662;</span>
                                    </button>
                                    @include('admin.ats-overview.status-tooltip', ['statuses' => $job->statuses])
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-16 text-center">
                                <p class="text-[15px] font-semibold text-[#3D4A5C]">No active jobs</p>
                                <p class="mt-1 text-[12.5px] text-[#8892A0]">Open jobs will appear here once their start date is reached.</p>
                            </td>
                        </tr>
                    @endforelse
                    @if($jobs->isNotEmpty())
                        <tr id="ats-filter-empty" class="hidden">
                            <td colspan="5" class="px-6 py-14 text-center">
                                <p class="text-[14px] font-semibold text-[#3D4A5C]">No jobs match these filters</p>
                                <p class="mt-1 text-[12px] text-[#8892A0]">Change a filter or click Reset to show active jobs.</p>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('head-script')
    <style>
        .ats-status-tooltip {
            position: fixed;
            z-index: 60;
            width: 290px;
            visibility: hidden;
            opacity: 0;
            pointer-events: none;
            transition: opacity .15s ease, visibility .15s;
        }
        .ats-status-hover:hover .ats-status-tooltip,
        .ats-status-hover:focus-within .ats-status-tooltip,
        .ats-status-hover.is-status-open .ats-status-tooltip {
            visibility: visible;
            opacity: 1;
            pointer-events: auto;
        }
        .ats-status-applicants {
            display: none;
            position: fixed;
            z-index: 80;
            width: 280px;
            max-height: min(420px, calc(100vh - 24px));
            overflow-y: auto;
            border: 1px solid #DCE2EB;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 14px 36px rgba(28,39,60,.2);
            scrollbar-width: thin;
        }
        .ats-status-stage:hover .ats-status-applicants {
            display: block;
        }
    </style>
@endpush

@push('footer-script')
    <script>
        (function () {
            function positionAtsStatusTooltip(wrapper) {
                var tooltip = wrapper.querySelector('.ats-status-tooltip');
                if (!tooltip) return;

                var trigger = wrapper.getBoundingClientRect();
                var width = Math.min(290, window.innerWidth - 24);
                tooltip.style.width = width + 'px';

                var left = Math.max(12, Math.min(trigger.right - width, window.innerWidth - width - 12));
                var height = tooltip.offsetHeight || 260;
                var top = trigger.bottom - 1;

                if (top + height > window.innerHeight - 12) {
                    top = Math.max(12, trigger.top - height + 1);
                }

                tooltip.style.left = left + 'px';
                tooltip.style.top = top + 'px';
            }

            document.querySelectorAll('.ats-status-hover').forEach(function (wrapper) {
                var closeTimer;

                function openTooltip() {
                    window.clearTimeout(closeTimer);
                    wrapper.classList.add('is-status-open');
                    positionAtsStatusTooltip(wrapper);
                }

                function closeTooltip() {
                    window.clearTimeout(closeTimer);
                    closeTimer = window.setTimeout(function () {
                        wrapper.classList.remove('is-status-open');
                    }, 250);
                }

                wrapper.addEventListener('mouseenter', function () {
                    openTooltip();
                });
                wrapper.addEventListener('focusin', function () {
                    openTooltip();
                });
                wrapper.addEventListener('mouseleave', closeTooltip);
                wrapper.addEventListener('focusout', closeTooltip);

                var tooltip = wrapper.querySelector('.ats-status-tooltip');
                if (tooltip) {
                    tooltip.addEventListener('mouseenter', openTooltip);
                    tooltip.addEventListener('mouseleave', closeTooltip);
                }
            });

            document.querySelectorAll('.ats-status-stage').forEach(function (stage) {
                stage.addEventListener('mouseenter', function () {
                    var panel = stage.querySelector('.ats-status-applicants');
                    if (!panel) return;

                    var row = stage.getBoundingClientRect();
                    var width = Math.min(280, window.innerWidth - 24);
                    panel.style.width = width + 'px';

                    var left = row.right;
                    if (left + width > window.innerWidth - 12) {
                        left = row.left - width;
                    }
                    left = Math.max(12, Math.min(left, window.innerWidth - width - 12));

                    // Use the rendered (max-height constrained) panel height,
                    // not the full scrollHeight. A long stage such as Rejected
                    // must remain beside the hovered row instead of jumping to
                    // the top of the viewport.
                    var height = panel.getBoundingClientRect().height
                        || Math.min(panel.scrollHeight, 420, window.innerHeight - 24);
                    var preferredTop = row.top + (row.height / 2) - (height / 2);
                    var top = Math.max(12, Math.min(preferredTop, window.innerHeight - height - 12));

                    panel.style.left = left + 'px';
                    panel.style.top = top + 'px';
                });
            });

            var jobFilter = document.getElementById('ats-filter-job');
            var companyFilter = document.getElementById('ats-filter-company');
            var locationFilter = document.getElementById('ats-filter-location');
            var resetFilter = document.getElementById('ats-filter-reset');
            var filterEmpty = document.getElementById('ats-filter-empty');
            var jobCount = document.getElementById('ats-visible-job-count');
            var applicantCount = document.getElementById('ats-visible-applicant-count');

            function applyAtsFilters() {
                var selectedJob = jobFilter ? jobFilter.value : '';
                var selectedCompany = companyFilter ? companyFilter.value : '';
                var selectedLocation = locationFilter ? locationFilter.value : '';
                var visibleJobs = 0;
                var visibleApplicants = 0;

                document.querySelectorAll('.ats-job-row').forEach(function (row) {
                    var locations = row.getAttribute('data-location-ids') || '||';
                    var matches = (!selectedJob || row.getAttribute('data-job-id') === selectedJob)
                        && (!selectedCompany || row.getAttribute('data-company-id') === selectedCompany)
                        && (!selectedLocation || locations.indexOf('|' + selectedLocation + '|') !== -1);

                    row.classList.toggle('hidden', !matches);
                    if (matches) {
                        visibleJobs++;
                        visibleApplicants += parseInt(row.getAttribute('data-applicant-count'), 10) || 0;
                    }
                });

                document.querySelectorAll('.ats-status-hover.is-status-open').forEach(function (item) {
                    item.classList.remove('is-status-open');
                });
                if (filterEmpty) filterEmpty.classList.toggle('hidden', visibleJobs !== 0);
                if (jobCount) jobCount.textContent = visibleJobs.toLocaleString();
                if (applicantCount) applicantCount.textContent = visibleApplicants.toLocaleString();
            }

            [jobFilter, companyFilter, locationFilter].forEach(function (filter) {
                if (filter) filter.addEventListener('change', applyAtsFilters);
            });
            if (resetFilter) {
                resetFilter.addEventListener('click', function () {
                    if (jobFilter) jobFilter.value = '';
                    if (companyFilter) companyFilter.value = '';
                    if (locationFilter) locationFilter.value = '';
                    applyAtsFilters();
                });
            }

            $(document).off('click.atsApplicant', '.ats-open-applicant').on('click.atsApplicant', '.ats-open-applicant', function (event) {
                event.preventDefault();
                event.stopPropagation();

                var applicantId = $(this).data('applicant-id');
                if (!applicantId) return;

                document.querySelectorAll('.ats-status-hover.is-status-open').forEach(function (item) {
                    item.classList.remove('is-status-open');
                });

                var $sidebar = $('#right-sidebar');
                var $backdrop = $('#right-sidebar-backdrop');
                var url = "{{ route('admin.job-applications.show', ':id') }}".replace(':id', applicantId);

                $.ajax({
                    type: 'GET',
                    url: url,
                    success: function (response) {
                        if (response.status !== 'success') return;
                        $sidebar.removeClass('translate-x-full').addClass('translate-x-0');
                        $backdrop.removeClass('hidden').css('display', 'block');
                        $('#right-sidebar-content').html(response.view);
                    },
                    error: function () {
                        if (typeof toastr !== 'undefined') toastr.error('Applicant profile could not be opened.');
                    }
                });
            });
        })();
    </script>
@endpush
