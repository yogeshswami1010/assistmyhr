<style>
.subscription-page { background:#eef2f8; padding:20px; border-radius:16px; }
.subscription-page h1 { font-size:25px; font-weight:800; margin:0; }
.subscription-page h1 span { color:#2563eb; }
.subscription-page h2 { font-size:17px; margin:0 0 5px; }
.subscription-page p { margin:0; }
.subscription-header,.subscription-summary,.subscription-renewal { display:flex; align-items:center; gap:16px; flex-wrap:wrap; }
.subscription-header { justify-content:space-between; }
.subscription-summary { background:#10264b; color:#fff; padding:13px 16px; border-radius:10px; margin:16px 0; }
.subscription-summary .summary-chip { background:#eff6ff; color:#2047a0; border-radius:20px; padding:4px 10px; font-size:11px; font-weight:800; }
.subscription-summary .summary-active { background:#dcfce7; color:#166534; }
.subscription-summary .summary-paused { background:#ffe4e6; color:#9f1239; }
.subscription-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:14px; margin-bottom:14px; }
.subscription-stack { display:grid; gap:14px; }
.subscription-page .subscription-card { background:#fff; padding:16px; border:1px solid #e1e7f0; border-radius:12px; min-width:0; }
.subscription-page .billing-option { display:flex; align-items:center; gap:12px; margin:10px 0 0; padding:12px; border:1px solid #dce4ef; border-radius:10px; cursor:pointer; color:#1a1e2e; }
.subscription-page .billing-option:has(input:checked) { background:#edf4ff; border-color:#2563eb; }
.subscription-page .billing-option input { width:auto; margin:0; accent-color:#2563eb; }
.subscription-page .billing-price { margin-inline-start:auto; text-align:end; font-size:15px; }
.subscription-page .billing-note { background:#edf4ff; padding:9px 12px; border-radius:8px; color:#2047a0; margin-top:12px; }
.subscription-metrics { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:10px; margin-top:12px; }
.subscription-metrics strong,.subscription-metrics small { display:block; }
.subscription-metrics strong { font-size:17px; }
.subscription-icon { display:inline-flex; padding:4px 7px; border-radius:8px; background:#edf4ff; color:#2563eb; margin-bottom:4px; }
.subscription-features { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:7px; list-style:none; padding:0; margin:10px 0 0; }
.subscription-features li::before { content:'✓'; color:#15803d; margin-inline-end:6px; }
.subscription-renewal { margin-bottom:14px; }
.subscription-renewal > div:first-child { flex:1 1 220px; }
.subscription-renewal .renewal-message { flex:2 1 280px; }
.subscription-page .renewal-message label { margin:0 0 5px; }
.subscription-page .renewal-message textarea { min-height:64px; resize:vertical; }
.subscription-page .button { margin:0; }
.subscription-page .recent-request { margin-top:10px; padding-top:10px; border-top:1px solid #e1e7f0; white-space:pre-wrap; overflow-wrap:anywhere; }
@media (max-width:800px) { .subscription-grid { grid-template-columns:1fr; } }
@media (max-width:480px) { .subscription-metrics,.subscription-features { grid-template-columns:repeat(2,minmax(0,1fr)); } }
</style>

@php
    $subscription = $tenant->subscription;
    $plan = $subscription?->plan;
    $hasAccess = $tenant->hasAccess();
    $canRequest = auth()->user()->cans('manage_settings') && $plans->isNotEmpty();
    $selectedPlan = old('plan_id', $plans->firstWhere('id', $plan?->id)?->id ?? $plans->first()?->id);
@endphp

<header class="subscription-header">
    <div>
        <p class="muted">Manage your plan, review usage and request a renewal or plan change.</p>
        <h1>Workspace: <span>{{ $tenant->name }}</span></h1>
    </div>
    @if($hasAccess)
        <a class="button" href="{{ tenant_route('admin.dashboard') }}">Open ATS →</a>
    @endif
</header>

<section class="subscription-summary" aria-label="Current plan">
    <span class="summary-chip">CURRENT PLAN</span>
    <strong>{{ $plan?->name ?? 'No plan' }}</strong>
    <span class="summary-chip {{ $hasAccess ? 'summary-active' : 'summary-paused' }}">{{ ucfirst($subscription?->status ?? 'Unavailable') }}</span>
    <span>{{ $subscription?->expires_at ? 'Expires '.$subscription->expires_at->format('d M Y') : 'No expiry' }}</span>
</section>
@if(!$hasAccess)
    <div class="notice error" role="status">Your subscription has expired or access is paused. Select a plan below and send a renewal request to restore access after administrator approval.</div>
@endif

<div class="subscription-grid">
    <section class="subscription-card" aria-labelledby="billing-title">
        <h2 id="billing-title">Choose your billing</h2>
        <p class="muted">Select a plan and duration for your renewal or change request.</p>
        @forelse($plans as $availablePlan)
            <label class="billing-option">
                <input type="radio" name="plan_id" value="{{ $availablePlan->id }}" @checked((string) $selectedPlan === (string) $availablePlan->id)
                    data-unit-price="{{ $availablePlan->price }}" data-currency="{{ $availablePlan->currency }}" data-duration="{{ $availablePlan->durationDays() }}"
                    @if($canRequest) form="subscription-request" required @else disabled @endif>
                <strong>{{ $availablePlan->name }}</strong>
                <strong class="billing-price">{{ $availablePlan->currency }} {{ number_format($availablePlan->price, 2) }}<small> per user / {{ $availablePlan->durationDays() }} days</small></strong>
            </label>
        @empty
            <p class="billing-note">No public plans are available. Contact the platform administrator to renew.</p>
        @endforelse
    </section>

    <div class="subscription-stack">
        <section class="subscription-card" aria-labelledby="usage-title">
            <h2 id="usage-title">Workspace usage</h2>
            <p class="muted">Your current usage against this plan's limits.</p>
            <div class="subscription-metrics">
                @foreach([['Team members', $usage['users'], $subscription?->userLimit(), '', 'fa-users'], ['Stored jobs', $usage['jobs'], $plan?->max_jobs, '', 'fa-briefcase'], ['Candidates', $usage['candidates'], $plan?->max_candidates, '', 'fa-user'], ['Storage', $usage['storage_bytes'] / 1048576, $plan?->storage_mb, ' MB', 'fa-database']] as [$label, $used, $limit, $unit, $icon])
                    <div>
                        <span class="subscription-icon" aria-hidden="true"><i class="fa {{ $icon }}"></i></span>
                        <div>{{ $label }}</div>
                        <strong>{{ number_format($used, $unit ? 1 : 0) }}{{ $unit }}</strong>
                        <small class="muted">/ {{ $limit !== null ? number_format($limit).$unit : 'Unlimited' }}</small>
                    </div>
                @endforeach
            </div>
        </section>
        <section class="subscription-card" aria-labelledby="subscription-features">
            <h2 id="subscription-features">Workspace features</h2>
            <ul class="subscription-features">
                @foreach(['Applicant Tracking System (ATS)', 'Candidate Marketing & Sourcing', 'Candidate Database & CRM', 'AI Search & Matching', 'Job Posting & Distribution', 'Team Collaboration'] as $feature)
                    <li>{{ $feature }}</li>
                @endforeach
            </ul>
        </section>
    </div>
</div>

@if($canRequest)
    <form id="subscription-request" class="subscription-card subscription-renewal" method="post" action="{{ tenant_route('saas.request-plan') }}" aria-labelledby="renewal-title">
        @csrf
        <div>
            <h2 id="renewal-title">Request a renewal or plan change</h2>
            <p class="muted">Select your plan and total user seats, including the owner. Super admin reviews your request. Access and extra seats are enabled after approval; no payment is collected here.</p>
            <label for="seat-count">Total user seats</label>
            <input id="seat-count" name="seat_count" type="number" min="{{ max(1, $usage['users']) }}" max="100000" required value="{{ old('seat_count', max(1, $usage['users'], $subscription?->seat_count ?? 0)) }}">
            <p id="subscription-total" role="status" aria-live="polite"></p>
        </div>
        <div class="renewal-message">
            <label for="reason">Describe your request</label>
            <textarea id="reason" name="reason" required maxlength="1000" placeholder="Renew my subscription, change plans, add users…">{{ old('reason') }}</textarea>
        </div>
        <button class="button" type="submit">{{ $hasAccess ? 'Send request →' : 'Request renewal →' }}</button>
    </form>
@endif

<script>
(() => {
    const seats = document.getElementById('seat-count');
    const total = document.getElementById('subscription-total');
    if (!seats || !total) return;
    const update = () => {
        const plan = document.querySelector('input[name="plan_id"]:checked');
        if (!plan || !seats.validity.valid) { total.textContent = 'Choose a plan and a valid number of seats.'; return; }
        const amount = Number(plan.dataset.unitPrice) * Number(seats.value);
        total.textContent = `${seats.value} users × ${plan.dataset.currency} ${Number(plan.dataset.unitPrice).toFixed(2)} = ${plan.dataset.currency} ${amount.toFixed(2)} per ${plan.dataset.duration} days. Pending super admin approval.`;
    };
    seats.addEventListener('input', update);
    document.querySelectorAll('input[name="plan_id"]').forEach(input => input.addEventListener('change', update));
    update();
})();
</script>

<section class="subscription-card">
    <label for="company-login">Your company login link</label>
    <input id="company-login" readonly value="{{ route('saas.workspace', $tenant->slug) }}">
    @if(isset($requests) && $requests->isNotEmpty())
        <h2 class="mt-4">Your recent requests</h2>
        @foreach($requests as $sent)
            <div class="recent-request"><small>{{ $sent->created_at }} UTC · Sent@if(isset($sent->after['duration_days'])) · {{ $sent->after['duration_days'] }} days@endif</small>@if(isset($sent->after['seat_count']))<p>{{ $sent->after['seat_count'] }} users · {{ $sent->after['currency'] ?? '' }} {{ $sent->after['total_price'] ?? '' }} total requested</p>@endif<p>{{ $sent->reason }}</p></div>
        @endforeach
    @endif
</section>
