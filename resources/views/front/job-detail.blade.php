@extends('layouts.front')

@section('content')

<section class="fr-mini-hero relative px-6 pt-10 pb-12">
    <div class="max-w-6xl mx-auto relative z-10">
        <nav class="fr-breadcrumb flex flex-wrap items-center mb-6">
            <a href="{{ tenant_route('jobs.jobOpenings') }}">@lang('modules.front.jobOpenings')</a>
            <span class="sep">›</span>
            <span class="cur">{{ ucwords($job->title) }}</span>
        </nav>
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
            <div>
                <div class="flex flex-wrap gap-2 mb-4">
                    @if($job->show_job_type && $job->jobType)
                        <span class="fr-badge bg-blue-500/25 text-sky-300">{{ $job->jobType->job_type }}</span>
                    @endif
                    <span class="fr-badge bg-white/10 text-white/70">{{ ucwords($job->category->name) }}</span>
                </div>
                <h1 class="text-white font-bold leading-tight tracking-[-0.02em] text-[clamp(22px,3.5vw,40px)]">{{ ucwords($job->title) }}</h1>
                <div class="flex flex-wrap items-center gap-4 mt-3 text-[13px] text-white/55">
                   
                    <span class="flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                
{{ optional($locations)->location ? ucwords($locations->location) : 'Not Specified' }}


                    </span>
                </div>
            </div>
            <a href="{{ tenant_route('jobs.jobApply', [$job->slug, $locations ? $locations->id : null]) }}" class="fr-btn-lg shrink-0 text-center inline-block">@lang('modules.front.applyForJob')</a>
        </div>
    </div>
</section>

