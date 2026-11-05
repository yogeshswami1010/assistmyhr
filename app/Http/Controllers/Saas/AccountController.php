<?php

namespace App\Http\Controllers\Saas;

use App\Saas\AuditLog;
use App\Saas\Plan;
use App\Saas\QuotaService;
use App\Saas\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class AccountController extends Controller
{
    public function subscription()
{
    $tenant = app(TenantContext::class)->current();

    abort_unless($tenant, 404);

    $plans = Plan::where('enabled', true)
        ->where('public', true)
        ->orderBy('price', 'asc')
        ->get();

    return view('saas.subscription', [
        'tenant' => $tenant,
        'subscription' => $tenant->subscription,
        'requests' => AuditLog::where('tenant_id', $tenant->id)
            ->where('action', 'subscription.change_requested')
            ->latest('id')
            ->limit(10)
            ->get(),
        'usage' => app(QuotaService::class)->usage(),
        'plans' => $plans,
    ]);
}

    public function requestPlan(Request $request)
    {
        abort_unless($request->user()->cans('manage_settings'), 403);
        $data = $request->validate([
            'plan_id' => ['required', Rule::exists('saas_landlord.saas_plans', 'id')->where('enabled', true)->where('public', true)],
            'seat_count' => ['required', 'integer', 'min:'.max(1, DB::table('users')->count()), 'max:100000'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);
        $plan = Plan::findOrFail($data['plan_id']);
        AuditLog::create([
            'tenant_id' => app(TenantContext::class)->current()->id, 'action' => 'subscription.change_requested',
            'reason' => $data['reason'], 'after' => [
                'plan_id' => $data['plan_id'], 'plan_name' => $plan->name, 'duration_days' => $plan->durationDays(),
                'seat_count' => (int) $data['seat_count'], 'unit_price' => $plan->price,
                'total_price' => number_format((float) $plan->price * (int) $data['seat_count'], 2, '.', ''),
                'currency' => $plan->currency, 'requested_by_user_id' => $request->user()->id,
            ],
        ]);
        return redirect(tenant_route('saas.subscription'))->with('status', 'Your plan request has been sent to the platform administrator.');
    }
}
