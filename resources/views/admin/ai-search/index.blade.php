@extends('layouts.app')

@push('head-script')
<style>
    .ai-search-page {
        font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;
        min-height: calc(100dvh - 9.5rem);
        background: #EEF0F5;
        margin: -1rem -1.5rem;
        padding: 2rem 1.5rem;
        position: relative;
    }
    .ai-search-hero { text-align: center; margin-bottom: 2rem; }
    .ai-search-hero-icon {
        width: 52px; height: 52px;
        background: linear-gradient(135deg, #2563eb, #7c3aed);
        border-radius: 16px;
        display: inline-flex; align-items: center; justify-content: center;
        margin-bottom: 12px;
        box-shadow: 0 8px 24px rgba(37,99,235,.25);
    }
    .ai-search-hero h1 { font-size: 22px; font-weight: 700; color: #1a1e2e; margin: 0 0 6px; }
    .ai-search-hero p { font-size: 13.5px; color: #8892a0; margin: 0; }
    .ai-search-box-wrap { max-width: 680px; margin: 0 auto 2rem; }
    .ai-search-box {
        display: flex; align-items: center; gap: 10px;
        background: #fff; border: 1.5px solid #e2ded8; border-radius: 14px;
        padding: 10px 14px;
        transition: border-color .15s, box-shadow .15s;
        box-shadow: 0 2px 12px rgba(0,0,0,.06);
    }
    .ai-search-box:focus-within {
        border-color: #2563eb;
        box-shadow: 0 0 0 4px rgba(37,99,235,.08), 0 2px 12px rgba(0,0,0,.06);
    }
    .ai-search-box-icon { color: #2563eb; flex-shrink: 0; }
    .ai-search-input {
        flex: 1; border: none; outline: none;
        font-size: 14.5px; color: #1a1e2e; background: transparent;
        font-family: inherit;
    }
    .ai-search-input::placeholder { color: #b0b8c4; }
    .ai-search-btn {
        display: inline-flex; align-items: center; gap: 6px;
        font-size: 13.5px; font-weight: 600; color: #fff;
        background: #2563eb; border: none; border-radius: 10px;
        padding: 8px 20px; cursor: pointer; font-family: inherit;
        transition: background .12s; white-space: nowrap; flex-shrink: 0;
    }
    .ai-search-btn:hover { background: #1d4ed8; }
    .ai-search-btn:disabled { opacity: .6; cursor: not-allowed; }
    .ai-search-btn.secondary {
        background: #fff; color: #5a6478; border: 1.5px solid #e2ded8;
    }
    .ai-search-btn.secondary:hover { background: #f8f9fb; border-color: #2563eb; color: #2563eb; }
    .ai-search-btn.secondary.saved { background: #d1fae5; color: #065f46; border-color: #a7f3d0; }
    .ai-search-suggestions { display: flex; flex-wrap: wrap; justify-content: center; gap: 8px; margin-top: 14px; }
    .ai-search-suggestion {
        font-size: 12px; font-weight: 500; padding: 5px 14px; border-radius: 20px;
        border: 1px solid #e2ded8; background: #fff; color: #5a6478; cursor: pointer; transition: all .12s;
    }
    .ai-search-suggestion:hover { border-color: #2563eb; color: #2563eb; background: #eff6ff; }

    .ai-saved-prompts-sidebar {
        position: fixed; top: 0; right: 0; width: 320px; height: 100vh;
        background: #fff; border-left: 1px solid #e2ded8;
        box-shadow: -4px 0 24px rgba(0,0,0,.08);
        transform: translateX(100%);
        transition: transform .3s cubic-bezier(.4,0,.2,1);
        z-index: 1050;
        display: flex; flex-direction: column;
    }
    .ai-saved-prompts-sidebar.open { transform: translateX(0); }
    .ai-saved-prompts-backdrop {
        position: fixed; inset: 0; background: rgba(0,0,0,.25);
        opacity: 0; pointer-events: none; transition: opacity .3s;
        z-index: 1049;
    }
    .ai-saved-prompts-backdrop.open { opacity: 1; pointer-events: auto; }
    .ai-saved-prompts-toggle {
        position: fixed; top: 50%; right: 0; transform: translateY(-50%);
        background: #fff; border: 1px solid #e2ded8; border-right: none;
        border-radius: 12px 0 0 12px; padding: 12px 10px 12px 14px;
        cursor: pointer; box-shadow: -2px 0 12px rgba(0,0,0,.08);
        display: flex; align-items: center; gap: 6px;
        color: #5a6478; font-size: 13px; font-weight: 600;
        z-index: 1048; transition: all .2s;
    }
    .ai-saved-prompts-toggle:hover { background: #eff6ff; color: #2563eb; border-color: #2563eb; }
    .ai-saved-prompts-count {
        background: #2563eb; color: #fff; font-size: 10px; font-weight: 700;
        min-width: 18px; height: 18px; border-radius: 9px;
        display: inline-flex; align-items: center; justify-content: center;
    }
    .ai-saved-prompts-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 16px 18px; border-bottom: 1px solid #f1f3f7;
    }
    .ai-saved-prompts-title {
        font-size: 14px; font-weight: 700; color: #1a1e2e;
        display: flex; align-items: center; gap: 8px;
    }
    .ai-saved-prompts-close {
        width: 28px; height: 28px; border-radius: 8px;
        border: 1px solid #e2ded8; background: #fff; color: #8892a0;
        cursor: pointer; display: flex; align-items: center; justify-content: center;
        transition: all .12s;
    }
    .ai-saved-prompts-close:hover { border-color: #dc2626; color: #dc2626; background: #fef2f2; }
    .ai-saved-prompts-list { flex: 1; overflow-y: auto; padding: 8px; }
    .ai-saved-prompts-empty {
        text-align: center; padding: 40px 20px; color: #b0b8c4; font-size: 13px;
    }
    .ai-saved-prompt-item {
        display: flex; align-items: center; gap: 10px;
        padding: 10px 12px; border-radius: 10px; cursor: pointer;
        transition: all .12s; margin-bottom: 4px;
        border: 1px solid transparent;
    }
    .ai-saved-prompt-item:hover { background: #f8f9fb; border-color: #e2ded8; }
    .ai-saved-prompt-item.active { background: #eff6ff; border-color: #2563eb; }
    .ai-saved-prompt-text {
        flex: 1; font-size: 13px; color: #1a1e2e; font-weight: 500;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .ai-saved-prompt-date { font-size: 10px; color: #b0b8c4; white-space: nowrap; }
    .ai-saved-prompt-delete {
        width: 24px; height: 24px; border-radius: 6px;
        border: none; background: transparent; color: #b0b8c4;
        cursor: pointer; display: flex; align-items: center; justify-content: center;
        opacity: 0; transition: all .12s; flex-shrink: 0;
    }
    .ai-saved-prompt-item:hover .ai-saved-prompt-delete { opacity: 1; }
    .ai-saved-prompt-delete:hover { background: #fef2f2; color: #dc2626; }

    .ai-search-status-bar {
        max-width: 880px; margin: 0 auto 16px;
        display: flex; align-items: center; justify-content: space-between; gap: 12px;
    }
    .ai-search-status-label {
        font-size: 13px; font-weight: 600; color: #5a6478;
        display: flex; align-items: center; gap: 8px;
    }
    .ai-search-status-label span { color: #1a1e2e; }
    .ai-search-clear-btn {
        font-size: 12.5px; color: #8892a0; background: #fff;
        border: 1px solid #e2ded8; border-radius: 8px;
        padding: 5px 12px; cursor: pointer; font-family: inherit;
        transition: all .12s;
    }
    .ai-search-clear-btn:hover { border-color: #dc2626; color: #dc2626; }
    .ai-search-sort-bar { display: flex; align-items: center; gap: 8px; }
    .ai-search-sort-label { font-size: 12px; color: #8892a0; }
    .ai-search-sort-btn {
        font-size: 12px; font-weight: 500; padding: 4px 10px; border-radius: 6px;
        border: 1px solid #e2ded8; background: #fff; color: #5a6478;
        cursor: pointer; font-family: inherit; transition: all .12s;
    }
    .ai-search-sort-btn.active { background: #1a1e2e; color: #fff; border-color: #1a1e2e; }
    .ai-search-results-grid { max-width: 880px; margin: 0 auto; display: flex; flex-direction: column; gap: 10px; }

    .ai-result-card {
        background: #fff; border: 0.5px solid #e8e6e1; border-radius: 14px;
        padding: 16px 18px; display: flex; align-items: center; gap: 14px;
        cursor: pointer; transition: border-color .12s, box-shadow .12s, transform .1s;
        position: relative;
    }
    .ai-result-card:hover {
        border-color: #2563eb; box-shadow: 0 4px 20px rgba(37,99,235,.1); transform: translateY(-1px);
    }
    .ai-result-card.selected { border-color: #2563eb; background: #f8fbff; }
    /* The global form theme visually hides native checkboxes at left:-9999px. */
    input[type="checkbox"].ai-result-select {
        position: static !important; left: auto !important; opacity: 1 !important;
        width: 22px !important; height: 22px !important; margin: 0 !important;
        appearance: auto !important; -webkit-appearance: checkbox !important;
        accent-color: #2563eb; cursor: pointer; flex-shrink: 0; display: block !important;
    }
    .ai-bulk-actions { display:none; align-items:center; gap:10px; margin-left:auto; }
    .ai-bulk-actions.visible { display:flex; }
    .ai-email-modal .modal-dialog { max-width:760px; }
    .ai-email-modal .modal-content { border:0;border-radius:18px;overflow:hidden;box-shadow:0 24px 70px rgba(15,31,61,.22); }
    .ai-email-modal .modal-header { padding:18px 24px;border-bottom:1px solid #edf0f4; }
    .ai-email-modal .modal-body { padding:22px 24px;background:#fafbfc; }
    .ai-email-modal label { font-size:12px;font-weight:700;color:#53627a;margin-bottom:6px; }
    .ai-email-modal .form-control { height:42px;border:1px solid #dfe5ed;border-radius:9px;box-shadow:none; }
    .ai-email-modal textarea.form-control { height:auto;min-height:185px;padding:12px;resize:vertical; }
    .ai-email-modal .modal-footer { padding:15px 24px;border-top:1px solid #edf0f4;background:#fff; }
    #ai-email-recipient-names { padding:12px 14px!important;background:#f0f6ff!important;border:1px solid #dceaff!important;border-radius:10px!important;color:#334155!important; }
    .ai-email-modal .form-row { padding:14px;margin:0 0 18px!important;background:#fff;border:1px solid #e7ecf2;border-radius:12px; }
    .ai-email-modal .form-row .btn { height:42px;border-radius:9px;font-weight:600; }
    @media (min-width:768px) {
        .ai-email-modal .modal-body { display:grid;grid-template-columns:minmax(0,1fr) 220px;gap:14px 18px;align-items:start; }
        .ai-email-modal .modal-body > p { grid-column:1;margin:0!important; }
        .ai-email-modal #ai-email-recipient-names { grid-column:1;margin:0!important; }
        .ai-email-modal .modal-body > .form-group { grid-column:1;margin:0!important; }
        .ai-email-modal .form-row { grid-column:2;grid-row:1 / span 4;display:block;padding:15px!important;margin:0!important;background:#f5f8ff;border-color:#dbe7ff; }
        .ai-email-modal .form-row > div { width:100%;max-width:100%;margin:0 0 13px; }
        .ai-email-modal .form-row > div:last-child { margin-bottom:0; }
        .ai-email-modal .form-row .btn { width:100%; }
    }
    .ai-result-rank { font-size: 11px; font-weight: 700; color: #b0b8c4; width: 20px; text-align: center; flex-shrink: 0; }
    .ai-result-rank.top { color: #f59e0b; }
    .ai-result-avatar {
        width: 42px; height: 42px; border-radius: 50%; background: #dbeafe; color: #1d4ed8;
        font-weight: 700; font-size: 15px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .ai-result-body { flex: 1; min-width: 0; }
    .ai-result-name { font-size: 14.5px; font-weight: 700; color: #1a1e2e; margin: 0 0 2px; }
    .ai-result-meta {
        font-size: 12px; color: #8892a0; margin: 0 0 6px;
        display: flex; flex-wrap: wrap; gap: 6px; align-items: center;
    }
    .ai-result-meta-sep { width: 3px; height: 3px; border-radius: 50%; background: #d1d5db; display: inline-block; }
    .ai-result-status { display: inline-flex; align-items: center; padding: 1px 8px; border-radius: 20px; font-size: 11px; font-weight: 600; color: #fff; }
    .ai-result-skills { display: flex; flex-wrap: wrap; gap: 5px; margin-top: 4px; }
    .ai-result-skill-tag { font-size: 11px; padding: 2px 8px; border-radius: 20px; background: #f1f3f7; color: #5a6478; font-weight: 500; }
    .ai-result-skill-tag.matched { background: #d1fae5; color: #065f46; }
    .ai-result-score-col { display: flex; flex-direction: column; align-items: flex-end; gap: 5px; flex-shrink: 0; min-width: 70px; }
    .ai-result-score-pct { font-size: 18px; font-weight: 800; line-height: 1; }
    .ai-result-score-label { font-size: 10px; color: #8892a0; font-weight: 500; }
    .ai-result-score-bar-wrap { width: 60px; height: 5px; background: #e5e7eb; border-radius: 5px; overflow: hidden; }
    .ai-result-score-bar { height: 100%; border-radius: 5px; transition: width .5s ease; }
    .ai-result-view-btn {
        font-size: 12px; font-weight: 600; padding: 6px 14px; border-radius: 8px;
        border: 1.5px solid #e2ded8; background: #fff; color: #5a6478;
        cursor: pointer; font-family: inherit; flex-shrink: 0; transition: all .12s;
    }
    .ai-result-view-btn:hover { border-color: #2563eb; color: #2563eb; background: #eff6ff; }

    .ai-search-empty { max-width: 880px; margin: 0 auto; text-align: center; padding: 60px 20px; color: #8892a0; }
    .ai-search-empty-icon { font-size: 40px; opacity: .2; margin-bottom: 14px; }
    .ai-search-empty h3 { font-size: 16px; font-weight: 600; color: #5a6478; margin: 0 0 6px; }
    .ai-search-empty p { font-size: 13px; margin: 0; }
    .ai-search-loading { max-width: 880px; margin: 0 auto; display: flex; flex-direction: column; gap: 10px; }
    .ai-search-skeleton {
        background: #fff; border: 0.5px solid #e8e6e1; border-radius: 14px;
        padding: 16px 18px; display: flex; align-items: center; gap: 14px;
    }
    .ai-skel-circle { width: 42px; height: 42px; border-radius: 50%; background: #f1f3f7; flex-shrink: 0; animation: ai-skel-pulse 1.4s ease-in-out infinite; }
    .ai-skel-body { flex: 1; display: flex; flex-direction: column; gap: 8px; }
    .ai-skel-line { height: 12px; border-radius: 6px; background: #f1f3f7; animation: ai-skel-pulse 1.4s ease-in-out infinite; }
    .ai-skel-line.short { width: 40%; }
    .ai-skel-line.mid   { width: 65%; }
    @keyframes ai-skel-pulse { 0%, 100% { opacity: 1; } 50% { opacity: .5; } }
    .ai-thinking-badge {
        display: inline-flex; align-items: center; gap: 6px;
        font-size: 12px; font-weight: 600; color: #7c3aed;
        background: #f5f3ff; border: 1px solid #ddd6fe;
        border-radius: 20px; padding: 4px 12px;
    }
</style>
@endpush

@section('content')
@include('admin.partials.candidate-communications')
<div class="ai-search-page">

    {{-- Hero --}}
    <div class="ai-search-hero">
        <div class="ai-search-hero-icon">
            <svg width="26" height="26" fill="none" stroke="#fff" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
            </svg>
        </div>
        <h1>AI Candidate Search</h1>
        <p>Search across all applicant skills, CVs and cover letters using natural language</p>
    </div>

    {{-- Search box --}}
    <div class="ai-search-box-wrap">
        <div class="ai-search-box">
            <div class="ai-search-box-icon">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <input type="text" id="ai-search-input" class="ai-search-input"
                placeholder="Try: web developer, Python backend, sales manager with 5 years..."
                onkeydown="if(event.key==='Enter') aiDoSearch()">
            <button type="button" class="ai-search-btn secondary" id="ai-save-prompt-btn" onclick="aiSavePrompt()">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                </svg>
                Save
            </button>
            <button type="button" class="ai-search-btn" id="ai-search-btn" onclick="aiDoSearch()">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                        d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
                Search with AI
            </button>
        </div>

        <div class="mt-3 flex flex-wrap items-center gap-2 text-sm text-slate-600">
            <label for="ai-required-experience">Required years of experience</label>
            <input type="number" id="ai-required-experience" min="0" max="100" step="any"
                class="w-24 rounded-lg border border-slate-300 bg-white px-2 py-1.5" aria-describedby="ai-experience-hint">
            <span id="ai-experience-hint">Optional. Blank uses experience as a preference.</span>
        </div>

        {{-- Suggestions --}}
        <div class="ai-search-suggestions" id="ai-suggestions">
            <span class="ai-search-suggestion" onclick="aiQuick('Web Developer in Toronto')">Web Developer in Toronto</span>
            <span class="ai-search-suggestion" onclick="aiQuick('Welder with 5 years experience')">Welder 5 yrs exp</span>
            <span class="ai-search-suggestion" onclick="aiQuick('Laravel PHP Developer')">Laravel PHP</span>
            <span class="ai-search-suggestion" onclick="aiQuick('Sales Manager')">Sales Manager</span>
            <span class="ai-search-suggestion" onclick="aiQuick('React Frontend Developer')">React Frontend</span>
            <span class="ai-search-suggestion" onclick="aiQuick('Data Analyst in Vancouver')">Data Analyst Vancouver</span>
            <span class="ai-search-suggestion" onclick="aiQuick('Python Backend 3 years experience')">Python 3yr exp</span>
            <span class="ai-search-suggestion" onclick="aiQuick('Graphic Designer')">Graphic Designer</span>
        </div>
    </div>

    {{-- Index CVs admin panel --}}
    <div style="max-width:680px;margin:0 auto 20px;display:flex;align-items:center;justify-content:flex-end;gap:10px;" id="ai-index-bar">
        <span style="font-size:11.5px;color:#b0b8c4" id="ai-index-status"></span>
        <button type="button" onclick="aiIndexCvs()" id="ai-index-btn"
            style="font-size:12px;font-weight:600;padding:6px 14px;border-radius:9px;border:1px solid #e2ded8;background:#fff;color:#5a6478;cursor:pointer;font-family:inherit;display:inline-flex;align-items:center;gap:6px;">
            <i class="fa fa-database"></i> Index CVs for search
        </button>
    </div>

    {{-- Output area --}}
    <div id="ai-search-output">
        <div class="ai-search-empty" id="ai-empty-state">
            <div class="ai-search-empty-icon"><i class="fa fa-search"></i></div>
            <h3>Search your candidate database</h3>
            <p>Search by role, location, experience — AI reads uploaded CVs to find the best matches</p>
        </div>
    </div>

    {{-- Saved Prompts Sidebar --}}
    <div class="ai-saved-prompts-sidebar" id="ai-saved-prompts-sidebar">
        <div class="ai-saved-prompts-header">
            <div class="ai-saved-prompts-title">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                </svg>
                Saved Prompts
            </div>
            <button class="ai-saved-prompts-close" onclick="aiToggleSavedSidebar()">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="ai-saved-prompts-list" id="ai-saved-prompts-list">
            <div class="ai-saved-prompts-empty" id="ai-saved-prompts-empty">
                <i class="fa fa-bookmark-o" style="font-size:28px;opacity:.3;margin-bottom:8px;display:block;"></i>
                <div>No saved prompts yet</div>
                <div style="font-size:11px;margin-top:4px;">Click "Save" next to the search bar to store your prompts</div>
            </div>
        </div>
    </div>

    {{-- Saved Prompts Toggle Tab --}}
    <button class="ai-saved-prompts-toggle" id="ai-saved-prompts-toggle" onclick="aiToggleSavedSidebar()">
        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
        </svg>
        <span class="ai-saved-prompts-count" id="ai-saved-prompts-count">0</span>
    </button>

    {{-- Backdrop --}}
    <div class="ai-saved-prompts-backdrop" id="ai-saved-prompts-backdrop" onclick="aiToggleSavedSidebar()"></div>

</div>
@endsection

@push('footer-script')
<script src="{{ asset('assets/node_modules_files/select2/dist/js/select2.full.min.js') }}"></script>
<script>
// ═══════════════════════════════════════════════════════════════════════
// ═══ ALL GLOBAL VARIABLES ═════════════════════════════════════════════
// ═══════════════════════════════════════════════════════════════════════
var aiCurrentSort = 'score';
var aiLastResults = [];
var aiLastQuery   = '';
var aiLastMeta    = { location: '', min_experience: 0 };
var aiSavedPrompts = [];
var aiSavedSidebarOpen = false;
var aiSelectedApplicantIds = [];

// ═══════════════════════════════════════════════════════════════════════
// ═══ SAVED PROMPTS FUNCTIONS ══════════════════════════════════════════
// ═══════════════════════════════════════════════════════════════════════

function aiLoadSavedPrompts() {
    $.ajax({
        url: '{{ route("admin.ai-search.prompts.index") }}',
        type: 'GET',
        success: function(res) {
            aiSavedPrompts = res.data || [];
            aiRenderSavedPrompts();
        },
        error: function() {
            aiSavedPrompts = [];
            aiRenderSavedPrompts();
        }
    });
}

function aiToggleSavedSidebar() {
    aiSavedSidebarOpen = !aiSavedSidebarOpen;
    var sidebar = document.getElementById('ai-saved-prompts-sidebar');
    var backdrop = document.getElementById('ai-saved-prompts-backdrop');
    var toggle = document.getElementById('ai-saved-prompts-toggle');
    if (sidebar) sidebar.classList.toggle('open', aiSavedSidebarOpen);
    if (backdrop) backdrop.classList.toggle('open', aiSavedSidebarOpen);
    if (toggle) toggle.style.display = aiSavedSidebarOpen ? 'none' : 'flex';
}

function aiSavePrompt() {
    var input = document.getElementById('ai-search-input');
    var query = input ? input.value.trim() : '';
    if (!query) {
        alert('Please enter a search query first');
        return;
    }

    var btn = document.getElementById('ai-save-prompt-btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Saving…';

    $.ajax({
        url: '{{ route("admin.ai-search.prompts.store") }}',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            query_text: query,
            label: null
        },
        success: function(res) {
            btn.disabled = false;
            btn.classList.add('saved');
            btn.innerHTML = '<svg width="15" height="15" fill="currentColor" viewBox="0 0 24 24"><path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg> Saved';
            setTimeout(function() {
                btn.classList.remove('saved');
                btn.innerHTML = '<svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg> Save';
            }, 2000);
            aiLoadSavedPrompts();
            if (!aiSavedSidebarOpen) aiToggleSavedSidebar();
        },
        error: function(xhr) {
            btn.disabled = false;
            btn.innerHTML = '<svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg> Save';
            var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Failed to save';
            alert(msg);
        }
    });
}

function aiDeletePrompt(id, event) {
    if (event) event.stopPropagation();
    if (!confirm('Delete this saved prompt?')) return;

    $.ajax({
        url: '{{ route("admin.ai-search.prompts.destroy", ":id") }}'.replace(':id', id),
        type: 'DELETE',
        data: { _token: '{{ csrf_token() }}' },
        success: function() {
            aiLoadSavedPrompts();
        },
        error: function() {
            alert('Failed to delete prompt');
        }
    });
}

function aiLoadPrompt(id) {
    var prompt = aiSavedPrompts.find(function(p) { return p.id === id; });
    if (!prompt) return;

    var input = document.getElementById('ai-search-input');
    if (input) {
        input.value = prompt.query_text;
        input.focus();
    }

    $.ajax({
        url: '{{ route("admin.ai-search.prompts.use", ":id") }}'.replace(':id', id),
        type: 'POST',
        data: { _token: '{{ csrf_token() }}' }
    });

    aiToggleSavedSidebar();
}

function aiToggleFavorite(id, event) {
    if (event) event.stopPropagation();
    $.ajax({
        url: '{{ route("admin.ai-search.prompts.favorite", ":id") }}'.replace(':id', id),
        type: 'PATCH',
        data: { _token: '{{ csrf_token() }}' },
        success: function() {
            aiLoadSavedPrompts();
        }
    });
}

function aiRenderSavedPrompts() {
    var list = document.getElementById('ai-saved-prompts-list');
    var count = document.getElementById('ai-saved-prompts-count');
    if (count) count.textContent = aiSavedPrompts.length;
    if (!list) return;

    if (aiSavedPrompts.length === 0) {
        list.innerHTML =
            '<div class="ai-saved-prompts-empty" id="ai-saved-prompts-empty">' +
                '<i class="fa fa-bookmark-o" style="font-size:28px;opacity:.3;margin-bottom:8px;display:block;"></i>' +
                '<div>No saved prompts yet</div>' +
                '<div style="font-size:11px;margin-top:4px;">Click "Save" next to the search bar to store your prompts</div>' +
            '</div>';
        return;
    }

    var html = '';
    aiSavedPrompts.forEach(function(p) {
        var dateStr = new Date(p.created_at).toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
        var favIcon = p.is_favorite
            ? '<i class="fa fa-star" style="color:#f59e0b;font-size:11px;cursor:pointer;" onclick="aiToggleFavorite(' + p.id + ', event)" title="Unfavorite"></i>'
            : '<i class="fa fa-star-o" style="color:#b0b8c4;font-size:11px;cursor:pointer;" onclick="aiToggleFavorite(' + p.id + ', event)" title="Favorite"></i>';

        html +=
            '<div class="ai-saved-prompt-item" onclick="aiLoadPrompt(' + p.id + ')">' +
                '<svg width="14" height="14" fill="none" stroke="#2563eb" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:1px;">' +
                    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>' +
                '</svg>' +
                '<div style="flex:1;min-width:0;">' +
                    '<div class="ai-saved-prompt-text">' + aiEsc(p.query_text) + '</div>' +
                    '<div class="ai-saved-prompt-date">' + dateStr + ' &middot; Used ' + p.use_count + '&times;</div>' +
                '</div>' +
                favIcon +
                '<button class="ai-saved-prompt-delete" onclick="aiDeletePrompt(' + p.id + ', event)" title="Delete">' +
                    '<svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>' +
                '</button>' +
            '</div>';
    });
    list.innerHTML = html;
}

// ═══════════════════════════════════════════════════════════════════════
// ═══ SEARCH FUNCTIONS ═════════════════════════════════════════════════
// ═══════════════════════════════════════════════════════════════════════

function aiQuick(text) {
    document.getElementById('ai-search-input').value = text;
    aiDoSearch();
}

function aiDoSearch() {
    var query = document.getElementById('ai-search-input').value.trim();
    if (!query) return;

    var btn = document.getElementById('ai-search-btn');
    if (btn.disabled) return;
    var experienceInput = document.getElementById('ai-required-experience');
    if (!experienceInput.reportValidity()) return;
    var requiredExperience = Number(experienceInput.value || 0);

    aiLastQuery = query;
    document.getElementById('ai-suggestions').style.display = 'none';
    aiShowLoading();

    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Thinking…';

    $.ajax({
        url: '{{ route("admin.ai-search.parse-query") }}',
        type: 'POST',
        data: { _token: '{{ csrf_token() }}', query: query },
        success: function(res) {
            var parsed = res.data || res;
            var roles = parsed.roles || [];
            var allTerms = [].concat(parsed.skills || []).concat(parsed.keywords || []).concat(roles);
            aiLastMeta = {
                location: parsed.location || '',
                min_experience: requiredExperience || Number(parsed.min_experience || 0),
                required_experience: requiredExperience
            };
            aiSearchServer(query, allTerms, roles, aiLastMeta.location, aiLastMeta.min_experience, requiredExperience);
        },
        error: function() {
            aiLastMeta = { location: '', min_experience: requiredExperience, required_experience: requiredExperience };
            aiSearchServer(query, [query], [], '', requiredExperience, requiredExperience);
        }
    });
}

function aiSearchServer(query, terms, roles, location, minExp, requiredExperience) {
    $.ajax({
        url: '{{ route("admin.ai-search.results") }}',
        type: 'GET',
        data: { query: query, terms: terms, roles: roles, location: location, min_experience: minExp, required_experience: requiredExperience },
        success: function(res) {
            var btn = document.getElementById('ai-search-btn');
            btn.disabled = false;
            btn.innerHTML = '<svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg> Search with AI';
            aiLastResults = res.results || [];
            aiSelectedApplicantIds = [];
            aiUpdateBulkActions();
            aiRenderResults(aiLastResults, query);
        },
        error: function() {
            var btn = document.getElementById('ai-search-btn');
            btn.disabled = false;
            btn.innerHTML = 'Search with AI';
            aiSelectedApplicantIds = [];
            aiUpdateBulkActions();
            aiRenderResults([], query);
        }
    });
}

function aiIndexCvs() {
    var btn    = document.getElementById('ai-index-btn');
    var status = document.getElementById('ai-index-status');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Indexing…';
    status.textContent = '';

    function runBatch() {
        $.ajax({
            url: '{{ route("admin.ai-search.index-cvs") }}',
            type: 'POST',
            data: { _token: '{{ csrf_token() }}', limit: 20 },
            success: function(res) {
                var d = res.data || res;
                status.textContent = d.processed + ' indexed, ' + d.failed + ' skipped, ' + d.remaining + ' remaining';
                if (d.remaining > 0) {
                    setTimeout(runBatch, 800);
                } else {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa fa-check"></i> All CVs indexed';
                    setTimeout(function() { btn.innerHTML = '<i class="fa fa-database"></i> Index CVs for search'; }, 4000);
                }
            },
            error: function() {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa fa-database"></i> Index CVs for search';
                status.textContent = 'Indexing failed — check server log.';
            }
        });
    }
    runBatch();
}

function aiRenderResults(results, query) {
    var out = document.getElementById('ai-search-output');
    if (!results.length) {
        out.innerHTML = '<div class="ai-search-empty"><div class="ai-search-empty-icon"><i class="fa fa-user-times"></i></div><h3>No matches found</h3><p>No candidates matched "<strong>' + aiEsc(query) + '</strong>" — try different keywords</p><button onclick="aiClear()" style="margin-top:14px;font-size:13px;font-weight:600;padding:8px 20px;border-radius:10px;border:1.5px solid #e2ded8;background:#fff;color:#5a6478;cursor:pointer;font-family:inherit;">Clear search</button></div>';
        return;
    }

    var filterBadges = '';
    if (aiLastMeta.location) filterBadges += '<span style="font-size:11px;font-weight:600;color:#2563eb;background:#eff6ff;padding:3px 9px;border-radius:12px;"><i class="fa fa-map-marker" style="font-size:10px"></i> ' + aiEsc(aiLastMeta.location) + '</span>';
    if (aiLastMeta.min_experience > 0) filterBadges += '<span style="font-size:11px;font-weight:600;color:#7c3aed;background:#f5f3ff;padding:3px 9px;border-radius:12px;"><i class="fa fa-clock-o" style="font-size:10px"></i> ' + aiLastMeta.min_experience + '+ yrs exp</span>';

    // Build sort buttons HTML - use data-sort attribute instead of inline onclick with quotes
    var sortScoreClass = aiCurrentSort === 'score' ? 'active' : '';
    var sortNameClass  = aiCurrentSort === 'name'  ? 'active' : '';
    var sortRecentClass = aiCurrentSort === 'recent' ? 'active' : '';

    var html = '<div class="ai-search-status-bar"><div class="ai-search-status-label" style="flex-wrap:wrap;"><span class="ai-thinking-badge"><i class="fa fa-magic"></i> AI matched</span><span>' + results.length + ' candidate' + (results.length !== 1 ? 's' : '') + '</span> for "' + aiEsc(query) + '"' + (filterBadges ? '<span style="display:inline-flex;gap:5px;margin-left:4px;">' + filterBadges + '</span>' : '') + '</div><div style="display:flex;align-items:center;gap:10px;"><div class="ai-bulk-actions" id="ai-bulk-actions"><button type="button" class="ai-search-btn secondary" onclick="aiToggleAllApplicants()"><i class="fa fa-check-square-o"></i> Select all</button><button type="button" class="ai-search-btn" onclick="aiOpenEmailModal()"><i class="fa fa-envelope-o"></i> Email <span id="ai-selected-count">0</span></button></div><div class="ai-search-sort-bar"><span class="ai-search-sort-label">Sort:</span><button class="ai-search-sort-btn ' + sortScoreClass + '" data-sort="score">Best match</button><button class="ai-search-sort-btn ' + sortNameClass + '" data-sort="name">Name</button><button class="ai-search-sort-btn ' + sortRecentClass + '" data-sort="recent">Recent</button></div><button class="ai-search-clear-btn" onclick="aiClear()"><i class="fa fa-times"></i> Clear</button></div></div><div style="max-width:880px;margin:0 auto 8px;font-size:12px;color:#5a6478;"><i class="fa fa-check-square-o text-primary"></i> Select applicants using the checkbox at the far left of each result.</div><div class="ai-search-results-grid" id="ai-results-grid"></div>';
    out.innerHTML = html;

    // Attach click handlers for sort buttons (avoids quote escaping issues)
    out.querySelectorAll('.ai-search-sort-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            aiSort(this.getAttribute('data-sort'));
        });
    });

    aiRenderCards(results);
}

function aiRenderCards(results) {
    var grid = document.getElementById('ai-results-grid');
    if (!grid) return;
    var html = '';
    results.forEach(function(r, idx) {
        var initial = (r.full_name || '?').charAt(0).toUpperCase();
        var scoreColor = r.score >= 75 ? '#059669' : r.score >= 45 ? '#2563eb' : '#8892a0';
        var isTop = idx < 3;
        var avatarColors = ['#dbeafe:#1d4ed8','#d1fae5:#065f46','#fef3c7:#92400e','#fce7f3:#9d174d','#ede9fe:#5b21b6'];
        var ac = avatarColors[r.full_name.charCodeAt(0) % avatarColors.length].split(':');
        var skillsHtml = '';
        if (r.all_skills && r.all_skills.length) {
            skillsHtml = '<div class="ai-result-skills">' + r.all_skills.slice(0, 8).map(function(s) {
                var isMatch = r.matched_skills && r.matched_skills.indexOf(s) > -1;
                return '<span class="ai-result-skill-tag' + (isMatch ? ' matched' : '') + '">' + aiEsc(s) + '</span>';
            }).join('') + (r.all_skills.length > 8 ? '<span class="ai-result-skill-tag">+' + (r.all_skills.length - 8) + '</span>' : '') + '</div>';
        }
        var cvBadge = r.has_cv ? '<span style="font-size:10px;font-weight:600;color:#059669;background:#d1fae5;padding:2px 7px;border-radius:10px;margin-left:6px;"><i class="fa fa-file-text-o" style="font-size:9px"></i> CV indexed</span>' : '<span style="font-size:10px;color:#b0b8c4;background:#f1f3f7;padding:2px 7px;border-radius:10px;margin-left:6px;">No CV</span>';
        var evidence = [];
        if (r.matched_role) evidence.push('Role match: ' + r.matched_role);
        if (r.matched_codes?.length) evidence.push('Qualification found: ' + r.matched_codes.join(', '));
        if (r.location_matched) evidence.push('Location matched');
        if (aiLastMeta.min_experience > 0) {
            evidence.push(r.experience_years == null ? 'Indexed experience unavailable' : 'Indexed experience: ' + r.experience_years + ' years');
        }
        if (evidence.length) {
            skillsHtml += '<div class="mt-1 text-xs text-slate-600">' + aiEsc(evidence.join(' · ')) + '</div>';
        }
        var isSelected = aiSelectedApplicantIds.indexOf(r.id) !== -1;
        html += '<div class="ai-result-card' + (isSelected ? ' selected' : '') + '" onclick="aiOpenApplicant(' + r.id + ')"><input type="checkbox" class="ai-result-select" value="' + r.id + '" aria-label="Select ' + aiEsc(r.full_name) + '" title="Select ' + aiEsc(r.full_name) + '" ' + (isSelected ? 'checked' : '') + ' onclick="event.stopPropagation()" onchange="aiToggleApplicant(' + r.id + ', this.checked)"><div class="ai-result-rank ' + (isTop ? 'top' : '') + '">' + (isTop ? '★' : (idx+1)) + '</div><div class="ai-result-avatar" style="background:' + ac[0] + ';color:' + ac[1] + '">' + aiEsc(initial) + '</div><div class="ai-result-body"><div class="ai-result-name">' + aiEsc(r.full_name) + cvBadge + '</div><div class="ai-result-meta">' + aiEsc(r.job_title || '—') + '<span class="ai-result-meta-sep"></span><i class="fa fa-map-marker" style="font-size:10px;color:#8892a0;margin-right:2px"></i>' + aiEsc(r.location || '—') + '<span class="ai-result-meta-sep"></span><span class="ai-result-status" style="background:' + aiEsc(r.status_color || '#6b7280') + '">' + aiEsc(r.status || '—') + '</span></div>' + skillsHtml + '</div><div class="ai-result-score-col"><div class="ai-result-score-pct" style="color:' + scoreColor + '">' + r.score + '%</div><div class="ai-result-score-label">match</div><div class="ai-result-score-bar-wrap"><div class="ai-result-score-bar" style="width:' + r.score + '%;background:' + scoreColor + '"></div></div></div><button class="ai-result-view-btn" onclick="event.stopPropagation();aiOpenApplicant(' + r.id + ')">View <i class="fa fa-arrow-right" style="font-size:10px"></i></button></div>';
    });
    grid.innerHTML = html;
    aiUpdateBulkActions();
}

function aiToggleApplicant(id, selected) {
    var index = aiSelectedApplicantIds.indexOf(id);
    if (selected && index === -1) aiSelectedApplicantIds.push(id);
    if (!selected && index !== -1) aiSelectedApplicantIds.splice(index, 1);
    aiRenderCards(aiCurrentSort === 'score' ? aiLastResults.slice().sort(function(a,b){ return b.score - a.score; }) : aiLastResults);
}

function aiToggleAllApplicants() {
    var ids = aiLastResults.map(function(result) { return result.id; });
    aiSelectedApplicantIds = aiSelectedApplicantIds.length === ids.length ? [] : ids;
    aiRenderCards(aiLastResults);
}

window.ccSelection = function () { return aiSelectedApplicantIds.map(function(id) { return {type:'application',id:Number(id)}; }); };
function aiUpdateBulkActions() {
    if (window.ccRefreshSelection) window.ccRefreshSelection();
    var actions = document.getElementById('ai-bulk-actions');
    var count = document.getElementById('ai-selected-count');
    if (actions) actions.classList.toggle('visible', aiSelectedApplicantIds.length > 0);
    if (count) count.textContent = aiSelectedApplicantIds.length;
}

function aiOpenEmailModal() { if (window.ccOpen) window.ccOpen('email'); }

function aiSort(by) {
    aiCurrentSort = by;
    var sorted = aiLastResults.slice();
    if (by === 'score') sorted.sort(function(a,b){ return b.score - a.score; });
    else if (by === 'name') sorted.sort(function(a,b){ return (a.full_name||'').localeCompare(b.full_name||''); });
    else if (by === 'recent') sorted.sort(function(a,b){ return b.id - a.id; });
    // Update active class on buttons
    document.querySelectorAll('.ai-search-sort-btn').forEach(function(btn) {
        btn.classList.toggle('active', btn.getAttribute('data-sort') === by);
    });
    aiRenderCards(sorted);
}

function aiClear() {
    document.getElementById('ai-search-input').value = '';
    document.getElementById('ai-required-experience').value = '';
    document.getElementById('ai-suggestions').style.display = 'flex';
    aiLastResults = [];
    aiSelectedApplicantIds = [];
    aiLastQuery = '';
    aiUpdateBulkActions();
    document.getElementById('ai-search-output').innerHTML = '<div class="ai-search-empty" id="ai-empty-state"><div class="ai-search-empty-icon"><i class="fa fa-search"></i></div><h3>Search your candidate database</h3><p>Type a role, skill, or description — AI will find the best matches from all applicants</p></div>';
}

function aiShowLoading() {
    var html = '<div class="ai-search-loading">';
    for (var i = 0; i < 5; i++) html += '<div class="ai-search-skeleton"><div class="ai-skel-circle"></div><div class="ai-skel-body"><div class="ai-skel-line mid"></div><div class="ai-skel-line short"></div></div></div>';
    html += '</div>';
    document.getElementById('ai-search-output').innerHTML = html;
}

function aiOpenApplicant(id) {
    if (typeof window.jaDisposeApplicantProfile === 'function') window.jaDisposeApplicantProfile();
    var url = "{{ route('admin.job-applications.show', ':id') }}".replace(':id', id);
    var $sidebar = $('#right-sidebar');
    var $backdrop = $('#right-sidebar-backdrop');
    $sidebar.removeClass('translate-x-full').addClass('translate-x-0');
    $backdrop.removeClass('hidden').css({ display:'block', visibility:'visible' });
    var requestId = (window._jaDirectProfileRequestId || 0) + 1;
    window._jaDirectProfileRequestId = requestId;
    if (window._jaDirectProfileXhr && window._jaDirectProfileXhr.readyState !== 4) {
        window._jaDirectProfileXhr.abort();
    }
    window._jaDirectProfileXhr = $.ajax({ type: 'GET', url: url, success: function(response) {
        if (requestId === window._jaDirectProfileRequestId && response.status === 'success') { $('#right-sidebar').removeClass('translate-x-full').addClass('translate-x-0'); $('#right-sidebar-backdrop').removeClass('hidden').css('display', 'block'); $('#right-sidebar-content').html(response.view); }
    }});
}

function aiEsc(s) {
    return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ═══════════════════════════════════════════════════════════════════════
// ═══ SIDEBAR CLOSE HANDLERS ═══════════════════════════════════════════
// ═══════════════════════════════════════════════════════════════════════

var sidebarObserver = new MutationObserver(function () {
    var sidebar = document.getElementById('right-sidebar');
    if (!sidebar) return;
    if (sidebar.classList.contains('translate-x-full')) {
        var backdrop = document.getElementById('right-sidebar-backdrop');
        if (backdrop) {
            backdrop.classList.add('hidden');
            backdrop.style.display    = 'none';
            backdrop.style.visibility = 'hidden';
        }
    }
});

var sidebarEl = document.getElementById('right-sidebar');
if (sidebarEl) {
    sidebarObserver.observe(sidebarEl, { attributes: true, attributeFilter: ['class'] });
}

document.addEventListener('click', function (e) {
    var sidebar  = document.getElementById('right-sidebar');
    var backdrop = document.getElementById('right-sidebar-backdrop');
    if (!sidebar || !backdrop) return;

    var isInsideSidebar  = sidebar.contains(e.target);
    var isResultCard     = e.target.closest('.ai-result-card') || e.target.closest('.ai-result-view-btn');

    if (!isInsideSidebar && !isResultCard) {
        sidebar.classList.remove('translate-x-0');
        sidebar.classList.add('translate-x-full');
        backdrop.classList.add('hidden');
        backdrop.style.display    = 'none';
        backdrop.style.visibility = 'hidden';
    }
});

// ═══════════════════════════════════════════════════════════════════════
// ═══ INITIALIZATION ═══════════════════════════════════════════════════
// ═══════════════════════════════════════════════════════════════════════

$(document).ready(function() {
    aiLoadSavedPrompts();
});
</script>
@endpush
