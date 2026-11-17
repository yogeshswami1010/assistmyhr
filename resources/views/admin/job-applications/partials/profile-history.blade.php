@include('admin.job-applications.partials.candidate-call-history', ['candidateCalls' => $candidateCalls ?? collect()])
@if(isset($tempStaffingHistories) && $tempStaffingHistories->isNotEmpty() && app(\App\Saas\TenantContext::class)->featureEnabled('temp_staffing'))
<div class="ja-card">
    <div class="ja-card-title"><i class="fa fa-users"></i> Temp Staffing History</div>
    @foreach($tempStaffingHistories as $history)
        <div class="ja-info-row"><span class="ja-info-label" style="width:auto"><span style="width:8px;height:8px;border-radius:50%;background:{{ $history->action === 'added' ? '#10B981' : '#EF4444' }}"></span>{{ $history->action === 'added' ? 'Added to Temp Staffing' : 'Removed from Temp Staffing' }}</span><span class="ja-info-val" style="font-size:11px">{{ $history->user?->name ?? 'Unknown team member' }}<br><span style="color:#A0A8B5;font-weight:500">{{ $history->created_at->timezone('America/Toronto')->format('d M Y, h:i A') }} ET</span></span></div>
    @endforeach
</div>
@endif
@include('admin.job-applications.partials.resume-history', ['resumeHistories' => $resumeHistories])
@if($statusHistories->isNotEmpty())
<div class="ja-card" style="margin-bottom:10px">
    <div class="ja-card-title"><i class="fa fa-exchange"></i> Stage Activity</div>
    @foreach($statusHistories as $hist)
    <div style="padding:10px 0;border-bottom:1px solid #F0EEE9;font-size:12px;color:#1A1E2E">
        <div>
            <strong>{{ $hist->user?->name ?? 'Auto (screening rule)' }}</strong>
            — {{ $hist->notes ?: 'moved this applicant' }}
        </div>
        @if($hist->fromStatus || $hist->toStatus)
        <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-top:7px">
            @if($hist->fromStatus)
            <span style="display:inline-flex;padding:3px 8px;border-radius:20px;font-size:10.5px;font-weight:600;color:#fff;background:{{ $hist->fromStatus->color ?? '#6B7280' }}">
                {{ ucwords(str_replace('_', ' ', $hist->fromStatus->status)) }}
            </span>
            @endif
            @if($hist->fromStatus && $hist->toStatus)
            <i class="fa fa-long-arrow-right" style="color:#8A94A6"></i>
            @endif
            @if($hist->toStatus)
            <span style="display:inline-flex;padding:3px 8px;border-radius:20px;font-size:10.5px;font-weight:600;color:#fff;background:{{ $hist->toStatus->color ?? '#6B7280' }}">
                {{ ucwords(str_replace('_', ' ', $hist->toStatus->status)) }}
            </span>
            @endif
        </div>
        @endif
        <div style="font-size:11px;color:#B0B8C4;margin-top:5px">{{ $hist->created_at->timezone('America/Toronto')->format('d M Y, h:i A') }}</div>
    </div>
    @endforeach
</div>
@endif

