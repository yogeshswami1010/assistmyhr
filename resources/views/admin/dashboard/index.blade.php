@extends('layouts.app')

@section('page-title-html')
    @lang('menu.dashboard') <em>@lang('modules.dashboard.overview')</em>
@endsection

@section('page-subtitle')
    {{ __('modules.dashboard.welcomeToday', ['name' => $user->name]) }}
@endsection

@section('create-button')
    <div class="flex flex-wrap items-center gap-2">
    @if(config('saas.enabled') && !$user->hasVerifiedEmail())
        <form method="post" action="{{ tenant_route('verification.send') }}" class="flex flex-wrap items-center gap-2" style="background:#fffbeb;border:1px solid #fde68a;border-radius:12px;padding:6px 12px;margin:0">
            @csrf
            <span style="color:#92400e;font-size:12px;font-weight:600">Email verification pending</span>
            <button type="submit" style="background:transparent;border:0;color:#2563eb;font-size:12px;font-weight:600;cursor:pointer;padding:4px">Resend email</button>
        </form>
    @endif
    @if($tourAvailable ?? false)
        <a href="{{ tenant_route('admin.dashboard', ['tour' => 1]) }}" class="inline-flex items-center gap-1.5 text-[12.5px] font-semibold px-4 py-2 rounded-xl" style="background:#eff6ff;color:#2563eb;border:1px solid #bfdbfe">Guided Tour</a>
    @endif
    @if($user->cans('add_jobs'))
        <a href="{{ route('admin.jobs.create') }}" class="inline-flex items-center gap-1.5 text-[12.5px] font-semibold px-4 py-2 rounded-xl text-white shadow-sm hover:opacity-95 transition-opacity" style="background: var(--ra-accent, #2563eb);">
            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            @lang('modules.dashboard.newJobPost')
        </a>
    @endif
    </div>
@endsection

@push('head-script')
    <link rel="stylesheet" href="{{ asset('assets/plugins/jQueryUI/jquery-ui.min.css') }}">
@endpush

@section('content')
@if(config('saas.enabled') && ($dashboardTenant = app(\App\Saas\TenantContext::class)->current()))
<section class="mb-4 rounded-2xl border border-slate-200 bg-white p-4" aria-labelledby="dashboard-login-title">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div><h2 id="dashboard-login-title" class="text-sm font-semibold">Your company login URL</h2><p class="mt-1 text-xs text-slate-500">Save this link to sign in to your company's ATS.</p></div>
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" data-ats-install class="rounded-xl px-4 py-2 text-xs font-semibold" style="background:#2563eb;color:#fff">Install ATS app</button>
            <button type="button" id="dashboard-copy-login" class="rounded-xl px-4 py-2 text-xs font-semibold" style="background:#eff6ff;color:#2563eb">Copy link</button>
        </div>
    </div>
    <a id="dashboard-login-url" href="{{ route('saas.workspace', $dashboardTenant->slug) }}" class="mt-3 block text-sm" style="color:#2563eb;overflow-wrap:anywhere">{{ route('saas.workspace', $dashboardTenant->slug) }}</a>
    <span id="dashboard-copy-status" role="status" aria-live="polite" class="text-xs text-slate-500"></span>
    <p class="mt-3 text-xs text-slate-500" data-ats-install-status role="status" aria-live="polite">Install your ATS on your desktop or home screen for quick access.</p>
