@extends('layouts.front')

@php
    $welcomeTitle = !is_null($frontTheme->welcome_title) ? $frontTheme->welcome_title : __('modules.front.homeHeader');
    $welcomeSub = !is_null($frontTheme->welcome_sub_title) ? $frontTheme->welcome_sub_title : __('modules.front.jobOpeningText');
    $companyShort = $companyName ?? config('app.name');
    $headlineHtml = e($welcomeTitle);
    $parts = preg_split('/\s+at\s+/i', $welcomeTitle, 2);
    if (count($parts) === 2) {
        $headlineHtml = e(trim($parts[0])) . ' <span class="text-white">at</span> <span class="font-serif-recruit italic font-normal text-sky-300">' . e(trim($parts[1])) . '</span>';
    }
@endphp

@section('content')

<section class="fr-hero relative px-6 pt-16 pb-20">
    <div class="max-w-4xl mx-auto text-center relative z-10">
        <!-- <div class="inline-flex items-center gap-2 border text-xs font-semibold tracking-wide rounded-full px-4 py-2 mb-8 bg-white/[0.08] border-white/[0.12] text-white/65">
            <span class="w-1.5 h-1.5 rounded-full inline-block bg-emerald-400"></span>
            @lang('modules.front.jobOpeningHeading') &nbsp;·&nbsp; {{ $companyShort }}
        </div> -->
        <h1 class="text-white font-bold leading-[1.1] tracking-[-0.025em] mb-5 text-[clamp(36px,5vw,58px)]">
            Welcome to Consortium Staffing Solutions
        </h1>
        <p class="font-normal leading-relaxed mx-auto mb-10 text-white/50 text-[16.5px]">
          Explore our open positions to find roles that align with your interests and expertise. From entry-level positions to leadership roles.
        </p>

        <div class="fr-search-wrap max-w-3xl mx-auto">
            <select class="fr-search-select myselect" name="loaction" id="location_id">
                <option value="all">@lang('modules.front.allLocation')</option>
                @foreach($locations as $location)
                    <option value="{{ $location->id }}">{{ ucfirst($location->location) }}</option>
                @endforeach
            </select>
            <select class="fr-search-select myselect" name="category" id="category">
                <option value="all">@lang('modules.front.allCategory')</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ ucfirst($category->name) }}</option>
                @endforeach
            </select>
            <!-- <select class="fr-search-select myselect" name="company_name" id="company">
                <option value="all">@lang('modules.front.allCompany')</option>
                @foreach($companies as $company)
                    <option value="{{ $company->id }}">{{ ucfirst($company->company_name) }}</option>
                @endforeach
            </select> -->
            <!-- <select class="fr-search-select myselect" name="name" id="skill">
                <option value="all">@lang('modules.front.allSkill')</option>
                @foreach($skills as $skill)
                    <option value="{{ $skill->id }}">{{ ucfirst($skill->name) }}</option>
                @endforeach
            </select> -->
            <button type="button" name="search" class="fr-btn-search" id="search">@lang('modules.front.searchButton')</button>
        </div>

        <div class="mt-9 flex items-center justify-center flex-wrap gap-3">
            <div class="fr-stat-pill">
                <span class="text-white font-bold text-[15px]">{{ $locations->count() }}</span>
                <span class="text-sm text-white/40">@lang('modules.front.allLocation')</span>
            </div>
            <div class="fr-stat-pill">
                <span class="text-white font-bold text-[15px]">{{ $jobCount }}</span>
                <span class="text-sm text-white/40">@lang('modules.front.openPositions')</span>
            </div>
        </div>
    </div>
</section>

