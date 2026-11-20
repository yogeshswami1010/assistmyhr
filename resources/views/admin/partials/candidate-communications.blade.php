@if(auth()->user()->cans('view_job_applications') && auth()->user()->cans('edit_job_applications'))
@once
<div id="candidate-communications" data-preview-url="{{ route('admin.candidate-communications.preview') }}" data-send-url="{{ route('admin.candidate-communications.send') }}" data-templates-url="{{ route('admin.candidate-communications.templates') }}" data-source="{{ request()->routeIs('admin.ai-search') ? 'ai-search' : 'candidates' }}" data-token="{{ csrf_token() }}">
    <div class="cc-toolbar" hidden data-enabled="{{ ($showCommunicationToolbar ?? true) ? '1' : '0' }}">
        <span id="cc-count" hidden>0</span>
        <div class="cc-toolbar-actions">
            <button type="button" data-cc-open="email">Bulk email</button>
            <button type="button" data-cc-open="sms">Bulk SMS</button>
        </div>
    </div>
    <dialog id="cc-dialog" aria-labelledby="cc-title">
        <form id="cc-form">
            <header class="cc-modal-header">
                <div class="cc-heading"><span class="cc-heading-icon" aria-hidden="true"><i class="fa fa-envelope-o"></i></span><div><h2 id="cc-title">Bulk email</h2><p>Connect with your selected candidates</p></div></div>
                <button type="button" id="cc-close-top" onclick="this.closest('dialog').close()" class="cc-icon-button" aria-label="Close message popup" title="Close"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" stroke-linecap="round"/></svg></button>
            </header>
            <div class="cc-modal-body">
                <div class="cc-recipient-card"><i class="fa fa-users" aria-hidden="true"></i><div><p class="cc-eyebrow">RECIPIENTS</p><p id="cc-summary" role="status"></p><details><summary>Review recipient list</summary><ul id="cc-recipients"></ul></details></div></div>
                <div class="cc-compose-grid">
                    <section aria-label="Compose message">
                        <div id="cc-email-fields"><label for="cc-subject">Subject</label><input id="cc-subject" maxlength="191" placeholder="Enter an email subject"></div>
                        <label for="cc-message">Message</label><textarea id="cc-message" rows="10" required maxlength="10000" placeholder="Write your message to candidates…"></textarea>
                        <div id="cc-signature" hidden style="margin-top:14px;padding:12px;border:1px solid #e2e8f0;border-radius:10px">
                            <strong style="font-size:12px">Your email signature</strong>
                            <iframe id="cc-signature-html" hidden title="Signature preview" sandbox="" referrerpolicy="no-referrer" style="width:100%;height:250px;background:white;border:0"></iframe>
                            <img id="cc-signature-image" hidden alt="Email signature" style="max-width:100%;max-height:200px;margin-top:10px">
                            <p id="cc-signature-preview" style="white-space:pre-wrap;font-size:12px;margin:8px 0"></p>
                            <a href="{{ route('admin.profile.index') }}" target="_blank" rel="noopener" style="font-size:12px;color:#2563eb">Edit in My Profile</a>
                        </div>
                        <div class="cc-editor-hint"><i class="fa fa-info-circle" aria-hidden="true"></i> Add <code>[applicant_name]</code> to personalize your message.</div>
                    </section>
                    <aside id="cc-template-panel" aria-label="Email templates">
                        <h3><i class="fa fa-file-text-o" aria-hidden="true"></i> Email templates</h3>
                        <p class="cc-muted">Start with a saved message or save this email for next time.</p>
                        <label for="cc-template">Use a template</label><select id="cc-template"><option value="">Choose a template</option></select>
                        <div class="cc-template-divider"></div>
                        <label for="cc-template-name">Save this email</label><input id="cc-template-name" maxlength="80" placeholder="Template name">
                        <button type="button" id="cc-save-template"><i class="fa fa-bookmark-o" aria-hidden="true"></i> Save as template</button>
                        <small>Available across your candidate lists. Use an existing name to update that template.</small>
                    </aside>
                </div>
                <p id="cc-feedback" role="status" aria-live="polite"></p>
            </div>
            <footer class="cc-modal-footer"><span class="cc-delivery-note"><i class="fa fa-lock" aria-hidden="true"></i> Each candidate receives a separate message.</span><div class="cc-actions"><button type="button" id="cc-close" onclick="this.closest('dialog').close()">Cancel</button><button type="submit" id="cc-send">Send to selected candidates</button></div></footer>
        </form>
    </dialog>
