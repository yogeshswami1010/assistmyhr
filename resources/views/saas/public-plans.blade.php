<style>
.published-plans{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,320px),1fr));gap:24px;max-width:1100px;margin:0 auto}
.published-plans .pricing-plan{width:100%;max-width:none;min-width:0;margin:0;display:flex;flex-direction:column;box-sizing:border-box}
.published-plans .pricing-price{padding:24px 0;display:block}
.published-plans .pricing-price>strong{display:block;overflow-wrap:anywhere}
.published-plan-limits{list-style:none;padding:0;margin:0 0 24px;flex:1}
.published-plan-limits li{display:flex;justify-content:space-between;gap:16px;padding:10px 0;border-bottom:1px solid #e6edf8;color:#355780}
.published-plans-empty{text-align:center;padding:30px}
.published-trial{text-align:center;margin:24px auto;max-width:1100px;color:#355780}
</style>
<div class="published-plans">
@forelse($plans as $publishedPlan)
    <article class="pricing-plan" aria-labelledby="published-plan-{{ $publishedPlan->id }}">
        <div class="pricing-plan-intro"><h3 id="published-plan-{{ $publishedPlan->id }}">{{ $publishedPlan->name }}</h3></div>
        <div class="pricing-price">
            <strong>{{ $publishedPlan->currency }} {{ number_format($publishedPlan->price, 2) }}</strong>
            <div><span>per user / {{ $publishedPlan->durationDays() }} days</span></div>
        </div>
        <ul class="published-plan-limits">
            @foreach(['max_jobs'=>'Stored jobs','max_candidates'=>'Candidates','storage_mb'=>'Storage'] as $field=>$label)
                <li><span>{{ $label }}</span><strong>{{ $publishedPlan->$field === null ? 'Unlimited' : number_format($publishedPlan->$field).($field === 'storage_mb' ? ' MB' : '') }}</strong></li>
            @endforeach
        </ul>
        <a class="button pricing-trial" href="{{ route('register') }}">Start your {{ $pricing['trial_days'] }}-day free trial →</a>
    </article>
@empty
    <p class="published-plans-empty">Plans are being updated. <a href="/#contact">Contact our team for pricing.</a></p>
@endforelse
</div>
<p class="published-trial">{{ $pricing['trial_days'] }}-day free trial · Up to {{ \App\Saas\Subscription::TRIAL_USER_LIMIT }} users, including the account owner · No credit card required</p>