<div class="max-w-6xl mx-auto  py-10">
    <div class="flex flex-col lg:flex-row gap-8">
        <main class="flex-1 min-w-0">
            <div class="bg-white rounded-2xl border border-[#E8E6E1] p-6 sm:p-8 fr-detail-body prose prose-slate max-w-none">
                @if(count($job->skills) > 0)
                    <p class="fr-sec-title !mt-0">@lang('menu.skills')</p>
                    <div class="flex flex-wrap gap-2 mb-8 not-prose">
                        @foreach($job->skills as $skill)
                            <span class="fr-badge bg-[#EFF6FF] text-[#1D4ED8]">{{ $skill->skill->name }}</span>
                        @endforeach
                    </div>
                @endif

                <p class="fr-sec-title !mt-0">@lang('modules.jobs.jobDescription')</p>
                <div class="font-normal text-[#3D4A5C] text-base leading-relaxed">
                    {!! $job->job_description !!}
                </div>

                <!-- <p class="fr-sec-title">@lang('modules.jobs.jobRequirement')</p>
                <div class="font-normal text-[#3D4A5C] text-base leading-relaxed">
                    {!! $job->job_requirement !!}
                </div> -->
            </div>
        </main>

        <aside class="lg:w-72 shrink-0">
            <div class="sticky top-24 flex flex-col gap-4">
                <div class="fr-sidebar-card flex flex-col gap-5">
                    <div>
                        <p class="fr-sidebar-label">@lang('menu.locations')</p>
                        <p class="fr-sidebar-value">{{ $locations ? ucwords($locations->location) : '—' }}</p>
                    </div>
                    <div class="pt-4 border-t border-[#F0EEE9]">
                        <p class="fr-sidebar-label">@lang('menu.jobCategory')</p>
                        <p class="fr-sidebar-value">{{ ucwords($job->category->name) }}</p>
                    </div>
                    @if(count($job->skills) > 0)
                    <div class="pt-4 border-t border-[#F0EEE9]">
                        <p class="fr-sidebar-label">@lang('menu.skills')</p>
                        <div class="flex flex-wrap gap-2 mt-1">
                            @foreach($job->skills as $skill)
                                <span class="fr-badge bg-[#EFF6FF] text-[#1D4ED8]">{{ $skill->skill->name }}</span>
                            @endforeach
                        </div>
                    </div>
                    @endif
                    <div class="pt-4 border-t border-[#F0EEE9]">
                        <p class="fr-sidebar-label">@lang('menu.totalPositions')</p>
                        <p class="fr-sidebar-value">{{ $job->total_positions }}</p>
                    </div>
                    @if($job->show_work_experience)
                    <div class="pt-4 border-t border-[#F0EEE9]">
                        <p class="fr-sidebar-label">@lang('modules.jobs.workExperience')</p>
                        <p class="fr-sidebar-value">{{ $job->workExperience ? $job->workExperience->work_experience : '--' }}</p>
                    </div>
                    @endif
                    @if($job->show_job_type && $job->jobType)
                    <div class="pt-4 border-t border-[#F0EEE9]">
                        <p class="fr-sidebar-label">@lang('modules.jobs.jobType')</p>
                        <p class="fr-sidebar-value">{{ $job->jobType->job_type }}</p>
                    </div>
                    @endif
                   @if($job->show_salary)

                    @php
                        $currencySymbol = $job->currency->currency_symbol ?? '$';
                    @endphp

                    @if($job->pay_type == 'Range')
                        <div class="pt-4 border-t border-[#F0EEE9]">
                            <p class="fr-sidebar-label">
                                @lang('menu.salary') @lang('modules.jobs.range')
                            </p>

                            <p class="fr-sidebar-value">
                                {{ $currencySymbol }}{{ number_format($job->starting_salary) }}
                                -
                                {{ $currencySymbol }}{{ number_format($job->maximum_salary) }}
                                /{{ $job->pay_according }}
                            </p>
                        </div>

                    @elseif($job->pay_type == 'Starting')
                        <div class="pt-4 border-t border-[#F0EEE9]">
                            <p class="fr-sidebar-label">
                                @lang('modules.jobs.startingSalary')
                            </p>

                            <p class="fr-sidebar-value">
                                {{ $currencySymbol }}{{ number_format($job->starting_salary) }}
                                /{{ $job->pay_according }}
                            </p>
                        </div>

                    @elseif($job->pay_type == 'Maximum')
                        <div class="pt-4 border-t border-[#F0EEE9]">
                            <p class="fr-sidebar-label">
                                @lang('modules.jobs.maximumSalary')
                            </p>

                            <p class="fr-sidebar-value">
                                {{ $currencySymbol }}{{ number_format($job->maximum_salary) }}
                                /{{ $job->pay_according }}
                            </p>
                        </div>

                    @elseif($job->pay_type == 'Exact Amount')
                        <div class="pt-4 border-t border-[#F0EEE9]">
                            <p class="fr-sidebar-label">
                                @lang('modules.jobs.exactSalary')
                            </p>

                            <p class="fr-sidebar-value">
                                {{ $currencySymbol }}{{ number_format($job->starting_salary) }}
                                /{{ $job->pay_according }}
                            </p>
                        </div>
                    @endif

                @endif
                </div>

                <div class="fr-sidebar-card text-center">
                    <p class="fr-sidebar-label mb-4">@lang('modules.jobs.scantopay')</p>
                    <div class="inline-block p-3 rounded-xl border border-[#E8E6E1] bg-white">
                        {!! QrCode::size(160)->generate(tenant_route('jobs.jobApply', [$job->slug, $locations ? $locations->id : null])); !!}
                    </div>
                </div>

                <a href="{{ tenant_route('jobs.jobApply', [$job->slug, $locations ? $locations->id : null]) }}" class="fr-btn-lg w-full text-center block">@lang('modules.front.applyForJob')</a>

                <div class="fr-sidebar-card text-center">
                    <p class="fr-sidebar-label mb-3">@lang('modules.front.shareJob')</p>
                    <div class="flex items-center justify-center gap-2 flex-wrap">
                        <a class="fr-share-btn bg-[#0A66C2] text-white" href="https://www.linkedin.com/shareArticle?mini=true&url={{ tenant_route('jobs.jobDetail', [$job->slug]) }}&title={{ urlencode(ucwords($job->title)) }}&source=LinkedIn" target="_blank" rel="noopener noreferrer" title="LinkedIn">
                            <svg width="15" height="15" fill="white" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                        </a>
                        <a class="fr-share-btn bg-[#1877F2] text-white" href="https://www.facebook.com/sharer/sharer.php?u={{ tenant_route('jobs.jobDetail', [$job->slug]) }}" target="_blank" rel="noopener noreferrer" title="Facebook">
                            <svg width="15" height="15" fill="white" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </a>
                        <a class="fr-share-btn bg-[#1DA1F2] text-white" href="https://twitter.com/intent/tweet?status={{ tenant_route('jobs.jobDetail', [$job->slug]) }}" target="_blank" rel="noopener noreferrer" title="Twitter">
                            <svg width="15" height="15" fill="white" viewBox="0 0 24 24"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>
                        </a>
                        <a class="fr-share-btn bg-[#25D366] text-white" href="https://wa.me/?text={{ urlencode(tenant_route('jobs.jobDetail', [$job->slug])) }}" target="_blank" rel="noopener noreferrer" title="WhatsApp">
                            <svg width="15" height="15" fill="white" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        </a>
                    </div>
                </div>

                @if($linkedinGlobal->status == 'enable')
                    <a class="applyWithLinkedin fr-btn-lg w-full text-center block bg-[#0A66C2] hover:bg-[#094d92]" href="{{ tenant_route('jobs.linkedinRedirect', 'linkedin') }}">
                        <i class="fa fa-linkedin-square"></i>
                        @lang('modules.front.linkedinSignin')
                    </a>
                @endif
            </div>
        </aside>
    </div>
</div>

@endsection
