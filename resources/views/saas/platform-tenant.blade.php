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
<section class="client-panel" role="tabpanel" id="client-panel-overview" aria-labelledby="client-tab-overview"><p class="muted">{{ $tenant->owner_email }} · {{ $tenant->slug }} · Created {{ $tenant->created_at?->format('d M Y') }}</p><div class="card"><p>Workspace: {{ $tenant->status }} · Plan: {{ $tenant->subscription?->plan?->name ?? 'Unavailable' }} · Subscription: {{ $tenant->subscription?->status ?? 'Unavailable' }}<br>Expiry: {{ $tenant->subscription?->expires_at ? $tenant->subscription->expires_at->format('d M Y H:i').' UTC' : 'No expiry' }}</p>
@if($usage)<p>{{ $usage['users'] }} users · {{ $usage['jobs'] }} jobs · {{ $usage['candidates'] }} candidates · {{ number_format($usage['storage_bytes']/1048576, 1) }} MB local files</p>@else<p class="notice error">Usage could not be loaded. Check the workspace database configuration.</p>@endif<label class="bs-set-lbl">Client login URL</label><input class="bs-f-input" readonly value="{{ route('saas.workspace', $tenant->slug) }}"></div>
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
