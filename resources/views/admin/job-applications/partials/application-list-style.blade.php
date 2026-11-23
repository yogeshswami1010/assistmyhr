<style>
    .ja-applications {
        --ja-line: #e2e6ed;
        --ja-ink: #172033;
        --ja-blue: #1755d7;
        background: #fff;
        color: var(--ja-ink);
        min-height: calc(100dvh - 11rem);
    }
    .ja-list-layout { min-width: 0; }
    .ja-app-toolbar {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px 18px;
        padding: 8px 16px;
        border-bottom: 1px solid var(--ja-line);
    }
    .ja-app-toolbar .ja-stage-tab { padding: 10px 6px; }
    .ja-app-toolbar .ja-ko-tab { margin-left: 0; padding-left: 16px; }
    .ja-app-toolbar .ja-ko-tab .ja-stage-tab:not(.active) { color: #475569; }
    .ja-app-toolbar .ja-ko-tab .ja-stage-tab:not(.active) .ja-stage-badge { background: #edf0f4; color: #475569; }
    .ja-app-search {
        flex: 0 1 340px;
        margin-inline-start: auto;
        min-width: 0;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 0 12px;
        background: #f5f7fa;
        border: 1px solid #d6dce5;
        border-radius: 9px;
        color: #475569;
    }
    .ja-app-search:focus-within { outline: 2px solid var(--ja-blue); outline-offset: 2px; }
    .ja-app-search input {
        width: 100%;
        padding: 9px 0;
        border: 0;
        outline: none;
        background: transparent;
        color: var(--ja-ink);
        font-size: 13px;
    }
    .ja-filter-form {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px 16px;
        padding: 14px 16px;
        width: 75%;
    }
    .ja-filters { border-bottom: 1px solid var(--ja-line); }
    .ja-filter-field { flex: 1 1 150px; min-width: 104px; }
    .ja-applications #ja-table-filter-bar .select2-selection--single {
        background: #fff;
        border: 1px solid #d6dce5;
        border-radius: 8px;
    }
    .ja-applications #reset-filters { border: 0; border-radius: 0; border-inline-start: 1px solid var(--ja-line); }
    .ja-pipeline-tabs { overflow-x: auto; padding-inline: 16px; }
    .ja-pipeline-tabs:has(.ja-stage-tab) { border-bottom: 1px solid var(--ja-line); }
    .ja-pipeline-tabs .ja-stage-tab { padding-block: 8px; }
    .ja-table-overflow { overflow-x: auto; }
    .ja-applications #myTable { min-width: 680px; border-collapse: collapse; border: 0; }
    .ja-applications #myTable thead th {
        padding: 11px 14px;
        border-bottom: 1px solid var(--ja-line);
        background-color: #f7f8fa;
        color: #465266;
        font-size: 11px;
        font-weight: 750;
        letter-spacing: .025em;
        text-transform: uppercase;
    }
    .ja-applications #myTable tbody td {
        padding: 8px 14px;
        border-bottom: 1px solid var(--ja-line);
        color: var(--ja-ink);
        font-size: 13px;
        vertical-align: middle;
    }
    .ja-applications #myTable tbody tr:hover td { background: #f8faff; }
    .ja-applicant { display: flex; align-items: center; gap: 10px; }
    .ja-applicant-avatar {
        display: grid;
        place-items: center;
        flex: 0 0 32px;
        height: 32px;
        border-radius: 50%;
        background: #dbeafe;
        color: #174596;
        font-weight: 800;
    }
    .ja-applicant-name { padding: 0; border: 0; background: none; color: inherit; text-align: start; font-weight: 750; }
    .ja-applicant-name:hover { color: var(--ja-blue); }
    .ja-status-pill { display: inline-flex; white-space: nowrap; border-radius: 999px; padding: 4px 12px; font-weight: 750; font-size: 12px; }
    .ja-status-blue { color: #16408d; background: #dbeafe; }
    .ja-status-amber { color: #784307; background: #fde8ba; }
    .ja-status-green { color: #15603d; background: #dcfce7; }
    .ja-status-red { color: #991b1b; background: #fee2e2; }
    .ja-status-neutral { color: #334155; background: #edf1f6; box-shadow: inset 2px 0 var(--ja-stage-accent, transparent); }
    .ja-applications .ja-row-actions { gap: 8px; }
    .ja-applications .ja-act-btn { min-width: 34px; height: 30px; padding: 0 8px; border-radius: 8px; justify-content: center; }
    .ja-applications .ja-move-drop {
        position: fixed;
        z-index: 99999;
        width: 180px;
        max-height: 220px;
        overflow-y: auto;
        background: #fff;
        border: 1px solid var(--ja-line);
        border-radius: 9px;
        box-shadow: 0 8px 24px rgb(15 23 42 / 14%);
    }
    .ja-table-footer { display: flex; flex-wrap: wrap; align-items: center; gap: 10px 16px; padding: 14px 16px; font-size: 12px; }
    .ja-table-footer .dataTables_info { margin-inline-start: auto; }
    .ja-table-footer .dataTables_info,
    .ja-table-footer .dataTables_paginate { padding-top: 0; }
    .ja-table-footer .dataTables_length select { margin-inline-start: 5px; border: 1px solid var(--ja-line); border-radius: 7px; padding: 5px 8px; }
    .ja-table-footer .dataTables_paginate .paginate_button { min-width: 30px; margin-inline: 2px; padding: 5px 8px; border-radius: 7px; }
    .ja-table-footer .dataTables_paginate .paginate_button.current,
    .ja-table-footer .dataTables_paginate .paginate_button.current:hover { color: #fff !important; background: #1755d7; border-color: #1755d7; }
    .ja-table-footer .dataTables_paginate .paginate_button.previous,
    .ja-table-footer .dataTables_paginate .paginate_button.next { border-color: var(--ja-line); }
    @media (max-width: 1024px) {
        .ja-filter-form { width: 100%; }
    }
    @media (max-width: 700px) {
        .ja-app-search { flex: 1 0 100%; margin-inline-start: 0; }
        .ja-filter-field { flex: 1 1 125px; }
        .ja-table-footer .dataTables_info { margin-inline-start: 0; }
        .ja-table-footer .dataTables_paginate { margin-inline-start: auto; }
    }
</style>
