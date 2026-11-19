<style>
    .sign-overview {
        --sign-accent: var(--ra-accent, #2563eb);
        --sign-border: #e2e8f0;
        color: #172540;
        background: white;
        border: 1px solid var(--sign-border);
        border-radius: 18px;
        box-shadow: 0 8px 26px #1725400b;
    }
    .sign-overview-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        padding: 10px 14px;
        border-bottom: 1px solid var(--sign-border);
        background: #f8fafc;
        border-radius: 17px 17px 0 0;
    }
    .sign-overview-tabs a, .sign-overview-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 40px;
        padding: 8px 12px;
        border: 1px solid transparent;
        border-radius: 9px;
        color: #475569;
        font-size: 13px;
        font-weight: 650;
        text-decoration: none;
    }
    .sign-overview-tabs a:hover, .sign-overview-action:hover { background: #eff6ff; color: #1d4ed8; }
    .sign-overview-tabs a[aria-current="page"] {
        color: #1d4ed8;
        background: white;
        border-color: #dbeafe;
        box-shadow: 0 1px 3px #17254012;
    }
    .sign-overview-tabs a span {
        min-width: 23px;
        padding: 1px 6px;
        border-radius: 12px;
        background: #e9eff7;
        text-align: center;
        font-size: 11px;
        font-variant-numeric: tabular-nums;
    }
    .sign-overview-tabs a:focus-visible, .sign-overview-action:focus-visible {
        outline: 2px solid var(--sign-accent);
        outline-offset: 2px;
    }
    .sign-overview-header {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 16px 20px;
        border-bottom: 1px solid var(--sign-border);
    }
    .sign-overview-header h2 { margin: 0; font-size: 17px; font-weight: 700; }
    .sign-overview-action { border-color: var(--sign-border); color: #1d4ed8; background: white; }
    .sign-overview-primary { border-color: transparent; color: white; background: var(--sign-accent); }
    .sign-overview-primary:hover { color: white; background: #1d4ed8; }
    .sign-overview-table { width: 100%; min-width: 560px; font-size: 13px; }
    .sign-overview-table th, .sign-overview-table td { padding: 13px 20px; text-align: left; }
    .sign-overview-table th { background: #f8fafc; color: #475569; font-size: 12px; font-weight: 650; }
    .sign-overview-table td { border-top: 1px solid #edf1f6; }
    .sign-overview-table td:first-child { font-weight: 650; }
    .sign-overview-table tbody tr:hover { background: #f8fafc; }
    .sign-overview-table .btn, .sign-overview-table .sign-overview-action { padding: 6px 10px; font-size: 12px; min-height: 32px; }
    .sign-overview-table .sign-overview-remove { min-width: 32px; color: #64748b; font-size: 20px; line-height: 1; }
    .sign-overview-table .sign-overview-remove:hover { color: #b91c1c; border-color: #fecaca; background: #fef2f2; }
    .sign-overview-status {
        display: inline-block;
        padding: 4px 9px;
        background: #edf2f7;
        border-radius: 20px;
        color: #334155;
        font-size: 12px;
        font-weight: 650;
        white-space: nowrap;
    }
    .sign-overview-status[data-status="completed"] { color: #166534; background: #dcfce7; }
    .sign-overview-status[data-status="inprogress"], .sign-overview-status[data-status="viewed"] { color: #1e40af; background: #dbeafe; }
    .sign-overview-status[data-status="declined"], .sign-overview-status[data-status="rejected"], .sign-overview-status[data-status="expired"] { color: #991b1b; background: #fee2e2; }
    .sign-overview-empty { padding: 44px 20px; text-align: center; }
    .sign-overview-empty > i { padding: 13px; border-radius: 12px; background: #eff6ff; color: #1d4ed8; }
    .sign-overview-empty h3 { margin: 12px 0 4px; font-size: 15px; font-weight: 700; }
    .sign-overview-empty p { margin: 0; color: #475569; font-size: 13px; }
    .sign-overview-pagination { padding: 12px 20px; border-top: 1px solid var(--sign-border); }
</style>
