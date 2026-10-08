@extends('saas.platform-layout')
@push('head-script')
<style>
.client-tabs{display:flex;gap:6px;overflow-x:auto;border-bottom:1px solid #e3e7ef;margin:22px 0 24px;padding:0 0 10px}
.client-tabs [role=tab]{background:transparent!important;color:#64748b!important;border:0!important;border-radius:10px;padding:12px 18px;white-space:nowrap;font-weight:600;cursor:pointer}
.client-tabs [role=tab][aria-selected=true]{background:#eaf1ff!important;color:#2563eb!important}
.client-tabs [role=tab]:focus-visible{outline:2px solid #2563eb;outline-offset:2px}
.client-panel[hidden]{display:none!important}.client-summary{display:flex;align-items:center;justify-content:space-between;gap:16px}.client-status{background:#eaf1ff;color:#2563eb;padding:7px 12px;border-radius:999px;font-size:12px;font-weight:600}
.client-panel code{display:block;background:#f6f8fc;border:1px solid #e3e7ef;border-radius:10px;padding:14px;overflow-wrap:anywhere;margin:14px 0}
</style>
<style>
.co-grid{display:grid;grid-template-columns:1.3fr 1fr;gap:22px}.co-grid .card,.co-login.card{border:1px solid #e2e8f0;box-shadow:none;margin:0 0 22px}.co-heading{display:flex;align-items:center;justify-content:space-between;gap:16px}.co-account .co-heading{justify-content:flex-start}.co-heading h2,.co-login h2{margin:0 0 6px;font-size:20px}.co-avatar{width:52px;height:52px;flex-shrink:0;background:#eff6ff;color:#2563eb;border-radius:15px;display:grid;place-items:center;font-size:24px;font-weight:700}.co-muted{color:#64748b;font-size:13px}.co-details{margin:24px 0 0}.co-details>div{display:flex;justify-content:space-between;gap:20px;padding:12px 0;border-top:1px solid #f1f5f9}.co-details dt{color:#64748b;font-size:13px}.co-details dd{margin:0;font-size:13px;font-weight:600;overflow-wrap:anywhere;text-align:right}.co-badge{display:inline-block;padding:5px 12px;border-radius:20px;background:#f1f5f9;color:#475569;font-size:12px;font-weight:600}.co-badge.active,.co-badge.trialing{background:#ecfdf5;color:#047857}.co-badge.suspended,.co-badge.expired,.co-badge.failed{background:#fff1f2;color:#be123c}.co-eyebrow{font-size:10px;letter-spacing:1.2px;color:#64748b;font-weight:700}.co-expiry{display:flex;flex-direction:column;gap:6px;border-top:1px solid #e2e8f0;padding:24px 0;margin-top:22px}.co-expiry strong{font-size:27px;color:#17233b}.co-usage{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:18px;margin-bottom:22px}.co-metric{display:flex;flex-direction:column;gap:10px;background:white;border:1px solid #e2e8f0;border-radius:16px;padding:22px}.co-metric strong{font-size:28px;color:#17233b}.co-footnote{font-size:11px;color:#94a3b8}.co-login p{margin:0 0 20px}.co-copy{display:flex;gap:12px;align-items:center}.co-copy .bs-f-input{flex:1;min-width:0;margin:0}.co-copy button{white-space:nowrap;margin:0}.co-login [role=status]{display:block;margin-top:10px}@media(max-width:1000px){.co-grid{grid-template-columns:1fr}.co-usage{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:550px){.co-usage{grid-template-columns:1fr}.co-copy{align-items:stretch;flex-direction:column}.co-details>div{flex-direction:column;gap:6px}.co-details dd{text-align:left}}
</style>
@endpush
@section('platform-content')
<div class="client-summary"><p class="muted">Manage {{ $tenant->name }}'s account, subscription and communication services.</p><span class="client-status">{{ ucfirst($tenant->status) }}</span></div>
<nav class="client-tabs" role="tablist" aria-label="Client details">
<button type="button" role="tab" id="client-tab-overview" aria-controls="client-panel-overview" aria-selected="true" data-client-tab="overview" tabindex="0">Overview</button>
<button type="button" role="tab" id="client-tab-subscription" aria-controls="client-panel-subscription" aria-selected="false" data-client-tab="subscription" tabindex="-1">Subscription & Plan</button>
<button type="button" role="tab" id="client-tab-telephony" aria-controls="client-panel-telephony" aria-selected="false" data-client-tab="telephony" tabindex="-1">Calling & SMS</button>
<button type="button" role="tab" id="client-tab-history" aria-controls="client-panel-history" aria-selected="false" data-client-tab="history" tabindex="-1">History</button>
<button type="button" role="tab" id="client-tab-account" aria-controls="client-panel-account" aria-selected="false" data-client-tab="account" tabindex="-1">Account Controls</button>
</nav>
<section class="client-panel" role="tabpanel" id="client-panel-overview" aria-labelledby="client-tab-overview">
@php($currentPlan = $tenant->subscription?->plan)
<div class="co-grid">
<div class="card co-account"><div class="co-heading"><span class="co-avatar" aria-hidden="true">{{ mb_substr($tenant->name,0,1) }}</span><div><h2>{{ $tenant->name }}</h2><span class="co-muted">Client account</span></div></div><dl class="co-details"><div><dt>Owner email</dt><dd>{{ $tenant->owner_email }}</dd></div><div><dt>Workspace</dt><dd>{{ $tenant->slug }}</dd></div><div><dt>Created</dt><dd>{{ $tenant->created_at?->format('d M Y') ?? 'Unavailable' }}</dd></div><div><dt>Account status</dt><dd><span class="co-badge {{ $tenant->status }}">{{ ucfirst($tenant->status) }}</span></dd></div></dl></div>
<div class="card co-subscription"><div class="co-heading"><div><span class="co-eyebrow">CURRENT SUBSCRIPTION</span><h2>{{ $currentPlan?->name ?? 'Unavailable' }}</h2></div><span class="co-badge {{ $tenant->subscription?->status }}">{{ ucfirst($tenant->subscription?->status ?? 'Unavailable') }}</span></div><div class="co-expiry"><span class="co-muted">Subscription expiry</span><strong>{{ $tenant->subscription?->expires_at?->format('d M Y') ?? 'No expiry' }}</strong>@if($tenant->subscription?->expires_at)<small class="co-muted">{{ $tenant->subscription->expires_at->format('H:i') }} UTC</small>@endif</div><button type="button" class="secondary" onclick="document.getElementById('client-tab-subscription').click()">Manage subscription &rarr;</button></div>
</div>
@if($usage)
<div class="co-usage">@foreach([['Team members',$usage['users'],'users'],['Jobs',$usage['jobs'],'jobs'],['Candidates',$usage['candidates'],'candidates'],['Local storage',number_format($usage['storage_bytes']/1048576,1).' MB','storage']] as $metric)<div class="co-metric"><span class="co-muted">{{ $metric[0] }}</span><strong>{{ $metric[1] }}</strong><span class="co-footnote">{{ $metric[2]==='storage' ? 'Uploaded local files' : 'Workspace total' }}</span></div>@endforeach</div>
@else<div class="notice error">Usage could not be loaded. Check the workspace database configuration.</div>@endif
<div class="card co-login"><div><h2>Client login link</h2><p class="co-muted">Share this link with the client to access their workspace.</p></div><div class="co-copy"><input id="client-login-link" class="bs-f-input" aria-label="Client login URL" readonly value="{{ route('saas.workspace',$tenant->slug) }}"><button type="button" id="copy-client-login">Copy link</button></div><span id="copy-client-status" class="co-muted" role="status" aria-live="polite"></span></div>
</section>

<section class="client-panel" role="tabpanel" id="client-panel-subscription" aria-labelledby="client-tab-subscription" hidden>@if($tenant->subscription)<div class="grid"><div class="card"><h2>Extend subscription manually</h2><p class="muted">Add days from the current expiry (or today if expired), or set a later expiry date. The reason is recorded.</p><form method="post" action="{{ route('superadmin.tenants.extend', $tenant) }}">@csrf<input type="hidden" name="client_tab" value="subscription"><label class="bs-set-lbl">Add days</label><input class="bs-f-input" name="days" type="number" min="1" max="3650" value="{{ old('days') }}"><label class="bs-set-lbl">Or set expiry date</label><input class="bs-f-input" name="expires_at" type="date" value="{{ old('expires_at') }}"><label class="bs-set-lbl">Optional plan change</label><select class="bs-f-sel" name="plan_id"><option value="">Keep current plan</option>@foreach($plans as $plan)<option value="{{ $plan->id }}">{{ $plan->name }}</option>@endforeach</select><label class="bs-set-lbl">Reason</label><textarea class="bs-f-textarea" name="reason" required maxlength="1000"></textarea><button>Extend subscription</button></form></div>
<div class="card"><h2>Change plan</h2><p class="muted">Changing the plan preserves the current expiry.</p><form method="post" action="{{ route('superadmin.tenants.plan', $tenant) }}">@csrf<input type="hidden" name="client_tab" value="subscription"><label class="bs-set-lbl">Plan</label><select class="bs-f-sel" name="plan_id" required>@foreach($plans as $plan)<option value="{{ $plan->id }}" @selected($tenant->subscription->plan_id === $plan->id)>{{ $plan->name }}</option>@endforeach</select><label class="bs-set-lbl">Reason</label><textarea class="bs-f-textarea" name="reason" required maxlength="1000"></textarea><button>Update plan</button></form></div></div>@endif
</section>
<section class="client-panel" role="tabpanel" id="client-panel-telephony" aria-labelledby="client-tab-telephony" hidden>@php($telephony = json_decode(\App\Saas\PlatformSetting::valueFor('telephony.'.$tenant->id, '{}'), true) ?: [])
<div class="card"><h2>Client calling and SMS</h2><form method="post" action="{{ route('superadmin.tenants.telephony',$tenant) }}">@csrf<input type="hidden" name="client_tab" value="telephony">
<label class="bs-set-lbl">Browser calling</label><select class="bs-f-sel" name="calls_enabled"><option value="0">Disabled</option><option value="1" @selected(!empty($telephony['calls_enabled']))>Enabled</option></select>
<label class="bs-set-lbl">SIP connection ID</label><input class="bs-f-input" name="connection_id" value="{{ $telephony['connection_id'] ?? '' }}">
<label class="bs-set-lbl">Calling number</label><input class="bs-f-input" name="voice_number" placeholder="+14165551234" value="{{ $telephony['voice_number'] ?? '' }}">
<label class="bs-set-lbl">Candidate SMS</label><select class="bs-f-sel" name="sms_enabled"><option value="0">Disabled</option><option value="1" @selected(!empty($telephony['sms_enabled']))>Enabled</option></select>
<label class="bs-set-lbl">SMS sender number</label><input class="bs-f-input" name="sms_number" placeholder="+14165551234" value="{{ $telephony['sms_number'] ?? '' }}">
<p class="muted">Assign a dedicated SMS number to each client. Configure its Telnyx messaging profile webhook with this URL:</p><code>{{ url('/telnyx-webhook').'?workspace='.rawurlencode($tenant->slug) }}</code>
<button>Save client calling and SMS</button></form></div>
</section>
<section class="client-panel" role="tabpanel" id="client-panel-history" aria-labelledby="client-tab-history" hidden><div class="card"><h2>Audit history</h2>@include('saas.audit', ['logs'=>$logs])</div>
</section>
<section class="client-panel" role="tabpanel" id="client-panel-account" aria-labelledby="client-tab-account" hidden>@if(!in_array($tenant->status,['failed','provisioning']))<div class="grid"><div class="card"><h2>Workspace access</h2><form method="post" action="{{ route('superadmin.tenants.update', $tenant) }}">@csrf<input type="hidden" name="client_tab" value="account"> @method('PUT')<label class="bs-set-lbl">Status</label><select class="bs-f-sel" name="status"><option value="active" @selected($tenant->status==='active')>Active</option><option value="suspended" @selected($tenant->status==='suspended')>Suspended</option></select><label class="bs-set-lbl">Reason</label><textarea class="bs-f-textarea" name="reason" required maxlength="1000"></textarea><button>Update access</button></form></div><div class="card"><h2>Approve owner email</h2><p class="muted">Use only after independently confirming the owner's identity if email delivery is unavailable.</p><form method="post" action="{{ route('superadmin.tenants.verify-owner',$tenant) }}">@csrf<input type="hidden" name="client_tab" value="account"><label class="bs-set-lbl">Verification reason</label><textarea class="bs-f-textarea" name="reason" required maxlength="1000"></textarea><button class="secondary">Approve owner email</button></form></div></div>@endif
@if($tenant->slug !== 'main' && $tenant->status !== 'provisioning')
<div class="card" style="border-color:#fecdd3"><h2 style="color:#be123c">Delete client</h2><p class="muted">Remove this client from the platform and revoke their login and Jobs API access. The client database and uploaded files remain on the server for recovery. This action removes the subscription and cannot be undone from this page.</p><form method="post" action="{{ route('superadmin.tenants.destroy',$tenant) }}" onsubmit="return window.confirm('Delete this client and revoke their access? The database and files will be retained for recovery.');">@csrf<input type="hidden" name="client_tab" value="account"> @method('DELETE')<button type="submit" style="background:#be123c">Delete client</button></form></div>
@endif
</section>
<script>
(function () {
    document.getElementById('copy-client-login').addEventListener('click', async function () {
        var input = document.getElementById('client-login-link');
        var status = document.getElementById('copy-client-status');
        try { await navigator.clipboard.writeText(input.value); status.textContent = 'Login link copied.'; }
        catch (e) { input.focus(); input.select(); status.textContent = 'Select and copy the login link.'; }
    });
    var tabs = Array.from(document.querySelectorAll('[data-client-tab]'));
    var storageKey = 'client-detail-tab:' + window.location.pathname;
    function selectTab(name, focus) {
        if (!tabs.some(function (tab) { return tab.dataset.clientTab === name; })) name = 'overview';
        tabs.forEach(function (tab) {
            var selected = tab.dataset.clientTab === name;
            tab.setAttribute('aria-selected', selected ? 'true' : 'false');
            tab.tabIndex = selected ? 0 : -1;
            document.getElementById(tab.getAttribute('aria-controls')).hidden = !selected;
            if (selected && focus) tab.focus();
        });
        try { sessionStorage.setItem(storageKey, name); } catch (e) {}
    }
    tabs.forEach(function (tab, index) {
        tab.addEventListener('click', function () { selectTab(tab.dataset.clientTab, false); });
        tab.addEventListener('keydown', function (event) {
            var next = index;
            if (event.key === 'ArrowRight') next = (index + 1) % tabs.length;
            else if (event.key === 'ArrowLeft') next = (index + tabs.length - 1) % tabs.length;
            else if (event.key === 'Home') next = 0;
            else if (event.key === 'End') next = tabs.length - 1;
            else return;
            event.preventDefault(); selectTab(tabs[next].dataset.clientTab, true);
        });
    });
    var initial = @json(old('client_tab'));
    if (!initial) { try { initial = sessionStorage.getItem(storageKey); } catch (e) {} }
    selectTab(initial || 'overview', false);
})();
</script>
@endsection