</div>
@push('head-script')
<style>
.cc-toolbar[hidden]{display:none!important}.cc-toolbar-actions{display:flex;gap:12px;margin-left:auto;flex-shrink:0}.cc-toolbar{position:fixed;right:24px;bottom:24px;z-index:150;display:flex;justify-content:flex-end;gap:12px;align-items:center;padding:10px;background:#0f1f3d;border:1px solid #243655;border-radius:12px;box-shadow:0 6px 22px #0f172a40;font-size:13px}.cc-toolbar #cc-count[hidden]{display:none!important}@media(max-width:600px){.cc-toolbar{right:12px;bottom:12px}}.cc-toolbar span,.cc-toolbar strong{color:#fff}.cc-toolbar button,#cc-dialog button{border:1px solid #cbd5e1;border-radius:8px;padding:8px 12px;background:#eff6ff;color:#1d4ed8;cursor:pointer}#cc-dialog{position:fixed;inset:0;margin:auto;width:min(620px,94vw);max-height:90vh;overflow:auto;border:1px solid #dde2ea;border-radius:16px;padding:24px;color:#1a1e2e;background:white;z-index:10000}#cc-dialog::backdrop{background:#0f172a99}#cc-dialog h2{font-size:20px;font-weight:700}#cc-dialog label{display:block;margin:12px 0 4px;font-weight:600}#cc-dialog input,#cc-dialog select,#cc-dialog textarea{width:100%;border:1px solid #cbd5e1;border-radius:8px;padding:9px;background:white;color:#1a1e2e}#cc-dialog small{display:block;font-size:12px;color:#64748b;margin:6px 0}#cc-dialog details{margin:12px 0}#cc-recipients{max-height:140px;overflow:auto;font-size:12px}.cc-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:16px}#cc-feedback{margin-top:12px;white-space:pre-line}#cc-dialog button:disabled,.cc-toolbar button:disabled{opacity:.5;cursor:default}input[type=checkbox].cc-select,input[type=checkbox].cc-all{position:static!important;left:auto!important;opacity:1!important;appearance:auto!important;width:16px!important;height:16px!important;display:inline-block!important;margin-right:10px;cursor:pointer}


.cc-toolbar-actions button[data-cc-open] {
    background: #20365e;
    border-color: #526b94;
    color: #fff;
}
.cc-toolbar-actions button[data-cc-open]:hover { background: #304d7e; }
.cc-toolbar-actions button[data-cc-open]:focus-visible { outline: 2px solid #bfdbfe; outline-offset: 2px; }

#cc-dialog{width:min(940px,calc(100vw - 40px));max-width:none;max-height:calc(100dvh - 48px);padding:0;overflow:hidden;border:1px solid #e2e8f0;border-radius:20px;box-shadow:0 24px 80px #0f172a40;font-family:inherit;text-align:left}
#cc-dialog::backdrop{background:#0f172a80;backdrop-filter:blur(4px)}
#cc-form{display:flex;flex-direction:column;max-height:calc(100dvh - 50px);margin:0}
#cc-dialog .cc-modal-header{display:flex;align-items:center;justify-content:space-between;gap:20px;padding:24px 28px;border-bottom:1px solid #e8edf3;flex-shrink:0}
#cc-dialog .cc-heading{display:flex;align-items:center;gap:14px}
#cc-dialog .cc-heading-icon{display:grid;place-items:center;width:44px;height:44px;border-radius:12px;background:#eff6ff;color:#2563eb;font-size:20px;flex-shrink:0}
#cc-dialog h2{font-size:20px;line-height:1.4;font-weight:700;letter-spacing:-.4px;margin:0;color:#172033}
#cc-dialog .cc-heading p{font-size:12px;color:#8490a2;margin:4px 0 0}
#cc-dialog button{display:inline-flex;align-items:center;justify-content:center;gap:8px;border:1px solid #dce3ec;border-radius:9px;padding:10px 16px;background:white;color:#475569;font:inherit;font-size:12px;font-weight:600;cursor:pointer;transition:background .15s}
#cc-dialog button:hover{background:#f8fafc;border-color:#b6c3d4}
#cc-dialog button:focus-visible,#cc-dialog summary:focus-visible{outline:3px solid #bfdbfe;outline-offset:3px}
#cc-dialog button.cc-icon-button{width:36px;height:36px;padding:0;border-color:transparent;color:#8390a3;background:#f8fafc;flex-shrink:0}
#cc-dialog button.cc-icon-button:hover{background:#eef2f7;color:#334155}
#cc-dialog .cc-modal-body{padding:24px 28px;overflow-y:auto;min-height:0}
#cc-dialog .cc-recipient-card{display:flex;gap:12px;padding:14px 16px;background:#f8fafc;border:1px solid #e8edf3;border-radius:12px;margin-bottom:24px;color:#64748b}
#cc-dialog .cc-recipient-card>i{padding-top:4px}
#cc-dialog .cc-recipient-card>div{min-width:0;flex:1}
#cc-dialog .cc-eyebrow{font-size:10px;letter-spacing:1px;color:#8390a3;font-weight:700;margin:0 0 4px}
#cc-summary{font-size:12px;line-height:1.6;margin:0;color:#475569}
#cc-dialog details{margin:6px 0 0}
#cc-dialog summary{font-size:11px;color:#2563eb;font-weight:600;cursor:pointer}
#cc-recipients{max-height:130px;overflow:auto;font-size:12px;line-height:1.8;padding-left:16px;margin:8px 0 0;overflow-wrap:anywhere}
#cc-dialog .cc-compose-grid{display:grid;grid-template-columns:minmax(0,1fr) 260px;gap:24px}
#cc-dialog .cc-compose-grid:has(#cc-template-panel[hidden]){grid-template-columns:1fr}
#cc-dialog [hidden]{display:none!important}
#cc-dialog label{display:block;margin:0 0 8px;font-size:12px;line-height:1.5;font-weight:600;color:#334155}
#cc-email-fields{margin-bottom:20px}
#cc-dialog input,#cc-dialog select,#cc-dialog textarea{box-sizing:border-box;width:100%;max-width:100%;border:1px solid #dce3ec;border-radius:9px;padding:11px 12px;background:#fff;color:#1e293b;font:inherit;font-size:13px;line-height:1.6;box-shadow:0 1px 2px #0f172a04;outline:none}
#cc-dialog input:focus,#cc-dialog select:focus,#cc-dialog textarea:focus{border-color:#729cf4;box-shadow:0 0 0 3px #eff6ff}
#cc-dialog input::placeholder,#cc-dialog textarea::placeholder{color:#9aa6b6}
#cc-dialog select{padding-right:30px}
#cc-dialog textarea{resize:vertical;min-height:230px;display:block}
#cc-dialog .cc-editor-hint{font-size:11px;line-height:1.8;color:#8490a2;margin-top:10px}
#cc-dialog code{font-size:10px;background:#f1f5f9;color:#536782;border-radius:4px;padding:2px 4px}
#cc-template-panel{align-self:start;background:#f8fafc;border:1px solid #e8edf3;border-radius:12px;padding:18px}
#cc-dialog h3{font-size:13px;font-weight:700;color:#334155;margin:0}
#cc-dialog h3 i{margin-right:5px;color:#64748b}
#cc-dialog .cc-muted{font-size:11px;color:#8490a2;line-height:1.7;margin:10px 0 18px}
#cc-dialog .cc-template-divider{height:1px;background:#e2e8f0;margin:22px 0}
#cc-dialog #cc-save-template{width:100%;margin-top:10px;background:#eff6ff;border-color:#dbe7ff;color:#2563eb}
#cc-dialog small{font-size:10px;color:#94a3b8;line-height:1.7;margin-top:10px}
#cc-feedback:empty{display:none}
#cc-feedback{margin:20px 0 0;padding:12px 14px;border-radius:10px;background:#f1f5f9;font-size:12px;line-height:1.7;color:#475569;overflow-wrap:anywhere}
#cc-dialog .cc-modal-footer{display:flex;justify-content:space-between;align-items:center;gap:18px;padding:18px 28px;border-top:1px solid #e8edf3;background:#fff;flex-shrink:0}
#cc-dialog .cc-delivery-note{font-size:10px;color:#94a3b8;line-height:1.6}
#cc-dialog .cc-actions{display:flex;gap:10px;flex-shrink:0;margin:0}
#cc-dialog #cc-send{background:#2563eb;color:#fff;border-color:#2563eb;box-shadow:0 2px 4px #2563eb20}
#cc-dialog #cc-send:hover{background:#1d4ed8}
@media(max-width:700px){#cc-dialog{width:calc(100vw - 24px);max-height:calc(100dvh - 24px);border-radius:14px}#cc-form{max-height:calc(100dvh - 26px)}#cc-dialog .cc-modal-header,#cc-dialog .cc-modal-body{padding:18px}#cc-dialog .cc-compose-grid{grid-template-columns:1fr;gap:18px}#cc-dialog .cc-modal-footer{padding:16px 18px;flex-direction:column;align-items:stretch;gap:10px}#cc-dialog .cc-actions{justify-content:flex-end}#cc-dialog .cc-delivery-note{order:2}#cc-dialog h2{font-size:18px}#cc-dialog textarea{min-height:180px}}

</style>
@endpush
@push('footer-script')
<script src="{{ asset('js/candidate-communications.js') }}?v={{ filemtime(public_path('js/candidate-communications.js')) }}"></script>
@endpush
@endonce
@endif