</section>
<script>
async function copyDashboardLogin(event){
    event.preventDefault();
    const link=document.getElementById('dashboard-login-url'),status=document.getElementById('dashboard-copy-status');
    try{await navigator.clipboard.writeText(link.href);status.textContent='Login link copied.';}
    catch(error){const range=document.createRange();range.selectNodeContents(link);const selection=window.getSelection();selection.removeAllRanges();selection.addRange(range);status.textContent='Select and copy your login link.';}
}
document.getElementById('dashboard-copy-login').addEventListener('click',copyDashboardLogin);
document.getElementById('dashboard-login-url').addEventListener('click',copyDashboardLogin);
</script>
@endif
@if(session('status'))<div role="status" class="mb-4 rounded-xl p-4" style="background:#eff6ff;color:#1e40af">{{ session('status') }}</div>@endif
@if($errors->has('delivery'))<div role="alert" class="mb-4 rounded-xl p-4" style="background:#fff1f2;color:#be123c">{{ $errors->first('delivery') }}</div>@endif
@if(($showWelcome ?? false) || ($replayTour ?? false)) @include('admin.dashboard.welcome') @endif
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

    {{-- Single full-width column (right sidebar removed) --}}
    <div class="flex flex-col gap-5">
        <div class="min-w-0 flex-1">
            @if(!$progress['progress_completed'])
                @include('admin.dashboard.onboarding-card')
            @endif

            {{-- ================= STAT CARDS (8) ================= --}}
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

                {{-- NEW 8th CARD: Online Team Members --}}
                <div class="rd-sc rd-sc-static rd-c-white rd-c-teal rd-a rd-a6">
                    <div class="flex items-center justify-between">
                        <div class="rd-sico">
                            <svg class="h-[18px] w-[18px]" fill="none" stroke="#0D9488" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <span class="rd-stag bg-teal-50 text-teal-600">Online</span>
                    </div>
                    <div id="dashboard-online-count" class="rd-snum rd-cnt" data-v="{{ (int) $onlineTeamMemberIds->count() }}">0</div>
                    <div class="rd-slbl">Online Team Members</div>
                </div>
            </div>

            {{-- ================= ROW 1: Recent Applications + To-Do ================= --}}
            <div class="mt-5 grid grid-cols-1 gap-4 xl:grid-cols-3">
                <div class="xl:col-span-2 rounded-xl border border-[#E8E6E1] bg-white shadow-sm rd-a rd-a3">
                    <div class="flex items-center justify-between px-4 py-3">
                        <h3 class="text-sm font-bold text-[#1A1E2E]">Recent Applications</h3>
                        <a href="{{ route('admin.job-applications.table') }}" class="text-[11.5px] font-semibold text-[#2563EB]">View All</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-[12px]">
                            <thead>
                                <tr class="border-y border-[#F0EEE9] text-[10px] uppercase tracking-wider text-[#8892A0]">
                                    <th class="px-4 py-2 font-semibold">Candidate</th>
                                    <th class="px-4 py-2 font-semibold">Job Title</th>
                                    <th class="px-4 py-2 font-semibold">Applied On</th>
                                    <th class="px-4 py-2 font-semibold">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse(($recentApplications ?? collect()) as $app)
                                <tr class="border-b border-[#F8F9FB]">
                                    <td class="px-4 py-2.5">
                                        <span class="inline-flex items-center gap-2">
                                            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-[#EFF6FF] text-[10px] font-bold text-[#2563EB]">
                                                {{ strtoupper(collect(explode(' ', $app->full_name))->map(fn($w) => $w[0] ?? '')->take(2)->implode('')) }}
                                            </span>
                                            <span class="font-semibold text-[#1A1E2E]">{{ $app->full_name }}</span>
                                        </span>
                                    </td>
                                    <td class="px-4 py-2.5 text-[#3D4A5C]">{{ $app->job->title ?? '-' }}</td>
                                    <td class="px-4 py-2.5 text-[#3D4A5C]">{{ $app->created_at->format('M j, Y') }}</td>
                                    <td class="px-4 py-2.5">
                                        <span class="rounded-full bg-[#EFF6FF] px-2.5 py-1 text-[10.5px] font-semibold text-[#2563EB]">{{ ucfirst($app->status->status ?? 'New') }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-4 py-8 text-center text-[#8892A0]">No recent applications.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if ($user->roles->count() > 0)
                <div class="rd-todo-card flex max-h-[min(70vh,420px)] flex-col rd-a rd-a3">
                    <div class="rd-todo-hd">
                        <div>
                            <h3 class="text-sm font-bold text-[#1A1E2E]">@lang('modules.module.todos.todoList')</h3>
                        </div>
                        <a href="{{ route('admin.todo-items.index') }}" class="rounded-lg bg-[#EFF6FF] px-3 py-1.5 text-[11.5px] font-semibold text-[#2563EB] hover:bg-blue-100">@lang('modules.module.todos.viewAll')</a>
                    </div>
                    <div class="flex gap-2 px-3 pb-2">
                        <input id="quick-todo-title" type="text" placeholder="Add a task..." class="min-w-0 flex-1 rounded-lg border border-[#E8E6E1] px-3 py-2 text-[12px] focus:outline-none">
                        <button type="button" id="quick-todo-add" class="rounded-lg bg-[#2563EB] px-3 text-white" aria-label="Add task">+</button>
                    </div>
                    <div id="todo-items-list" class="min-h-0 flex-1 overflow-y-auto"></div>
                </div>
                @endif
            </div>

            {{-- ================= ROW 2: Jobs Overview + Quick Actions ================= --}}
            @php
                $jo = $jobsOverview ?? ['total' => 0, 'active' => 0, 'filled' => 0, 'closed' => 0, 'on_hold' => 0];
                $segments = [
                    ['label' => 'Active Jobs', 'count' => $jo['active'],  'color' => '#2563EB'],
                    ['label' => 'Filled Jobs', 'count' => $jo['filled'],  'color' => '#10B981'],
                    ['label' => 'Closed Jobs', 'count' => $jo['closed'],  'color' => '#F43F5E'],
                    ['label' => 'On Hold',     'count' => $jo['on_hold'], 'color' => '#F59E0B'],
                ];
                $sum = max(1, collect($segments)->sum('count'));
                $offset = 0;
            @endphp
            <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-3">
                <div class="xl:col-span-2 rounded-xl border border-[#E8E6E1] bg-white p-4 shadow-sm rd-a rd-a4">
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="text-sm font-bold text-[#1A1E2E]">Jobs Overview</h3>
                        <a href="{{ route('admin.jobs.index') }}" class="text-[11.5px] font-semibold text-[#2563EB]">View All</a>
                    </div>
                    <div class="flex flex-wrap items-center gap-8">
                        <div class="relative h-32 w-32">
                            <svg viewBox="0 0 36 36" class="h-32 w-32 -rotate-90">
                                <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#F0EEE9" stroke-width="4"/>
                                @foreach($segments as $s)
                                    @php $pct = $s['count'] / $sum * 100; @endphp
                                    <circle cx="18" cy="18" r="15.9155" fill="none" stroke="{{ $s['color'] }}" stroke-width="4"
                                            stroke-dasharray="{{ $pct }} {{ 100 - $pct }}" stroke-dashoffset="{{ -$offset }}"/>
                                    @php $offset += $pct; @endphp
                                @endforeach
                            </svg>
                            <div class="absolute inset-0 flex flex-col items-center justify-center">
                                <span class="text-2xl font-extrabold text-[#1A1E2E]">{{ $jo['total'] }}</span>
                                <span class="text-[10px] text-[#8892A0]">Total Jobs</span>
                            </div>
                        </div>
                        <ul class="min-w-[220px] flex-1 space-y-2 text-[12px]">
                            @foreach($segments as $s)
                                <li class="flex items-center justify-between">
                                    <span class="flex items-center gap-2 text-[#3D4A5C]">
                                        <span class="h-2.5 w-2.5 rounded-full" style="background:{{ $s['color'] }}"></span>{{ $s['label'] }}
                                    </span>
                                    <span class="font-semibold text-[#1A1E2E]">{{ $s['count'] }}
                                        <span class="ml-3 text-[#8892A0]">{{ round($s['count'] / $sum * 100) }}%</span></span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <div class="rounded-xl border border-[#E8E6E1] bg-white p-4 shadow-sm rd-a rd-a4">
                    <h3 class="mb-3 text-sm font-bold text-[#1A1E2E]">Quick Actions</h3>
                    <div class="grid grid-cols-2 gap-3">
                        @if($user->cans('add_jobs'))
                        <a href="{{ route('admin.jobs.create') }}" class="flex flex-col items-center gap-2 rounded-xl bg-[#EFF6FF] px-3 py-4 text-center text-[12px] font-semibold text-[#2563EB]">
                            <span class="text-lg">⊕</span>Post a New Job
                        </a>
                        @endif
                        @if($user->cans('view_schedule'))
                        <a href="{{ route('admin.interview-schedule.create') }}" class="flex flex-col items-center gap-2 rounded-xl bg-[#F5F3FF] px-3 py-4 text-center text-[12px] font-semibold text-[#7C3AED]">
                            <span class="text-lg">📅</span>Schedule Interview
                        </a>
                        @endif
                        <a href="{{ route('admin.report.index') }}" class="flex flex-col items-center gap-2 rounded-xl bg-emerald-50 px-3 py-4 text-center text-[12px] font-semibold text-emerald-600">
                            <span class="text-lg">📊</span>View Reports
                        </a>
                        <a href="{{ route('admin.job-applications.table') }}" class="flex flex-col items-center gap-2 rounded-xl bg-amber-50 px-3 py-4 text-center text-[12px] font-semibold text-amber-600">
                            <span class="text-lg">🔍</span>Search Candidates
                        </a>
                    </div>
                </div>
            </div>
        </div>
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
        });

        // Quick "Add a task..." input
        $('body').on('click', '#quick-todo-add', function () {
            const title = $('#quick-todo-title').val().trim();
            if (!title) return;
            $.easyAjax({
                url: "{{ route('admin.todo-items.store') }}",
                type: 'POST',
                data: { _token: '{{ csrf_token() }}', title: title },
                success: function (response) {
                    if (response.status == 'success') {
                        $('#todo-items-list').html(response.view);
                        initSortable();
                        $('#quick-todo-title').val('');
                    }
                }
            });
        });
        $('body').on('keypress', '#quick-todo-title', function (e) {
            if (e.which === 13) { $('#quick-todo-add').click(); }
        });
    </script>
  
@endpush