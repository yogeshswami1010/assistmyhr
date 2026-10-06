@extends('layouts.app')

@section('page-title-html')
    @lang('menu.dashboard') <em>@lang('modules.dashboard.overview')</em>
@endsection

@section('page-subtitle')
    {{ __('modules.dashboard.welcomeToday', ['name' => $user->name]) }}
@endsection

@section('create-button')
    @if($user->cans('add_jobs'))
        <a href="{{ route('admin.jobs.create') }}" class="inline-flex items-center gap-1.5 text-[12.5px] font-semibold px-4 py-2 rounded-xl text-white shadow-sm hover:opacity-95 transition-opacity" style="background: var(--ra-accent, #2563eb);">
            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            @lang('modules.dashboard.newJobPost')
        </a>
    @endif
@endsection

@push('head-script')
    <link rel="stylesheet" href="{{ asset('assets/plugins/jQueryUI/jquery-ui.min.css') }}">
@endpush

@section('content')
    @if (!$user->mobile_verified && $smsSettings->nexmo_status == 'active')
        <div id="verify-mobile-info" class="rd-a rd-a1 mb-4">
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4" role="alert">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-3">
                        <i class="fa fa-info-circle text-amber-600 text-2xl"></i>
                        <span class="text-sm text-amber-900">@lang('messages.info.verifyAlert')</span>
                    </div>
                    <a href="{{ route('admin.profile.index') }}" class="inline-flex items-center justify-center rounded-xl bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">@lang('menu.profile')</a>
                </div>
            </div>
        </div>
    @endif

    <div class="flex flex-col gap-5 xl:flex-row">
        <div class="min-w-0 flex-1">
            @if(!$progress['progress_completed'])
                @include('admin.dashboard.onboarding-card')
            @endif

            <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
                @if($user->cans('view_company'))
                    <a href="{{ route('admin.company.index') }}" class="rd-sc rd-c-navy rd-a rd-a3">
                @else
                    <div class="rd-sc rd-sc-static rd-c-navy rd-a rd-a3">
                @endif
                        <div class="flex items-center justify-between">
                            <div class="rd-sico">
                                <svg class="h-[18px] w-[18px]" fill="none" stroke="rgba(255,255,255,0.65)" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            </div>
                            <span class="rd-stag bg-white/10 text-white/40">@lang('modules.dashboard.tagTotal')</span>
                        </div>
                        <div class="rd-snum rd-cnt" data-v="{{ (int) $totalCompanies }}">0</div>
                        <div class="rd-slbl">@lang('modules.dashboard.totalCompanies')</div>
                @if($user->cans('view_company'))
                    </a>
                @else
                    </div>
                @endif

                @if($user->cans('view_jobs'))
                    <a href="{{ route('admin.jobs.index') }}" class="rd-sc rd-c-blue rd-a rd-a4">
                @else
                    <div class="rd-sc rd-sc-static rd-c-blue rd-a rd-a4">
                @endif
                        <div class="flex items-center justify-between">
                            <div class="rd-sico">
                                <svg class="h-[18px] w-[18px]" fill="none" stroke="rgba(255,255,255,0.75)" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </div>
                            <span class="rd-stag bg-white/15 text-white/60">@lang('modules.dashboard.tagLive')</span>
                        </div>
                        <div class="rd-snum rd-cnt" data-v="{{ (int) $totalOpenings }}">0</div>
                        <div class="rd-slbl">@lang('modules.dashboard.totalOpenings')</div>
                @if($user->cans('view_jobs'))
                    </a>
                @else
                    </div>
                @endif
                @if($user->cans('view_jobs'))
                    <a href="#" class="rd-sc rd-c-blue rd-a rd-a4">
                @else
                    <div class="rd-sc rd-sc-static rd-c-blue rd-a rd-a4">
                @endif

                <div class="flex items-center justify-between">
                    <div class="rd-sico">
                       <svg class="h-[18px] w-[18px]" fill="none" stroke="rgba(255,255,255,0.75)" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>

                    <span class="rd-stag bg-white/15 text-white/60">
                        ATS
                    </span>
                </div>

                <div class="rd-snum rd-cnt" data-v="{{ (int) $totalAtsJobs }}">0</div>

                <div class="rd-slbl">
                    ATS Jobs
                </div>

                @if($user->cans('view_jobs'))
                    </a>
                @else
                    </div>
                @endif
                @if($user->cans('view_job_applications'))
                    <a href="{{ route('admin.applications-archive.index') }}" class="rd-sc rd-c-white rd-c-rose rd-a rd-a5">
                @else
                    <div class="rd-sc rd-sc-static rd-c-white rd-c-rose rd-a rd-a5">
                @endif
                        <div class="flex items-center justify-between">
                            <div class="rd-sico">
                                <svg class="h-[18px] w-[18px]" fill="none" stroke="#F43F5E" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <span class="rd-stag bg-rose-50 text-rose-500">@lang('modules.dashboard.tagAll')</span>
                        </div>
                        <div class="rd-snum rd-cnt" data-v="{{ (int) $totalApplications }}">0</div>
                        <div class="rd-slbl">Candidate Database</div>
                @if($user->cans('view_job_applications'))
                    </a>
                @else
                    </div>
                @endif

                @if($user->cans('view_job_applications'))
                     <a href="{{ route('admin.job-applications.table', ['status_name' => 'hired']) }}" class="rd-sc rd-c-emerald rd-a rd-a6">
                @else
                    <div class="rd-sc rd-sc-static rd-c-emerald rd-a rd-a6">
                @endif
                        <div class="flex items-center justify-between">
                            <div class="rd-sico">
                                <svg class="h-[18px] w-[18px]" fill="none" stroke="rgba(255,255,255,0.75)" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                            </div>
                            <span class="rd-stag bg-white/15 text-white/60">@lang('modules.dashboard.tagHired')</span>
                        </div>
                        <div class="rd-snum rd-cnt" data-v="{{ (int) $totalHired }}">0</div>
                        <div class="rd-slbl">@lang('modules.dashboard.totalHired')</div>
                @if($user->cans('view_job_applications'))
                    </a>
                @else
                    </div>
                @endif

                @if($user->cans('view_job_applications'))
                    <a href="{{ route('admin.job-applications.table', ['status_name' => 'rejected']) }}" class="rd-sc rd-c-white rd-c-amber rd-a rd-a3">
                @else
                    <div class="rd-sc rd-sc-static rd-c-white rd-c-amber rd-a rd-a3">
                @endif
                        <div class="flex items-center justify-between">
                            <div class="rd-sico">
                                <svg class="h-[18px] w-[18px]" fill="none" stroke="#F97316" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <span class="rd-stag bg-orange-50 text-orange-500">@lang('modules.dashboard.tagClosed')</span>
                        </div>
                        <div class="rd-snum rd-cnt" data-v="{{ (int) $totalRejected }}">0</div>
                        <div class="rd-slbl">@lang('modules.dashboard.totalRejected')</div>
                @if($user->cans('view_job_applications'))
                    </a>
                @else
                    </div>
                @endif

                @if($user->cans('view_job_applications'))
                    <a href="{{ route('admin.job-applications.table') }}" class="rd-sc rd-c-white rd-c-violet rd-a rd-a4">
                @else
                    <div class="rd-sc rd-sc-static rd-c-white rd-c-violet rd-a rd-a4">
                @endif
                        <div class="flex items-center justify-between">
                            <div class="rd-sico">
                                <svg class="h-[18px] w-[18px]" fill="none" stroke="#7C3AED" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                            </div>
                            <span class="rd-stag bg-violet-50 text-violet-600">@lang('modules.dashboard.tagNew')</span>
                        </div>
                        <div class="rd-snum rd-cnt" data-v="{{ (int) $newApplications }}">0</div>
                        <div class="rd-slbl">@lang('modules.dashboard.newApplications')</div>
                @if($user->cans('view_job_applications'))
                    </a>
                @else
                    </div>
                @endif

                @if($user->cans('view_job_applications'))
                    <a href="{{ route('admin.candidate-marketing.index') }}" class="rd-sc rd-c-white rd-c-indigo rd-a rd-a5">
                @else
                    <div class="rd-sc rd-sc-static rd-c-white rd-c-indigo rd-a rd-a5">
                @endif
                        <div class="flex items-center justify-between">
                            <div class="rd-sico">
                                <svg class="h-[18px] w-[18px]" fill="none" stroke="#2563EB" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </div>
                            <span class="rd-stag bg-blue-50 text-blue-600">@lang('modules.dashboard.tagPipeline')</span>
                        </div>
                        <div class="rd-snum rd-cnt" data-v="{{ (int) $candidateMarketing }}">0</div>
                        <div class="rd-slbl">Candidate Marketing</div>
                @if($user->cans('view_job_applications'))
                    </a>
                @else
                    </div>
                @endif

                @if($user->cans('view_schedule'))
                    <a href="{{ route('admin.interview-schedule.index') }}" class="rd-sc rd-c-white rd-c-teal rd-a rd-a6">
                @else
                    <div class="rd-sc rd-sc-static rd-c-white rd-c-teal rd-a rd-a6">
                @endif
                        <div class="flex items-center justify-between">
                            <div class="rd-sico">
                                <svg class="h-[18px] w-[18px]" fill="none" stroke="#0D9488" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <span class="rd-stag bg-teal-50 text-teal-600">@lang('modules.dashboard.tagToday')</span>
                        </div>
                        <div class="rd-snum rd-cnt" data-v="{{ (int) $totalTodayInterview }}">0</div>
                        <div class="rd-slbl">@lang('modules.dashboard.todayInterview')</div>
                @if($user->cans('view_schedule'))
                    </a>
                @else
                    </div>
                @endif
            </div>

            @if ($user->roles->count() === 0)
                <div class="rd-a rd-a3 mt-5 rounded-xl border border-[#E8E6E1] bg-white px-3 py-3">
                    <p class="mb-2 text-[10px] font-bold uppercase tracking-widest text-[#8892A0]">@lang('modules.dashboard.quickActions')</p>
                    <div class="flex flex-col gap-1">
                        @if($user->cans('add_jobs'))
                            <a href="{{ route('admin.jobs.create') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-left text-[12.5px] font-medium text-[#3D4A5C] transition-colors hover:bg-[#EEF0F5]">
                                <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-[#EFF6FF]"><svg class="h-3 w-3 text-[#2563EB]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg></span>
                                @lang('modules.dashboard.postNewJob')
                            </a>
                        @endif
                        @if($user->cans('view_schedule'))
                            <a href="{{ route('admin.interview-schedule.create') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-left text-[12.5px] font-medium text-[#3D4A5C] transition-colors hover:bg-[#EEF0F5]">
                                <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-[#F5F3FF]"><svg class="h-3 w-3 text-[#7C3AED]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></span>
                                @lang('modules.dashboard.scheduleInterview')
                            </a>
                        @endif
                        <a href="{{ route('admin.report.index') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-left text-[12.5px] font-medium text-[#3D4A5C] transition-colors hover:bg-[#EEF0F5]">
                            <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-emerald-50"><svg class="h-3 w-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></span>
                            @lang('modules.dashboard.viewReports')
                        </a>
                    </div>
                </div>
            @endif
        </div>

        @if ($user->roles->count() > 0)
            <div class="flex w-full shrink-0 flex-col gap-4 xl:w-[268px] rd-a rd-a3">
                <div id="dashboard-online-team" class="overflow-hidden rounded-xl border border-[#E8E6E1] bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-[#F0EEE9] px-4 py-3">
                        <div>
                            <h3 class="text-sm font-bold text-[#1A1E2E]">Online Team Members</h3>
                            <p class="mt-0.5 text-[11px] text-[#8892A0]">Currently using the ATS</p>
                        </div>
                        <span id="dashboard-online-count" class="inline-flex min-w-[26px] items-center justify-center rounded-full bg-emerald-50 px-2 py-1 text-[11px] font-bold text-emerald-600">{{ $onlineTeamMemberIds->count() }}</span>
                    </div>
                    <div class="max-h-[260px] overflow-y-auto p-2">
                        <?php foreach ($teamMembers as $member): ?>
                            <?php
                                $memberOnline = $onlineTeamMemberIds->contains((int) $member->id);
                                $memberRole = $member->role?->role?->display_name ?? 'Team Member';
                                $isCurrentMember = (int) $member->id === (int) $user->id;
                            ?>
                            <div data-ats-online-member="{{ $member->id }}" class="{{ $memberOnline ? '' : 'hidden' }} flex items-center gap-3 rounded-xl px-2.5 py-2 transition-colors hover:bg-[#F8F9FB]">
                                <span class="relative inline-flex shrink-0">
                                    <img src="{{ $member->profile_image_url }}" alt="" class="h-9 w-9 rounded-full object-cover ring-2 ring-[#EEF0F5]" width="36" height="36" loading="lazy">
                                    <span data-ats-presence-user="{{ $member->id }}" class="absolute -bottom-0.5 -right-0.5 h-3.5 w-3.5 rounded-full border-2 border-white {{ $memberOnline ? 'bg-emerald-500' : 'bg-red-500' }}"></span>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-[13px] font-semibold text-[#1A1E2E]">{{ ucwords($member->name) }} <span class="{{ $isCurrentMember ? '' : 'hidden' }} text-[10px] font-semibold text-[#2563EB]">(You)</span></p>
                                    <p class="truncate text-[11px] text-[#8892A0]">{{ ucwords($memberRole) }}</p>
                                </div>
                                <span class="h-2 w-2 shrink-0 rounded-full bg-emerald-500"></span>
                            </div>
                        <?php endforeach; ?>
                        <div id="dashboard-online-empty" class="{{ $onlineTeamMemberIds->isEmpty() ? '' : 'hidden' }} px-3 py-8 text-center">
                            <p class="text-[12px] font-medium text-[#8892A0]">No team members are online.</p>
                        </div>
                    </div>
                    @if($user->cans('view_team'))
                        <a href="{{ route('admin.team.index') }}" class="block border-t border-[#F0EEE9] px-4 py-2.5 text-center text-[11.5px] font-semibold text-[#2563EB] hover:bg-[#F8FAFF]">View all team members</a>
                    @endif
                </div>
                <div class="rd-todo-card flex max-h-[min(70vh,640px)] flex-col">
                    <div class="rd-todo-hd">
                        <div>
                            <h3 class="text-sm font-bold text-[#1A1E2E]">@lang('modules.module.todos.todoList')</h3>
                            <p class="mt-0.5 text-[11px] text-[#8892A0]">@lang('modules.dashboard.manageDailyTasks')</p>
                        </div>
                        <a href="{{ route('admin.todo-items.index') }}" class="rounded-lg bg-[#EFF6FF] px-3 py-1.5 text-[11.5px] font-semibold text-[#2563EB] hover:bg-blue-100">@lang('modules.module.todos.viewAll')</a>
                    </div>
                    <div id="todo-items-list" class="min-h-0 flex-1 overflow-y-auto"></div>
                </div>

                <div class="rounded-xl border border-[#E8E6E1] bg-white px-3 py-3">
                    <p class="mb-2 text-[10px] font-bold uppercase tracking-[0.12em] text-[#8892A0]">@lang('modules.dashboard.quickActions')</p>
                    <div class="flex flex-col gap-1">
                        @if($user->cans('add_jobs'))
                            <a href="{{ route('admin.jobs.create') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-left text-[12.5px] font-medium text-[#3D4A5C] transition-colors hover:bg-[#EEF0F5]">
                                <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-[#EFF6FF]"><svg class="h-3 w-3 text-[#2563EB]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg></span>
                                @lang('modules.dashboard.postNewJob')
                            </a>
                        @endif
                        @if($user->cans('view_schedule'))
                            <a href="{{ route('admin.interview-schedule.create') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-left text-[12.5px] font-medium text-[#3D4A5C] transition-colors hover:bg-[#EEF0F5]">
                                <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-[#F5F3FF]"><svg class="h-3 w-3 text-[#7C3AED]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></span>
                                @lang('modules.dashboard.scheduleInterview')
                            </a>
                        @endif
                        <a href="{{ route('admin.report.index') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-left text-[12.5px] font-medium text-[#3D4A5C] transition-colors hover:bg-[#EEF0F5]">
                            <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-emerald-50"><svg class="h-3 w-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></span>
                            @lang('modules.dashboard.viewReports')
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <div class="hidden fixed inset-0 z-50 overflow-y-auto" id="reviewModal" tabindex="-1" role="dialog" aria-labelledby="reviewModal" aria-hidden="true" data-backdrop="static">
        <div class="flex min-h-screen items-center justify-center px-4">
            <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" onclick="$('#reviewModal').addClass('hidden')"></div>
            <div class="relative max-h-[90vh] w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-xl">
                <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                    <h4 class="text-lg font-semibold text-gray-900" id="myModalLabel">Do you like Recruit? Help us Grow :)</h4>
                    <button type="button" class="text-gray-400 hover:text-gray-600 focus:outline-none" onclick="$('#reviewModal').addClass('hidden')">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
                <div class="max-h-[calc(90vh-140px)] overflow-y-auto p-6">
                    <div class="text-sm text-gray-700">
                        We hope you love it. If you do, would you mind taking 10 seconds to leave me a short review on codecanyon?
                        <br><br>
                        This helps us to continue providing great products, and helps potential buyers to make confident decisions.
                        <hr class="my-4">
                        Thank you in advance for your review and for being a preferred customer.
                        <hr class="my-4">
                        <div class="text-center">
                            <a href="{{\Froiden\Envato\Functions\EnvatoUpdate::reviewUrl()}}"><img src="{{asset('assets/images/recruit-review.png')}}" alt="" class="mx-auto"></a>
                            <div class="mt-4 space-x-2">
                                <button type="button" class="text-blue-600 underline hover:text-blue-800" onclick="hideReviewModal('closed_permanently_button_pressed'); $('#reviewModal').addClass('hidden');">Hide Pop up permanently</button>
                                <button type="button" class="text-blue-600 underline hover:text-blue-800" onclick="hideReviewModal('already_reviewed_button_pressed'); $('#reviewModal').addClass('hidden');">Already Reviewed</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-end space-x-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
                    <a href="{{\Froiden\Envato\Functions\EnvatoUpdate::reviewUrl()}}" target="_blank" class="btn btn-success" type="button">Give Review</a>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('footer-script')
    <script src="{{ asset('assets/plugins/jQueryUI/jquery-ui.min.js') }}"></script>

    <script>
        var updated = true;

        function showNewTodoForm() {
            let url = "{{ route('admin.todo-items.create') }}";
            $.ajaxModal('#application-md-modal', url);
        }

        function initSortable() {
            let updates = {'pending-tasks': false, 'completed-tasks': false};
            let completedFirstPosition = $('#completed-tasks').find('li.draggable').first().data('position');
            let pendingFirstPosition = $('#pending-tasks').find('li.draggable').first().data('position');

            $('#pending-tasks').sortable({
                connectWith: '#completed-tasks',
                cursor: 'move',
                handle: '.handle',
                stop: function (event, ui) {
                    const id = ui.item.data('id');
                    const oldPosition = ui.item.data('position');

                    if (updates['pending-tasks']===true && updates['completed-tasks']===true)
                    {
                        const inverseIndex =  completedFirstPosition > 0 ? completedFirstPosition - ui.item.index() + 1 : 1;
                        const newPosition = inverseIndex;

                        updateTodoItem(id, position={oldPosition, newPosition}, status='completed');

                    }
                    else if(updates['pending-tasks']===true && updates['completed-tasks']===false)
                    {
                        const newPosition = pendingFirstPosition - ui.item.index();

                        updateTodoItem(id, position={oldPosition, newPosition});
                    }

                    updates['pending-tasks']=false;
                    updates['completed-tasks']=false;
                },
                update: function (event, ui) {
                    updates[$(this).attr('id')] = true;
                }
            }).disableSelection();

            $('#completed-tasks').sortable({
                connectWith: '#pending-tasks',
                cursor: 'move',
                handle: '.handle',
                stop: function (event, ui) {
                    const id = ui.item.data('id');
                    const oldPosition = ui.item.data('position');

                    if (updates['pending-tasks']===true && updates['completed-tasks']===true)
                    {
                        const inverseIndex =  pendingFirstPosition > 0 ? pendingFirstPosition - ui.item.index() + 1 : 1;
                        const newPosition = inverseIndex;

                        updateTodoItem(id, position={oldPosition, newPosition}, status='pending');
                    }
                    else if(updates['pending-tasks']===false && updates['completed-tasks']===true)
                    {
                        const newPosition = completedFirstPosition - ui.item.index();

                        updateTodoItem(id, position={oldPosition, newPosition});
                    }

                    updates['pending-tasks']=false;
                    updates['completed-tasks']=false;
                },
                update: function (event, ui) {
                    updates[$(this).attr('id')] = true;
                }
            }).disableSelection();
        }

        function updateTodoItem(id, pos, status=null) {
            let data = {
                _token: '{{ csrf_token() }}',
                id: id,
                position: pos,
            };

            if (status) {
                data = {...data, status: status}
            }

            $.easyAjax({
                url: "{{ route('admin.todo-items.updateTodoItem') }}",
                type: 'POST',
                data: data,
                container: '#todo-items-list',
                success: function (response) {
                    $('#todo-items-list').html(response.view);
                    initSortable();
                }
            });
        }

        function showUpdateTodoForm(id) {
            let url = "{{ route('admin.todo-items.edit', ':id') }}";
            url = url.replace(':id', id);

            $.ajaxModal('#application-md-modal', url);
        }

        function deleteTodoItem(id) {
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
                    let url = "{{ route('admin.todo-items.destroy', ':id') }}";
                    url = url.replace(':id', id);

                    let data = {
                        _token: '{{ csrf_token() }}',
                        _method: 'DELETE'
                    }

                    $.easyAjax({
                        url,
                        data,
                        type: 'POST',
                        container: '#roleMemberTable',
                        success: function (response) {
                            if (response.status == 'success') {
                                $('#todo-items-list').html(response.view);
                                initSortable();
                            }
                        }
                    })
                }
            });
        }

        function rdAnimateDashboard() {
            var pb = document.getElementById('rd-pb-fill');
            if (pb) {
                var p = parseInt(pb.getAttribute('data-pct'), 10) || 0;
                setTimeout(function () { pb.style.width = Math.min(100, p) + '%'; }, 300);
            }
            document.querySelectorAll('.rd-cnt').forEach(function (el) {
                var target = parseInt(el.getAttribute('data-v'), 10) || 0;
                if (target === 0) { el.textContent = '0'; return; }
                var s = null;
                function step(ts) {
                    if (!s) s = ts;
                    var prog = Math.min((ts - s) / 850, 1);
                    el.textContent = Math.round((1 - Math.pow(1 - prog, 3)) * target);
                    if (prog < 1) requestAnimationFrame(step);
                }
                requestAnimationFrame(step);
            });
        }

        @if ($user->roles->count() > 0)
            $('#todo-items-list').html(`{!! $todoItemsView !!}`);
            initSortable();
        @endif

        $(document).ready(function () {
            setTimeout(rdAnimateDashboard, 200);
        });

        $('body').on('click', '#application-md-modal-save-btn', function () {
            // Only handle "Create Todo Item" when the create form is present in the modal.
            if ($('#application-md-modal #createTodoItem').length === 0) return;

            $.easyAjax({
                url: "{{route('admin.todo-items.store')}}",
                container: '#createTodoItem',
                type: "POST",
                data: $('#createTodoItem').serialize(),
                success: function (response) {
                    if (response.status == 'success') {
                        $('#todo-items-list').html(response.view);
                        initSortable();
                        $('#application-md-modal').addClass('hidden');
                    }
                }
            })
        });

        $('body').on('click', '#update-todo-item', function () {
            const id = $(this).data('id');
            let url = "{{route('admin.todo-items.update', ':id')}}"
            url = url.replace(':id', id);

            $.easyAjax({
                url: url,
                container: '#editTodoItem',
                type: "POST",
                data: $('#editTodoItem').serialize(),
                success: function (response) {
                    if(response.status == 'success'){
                        $('#todo-items-list').html(response.view);
                        initSortable();

                        $('#application-md-modal').addClass('hidden');
                    }
                }
            })
        });

        $('body').on('change', '#todo-items-list input[name="status"]', function () {
            const id = $(this).data('id');
            let status = 'pending';

            if ($(this).is(':checked')) {
                status = 'completed';
            }

            updateTodoItem(id, null, status);
        })
    </script>
  
@endpush