@forelse($previousApps as $prev)
<div class="ja-card" style="margin-bottom:10px">
    @php
        $previousResumeUrl = $prev->resume_url ?: null;
        if (!$previousResumeUrl) {
            $resumeAnswer = $prev->answers->first(function ($answer) {
                return $answer->file && preg_match('/\b(resume|cv|curriculum\s+vitae)\b/i', (string) $answer->question?->question);
            });
            $previousResumeUrl = $resumeAnswer?->file_url;
        }
    @endphp

    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px">
        <div>
            <div style="font-size:13.5px;font-weight:700;color:#1A1E2E">{{ ucwords($prev->job?->title ?? '—') }}</div>
            <div style="font-size:11px;color:#8A94A6;margin-top:4px">
                {{ $prev->created_at?->format('d M Y, h:i A') }}
                @if($prev->job?->company) · {{ $prev->job->company->company_name }} @endif
                @if($prev->location) · {{ ucwords($prev->location->location) }} @endif
            </div>
        </div>
        @if($previousResumeUrl)
        <div style="display:flex;gap:6px;flex-shrink:0">
            <a href="{{ $previousResumeUrl }}" target="_blank" rel="noopener" class="ja-note-btn"><i class="fa fa-eye"></i> View CV</a>
            <a href="{{ $previousResumeUrl }}" download class="ja-note-btn"><i class="fa fa-download"></i> Download</a>
        </div>
        @endif
    </div>

    <span style="display:inline-flex;margin-top:8px;padding:3px 10px;border-radius:20px;font-size:11px;color:#fff;background:{{ $prev->status?->color ?? '#6B7280' }}">{{ ucwords(str_replace('_',' ', $prev->status?->status ?? '—')) }}</span>

    @if($prev->cover_letter)
    <div style="margin-top:12px;padding-top:10px;border-top:1px solid #F0EEE9">
        <div style="font-size:11px;font-weight:700;color:#5A6478;margin-bottom:5px;text-transform:uppercase;letter-spacing:.04em">Cover Letter</div>
        <div style="font-size:12px;color:#5A6478;line-height:1.6;white-space:pre-line">{{ $prev->cover_letter }}</div>
    </div>
    @endif

    @if($prev->answers->isNotEmpty())
    <div style="margin-top:12px;padding-top:10px;border-top:1px solid #F0EEE9">
        <div style="font-size:11px;font-weight:700;color:#5A6478;margin-bottom:7px;text-transform:uppercase;letter-spacing:.04em">Job Questions</div>
        @foreach($prev->answers as $answer)
        <div style="padding:8px 10px;margin-bottom:6px;border:1px solid #EEF0F3;border-radius:9px;background:#FAFAF9">
            <div style="font-size:11.5px;font-weight:600;color:#1A1E2E">{{ $answer->question?->question ?? 'Application question' }}</div>
            <div style="font-size:12px;color:#5A6478;margin-top:4px;white-space:pre-line">
                @if($answer->file)
                    <a href="{{ $answer->file_url }}" target="_blank" rel="noopener" style="color:#2563EB;font-weight:600"><i class="fa fa-paperclip"></i> View uploaded file</a>
                @elseif(filled($answer->answer))
                    {{ $answer->answer }}
                @else
                    <span style="color:#B0B8C4">No answer provided</span>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>
@empty
@endforelse

<div class="ja-card" style="margin-bottom:10px">
    <div class="ja-card-title"><i class="fa fa-user-plus"></i> Candidate source & CV activity</div>
    @if($application->origin_label || $application->job)
        <p style="font-size:12px">
            {{ $application->origin_label }}
            @if($application->job)
                @if($application->origin_label)
                    ·
                @endif
                {{ $application->job->title }}
            @endif
        </p>
    @endif
    <p style="font-size:11px;color:#8892A0">Profile created {{ $application->created_at->timezone($global->timezone ?? config('app.timezone'))->format('d M Y, h:i A') }}</p>
    @forelse($application->profileActivities as $activity)
        <div style="padding:10px 0;border-bottom:1px solid #eee;font-size:12px">
            <strong>{{ $activity->actor_name ?? ($application->candidate_origin === 'job_application' && $activity->action === 'created' ? 'Candidate' : 'System') }}</strong>
            — {{ $activity->action === 'cv_parsed' ? 'parsed this CV' : 'created this candidate profile' }}
            <div style="color:#8892A0;font-size:11px">{{ $activity->created_at->timezone($global->timezone ?? config('app.timezone'))->format('d M Y, h:i A') }}</div>
        </div>
    @empty
        <p style="font-size:12px;color:#8892A0">Creator and parsing activity were not recorded for this older profile.</p>
    @endforelse
</div>
