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
        return view('saas.integrations', [
            'outlook' => \Illuminate\Support\Facades\Schema::hasTable('candidate_reply_mailboxes') ? \App\CandidateReplyMailbox::find(1) : null,
            'outlookAvailable' => app(\App\Services\MicrosoftReplyInbox::class)->configured()
                && \Illuminate\Support\Facades\Schema::hasTable('candidate_reply_mailboxes'),
        ]);
    }

    public function subscription()
    {
        $tenant = app(TenantContext::class)->current();
        abort_unless($tenant, 404);
        return view('saas.subscription', ['tenant' => $tenant, 'requests' => AuditLog::where('tenant_id',  $tenant->id)->where('action', 'subscription.change_requested')->latest('id')->limit(10)->get(), 'usage' => app(QuotaService::class)->usage(), 'plans' => Plan::where('enabled', true)->where('public', true)->get()]);
    }

    public function requestPlan(Request $request)
    {
        abort_unless($request->user()->cans('manage_settings'), 403);
        $data = $request->validate(['plan_id' => ['required', Rule::exists('saas_landlord.saas_plans', 'id')->where('enabled', true)->where('public', true)], 'reason' => ['required', 'string', 'max:1000']]);
        AuditLog::create([
            'tenant_id' => app(TenantContext::class)->current()->id, 'action' => 'subscription.change_requested',
            'reason' => $data['reason'], 'after' => ['plan_id' => $data['plan_id'], 'requested_by_user_id' => $request->user()->id],
        ]);
        return redirect(tenant_route('saas.subscription'))->with('status', 'Your plan request has been sent to the platform administrator.');
    }
}
