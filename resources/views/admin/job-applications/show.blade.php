<link rel="stylesheet" href="{{ asset('assets/plugins/jquery-bar-rating-master/dist/themes/fontawesome-stars.css') }}">
@php
    $detailCatName = $application->job?->category?->name ?? __('app.category');
    $detailCatKey = \Illuminate\Support\Str::slug($detailCatName);
    $detailCatClass = match (true) {
        str_contains($detailCatKey, 'engineer') || str_contains($detailCatKey, 'tech') || str_contains($detailCatKey, 'it') => 'bg-[#EFF6FF] text-[#1D4ED8]',
        str_contains($detailCatKey, 'sale') || str_contains($detailCatKey, 'market') => 'bg-[#FFF7ED] text-[#C2410C]',
        str_contains($detailCatKey, 'content') || str_contains($detailCatKey, 'design') => 'bg-[#ECFDF5] text-[#065F46]',
        str_contains($detailCatKey, 'hr') || str_contains($detailCatKey, 'people') => 'bg-[#F5F3FF] text-[#5B21B6]',
        default => 'bg-[#F1F3F7] text-[#5A6478]',
    };
    $stagePillBg = $application->status?->color ?? '#6366F1';
    $initials = collect(explode(' ', $application->full_name))->map(fn($w) => strtoupper(substr($w,0,1)))->take(2)->join('');
    // PERF: controller passes $allStatuses / $previousApps / $clientNotes already
    // loaded — only query as a fallback when rendered from somewhere else.
    if (!isset($allStatuses)) {
        $allStatuses = $application->job_id
            ? \App\ApplicationStatus::where('job_id', $application->job_id)->orderBy('position')->get()
            : collect();
    }
    $currentStatusId = $application->status_id;
    $currentStatus = $allStatuses->firstWhere('id', $currentStatusId);
    $smsFirstName = trim(explode(' ', trim($application->full_name))[0] ?? $application->full_name);
    $defaultSmsMessage = 'Hi '.$smsFirstName.', ';
    if ($application->schedule?->schedule_date) {
        $defaultSmsMessage .= 'your interview for '.($application->job?->title ?? 'the position')
            .' is scheduled for '.$application->schedule->schedule_date->format('M j, Y \a\t g:i A').'.';
    } else {
        $defaultSmsMessage .= 'we are contacting you regarding your application for '
            .($application->job?->title ?? 'the position').'.';
    }

    // ── Previous applications (same email) ──
    $previousApps = collect();

    $clientNotes = collect();

    // Resolve resume URL
    $currentResumeDocument = $application->documents->firstWhere('name', 'Resume');
    $resumeUrl = $currentResumeDocument ? route('admin.job-applications.resume.view', $application->id).'?v='.($currentResumeDocument->updated_at?->timestamp ?? time()) : null;
    if (!$resumeUrl && !empty($answers)) {
        foreach ($answers as $answer) {
            if (!empty($answer->file)) {
                $resumeUrl = !empty($answer->file_url) ? $answer->file_url : asset_url_local_s3('documents/' . basename($answer->file));
                break;
            }
        }
    }
@endphp

<style>
    .ja-pdf-toolbar-tabs { display:flex;align-items:center;gap:4px;flex-shrink:0; }
