@foreach($jobLocations as $location)
    @php
        $i = $loop->index % 3;
        $barStyles = [
            'linear-gradient(180deg,#2563EB,#60A5FA)',
            'linear-gradient(180deg,#059669,#34D399)',
            'linear-gradient(180deg,#7C3AED,#A78BFA)',
        ];
        $iconWrap = ['#EFF6FF', '#ECFDF5', '#F5F3FF'];
        $iconColor = ['#2563EB', '#059669', '#7C3AED'];
    @endphp
    <div class="job-list" data-shuffle="item" data-groups="{{ $location->location.','.$location->job->category->name }}">
        <a href="{{ tenant_route('jobs.jobDetail', [$location->job->slug, $location->location->id]) }}" class="fr-job-card job-opening-card block group">
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
                            @lang('app.by') {{ ucwords($location->job->jobCompany->company_name) }}
                        @else
                            @lang('app.by') {{ ucwords($location->job->company->company_name) }}
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
@endforeach
