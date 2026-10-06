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
    $allStatuses = $application->job_id
        ? \App\ApplicationStatus::where('job_id', $application->job_id)->orderBy('position')->get()
        : collect();
    $currentStatusId = $application->status_id;

    if (!$currentStatusId) {
        $currentStatus = (object) [
            'id' => 0,
            'status' => 'Internal',
            'color' => '#6366F1'
        ];
    } else {
        $currentStatus = $allStatuses->firstWhere('id', $currentStatusId);
    }

    // All jobs for the assign dropdown
    $allJobs = \App\Job::orderBy('title')->with('location')->get();

    // Client notes — shared across all applicants on the same job
    $clientNotes = $application->job_id
        ? \App\JobClientNote::with('user:id,name')
            ->where('job_id', $application->job_id)
            ->orderByDesc('created_at')
            ->get()
        : collect();

    // Previous applications (same email) — for the History tab
    $previousApps = \App\JobApplication::where('email', $application->email)
        ->where('is_candidate', 0)
        ->where('id', '!=', $application->id)
        ->with(['job:id,title', 'status:id,status,color'])
        ->orderByDesc('created_at')
        ->get();

    // Resolve resume URL
    $resumeUrl = null;

    // Application resume — stream through Laravel so local/S3 paths both work.
    if ($currentResumeDocument = $application->documents->firstWhere('name', 'Resume')) {
        $resumeUrl = route('admin.job-applications.resume.view', $application->id).'?v='.($currentResumeDocument->updated_at?->timestamp ?? time());
    }

    // Resume uploaded through custom questions
    if (!$resumeUrl && !empty($answers)) {
        foreach ($answers as $answer) {
            if (!empty($answer->file)) {
                if (!empty($answer->file_url)) {
                    $resumeUrl = $answer->file_url;
                } else {
                    $resumeUrl = asset_url_local_s3('documents/' . basename($answer->file));
                }
                break;
            }
        }
    }
@endphp

{{-- 
  ============================================================
  TWO-COLUMN APPLICANT DETAIL PANEL
  Left:  PDF resume viewer (pdf.js iframe or embed)
         Toolbar above CV: Resume / Additional Details / History / Interview tabs + View/Download
  Right: Tabbed detail panel (Details / Notes / Client Notes)
  Width: Controlled by the parent drawer (set to 75vw in JS)
  ============================================================
--}}

<style>
.ja-two-col-wrap {
    display: flex;
    flex-direction: column;
    height: 100vh;
    background: #F8F7F4;
    font-family: 'Plus Jakarta Sans', sans-serif;
    overflow: hidden;
}

