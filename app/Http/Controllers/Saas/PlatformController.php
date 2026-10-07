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
        $titles = ['saas.platform-profile' => 'My profile', 'saas.platform-admins' => 'Super admins', 'saas.platform-dashboard' => 'Client overview', 'saas.platform-plans' => 'Subscription plans', 'saas.platform-settings' => 'Signup and trial settings', 'saas.platform-tenant' => $data['tenant']->name ?? 'Client details'];
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
    public function profile()
    {
        return $this->platformView('saas.platform-profile');
    }

    public function updateProfile(Request $request)
    {
        if (is_string($request->input('email'))) { $request->merge(['email' => strtolower(trim($request->input('email')))]); }
        $admin = Auth::guard('platform')->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:254', Rule::unique('saas_landlord.saas_platform_admins', 'email')->ignore($admin->id)],
            'current_password' => ['required', 'current_password:platform'],
        ]);
        $before = $admin->only(['name', 'email']);
        $admin->update(['name' => $data['name'], 'email' => strtolower($data['email'])]);
        AuditLog::create(['admin_id' => $admin->id, 'action' => 'admin.profile_updated', 'reason' => 'Super admin updated their profile.', 'before' => $before, 'after' => $admin->only(['name', 'email'])]);
        return back()->with('status', 'Your profile has been updated.');
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password:platform'],
            'password' => ['required', 'string', 'min:12', 'max:200', 'confirmed'],
        ]);
        $admin = Auth::guard('platform')->user();
        $admin->update(['password' => \Illuminate\Support\Facades\Hash::make($data['password']), 'remember_token' => \Illuminate\Support\Str::random(60)]);
        $request->session()->regenerate();
        AuditLog::create(['admin_id' => $admin->id, 'action' => 'admin.password_changed', 'reason' => 'Super admin changed their own password.']);
        return back()->with('status', 'Your password has been updated.');
    }

    public function admins()
    {
        return $this->platformView('saas.platform-admins', ['admins' => \App\Saas\PlatformAdmin::orderBy('name')->get()]);
    }

    public function createAdmin(Request $request)
    {
        if (is_string($request->input('email'))) { $request->merge(['email' => strtolower(trim($request->input('email')))]); }
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:254', Rule::unique('saas_landlord.saas_platform_admins', 'email')],
            'password' => ['required', 'string', 'min:12', 'max:200', 'confirmed'],
            'current_password' => ['required', 'current_password:platform'],
        ]);
        $admin = \App\Saas\PlatformAdmin::create(['name' => $data['name'], 'email' => strtolower($data['email']), 'password' => \Illuminate\Support\Facades\Hash::make($data['password'])]);
        AuditLog::create(['admin_id' => Auth::guard('platform')->id(), 'action' => 'admin.created', 'reason' => 'Super admin added a platform administrator.', 'after' => $admin->only(['id', 'name', 'email'])]);
        return back()->with('status', 'Super admin created. They can sign in at /superadmin/login.');
    }
    public function deleteTenant(Request $request, Tenant $tenant)
    {
        abort_if($tenant->slug === 'main' || $tenant->status === 'provisioning', 403, 'This workspace cannot be deleted.');
        DB::connection('saas_landlord')->transaction(function () use ($request, $tenant) {
            $locked = Tenant::whereKey($tenant->id)->lockForUpdate()->firstOrFail();
            abort_if($locked->slug === 'main' || $locked->status === 'provisioning', 403);
            $before = $locked->only(['id', 'uuid', 'slug', 'name', 'owner_email', 'status']);
            // Preserve audit history without leaving a foreign key to the removed client.
            AuditLog::where('tenant_id', $locked->id)->update(['tenant_id' => null]);
            \App\Saas\ApiKey::where('tenant_id', $locked->id)->delete();
            Subscription::where('tenant_id', $locked->id)->delete();
            AuditLog::create(['admin_id' => Auth::guard('platform')->id(), 'action' => 'tenant.deleted', 'reason' => 'Client deleted by super admin after confirmation.', 'before' => $before]);
            $locked->delete();
        });
        return redirect()->route('superadmin.dashboard')->with('status', 'Client deleted and access revoked. The workspace database and files have been retained for recovery.');
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
            'name' => ['required', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999'], 'currency' => ['required', 'regex:/\A[A-Z]{3}\z/'],
            'max_users' => ['nullable', 'integer', 'min:1'], 'max_jobs' => ['nullable', 'integer', 'min:1'],
            'max_candidates' => ['nullable', 'integer', 'min:1'], 'storage_mb' => ['nullable', 'integer', 'min:1'],
            'enabled' => ['required', 'boolean'], 'public' => ['required', 'boolean'],
        ]);
        if (!$plan) {
            $baseSlug = substr(trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($data['name'])), '-') ?: 'plan', 0, 38);
            $data['slug'] = $baseSlug;
            while ($data['slug'] === 'existing-workspace' || Plan::where('slug', $data['slug'])->exists()) {
                $data['slug'] = $baseSlug.'-'.strtolower(\Illuminate\Support\Str::random(10));
            }
        }
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
