<div class="grid">
    <div class="card">
        <h2>Microsoft app settings</h2>
        <p class="muted">Set the Microsoft application used by {{ $tenant->name }} to receive candidate email replies. These settings apply to this client.</p>
        @if($microsoftApp['has_override'])
            <p class="muted">{{ $microsoftApp['available'] ? 'Microsoft app settings are saved for this client.' : 'Update this client’s Microsoft app settings to enable Outlook.' }}</p>
        @elseif($microsoftApp['available'])
            <p class="muted">This client currently uses the platform default. Save an application below to use their own Microsoft app.</p>
        @endif
        <form method="post" action="{{ route('superadmin.tenants.microsoft-inbox', $tenant) }}" autocomplete="off">
            @csrf
            <input type="hidden" name="client_tab" value="outlook">
            <label class="bs-set-lbl" for="microsoft-client-id">Application (client) ID</label>
            <input class="bs-f-input" id="microsoft-client-id" name="microsoft_client_id" value="{{ old('microsoft_client_id', $microsoftApp['client_id']) }}" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" maxlength="36" required autocomplete="off" spellcheck="false">
            <label class="bs-set-lbl" for="microsoft-client-secret">Microsoft client secret</label>
            <input class="bs-f-input" id="microsoft-client-secret" name="microsoft_client_secret" type="password" placeholder="{{ $microsoftApp['has_secret'] ? 'Leave blank to keep the saved secret' : 'Enter the client secret value' }}" maxlength="8192" autocomplete="new-password" @required(!$microsoftApp['has_secret'])>
            <p class="muted">{{ $microsoftApp['has_secret'] ? 'Secret saved. Leave it blank to keep it, or enter a new value to replace it.' : 'Copy the secret Value from the Microsoft app’s Certificates & secrets page.' }}</p>
            <p class="muted">After changing the application ID, enter its secret and reconnect the client’s Outlook mailbox.</p>
            <button type="submit">Save Microsoft app settings</button>
        </form>
    </div>
    <div class="card">
        <h2>Outlook inbox connection</h2>
        @if($outlookMailbox)
            <p><strong>Connected mailbox:</strong> {{ $outlookMailbox->email }}</p>
            <p class="muted">{{ $outlookMailbox->last_synced_at ? 'Last inbox check: '.$outlookMailbox->last_synced_at->utc()->format('M j, Y H:i').' UTC' : 'Waiting for the first scheduled inbox sync.' }}</p>
            @if($outlookMailbox->sync_error)<div class="notice error">{{ $outlookMailbox->sync_error }}</div>@endif
        @else
            <p class="muted">No Outlook mailbox is connected for this client.</p>
        @endif
        <p class="muted">Connect this client's mailbox here. Sign in with the mailbox that receives candidate replies and approve Microsoft's inbox permissions.</p>
        @if(!in_array($tenant->status, ['failed', 'provisioning']))
        <form method="post" action="{{ route('superadmin.tenants.outlook.connect', $tenant) }}">@csrf
            <button type="submit" @disabled(!$microsoftApp['available'])>{{ $outlookMailbox ? 'Reconnect Outlook' : 'Connect Outlook' }}</button>
        </form>
        @if($outlookMailbox)
        <form method="post" action="{{ route('superadmin.tenants.outlook.disconnect', $tenant) }}" style="margin-top:12px">@csrf @method('DELETE')
            <button type="submit" class="secondary">Disconnect Outlook</button>
        </form>
        @endif
        @endif
        <label class="bs-set-lbl" for="microsoft-callback-url">Microsoft Web redirect URI</label>
        <input class="bs-f-input" id="microsoft-callback-url" readonly value="{{ app(\App\Services\MicrosoftReplyInbox::class)->redirectUri() }}">
        <p class="muted">Add this redirect URI to the Microsoft app. Use delegated Microsoft Graph permissions <strong>User.Read</strong> and <strong>Mail.Read</strong>, and enable sign-in for multiple organizations and personal Microsoft accounts.</p>
    </div>
</div>
