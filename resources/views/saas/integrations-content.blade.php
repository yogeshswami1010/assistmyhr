<section class="card mailbox-card mailbox-layout" aria-labelledby="mailbox-title">
    <div>
        <h2 id="mailbox-title">Candidate reply inbox</h2>
        <p class="muted">Connect the mailbox that receives replies to your recruitment emails. Candidate replies appear automatically in Email Conversation after each scheduled sync.</p>
        <div class="integration-note">For Outlook and Microsoft 365, use <strong>Connect Outlook</strong> and sign in with your company mailbox. Microsoft requires this sign-in to read incoming emails. Each workspace connects its own mailbox.</div>
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

    </div>
</section>
