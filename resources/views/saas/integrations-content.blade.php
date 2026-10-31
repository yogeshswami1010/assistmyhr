<div class="integration-grid">

    <section class="card">
        <span class="integration-mark" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m12 3 2.5 6.5L21 12l-6.5 2.5L12 21l-2.5-6.5L3 12l6.5-2.5Z"/></svg></span>
        <h2>AI services</h2>
        <p class="muted">Manage the AI API keys used by your workspace in your company's ATS settings.</p>
        <a class="button secondary" href="{{ tenant_route('admin.ai-settings.index') }}">Configure AI services →</a>
    </section>
</div>
<section class="card mailbox-card mailbox-layout" aria-labelledby="mailbox-title">
    <div>
        <h2 id="mailbox-title">Candidate reply inbox</h2>
        <p class="muted">Connect the mailbox that receives replies to your recruitment emails. Candidate replies appear automatically in Email Conversation after each scheduled sync.</p>
        <div class="integration-note">For Outlook and Microsoft 365, use <strong>Connect Outlook</strong> and sign in with your company mailbox. Microsoft requires this sign-in to read incoming emails. Each workspace connects its own mailbox.</div>
        <div class="integration-note">For Gmail, Zoho and other IMAP providers, use the IMAP settings. Your platform administrator manages the email credentials, which must also allow IMAP access.</div>
    </div>
    <div>
        <h3 style="font-size:16px;margin:0 0 12px">Outlook / Microsoft 365</h3>
        @if($outlook)
            <p role="status"><strong>Connected:</strong> {{ $outlook->email }}</p>
            <p class="hint">{{ $outlook->last_synced_at ? 'Last inbox check: '.$outlook->last_synced_at->utc()->format('M j, Y H:i').' UTC' : 'Waiting for the first scheduled inbox sync.' }}</p>
            @if($outlook->sync_error)<p class="field-error" role="alert">{{ $outlook->sync_error }}</p>@endif
        @else
            <p class="hint">Sign in with the mailbox that receives candidate replies. Entering an Outlook IMAP host and password alone will not connect the inbox.</p>
        @endif
        <div style="display:flex;gap:12px;flex-wrap:wrap;margin:16px 0">
            <form method="post" action="{{ tenant_route('saas.microsoft.connect') }}">@csrf
                <button type="submit" @disabled(!$outlookAvailable)>{{ $outlook ? 'Reconnect Outlook' : 'Connect Outlook' }}</button>
            </form>
            @if($outlook)
                <form method="post" action="{{ tenant_route('saas.microsoft.disconnect') }}">@csrf @method('DELETE')
                    <button type="submit" class="button secondary">Disconnect Outlook</button>
                </form>
            @endif
        </div>
        @unless($outlookAvailable)<p class="hint">Microsoft inbox connection needs platform setup. Contact the platform administrator to enable it.</p>@endunless
        <p class="hint">Outlook replies sync every minute while the server scheduler is running. Connecting Outlook keeps your outgoing email setup.</p>
        <details style="margin-top:24px" @if(!$outlook) open @endif>
            <summary style="cursor:pointer;font-weight:600;margin-bottom:16px">Gmail, Zoho or other IMAP inbox</summary>
            @if($outlook)<p class="hint" style="margin-bottom:12px">Outlook is connected. IMAP settings are saved for use after disconnecting Outlook.</p>@endif
            <form method="post" action="{{ tenant_route('saas.integrations.save') }}">@csrf
                <div class="fields">
                    <div>
                        <label for="imap_host" class="bs-set-lbl">IMAP host</label>
                        <input class="bs-f-input" id="imap_host" name="imap_host" value="{{ old('imap_host',$settings?->imap_host) }}" placeholder="imap.example.com" maxlength="253" autocomplete="off" spellcheck="false" aria-describedby="imap-host-hint" @error('imap_host') aria-invalid="true" @enderror>
                        <p class="hint" id="imap-host-hint">Leave blank for automatic Zoho / Gmail detection. For Outlook, use Connect Outlook above.</p>
                        @error('imap_host')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="imap_port" class="bs-set-lbl">IMAP TLS port</label>
                        <input class="bs-f-input" id="imap_port" name="imap_port" type="number" value="{{ old('imap_port',$settings?->imap_port ?? 993) }}" min="1" max="65535" required aria-describedby="imap-port-hint" @error('imap_port') aria-invalid="true" @enderror>
                        <p class="hint" id="imap-port-hint">Use the TLS port supplied by your provider.</p>
                        @error('imap_port')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                </div>
                <button type="submit">Save mailbox settings</button>
                <p class="hint" style="margin-top:14px">IMAP replies sync every minute while the server scheduler is running and PHP IMAP is installed. Enable IMAP access in your mailbox account.</p>
            </form>
        </details>
    </div>
</section>
