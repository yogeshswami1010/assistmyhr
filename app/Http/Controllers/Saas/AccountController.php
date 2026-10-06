<?php

namespace App\Http\Controllers\Saas;

use App\Saas\AuditLog;
use App\Saas\Plan;
use App\Saas\QuotaService;
use App\Saas\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    public function integrations(Request $request)
    {
        abort_unless($request->user()->cans('manage_settings'), 403);
        return view('saas.integrations', ['settings' => \Illuminate\Support\Facades\DB::table('tenant_service_settings')->first()]);
    }

    public function saveIntegrations(Request $request)
    {
        abort_unless($request->user()->cans('manage_settings'), 403);
        $data = $request->validate(['imap_host' => ['nullable', 'string', 'max:253', 'regex:/\A[a-zA-Z0-9.-]+\z/'], 'imap_port' => ['required', 'integer', 'min:1', 'max:65535']]);
        $table = \Illuminate\Support\Facades\DB::table('tenant_service_settings');
        $id = $table->value('id');
        $id ? $table->where('id', $id)->update($data + ['updated_at' => now()]) : $table->insert($data + ['created_at' => now(), 'updated_at' => now()]);
        return back()->with('status', 'Mailbox settings saved. SMTP username and password are taken from your ATS SMTP settings.');
    }
    public function subscription()
    {
        $tenant = app(TenantContext::class)->current();
        abort_unless($tenant, 404);
        return view('saas.subscription', ['tenant' => $tenant, 'usage' => app(QuotaService::class)->usage(), 'plans' => Plan::where('enabled', true)->where('public', true)->get()]);
    }

    public function requestPlan(Request $request)
    {
        abort_unless($request->user()->cans('manage_settings'), 403);
        $data = $request->validate(['plan_id' => ['required', Rule::exists('saas_landlord.saas_plans', 'id')->where('enabled', true)->where('public', true)], 'reason' => ['required', 'string', 'max:1000']]);
        AuditLog::create([
            'tenant_id' => app(TenantContext::class)->current()->id, 'action' => 'subscription.change_requested',
            'reason' => $data['reason'], 'after' => ['plan_id' => $data['plan_id'], 'requested_by_user_id' => $request->user()->id],
        ]);
        return back()->with('status', 'Your plan request has been recorded for the platform administrator.');
    }
}
