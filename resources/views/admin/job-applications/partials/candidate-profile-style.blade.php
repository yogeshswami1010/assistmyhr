<style>
    .ja-candidate-profile {
        --profile-line: #dce3ef;
        --profile-ink: #111c34;
        --profile-blue: #184dc7;
        height: 100dvh;
        background: #eef3f9;
    }
    .ja-candidate-profile .ja-header {
        gap: clamp(10px, 2vw, 26px);
        padding: 9px clamp(14px, 2.6vw, 40px);
        background: radial-gradient(ellipse at 42% 0%, #303477, transparent 58%), linear-gradient(105deg, #101938, #111d48 62%, #0a142d);
    }
    .ja-candidate-profile .ja-header-avatar,
    .ja-candidate-profile .ja-header-avatar-initials {
        width: clamp(40px, 4vw, 56px);
        height: clamp(40px, 4vw, 56px);
        background: #dbe3ff;
        color: #13234a;
        font-size: clamp(16px, 2vw, 27px);
    }
    .ja-candidate-profile .ja-header-meta { padding-inline-start: clamp(10px, 2vw, 28px); border-inline-start: 1px solid #ffffff45; }
    .ja-candidate-profile .ja-header-meta h2 { font-size: clamp(20px, 2vw, 28px); line-height: 1.12; }
    .ja-candidate-profile .ja-header-meta p { color: #d3ddfa; font-size: 12px; line-height: 1.3; }
    .ja-candidate-profile .ja-header-meta .ja-header-job { color: #fff; font-size: clamp(13px, 1.2vw, 16px); }
    .ja-header-meta-separator { margin-inline: 5px; }
    .ja-candidate-profile .ja-nav-arrows { background: none; border: 0; gap: 9px; }
    .ja-candidate-profile .ja-nav-btn,
    .ja-candidate-profile .ja-close-btn { width: 36px; height: 36px; color: #fff; }
    .ja-candidate-profile .ja-nav-counter { color: #fff; font-size: 12px; }
    .ja-candidate-profile .ja-profile-toolbar { position: relative; z-index: 4; overflow: visible; flex-wrap: wrap; justify-content: space-between; gap: 4px 12px; padding: 8px clamp(12px, 2vw, 32px); border-color: var(--profile-line); }
    .ja-candidate-profile .ja-profile-toolbar .ja-profile-tags,
    .ja-candidate-profile .ja-profile-toolbar .ja-pdf-toolbar-tabs,
    .ja-candidate-profile .ja-profile-toolbar .ja-profile-doc-actions { display: contents; }
    .ja-candidate-profile .ja-profile-toolbar .ja-pdf-btn,
    .ja-candidate-profile .ja-profile-toolbar .ja-tab { border-radius: 7px; color: var(--profile-ink); font-size: 13px; gap: 6px; }
    .ja-candidate-profile .ja-profile-toolbar :is(.ja-pdf-btn, .ja-tab) > i { font-size: 13px; }
    .ja-candidate-profile .ja-profile-toolbar .ja-pdf-btn { border-color: transparent; }
    .ja-candidate-profile .ja-profile-toolbar .ja-tab.active { color: var(--profile-blue); }
    .ja-conversations { position: relative; }
    .ja-conversations summary { list-style: none; }
    .ja-conversations summary::-webkit-details-marker { display: none; }
    .ja-conversation-menu {
        position: absolute;
        inset-block-start: calc(100% + 4px);
        inset-inline-start: 0;
        background: #fff;
        border: 1px solid var(--profile-line);
        border-radius: 9px;
        padding: 5px;
        box-shadow: 0 8px 20px #162a521e;
    }
    .ja-conversation-menu .ja-tab { width: 100%; }
    .ja-candidate-profile .ja-body { grid-template-columns: minmax(0, 1.75fr) minmax(370px, 1fr); gap: clamp(12px, 1.5vw, 24px); padding: clamp(14px, 2vw, 32px); }
    .ja-candidate-profile .ja-pdf-panel { border: 1px solid var(--profile-line); border-radius: 13px; background: #dfe7f1; }
    .ja-candidate-profile .ja-resume-toolbar { justify-content: space-between; gap: 10px; flex-wrap: wrap; padding: 10px 14px; border-color: var(--profile-line); }
    .ja-candidate-profile .ja-pdf-toolbar-label { margin: 0; font-size: 16px; font-weight: 750; color: var(--profile-ink); }
    .ja-resume-toolbar .ja-pdf-toolbar-label i { color: var(--profile-blue); }
    .ja-resume-toolbar .ja-pdf-toolbar-actions { font-size: 12px; color: var(--profile-ink); }
    .ja-resume-zoom:not([hidden]) { display: flex; align-items: center; gap: 5px; border-inline: 1px solid var(--profile-line); padding-inline: 5px; }
    .ja-resume-zoom output { min-width: 4ch; text-align: center; }
    .ja-resume-toolbar .ja-pdf-btn { padding: 5px 8px; border-color: transparent; }
    .ja-candidate-profile #ja-pdf-container,
    .ja-candidate-profile .ja-pdf-loading { background: #dfe7f1 !important; color: #465871; }
    .ja-resume-pages { display: flex; flex-direction: column; align-items: center; gap: 16px; width: max-content; min-width: 100%; padding: 24px; }
    .ja-candidate-profile .ja-pdf-page { box-shadow: 0 3px 18px #243d6526 !important; }
    .ja-candidate-profile .ja-right-panel { min-width: 0; min-height: 0; background: transparent; }
    .ja-candidate-profile .ja-tabs { justify-content: flex-start; gap: 8px; overflow-x: auto; padding: 0 5px; background: transparent; border-color: var(--profile-line); }
    .ja-candidate-profile .ja-tabs .ja-tab { padding: 10px 8px; font-size: 13px; gap: 6px; }
    .ja-candidate-profile .ja-tabs .ja-tab > i { width: 1em; text-align: center; }
    .ja-candidate-profile .ja-right-scroll { padding: 12px 1px 1px; scrollbar-width: thin; scrollbar-color: #a9b8cc transparent; }
    .ja-candidate-profile .ja-card { border-color: var(--profile-line); padding: 15px 17px; margin-bottom: 13px; box-shadow: 0 2px 8px #12254b0a; }
    .ja-candidate-profile .ja-card-title { color: var(--profile-ink); font-size: 16px; font-weight: 750; text-transform: none; letter-spacing: normal; }
    .ja-candidate-profile .ja-info-row { gap: 12px; border-color: var(--profile-line); }
    .ja-candidate-profile .ja-info-label { width: auto; color: #4b5870; }
    .ja-candidate-profile .ja-info-val { word-break: normal; overflow-wrap: anywhere; }
    .ja-quick-actions .ja-action-btns { margin: 0; }
    .ja-quick-actions .ja-action-btns button { flex: 0 0 43px; min-width: 43px; height: 38px; font-size: 16px; padding: 0; }
    .ja-quick-actions .ja-action-btns .ja-btn-red { margin-inline-start: auto; }
    .ja-stage-section, .ja-client-actions:not(:empty) { margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--profile-line); }
    .ja-stage-section .ja-stage-row { flex-wrap: wrap; }
    .ja-stage-section label { color: var(--profile-ink); font-size: 12px; font-weight: 700; }
    .ja-stage-section select { min-width: 125px; width: auto; }
    .ja-client-actions { display: flex; flex-wrap: wrap; gap: 10px; }
    .ja-client-actions form { flex: 1 1 150px; }
    .ja-client-actions .ja-pdf-btn { justify-content: center; width: 100%; min-height: 36px; color: var(--profile-blue); border-color: var(--profile-blue); }
    .ja-client-actions .ja-btn-blue { background: var(--profile-blue); color: #fff; }
    @media (max-width: 840px) {
        .ja-candidate-profile .ja-header-pills { display: none; }
        .ja-candidate-profile .ja-body { display: flex; flex-direction: column; overflow-y: auto; }
        .ja-candidate-profile .ja-pdf-panel { flex: 0 0 55dvh; min-height: 260px; }
        .ja-candidate-profile .ja-right-panel,
        .ja-candidate-profile .ja-right-scroll { overflow: visible; flex: none; }
        .ja-candidate-profile .ja-profile-toolbar { justify-content: flex-start; }
    }
    @media (max-width: 480px) {
        .ja-candidate-profile .ja-header { flex-wrap: wrap; }
        .ja-candidate-profile .ja-header-meta { flex-basis: calc(100% - 60px); }
        .ja-candidate-profile .ja-nav-arrows { margin-inline-start: auto; }
    }
</style>