<section class="px-6 py-20 bg-[#F8F7F4]">
    <div class="max-w-6xl mx-auto">
        <div class="text-center mb-14">
            <p class="fr-section-label">@lang('modules.front.currentOpenings')</p>
            <h2 class="font-bold tracking-[-0.02em] mt-2 text-[clamp(28px,3.5vw,42px)] text-[#1A1A2A]">
                @lang('modules.front.job')&nbsp;<span class="font-serif-recruit italic font-normal">@lang('modules.front.openings')</span>
            </h2>
            <div class="fr-section-rule"></div>
        </div>

        <div data-provide="shuffle" id="applicant-notes">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5" id="jobList">
                @forelse($jobLocations as $index => $location)
                    @php
                        $barStyles = [
                            'linear-gradient(180deg,#2563EB,#60A5FA)',
                            'linear-gradient(180deg,#059669,#34D399)',
                            'linear-gradient(180deg,#7C3AED,#A78BFA)',
                        ];
                        $iconWrap = ['#EFF6FF', '#ECFDF5', '#F5F3FF'];
                        $iconColor = ['#2563EB', '#059669', '#7C3AED'];
                        $i = $index % 3;
                    @endphp
                    <div class="job-list" data-shuffle="item" data-groups="{{ $location->location.','.$location->job->category->name }}">
                        <a href="{{ route('jobs.jobDetail', [$location->job->slug, $location->location->id]) }}" class="fr-job-card job-opening-card group">
                            <div class="fr-job-card-bar" style="background: {{ $barStyles[$i] }};"></div>
                            <div class="pl-3">
                                <div class="flex items-center justify-between mb-5">
                                    <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background: {{ $iconWrap[$i] }};">
                                        <svg class="w-5 h-5" style="color: {{ $iconColor[$i] }};" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    </div>
                                    @if($location->job->jobType)
                                        <span class="fr-badge" style="background: {{ $iconWrap[$i] }}; color: {{ $iconColor[$i] }};">{{ ucfirst($location->job->jobType->job_type) }}</span>
                                    @endif
                                </div>
                                <h3 class="font-bold text-[17px] leading-snug tracking-[-0.01em] mb-1 text-[#1A1A2A] group-hover:text-[#2563EB] transition-colors">{{ ucwords($location->job->title) }}</h3>
                                @if($location->job->company->show_in_frontend == 'true')
                                    <p class="text-[13px] font-medium mb-5 text-[#8892A0]">
                                        @if($location->job->job_company_id != null && $location->job->job_company_id != '' && !is_null($location->job->jobCompany))
                                            <!-- @lang('app.by') {{ ucwords($location->job->jobCompany->company_name) }} -->
                                        @else
                                            <!-- @lang('app.by') {{ ucwords($location->job->company->company_name) }} -->
                                        @endif
                                    </p>
                                @else
                                    <p class="text-[13px] font-medium mb-5 text-[#8892A0]">&nbsp;</p>
                                @endif
                                <div class="flex items-center justify-between pt-4 border-t border-[#F0EEE9]">
                                    <span class="flex items-center gap-1.5 text-[13px] text-[#8892A0]">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        {{ ucwords($location->location->location) }}
                                    </span>
                                    <span class="fr-badge" style="background: {{ $iconWrap[$i] }}; color: {{ $iconColor[$i] }};">{{ ucwords($location->job->category->name) }}</span>
                                </div>
                            </div>
                        </a>
                    </div>
                @empty
                    <h4 id="no-data" class="col-span-full mt-12 mb-10 text-xl font-semibold text-center text-[#8892A0]">@lang('modules.front.noData')</h4>
                @endforelse
            </div>

            <div class="text-center mt-12">
                <button type="button" name="load_more_button" id="load_more_button"
                        class="inline-flex items-center gap-2 font-semibold border rounded-lg px-7 py-3 text-[13px] transition-all duration-200 text-[#3D5273] border-[#E2DFD8] hover:bg-white hover:border-[#D1D9E8]">
                    @lang('modules.front.loadMore')
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </button>
            </div>
        </div>
    </div>
</section>

@if($global->job_alert_status == 1)
<section class="px-6 py-16 bg-gradient-to-br from-[#0F1F3D] to-[#162849]">
    <div class="max-w-2xl mx-auto text-center">
        <p class="fr-section-label mb-3 !text-sky-300">@lang('modules.front.stayInformed')</p>
        <h2 class="font-bold tracking-[-0.02em] leading-tight mb-3 text-white text-[clamp(26px,3vw,36px)]">@lang('modules.front.dontSeeRole')</h2>
        <p class="leading-relaxed mb-8 max-w-md mx-auto text-[15px] text-white/45">@lang('modules.front.dropResumeText')</p>
        <button type="button" class="fr-btn-nav px-8 py-3 text-[13.5px]" id="job-alert-cta">@lang('modules.front.getNotified')</button>
    </div>
</section>
@endif

@endsection

@push('footer-script')
<script>
    $(document).ready(function(){
        $('#job-alert-cta').on('click', function () { $('#job-alert').trigger('click'); });

        var perPage = '{{ $perPage }}';
        totalCurrentData = perPage;
        var jobCount = {{ $jobCount }};
        if(jobCount > perPage) {
            $('#load_more_button').show();
        } else {
            $('#load_more_button').hide();
        }

        $('body').on('click', '#load_more_button', function () {
            var location_id = $('#location_id').val();
            var category = $('#category').val();
            var skill = $('#skill').val();
            var company = $('#company').val();
            var token = '{{ csrf_token() }}';
            $('#load_more_button').html('<b>'+"@lang('app.loading')"+'...</b>');
            $.easyAjax({
                url:"{{ route('jobs.more-data') }}",
                type:'POST',
                data: {'_token':token, 'totalCurrentData':totalCurrentData,'location_id':location_id, 'category':category, 'skill':skill, 'company':company},
                success:function(response) {
                    $('#jobList').append(response.view);
                    totalCurrentData = response.data.job_current_count;
                    $('#load_more_button').blur();
                    $('#load_more_button').html('@lang('modules.front.loadMore')');
                    if (response.data.hideButton !== 'undefined' && response.data.hideButton === 'yes'){
                        $('#load_more_button').hide();
                    }
                    if (response.data.hideButton !== 'undefined' && response.data.hideButton === 'no') {
                        $('#load_more_button').show();
                    }
                }
            });
        });

        $('body').on('click', '#search', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var location_id = $('#location_id').val();
            var category = $('#category').val();
            var skill = $('#skill').val();
            var company = $('#company').val();
            var token = '{{ csrf_token() }}';
            $.easyAjax({
                url:"{{ route('jobs.search-job') }}",
                type:'POST',
                data: {'_token':token, location_id:location_id, category:category, skill:skill, company:company},
                success:function(response){
                    $('#jobList').html(response.view);
                    totalCurrentData = response.data.job_current_count;
                    $([document.documentElement, document.body]).animate({
                        scrollTop: $("#applicant-notes").offset().top - 80
                    }, 600);
                    if (response.data.hideButton != 'undefined' && response.data.hideButton == 'yes'){
                        $('#load_more_button').hide();
                    }
                    if (response.data.hideButton != 'undefined' && response.data.hideButton == 'no') {
                        $('#load_more_button').show();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Search error:', error, xhr);
                }
            });
        });

        $('body').on('keypress', '#location_id, #category, #company, #skill', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                $('#search').click();
            }
        });
    });
</script>
@endpush