.ja-pdf-toolbar-tabs .ja-tab {
    padding:7px 11px;font-size:12px;font-weight:600;color:#8A94A6;cursor:pointer;
    border-bottom:2.5px solid transparent;white-space:nowrap;display:flex;align-items:center;gap:5px;
    transition:color .15s;
}
.ja-pdf-toolbar-tabs .ja-tab.active { color:#2563EB;border-bottom-color:#2563EB; }
.ja-pdf-toolbar-tabs .ja-tab:hover:not(.active) { color:#1A1E2E; }
.ja-two-col-wrap {
    display: flex; flex-direction: column;
    height: 100vh; background: #F8F7F4;
    font-family: 'Plus Jakarta Sans', sans-serif; overflow: hidden; position: relative;
}
.ja-header {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 20px; background: #0F1F3D; flex-shrink: 0;
    border-bottom: 1px solid rgba(255,255,255,.08);
}
.ja-header-avatar { width:38px;height:38px;border-radius:50%;border:1.5px solid rgba(255,255,255,.25);object-fit:cover;flex-shrink:0; }
.ja-header-avatar-initials { width:38px;height:38px;border-radius:50%;background:rgba(255,255,255,.12);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#fff;flex-shrink:0; }
.ja-header-meta { flex:1;min-width:0; }
.ja-header-meta h2 { font-size:15px;font-weight:700;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin:0; }
.ja-header-meta p { font-size:11.5px;color:rgba(255,255,255,.5);margin:2px 0 0; }
.ja-header-pills { display:flex;align-items:center;gap:6px; }
.ja-pill { display:inline-flex;align-items:center;gap:4px;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:600; }
.ja-pill-stage { color:#fff; }
.ja-pill-cat { background:rgba(255,255,255,.1);color:rgba(255,255,255,.75); }
.ja-close-btn { width:30px;height:30px;border-radius:8px;border:1px solid rgba(255,255,255,.15);background:rgba(255,255,255,.07);color:rgba(255,255,255,.7);cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:background .15s; }
.ja-close-btn:hover { background:rgba(255,255,255,.15); }
.ja-nav-arrows { display:flex;align-items:center;gap:4px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);border-radius:10px;padding:3px 6px;flex-shrink:0; }
.ja-nav-btn { width:26px;height:26px;border-radius:7px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.08);color:rgba(255,255,255,.75);cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .15s,color .15s,opacity .15s;flex-shrink:0; }
.ja-nav-btn:hover:not(:disabled) { background:rgba(255,255,255,.18);color:#fff; }
.ja-nav-btn:disabled { opacity:.25;cursor:not-allowed; }
.ja-nav-counter { font-size:11px;font-weight:600;color:rgba(255,255,255,.5);min-width:36px;text-align:center;white-space:nowrap; }
.ja-body { flex:1;display:grid;grid-template-columns:minmax(0,1fr) 380px;overflow:hidden;min-height:0; }
.ja-pdf-panel { display:flex;flex-direction:column;border-right:1px solid #E8E6E1;overflow:hidden;background:#525659;min-width:0; }
.ja-pdf-toolbar { display:flex;align-items:center;justify-content:end;padding:9px 14px;background:#fff;border-bottom:1px solid #E8E6E1;flex-shrink:0; }
.ja-pdf-toolbar-label { display:flex;align-items:center;gap:7px;font-size:12px;font-weight:600;color:#5A6478; }
.ja-pdf-toolbar-actions { display:flex;align-items:center;gap:6px; }
.ja-pdf-btn { display:inline-flex;align-items:center;gap:5px;padding:5px 11px;border-radius:8px;border:1px solid #E2DED8;background:#fff;cursor:pointer;font-size:12px;color:#5A6478;font-family:'Plus Jakarta Sans',sans-serif;transition:background .15s,color .15s; }
.ja-pdf-btn:hover { background:#F8F7F4;color:#1A1E2E; }
.ja-pdf-btn-primary { background:#2563EB;color:#fff;border-color:transparent; }
.ja-pdf-btn-primary:hover { background:#1d4ed8;color:#fff; }
.ja-profile-toolbar { flex-direction:row;flex-wrap:nowrap;align-items:center;justify-content:flex-start;gap:8px;padding:8px 12px;min-width:0;overflow-x:auto;scrollbar-width:thin;scrollbar-color:#CBD5E1 transparent; }
.ja-profile-toolbar .ja-profile-tags { display:flex;align-items:center;flex-wrap:nowrap;gap:6px;flex-shrink:0; }
.ja-profile-toolbar [id^="ja-marketing-wrap-"] { flex-wrap:nowrap;flex-shrink:0; }
.ja-profile-toolbar .ja-profile-doc-actions { display:flex;align-items:center;flex-wrap:nowrap;gap:5px;flex-shrink:0;margin-left:auto;padding-left:8px;border-left:1px solid #E8E6E1; }
.ja-profile-toolbar .ja-pdf-btn { height:34px;padding:0 8px;gap:5px;white-space:nowrap;flex-shrink:0;line-height:1;font-size:11.5px;font-weight:600; }
.ja-profile-toolbar .ja-pdf-btn i { font-size:12px; }
.ja-profile-toolbar input[id^="ja-marketing-label-input-"] { height:34px; }
.ja-profile-toolbar .ja-pdf-toolbar-tabs { width:auto;gap:2px;flex-wrap:nowrap;flex-shrink:0; }
.ja-profile-toolbar::-webkit-scrollbar { height:3px; }
.ja-profile-toolbar::-webkit-scrollbar-thumb { background:#CBD5E1;border-radius:3px; }
.ja-profile-toolbar .ja-pdf-toolbar-tabs .ja-tab { height:34px;padding:0 8px;font-size:11.5px;border-radius:6px 6px 0 0;color:#64748B;flex-shrink:0; }
.ja-profile-toolbar .ja-pdf-toolbar-tabs .ja-tab.active { background:#EFF6FF;color:#2563EB; }
.ja-profile-toolbar .ja-pdf-btn:focus-visible,.ja-profile-toolbar .ja-tab:focus-visible { outline:2px solid #2563EB;outline-offset:2px; }
.ja-pdf-no-resume { flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#aaa;text-align:center;padding:40px; }
.ja-pdf-no-resume i { font-size:48px;opacity:.35;display:block;margin-bottom:14px; }
.ja-pdf-no-resume p { font-size:13px;opacity:.6; }
    .ja-pdf-frame { position:absolute;inset:0;z-index:1;border:0;width:100%;height:100%;display:block;background:#525659; }
.ja-pdf-loading { position:absolute;inset:0;z-index:2;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:9px;color:#d1d5db;background:#525659;font-size:12px;pointer-events:none; }
#ja-pdf-scroll::-webkit-scrollbar { width:14px; }
#ja-pdf-scroll::-webkit-scrollbar-track { background:#D1D5DB; }
#ja-pdf-scroll::-webkit-scrollbar-thumb { background:#111;border-radius:10px;border:2px solid #D1D5DB; }
.ja-right-panel { display:flex;flex-direction:column;overflow:hidden;background:#F8F7F4; }
.ja-tabs { display:flex;background:#fff;border-bottom:1px solid #E8E6E1;flex-shrink:0;padding:0 16px;}
.ja-tab { padding:11px 13px;font-size:12.5px;font-weight:600;color:#8A94A6;cursor:pointer;border-bottom:2.5px solid transparent;white-space:nowrap;display:flex;align-items:center;gap:5px;transition:color .15s;flex-shrink:0; }
.ja-tab.active { color:#2563EB;border-bottom-color:#2563EB; }
.ja-tab:hover:not(.active) { color:#1A1E2E; }
.ja-tab-badge { display:inline-flex;align-items:center;justify-content:center;min-width:18px;height:18px;padding:0 5px;border-radius:20px;background:#F0EEE9;font-size:10px;font-weight:600;color:#8A94A6; }
.ja-tab-loading { text-align:center;padding:24px 0;color:#B0B8C4;font-size:12.5px; }
.ja-right-scroll { flex:1;overflow-y:auto;padding:12px;scrollbar-width:auto;scrollbar-color:#111 #E8E6E1; }
.ja-right-scroll::-webkit-scrollbar { width:12px; }
.ja-right-scroll::-webkit-scrollbar-track { background:#E8E6E1;border-radius:10px; }
.ja-right-scroll::-webkit-scrollbar-thumb { background:#111;border-radius:10px;border:2px solid #E8E6E1; }
.ja-right-scroll::-webkit-scrollbar-thumb:hover { background:#000; }
.ja-card { background:#fff;border:1px solid #E8E6E1;border-radius:14px;padding:14px 16px;margin-bottom:10px;box-shadow:0 1px 3px rgba(15,31,61,.04); }
.ja-card-title { font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#B0B8C4;margin-bottom:12px;display:flex;align-items:center;gap:6px; }
.ja-info-row { display:flex;align-items:flex-start;justify-content:space-between;padding:7px 0;border-bottom:1px solid #F0EEE9; }
.ja-info-row:last-child { border-bottom:none;padding-bottom:0; }
.ja-info-label { font-size:11.5px;color:#8A94A6;display:flex;align-items:center;gap:5px;flex-shrink:0;width:105px; }
.ja-info-val { font-size:12.5px;font-weight:600;color:#1A1E2E;text-align:right;word-break:break-all; }
.ja-info-val a { color:#2563EB;text-decoration:none; }
.ja-info-val a:hover { text-decoration:underline; }
.ja-stage-row { display:flex;align-items:center;gap:8px; }
.ja-current-badge { display:inline-flex;align-items:center;gap:5px;padding:5px 11px;border-radius:20px;font-size:11.5px;font-weight:600;color:#fff;flex-shrink:0; }
.ja-stage-select { flex:1; width: 100%; padding:7px 10px;border-radius:9px;border:1.5px solid #E2DED8;background:#fff;font-size:12.5px;font-weight:500;color:#5A6478;cursor:pointer;font-family:'Plus Jakarta Sans',sans-serif;transition:border-color .15s; } 
.ja-stage-select:hover,.ja-stage-select:focus { border-color:#2563EB;outline:none; }
.ja-note { background:#fff;border-radius:0 10px 10px 0;border:1px solid #E8E6E1;border-left:3px solid #2563EB;padding:10px 13px;margin-bottom:8px; }
.ja-note-meta { display:flex;align-items:center;justify-content:space-between;margin-bottom:5px; }
.ja-note-author { font-size:11.5px;font-weight:600;color:#1A1E2E;display:flex;align-items:center;gap:5px; }
.ja-note-time { font-size:10.5px;color:#B0B8C4; }
.ja-note-body { font-size:12.5px;color:#5A6478;line-height:1.6; }
.ja-note-actions { display:flex;gap:5px;margin-top:7px; }
.ja-note-btn { display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:7px;border:1px solid #E8E6E1;background:transparent;cursor:pointer;font-size:11px;color:#8A94A6;font-family:'Plus Jakarta Sans',sans-serif;transition:background .12s; }
.ja-note-btn:hover { background:#F8F7F4;color:#1A1E2E; }
.ja-add-note { background:#fff;border:1px solid #E8E6E1;border-radius:14px;padding:14px; }
.ja-note-textarea { width:100%;border:1px solid #E2DED8;border-radius:9px;padding:9px 12px;font-size:12.5px;font-family:'Plus Jakarta Sans',sans-serif;color:#1A1E2E;background:#F8F7F4;resize:none;outline:none;transition:border-color .15s;line-height:1.6; }
.ja-note-textarea:focus { border-color:#2563EB;background:#fff; }
.ja-save-note-btn { margin-top:8px;display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:9px;border:none;background:#2563EB;color:#fff;cursor:pointer;font-size:12.5px;font-weight:600;font-family:'Plus Jakarta Sans',sans-serif;transition:background .15s; }
.ja-save-note-btn:hover { background:#1d4ed8; }
.ja-qa-item { background:#fff;border:1px solid #E8E6E1;border-radius:10px;padding:11px 13px;margin-bottom:8px;border-left:3px solid #E8E6E1; }
.ja-qa-q { font-size:12px;font-weight:600;color:#1A1E2E;margin-bottom:7px; }
.ja-qa-badge { display:inline-flex;align-items:center;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700; }
.ja-qa-yes { background:#ECFDF5;color:#065F46; }
.ja-qa-no  { background:#FEF2F2;color:#991B1B; }
.ja-action-btns { display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px; }
.ja-btn { flex:1;min-width:110px;display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:8px 12px;border-radius:10px;border:none;cursor:pointer;font-size:12.5px;font-weight:600;font-family:'Plus Jakarta Sans',sans-serif;transition:opacity .15s; }
.ja-btn:hover { opacity:.85; }
.ja-btn-blue  { background:#EFF6FF;color:#1D4ED8; }
.ja-btn-green { background:#ECFDF5;color:#065F46; }
.ja-btn-red   { background:#FFF1F2;color:#EF4444; }
.ja-schedule-card { background:#ECFDF5;border:1px solid #A7F3D0;border-radius:10px;padding:12px 14px; }
.ja-schedule-date { font-size:14px;font-weight:700;color:#065F46;margin-bottom:3px; }
.ja-schedule-type { font-size:12px;color:#059669; }
.ja-interviewer-row { display:flex;align-items:center;justify-content:space-between;padding:8px 10px;background:#F8F7F4;border-radius:9px;border:1px solid #E8E6E1;margin-bottom:6px; }
.ja-interviewer-avatar { width:28px;height:28px;border-radius:50%;background:#EFF6FF;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:#1D4ED8;flex-shrink:0; }
.ja-badge-accept { background:#ECFDF5;color:#065F46; }
.ja-badge-refuse { background:#FEF2F2;color:#991B1B; }
.ja-badge-pending { background:#FFF7ED;color:#92400E; }
.ja-small-badge { font-size:11px;font-weight:600;padding:3px 9px;border-radius:20px; }
.ja-nav-loading { opacity:.5;pointer-events:none;transition:opacity .2s; }
</style>

<div class="ja-two-col-wrap" data-ats-application-id="{{ $application->id }}" data-ats-sync-url="{{ route('admin.job-applications.show', $application->id) }}">

    {{-- ── HEADER ── --}}
    <div class="ja-header">
        <img src="{{ $application->photo_url }}" alt=""
             class="ja-header-avatar"
             onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
        <div class="ja-header-avatar-initials" style="display:none">{{ $initials }}</div>

        <div class="ja-header-meta">
            <h2>{{ ucwords($application->full_name) }}</h2>
            <p>
                @if($application->job)
                    {{ $application->job->title }} · Applied {{ $application->created_at->timezone($global->timezone)->format('d M Y, h:i A') }}
                @else
                    @if($application->origin_label)
                        {{ $application->origin_label }} ·
                    @endif
                    Added {{ $application->created_at->timezone($global->timezone)->format('d M Y, h:i A') }}
                @endif
                @if($application->originUser)
                    · Added by {{ $application->originUser->name }}
                @endif
            </p>
        </div>

        <div class="ja-header-pills">
            <span class="ja-pill ja-pill-stage" style="background: {{ $stagePillBg }};">
                {{ $application->status ? ucwords($application->status->status) : 'Internal' }}
            </span>
            <span class="ja-pill ja-pill-cat {{ $detailCatClass }}">{{ ucfirst($detailCatName) }}</span>
        </div>

        <div class="ja-nav-arrows" id="ja-nav-arrows">
            <button type="button" class="ja-nav-btn" id="ja-prev-btn" onclick="jaNavigate('prev', {{ $application->id }})" title="Previous applicant" disabled>
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
            </button>
            <span class="ja-nav-counter" id="ja-nav-counter">—</span>
            <button type="button" class="ja-nav-btn" id="ja-next-btn" onclick="jaNavigate('next', {{ $application->id }})" title="Next applicant" disabled>
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
            </button>
        </div>

        <button type="button" class="right-side-toggle ja-close-btn" title="@lang('app.close')">
            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    <div class="ja-body">

        {{-- ── LEFT: PDF ── --}}
        <div class="ja-pdf-panel">
            <div class="ja-pdf-toolbar ja-profile-toolbar">
                

                <div class="ja-profile-tags">

@if($user->cans('edit_job_applications'))
<div style="display:flex;align-items:center;gap:6px;" id="ja-marketing-wrap-{{ $application->id }}">
    <button type="button"
            id="ja-marketing-btn-{{ $application->id }}"
            onclick="jaToggleMarketing({{ $application->id }})"
            class="ja-pdf-btn"
            style="{{ $application->is_marketing ? 'background:#ECFDF5;color:#065F46;border-color:#A7F3D0;' : '' }}">
        <i class="fa fa-bullhorn" id="ja-marketing-icon-{{ $application->id }}"></i>
        <span id="ja-marketing-label-text-{{ $application->id }}">{{ $application->is_marketing ? 'In Candidate Marketing' : 'Candidate Marketing' }}</span>
    </button>

    {{-- Label input — only visible once marketing is ON --}}
    <input type="text"
           id="ja-marketing-label-input-{{ $application->id }}"
           value="{{ $application->marketing_label }}"
           placeholder="Marketing label…"
           onchange="jaSaveMarketingLabel({{ $application->id }})"
           style="display:{{ $application->is_marketing ? 'inline-block' : 'none' }};width:140px;padding:5px 9px;border-radius:8px;border:1px solid #E2DED8;font-size:12px;font-family:'Plus Jakarta Sans',sans-serif;">
</div>
@endif

@include('admin.job-applications.partials.temp-staffing-control')
                </div>
                <div class="ja-pdf-toolbar-tabs">

<script>
function jaToggleMarketing(appId) {
    var btn   = document.getElementById('ja-marketing-btn-' + appId);
    var icon  = document.getElementById('ja-marketing-icon-' + appId);
    var label = document.getElementById('ja-marketing-label-text-' + appId);
    var input = document.getElementById('ja-marketing-label-input-' + appId);

    icon.className = 'fa fa-spinner fa-spin';

    $.ajax({
        type: 'POST',
        url: "{{ route('admin.job-applications.toggle-marketing', ':id') }}".replace(':id', appId),
        data: {
            _token: '{{ csrf_token() }}',
            marketing_label: input ? input.value : ''
        },
        success: function (res) {
            icon.className = 'fa fa-bullhorn';

            if (res.status !== 'success') return;

            // Response shape can vary — payload may sit under res.data.*
            // or directly on res.* depending on how Reply::successWithData
            // serializes. Check both rather than assuming one.
            var payload = (res.data && typeof res.data.is_marketing !== 'undefined')
                ? res.data
                : res;

            var isOn = !!payload.is_marketing;

            label.textContent = isOn ? 'In Candidate Marketing' : 'Candidate Marketing';
            btn.style.background  = isOn ? '#ECFDF5' : '';
            btn.style.color       = isOn ? '#065F46' : '';
            btn.style.borderColor = isOn ? '#A7F3D0' : '';
            if (input) {
                input.style.display = isOn ? 'inline-block' : 'none';
                if (typeof payload.marketing_label !== 'undefined' && payload.marketing_label !== null) {
                    input.value = payload.marketing_label;
                }
            }
        },
        error: function () {
            icon.className = 'fa fa-bullhorn';
        }
    });
}

function jaSaveMarketingLabel(appId) {
    var input = document.getElementById('ja-marketing-label-input-' + appId);
    $.ajax({
        type: 'POST',
        url: "{{ route('admin.job-applications.update-marketing-label', ':id') }}".replace(':id', appId),
        data: { _token: '{{ csrf_token() }}', marketing_label: input.value }
    });
}
</script>
                    @if(count($answers) > 0)
                    <div class="ja-tab" data-tab="qa">
                        <i class="fa fa-question-circle-o" style="font-size:11px"></i> @lang('modules.front.additionalDetails')
                    </div>
                    @endif
                    <div class="ja-tab" data-tab="sms">
                        <i class="fa fa-comments-o" style="font-size:11px"></i> SMS Conversation
                    </div>
                    <div class="ja-tab" data-tab="email-conversation" id="ja-email-tab-{{ $application->id }}">
                        <i class="fa fa-envelope-o" style="font-size:11px"></i> Email Conversation
                        <span id="ja-email-unread-{{ $application->id }}" class="ja-tab-badge" style="display:none">0</span>
                    </div>
                    <div class="ja-tab" data-tab="history">
                        <i class="fa fa-history"></i> History
                    </div>
                    <div class="ja-tab" data-tab="client-reviews">
                        <i class="fa fa-user-o"></i> Client Reviews
                        <span id="ja-client-review-unread" class="ja-tab-badge" style="display:none">0</span>
                    </div>
                      @if(!is_null($application->schedule))
                        <div class="ja-tab" data-tab="schedule">
                            <i class="fa fa-calendar" stylfe="font-size:11px"></i> @lang('modules.interviewSchedule.scheduleDetail')
                        </div>
                    @endif
                </div>
                <div class="ja-profile-doc-actions">
                    <button type="button" class="ja-pdf-btn" onclick="jaShowJobDesc()">
                        <i class="fa fa-file-text-o"></i> Job Description
                    </button>
                    @include('admin.job-applications.partials.resume-update-control')
                    @if($resumeUrl)
                        <a href="{{ $resumeUrl }}" target="_blank" class="ja-pdf-btn ja-current-resume-link"><i class="fa fa-external-link"></i> View</a>
                        <a href="{{ $resumeUrl }}" download class="ja-pdf-btn ja-current-resume-link"><i class="fa fa-download"></i> Download</a>
                    @endif
                </div>
            </div>
            @if($resumeUrl)
                <div id="ja-pdf-container" style="flex:1;min-height:0;position:relative;overflow:hidden;background:#525659;">
                    <div id="ja-pdf-loading" class="ja-pdf-loading"><i class="fa fa-spinner fa-spin" style="font-size:22px;"></i><span>Loading CV...</span></div>
                    <div id="ja-pdf-error" style="display:none;position:absolute;inset:0;z-index:3;flex-direction:column;align-items:center;justify-content:center;gap:10px;color:#d1d5db;background:#525659;"><i class="fa fa-exclamation-triangle" style="font-size:28px"></i><span style="font-size:13px">Couldn't render this PDF.</span><button type="button" class="ja-pdf-btn ja-pdf-btn-primary" onclick="jaShowNativeApplicantPdf()">Show PDF</button></div>
                    <iframe id="ja-pdf-frame" class="ja-pdf-frame" title="Applicant CV" style="display:none"></iframe>
                    <div id="ja-pdf-scroll" style="position:absolute;inset:0;overflow-y:auto;overflow-x:hidden;display:none;padding:16px;scrollbar-width:auto;scrollbar-color:#111 #D1D5DB;">
                        <div id="ja-pdf-pages" style="display:flex;flex-direction:column;align-items:center;gap:16px;"></div>
                    </div>
                </div>
            @else
                <div class="ja-pdf-no-resume">
                    <i class="fa fa-file-pdf-o"></i>
                    <p>No resume uploaded.</p>
                </div>
            @endif
        </div>

        {{-- ── RIGHT: TABS ── --}}
        <div class="ja-right-panel">
            <div class="ja-tabs">
                <div class="ja-tab active" data-tab="details">
                    <i class="fa fa-user" style="font-size:11px"></i> @lang('app.details')
                </div>
                <div class="ja-tab" data-tab="notes">
                    <i class="fa fa-sticky-note-o" style="font-size:11px"></i> @lang('modules.jobApplication.applicantNotes')
                </div>
                <div class="ja-tab" data-tab="client-notes">
                    <i class="fa fa-building" style="font-size:11px"></i> Client Notes
                </div>
            </div>

            <div class="ja-right-scroll">
                <div id="ja-tab-client-reviews" class="ja-tab-pane" style="display:none">
                    @include('admin.job-applications.partials.client-review-panel')
                </div>
                {{-- ── HISTORY TAB ── --}}
                <div id="ja-tab-history" class="ja-tab-pane" style="display:none" data-url="{{ route('admin.job-applications.profile-tab', [$application->id, 'history']) }}">
                    <div class="ja-tab-loading">Open the History tab to load activity.</div>
                </div>

                <div id="ja-tab-email-conversation" class="ja-tab-pane" style="display:none">
                    <div class="ja-card">
                        <div class="ja-card-title"><i class="fa fa-envelope-o" style="font-size:11px"></i> Email Conversation</div>
                        <div id="ja-email-conversation-{{ $application->id }}" style="display:flex;flex-direction:column;gap:9px;max-height:calc(100vh - 245px);overflow-y:auto;padding:4px 2px;">
                            <div class="ja-tab-loading"><i class="fa fa-spinner fa-spin"></i> Loading email conversation...</div>
                        </div>
                    </div>
                </div>

                <div id="ja-tab-sms" class="ja-tab-pane" style="display:none">
                    <div class="ja-card" id="ja-sms-history-card-{{ $application->id }}">
                        <div class="ja-card-title" style="justify-content:space-between;">
                            <span><i class="fa fa-comments-o" style="font-size:11px"></i> SMS Conversation</span>
                            <span style="display:flex;align-items:center;gap:8px;">
                                <span id="ja-sms-history-count-{{ $application->id }}" style="font-size:10.5px;color:#8892A0;font-weight:600;">{{ $application->smsMessages->count() }} messages</span>
                                @if($user->cans('edit_job_applications') && $application->phone)
                                    <button type="button" onclick="jaOpenSmsModal({{ $application->id }})"
                                            style="display:inline-flex;align-items:center;gap:5px;border:0;border-radius:8px;background:#2563EB;color:#fff;padding:6px 10px;font-size:11px;font-weight:700;cursor:pointer;">
                                        <i class="fa fa-paper-plane" style="font-size:10px"></i> Send SMS
                                    </button>
                                @endif
                            </span>
                        </div>
                        <div id="ja-sms-history-{{ $application->id }}" style="display:flex;flex-direction:column;gap:9px;max-height:calc(100vh - 245px);overflow-y:auto;padding:4px 2px;">
                            @forelse($application->smsMessages as $smsMessage)
                                <div style="display:flex;justify-content:{{ $smsMessage->direction === 'outbound' ? 'flex-end' : 'flex-start' }};">
                                    <div style="max-width:86%;border-radius:12px;padding:9px 11px;{{ $smsMessage->direction === 'outbound' ? 'background:#2563EB;color:#fff;border-bottom-right-radius:4px;' : 'background:#F1F3F7;color:#1A1E2E;border-bottom-left-radius:4px;' }}">
                                        <div style="font-size:12px;line-height:1.5;white-space:pre-wrap;overflow-wrap:anywhere;">{{ $smsMessage->message }}</div>
                                        <div style="margin-top:5px;font-size:9.5px;opacity:.7;text-align:{{ $smsMessage->direction === 'outbound' ? 'right' : 'left' }};">
                                            {{ $smsMessage->direction === 'outbound' ? ($smsMessage->user?->name ?? 'ATS') : $application->full_name }}
                                            &bull; {{ ($smsMessage->received_at ?? $smsMessage->created_at)?->copy()->timezone('America/Toronto')->format('M j, Y g:i A') }} ET
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div id="ja-sms-history-empty-{{ $application->id }}" style="padding:30px 8px;text-align:center;color:#A0A8B5;font-size:12px;">
                                    <i class="fa fa-comment-o" style="display:block;font-size:24px;margin-bottom:8px;"></i>
                                    No SMS messages yet.
                                </div>
                            @endforelse
                        </div>
                        @if(!$application->phone)
                            <p style="margin-top:12px;padding:10px;border-radius:9px;background:#FFF7ED;color:#9A5B13;font-size:11.5px;text-align:center;">Add a phone number to send SMS messages.</p>
                        @endif
                        <p style="margin:9px 2px 0;font-size:10.5px;color:#A0A8B5;">Incoming replies appear here through the configured Telnyx webhook.</p>
                    </div>
                </div>
                @if(false)
                <div id="ja-tab-history" class="ja-tab-pane" style="display:none">

                @if($application->statusHistories->isNotEmpty())
                <div class="ja-card" style="margin-bottom:10px">
                    <div class="ja-card-title"><i class="fa fa-exchange" style="font-size:11px"></i> Stage Activity</div>
                    @foreach($application->statusHistories as $hist)
                    <div style="display:flex;align-items:flex-start;gap:9px;padding:9px 0;border-bottom:1px solid #F0EEE9">
                        <div style="width:26px;height:26px;border-radius:50%;background:{{ $hist->notes ? '#FFF7ED' : '#EFF6FF' }};display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px">
                            <i class="fa {{ $hist->notes ? 'fa-star' : (is_null($hist->user_id) ? 'fa-cogs' : 'fa-user') }}" style="font-size:10px;color:{{ $hist->notes ? '#EA580C' : '#2563EB' }}"></i>
                        </div>
                        <div style="flex:1;min-width:0">
                            <div style="font-size:12.5px;color:#1A1E2E;line-height:1.5">
                                <strong>{{ $hist->user ? ucwords($hist->user->name) : 'Auto (screening rule)' }}</strong>
                                @if($hist->notes)
                                    &mdash; <strong>{{ $hist->notes }}</strong>
                                @else
                                    moved this applicant
                                    @if($hist->fromStatus)
                                        from <strong>{{ ucwords(str_replace('_',' ',$hist->fromStatus->status)) }}</strong>
                                    @endif
                                    @if($hist->toStatus)
                                        to <strong>{{ ucwords(str_replace('_',' ',$hist->toStatus->status)) }}</strong>
                                    @endif
                                @endif
                            </div>
                            <div style="font-size:11px;color:#B0B8C4;margin-top:2px">
                                {{ $hist->created_at->timezone('America/Toronto')->format('d M Y, h:i A') }}
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif

                @foreach($previousApps as $prev)
                    <div class="ja-card" style="margin-bottom:10px">
                        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid #F0EEE9">
                            <div style="flex:1;min-width:0">
                                <div style="font-size:13.5px;font-weight:700;color:#1A1E2E;margin-bottom:3px">{{ ucwords($prev->job?->title ?? '—') }}</div>
                                <div style="font-size:11px;color:#8A94A6;display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                                    <span><i class="fa fa-calendar-o" style="font-size:10px;margin-right:3px"></i>{{ $prev->created_at?->format('d M Y') }}</span>
                                    @if($prev->location)<span><i class="fa fa-map-marker" style="font-size:10px;margin-right:3px"></i>{{ ucwords($prev->location->location) }}</span>@endif
                                </div>
                            </div>
                            <span style="display:inline-flex;align-items:center;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;color:#fff;flex-shrink:0;background:{{ $prev->status?->color ?? '#6B7280' }}">
                                {{ ucwords(str_replace('_', ' ', $prev->status?->status ?? '—')) }}
                            </span>
                        </div>
                        @if($prev->cover_letter)
                        <div style="margin-bottom:10px;padding-bottom:10px;border-bottom:1px solid #F0EEE9">
                            <div style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#B0B8C4;margin-bottom:5px"><i class="fa fa-file-text-o" style="font-size:10px"></i> Cover Letter</div>
                            <p style="font-size:12px;color:#5A6478;line-height:1.6;margin:0;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden">{{ $prev->cover_letter }}</p>
                        </div>
                        @endif
                       
                        @php $prevAnswers = $prev->answers->filter(fn($a) => !empty(trim($a->answer))); @endphp

                        @if($prevAnswers->isNotEmpty())
                        <div style="margin-bottom:10px;padding-bottom:10px;border-bottom:1px solid #F0EEE9">
                            <div style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#B0B8C4;margin-bottom:6px"><i class="fa fa-question-circle-o" style="font-size:10px"></i> Screening Answers</div>
                            @foreach($prevAnswers as $ans)
                            <div style="margin-bottom:6px">
                                <div style="font-size:11.5px;font-weight:600;color:#1A1E2E;margin-bottom:2px">{{ $ans->question?->question ?? '—' }}</div>
                                <div style="font-size:12px;color:#5A6478">
                                    @if(strtolower(trim($ans->answer)) === 'yes')<span style="display:inline-flex;align-items:center;padding:2px 9px;border-radius:20px;font-size:11px;font-weight:700;background:#ECFDF5;color:#065F46"><i class="fa fa-check" style="font-size:9px;margin-right:4px"></i> Yes</span>
                                    @elseif(strtolower(trim($ans->answer)) === 'no')<span style="display:inline-flex;align-items:center;padding:2px 9px;border-radius:20px;font-size:11px;font-weight:700;background:#FEF2F2;color:#991B1B"><i class="fa fa-times" style="font-size:9px;margin-right:4px"></i> No</span>
                                    @else{{ ucfirst($ans->answer) }}@endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @endif
                        @php $prevNotes = $prev->notes; @endphp
                        @if($prevNotes->isNotEmpty())
                        <div>
                            <div style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#B0B8C4;margin-bottom:6px"><i class="fa fa-sticky-note-o" style="font-size:10px"></i> Notes <span style="display:inline-flex;align-items:center;justify-content:center;min-width:16px;height:16px;padding:0 4px;border-radius:20px;background:#F0EEE9;font-size:10px;color:#8A94A6;margin-left:4px">{{ $prevNotes->count() }}</span></div>
                            @foreach($prevNotes as $pNote)
                            <div style="background:#F8F7F4;border-radius:8px;border-left:3px solid #2563EB;padding:8px 11px;margin-bottom:6px">
                                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px">
                                    <span style="font-size:11.5px;font-weight:600;color:#1A1E2E"><i class="fa fa-user-circle" style="font-size:12px;color:#2563EB;margin-right:4px"></i>{{ ucwords($pNote->user?->name ?? '—') }}</span>
                                    <span style="font-size:10.5px;color:#B0B8C4">{{ $pNote->created_at?->format('d M Y') }}</span>
                                </div>
                                <p style="font-size:12px;color:#5A6478;line-height:1.6;margin:0">{{ ucfirst($pNote->note_text) }}</p>
                            </div>
                            @endforeach
                        </div>
                        @endif
                        @if(!$prev->cover_letter && $prevAnswers->isEmpty() && $prevNotes->isEmpty())
                        <div style="font-size:12px;color:#B0B8C4;text-align:center;padding:8px 0"><i class="fa fa-info-circle" style="margin-right:5px"></i> No additional details recorded.</div>
                        @endif
                    </div>
                    @endforeach
                </div>
                @endif

                {{-- ── DETAILS TAB ── --}}
                <div id="ja-tab-details" class="ja-tab-pane">
                    {{-- Quick actions --}}
                    <div class="ja-card">
                        <div class="ja-action-btns">
                            @if($user->cans('add_schedule') && is_null($application->schedule))
                            @php
                                $statusName = $application->status ? (string) $application->status->status : '';
                                $isInterviewStage = stripos($statusName, 'interview') !== false;
                            @endphp
                            <a id="schedule-interview-action-{{ $application->id }}"
                               onclick="createSchedule('{{ $application->id }}')" href="javascript:;"
                               class="ja-btn ja-btn-blue" @if(!$isInterviewStage) style="display:none" @endif>
                                <i class="fa fa-calendar-plus-o"></i> @lang('modules.interviewSchedule.scheduleInterview')
                            </a>
                            @endif
                            @if($application->status?->status == 'hired' && is_null($application->onboard))

                            <a href="{{ route('admin.job-onboard.create') }}?id={{ $application->id }}" class="ja-btn ja-btn-green">
                                <i class="fa fa-rocket"></i> @lang('app.startOnboard')
                            </a>
                            @endif
                            @if($user->cans('view_job_applications') && $user->cans('edit_job_applications'))
                            <button type="button" onclick="ccOpen('email', [{type: 'application', id: {{ (int) $application->id }}}])" class="ja-btn ja-btn-blue">
                                <i class="fa fa-envelope-o"></i> Send Email
                            </button>
                            @endif
                            @if($user->cans('edit_job_applications') && $application->phone)
                            <button type="button" onclick="jaOpenSmsModal({{ $application->id }})" class="ja-btn ja-btn-blue">
                                <i class="fa fa-commenting-o"></i> Send SMS
                            </button>
                            <button type="button" onclick="jaOpenCallModal({{ $application->id }})" class="ja-btn ja-btn-blue" aria-label="Call candidate" title="Call candidate">
                                <i class="fa fa-phone" aria-hidden="true"></i>
                            </button>
                            @endif
                            @if(auth()->user()->hasRole('admin'))
                            <button type="button" onclick="deleteApplication({{ $application->id }})" class="ja-btn ja-btn-red">
                                <i class="fa fa-trash-o"></i> @lang('app.delete')
                            </button>
                            @endif
                        </div>

                        @if($user->cans('edit_job_applications') && $application->phone)
                        <style>
                            #call-panel { max-height:calc(100dvh - 32px); overflow:auto; pointer-events:auto; }
                            #candidate-calls.call-floating { background:transparent!important; pointer-events:none; }
                            #candidate-calls.call-minimized .call-extra { display:none!important; }
                            #candidate-calls.call-minimized #call-panel { width:300px!important; padding:18px!important; border-radius:18px!important; }
                            #candidate-calls.call-minimized #call-duration { font-size:24px!important; }
                            #candidate-calls.call-minimized #call-status { margin:8px 0!important; }
                            #candidate-calls.call-minimized .call-controls { margin:12px 0 0!important; }
                            #candidate-calls.call-minimized .call-controls button { width:44px!important; height:44px!important; }
                            #candidate-calls.call-minimized #call-drag-handle { margin-bottom:14px!important; }
                        </style>
                        <div id="candidate-calls" style="display:none;position:fixed;inset:0;z-index:10000;align-items:center;justify-content:center;background:rgba(15,23,42,.55);padding:16px" class="fixed inset-0 z-[10000] hidden items-center justify-center bg-slate-950/55 p-4"
                             data-start="{{ route('admin.candidate-calls.start', $application->id) }}"
                             data-token="{{ csrf_token() }}" data-auto-record="1" data-embedded="1" data-application-id="{{ $application->id }}"
                             role="dialog" aria-modal="true" aria-labelledby="call-title-{{ $application->id }}">

                            <section id="call-panel" style="position:relative;width:100%;max-width:390px;border-radius:28px;background:linear-gradient(155deg,#182c4d,#0c1628);color:#fff;padding:32px 24px;text-align:center;box-shadow:0 24px 80px #0006" onclick="event.stopPropagation()">
                                <button type="button" id="call-close" style="position:absolute;right:18px;top:12px;background:none;border:0;color:#aebdd2;font-size:26px" aria-label="Close call window">&times;</button>
                                <button type="button" id="call-minimize" style="position:absolute;right:52px;top:12px;background:none;border:0;color:#aebdd2;font-size:24px" title="Minimize call" aria-label="Minimize call" aria-expanded="true">−</button>
                                <p id="call-drag-handle" title="Drag to move call window" style="cursor:move;touch-action:none;user-select:none;font-size:11px;letter-spacing:2px;color:#9eb3cf;margin:0 60px 24px 0;text-align:left">⠿ CANDIDATE CALL</p>
                                <div class="call-extra" style="display:grid;place-items:center;width:80px;height:80px;margin:0 auto 18px;border-radius:50%;background:#ffffff15;border:1px solid #ffffff20;font-size:30px"><i class="fa fa-user" aria-hidden="true"></i></div>
                                <h3 id="call-title-{{ $application->id }}" style="color:white;font-size:22px;font-weight:600;margin:0 0 6px">{{ $application->full_name }}</h3>
                                <p class="call-extra" style="color:#aebdd2;margin:0">{{ $application->phone }}</p>
                                <p id="call-status" style="min-height:24px;margin:22px 0 6px;color:#c1d4f0;font-size:14px" role="status" aria-live="polite">Ready to call</p>
                                <div id="call-duration" style="font-size:36px;font-variant-numeric:tabular-nums;font-weight:300;letter-spacing:2px">00:00:00</div>
                                <p id="call-recording" hidden style="color:#fca5a5;font-size:12px;margin-top:8px">● Recording call</p>
                                <div class="call-controls" style="display:flex;justify-content:center;gap:18px;margin:28px 0">
                                    <button type="button" id="call-start" title="Call candidate" aria-label="Call candidate" style="width:68px;height:68px;border:0;border-radius:50%;background:#16a776;color:white;font-size:25px"><i class="fa fa-phone" aria-hidden="true"></i></button>
                                    <button type="button" id="call-mute" style="width:68px;height:68px;border:1px solid #ffffff30;border-radius:50%;background:#ffffff12;color:white;font-size:12px" disabled>Mute</button>
                                    <button type="button" id="call-end" title="End call" aria-label="End call" style="width:68px;height:68px;border:0;border-radius:50%;background:#e5484d;color:white;font-size:25px" disabled><i class="fa fa-phone" style="transform:rotate(135deg)" aria-hidden="true"></i></button>
                                </div>
                                <div id="call-consent" class="call-extra" style="text-align:left;background:#ffffff0a;border-radius:12px;padding:12px;font-size:12px;color:#bfcee2">
                                    <label title="Confirm candidate consent to recording and AI transcription"><input type="checkbox" id="call-consent-check" aria-label="Confirm candidate consent to recording and AI transcription"></label>
                                    <button type="button" id="call-record" hidden>Start recording</button>
                                </div>
                                <button type="button" id="call-retry-upload" hidden>Retry saving call</button>
                                <audio id="call-remote" autoplay></audio>
                            </section>
                        </div>
                        @endif


                        @if($user->cans('edit_job_applications') && $application->phone)
                        <div id="ja-sms-modal-{{ $application->id }}" class="absolute inset-0 z-[300] hidden items-center justify-center bg-black/50 p-4">
                            <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl" onclick="event.stopPropagation()">
                                <div class="mb-4 flex items-center justify-between">
                                    <h3 class="text-[16px] font-bold text-[#1A1E2E]">Send SMS to {{ $application->full_name }}</h3>
                                    <button type="button" onclick="jaCloseSmsModal({{ $application->id }})" class="text-xl text-gray-400">&times;</button>
                                </div>
                                <div class="mb-3 text-[12px] text-[#5A6478]">To: {{ $application->phone }}</div>
                                <textarea id="ja-sms-message-{{ $application->id }}" maxlength="1600" rows="6" class="w-full rounded-xl border border-[#D9E1EC] p-3 text-[13px] focus:border-blue-500 focus:outline-none">{{ $defaultSmsMessage }}</textarea>
                                <div id="ja-sms-count-{{ $application->id }}" class="mt-1 text-right text-[11px] text-[#8892A0]">{{ strlen($defaultSmsMessage) }}/1600</div>
                                <div class="mt-5 flex justify-end gap-3">
                                    <button type="button" onclick="jaCloseSmsModal({{ $application->id }})" class="rounded-lg bg-gray-100 px-4 py-2 text-[13px] font-semibold text-gray-600">Cancel</button>
                                    <button type="button" id="ja-send-sms-btn-{{ $application->id }}" onclick="jaSendApplicantSms({{ $application->id }})" class="rounded-lg bg-blue-600 px-5 py-2 text-[13px] font-bold text-white">Send SMS</button>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if($user->cans('edit_job_applications'))
                        <button type="button" class="ja-btn ja-btn-blue" style="width:100%;margin:0 0 14px" data-client-review-open aria-haspopup="dialog" aria-controls="ja-client-review-modal">
                            <i class="fa fa-share-square-o"></i> Send profile to client
                        </button>
                        @endif

                        {{-- Stage mover --}}
                        @if($user->cans('edit_job_applications'))
                        <div style="margin-bottom:14px">
                            <div class="ja-card-title" style="margin-bottom:8px">
                                <i class="fa fa-exchange" style="font-size:11px"></i> Move to Stage
                            </div>
                            <div class="ja-stage-row">
                                <span class="ja-current-badge" style="background: {{ $currentStatus?->color ?? '#6B7280' }};">
                                    <i class="fa fa-check" style="font-size:9px"></i>
                                    {{ ucwords(str_replace('_', ' ', $currentStatus?->status ?? '')) }}
                                </span>
                                <i class="fa fa-long-arrow-right" style="color:#B0B8C4;font-size:14px;flex-shrink:0"></i>
                                <select class="ja-stage-select" id="stage-mover-select-{{ $application->id }}"
                                    onchange="jaMoveFromDetail({{ $application->id }}, this.value, this.options[this.selectedIndex].text, {{ $currentStatusId }})">
                                    <option value="">Select stage…</option>
                                    @foreach($allStatuses as $stageOption)
                                        @if($stageOption->id !== $currentStatusId)
                                        <option value="{{ $stageOption->id }}" data-color="{{ $stageOption->color ?? '#6B7280' }}">{{ ucwords(str_replace('_', ' ', $stageOption->status)) }}</option>
                                        @endif
                                    @endforeach
                                    @if($application->job_id && $user->cans('edit_jobs'))
                                        <option value="__add_stage__">+ Add new stage…</option>
                                    @endif
                                </select>
                            </div>
                            @if($application->job_id && $user->cans('edit_jobs'))
                            <form id="ja-stage-form-{{ $application->id }}" style="display:none;margin-top:12px;padding:12px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px" onsubmit="event.preventDefault();jaSaveProfileStage({{ $application->id }});">
                                <label for="ja-stage-name-{{ $application->id }}" style="display:block;font-size:12px;font-weight:600;margin-bottom:6px">Stage name</label>
                                <input id="ja-stage-name-{{ $application->id }}" name="status_name" type="text" maxlength="255" required class="ja-stage-select" placeholder="e.g. Reference check">
                                <div style="display:flex;align-items:center;gap:8px;margin-top:10px">
                                    <label for="ja-stage-color-{{ $application->id }}" style="font-size:12px">Colour</label>
                                    <input id="ja-stage-color-{{ $application->id }}" name="status_color" type="color" value="#2563EB" style="width:32px;height:28px;padding:2px;border:1px solid #E2E8F0;border-radius:5px;background:#fff">
                                    <button type="button" class="ja-pdf-btn" style="margin-left:auto" onclick="jaToggleProfileStage({{ $application->id }}, false)">Cancel</button>
                                    <button id="ja-stage-save-{{ $application->id }}" type="submit" class="ja-pdf-btn ja-pdf-btn-primary">Save stage</button>
                                </div>
                                <p style="font-size:11px;color:#64748B;margin:8px 0 0">Added to this job's pipeline for all its candidates.</p>
                            </form>
                            <div id="ja-stage-message-{{ $application->id }}" role="status" aria-live="polite" style="display:none;margin-top:8px;font-size:12px"></div>
                            @endif
                        </div>
                        @endif
                    </div>

                    {{-- ── PERSONAL INFO CARD ── --}}
                    <div class="ja-card" id="ja-info-card-{{ $application->id }}">
                        <div class="ja-card-title" style="justify-content:space-between;">
                            <span><i class="fa fa-user" style="font-size:11px"></i> @lang('Personal Information')</span>
                            @if($user->cans('edit_job_applications'))
                            <button type="button" onclick="jaToggleInfoEdit({{ $application->id }})"
                                    id="ja-info-edit-btn-{{ $application->id }}"
                                    style="display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:7px;
                                           border:1px solid #E2DED8;background:#F8F7F4;cursor:pointer;font-size:11px;
                                           color:#5A6478;font-family:inherit;transition:all .15s;"
                                    onmouseover="this.style.background='#EFF6FF';this.style.color='#2563EB';this.style.borderColor='#2563EB'"
                                    onmouseout="this.style.background='#F8F7F4';this.style.color='#5A6478';this.style.borderColor='#E2DED8'">
                                <i class="fa fa-pencil" style="font-size:10px"></i> Edit
                            </button>
                            @endif
                        </div>

                        {{-- VIEW MODE --}}
                        <div id="ja-info-view-{{ $application->id }}">
                            <div class="ja-info-row">
                                <span class="ja-info-label"><i class="fa fa-id-card-o" style="font-size:11px"></i> @lang('app.name')</span>
                                <span class="ja-info-val" id="ja-display-name-{{ $application->id }}">{{ ucwords($application->full_name) }}</span>
                            </div>
                            <div class="ja-info-row">
                                <span class="ja-info-label"><i class="fa fa-envelope-o" style="font-size:11px"></i> @lang('app.email')</span>
                                <span class="ja-info-val" id="ja-display-email-{{ $application->id }}">
                                    <a href="mailto:{{ $application->email }}">{{ $application->email }}</a>
                                </span>
                            </div>
                            <div class="ja-info-row">
                                <span class="ja-info-label"><i class="fa fa-phone" style="font-size:11px"></i> @lang('app.phone')</span>
                                <span class="ja-info-val" id="ja-display-phone-{{ $application->id }}">
                                    <a href="tel:{{ $application->phone }}">{{ $application->phone }}</a>
                                </span>
                            </div>
                            @if (!is_null($application->gender))
                            <div class="ja-info-row">
                                <span class="ja-info-label"><i class="fa fa-venus-mars" style="font-size:11px"></i> @lang('app.gender')</span>
                                <span class="ja-info-val">{{ ucfirst($application->gender) }}</span>
                            </div>
                            @endif
                            @if (!is_null($application->dob))
                            <div class="ja-info-row">
                                <span class="ja-info-label"><i class="fa fa-birthday-cake" style="font-size:11px"></i> @lang('app.dob')</span>
                                <span class="ja-info-val">{{ $application->dob->format('jS F, Y') }}</span>
                            </div>
                            @endif
                            {{-- Applied For — editable via pencil --}}
                            <div class="ja-info-row" style="flex-wrap:wrap;">
                                <span class="ja-info-label"><i class="fa fa-briefcase" style="font-size:11px"></i> @lang('modules.jobApplication.appliedFor')</span>

                                {{-- VIEW MODE --}}
                                <div id="ja-job-view-{{ $application->id }}" style="display:flex;flex-direction:column;align-items:flex-end;gap:2px;">
                                    <span class="ja-info-val" style="display:inline-flex;align-items:center;gap:6px;">
                                        <span id="ja-display-job-{{ $application->id }}">{{ ucwords($application->job?->title ?? 'N/A') }}</span>
                                        @if($user->cans('edit_job_applications'))
                                        <button type="button" onclick="jaToggleJobEdit({{ $application->id }})" title="Change job"
                                                style="width:20px;height:20px;border-radius:6px;border:1px solid #E2DED8;background:#F8F7F4;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;color:#8A94A6;flex-shrink:0;padding:0;transition:all .15s;"
                                                onmouseover="this.style.background='#EFF6FF';this.style.color='#2563EB';this.style.borderColor='#2563EB'"
                                                onmouseout="this.style.background='#F8F7F4';this.style.color='#8A94A6';this.style.borderColor='#E2DED8'">
                                            <i class="fa fa-pencil" style="font-size:9px"></i>
                                        </button>
                                        @endif
                                    </span>
                                    @if($application->job?->company)
                                    <span id="ja-display-job-company-{{ $application->id }}" style="font-size:11.5px;font-weight:600;color:#3D4A5C;">
                                        {{ ucwords($application->job->company->company_name) }}
                                    </span>
                                    @endif
                                    @if($application->location)
                                    <span style="font-size:11px;color:#B0B8C4;display:inline-flex;align-items:center;gap:3px;">
                                        <svg style="width:11px;height:11px;flex-shrink:0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        {{ ucwords($application->location->location) }}
                                    </span>
                                    @endif
                                </div>

                                {{-- EDIT MODE --}}
                                @if($user->cans('edit_job_applications'))
                                <div id="ja-job-edit-{{ $application->id }}" style="display:none;width:100%;margin-top:8px;">
                                    <select id="ja-job-select-{{ $application->id }}" class="ja-stage-select" style="width:100%;">
                                        @foreach($jobOptions as $jobOption)
                                            @php
                                                $jobOptionLocations = $jobOption->jobLocation->pluck('location')->filter()->unique();
                                                if ($jobOptionLocations->isEmpty() && $jobOption->location?->location) {
                                                    $jobOptionLocations = collect([$jobOption->location->location]);
                                                }
                                            @endphp
                                            <option value="{{ $jobOption->id }}" @selected($jobOption->id === $application->job_id)>
                                                {{ ucwords($jobOption->title) }}
                                                {{ $jobOption->company?->company_name ? ' - '.ucwords($jobOption->company->company_name) : '' }}
                                                {{ $jobOptionLocations->isNotEmpty() ? ' - '.ucwords($jobOptionLocations->implode(', ')) : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div style="display:flex;gap:6px;margin-top:7px;justify-content:flex-end;">
                                        <button type="button" onclick="jaSaveJobEdit({{ $application->id }})" id="ja-job-save-btn-{{ $application->id }}"
                                                style="display:inline-flex;align-items:center;gap:5px;padding:6px 12px;border-radius:8px;border:none;background:#2563EB;color:#fff;font-size:11.5px;font-weight:600;cursor:pointer;font-family:inherit;">
                                            <i class="fa fa-check" id="ja-job-save-icon-{{ $application->id }}"></i> Save
                                        </button>
                                        <button type="button" onclick="jaToggleJobEdit({{ $application->id }})"
                                                style="display:inline-flex;align-items:center;gap:5px;padding:6px 12px;border-radius:8px;border:1px solid #E2DED8;background:#fff;font-size:11.5px;font-weight:600;cursor:pointer;color:#5A6478;font-family:inherit;">
                                            Cancel
                                        </button>
                                    </div>
                                    <div id="ja-job-save-msg-{{ $application->id }}" style="display:none;font-size:11px;margin-top:5px;text-align:right;"></div>
                                </div>
                                @endif
                            </div>
                            @if (!is_null($application->city))
                            <div class="ja-info-row">
                                <span class="ja-info-label"><i class="fa fa-map-marker" style="font-size:11px"></i> @lang('app.city')</span>
                                <span class="ja-info-val">{{ $application->city }}{{ $application->state ? ', '.$application->state : '' }}{{ $application->country ? ', '.$application->country : '' }}</span>
                            </div>
                            @endif
                            @if (!is_null($application->address))
                            <div class="ja-info-row">
                                <span class="ja-info-label"><i class="fa fa-home" style="font-size:11px"></i> @lang('app.address')</span>
                                <span class="ja-info-val">{{ $application->address }}</span>
                            </div>
                            @endif
                        </div>

                        {{-- EDIT MODE --}}
                        @if($user->cans('edit_job_applications'))
                        <div id="ja-info-edit-{{ $application->id }}" style="display:none;margin-top:4px;">
                            <div style="display:flex;flex-direction:column;gap:8px;">
                                <div>
                                    <label style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#B0B8C4;display:block;margin-bottom:4px;">@lang('app.name')</label>
                                    <input type="text" id="ja-edit-name-{{ $application->id }}" value="{{ $application->full_name }}"
                                           class="ja-note-textarea" style="resize:none;height:36px;padding:6px 10px;font-size:13px;">
                                </div>
                                <div>
                                    <label style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#B0B8C4;display:block;margin-bottom:4px;">@lang('app.email')</label>
                                    <input type="email" id="ja-edit-email-{{ $application->id }}" value="{{ $application->email }}"
                                           class="ja-note-textarea" style="resize:none;height:36px;padding:6px 10px;font-size:13px;">
                                </div>
                                <div>
                                    <label style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#B0B8C4;display:block;margin-bottom:4px;">@lang('app.phone')</label>
                                    <input type="text" id="ja-edit-phone-{{ $application->id }}" value="{{ $application->phone }}"
                                           class="ja-note-textarea" style="resize:none;height:36px;padding:6px 10px;font-size:13px;">
                                </div>
                                <div style="display:flex;gap:7px;margin-top:4px;">
                                    <button type="button" onclick="jaSaveInfoEdit({{ $application->id }})"
                                            id="ja-info-save-btn-{{ $application->id }}"
                                            style="display:inline-flex;align-items:center;gap:5px;padding:7px 14px;border-radius:9px;border:none;background:#2563EB;color:#fff;font-size:12px;font-weight:600;cursor:pointer;font-family:inherit;">
                                        <i class="fa fa-check" id="ja-info-save-icon-{{ $application->id }}"></i> Save
                                    </button>
                                    <button type="button" onclick="jaToggleInfoEdit({{ $application->id }})"
                                            style="display:inline-flex;align-items:center;gap:5px;padding:7px 12px;border-radius:9px;border:1px solid #E2DED8;background:#fff;font-size:12px;font-weight:600;cursor:pointer;color:#5A6478;font-family:inherit;">
                                        Cancel
                                    </button>
                                </div>
                                <div id="ja-info-save-msg-{{ $application->id }}" style="display:none;font-size:11.5px;margin-top:2px;"></div>
                            </div>
                        </div>
                        @endif
                    </div>

                    @if($consortiumRegistration)
                        @include('admin.consortium-registrations.partials.information-card', ['registration' => $consortiumRegistration])
                    @endif

                    {{-- Cover letter --}}
                    @if (!is_null($application->cover_letter))
                    <div class="ja-card">
                        <div class="ja-card-title"><i class="fa fa-file-text-o" style="font-size:11px"></i> @lang('modules.jobs.coverLetter')</div>
                        <p style="font-size:12.5px;color:#5A6478;line-height:1.7;white-space:pre-wrap;margin:0">{{ $application->cover_letter }}</p>
                    </div>
                    @endif

                    {{-- Skills --}}
                    @if ($user->cans('edit_job_applications'))
                    <div class="ja-card" id="skills-container">
                        <div class="ja-card-title"><i class="fa fa-star" style="font-size:11px"></i> @lang('modules.jobApplication.skills')</div>
                        <div class="mb-3">
                            <select name="skills[]" id="skills" class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm select2" multiple>
                                @forelse ($skills as $skill)
                                    <option @if(!is_null($application->skills) && in_array($skill->id, $application->skills)) selected @endif value="{{ $skill->id }}">{{ $skill->name }}</option>
                                @empty
                                @endforelse
                            </select>
                        </div>
                        {{-- Parse from CV --}}
                        <div style="border:1px solid #E8E6E1;border-radius:10px;padding:12px;margin-bottom:10px;background:#F8F7F4">
                            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#B0B8C4;margin-bottom:8px">
                                <i class="fa fa-magic" style="font-size:11px"></i> Auto-detect from CV
                            </div>
                            <button type="button" id="parse-skills-btn-{{ $application->id }}" onclick="jaParseSkills({{ $application->id }})"
                                    class="ja-btn ja-btn-blue" style="min-width:auto;flex:none;display:inline-flex;margin-bottom:8px">
                                <i class="fa fa-search" id="parse-icon-{{ $application->id }}"></i>
                                <span id="parse-label-{{ $application->id }}">Parse skills from CV</span>
                            </button>
                            <div id="parse-result-{{ $application->id }}" style="display:none">
                                <div id="parse-matched-wrap-{{ $application->id }}" style="display:none;margin-bottom:8px">
                                    <div style="font-size:11px;color:#065F46;font-weight:600;margin-bottom:5px"><i class="fa fa-check-circle" style="font-size:11px"></i> Matched — click to add:</div>
                                    <div id="parse-matched-chips-{{ $application->id }}" style="display:flex;flex-wrap:wrap;gap:5px"></div>
                                </div>
                                <div id="parse-new-wrap-{{ $application->id }}" style="display:none">
                                    <div style="font-size:11px;color:#92400E;font-weight:600;margin-bottom:5px"><i class="fa fa-plus-circle" style="font-size:11px"></i> New skills — click to create & add:</div>
                                    <div id="parse-new-chips-{{ $application->id }}" style="display:flex;flex-wrap:wrap;gap:5px"></div>
                                </div>
                                <div id="parse-status-{{ $application->id }}" style="font-size:11.5px;color:#059669;margin-top:6px"></div>
                            </div>
                            <div id="parse-error-{{ $application->id }}" style="display:none;font-size:12px;color:#EF4444;margin-top:6px">
                                <i class="fa fa-exclamation-circle"></i> <span></span>
                            </div>
                        </div>
                        {{-- Add Manually --}}
                        <div style="border:1px solid #E8E6E1;border-radius:10px;padding:12px;margin-bottom:12px;background:#F8F7F4">
                            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#B0B8C4;margin-bottom:8px">
                                <i class="fa fa-keyboard-o" style="font-size:11px"></i> Add manually
                            </div>
                            <div style="display:flex;gap:7px;align-items:center">
                                <input type="text" id="manual-skill-input-{{ $application->id }}" placeholder="Type a skill name…"
                                    class="ja-note-textarea" style="flex:1;resize:none;height:36px;padding:6px 10px;font-size:12.5px"
                                    onkeydown="if(event.key==='Enter'){event.preventDefault();jaAddManualSkill({{ $application->id }});}">
                                <button type="button" onclick="jaAddManualSkill({{ $application->id }})" class="ja-btn ja-btn-blue"
                                        style="min-width:auto;flex:none;display:inline-flex;white-space:nowrap;height:36px;padding:0 12px">
                                    <i class="fa fa-plus"></i> Add
                                </button>
                            </div>
                        </div>
                        <a href="javascript:addSkills({{ $application->id }});" id="add-skills" class="ja-btn ja-btn-blue" style="min-width:auto;flex:none;display:inline-flex">
                            <i class="fa fa-plus"></i>
                            @if (!is_null($application->skills) && sizeof($application->skills) > 0) @lang('modules.jobApplication.updateSkills') @else @lang('modules.jobApplication.addSkills') @endif
                        </a>
                    </div>
                    <script>
                    (function () {
                        window.jaParseSkills = function (appId) {
                            var btn = document.getElementById('parse-skills-btn-' + appId);
                            var icon = document.getElementById('parse-icon-' + appId);
                            var label = document.getElementById('parse-label-' + appId);
                            var resArea = document.getElementById('parse-result-' + appId);
                            var errArea = document.getElementById('parse-error-' + appId);
                            var status = document.getElementById('parse-status-' + appId);
                            btn.disabled = true; icon.className = 'fa fa-spinner fa-spin'; label.textContent = 'Parsing…';
                            resArea.style.display = 'none'; errArea.style.display = 'none';
                            $.ajax({
                                type: 'POST',
                                url: '{{ route("admin.job-applications.parse-skills", ":id") }}'.replace(':id', appId),
                                data: { _token: '{{ csrf_token() }}' },
                                success: function (res) {
                                    btn.disabled = false; icon.className = 'fa fa-search'; label.textContent = 'Re-parse CV';
                                    if (res.status !== 'success') { errArea.style.display = 'block'; errArea.querySelector('span').textContent = res.message || 'Parsing failed.'; return; }
                                    resArea.style.display = 'block';
                                    var matchedWrap = document.getElementById('parse-matched-wrap-' + appId);
                                    var matchedChips = document.getElementById('parse-matched-chips-' + appId);
                                    matchedChips.innerHTML = '';
                                    if (res.matched_skills && res.matched_skills.length) {
                                        matchedWrap.style.display = 'block';
                                        res.matched_skills.forEach(function (skill) {
                                            var chip = document.createElement('span');
                                            chip.style.cssText = 'display:inline-flex;align-items:center;gap:4px;padding:4px 10px;border-radius:20px;font-size:11.5px;font-weight:500;cursor:pointer;background:#ECFDF5;color:#065F46;border:1px dashed #6EE7B7;transition:all .15s';
                                            chip.innerHTML = '<i class="fa fa-plus" style="font-size:9px"></i> ' + skill.name;
                                            chip.onclick = function () {
                                                var $sel = $('#skills');
                                                if ($sel.val() && $sel.val().indexOf(String(skill.id)) > -1) return;
                                                var opt = $sel.find('option[value="' + skill.id + '"]');
                                                if (opt.length) { opt.prop('selected', true); $sel.trigger('change'); } else { $sel.append(new Option(skill.name, skill.id, true, true)).trigger('change'); }
                                                chip.style.borderStyle = 'solid'; chip.style.background = '#D1FAE5';
                                                chip.innerHTML = '<i class="fa fa-check" style="font-size:9px"></i> ' + skill.name;
                                                chip.onclick = null; chip.style.cursor = 'default';
                                                addSkills(appId, function() { status.textContent = 'Saved successfully'; });
                                            };
                                            matchedChips.appendChild(chip);
                                        });
                                    } else { matchedWrap.style.display = 'none'; }
                                    var newWrap = document.getElementById('parse-new-wrap-' + appId);
                                    var newChips = document.getElementById('parse-new-chips-' + appId);
                                    newChips.innerHTML = '';
                                    if (res.new_skills && res.new_skills.length) {
                                        newWrap.style.display = 'block';
                                        res.new_skills.forEach(function (name) {
                                            var chip = document.createElement('span');
                                            chip.style.cssText = 'display:inline-flex;align-items:center;gap:4px;padding:4px 10px;border-radius:20px;font-size:11.5px;font-weight:500;cursor:pointer;background:#FFFBEB;color:#92400E;border:1px dashed #FDE68A;transition:all .15s';
                                            chip.innerHTML = '<i class="fa fa-plus" style="font-size:9px"></i> ' + name;
                                            chip.onclick = function () {
                                                $.ajax({ type:'POST', url:'{{ route("admin.skills.quick-create") }}', data:{_token:'{{ csrf_token() }}',name:name},
                                                    success: function(r) {
                                                        if (r.status === 'success' && r.id) {
                                                            $('#skills').append(new Option(name, r.id, true, true)).trigger('change');
                                                            chip.style.background = '#FEF3C7'; chip.style.borderStyle = 'solid';
                                                            chip.innerHTML = '<i class="fa fa-check" style="font-size:9px"></i> ' + name;
                                                            chip.onclick = null; chip.style.cursor = 'default';
                                                            addSkills(appId, function() { status.textContent = 'Saved successfully.'; });
                                                        }
                                                    }, error: function() { chip.style.opacity = '.4'; chip.title = 'Could not create skill.'; }
                                                });
                                            };
                                            newChips.appendChild(chip);
                                        });
                                    } else { newWrap.style.display = 'none'; }
                                    if (!res.matched_skills.length && !res.new_skills.length) { status.textContent = 'No skills detected in this CV.'; }
                                },
                                error: function () {
                                    btn.disabled = false; icon.className = 'fa fa-search'; label.textContent = 'Parse skills from CV';
                                    errArea.style.display = 'block'; errArea.querySelector('span').textContent = 'Server error. Please try again.';
                                }
                            });
                        };
                        window.jaAddManualSkill = function (appId) {
                            var input = document.getElementById('manual-skill-input-' + appId);
                            var name = input.value.trim();
                            if (!name) return;
                            var $sel = $('#skills');
                            var exists = $sel.find('option').filter(function() { return $(this).text().toLowerCase() === name.toLowerCase(); });
                            if (exists.length) { exists.prop('selected', true); $sel.trigger('change'); input.value = ''; addSkills(appId); return; }
                            $sel.append(new Option(name, 'new:' + name, true, true)).trigger('change');
                            input.value = ''; addSkills(appId);
                        };
                    })();
                    </script>
                    @endif

                </div>{{-- /details --}}

                {{-- ── NOTES TAB ── --}}
                <div id="ja-tab-notes" class="ja-tab-pane" style="display:none">
                    @if($user->cans('edit_job_applications'))
                    <div class="ja-add-note" style="margin-bottom:10px">
                        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#B0B8C4;margin-bottom:8px">@lang('modules.jobApplication.addNote')</div>
                        <div style="position:relative">
                            <textarea id="note_text" rows="3" class="ja-note-textarea"
                                    placeholder="Type a note… use @ to mention a team member"
                                    oninput="jaNoteHandleInput(this)"></textarea>
                            <div id="ja-mention-drop" style="display:none;position:absolute;bottom:calc(100% + 4px);left:0;background:#fff;border:1.5px solid #E2DED8;border-radius:10px;box-shadow:0 8px 24px rgba(15,23,42,.13);z-index:9999;min-width:200px;max-height:180px;overflow-y:auto;"></div>
                        </div>
                        <button id="add-note" class="ja-save-note-btn">
                            <i class="fa fa-plus"></i> @lang('modules.jobApplication.addNote')
                        </button>
                    </div>
                    @endif
                    <div id="applicant-notes" data-url="{{ route('admin.job-applications.profile-tab', [$application->id, 'notes']) }}">
                        <div class="ja-tab-loading">Open the Applicant Notes tab to load notes.</div>
                    </div>
                </div>

                {{-- ── CLIENT NOTES TAB ── --}}
                <div id="ja-tab-client-notes" class="ja-tab-pane" style="display:none">
                    <div style="background:#F0FDF4;border:1px solid #BBF7D0;border-radius:10px;padding:10px 14px;margin-bottom:10px;display:flex;align-items:center;gap:8px">
                        <i class="fa fa-info-circle" style="color:#059669;font-size:13px;flex-shrink:0"></i>
                        <span style="font-size:11.5px;color:#065F46;line-height:1.5">These notes are shared across <strong>all applicants</strong> for <strong>{{ ucwords($application->job?->title ?? 'this job') }}</strong>.</span>
                    </div>
                    <div class="ja-add-note" style="margin-bottom:10px">
                        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#B0B8C4;margin-bottom:8px">
                            <i class="fa fa-plus-circle" style="color:#059669"></i> Add Client Note
                        </div>
                        <textarea id="client_note_text" rows="4" class="ja-note-textarea" style="width:100%;min-height:90px" placeholder="Add a note visible to all applicants on this job…"></textarea>
                        <button id="add-client-note" class="ja-save-note-btn" style="background:#059669;margin-top:8px" data-job-id="{{ $application->job_id }}">
                            <i class="fa fa-plus"></i> Add Client Note
                        </button>
                    </div>
                    <div id="client-notes-list" data-url="{{ route('admin.job-applications.profile-tab', [$application->id, 'client-notes']) }}">
                        <div style="text-align:center;padding:24px 0;color:#B0B8C4;font-size:12.5px">Open the Client Notes tab to load notes.</div>
                    </div>
                </div>

                {{-- ── Q&A TAB ── --}}
                @if(count($answers) > 0)
                <div id="ja-tab-qa" class="ja-tab-pane" style="display:none">
                    @forelse($answers as $answer)
                    <div class="ja-qa-item">
                        <div class="ja-qa-q">{{ $answer->question->question }}</div>
                        @if($answer->question->type == 'text')
                            <span style="font-size:12.5px;color:#5A6478">{{ ucfirst($answer->answer) }}</span>
                        @elseif($answer->question->type == 'radio')
                            <span class="ja-qa-badge {{ strtolower($answer->answer) == 'yes' ? 'ja-qa-yes' : 'ja-qa-no' }}">
                                <i class="fa fa-circle" style="font-size:8px;margin-right:4px"></i>{{ ucfirst($answer->answer) }}
                            </span>
                        @else
                            @if(!is_null($answer->file))
                            <a target="_blank" href="{{ $answer->file_url }}" class="ja-btn ja-btn-blue" style="display:inline-flex;flex:none;min-width:auto;margin-top:4px">
                                <i class="fa fa-file-o"></i> @lang('app.view') @lang('app.file')
                            </a>
                            @endif
                        @endif
                    </div>
                    @empty
                    @endforelse
                </div>
                @endif

                {{-- ── SCHEDULE TAB ── --}}
                @if(!is_null($application->schedule))
                <div id="ja-tab-schedule" class="ja-tab-pane" style="display:none">
                    <div class="ja-card">
                        <div class="ja-card-title"><i class="fa fa-calendar-check-o" style="font-size:11px"></i> @lang('modules.interviewSchedule.scheduleDetail')</div>
                        <div class="ja-schedule-card" style="margin-bottom:14px">
                            <div class="ja-schedule-date"><i class="fa fa-clock-o" style="font-size:13px"></i> {{ $application->schedule->schedule_date->format('d M, Y \a\t H:i') }}</div>
                            @if($zoom_setting->enable_zoom == 1)
                            <div class="ja-schedule-type">
                                <i class="fa {{ $application->schedule->interview_type == 'online' ? 'fa-video-camera' : 'fa-building' }}" style="margin-right:5px"></i>
                                {{ $application->schedule->interview_type == 'online' ? 'Online' : 'Offline' }}
                            </div>
                            @endif
                        </div>
                        @if(count($application->schedule->employee) > 0)
                        <div class="ja-card-title" style="margin-bottom:10px"><i class="fa fa-users" style="font-size:11px"></i> @lang('modules.interviewSchedule.assignedEmployee')</div>
                        @foreach($application->schedule->employee as $emp)
                        <div class="ja-interviewer-row" style="margin-bottom:6px">
                            <div style="display:flex;align-items:center;gap:9px">
                                <div class="ja-interviewer-avatar">{{ strtoupper(substr($emp->user->name, 0, 2)) }}</div>
                                <span style="font-size:12.5px;font-weight:600;color:#1A1E2E">{{ ucwords($emp->user->name) }}</span>
                            </div>
                            <span class="ja-small-badge {{ $emp->user_accept_status == 'accept' ? 'ja-badge-accept' : ($emp->user_accept_status == 'refuse' ? 'ja-badge-refuse' : 'ja-badge-pending') }}">
                                {{ ucwords($emp->user_accept_status) }}
                            </span>
                        </div>
                        @endforeach
                        @endif
                    </div>
                    @if(isset($application->schedule->comments) && count($application->schedule->comments) > 0)
                    <div class="ja-card">
                        <div class="ja-card-title"><i class="fa fa-comments" style="font-size:11px"></i> @lang('modules.interviewSchedule.comments')</div>
                        @foreach($application->schedule->comments as $comment)
                        <div class="ja-note" style="border-left-color:#059669;margin-bottom:8px">
                            <div class="ja-note-meta"><span class="ja-note-author">{{ $comment->user->name }}</span></div>
                            <p class="ja-note-body">{{ $comment->comment }}</p>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
                @endif

            </div>{{-- /right-scroll --}}
        </div>{{-- /right-panel --}}
    </div>{{-- /body --}}

    @include('admin.job-applications.partials.client-review-modal')

    {{-- ── JOB DESCRIPTION MODAL ── --}}
    <div id="ja-jobdesc-overlay" onclick="if(event.target===this)jaHideJobDesc()"
         style="display:none;position:fixed;inset:0;background:rgba(15,31,61,.55);z-index:9999;align-items:center;justify-content:center;padding:24px">
        <div style="background:#fff;border-radius:18px;width:100%;max-width:680px;max-height:85vh;display:flex;flex-direction:column;box-shadow:0 24px 60px rgba(15,31,61,.22);overflow:hidden">
            <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 22px;border-bottom:1px solid #E8E6E1;flex-shrink:0">
                <div>
                    <div style="font-size:15px;font-weight:700;color:#1A1E2E">{{ ucwords($application->job?->title ?? 'Job Description') }}</div>
                    @if($application->job?->company)
                    <div style="font-size:12px;color:#8A94A6;margin-top:2px">
                        <i class="fa fa-building-o" style="font-size:11px;margin-right:4px"></i>{{ ucwords($application->job->company->company_name ?? '') }}
                        @if($application->location) &nbsp;·&nbsp; <i class="fa fa-map-marker" style="font-size:11px;margin-right:3px"></i>{{ ucwords($application->location->location) }} @endif
                    </div>
                    @endif
                </div>
                <button type="button" onclick="jaHideJobDesc()" style="width:32px;height:32px;border-radius:8px;border:1px solid #E8E6E1;background:#F8F7F4;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#8A94A6;font-size:14px;flex-shrink:0"><i class="fa fa-times"></i></button>
            </div>
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;padding:12px 22px;border-bottom:1px solid #F0EEE9;flex-shrink:0">
                @if($application->job?->job_type)
                <span style="display:inline-flex;align-items:center;gap:4px;padding:4px 11px;border-radius:20px;font-size:11.5px;font-weight:600;background:#EFF6FF;color:#1D4ED8"><i class="fa fa-briefcase" style="font-size:10px"></i>{{ ucwords($application->job->job_type) }}</span>
                @endif

                {{-- ── SALARY BADGE ── --}}
                @php
                    $modalJob = $application->job;
                    $modalSalaryText = null;
                    if ($modalJob) {
                        $modalCurrencySymbol = $modalJob->currency->currency_symbol ?? '$';
                        if ($modalJob->pay_type == 'Range') {
                            $modalSalaryText = $modalCurrencySymbol . number_format($modalJob->starting_salary)
                                . ' - ' . $modalCurrencySymbol . number_format($modalJob->maximum_salary)
                                . ' /' . $modalJob->pay_according;
                        } elseif ($modalJob->pay_type == 'Starting') {
                            $modalSalaryText = $modalCurrencySymbol . number_format($modalJob->starting_salary) . ' /' . $modalJob->pay_according;
                        } elseif ($modalJob->pay_type == 'Maximum') {
                            $modalSalaryText = $modalCurrencySymbol . number_format($modalJob->maximum_salary) . ' /' . $modalJob->pay_according;
                        } elseif ($modalJob->pay_type == 'Exact Amount') {
                            $modalSalaryText = $modalCurrencySymbol . number_format($modalJob->starting_salary) . ' /' . $modalJob->pay_according;
                        }
                    } 
                @endphp
                @if($modalSalaryText)
                <span style="display:inline-flex;align-items:center;gap:4px;padding:4px 11px;border-radius:20px;font-size:11.5px;font-weight:600;background:#ECFDF5;color:#065F46"><i class="fa fa-money" style="font-size:10px"></i>{{ $modalSalaryText }}</span>
                @endif

                @if($application->job?->start_date)
                <span style="display:inline-flex;align-items:center;gap:4px;padding:4px 11px;border-radius:20px;font-size:11.5px;font-weight:600;background:#F1F3F7;color:#5A6478"><i class="fa fa-calendar-o" style="font-size:10px"></i>Posted {{ $application->job->start_date->format('d M Y') }}</span>
                @endif
                @if($application->job?->end_date)
                <span style="display:inline-flex;align-items:center;gap:4px;padding:4px 11px;border-radius:20px;font-size:11.5px;font-weight:600;background:#FFF7ED;color:#C2410C"><i class="fa fa-clock-o" style="font-size:10px"></i>Closes {{ $application->job->end_date->format('d M Y') }}</span>
                @endif
            </div>
            <div style="flex:1;overflow-y:auto;padding:20px 22px">
                @if($application->job?->job_description)
                <div style="margin-bottom:20px">
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#B0B8C4;margin-bottom:10px;display:flex;align-items:center;gap:6px"><i class="fa fa-align-left" style="font-size:11px"></i> Description</div>
                    <div style="font-size:13px;color:#374151;line-height:1.75">{!! $application->job->job_description !!}</div>
                </div>
                @endif
                @if($application->job?->job_requirement)
                <div style="border-top:1px solid #F0EEE9;padding-top:18px;margin-bottom:20px">
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#B0B8C4;margin-bottom:10px;display:flex;align-items:center;gap:6px"><i class="fa fa-list-ul" style="font-size:11px"></i> Requirements</div>
                    <div style="font-size:13px;color:#374151;line-height:1.75">{!! $application->job->job_requirement !!}</div>
                </div>
                @endif
                @php $jobSkillNames = $application->job ? $application->job->skills->pluck('skill.name')->filter() : collect(); @endphp
                @if($jobSkillNames && $jobSkillNames->isNotEmpty())
                <div style="border-top:1px solid #F0EEE9;padding-top:18px">
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#B0B8C4;margin-bottom:10px;display:flex;align-items:center;gap:6px"><i class="fa fa-tags" style="font-size:11px"></i> Required Skills</div>
                    <div style="display:flex;flex-wrap:wrap;gap:7px">
                        @foreach($jobSkillNames as $sName)<span style="display:inline-flex;align-items:center;padding:5px 12px;border-radius:20px;font-size:12px;font-weight:500;background:#F1F3F7;color:#5A6478;border:1px solid #E2DED8">{{ $sName }}</span>@endforeach
                    </div>
                </div>
                @endif
                @if(!$application->job?->job_description && !$application->job?->job_requirement)
                <div style="text-align:center;padding:40px 20px;color:#B0B8C4"><i class="fa fa-file-text-o" style="font-size:36px;display:block;margin-bottom:12px;opacity:.35"></i><p style="font-size:13px">No job description available.</p></div>
                @endif
            </div>
            <div style="padding:12px 22px;border-top:1px solid #E8E6E1;flex-shrink:0;display:flex;justify-content:flex-end">
                <button type="button" onclick="jaHideJobDesc()" style="padding:8px 20px;border-radius:9px;border:1px solid #E2DED8;background:#fff;font-size:12.5px;font-weight:600;color:#5A6478;cursor:pointer;font-family:'Plus Jakarta Sans',sans-serif">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
/* ── Tab switching ── */
window.jaUnloadPdf = function () {
    if (window._jaPdfIdleCallback && window.cancelIdleCallback) {
        window.cancelIdleCallback(window._jaPdfIdleCallback);
        window._jaPdfIdleCallback = null;
    }
    if (window._jaPdfLoadTimer) {
        clearTimeout(window._jaPdfLoadTimer);
        window._jaPdfLoadTimer = null;
    }
    if (window._jaPdfRenderTask) { try { window._jaPdfRenderTask.cancel(); } catch (e) {} window._jaPdfRenderTask = null; }
    if (window._jaPdfObserver) { try { window._jaPdfObserver.disconnect(); } catch (e) {} window._jaPdfObserver = null; }
    if (window._jaPdfDoc) { try { window._jaPdfDoc.destroy(); } catch (e) {} window._jaPdfDoc = null; }
    var oldFrame = document.getElementById('ja-pdf-frame');
    if (oldFrame) oldFrame.src = 'about:blank';
};

window.jaUnloadPdf();
(function () {
    var container = document.getElementById('ja-pdf-container');
    var ready = document.getElementById('ja-pdf-ready');
    var loading = document.getElementById('ja-pdf-loading');
    var scroll = document.getElementById('ja-pdf-scroll');
    var pages = document.getElementById('ja-pdf-pages');
    var error = document.getElementById('ja-pdf-error');
    var frame = document.getElementById('ja-pdf-frame');
    if (!container || !pages || !window.pdfjsLib) return;
    var started = false;

    window.jaShowNativeApplicantPdf = function () {
        if (!frame) return;
        if (ready) ready.style.display = 'none';
        if (loading) loading.style.display = 'none';
        if (error) error.style.display = 'none';
        if (scroll) scroll.style.display = 'none';
        frame.style.display = 'block';
        if (!frame.src || frame.src === 'about:blank') frame.src = @json($resumeUrl) + '#view=FitH';
    };

    var loadPdf = function () {
        pdfjsLib.getDocument({
            url: @json($resumeUrl),
            withCredentials: true,
            // Keep exceptionally large embedded images from exhausting the
            // renderer while a recruiter is browsing profiles.
            maxImageSize: 4096 * 4096,
            stopAtErrors: true
        }).promise.then(function(pdf) {
            if (document.getElementById('ja-pdf-container') !== container) { pdf.destroy(); return; }
            window._jaPdfDoc = pdf;
            return pdf.getPage(1);
        }).then(function(page) {
            if (!page || document.getElementById('ja-pdf-container') !== container) return;
            var base = page.getViewport({scale:1});
            var scale = Math.max(.7, Math.min(1.15, (container.clientWidth - 32) / base.width));
            var pageHeight = Math.ceil(base.height * scale);
            var pageWidth = Math.ceil(base.width * scale);

            // Add lightweight placeholders for every page. This gives the CV
            // its complete vertical scrollbar immediately, but only paints a
            // page when it is close to the visible area.
            for (var number = 1; number <= pdf.numPages; number++) {
                var holder = document.createElement('div');
                holder.className = 'ja-pdf-page';
                holder.dataset.page = number;
                holder.style.cssText = 'width:' + pageWidth + 'px;min-height:' + pageHeight + 'px;background:#fff;box-shadow:0 2px 10px rgba(0,0,0,.35);display:flex;align-items:center;justify-content:center;color:#9CA3AF;font-size:12px;';
                holder.innerHTML = '<i class="fa fa-spinner fa-spin" style="margin-right:6px"></i> Loading page ' + number + ' of ' + pdf.numPages;
                pages.appendChild(holder);
            }

            var renderingPage = false;
            var pendingPages = [];
            var renderPage = function (holder) {
                if (holder.dataset.rendered || holder.dataset.rendering || document.getElementById('ja-pdf-container') !== container) return;
                if (renderingPage) {
                    if (pendingPages.indexOf(holder) === -1) pendingPages.push(holder);
                    return;
                }
                renderingPage = true;
                holder.dataset.rendering = '1';
                var pageNumber = Number(holder.dataset.page);
                pdf.getPage(pageNumber).then(function (pdfPage) {
                    if (document.getElementById('ja-pdf-container') !== container) return;
                    var viewport = pdfPage.getViewport({scale:scale});
                    var canvas = document.createElement('canvas');
                    canvas.width = Math.floor(viewport.width);
                    canvas.height = Math.floor(viewport.height);
                    canvas.style.cssText = 'display:block;width:100%;height:auto;';
                    window._jaPdfRenderTask = pdfPage.render({canvasContext:canvas.getContext('2d'), viewport:viewport});
                    return window._jaPdfRenderTask.promise.then(function () {
                        holder.innerHTML = '';
                        holder.appendChild(canvas);
                        holder.dataset.rendered = '1';
                        pdfPage.cleanup();
                    });
                }).catch(function () {
                    holder.innerHTML = '<span>Could not render page ' + pageNumber + '.</span>';
                }).then(function () {
                    renderingPage = false;
                    holder.dataset.rendering = '';
                    if (pendingPages.length) renderPage(pendingPages.shift());
                }, function () {
                    renderingPage = false;
                    holder.dataset.rendering = '';
                    if (pendingPages.length) renderPage(pendingPages.shift());
                });
            };

            if (loading) loading.style.display = 'none';
            scroll.style.display = 'block';
            window._jaPdfObserver = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) renderPage(entry.target);
                });
            }, { root: scroll, rootMargin: '400px 0px' });
            Array.prototype.forEach.call(pages.children, function (holder) { window._jaPdfObserver.observe(holder); });
            renderPage(pages.firstElementChild);
        }).catch(function() {
            // Some protected or external file URLs cannot be fetched by
            // PDF.js. Let the browser's PDF viewer handle those files.
            window.jaShowNativeApplicantPdf();
        });
    };

    window.jaLoadApplicantPdf = function () {
        if (started) return;
        started = true;
        if (ready) ready.style.display = 'none';
        if (loading) loading.style.display = 'flex';
        window._jaPdfLoadTimer = setTimeout(loadPdf, 0);
    };

    // Display the CV immediately when the applicant profile opens.
    window.jaLoadApplicantPdf();
})();

document.querySelectorAll('.ja-tab').forEach(function(tab) {
    tab.addEventListener('click', function() {
        var target = this.dataset.tab;
        document.querySelectorAll('.ja-tab').forEach(function(t) { t.classList.remove('active'); });
        document.querySelectorAll('.ja-tab-pane').forEach(function(p) { p.style.display = 'none'; });
        this.classList.add('active');
        var pane = document.getElementById('ja-tab-' + target);
        if (pane) pane.style.display = 'block';

        if (target === 'email-conversation') {
            jaLoadCandidateEmailConversation({{ $application->id }});
        }
        if (target === 'history' || target === 'notes' || target === 'client-notes') {
            var selector = target === 'notes' ? '#applicant-notes' : (target === 'client-notes' ? '#client-notes-list' : '#ja-tab-history');
            var $tabContent = $(selector);
            var tabUrl = $tabContent.data('url');
            if (tabUrl && !$tabContent.data('loaded') && !$tabContent.data('loading')) {
                $tabContent.data('loading', true).html('<div class="ja-tab-loading"><i class="fa fa-spinner fa-spin"></i> Loading...</div>');
                $.get(tabUrl, function(response) {
                    if (response.status === 'success') {
                        $tabContent.html(response.view).data('loaded', true);
                    }
                }).always(function() { $tabContent.data('loading', false); });
            }
        }
    });
});

window.jaLoadCandidateEmailConversation = function (applicationId) {
    var box = document.getElementById('ja-email-conversation-' + applicationId);
    if (!box || $(box).data('loading')) return;
    $(box).data('loading', true);
    $.ajax({url: '{{ route('admin.candidate-communications.email', ':id') }}'.replace(':id', applicationId), type: 'GET', cache: false, global: false}).done(function (data) {
        if (!box.isConnected) return;
        var messages = data.messages || [];
        var badge = document.getElementById('ja-email-unread-' + applicationId);
        if (badge) badge.style.display = 'none';
        var fingerprint = JSON.stringify(messages.map(function (message) {
            return [message.id, message.direction, message.subject, message.display_body || message.body, message.quoted_body, message.received_at, message.created_at, message.user && message.user.name];
        }));
        if ($(box).data('fingerprint') === fingerprint) return;
        var atBottom = box.scrollHeight - box.scrollTop - box.clientHeight < 60;
        var previousScroll = box.scrollTop;
        function escapeText(value) {
            return String(value || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        }
        if (!messages.length) {
            box.innerHTML = '<div style="padding:30px 8px;text-align:center;color:#A0A8B5;font-size:12px;"><i class="fa fa-envelope-o" style="display:block;font-size:24px;margin-bottom:8px;"></i>No email messages yet.</div>';
            return;
        }
        box.innerHTML = messages.map(function (message) {
            var outbound = message.direction === 'outbound';
            var body = escapeText(message.display_body === undefined ? message.body : message.display_body).replace(/\n/g, '<br>');
            if (message.quoted_body) {
                body += '<details style="margin-top:8px;font-size:11px;opacity:.85"><summary style="cursor:pointer;font-weight:600">Show quoted email</summary><div style="margin-top:6px;max-height:220px;overflow:auto;line-height:1.45">' + escapeText(message.quoted_body).replace(/\n/g, '<br>') + '</div></details>';
            }
            var date = new Date(message.received_at || message.created_at);
            var timestamp = isNaN(date.getTime()) ? '' : date.toLocaleString();
            var author = outbound ? (message.user && message.user.name || 'ATS team') : 'Candidate';
            return '<div style="display:flex;justify-content:' + (outbound ? 'flex-end' : 'flex-start') + '"><div style="max-width:92%;border-radius:12px;padding:10px 11px;' + (outbound ? 'background:#2563EB;color:#fff;border-bottom-right-radius:4px;' : 'background:#F1F3F7;color:#1A1E2E;border-bottom-left-radius:4px;') + '"><div style="font-size:11px;font-weight:700;margin-bottom:5px">' + escapeText(author) + (message.subject ? ' · ' + escapeText(message.subject) : '') + '</div><div style="font-size:12px;line-height:1.5;white-space:normal;overflow-wrap:anywhere">' + body + '</div><div style="margin-top:5px;font-size:9.5px;opacity:.7">' + escapeText(timestamp) + '</div></div></div>';
        }).join('');
        box.scrollTop = atBottom ? box.scrollHeight : previousScroll;
        $(box).data('loaded', true);
        $(box).data('fingerprint', fingerprint);
    }).fail(function () {
        if (box.isConnected && !$(box).data('loaded')) box.innerHTML = '<div style="padding:20px;text-align:center;color:#A0A8B5;font-size:12px">Unable to load email conversation.</div>';
    }).always(function () { $(box).data('loading', false); });
};

window.jaPollCandidateEmailUnread = function (applicationId) {
    $.ajax({url: '{{ route('admin.candidate-communications.email.unread', ':id') }}'.replace(':id', applicationId), type: 'GET', cache: false, global: false}).done(function (data) {
        var badge = document.getElementById('ja-email-unread-' + applicationId);
        if (!badge) return;
        var count = Number(data.unread || 0);
        badge.textContent = (count > 99 ? '99+' : count) + ' new';
        badge.style.display = count ? 'inline-flex' : 'none';
    });
};
jaPollCandidateEmailUnread({{ $application->id }});
if (window._jaEmailConversationTimer) window.clearInterval(window._jaEmailConversationTimer);
window._jaEmailConversationTimer = window.setInterval(function () {
    var box = document.getElementById('ja-email-conversation-{{ $application->id }}');
    if (!box) { window.clearInterval(window._jaEmailConversationTimer); return; }
    if (document.hidden) return;
    var pane = document.getElementById('ja-tab-email-conversation');
    if (pane && pane.style.display !== 'none') jaLoadCandidateEmailConversation({{ $application->id }});
    else jaPollCandidateEmailUnread({{ $application->id }});
}, 15000);

window.jaRefreshCandidateCallHistory = function (applicationId) {
    var root = document.getElementById('candidate-calls');
    var pane = document.getElementById('ja-tab-history');
    if (!root || root.dataset.applicationId !== String(applicationId) || !pane || !pane.dataset.url) return;
    $.get(pane.dataset.url, function (response) {
        if (response && response.status === 'success') {
            pane.innerHTML = response.view;
            $(pane).data('loaded', true);
        }
    });
};

/* ── Stage mover ── */
function jaToggleProfileStage(appId, show) {
    var form = document.getElementById('ja-stage-form-' + appId);
    var message = document.getElementById('ja-stage-message-' + appId);
    if (!form) return;
    form.style.display = show ? 'block' : 'none';
    if (message) { message.style.display = 'none'; message.textContent = ''; }
    if (show) document.getElementById('ja-stage-name-' + appId).focus();
}

function jaSaveProfileStage(appId) {
    var form = document.getElementById('ja-stage-form-' + appId);
    var button = document.getElementById('ja-stage-save-' + appId);
    var name = document.getElementById('ja-stage-name-' + appId);
    var color = document.getElementById('ja-stage-color-' + appId);
    var message = document.getElementById('ja-stage-message-' + appId);
    if (!form || !button || button.disabled || !form.reportValidity()) return;
    button.disabled = true;
    button.textContent = 'Saving…';
    message.style.display = 'none';
    function showMessage(text, failed) {
        message.textContent = text;
        message.style.color = failed ? '#DC2626' : '#059669';
        message.style.display = 'block';
    }
    $.ajax({
        type: 'POST',
        url: "{{ route('admin.job-applications.stages.store', ':id') }}".replace(':id', appId),
        data: { _token: '{{ csrf_token() }}', status_name: name.value.trim(), status_color: color.value },
        success: function(response) {
            if (!form.isConnected) return;
            if (response.status !== 'success' || !response.stage) {
                showMessage(response.message || 'Could not add the stage. Please try again.', true);
                return;
            }
            var select = document.getElementById('stage-mover-select-' + appId);
            if (!select) return;
            var stage = response.stage;
            var option = Array.prototype.find.call(select.options, function(item) { return item.value === String(stage.id); });
            if (!option) {
                option = document.createElement('option');
                option.value = String(stage.id);
                select.insertBefore(option, select.querySelector('option[value="__add_stage__"]'));
            }
            option.textContent = stage.label;
            option.setAttribute('data-color', stage.color);
            select.value = '';
            form.reset();
            form.style.display = 'none';
            showMessage('Stage added. Select it above to move this candidate.', false);
        },
        error: function(xhr) {
            if (!form.isConnected) return;
            var response = xhr.responseJSON || {};
            var errors = response.errors || {};
            var first = errors.status_name || errors.status_color;
            showMessage(first ? first[0] : (response.message || 'Could not add the stage. Please try again.'), true);
        },
        complete: function() {
            button.disabled = false;
            button.textContent = 'Save stage';
        }
    });
}

function jaMoveFromDetail(appId, toStatusId, toStatusLabel, currentStatusId) {
    if (toStatusId === '__add_stage__') {
        var select = document.getElementById('stage-mover-select-' + appId);
        if (select) select.value = '';
        jaToggleProfileStage(appId, true);
        return;
    }
    if (!toStatusId) return;
    $.easyAjax({
        type: 'POST', url: '{{ route("admin.job-applications.bulk-status-update") }}',
        data: { _token: '{{ csrf_token() }}', ids: [appId], status_id: toStatusId },
        success: function(response) {
            if (response.status === 'success') {
                var select = document.getElementById('stage-mover-select-' + appId);
                var option = select && select.options[select.selectedIndex];
                var color = option ? (option.getAttribute('data-color') || '#6B7280') : '#6B7280';

                // A stage update must not rebuild the complete profile. That
                // would recreate the PDF, Select2, and every event handler.
                document.querySelectorAll('.ja-pill-stage').forEach(function (pill) {
                    pill.style.background = color;
                    pill.textContent = toStatusLabel;
                });
                document.querySelectorAll('.ja-current-badge').forEach(function (badge) {
                    badge.style.background = color;
                    badge.innerHTML = '<i class="fa fa-check" style="font-size:9px"></i> ';
                    badge.appendChild(document.createTextNode(toStatusLabel));
                });
                var scheduleAction = document.getElementById('schedule-interview-action-' + appId);
                if (scheduleAction) {
                    scheduleAction.style.display = String(toStatusLabel).toLowerCase().includes('interview') ? '' : 'none';
                }
                if (select) {
                    select.value = '';
                    Array.prototype.slice.call(select.options).forEach(function (stage) {
                        if (stage.value === String(toStatusId)) stage.remove();
                    });
                }
                if (typeof table !== 'undefined') table.draw(false);
                if (typeof jaLoadTabCounts === 'function') jaLoadTabCounts();
            }
        }
    });
}

/* ── Skills ── */
// The profile HTML is replaced repeatedly. Select2 attaches listeners outside
// the select element, so it must be explicitly destroyed before a swap.
window.jaDisposeApplicantProfile = function () {
    if (typeof window.jaUnloadPdf === 'function') window.jaUnloadPdf();
    var $skills = $('#right-sidebar-content #skills');
    if ($skills.length && $.fn.select2 && $skills.data('select2')) {
        $skills.select2('destroy');
    }
};

window.jaRenderApplicantProfile = function (html) {
    if (typeof window.jaDisposeApplicantProfile === 'function') window.jaDisposeApplicantProfile();
    $('#right-sidebar-content').html(html);
};

var $profileSkills = $('#right-sidebar-content #skills');
if ($profileSkills.length && $.fn.select2) {
    if ($profileSkills.data('select2')) $profileSkills.select2('destroy');
    $profileSkills.select2({
        width: '100%',
        placeholder: 'Search skills...',
        ajax: {
            url: "{{ route('admin.job-applications.skill-search') }}",
            dataType: 'json',
            delay: 250,
            data: function (params) { return { term: params.term || '' }; },
            processResults: function (data) { return data; }
        },
        minimumInputLength: 1,
        dropdownParent: $('#right-sidebar-content')
    });
}
function addSkills(applicationId, callback) {
    var url = "{{ route('admin.job-applications.addSkills', ':id') }}".replace(':id', applicationId);
    $.easyAjax({
        url: url, type: 'POST', container: '#skills-container',
        data: { _token: '{{ csrf_token() }}', skills: $('#skills').val() },
        success: function(response) {
            if (response.status === 'success') {
                if (typeof callback === 'function') callback();
                if (typeof table !== 'undefined') table.draw(false); else loadData();
            }
        }
    });
}

/* ── Notes ── */
$('#add-note').click(function() {
    $.easyAjax({
        type: 'POST', url: "{{ route('admin.applicant-note.store') }}",
        data: { '_token': '{{ csrf_token() }}', 'id': {{ $application->id }}, 'note': $('#note_text').val() },
        success: function(response) {
            if (response.status == 'success') { $('#applicant-notes').html(response.view); $('#note_text').val(''); }
        }
    });
});

$('body').off('click.jaApp'); // drop handlers stacked by previous profile opens
$('body').on('click.jaApp', '.edit-note', function() {
    $(this).hide();
    var noteId = $(this).data('note-id');
    $('body').find('#note-' + noteId + ' .note-text').hide();
    var noteText = $('body').find('#note-' + noteId + ' .note-text').data('raw') || '';
    var textArea = '<textarea id="edit-note-text-' + noteId + '" class="ja-note-textarea" rows="3">' + noteText + '</textarea>' +
        '<button class="update-note ja-save-note-btn" data-note-id="' + noteId + '" style="margin-top:6px"><i class="fa fa-check"></i> @lang("app.save")</button>';
    $('body').find('#note-' + noteId + ' .note-textarea').html(textArea);
});

$('body').on('click.jaApp', '.update-note', function() {
    var noteId = $(this).data('note-id');
    $.easyAjax({
        type: 'POST', url: "{{ route('admin.applicant-note.update', ':id') }}".replace(':id', noteId),
        data: { '_token': '{{ csrf_token() }}', 'noteId': noteId, 'note': $('#edit-note-text-' + noteId).val(), '_method': 'PUT' },
        success: function(response) { if (response.status == 'success') $('#applicant-notes').html(response.view); }
    });
});

$('body').on('click.jaApp', '.delete-note', function() {
    var noteId = $(this).data('note-id');
    swal({ title:"@lang('errors.areYouSure')", text:"@lang('errors.deleteWarning')", type:"warning", showCancelButton:true, confirmButtonColor:"#DD6B55", confirmButtonText:"@lang('app.delete')", cancelButtonText:"@lang('app.cancel')", closeOnConfirm:true, closeOnCancel:true },
    function(isConfirm) {
        if (isConfirm) {
            $.easyAjax({ type:'POST', url:"{{ route('admin.applicant-note.destroy', ':id') }}".replace(':id', noteId),
                data: { '_token': '{{ csrf_token() }}', '_method': 'DELETE' },
                success: function(response) { if (response.status == 'success') $('#applicant-notes').html(response.view); }
            });
        }
    });
});

/* ── Delete application ── */
function deleteApplication(applicationId) {
    @if(!$user->hasRole('admin'))
    return;
    @endif
    swal({ title:"@lang('errors.areYouSure')", text:"@lang('errors.deleteWarning')", type:"warning", showCancelButton:true, confirmButtonColor:"#DD6B55", confirmButtonText:"@lang('app.delete')", cancelButtonText:"@lang('app.cancel')", closeOnConfirm:true, closeOnCancel:true },
    function(isConfirm) {
        if (isConfirm) {
            $.easyAjax({ type:'POST', url:"{{ route('admin.job-applications.destroy', ':id') }}".replace(':id', applicationId),
                data: { '_token': '{{ csrf_token() }}', '_method': 'DELETE' },
                success: function(response) {
                    if (window.raCloseRightSidebar) window.raCloseRightSidebar();
                    if (response.status === 'success') { if (typeof table !== 'undefined') table.draw(false); else loadData(); }
                }
            });
        }
    });
}

/* ── Prev/Next navigation ── */
(function () {
    var CURRENT_ID = {{ $application->id }};
    var showUrlTpl = "{{ route('admin.job-applications.show', ':id') }}";
    // Full profile HTML is large; retain only the two most recently opened panels.
    var CACHE_LIMIT = 2;

    // Persist across profile swaps — this script re-runs on every profile open.
    window._jaProfileCache = window._jaProfileCache || new Map();
    window._jaNavSeq       = window._jaNavSeq || 0;

    // Any successful POST (note add/edit/delete, status move, skill save …)
    // can change a profile, so drop all cached views and let them refetch.
    $(document).off('ajaxSuccess.jaApp').on('ajaxSuccess.jaApp', function (e, xhr, settings) {
        if (settings && settings.type && String(settings.type).toUpperCase() !== 'GET' && window._jaProfileCache) {
            window._jaProfileCache.clear();
        }
    });

    function jaCachePut(id, html) {
        var c = window._jaProfileCache;
        if (c.has(id)) c.delete(id);
        c.set(id, html);
        while (c.size > CACHE_LIMIT) c.delete(c.keys().next().value); // evict oldest
    }
    function jaSwapIn(targetId, html) {
        window.jaRenderApplicantProfile(html);
        var $row = $('[data-id="' + targetId + '"]');
        if ($row.length) { $row[0].scrollIntoView({behavior:'smooth',block:'nearest'}); $row.addClass('table-active'); setTimeout(function() { $row.removeClass('table-active'); }, 1200); }
    }
    function getIds() { return (typeof jaApplicantIds !== 'undefined' && Array.isArray(jaApplicantIds) && jaApplicantIds.length) ? jaApplicantIds : []; }
    function currentIndex() { return getIds().indexOf(CURRENT_ID); }
    function updateNavUI() {
        var ids = getIds(), idx = currentIndex();
        var prev = document.getElementById('ja-prev-btn'), next = document.getElementById('ja-next-btn'), ctr = document.getElementById('ja-nav-counter');
        if (!ids.length || idx === -1) { if (ctr) ctr.textContent = '—'; if (prev) prev.disabled = true; if (next) next.disabled = true; return; }
        if (ctr) ctr.textContent = (idx + 1) + ' / ' + ids.length;
        if (prev) prev.disabled = (idx === 0);
        if (next) next.disabled = (idx === ids.length - 1);
    }
    window.jaNavigate = function (direction, fromId) {
        var ids = getIds(), idx = ids.indexOf(fromId);
        if (idx === -1 || !ids.length) return;
        var targetId = (direction === 'prev') ? ids[idx - 1] : ids[idx + 1];
        if (targetId === undefined) return;

        // Token: only the most recent click is allowed to render.
        var seq = ++window._jaNavSeq;

        // Cancel whatever previous navigation is still in flight — rapid
        // clicking must never queue renders (caused out-of-order swaps and
        // the "profile does not open" symptom).
        if (window._jaShowXhr && window._jaShowXhr.readyState !== 4) window._jaShowXhr.abort();

        // Serve instantly from cache when possible (this is what makes
        // back-to-back browsing fast).
        if (window._jaProfileCache.has(targetId)) {
            jaSwapIn(targetId, window._jaProfileCache.get(targetId));
            return;
        }

        var prevBtn = document.getElementById('ja-prev-btn'), nextBtn = document.getElementById('ja-next-btn');
        if (prevBtn) prevBtn.disabled = true; if (nextBtn) nextBtn.disabled = true;
        var wrap = document.querySelector('.ja-two-col-wrap'); if (wrap) wrap.classList.add('ja-nav-loading');

        window._jaShowXhr = $.ajax({
            type: 'GET', url: showUrlTpl.replace(':id', targetId),
            success: function (res) {
                if (seq !== window._jaNavSeq) return; // a newer click already won
                if (res.status === 'success') {
                    jaCachePut(targetId, res.view);
                    jaSwapIn(targetId, res.view);
                } else { if (wrap) wrap.classList.remove('ja-nav-loading'); updateNavUI(); }
            },
            error: function (xhr, status) {
                if (status === 'abort') return; // replaced by a newer click
                if (seq !== window._jaNavSeq) return;
                if (wrap) wrap.classList.remove('ja-nav-loading'); updateNavUI();
            }
        });
    };
    function onKeyDown(e) {
        var tag = document.activeElement ? document.activeElement.tagName : '';
        if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') return;
        if (e.key === 'ArrowLeft' || e.keyCode === 37) { var p = document.getElementById('ja-prev-btn'); if (p && !p.disabled) jaNavigate('prev', CURRENT_ID); }
        if (e.key === 'ArrowRight' || e.keyCode === 39) { var n = document.getElementById('ja-next-btn'); if (n && !n.disabled) jaNavigate('next', CURRENT_ID); }
    }
    document.removeEventListener('keydown', window._jaKeyNav);
    window._jaKeyNav = onKeyDown;
    document.addEventListener('keydown', window._jaKeyNav);
    updateNavUI();
})();

/* ── Client Notes ── */
$('#add-client-note').click(function() {
    $.easyAjax({ type:'POST', url:"{{ route('admin.job-client-notes.store') }}",
        data: { '_token':'{{ csrf_token() }}', 'job_id':$(this).data('job-id'), 'note_text':$('#client_note_text').val() },
        success: function(response) { if (response.status === 'success') { $('#client-notes-list').html(response.view); $('#client_note_text').val(''); } }
    });
});
$('body').on('click.jaApp', '.edit-client-note', function() {
    $(this).hide();
    var noteId = $(this).data('note-id'), $noteEl = $('#cn-note-' + noteId);
    $noteEl.find('.cn-note-text').hide();
    var noteText = $noteEl.find('.cn-note-text').text().trim();
    $noteEl.find('.cn-note-textarea').html('<textarea id="cn-edit-text-' + noteId + '" class="ja-note-textarea" rows="3">' + noteText + '</textarea><button class="update-client-note ja-save-note-btn" data-note-id="' + noteId + '" style="margin-top:6px;background:#059669"><i class="fa fa-check"></i> Save</button>');
});
$('body').on('click.jaApp', '.update-client-note', function() {
    var noteId = $(this).data('note-id');
    $.easyAjax({ type:'POST', url:"{{ route('admin.job-client-notes.update', ':id') }}".replace(':id', noteId),
        data: { '_token':'{{ csrf_token() }}', 'note':$('#cn-edit-text-' + noteId).val() },
        success: function(response) { if (response.status === 'success') $('#client-notes-list').html(response.view); }
    });
});
$('body').on('click.jaApp', '.delete-client-note', function() {
    var noteId = $(this).data('note-id');
    swal({ title:"@lang('errors.areYouSure')", text:"@lang('errors.deleteWarning')", type:"warning", showCancelButton:true, confirmButtonColor:"#DD6B55", confirmButtonText:"@lang('app.delete')", cancelButtonText:"@lang('app.cancel')", closeOnConfirm:true, closeOnCancel:true },
    function(isConfirm) {
        if (isConfirm) {
            $.easyAjax({ type:'POST', url:"{{ route('admin.job-client-notes.destroy', ':id') }}".replace(':id', noteId),
                data: { '_token':'{{ csrf_token() }}' },
                success: function(response) { if (response.status === 'success') $('#client-notes-list').html(response.view); }
            });
        }
    });
});

/* ── Job Description Modal ── */
function jaShowJobDesc() { var o = document.getElementById('ja-jobdesc-overlay'); o.style.display = 'flex'; document.body.style.overflow = 'hidden'; }
function jaHideJobDesc() { var o = document.getElementById('ja-jobdesc-overlay'); o.style.display = 'none'; document.body.style.overflow = ''; }
if (window._jaEscKeyHandler) document.removeEventListener('keydown', window._jaEscKeyHandler);
window._jaEscKeyHandler = function(e) { if (e.key === 'Escape') jaHideJobDesc(); };
document.addEventListener('keydown', window._jaEscKeyHandler);

/* ── @mention autocomplete ── */
var jaAllUsers = @json($mentionUsers);
var jaMentionQuery = '', jaMentionStart = -1;
function jaNoteHandleInput(el) {
    var val = el.value, caret = el.selectionStart, drop = document.getElementById('ja-mention-drop');
    if (!drop) return;
    var textBefore = val.substring(0, caret), atMatch = textBefore.match(/@([a-zA-Z0-9_]*)$/);
    if (!atMatch) { drop.style.display = 'none'; jaMentionStart = -1; return; }
    jaMentionQuery = atMatch[1].toLowerCase(); jaMentionStart = caret - atMatch[0].length;
    var matches = jaAllUsers.filter(function(u) { return u.name.toLowerCase().includes(jaMentionQuery) && u.id !== {{ auth()->id() }}; }).slice(0, 6);
    if (!matches.length) { drop.style.display = 'none'; return; }
    drop.innerHTML = matches.map(function(u) {
        var initials = u.name.split(' ').map(function(w){ return w[0]; }).join('').substring(0,2).toUpperCase();
        return '<div onclick="jaInsertMention(\'' + u.name.replace(/'/g, "\\'") + '\')" style="display:flex;align-items:center;gap:8px;padding:8px 12px;cursor:pointer;font-size:13px;color:#1A1E2E;" onmouseover="this.style.background=\'#EFF6FF\'" onmouseout="this.style.background=\'none\'"><span style="width:26px;height:26px;border-radius:50%;background:#2563EB;color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0">' + initials + '</span><span style="font-weight:500">' + u.name + '</span></div>';
    }).join('');
    drop.style.display = 'block';
}
function jaInsertMention(name) {
    var ta = document.getElementById('note_text'), val = ta.value, caret = ta.selectionStart;
    var mentionToken = name.trim().replace(/\s+/g, '_');
    ta.value = val.substring(0, jaMentionStart) + '@' + mentionToken + ' ' + val.substring(caret);
    var newPos = jaMentionStart + mentionToken.length + 2;
    ta.setSelectionRange(newPos, newPos); ta.focus();
    document.getElementById('ja-mention-drop').style.display = 'none'; jaMentionStart = -1;
}
if (window._jaMentionCloser) document.removeEventListener('click', window._jaMentionCloser);
window._jaMentionCloser = function(e) {
    if (!e.target.closest('#ja-mention-drop') && e.target.id !== 'note_text') { var d = document.getElementById('ja-mention-drop'); if (d) d.style.display = 'none'; }
};
document.addEventListener('click', window._jaMentionCloser);

/* ── Inline info edit ── */
function jaToggleInfoEdit(appId) {
    var view = document.getElementById('ja-info-view-' + appId);
    var editBox = document.getElementById('ja-info-edit-' + appId);
    var btn = document.getElementById('ja-info-edit-btn-' + appId);
    var isEdit = editBox.style.display !== 'none';
    if (isEdit) {
        editBox.style.display = 'none'; view.style.display = 'block';
        btn.innerHTML = '<i class="fa fa-pencil" style="font-size:10px"></i> Edit';
    } else {
        view.style.display = 'none'; editBox.style.display = 'block';
        btn.innerHTML = '<i class="fa fa-times" style="font-size:10px"></i> Cancel';
        document.getElementById('ja-edit-name-' + appId).focus();
    }
    var msg = document.getElementById('ja-info-save-msg-' + appId);
    if (msg) { msg.style.display = 'none'; msg.innerHTML = ''; }
}

function jaSaveInfoEdit(appId) {
    var name  = document.getElementById('ja-edit-name-' + appId).value.trim();
    var email = document.getElementById('ja-edit-email-' + appId).value.trim();
    var phone = document.getElementById('ja-edit-phone-' + appId).value.trim();
    var icon  = document.getElementById('ja-info-save-icon-' + appId);
    var msg   = document.getElementById('ja-info-save-msg-' + appId);
    var btn   = document.getElementById('ja-info-save-btn-' + appId);

    if (!name || !email) {
        msg.style.display = 'block'; msg.style.color = '#EF4444';
        msg.innerHTML = '<i class="fa fa-exclamation-circle"></i> Name and email are required.';
        return;
    }

    icon.className = 'fa fa-spinner fa-spin'; btn.disabled = true;

    $.ajax({
        type: 'POST',
        url: "{{ route('admin.job-applications.update-basic-info', ':id') }}".replace(':id', appId),
        data: { _token: '{{ csrf_token() }}', full_name: name, email: email, phone: phone },
        success: function(res) {
            icon.className = 'fa fa-check'; btn.disabled = false;
            if (res.status === 'success') {
                var n = res.data ? res.data.full_name : name;
                var e = res.data ? res.data.email     : email;
                var p = res.data ? res.data.phone     : phone;
                var nameEl  = document.getElementById('ja-display-name-' + appId);
                var emailEl = document.getElementById('ja-display-email-' + appId);
                var phoneEl = document.getElementById('ja-display-phone-' + appId);
                if (nameEl)  nameEl.textContent = n;
                if (emailEl) emailEl.innerHTML  = '<a href="mailto:' + e + '" style="color:#2563EB">' + e + '</a>';
                if (phoneEl) phoneEl.innerHTML  = '<a href="tel:' + p + '" style="color:#2563EB">' + p + '</a>';
                var viewEl  = document.getElementById('ja-info-view-' + appId);
                var editEl  = document.getElementById('ja-info-edit-' + appId);
                var editBtn = document.getElementById('ja-info-edit-btn-' + appId);
                if (editEl)  editEl.style.display = 'none';
                if (viewEl)  viewEl.style.display = 'block';
                if (editBtn) editBtn.innerHTML = '<i class="fa fa-pencil" style="font-size:10px"></i> Edit';
                if (msg)     msg.style.display = 'none';
                if (typeof table !== 'undefined') table.draw(false);
            } else {
                msg.style.display = 'block'; msg.style.color = '#EF4444';
                msg.innerHTML = '<i class="fa fa-exclamation-circle"></i> ' + (res.message || 'Save failed.');
            }
        },
        error: function() {
            icon.className = 'fa fa-check'; btn.disabled = false;
            msg.style.display = 'block'; msg.style.color = '#EF4444';
            msg.innerHTML = '<i class="fa fa-exclamation-circle"></i> Server error. Please try again.';
        }
    });
}
function jaOpenSmsModal(appId) {
    var modal = document.getElementById('ja-sms-modal-' + appId);
    if (!modal) return;
    modal._jaOriginalParent = modal.parentNode;
    var profile = modal.closest('.ja-two-col-wrap');
    modal._jaProfile = profile;
    if (profile) profile.appendChild(modal);
    (profile || document).querySelectorAll('#ja-pdf-frame, #ja-pdf-container iframe, #ja-pdf-container embed, #ja-pdf-container object').forEach(function (viewer) {
        viewer.dataset.jaSmsVisibility = viewer.style.visibility || '';
        viewer.dataset.jaSmsDisplay = viewer.style.display || '';
        viewer.style.visibility = 'hidden';
        viewer.style.display = 'none';
    });
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    var field = document.getElementById('ja-sms-message-' + appId);
    if (field) {
        field.focus();
        field.oninput = function () {
            document.getElementById('ja-sms-count-' + appId).textContent = field.value.length + '/1600';
        };
    }
}

function jaCloseSmsModal(appId) {
    var modal = document.getElementById('ja-sms-modal-' + appId);
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    (modal._jaProfile || document).querySelectorAll('#ja-pdf-frame, #ja-pdf-container iframe, #ja-pdf-container embed, #ja-pdf-container object').forEach(function (viewer) {
        viewer.style.visibility = viewer.dataset.jaSmsVisibility || '';
        viewer.style.display = viewer.dataset.jaSmsDisplay || '';
        delete viewer.dataset.jaSmsVisibility;
        delete viewer.dataset.jaSmsDisplay;
    });
    if (modal._jaOriginalParent && document.contains(modal._jaOriginalParent)) {
        modal._jaOriginalParent.appendChild(modal);
    }
}

function jaSendApplicantSms(appId) {
    var field = document.getElementById('ja-sms-message-' + appId);
    var button = document.getElementById('ja-send-sms-btn-' + appId);
    var message = field ? field.value.trim() : '';
    if (!message) {
        if (typeof toastr !== 'undefined') toastr.error('Please enter an SMS message.');
        return;
    }

    button.disabled = true;
    button.textContent = 'Sending...';
    $.ajax({
        type: 'POST',
        url: "{{ route('admin.job-applications.send-sms', ':id') }}".replace(':id', appId),
        data: { _token: '{{ csrf_token() }}', message: message },
        success: function (response) {
            if (response.status === 'success') {
                jaCloseSmsModal(appId);
                if (response.sms) jaAppendSmsHistory(appId, response.sms);
                if (typeof toastr !== 'undefined') toastr.success(response.message || 'SMS sent successfully.');
            } else if (typeof toastr !== 'undefined') {
                toastr.error(response.message || 'SMS could not be sent.');
            }
        },
        error: function (xhr) {
            var response = xhr.responseJSON || {};
            var validation = response.errors && response.errors.message ? response.errors.message[0] : null;
            if (typeof toastr !== 'undefined') toastr.error(validation || response.message || 'SMS could not be sent.');
        },
        complete: function () {
            button.disabled = false;
            button.textContent = 'Send SMS';
        }
    });
}

/* ── Applied For (job) inline edit ── */
function jaAppendSmsHistory(appId, sms) {
    var history = document.getElementById('ja-sms-history-' + appId);
    if (!history) return;

    var empty = document.getElementById('ja-sms-history-empty-' + appId);
    if (empty) empty.remove();

    var row = document.createElement('div');
    row.style.cssText = 'display:flex;justify-content:flex-end';
    var bubble = document.createElement('div');
    bubble.style.cssText = 'max-width:86%;border-radius:12px;border-bottom-right-radius:4px;padding:9px 11px;background:#2563EB;color:#fff';
    var body = document.createElement('div');
    body.style.cssText = 'font-size:12px;line-height:1.5;white-space:pre-wrap;overflow-wrap:anywhere';
    body.textContent = sms.message || '';
    var meta = document.createElement('div');
    meta.style.cssText = 'margin-top:5px;font-size:9.5px;opacity:.7;text-align:right';
    meta.textContent = (sms.sender || 'ATS') + ' \u2022 ' + (sms.time || '');

    bubble.appendChild(body);
    bubble.appendChild(meta);
    row.appendChild(bubble);
    history.appendChild(row);
    history.scrollTop = history.scrollHeight;

    var count = document.getElementById('ja-sms-history-count-' + appId);
    if (count) {
        var total = history.children.length;
        count.textContent = total + (total === 1 ? ' message' : ' messages');
    }
}

function jaToggleJobEdit(appId) {
    var view = document.getElementById('ja-job-view-' + appId);
    var edit = document.getElementById('ja-job-edit-' + appId);
    var isEdit = edit.style.display !== 'none';
    edit.style.display = isEdit ? 'none' : 'block';
    var msg = document.getElementById('ja-job-save-msg-' + appId);
    if (msg) { msg.style.display = 'none'; msg.innerHTML = ''; }
}

function jaSaveJobEdit(appId) {
    var select = document.getElementById('ja-job-select-' + appId);
    var jobId  = select.value;
    var icon   = document.getElementById('ja-job-save-icon-' + appId);
    var btn    = document.getElementById('ja-job-save-btn-' + appId);
    var msg    = document.getElementById('ja-job-save-msg-' + appId);

    if (!jobId) return;

    icon.className = 'fa fa-spinner fa-spin';
    btn.disabled = true;

    $.ajax({
        type: 'POST',
        url: "{{ route('admin.job-applications.assign-job', ':id') }}".replace(':id', appId),
        data: { _token: '{{ csrf_token() }}', job_id: jobId },
        success: function(res) {
            icon.className = 'fa fa-check';
            btn.disabled = false;

            if (res.status === 'success') {
                // Reload the whole detail panel so job title, company,
                // location, salary, and skills-required all refresh together.
                $.easyAjax({
                    type: 'GET',
                    url: "{{ route('admin.job-applications.show', ':id') }}".replace(':id', appId),
                    success: function(r) {
                        if (r.status === 'success') {
                            window.jaRenderApplicantProfile(r.view);
                        }
                    }
                });
                if (typeof table !== 'undefined') table.draw(false);
            } else {
                msg.style.display = 'block'; msg.style.color = '#EF4444';
                msg.innerHTML = '<i class="fa fa-exclamation-circle"></i> ' + (res.message || 'Save failed.');
            }
        },
        error: function() {
            icon.className = 'fa fa-check';
            btn.disabled = false;
            msg.style.display = 'block'; msg.style.color = '#EF4444';
            msg.innerHTML = '<i class="fa fa-exclamation-circle"></i> Server error. Please try again.';
        }
    });
}
</script>

@if(!is_null($application->skype_id))
<script src="https://swc.cdn.skype.com/sdk/v1/sdk.min.js"></script>
<div style="padding:12px 16px">
    <span class="skype-button rounded" data-contact-id="live:{{ $application->skype_id }}" data-text="Call"></span>
</div>
@endif
@include('admin.job-applications.partials.profile-live-sync')
@include('admin.job-applications.partials.client-review-scripts')