/* ── Header ── */
.ja-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    background: #0F1F3D;
    flex-shrink: 0;
    border-bottom: 1px solid rgba(255,255,255,.08);
}
.ja-header-avatar {
    width: 38px; height: 38px; border-radius: 50%;
    border: 1.5px solid rgba(255,255,255,.25);
    object-fit: cover; flex-shrink: 0;
}
.ja-header-avatar-initials {
    width: 38px; height: 38px; border-radius: 50%;
    background: rgba(255,255,255,.12);
    display: flex; align-items: center; justify-content: center;
    font-size: 13px; font-weight: 700; color: #fff; flex-shrink: 0;
}
.ja-header-meta { flex: 1; min-width: 0; }
.ja-header-meta h2 {
    font-size: 15px; font-weight: 700; color: #fff;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin: 0;
}
.ja-header-meta p {
    font-size: 11.5px; color: rgba(255,255,255,.5); margin: 2px 0 0;
}
.ja-header-pills { display: flex; align-items: center; gap: 6px; }
.ja-pill {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;
}
.ja-pill-stage { color: #fff; }
.ja-pill-cat { background: rgba(255,255,255,.1); color: rgba(255,255,255,.75); }
.ja-close-btn {
    width: 30px; height: 30px; border-radius: 8px;
    border: 1px solid rgba(255,255,255,.15); background: rgba(255,255,255,.07);
    color: rgba(255,255,255,.7); cursor: pointer; display: flex;
    align-items: center; justify-content: center; flex-shrink: 0;
    transition: background .15s;
}
.ja-close-btn:hover { background: rgba(255,255,255,.15); }

/* ── Prev / Next navigation ── */
.ja-nav-arrows {
    display: flex; align-items: center; gap: 4px;
    background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.12);
    border-radius: 10px; padding: 3px 6px;
    flex-shrink: 0;
}
.ja-nav-btn {
    width: 26px; height: 26px; border-radius: 7px;
    border: 1px solid rgba(255,255,255,.12); background: rgba(255,255,255,.08);
    color: rgba(255,255,255,.75); cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: background .15s, color .15s, opacity .15s;
    flex-shrink: 0;
}
.ja-nav-btn:hover:not(:disabled) { background: rgba(255,255,255,.18); color: #fff; }
.ja-nav-btn:disabled { opacity: .25; cursor: not-allowed; }
.ja-nav-counter {
    font-size: 11px; font-weight: 600; color: rgba(255,255,255,.5);
    min-width: 36px; text-align: center; white-space: nowrap;
}

/* ── Body: two columns ── */
.ja-body {
    flex: 1; display: grid;
    grid-template-columns: 1fr 380px;
    overflow: hidden; min-height: 0;
}

/* ── LEFT: PDF viewer ── */
.ja-pdf-panel {
    display: flex; flex-direction: column;
    border-right: 1px solid #E8E6E1; overflow: hidden; background: #525659;
}
.ja-pdf-toolbar {
    display: flex; align-items: center; justify-content: end;
    padding: 9px 14px; background: #fff; border-bottom: 1px solid #E8E6E1;
    flex-shrink: 0; flex-wrap: wrap; gap: 8px;
}
.ja-pdf-toolbar-label {
    display: flex; align-items: center; gap: 7px;
    font-size: 12px; font-weight: 600; color: #5A6478; flex-shrink: 0;
}

/* ── Toolbar tabs (Resume / Additional Details / History / Interview) ── */
.ja-pdf-toolbar-tabs {
    display: flex; align-items: center; gap: 4px; flex-shrink: 0;
    overflow-x: auto;
}
.ja-pdf-toolbar-tabs .ja-pdftab {
    padding: 7px 11px; font-size: 12px; font-weight: 600; color: #8A94A6;
    cursor: pointer; border-bottom: 2.5px solid transparent; white-space: nowrap;
    display: flex; align-items: center; gap: 5px; transition: color .15s;
}
.ja-pdf-toolbar-tabs .ja-pdftab.active { color: #2563EB; border-bottom-color: #2563EB; }
.ja-pdf-toolbar-tabs .ja-pdftab:hover:not(.active) { color: #1A1E2E; }

.ja-pdf-toolbar-actions { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }
.ja-pdf-btn {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 5px 11px; border-radius: 8px;
    border: 1px solid #E2DED8; background: #fff; cursor: pointer;
    font-size: 12px; color: #5A6478; font-family: 'Plus Jakarta Sans', sans-serif;
    transition: background .15s, color .15s;
}
.ja-pdf-btn:hover { background: #F8F7F4; color: #1A1E2E; }
.ja-pdf-btn-primary {
    background: #2563EB; color: #fff; border-color: transparent;
}
.ja-pdf-btn-primary:hover { background: #1d4ed8; color: #fff; }
.ja-pdf-no-resume {
    flex: 1; display: flex; flex-direction: column;
    align-items: center; justify-content: center; color: #aaa; text-align: center;
    padding: 40px;
}
.ja-pdf-no-resume i { font-size: 48px; opacity: .35; display: block; margin-bottom: 14px; }
.ja-pdf-no-resume p { font-size: 13px; opacity: .6; }
.ja-pdf-frame {
    flex: 1; border: none; width: 100%; height: 100%;
    display: block;
}

/* ── PDF panel content panes (rendered above/instead of the CV embed) ── */
.ja-pdf-content-scroll {
    flex: 1; overflow-y: auto; background: #F8F7F4; padding: 16px;
}
.ja-pdf-content-pane {
    display: none;
    flex: 1;
    min-height: 0;
    overflow: hidden;
    flex-direction: column;
}
.ja-pdf-content-pane.active { display: flex; }

/* ── RIGHT: Tab panel ── */
.ja-right-panel {
    display: flex; flex-direction: column;
    overflow: hidden; background: #F8F7F4;
}
.ja-tabs {
    display: flex; background: #fff; border-bottom: 1px solid #E8E6E1;
    flex-shrink: 0; padding: 0 16px; overflow-x: auto;
}
.ja-tab {
    padding: 11px 13px; font-size: 12.5px; font-weight: 600; color: #8A94A6;
    cursor: pointer; border-bottom: 2.5px solid transparent; white-space: nowrap;
    display: flex; align-items: center; gap: 5px; transition: color .15s;
    flex-shrink: 0;
}
.ja-tab.active { color: #2563EB; border-bottom-color: #2563EB; }
.ja-tab:hover:not(.active) { color: #1A1E2E; }
.ja-tab-badge {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 18px; height: 18px; padding: 0 5px; border-radius: 20px;
    background: #F0EEE9; font-size: 10px; font-weight: 600; color: #8A94A6;
}
.ja-right-scroll {
    flex: 1; overflow-y: auto; padding: 12px;
}

/* ── Section cards ── */
.ja-card {
    background: #fff; border: 1px solid #E8E6E1; border-radius: 14px;
    padding: 14px 16px; margin-bottom: 10px;
    box-shadow: 0 1px 3px rgba(15,31,61,.04);
}
.ja-card-title {
    font-size: 11px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .08em; color: #B0B8C4; margin-bottom: 12px;
    display: flex; align-items: center; gap: 6px;
}
.ja-info-row {
    display: flex; align-items: flex-start; justify-content: space-between;
    padding: 7px 0; border-bottom: 1px solid #F0EEE9;
}
.ja-info-row:last-child { border-bottom: none; padding-bottom: 0; }
.ja-info-label {
    font-size: 11.5px; color: #8A94A6; display: flex; align-items: center;
    gap: 5px; flex-shrink: 0; width: 105px;
}
.ja-info-val {
    font-size: 12.5px; font-weight: 600; color: #1A1E2E;
    text-align: right; word-break: break-all;
}
.ja-info-val a { color: #2563EB; text-decoration: none; }
.ja-info-val a:hover { text-decoration: underline; }

/* ── Stage mover ── */
.ja-stage-row { display: flex; align-items: center; gap: 8px; }
.ja-current-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 5px 11px; border-radius: 20px;
    font-size: 11.5px; font-weight: 600; color: #fff; flex-shrink: 0;
}
.ja-stage-select {
    flex: 1; padding: 7px 10px; border-radius: 9px;
    border: 1.5px solid #E2DED8; background: #fff;
    font-size: 12.5px; font-weight: 500; color: #5A6478;
    cursor: pointer; font-family: 'Plus Jakarta Sans', sans-serif;
    transition: border-color .15s;
}
.ja-stage-select:hover, .ja-stage-select:focus { border-color: #2563EB; outline: none; }

/* ── Stars ── */
.ja-stars { display: flex; align-items: center; gap: 3px; }
.ja-star {
    font-size: 20px; cursor: pointer; color: #D1D5DB; transition: color .1s;
}
.ja-star.on { color: #F59E0B; }

/* ── Notes ── */
.ja-note {
    background: #fff; border-radius: 0 10px 10px 0;
    border: 1px solid #E8E6E1; border-left: 3px solid #2563EB;
    padding: 10px 13px; margin-bottom: 8px;
}
.ja-note-meta {
    display: flex; align-items: center; justify-content: space-between; margin-bottom: 5px;
}
.ja-note-author {
    font-size: 11.5px; font-weight: 600; color: #1A1E2E;
    display: flex; align-items: center; gap: 5px;
}
.ja-note-time { font-size: 10.5px; color: #B0B8C4; }
.ja-note-body { font-size: 12.5px; color: #5A6478; line-height: 1.6; }
.ja-note-actions { display: flex; gap: 5px; margin-top: 7px; }
.ja-note-btn {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 9px; border-radius: 7px; border: 1px solid #E8E6E1;
    background: transparent; cursor: pointer; font-size: 11px; color: #8A94A6;
    font-family: 'Plus Jakarta Sans', sans-serif; transition: background .12s;
}
.ja-note-btn:hover { background: #F8F7F4; color: #1A1E2E; }
.ja-add-note {
    background: #fff; border: 1px solid #E8E6E1; border-radius: 14px; padding: 14px;
}
.ja-note-textarea {
    width: 100%; border: 1px solid #E2DED8; border-radius: 9px;
    padding: 9px 12px; font-size: 12.5px; font-family: 'Plus Jakarta Sans', sans-serif;
    color: #1A1E2E; background: #F8F7F4; resize: none; outline: none;
    transition: border-color .15s; line-height: 1.6;
}
.ja-note-textarea:focus { border-color: #2563EB; background: #fff; }
.ja-save-note-btn {
    margin-top: 8px; display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 16px; border-radius: 9px; border: none;
    background: #2563EB; color: #fff; cursor: pointer;
    font-size: 12.5px; font-weight: 600; font-family: 'Plus Jakarta Sans', sans-serif;
    transition: background .15s;
}
.ja-save-note-btn:hover { background: #1d4ed8; }

/* ── Q&A ── */
.ja-qa-item {
    background: #fff; border: 1px solid #E8E6E1; border-radius: 10px;
    padding: 11px 13px; margin-bottom: 8px;
    border-left: 3px solid #E8E6E1;
}
.ja-qa-q { font-size: 12px; font-weight: 600; color: #1A1E2E; margin-bottom: 7px; }
.ja-qa-badge {
    display: inline-flex; align-items: center; padding: 3px 10px;
    border-radius: 20px; font-size: 11px; font-weight: 700;
}
.ja-qa-yes { background: #ECFDF5; color: #065F46; }
.ja-qa-no  { background: #FEF2F2; color: #991B1B; }

/* ── Skills ── */
.ja-skills-wrap { display: flex; flex-wrap: wrap; gap: 7px; margin-bottom: 12px; }
.ja-skill-tag {
    display: inline-flex; align-items: center; padding: 5px 12px;
    border-radius: 20px; font-size: 12px; font-weight: 500;
    background: #F1F3F7; color: #5A6478; border: 1px solid #E2DED8;
}

/* ── Action buttons ── */
.ja-action-btns { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 12px; }
.ja-btn {
    flex: 1; min-width: 110px; display: inline-flex; align-items: center;
    justify-content: center; gap: 6px; padding: 8px 12px; border-radius: 10px;
    border: none; cursor: pointer; font-size: 12.5px; font-weight: 600;
    font-family: 'Plus Jakarta Sans', sans-serif; transition: opacity .15s;
}
.ja-btn:hover { opacity: .85; }
.ja-btn-blue  { background: #EFF6FF; color: #1D4ED8; }
.ja-btn-green { background: #ECFDF5; color: #065F46; }
.ja-btn-red   { background: #FFF1F2; color: #EF4444; }

/* ── Job assign box ── */
.ja-job-assign-box {
    margin-top: 8px;
    background: #F8F7F4;
    border: 1px solid #E2DED8;
    border-radius: 10px;
    padding: 10px 12px;
}
.ja-job-assign-box-title {
    font-size: 11px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .07em; color: #B0B8C4; margin-bottom: 7px;
    display: flex; align-items: center; gap: 5px;
}
.ja-job-assign-row { display: flex; gap: 7px; align-items: center; }
.ja-job-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 10px; border-radius: 20px;
    font-size: 11.5px; font-weight: 600;
    background: #EFF6FF; color: #1D4ED8;
}
.ja-job-none-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 10px; border-radius: 20px;
    font-size: 11.5px; font-weight: 600;
    background: #F1F3F7; color: #8A94A6;
    border: 1px dashed #D1D5DB;
}
.ja-job-edit-btn {
    width: 24px; height: 24px; border-radius: 6px;
    border: 1px solid #E2DED8; background: #F8F7F4;
    color: #8A94A6; display: flex; align-items: center;
    justify-content: center; cursor: pointer; flex-shrink: 0;
    transition: background .15s, color .15s;
}
.ja-job-edit-btn:hover { background: #E8E6E1; color: #1A1E2E; }
.ja-job-save-btn {
    padding: 7px 14px; border-radius: 9px;
    border: none; background: #2563EB; color: #fff;
    font-size: 12px; font-weight: 600; cursor: pointer;
    display: inline-flex; align-items: center; gap: 5px;
    font-family: 'Plus Jakarta Sans', sans-serif;
    white-space: nowrap; transition: background .15s; flex-shrink: 0;
}
.ja-job-save-btn:hover { background: #1d4ed8; }
.ja-job-cancel-btn {
    padding: 7px 10px; border-radius: 9px;
    border: 1px solid #E2DED8; background: #fff;
    font-size: 12px; font-weight: 600; cursor: pointer;
    display: inline-flex; align-items: center; gap: 5px;
    color: #5A6478; font-family: 'Plus Jakarta Sans', sans-serif;
    white-space: nowrap; transition: background .15s; flex-shrink: 0;
}
.ja-job-cancel-btn:hover { background: #F8F7F4; }

/* ── Interview card ── */
.ja-schedule-card {
    background: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 10px; padding: 12px 14px;
}
.ja-schedule-date { font-size: 14px; font-weight: 700; color: #065F46; margin-bottom: 3px; }
.ja-schedule-type { font-size: 12px; color: #059669; }
.ja-interviewer-row {
    display: flex; align-items: center; justify-content: space-between;
    padding: 8px 10px; background: #F8F7F4; border-radius: 9px;
    border: 1px solid #E8E6E1; margin-bottom: 6px;
}
.ja-interviewer-avatar {
    width: 28px; height: 28px; border-radius: 50%; background: #EFF6FF;
    display: flex; align-items: center; justify-content: center;
    font-size: 10px; font-weight: 700; color: #1D4ED8; flex-shrink: 0;
}
.ja-badge-accept { background: #ECFDF5; color: #065F46; }
.ja-badge-refuse { background: #FEF2F2; color: #991B1B; }
.ja-badge-pending { background: #FFF7ED; color: #92400E; }
.ja-small-badge {
    font-size: 11px; font-weight: 600; padding: 3px 9px; border-radius: 20px;
}

/* ── History tab cards ── */
.ja-history-app-card { margin-bottom: 10px; }
.ja-history-app-card .ja-history-head {
    display:flex;align-items:flex-start;justify-content:space-between;gap:10px;
    margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid #F0EEE9;
}
.ja-history-section-label {
    font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;
    color:#B0B8C4;margin-bottom:6px;
}

/* ── Nav loading shimmer ── */
.ja-nav-loading {
    opacity: .5;
    pointer-events: none;
    transition: opacity .2s;
}
</style>

<div class="ja-two-col-wrap">

    {{-- ── HEADER ── --}}
    <div class="ja-header">
        <img src="{{ $application->photo_url }}" alt=""
             class="ja-header-avatar"
             onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
        <div class="ja-header-avatar-initials" style="display:none">{{ $initials }}</div>

        <div class="ja-header-meta">
            <h2>{{ ucwords($application->full_name) }}</h2>
            <p>{{ ucwords($application->job?->title ?? 'No job assigned') }} · Applied {{ $application->created_at->timezone($global->timezone)->format('d M, Y') }}</p>
        </div>

        <div class="ja-header-pills">
           <span class="ja-pill ja-pill-stage" style="background: {{ $stagePillBg }};">
                {{ $application->status ? ucwords($application->status->status) : 'Internal' }}
            </span>
            <span class="ja-pill ja-pill-cat {{ $detailCatClass }}">{{ ucfirst($detailCatName) }}</span>
        </div>

        {{-- ── PREV / NEXT NAVIGATION ── --}}
        <div class="ja-nav-arrows" id="ja-nav-arrows">
            <button type="button"
                    class="ja-nav-btn"
                    id="ja-prev-btn"
                    onclick="jaNavigate('prev', {{ $application->id }})"
                    title="Previous applicant"
                    disabled>
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>
            <span class="ja-nav-counter" id="ja-nav-counter">—</span>
            <button type="button"
                    class="ja-nav-btn"
                    id="ja-next-btn"
                    onclick="jaNavigate('next', {{ $application->id }})"
                    title="Next applicant"
                    disabled>
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
        </div>

        <button type="button" class="right-side-toggle ja-close-btn" title="@lang('app.close')" aria-label="@lang('app.close')">
            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    {{-- ── BODY: two columns ── --}}
    <div class="ja-body">

        {{-- ──────── LEFT: RESUME VIEWER + TOOLBAR TABS ──────── --}}
        <div class="ja-pdf-panel">

            <div class="ja-pdf-toolbar">
                

                <div class="ja-pdf-toolbar-actions">
                    {{--
                        Drop this block inside the `.ja-pdf-toolbar-actions` div in THIS view
                        (the Candidate Database / applications-archive applicant profile partial),
                        anywhere alongside the "Job Description" / View / Download buttons.
                        Needs $application in scope — already true on this view.
                    --}}

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
                    @if($previousApps->isNotEmpty() || $application->statusHistories->isNotEmpty() || $application->resumeHistories->isNotEmpty())
                        <div class="ja-tab" data-tab="history">
                            <i class="fa fa-history"></i> History
                        </div>
                    @endif
                      @if(!is_null($application->schedule))
                        <div class="ja-tab" data-tab="schedule">
                            <i class="fa fa-calendar" style="font-size:11px"></i> @lang('modules.interviewSchedule.scheduleDetail')
                        </div>
                    @endif
                    <button type="button" class="ja-pdf-btn" onclick="jaShowJobDesc()">
                        <i class="fa fa-file-text-o"></i> Job Description
                    </button>
                    @if($resumeUrl)
                        @include('admin.job-applications.partials.resume-update-control')
                        <a href="{{ $resumeUrl }}" target="_blank" class="ja-pdf-btn ja-current-resume-link"><i class="fa fa-external-link"></i> View</a>
                        <a href="{{ $resumeUrl }}" download class="ja-pdf-btn ja-pdf-btn-primary ja-current-resume-link"><i class="fa fa-download"></i> Download</a>
                    @endif
                </div>
            </div>
             @if($resumeUrl)
                <embed src="{{ $resumeUrl }}" type="application/pdf" class="ja-pdf-frame">
            @else
                <div class="ja-pdf-no-resume">
                    <i class="fa fa-file-pdf-o"></i>
                    <p>No resume uploaded.</p>
                </div>
            @endif


        </div>

        {{-- ──────── RIGHT: DETAIL TABS ──────── --}}
        <div class="ja-right-panel">

            {{-- Tabs --}}
            <div class="ja-tabs">
                <div class="ja-tab active" data-tab="details">
                    <i class="fa fa-user" style="font-size:11px"></i>
                    @lang('app.details')
                </div>
                <div class="ja-tab" data-tab="notes">
                    <i class="fa fa-sticky-note-o" style="font-size:11px"></i>
                    @lang('modules.jobApplication.applicantNotes')
                    @if($application->notes->count() > 0)
                        <span class="ja-tab-badge">{{ $application->notes->count() }}</span>
                    @endif
                </div>
                <div class="ja-tab" data-tab="client-notes">
                    <i class="fa fa-building" style="font-size:11px"></i>
                    Client Notes
                    @if($clientNotes->count() > 0)
                        <span class="ja-tab-badge">{{ $clientNotes->count() }}</span>
                    @endif
                </div>
            </div>

            <div class="ja-right-scroll">
                @include('admin.job-applications.partials.sms-conversation-tab')
                @if($previousApps->isNotEmpty() || $application->statusHistories->isNotEmpty() || $application->resumeHistories->isNotEmpty())
                <div id="ja-tab-history" class="ja-tab-pane" style="display:none">
                @include('admin.job-applications.partials.resume-history', ['resumeHistories' => $application->resumeHistories])

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
                                    @if($hist->fromStatus && $hist->toStatus && $hist->fromStatus->id !== $hist->toStatus->id)
                                        from <strong>{{ ucwords(str_replace('_',' ',$hist->fromStatus->status)) }}</strong>
                                        to <strong>{{ ucwords(str_replace('_',' ',$hist->toStatus->status)) }}</strong>
                                    @elseif($hist->toStatus)
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
                       
                        @php $prevAnswers = \App\JobApplicationAnswer::where('job_application_id', $prev->id)->with('question')->whereNotNull('answer')->get()->filter(fn($a) => !empty(trim($a->answer))); @endphp
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
                        @php $prevNotes = \App\ApplicantNote::where('job_application_id', $prev->id)->with('user:id,name')->orderByDesc('created_at')->get(); @endphp
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
                            @if($user->cans('add_schedule') && $application->status?->status == 'interview' && is_null($application->schedule))
                            <a onclick="createSchedule('{{ $application->id }}')" href="javascript:;" class="ja-btn ja-btn-blue">
                                <i class="fa fa-calendar-plus-o"></i>
                                @lang('modules.interviewSchedule.scheduleInterview')
                            </a>
                            @endif
                                @if($application->status?->status == 'hired' && is_null($application->onboard))
                            <a href="{{ route('admin.job-onboard.create') }}?id={{ $application->id }}" class="ja-btn ja-btn-green">
                                <i class="fa fa-rocket"></i>
                                @lang('app.startOnboard')
                            </a>
                            @endif
                        </div>

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
                                        @if($stageOption->id != $currentStatusId)
                                            <option value="{{ $stageOption->id }}">
                                                {{ ucwords(str_replace('_', ' ', $stageOption->status)) }}
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        @endif
                    </div>

                    {{-- Personal info --}}
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

                    {{-- ── VIEW MODE ── --}}
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
                    </div>

                    {{-- ── EDIT MODE ── --}}
                    @if($user->cans('edit_job_applications'))
                    <div id="ja-info-edit-{{ $application->id }}" style="display:none;margin-top:4px;">
                        <div style="display:flex;flex-direction:column;gap:8px;">

                            <div>
                                <label style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#B0B8C4;display:block;margin-bottom:4px;">
                                    @lang('app.name')
                                </label>
                                <input type="text" id="ja-edit-name-{{ $application->id }}"
                                    value="{{ $application->full_name }}"
                                    class="ja-note-textarea"
                                    style="resize:none;height:36px;padding:6px 10px;font-size:13px;">
                            </div>

                            <div>
                                <label style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#B0B8C4;display:block;margin-bottom:4px;">
                                    @lang('app.email')
                                </label>
                                <input type="email" id="ja-edit-email-{{ $application->id }}"
                                    value="{{ $application->email }}"
                                    class="ja-note-textarea"
                                    style="resize:none;height:36px;padding:6px 10px;font-size:13px;">
                            </div>

                            <div>
                                <label style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#B0B8C4;display:block;margin-bottom:4px;">
                                    @lang('app.phone')
                                </label>
                                <input type="text" id="ja-edit-phone-{{ $application->id }}"
                                    value="{{ $application->phone }}"
                                    class="ja-note-textarea"
                                    style="resize:none;height:36px;padding:6px 10px;font-size:13px;">
                            </div>

                            <div style="display:flex;gap:7px;margin-top:4px;">
                                <button type="button" onclick="jaSaveInfoEdit({{ $application->id }})"
                                        id="ja-info-save-btn-{{ $application->id }}"
                                        style="display:inline-flex;align-items:center;gap:5px;padding:7px 14px;
                                            border-radius:9px;border:none;background:#2563EB;color:#fff;
                                            font-size:12px;font-weight:600;cursor:pointer;font-family:inherit;">
                                    <i class="fa fa-check" id="ja-info-save-icon-{{ $application->id }}"></i> Save
                                </button>
                                <button type="button" onclick="jaToggleInfoEdit({{ $application->id }})"
                                        style="display:inline-flex;align-items:center;gap:5px;padding:7px 12px;
                                            border-radius:9px;border:1px solid #E2DED8;background:#fff;
                                            font-size:12px;font-weight:600;cursor:pointer;color:#5A6478;font-family:inherit;">
                                    Cancel
                                </button>
                            </div>

                            <div id="ja-info-save-msg-{{ $application->id }}" style="display:none;font-size:11.5px;margin-top:2px;"></div>
                        </div>
                    </div>
                    @endif

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

                       {{-- ══ JOB ROW ══ --}}
                        <div class="ja-info-row" style="flex-direction:column;align-items:stretch;gap:0;border-bottom:1px solid #F0EEE9;">

                            {{-- Label + badge + edit button --}}
                            <div style="display:flex;align-items:center;justify-content:space-between;padding:7px 0;">
                                <span class="ja-info-label" style="flex-shrink:0;width:105px;">
                                    <i class="fa fa-briefcase" style="font-size:11px"></i>
                                    @lang('modules.jobApplication.appliedFor')
                                </span>

                                <div style="display:flex;align-items:center;gap:6px;flex:1;justify-content:flex-end;" id="ja-job-display-{{ $application->id }}">
                                    @if(!is_null($application->job))
                                        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:2px;">
                                            <span class="ja-job-badge">
                                                <i class="fa fa-briefcase" style="font-size:9px"></i>
                                                {{ ucwords($application->job->title) }}
                                            </span>
                                            @if($application->job->company)
                                            <span style="font-size:11.5px;font-weight:600;color:#3D4A5C;">
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
                                        @if($user->cans('edit_job_applications'))
                                        <button type="button" class="ja-job-edit-btn"
                                                onclick="jaToggleJobAssign({{ $application->id }})"
                                                title="Change job">
                                            <i class="fa fa-pencil" style="font-size:10px"></i>
                                        </button>
                                        @endif
                                    @else
                                        <span class="ja-job-none-badge">
                                            <i class="fa fa-unlink" style="font-size:9px"></i>
                                            No job assigned
                                        </span>
                                        @if($user->cans('edit_job_applications'))
                                        <button type="button" class="ja-job-edit-btn"
                                                onclick="jaToggleJobAssign({{ $application->id }})"
                                                title="Assign a job">
                                            <i class="fa fa-plus" style="font-size:10px"></i>
                                        </button>
                                        @endif
                                    @endif
                                </div>
                            </div>

                            {{-- Assign / change dropdown --}}
                            @if($user->cans('edit_job_applications'))
                            <div class="ja-job-assign-box"
                                id="ja-job-assign-box-{{ $application->id }}"
                                style="display:{{ is_null($application->job) ? 'block' : 'none' }};">

                                <div class="ja-job-assign-box-title">
                                    <i class="fa fa-link" style="font-size:11px"></i>
                                    {{ is_null($application->job) ? 'Assign to a job' : 'Change job' }}
                                </div>

                                <div class="ja-job-assign-row">
                                    <select id="ja-job-select-{{ $application->id }}"
                                            class="ja-stage-select select2" style="flex:1">
                                        <option value="">Select a job posting…</option>
                                        @foreach($allJobs as $jobOption)
                                            <option value="{{ $jobOption->id }}"
                                                    {{ $application->job_id == $jobOption->id ? 'selected' : '' }}>
                                                {{ ucwords($jobOption->title) }}
                                                @if($jobOption->location)
                                                    – {{ ucwords($jobOption->location->location ?? '') }}
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>

                                    <button type="button" class="ja-job-save-btn"
                                            onclick="jaAssignJob({{ $application->id }})">
                                        <i class="fa fa-check" id="ja-job-save-icon-{{ $application->id }}"></i>
                                        <span id="ja-job-save-label-{{ $application->id }}">Assign</span>
                                    </button>

                                    @if(!is_null($application->job))
                                    <button type="button" class="ja-job-cancel-btn"
                                            onclick="jaToggleJobAssign({{ $application->id }})">
                                        Cancel
                                    </button>
                                    @endif
                                </div>

                                <div id="ja-job-assign-msg-{{ $application->id }}"
                                    style="display:none;font-size:11.5px;margin-top:6px;"></div>
                            </div>
                            @endif

                        </div>{{-- ══ end job row ══ --}}

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

                    {{-- Cover letter --}}
                    @if (!is_null($application->cover_letter))
                    <div class="ja-card">
                        <div class="ja-card-title">
                            <i class="fa fa-file-text-o" style="font-size:11px"></i> @lang('modules.jobs.coverLetter')
                        </div>
                        <p style="font-size:12.5px;color:#5A6478;line-height:1.7;white-space:pre-wrap;margin:0">{{ $application->cover_letter }}</p>
                    </div>
                    @endif

                    {{-- Skills --}}
                    @if ($user->cans('edit_job_applications'))
                    <div class="ja-card" id="skills-container-{{ $application->id }}">

                        <div class="ja-card-title">
                            <i class="fa fa-star" style="font-size:11px"></i> @lang('modules.jobApplication.skills')
                        </div>

                        {{-- Select2 multi-select --}}
                        <div class="mb-3">
                            <select name="skills[]" id="skills" class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm select2" multiple>
                                @forelse ($skills as $skill)
                                    <option @if(!is_null($application->skills) && in_array((string)$skill->id, array_map('strval', (array)$application->skills))) selected @endif value="{{ $skill->id }}">{{ $skill->name }}</option>
                                @empty
                                @endforelse
                            </select>
                        </div>

                        {{-- ── Parse from CV ── --}}
                        <div style="border:1px solid #E8E6E1;border-radius:10px;padding:12px;margin-bottom:10px;background:#F8F7F4">
                            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#B0B8C4;margin-bottom:8px">
                                <i class="fa fa-magic" style="font-size:11px"></i> Auto-detect from CV
                            </div>

                            <button type="button" id="parse-skills-btn-{{ $application->id }}"
                                    onclick="jaParseSkills({{ $application->id }})"
                                    class="ja-btn ja-btn-blue" style="min-width:auto;flex:none;display:inline-flex;margin-bottom:8px">
                                <i class="fa fa-search" id="parse-icon-{{ $application->id }}"></i>
                                <span id="parse-label-{{ $application->id }}">Parse skills from CV</span>
                            </button>

                            {{-- Results area --}}
                            <div id="parse-result-{{ $application->id }}" style="display:none">

                                {{-- Matched chips --}}
                                <div id="parse-matched-wrap-{{ $application->id }}" style="display:none;margin-bottom:8px">
                                    <div style="font-size:11px;color:#065F46;font-weight:600;margin-bottom:5px">
                                        <i class="fa fa-check-circle" style="font-size:11px"></i> Matched in your skills list — click to add:
                                    </div>
                                    <div id="parse-matched-chips-{{ $application->id }}" style="display:flex;flex-wrap:wrap;gap:5px"></div>
                                </div>

                                {{-- New chips --}}
                                <div id="parse-new-wrap-{{ $application->id }}" style="display:none">
                                    <div style="font-size:11px;color:#92400E;font-weight:600;margin-bottom:5px">
                                        <i class="fa fa-plus-circle" style="font-size:11px"></i> New skills found — click to create & add:
                                    </div>
                                    <div id="parse-new-chips-{{ $application->id }}" style="display:flex;flex-wrap:wrap;gap:5px"></div>
                                </div>

                                <div id="parse-status-{{ $application->id }}" style="font-size:11.5px;color:#059669;margin-top:6px"></div>
                            </div>

                            {{-- Error --}}
                            <div id="parse-error-{{ $application->id }}" style="display:none;font-size:12px;color:#EF4444;margin-top:6px">
                                <i class="fa fa-exclamation-circle"></i> <span></span>
                            </div>
                        </div>

                        {{-- ── Add Manually ── --}}
                        <div style="border:1px solid #E8E6E1;border-radius:10px;padding:12px;margin-bottom:12px;background:#F8F7F4">
                            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#B0B8C4;margin-bottom:8px">
                                <i class="fa fa-keyboard-o" style="font-size:11px"></i> Add manually
                            </div>
                            <div style="display:flex;gap:7px;align-items:center">
                                <input type="text"
                                    id="manual-skill-input-{{ $application->id }}"
                                    placeholder="Type a skill name…"
                                    class="ja-note-textarea"
                                    style="flex:1;resize:none;height:36px;padding:6px 10px;font-size:12.5px"
                                    onkeydown="if(event.key==='Enter'){event.preventDefault();jaAddManualSkill({{ $application->id }});}">
                                <button type="button"
                                        onclick="jaAddManualSkill({{ $application->id }})"
                                        class="ja-btn ja-btn-blue"
                                        style="min-width:auto;flex:none;display:inline-flex;white-space:nowrap;height:36px;padding:0 12px">
                                    <i class="fa fa-plus"></i> Add
                                </button>
                            </div>
                        </div>

                        {{-- Save button --}}
                        <a href="javascript:addSkills({{ $application->id }});" id="add-skills"
                           class="ja-btn ja-btn-blue" style="min-width:auto;flex:none;display:inline-flex">
                            <i class="fa fa-plus"></i>
                            @if (!is_null($application->skills) && sizeof($application->skills) > 0)
                                @lang('modules.jobApplication.updateSkills')
                            @else
                                @lang('modules.jobApplication.addSkills')
                            @endif
                        </a>
                    </div>

                    {{-- ── Inline JS for skills panel ── --}}
                    <script>
                        (function () {

                            var appId = {{ $application->id }};

                            /* ── Ensure Select2 is initialised on THIS panel's #skills ── */
                            function getSkillSelect() {
                                return $('#skills-container-' + appId + ' select#skills');
                            }

                            /* Re-init Select2 scoped to this skills container */
                            (function initSelect2() {
                                var $sel = getSkillSelect();
                                if (!$sel.length) {
                                    /* fallback: grab by ID directly */
                                    $sel = $('#skills');
                                }
                                if ($sel.length && !$sel.hasClass('select2-hidden-accessible')) {
                                    $sel.select2({ width: '100%' });
                                }
                            })();

                            /* ── Parse skills from CV ── */
                            window.jaParseSkills = function (appId) {
                                var btn      = document.getElementById('parse-skills-btn-' + appId);
                                var icon     = document.getElementById('parse-icon-' + appId);
                                var label    = document.getElementById('parse-label-' + appId);
                                var resArea  = document.getElementById('parse-result-' + appId);
                                var errArea  = document.getElementById('parse-error-' + appId);
                                var status   = document.getElementById('parse-status-' + appId);

                                btn.disabled      = true;
                                icon.className    = 'fa fa-spinner fa-spin';
                                label.textContent = 'Parsing…';
                                resArea.style.display = 'none';
                                errArea.style.display = 'none';

                                $.ajax({
                                    type: 'POST',
                                    url: '{{ route("admin.job-applications.parse-skills", ":id") }}'.replace(':id', appId),
                                    data: { _token: '{{ csrf_token() }}' },
                                    success: function (res) {
                                        btn.disabled      = false;
                                        icon.className    = 'fa fa-search';
                                        label.textContent = 'Re-parse CV';

                                        if (res.status !== 'success') {
                                            errArea.style.display = 'block';
                                            errArea.querySelector('span').textContent = res.message || 'Parsing failed.';
                                            return;
                                        }

                                        resArea.style.display = 'block';

                                        /* ── Matched chips ── */
                                        var matchedWrap  = document.getElementById('parse-matched-wrap-' + appId);
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

                                                    /* Check not already selected */
                                                    var currentVals = $sel.val() || [];
                                                    if (currentVals.indexOf(String(skill.id)) > -1) {
                                                        chip.style.borderStyle = 'solid';
                                                        chip.style.background  = '#D1FAE5';
                                                        chip.innerHTML = '<i class="fa fa-check" style="font-size:9px"></i> ' + skill.name;
                                                        chip.onclick = null; chip.style.cursor = 'default';
                                                        return;
                                                    }

                                                    /* Select the option if it exists, otherwise create it */
                                                    var opt = $sel.find('option[value="' + skill.id + '"]');
                                                    if (opt.length) {
                                                        opt.prop('selected', true);
                                                    } else {
                                                        $sel.append(new Option(skill.name, skill.id, true, true));
                                                    }
                                                    $sel.trigger('change');

                                                    chip.style.borderStyle = 'solid';
                                                    chip.style.background  = '#D1FAE5';
                                                    chip.innerHTML = '<i class="fa fa-check" style="font-size:9px"></i> ' + skill.name;
                                                    chip.onclick   = null;
                                                    chip.style.cursor = 'default';

                                                    status.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Saving…';
                                                    jaDoSaveSkills(appId, status);
                                                };
                                                matchedChips.appendChild(chip);
                                            });
                                        } else {
                                            matchedWrap.style.display = 'none';
                                        }

                                        /* ── New chips ── */
                                        var newWrap  = document.getElementById('parse-new-wrap-' + appId);
                                        var newChips = document.getElementById('parse-new-chips-' + appId);
                                        newChips.innerHTML = '';

                                        if (res.new_skills && res.new_skills.length) {
                                            newWrap.style.display = 'block';
                                            res.new_skills.forEach(function (name) {
                                                var chip = document.createElement('span');
                                                chip.style.cssText = 'display:inline-flex;align-items:center;gap:4px;padding:4px 10px;border-radius:20px;font-size:11.5px;font-weight:500;cursor:pointer;background:#FFFBEB;color:#92400E;border:1px dashed #FDE68A;transition:all .15s';
                                                chip.innerHTML = '<i class="fa fa-plus" style="font-size:9px"></i> ' + name;

                                                chip.onclick = function () {
                                                    chip.style.opacity = '.5';
                                                    chip.style.cursor  = 'default';
                                                    chip.onclick       = null;

                                                    /* Create the skill in DB first, then add to Select2 */
                                                    $.ajax({
                                                        type: 'POST',
                                                        url:  '{{ route("admin.skills.quick-create") }}',
                                                        data: { _token: '{{ csrf_token() }}', name: name },
                                                        success: function (r) {
                                                            if (r.status === 'success' && r.id) {
                                                                var $sel = $('#skills');
                                                                var opt  = $sel.find('option[value="' + r.id + '"]');
                                                                if (opt.length) {
                                                                    opt.prop('selected', true);
                                                                } else {
                                                                    $sel.append(new Option(name, r.id, true, true));
                                                                }
                                                                $sel.trigger('change');

                                                                chip.style.opacity    = '1';
                                                                chip.style.background = '#FEF3C7';
                                                                chip.style.borderStyle = 'solid';
                                                                chip.innerHTML = '<i class="fa fa-check" style="font-size:9px"></i> ' + name;

                                                                status.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Saving…';
                                                                jaDoSaveSkills(appId, status);
                                                            } else {
                                                                chip.style.opacity = '.4';
                                                                chip.title = 'Could not create skill.';
                                                            }
                                                        },
                                                        error: function () {
                                                            chip.style.opacity = '.4';
                                                            chip.title = 'Server error — could not create skill.';
                                                        }
                                                    });
                                                };
                                                newChips.appendChild(chip);
                                            });
                                        } else {
                                            newWrap.style.display = 'none';
                                        }

                                        if ((!res.matched_skills || !res.matched_skills.length) &&
                                            (!res.new_skills     || !res.new_skills.length)) {
                                            status.textContent = 'No skills detected in this CV.';
                                        }
                                    },
                                    error: function () {
                                        btn.disabled      = false;
                                        icon.className    = 'fa fa-search';
                                        label.textContent = 'Parse skills from CV';
                                        errArea.style.display = 'block';
                                        errArea.querySelector('span').textContent = 'Server error. Please try again.';
                                    }
                                });
                            };

                            /* ── Shared save helper — debounced so rapid clicks don't fire multiple POSTs ── */
                            var _saveTimer = null;
                            function jaDoSaveSkills(appId, statusEl) {
                                clearTimeout(_saveTimer);
                                _saveTimer = setTimeout(function () {
                                    var url = "{{ route('admin.job-applications.addSkills', ':id') }}".replace(':id', appId);
                                    $.easyAjax({
                                        url: url, type: 'POST', container: '#skills-container-' + appId,
                                        data: { _token: '{{ csrf_token() }}', skills: $('#skills').val() },
                                        success: function (response) {
                                            if (response.status === 'success') {
                                                if (statusEl) statusEl.innerHTML = '<i class="fa fa-check-circle" style="color:#059669"></i> Saved successfully';
                                                if (typeof table !== 'undefined') table.draw(false);
                                            } else {
                                                if (statusEl) statusEl.innerHTML = '<span style="color:#EF4444"><i class="fa fa-exclamation-circle"></i> Save failed</span>';
                                            }
                                        },
                                        error: function () {
                                            if (statusEl) statusEl.innerHTML = '<span style="color:#EF4444"><i class="fa fa-exclamation-circle"></i> Save failed</span>';
                                        }
                                    });
                                }, 300);
                            }

                            /* ── Add skill manually ── */
                            window.jaAddManualSkill = function (appId) {
                                var input = document.getElementById('manual-skill-input-' + appId);
                                var name  = input.value.trim();
                                if (!name) return;

                                var $sel   = $('#skills');
                                var exists = $sel.find('option').filter(function () {
                                    return $(this).text().toLowerCase() === name.toLowerCase();
                                });

                                if (exists.length) {
                                    exists.prop('selected', true);
                                    $sel.trigger('change');
                                    input.value = '';
                                    addSkills(appId);
                                    return;
                                }

                                var newOpt = new Option(name, 'new:' + name, true, true);
                                $sel.append(newOpt).trigger('change');
                                input.value = '';
                                addSkills(appId);
                            };

                        })();
                        </script>
                    @endif
                </div>{{-- /details --}}

                {{-- ── NOTES TAB ── --}}
                <div id="ja-tab-notes" class="ja-tab-pane" style="display:none">

                    @if($user->cans('edit_job_applications'))
                    <div class="ja-add-note" style="margin-bottom:10px">
                        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#B0B8C4;margin-bottom:8px">
                            @lang('modules.jobApplication.addNote')
                        </div>
                        <div style="position:relative">
                            <textarea id="note_text" rows="3" class="ja-note-textarea"
                                    placeholder="Type a note… use @ to mention a team member"
                                    oninput="jaNoteHandleInput(this)"></textarea>
                            <div id="ja-mention-drop"
                                style="display:none;position:absolute;bottom:calc(100% + 4px);left:0;
                                        background:#fff;border:1.5px solid #E2DED8;border-radius:10px;
                                        box-shadow:0 8px 24px rgba(15,23,42,.13);z-index:9999;
                                        min-width:200px;max-height:180px;overflow-y:auto;">
                            </div>
                        </div>
                        <button id="add-note" class="ja-save-note-btn">
                            <i class="fa fa-plus"></i> @lang('modules.jobApplication.addNote')
                        </button>
                    </div>
                    @endif

                    <div id="applicant-notes">
                        @include('admin.job-applications.partials.applicant-notes-list', [
                            'notes' => $application->notes()->with('user:id,name')->orderByDesc('created_at')->get()
                        ])
                    </div>
                </div>{{-- /notes --}}

                {{-- ── CLIENT NOTES TAB ── --}}
                <div id="ja-tab-client-notes" class="ja-tab-pane" style="display:none">
                    @if(is_null($application->job_id))
                        <div style="text-align:center;padding:30px 20px;color:#B0B8C4">
                            <i class="fa fa-link" style="font-size:30px;display:block;margin-bottom:10px;opacity:.35"></i>
                            <p style="font-size:12.5px">Assign a job to this applicant to view or add client notes.</p>
                        </div>
                    @else
                        <div style="background:#F0FDF4;border:1px solid #BBF7D0;border-radius:10px;padding:10px 14px;margin-bottom:10px;display:flex;align-items:center;gap:8px">
                            <i class="fa fa-info-circle" style="color:#059669;font-size:13px;flex-shrink:0"></i>
                            <span style="font-size:11.5px;color:#065F46;line-height:1.5">These notes are shared across <strong>all applicants</strong> for <strong>{{ ucwords($application->job?->title ?? 'this job') }}</strong>.</span>
                        </div>

                        @if($user->cans('edit_job_applications'))
                        <div class="ja-add-note" style="margin-bottom:10px">
                            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#B0B8C4;margin-bottom:8px">
                                <i class="fa fa-plus-circle" style="color:#059669"></i> Add Client Note
                            </div>
                            <textarea id="client_note_text" rows="4" class="ja-note-textarea" style="width:100%;min-height:90px" placeholder="Add a note visible to all applicants on this job…"></textarea>
                            <button id="add-client-note" class="ja-save-note-btn" style="background:#059669;margin-top:8px" data-job-id="{{ $application->job_id }}">
                                <i class="fa fa-plus"></i> Add Client Note
                            </button>
                        </div>
                        @endif

                        <div id="client-notes-list">
                            @include('admin.job-applications.partials.client-notes-list', ['clientNotes' => $clientNotes])
                        </div>
                    @endif
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
                @php $jobSkillNames = $application->job?->skills()->with('skill')->get()->pluck('skill.name')->filter(); @endphp
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

{{-- ── Scripts ── --}}
@if($user->cans('edit_job_applications'))
<script src="{{ asset('assets/plugins/jquery-bar-rating-master/dist/jquery.barrating.min.js') }}" type="text/javascript"></script>
@endif

<script>
/* ── Right-panel tab switching (Details / Notes / Client Notes) ── */
document.querySelectorAll('.ja-tab').forEach(function(tab) {
    tab.addEventListener('click', function() {
        var target = this.dataset.tab;
        document.querySelectorAll('.ja-tab').forEach(function(t) { t.classList.remove('active'); });
        document.querySelectorAll('.ja-tab-pane').forEach(function(p) { p.style.display = 'none'; });
        this.classList.add('active');
        var pane = document.getElementById('ja-tab-' + target);
        if (pane) pane.style.display = 'block';
    });
});

/* ── PDF-toolbar tab switching (Resume / Additional Details / History / Interview) ── */
document.querySelectorAll('.ja-pdftab').forEach(function(tab) {
    tab.addEventListener('click', function() {
        var target = this.dataset.pdftab;
        document.querySelectorAll('.ja-pdftab').forEach(function(t) { t.classList.remove('active'); });
        document.querySelectorAll('.ja-pdf-content-pane').forEach(function(p) { p.classList.remove('active'); });
        this.classList.add('active');
        var pane = document.getElementById('ja-pdftab-' + target);
        if (pane) pane.classList.add('active');
    });
});

/* ── Stage mover ── */
function jaMoveFromDetail(appId, toStatusId, toStatusLabel, currentStatusId) {
    if (!toStatusId) return;

    var token = '{{ csrf_token() }}';
    var url = '{{ route("admin.job-applications.bulk-status-update") }}';

    $.easyAjax({
        type: 'POST',
        url: url,
        data: {
            _token: token,
            ids: [appId],
            status_id: toStatusId
        },
        success: function(response) {
            if (response.status === 'success') {

                var showUrl = "{{ route('admin.job-applications.show', ':id') }}"
                    .replace(':id', appId);

                $.easyAjax({
                    type: 'GET',
                    url: showUrl,
                    success: function(res) {
                        if (res.status === 'success') {
                            $('#right-sidebar-content').html(res.view);
                        }
                    }
                });

                if (typeof table !== 'undefined') {
                    table.draw(false);
                }

                if (typeof jaLoadTabCounts === 'function') {
                    jaLoadTabCounts();
                }
            }
        }
    });
}

/* ── Rating ── */
function jaSetRating(appId, val) {
    document.querySelectorAll('#stars-wrapper-' + appId + ' i').forEach(function(s, i) {
        s.style.color = i < val ? '#F59E0B' : '#D1D5DB';
    });
    var url = "{{ route('admin.job-applications.rating-save',':id') }}".replace(':id', appId);
    $.easyAjax({
        type: 'POST', url: url,
        data: { rating: val, '_token': '{{ csrf_token() }}' }
    });
}

/* ── Skills ── */
$('.select2#skills').select2();
function addSkills(applicationId, callback) {
    var url = "{{ route('admin.job-applications.addSkills', ':id') }}".replace(':id', applicationId);
    $.easyAjax({
        url: url, type: 'POST', container: '#skills-container-{{ $application->id }}',
        data: { _token: '{{ csrf_token() }}', skills: $('#skills').val() },
        success: function(response) {
            if (response.status === 'success') {
                if (typeof callback === 'function') callback();
                if (typeof table !== 'undefined') table.draw(false); else loadData();
            }
        }
    });
}

/* ── Applicant Notes ── */
$('#add-note').click(function() {
    $.easyAjax({
        type: 'POST', url: "{{ route('admin.applicant-note.store') }}",
        data: { '_token': '{{ csrf_token() }}', 'id': {{ $application->id }}, 'note': $('#note_text').val() },
        success: function(response) {
            if (response.status == 'success') {
                $('#applicant-notes').html(response.view);
                $('#note_text').val('');
            }
        }
    });
});

$('body').on('click', '.edit-note', function() {
    $(this).hide();
    var noteId = $(this).data('note-id');
    $('body').find('#note-' + noteId + ' .note-text').hide();
    var noteText = $('body').find('#note-' + noteId + ' .note-text').data('raw') || '';
    var textArea = '<textarea id="edit-note-text-' + noteId + '" class="ja-note-textarea" rows="3">' + noteText + '</textarea>' +
        '<button class="update-note ja-save-note-btn" data-note-id="' + noteId + '" style="margin-top:6px"><i class="fa fa-check"></i> @lang("app.save")</button>';
    $('body').find('#note-' + noteId + ' .note-textarea').html(textArea);
});

$('body').on('click', '.update-note', function() {
    var noteId = $(this).data('note-id');
    var url = "{{ route('admin.applicant-note.update', ':id') }}".replace(':id', noteId);
    $.easyAjax({
        type: 'POST', url: url,
        data: { '_token': '{{ csrf_token() }}', 'noteId': noteId, 'note': $('#edit-note-text-' + noteId).val(), '_method': 'PUT' },
        success: function(response) {
            if (response.status == 'success') $('#applicant-notes').html(response.view);
        }
    });
});

$('body').on('click', '.delete-note', function() {
    var noteId = $(this).data('note-id');
    swal({
        title: "@lang('errors.areYouSure')", text: "@lang('errors.deleteWarning')", type: "warning",
        showCancelButton: true, confirmButtonColor: "#DD6B55", confirmButtonText: "@lang('app.delete')",
        cancelButtonText: "@lang('app.cancel')", closeOnConfirm: true, closeOnCancel: true
    }, function(isConfirm) {
        if (isConfirm) {
            var url = "{{ route('admin.applicant-note.destroy', ':id') }}".replace(':id', noteId);
            $.easyAjax({
                type: 'POST', url: url,
                data: { '_token': '{{ csrf_token() }}', '_method': 'DELETE' },
                success: function(response) {
                    if (response.status == 'success') $('#applicant-notes').html(response.view);
                }
            });
        }
    });
});

/* ── Client Notes ── */
$('#add-client-note').click(function() {
    $.easyAjax({
        type: 'POST', url: "{{ route('admin.job-client-notes.store') }}",
        data: { '_token':'{{ csrf_token() }}', 'job_id':$(this).data('job-id'), 'note_text':$('#client_note_text').val() },
        success: function(response) {
            if (response.status === 'success') {
                $('#client-notes-list').html(response.view);
                $('#client_note_text').val('');
            }
        }
    });
});

$('body').on('click', '.edit-client-note', function() {
    $(this).hide();
    var noteId = $(this).data('note-id'), $noteEl = $('#cn-note-' + noteId);
    $noteEl.find('.cn-note-text').hide();
    var noteText = $noteEl.find('.cn-note-text').text().trim();
    $noteEl.find('.cn-note-textarea').html('<textarea id="cn-edit-text-' + noteId + '" class="ja-note-textarea" rows="3">' + noteText + '</textarea><button class="update-client-note ja-save-note-btn" data-note-id="' + noteId + '" style="margin-top:6px;background:#059669"><i class="fa fa-check"></i> Save</button>');
});

$('body').on('click', '.update-client-note', function() {
    var noteId = $(this).data('note-id');
    $.easyAjax({
        type: 'POST', url: "{{ route('admin.job-client-notes.update', ':id') }}".replace(':id', noteId),
        data: { '_token':'{{ csrf_token() }}', 'note':$('#cn-edit-text-' + noteId).val() },
        success: function(response) { if (response.status === 'success') $('#client-notes-list').html(response.view); }
    });
});

$('body').on('click', '.delete-client-note', function() {
    var noteId = $(this).data('note-id');
    swal({
        title: "@lang('errors.areYouSure')", text: "@lang('errors.deleteWarning')", type: "warning",
        showCancelButton: true, confirmButtonColor: "#DD6B55", confirmButtonText: "@lang('app.delete')",
        cancelButtonText: "@lang('app.cancel')", closeOnConfirm: true, closeOnCancel: true
    }, function(isConfirm) {
        if (isConfirm) {
            $.easyAjax({
                type: 'POST', url: "{{ route('admin.job-client-notes.destroy', ':id') }}".replace(':id', noteId),
                data: { '_token':'{{ csrf_token() }}' },
                success: function(response) { if (response.status === 'success') $('#client-notes-list').html(response.view); }
            });
        }
    });
});
function jaShowJobDesc() { var o = document.getElementById('ja-jobdesc-overlay'); o.style.display = 'flex'; document.body.style.overflow = 'hidden'; }
function jaHideJobDesc() { var o = document.getElementById('ja-jobdesc-overlay'); o.style.display = 'none'; document.body.style.overflow = ''; }
document.addEventListener('keydown', function(e) { if (e.key === 'Escape') jaHideJobDesc(); });
function deleteApplication(applicationId) {
    swal({
        title: "@lang('errors.areYouSure')", text: "@lang('errors.deleteWarning')", type: "warning",
        showCancelButton: true, confirmButtonColor: "#DD6B55", confirmButtonText: "@lang('app.delete')",
        cancelButtonText: "@lang('app.cancel')", closeOnConfirm: true, closeOnCancel: true
    }, function(isConfirm) {
        if (isConfirm) {
            var url = "{{ route('admin.job-applications.destroy', ':id') }}".replace(':id', applicationId);
            $.easyAjax({
                type: 'POST', url: url,
                data: { '_token': '{{ csrf_token() }}', '_method': 'DELETE' },
                success: function(response) {
                    if (window.raCloseRightSidebar) window.raCloseRightSidebar();
                    if (response.status === 'success') {
                        if (typeof table !== 'undefined') table.draw(false); else loadData();
                    }
                }
            });
        }
    });
}

/* ══════════════════════════════════════════════
   JOB ASSIGN / CHANGE
   ══════════════════════════════════════════════ */

/* Toggle the assign box open/closed */
function jaToggleJobAssign(appId) {
    var box = document.getElementById('ja-job-assign-box-' + appId);
    if (!box) return;
    var isHidden = box.style.display === 'none';
    box.style.display = isHidden ? 'block' : 'none';

    /* Update box title text when opening */
    var title = box.querySelector('.ja-job-assign-box-title');
    if (title && isHidden) {
        var hasBadge = !!document.querySelector('#ja-job-display-' + appId + ' .ja-job-badge');
        title.innerHTML = '<i class="fa fa-link" style="font-size:11px"></i> ' + (hasBadge ? 'Change job' : 'Assign to a job');
    }

    /* Also initialise Select2 on the job dropdown if not done yet */
    var $sel = $('#ja-job-select-' + appId);
    if (isHidden && $sel.length && !$sel.hasClass('select2-hidden-accessible')) {
        $sel.select2({ width: 'resolve', placeholder: 'Select a job posting…' });
    }

    if (isHidden) {
        /* Hide any previous feedback message */
        var msg = document.getElementById('ja-job-assign-msg-' + appId);
        if (msg) { msg.style.display = 'none'; msg.textContent = ''; }
    }
}

/* Save the selected job to the application */
function jaAssignJob(appId) {
    var $sel   = $('#ja-job-select-' + appId);
    var jobId  = $sel.val();
    var msg    = document.getElementById('ja-job-assign-msg-' + appId);
    var icon   = document.getElementById('ja-job-save-icon-' + appId);
    var label  = document.getElementById('ja-job-save-label-' + appId);

    if (!jobId) {
        if (msg) {
            msg.style.display = 'block';
            msg.style.color   = '#EF4444';
            msg.innerHTML     = '<i class="fa fa-exclamation-circle"></i> Please select a job first.';
        }
        return;
    }

    /* Loading state */
    if (icon)  icon.className = 'fa fa-spinner fa-spin';
    if (label) label.textContent = 'Saving…';
    if (msg)   { msg.style.display = 'none'; }

   var url = "{{ route('admin.job-applications.assign-job', ':id') }}".replace(':id', appId);

    $.ajax({
        type: 'POST',
        url: url,
        data: { _token: '{{ csrf_token() }}', job_id: jobId },
        success: function (response) {
            /* Reset button */
            if (icon)  icon.className = 'fa fa-check';
            if (label) label.textContent = 'Assign';

            if (response.status === 'success') {

                /* Show inline success feedback briefly */
                if (msg) {
                    msg.style.display = 'block';
                    msg.style.color   = '#059669';
                    msg.innerHTML     = '<i class="fa fa-check-circle"></i> Job assigned successfully.';
                }

                /* Reload the entire sidebar panel to reflect all changes
                   (header subtitle, badge, location, etc.) */
                var showUrl = "{{ route('admin.applications-archive.show', ':id') }}".replace(':id', appId);
                $.easyAjax({
                    type: 'GET', url: showUrl,
                    success: function (res) {
                        if (res.status === 'success') {
                            $('#right-sidebar-content').html(res.view);  // full reload already happens
                        }
                    }
                });

                /* Refresh the DataTable row if it exists */
                if (typeof table !== 'undefined') table.draw(false);

            } else {
                if (msg) {
                    msg.style.display = 'block';
                    msg.style.color   = '#EF4444';
                    msg.innerHTML     = '<i class="fa fa-exclamation-circle"></i> ' + (response.message || 'Could not assign job. Please try again.');
                }
            }
        },
        error: function () {
            if (icon)  icon.className = 'fa fa-check';
            if (label) label.textContent = 'Assign';
            if (msg) {
                msg.style.display = 'block';
                msg.style.color   = '#EF4444';
                msg.innerHTML     = '<i class="fa fa-exclamation-circle"></i> Server error. Please try again.';
            }
        }
    });
}

/* ══════════════════════════════════════════════
   PREV / NEXT APPLICANT NAVIGATION
   jaApplicantIds is built in index.blade.php
   inside drawCallback via jaRebuildIds().
   ══════════════════════════════════════════════ */
(function () {

    var CURRENT_ID = {{ $application->id }};
    var showUrlTpl = "{{ route('admin.job-applications.show', ':id') }}";

    function getIds() {
        return (typeof jaApplicantIds !== 'undefined' && Array.isArray(jaApplicantIds) && jaApplicantIds.length)
            ? jaApplicantIds
            : [];
    }

    function currentIndex() {
        return getIds().indexOf(CURRENT_ID);
    }

    function updateNavUI() {
        var ids  = getIds();
        var idx  = currentIndex();
        var prev = document.getElementById('ja-prev-btn');
        var next = document.getElementById('ja-next-btn');
        var ctr  = document.getElementById('ja-nav-counter');

        if (!ids.length || idx === -1) {
            if (ctr) ctr.textContent = '—';
            if (prev) prev.disabled = true;
            if (next) next.disabled = true;
            return;
        }

        if (ctr)  ctr.textContent  = (idx + 1) + ' / ' + ids.length;
        if (prev) prev.disabled    = (idx === 0);
        if (next) next.disabled    = (idx === ids.length - 1);
    }

    window.jaNavigate = function (direction, fromId) {
        var ids = getIds();
        var idx = ids.indexOf(fromId);

        if (idx === -1 || !ids.length) return;

        var targetId = (direction === 'prev') ? ids[idx - 1] : ids[idx + 1];
        if (targetId === undefined) return;

        var prevBtn = document.getElementById('ja-prev-btn');
        var nextBtn = document.getElementById('ja-next-btn');
        if (prevBtn) prevBtn.disabled = true;
        if (nextBtn) nextBtn.disabled = true;

        var wrap = document.querySelector('.ja-two-col-wrap');
        if (wrap) wrap.classList.add('ja-nav-loading');

        var url = showUrlTpl.replace(':id', targetId);

        $.easyAjax({
            type: 'GET',
            url: url,
            success: function (res) {
                if (res.status === 'success') {
                    $('#right-sidebar-content').html(res.view);

                    var $row = $('[data-id="' + targetId + '"]');
                    if ($row.length) {
                        $row[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        $row.addClass('table-active');
                        setTimeout(function () { $row.removeClass('table-active'); }, 1200);
                    }
                } else {
                    if (wrap) wrap.classList.remove('ja-nav-loading');
                    updateNavUI();
                }
            },
            error: function () {
                if (wrap) wrap.classList.remove('ja-nav-loading');
                updateNavUI();
            }
        });
    };

    /* Keyboard: ← → arrows */
    function onKeyDown(e) {
        var tag = document.activeElement ? document.activeElement.tagName : '';
        if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') return;

        if (e.key === 'ArrowLeft'  || e.keyCode === 37) {
            var prevBtn = document.getElementById('ja-prev-btn');
            if (prevBtn && !prevBtn.disabled) jaNavigate('prev', CURRENT_ID);
        }
        if (e.key === 'ArrowRight' || e.keyCode === 39) {
            var nextBtn = document.getElementById('ja-next-btn');
            if (nextBtn && !nextBtn.disabled) jaNavigate('next', CURRENT_ID);
        }
    }

    document.removeEventListener('keydown', window._jaKeyNav);
    window._jaKeyNav = onKeyDown;
    document.addEventListener('keydown', window._jaKeyNav);

    updateNavUI();

})();
// ── @mention autocomplete ─────────────────────────────────
var jaAllUsers = @json(\App\User::select('id','name')->get());
var jaMentionQuery = '';
var jaMentionStart = -1;

function jaNoteHandleInput(el) {
    var val   = el.value;
    var caret = el.selectionStart;
    var drop  = document.getElementById('ja-mention-drop');
    if (!drop) return;

    var textBefore = val.substring(0, caret);
    var atMatch    = textBefore.match(/@([a-zA-Z0-9_]*)$/);

    if (!atMatch) {
        drop.style.display = 'none';
        jaMentionStart = -1;
        return;
    }

    jaMentionQuery = atMatch[1].toLowerCase();
    jaMentionStart = caret - atMatch[0].length;

    var matches = jaAllUsers.filter(function(u) {
        return u.name.toLowerCase().includes(jaMentionQuery) && u.id !== {{ auth()->id() }};
    }).slice(0, 6);

    if (!matches.length) { drop.style.display = 'none'; return; }

    drop.innerHTML = matches.map(function(u) {
        var initials = u.name.split(' ').map(function(w){ return w[0]; }).join('').substring(0,2).toUpperCase();
        return '<div onclick="jaInsertMention(\'' + u.name.replace(/'/g, "\\'") + '\')" '
            + 'style="display:flex;align-items:center;gap:8px;padding:8px 12px;cursor:pointer;font-size:13px;color:#1A1E2E;" '
            + 'onmouseover="this.style.background=\'#EFF6FF\'" onmouseout="this.style.background=\'none\'">'
            + '<span style="width:26px;height:26px;border-radius:50%;background:#2563EB;color:#fff;'
            + 'display:inline-flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0">'
            + initials + '</span>'
            + '<span style="font-weight:500">' + u.name + '</span>'
            + '</div>';
    }).join('');

    drop.style.display = 'block';
}

function jaInsertMention(name) {
    var ta    = document.getElementById('note_text');
    var val   = ta.value;
    var caret = ta.selectionStart;
    var firstName = name.split(' ')[0];
    var before    = val.substring(0, jaMentionStart);
    var after     = val.substring(caret);
    ta.value      = before + '@' + firstName + ' ' + after;
    var newPos    = jaMentionStart + firstName.length + 2;
    ta.setSelectionRange(newPos, newPos);
    ta.focus();
    var drop = document.getElementById('ja-mention-drop');
    if (drop) drop.style.display = 'none';
    jaMentionStart = -1;
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('#ja-mention-drop') && e.target.id !== 'note_text') {
        var drop = document.getElementById('ja-mention-drop');
        if (drop) drop.style.display = 'none';
    }
});
// ── Inline info edit ─────────────────────────────────────
function jaToggleInfoEdit(appId) {
    var view    = document.getElementById('ja-info-view-' + appId);
    var editBox = document.getElementById('ja-info-edit-' + appId);
    var btn     = document.getElementById('ja-info-edit-btn-' + appId);
    var isEdit  = editBox.style.display !== 'none';

    if (isEdit) {
        // Switch back to view
        editBox.style.display = 'none';
        view.style.display    = 'block';
        btn.innerHTML = '<i class="fa fa-pencil" style="font-size:10px"></i> Edit';
    } else {
        // Switch to edit
        view.style.display    = 'none';
        editBox.style.display = 'block';
        btn.innerHTML = '<i class="fa fa-times" style="font-size:10px"></i> Cancel';
        document.getElementById('ja-edit-name-' + appId).focus();
    }

    // Clear any previous message
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
        msg.style.display = 'block';
        msg.style.color   = '#EF4444';
        msg.innerHTML     = '<i class="fa fa-exclamation-circle"></i> Name and email are required.';
        return;
    }

    icon.className = 'fa fa-spinner fa-spin';
    btn.disabled   = true;

    var url = "{{ route('admin.job-applications.update-basic-info', ':id') }}".replace(':id', appId);

    $.ajax({
        type: 'POST',
        url: url,
        data: {
            _token:    '{{ csrf_token() }}',
            full_name: name,
            email:     email,
            phone:     phone
        },
        success: function (res) {
            icon.className = 'fa fa-check';
            btn.disabled   = false;

            if (res.status === 'success') {

                var updatedName  = res.data ? res.data.full_name : name;
                var updatedEmail = res.data ? res.data.email     : email;
                var updatedPhone = res.data ? res.data.phone     : phone;

                // ── Update view-mode display ──
                var nameEl  = document.getElementById('ja-display-name-' + appId);
                var emailEl = document.getElementById('ja-display-email-' + appId);
                var phoneEl = document.getElementById('ja-display-phone-' + appId);

                if (nameEl)  nameEl.textContent = updatedName;
                if (emailEl) emailEl.innerHTML  = '<a href="mailto:' + updatedEmail + '" style="color:#2563EB">' + updatedEmail + '</a>';
                if (phoneEl) phoneEl.innerHTML  = '<a href="tel:' + updatedPhone + '" style="color:#2563EB">' + updatedPhone + '</a>';

                // ── Force switch to view mode ──
                var viewEl  = document.getElementById('ja-info-view-' + appId);
                var editEl  = document.getElementById('ja-info-edit-' + appId);
                var editBtn = document.getElementById('ja-info-edit-btn-' + appId);

                if (editEl)  editEl.style.display = 'none';
                if (viewEl)  viewEl.style.display = 'block';
                if (editBtn) editBtn.innerHTML = '<i class="fa fa-pencil" style="font-size:10px"></i> Edit';

                // Hide any error message
                if (msg) msg.style.display = 'none';

                // Refresh DataTable row if visible
                if (typeof table !== 'undefined') table.draw(false);

            } else {
                msg.style.display = 'block';
                msg.style.color   = '#EF4444';
                msg.innerHTML     = '<i class="fa fa-exclamation-circle"></i> ' + (res.message || 'Save failed.');
            }
        },
        error: function () {
            icon.className = 'fa fa-check';
            btn.disabled   = false;
            msg.style.display = 'block';
            msg.style.color   = '#EF4444';
            msg.innerHTML     = '<i class="fa fa-exclamation-circle"></i> Server error. Please try again.';
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
