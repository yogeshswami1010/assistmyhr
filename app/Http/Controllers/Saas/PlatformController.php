<?php

namespace App\Http\Controllers\Saas;

use App\Saas\AuditLog;
use App\Saas\Plan;
use App\Saas\PlatformSetting;
use App\Saas\QuotaService;
use App\Saas\Subscription;
use App\Saas\SubscriptionService;
use App\Saas\Tenant;
use App\Saas\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PlatformController extends Controller
{
    private function platformView(string $view, array $data = [])
    {
        $brand = (object) ['company_name' => 'AssistMyHR', 'logo_url' => asset('logo.webp'), 'favicon_url' => asset('favicon/assistmyhr.svg')];
        $titles = ['saas.platform-dashboard' => 'Client overview', 'saas.platform-plans' => 'Subscription plans', 'saas.platform-settings' => 'Signup and trial settings', 'saas.platform-tenant' => $data['tenant']->name ?? 'Client details'];
        return view($view, $data + ['platformLayout' => true, 'platformAdmin' => Auth::guard('platform')->user(),
            'pageTitle' => $titles[$view] ?? 'Super admin login', 'companyName' => 'AssistMyHR',
            'companySetting' => $brand, 'setting' => $brand, 'frontTheme' => (object) ['primary_color' => '#2563eb']]);
    }
    public function loginForm() { abort_unless(config('saas.enabled'), 404); return $this->platformView('saas.platform-login'); }
    public function login(Request $request)
    {
        abort_unless(config('saas.enabled'), 404);
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        if (!Auth::guard('platform')->attempt($data)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['email' => 'The supplied credentials are incorrect.']);
        }
        $request->session()->regenerate();
        return redirect('/superadmin');
    }
    public function logout(Request $request)
    {
        Auth::guard('platform')->logout();
        $request->session()->invalidate(); $request->session()->regenerateToken();
        return redirect('/superadmin/login');
    }
    public function dashboard()
    {
        return $this->platformView('saas.platform-dashboard', [
            'total' => Tenant::count(), 'active' => Tenant::where('status', 'active')->count(),
            'suspended' => Tenant::where('status', 'suspended')->count(),
            'expired' => Subscription::whereNotNull('expires_at')->where('expires_at', '<=', now())->count(),
            'tenants' => Tenant::with('subscription.plan')->latest()->paginate(20),
            'logs' => AuditLog::with('admin')->latest('id')->limit(20)->get(),
        ]);
    }
    public function tenant(Tenant $tenant)
    {
        $context = app(TenantContext::class);
        $usage = null;
        try { $context->activate($tenant); $usage = app(QuotaService::class)->usage(); }
        catch (\Throwable $e) { report($e); }
        finally { $context->reset(); }
        return $this->platformView('saas.platform-tenant', ['tenant' => $tenant->load('subscription.plan'), 'usage' => $usage, 'plans' => Plan::where('enabled', true)->get(), 'logs' => $tenant->auditLogs()->with('admin')->limit(50)->get()]);
    }
    public function updateTenant(Request $request, Tenant $tenant)
    {
        $data = $request->validate(['status' => ['required', Rule::in(['active', 'suspended'])], 'reason' => ['required', 'string', 'max:1000']]);
        abort_if(in_array($tenant->status, ['failed', 'provisioning']), 422, 'A failed workspace must be repaired before it can be activated.');
        $this->auditMutation($request, $tenant, 'tenant.status_changed', $data['reason'], function () use ($tenant, $data) {
            $before = ['status' => $tenant->status]; $tenant->status = $data['status']; $tenant->save();
            return [$before, ['status' => $tenant->status]];
        });
        return back()->with('status', 'Workspace status updated.');
    }
    public function extend(Request $request, Tenant $tenant)
    {
        $data = $request->validate([
            'days' => ['required_without:expires_at', 'prohibits:expires_at', 'nullable', 'integer', 'min:1', 'max:3650'],
            'expires_at' => ['required_without:days', 'prohibits:days', 'nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
            'plan_id' => ['nullable', Rule::exists('saas_landlord.saas_plans', 'id')->where('enabled', true)],
            'reason' => ['required', 'string', 'max:1000'],
        ]);
        app(SubscriptionService::class)->extend($tenant, Auth::guard('platform')->user(), $data);
        return back()->with('status', 'Subscription extended.');
    }
    public function changePlan(Request $request, Tenant $tenant)
    {
        $data = $request->validate(['plan_id' => ['required', Rule::exists('saas_landlord.saas_plans', 'id')->where('enabled', true)], 'reason' => ['required', 'string', 'max:1000']]);
        $this->auditMutation($request, $tenant, 'subscription.plan_changed', $data['reason'], function () use ($tenant, $data) {
            $subscription = Subscription::where('tenant_id', $tenant->id)->lockForUpdate()->firstOrFail();
            $before = ['plan_id' => $subscription->plan_id]; $subscription->plan_id = $data['plan_id']; $subscription->save();
            return [$before, ['plan_id' => $subscription->plan_id]];
        });
        return back()->with('status', 'Plan changed; the subscription expiry is preserved.');
    }
    public function verifyOwner(Request $request, Tenant $tenant)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $context = app(TenantContext::class);
        try {
            $context->activate($tenant);
            DB::table('users')->where('email', $tenant->owner_email)->update(['email_verified_at' => now()]);
            AuditLog::create(['tenant_id' => $tenant->id, 'admin_id' => Auth::guard('platform')->id(), 'action' => 'owner.email_verified_by_admin', 'reason' => $data['reason']]);
        } finally { $context->reset(); }
        return back()->with('status', 'Owner email verification approved.');
    }
    public function plans() { return $this->platformView('saas.platform-plans', ['plans' => Plan::where('slug', '!=', 'existing-workspace')->orderBy('id')->get()]); }
    public function savePlan(Request $request, ?Plan $plan = null)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'], 'slug' => ['required', 'regex:/\A[a-z0-9-]+\z/', 'max:50', Rule::unique('saas_landlord.saas_plans', 'slug')->ignore($plan?->id)],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999'], 'currency' => ['required', 'regex:/\A[A-Z]{3}\z/'],
            'max_users' => ['nullable', 'integer', 'min:1'], 'max_jobs' => ['nullable', 'integer', 'min:1'],
            'max_candidates' => ['nullable', 'integer', 'min:1'], 'storage_mb' => ['nullable', 'integer', 'min:1'],
            'enabled' => ['required', 'boolean'], 'public' => ['required', 'boolean'],
        ]);
        $plan ??= new Plan;
        $before = $plan->exists ? $plan->toArray() : null;
        $plan->fill($data)->save();
        AuditLog::create(['admin_id' => Auth::guard('platform')->id(), 'action' => 'plan.saved', 'reason' => 'Platform plan configuration updated.', 'before' => $before, 'after' => $plan->toArray()]);
        return back()->with('status', 'Plan saved. Changes also apply to clients currently on this plan.');
    }
    public function settings()
    {
        return $this->platformView('saas.platform-settings', ['settings' => PlatformSetting::pluck('value', 'key'), 'plans' => Plan::where('enabled', true)->where('public', true)->get()]);
    }
    public function saveSettings(Request $request)
    {
        $data = $request->validate(['signup_enabled' => ['required', 'boolean'], 'trial_days' => ['required', 'integer', 'min:1', 'max:90'], 'trial_plan_id' => ['required', Rule::exists('saas_landlord.saas_plans', 'id')->where('enabled', true)->where('public', true)]]);
        $before = PlatformSetting::pluck('value', 'key')->all();
        foreach ($data as $key => $value) { PlatformSetting::updateOrCreate(['key' => $key], ['value' => (string) $value]); }
        AuditLog::create(['admin_id' => Auth::guard('platform')->id(), 'action' => 'platform.settings_changed', 'reason' => 'Signup and trial settings changed.', 'before' => $before, 'after' => $data]);
        return back()->with('status', 'Platform settings saved.');
    }
    private function auditMutation(Request $request, Tenant $tenant, string $action, string $reason, callable $mutation): void
    {
        DB::connection('saas_landlord')->transaction(function () use ($tenant, $action, $reason, $mutation) {
            Tenant::whereKey($tenant->id)->lockForUpdate()->firstOrFail();
            [$before, $after] = $mutation();
            AuditLog::create(['tenant_id' => $tenant->id, 'admin_id' => Auth::guard('platform')->id(), 'action' => $action, 'reason' => $reason, 'before' => $before, 'after' => $after]);
        });
    }
}
