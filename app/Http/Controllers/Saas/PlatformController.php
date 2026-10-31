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
use App\Saas\WebsiteEnquiry;
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
        $tenants = Tenant::with('subscription.plan')->latest()->paginate(20);
        foreach ($tenants as $tenant) {
            $tenant->setAttribute('owner_email_verified', $this->ownerEmailVerified($tenant));
        }
        return $this->platformView('saas.platform-dashboard', [
            'total' => Tenant::count(), 'active' => Tenant::where('status', 'active')->count(),
            'suspended' => Tenant::where('status', 'suspended')->count(),
            'expired' => Subscription::whereNotNull('expires_at')->where('expires_at', '<=', now())->count(),
            'tenants' => $tenants,
            'newEnquiryCount' => WebsiteEnquiry::whereNull('read_at')->count(),
            'latestEnquiries' => WebsiteEnquiry::latest('id')->limit(5)->get(),
        ]);
    }
    public function enquiries(Request $request)
    {
        $filters = $request->validate(['kind' => ['nullable', Rule::in(['demo', 'contact'])], 'unread' => ['nullable', Rule::in(['1'])]]);
        $query = WebsiteEnquiry::latest('id');
        if (!empty($filters['kind'])) $query->where('kind', $filters['kind']);
        if (!empty($filters['unread'])) $query->whereNull('read_at');
        return $this->platformView('saas.platform-enquiries', [
            'pageTitle' => 'Website enquiries', 'enquiries' => $query->paginate(25)->withQueryString(),
            'newEnquiryCount' => WebsiteEnquiry::whereNull('read_at')->count(), 'filters' => $filters,
        ]);
    }

    public function enquiry(WebsiteEnquiry $enquiry)
    {
        if (!$enquiry->read_at) $enquiry->update(['read_at' => now()]);
        return $this->platformView('saas.platform-enquiry', ['pageTitle' => $enquiry->kind_label.' #'.$enquiry->id, 'enquiry' => $enquiry]);
    }

    public function loginAsClient(Request $request, Tenant $tenant)
    {
        abort_if(in_array($tenant->status, ['provisioning', 'failed'], true), 422, 'This workspace is not ready.');
        $adminId = Auth::guard('platform')->id();
        $context = app(TenantContext::class);
        try {
            $context->activate($tenant);
            $owner = \App\User::where('email', $tenant->owner_email)->firstOrFail();
            abort_unless($owner->hasRole('admin'), 403, 'The client owner is not an administrator.');
            AuditLog::create(['tenant_id' => $tenant->id, 'admin_id' => $adminId,
                'action' => 'support.access_started', 'reason' => 'Super administrator accessed the client account for setup.',
                'after' => ['user_id' => $owner->id]]);
            $request->session()->forget(['user', 'storage_setting', 'password_hash_web', 'url.intended', 'auth.password_confirmed_at']);
            $request->session()->put('saas_tenant_id', $tenant->id);
            $request->session()->put('support_access', ['admin_id' => $adminId, 'tenant_id' => $tenant->id, 'expires_at' => now()->addHour()->timestamp]);
            Auth::guard('web')->login($owner, false);
            $request->session()->regenerate();
            return redirect(route('admin.dashboard', ['workspace' => $tenant->slug]));
        } finally { $context->reset(); }
    }

    public function exitClient(Request $request)
    {
        $access = $request->session()->get('support_access');
        if ($access && (int) $access['admin_id'] === (int) Auth::guard('platform')->id()) {
            AuditLog::create(['tenant_id' => $access['tenant_id'], 'admin_id' => $access['admin_id'],
                'action' => 'support.access_ended', 'reason' => 'Super administrator exited the client account.']);
        }
        $request->session()->forget([Auth::guard('web')->getName(), 'support_access', 'saas_tenant_id', 'user', 'storage_setting', 'password_hash_web', 'url.intended', 'auth.password_confirmed_at']);
        Auth::forgetGuards();
        $request->session()->regenerate();
        return redirect()->route('superadmin.dashboard');
    }
    private function ownerEmailVerified(Tenant $tenant): ?bool
    {
        if (in_array($tenant->status, ['provisioning', 'failed'], true)) { return null; }
        try {
            config(['database.connections.saas_owner_status' => app(TenantContext::class)->connectionConfig($tenant)]);
            DB::purge('saas_owner_status');
            $owner = DB::connection('saas_owner_status')->table('users')
                ->where('email', $tenant->owner_email)->first(['email_verified_at']);
            return $owner ? $owner->email_verified_at !== null : null;
        } catch (\Throwable $exception) {
            report($exception);
            return null;
        } finally {
            DB::purge('saas_owner_status');
            config(['database.connections.saas_owner_status' => null]);
        }
    }
    public function activity()
    {
        return $this->platformView('saas.platform-activity', ['logs' => AuditLog::with('admin')->latest('id')->paginate(25), 'pageTitle' => 'Platform activity']);
    }
    public function requests()
    {
        return $this->platformView('saas.platform-requests', ['requests' => AuditLog::with('tenant')->where('action', 'subscription.change_requested')->latest('id')->paginate(25), 'plans' => Plan::all()->keyBy('id'), 'pageTitle' => 'Client requests']);
    }
    public function tenant(Tenant $tenant)
    {
        $tenant->setAttribute('owner_email_verified', $this->ownerEmailVerified($tenant));
        $context = app(TenantContext::class);
        $usage = null;
        $outlookMailbox = null;
        $clientSmtp = null;
        try {
            $context->activate($tenant);
            $usage = app(QuotaService::class)->usage();
            $clientSmtp = \App\EmailSetting::first()?->only(['mail_host', 'mail_port', 'mail_encryption', 'mail_username', 'mail_from_name', 'mail_from_email', 'verified']);
            if (\Illuminate\Support\Facades\Schema::hasTable('candidate_reply_mailboxes')) {
                $outlookMailbox = \App\CandidateReplyMailbox::first(['email', 'last_synced_at', 'sync_error']);
            }
        }
        catch (\Throwable $e) { report($e); }
        finally { $context->reset(); }
        return $this->platformView('saas.platform-tenant', ['tenant' => $tenant->load('subscription.plan'), 'usage' => $usage, 'plans' => Plan::where('enabled', true)->get(), 'logs' => $tenant->auditLogs()->with('admin')->limit(50)->get(),
            'microsoftApp' => app(\App\Services\MicrosoftReplyAppSettings::class)->metadata($tenant), 'outlookMailbox' => $outlookMailbox, 'clientSmtp' => $clientSmtp]);
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
        $before = PlatformSetting::whereIn('key', array_keys($data))->pluck('value', 'key')->all();
        foreach ($data as $key => $value) { PlatformSetting::updateOrCreate(['key' => $key], ['value' => (string) $value]); }
        AuditLog::create(['admin_id' => Auth::guard('platform')->id(), 'action' => 'platform.settings_changed', 'reason' => 'Signup and trial settings changed.', 'before' => $before, 'after' => $data]);
        return back()->with('status', 'Platform settings saved.');
    }
    public function saveTelephony(Request $request) {
        foreach (['telnyx_api_key','telnyx_public_key'] as $field) {
            if (is_string($request->input($field))) $request->merge([$field=>trim($request->input($field))]);
        }
        $data=$request->validate(['telnyx_api_key'=>[Rule::requiredIf(!PlatformSetting::valueFor('telnyx_api_key')),'nullable','string','max:8192'],'telnyx_public_key'=>['nullable','string','max:255']]);
        if (!empty($data['telnyx_api_key'])) PlatformSetting::updateOrCreate(['key'=>'telnyx_api_key'],['value'=>\Illuminate\Support\Facades\Crypt::encryptString($data['telnyx_api_key'])]);
        if (!empty($data['telnyx_public_key'])) PlatformSetting::updateOrCreate(['key'=>'telnyx_public_key'],['value'=>$data['telnyx_public_key']]);
        AuditLog::create(['admin_id'=>Auth::guard('platform')->id(),'action'=>'telephony.provider_updated','reason'=>'Platform Telnyx settings updated.']);
        return back()->with('status','Platform calling and SMS provider saved.');
    }
    public function saveClientTelephony(Request $request,Tenant $tenant) {
        $data=$request->validate([
            'sms_enabled'=>['required','boolean'],'calls_enabled'=>['required','boolean'],
            'sms_number'=>['required_if:sms_enabled,1','nullable','regex:/^\+1[0-9]{10}$/'],
            'voice_number'=>['required_if:calls_enabled,1','nullable','regex:/^\+1[0-9]{10}$/'],
            'connection_id'=>['required_if:calls_enabled,1','nullable','string','max:191','regex:/^[A-Za-z0-9_-]+$/'],
        ]);
        if (!empty($data['sms_number'])) {
            foreach (PlatformSetting::where('key','like','telephony.%')->where('key','!=','telephony.'.$tenant->id)->pluck('value') as $value) {
                if ((json_decode($value,true)['sms_number'] ?? null) === $data['sms_number']) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['sms_number'=>'This SMS number is already assigned to another client.']);
                }
            }
        }
        PlatformSetting::updateOrCreate(['key'=>'telephony.'.$tenant->id],['value'=>json_encode($data)]);
        AuditLog::create(['tenant_id'=>$tenant->id,'admin_id'=>Auth::guard('platform')->id(),'action'=>'telephony.client_updated','reason'=>'Client calling and SMS permissions updated.','after'=>$data]);
        return back()->with('status','Client calling and SMS settings saved.');
    }

    public function saveClientSmtp(Request $request, Tenant $tenant)
    {
        abort_unless(Auth::guard('platform')->check(), 403);
        abort_if(in_array($tenant->status, ['failed', 'provisioning']), 422, 'Repair this workspace before configuring email.');
        $request->merge(['mail_driver' => 'smtp']);
        $data = $request->validate((new \App\Http\Requests\SmtpSetting\UpdateSmtpSetting())->rules());
        $data['mail_encryption'] = \App\Services\SmtpConfiguration::encryption($data['mail_encryption'] ?? null, (int) $data['mail_port']);
        if (empty($data['mail_password'])) unset($data['mail_password']);
        $data['verified'] = 0;
        $adminId = Auth::guard('platform')->id();
        $context = app(TenantContext::class);
        $previous = $context->current();
        try {
            $context->activate($tenant);
            $smtp = \App\EmailSetting::firstOrFail();
            $smtp->update($data);
            cache()->forget('smtp_setting');
            session()->forget('smtp_setting');
            AuditLog::create(['tenant_id' => $tenant->id, 'admin_id' => $adminId,
                'action' => 'email.smtp_updated', 'reason' => 'Client SMTP configuration updated.',
                'after' => ['password_updated' => isset($data['mail_password'])]]);
            $result = $smtp->verifySmtp();
            return redirect()->route('superadmin.tenants.show', $tenant)->with('client_tab', 'smtp')
                ->with('status', $result['success'] ? 'Client SMTP settings saved and connection verified.' : 'Settings saved. SMTP connection failed; check the host, port, encryption and credentials.');
        } finally {
            $previous ? $context->activate($previous) : $context->reset();
        }
    }

    public function saveClientMicrosoftInbox(Request $request, Tenant $tenant)
    {
        abort_unless(Auth::guard('platform')->check(), 403);
        abort_if(in_array($tenant->status, ['failed', 'provisioning']), 422, 'This workspace must be repaired before configuring Outlook.');
        foreach (['microsoft_client_id', 'microsoft_client_secret'] as $field) {
            if (is_string($request->input($field))) $request->merge([$field => trim($request->input($field))]);
        }
        if (is_string($request->input('microsoft_client_id'))) {
            $request->merge(['microsoft_client_id' => strtolower($request->input('microsoft_client_id'))]);
        }
        $adminId = Auth::guard('platform')->id();
        $context = app(TenantContext::class);
        $previous = $context->current();
        $lock = null;
        $locked = false;
        try {
            $context->activate($tenant);
            $lock = \Illuminate\Support\Facades\Cache::lock('candidate-outlook-inbox', 300);
            if (!($locked = $lock->get())) {
                throw \Illuminate\Validation\ValidationException::withMessages(['microsoft_client_id' => 'An Outlook sync is in progress for this client. Try saving again shortly.']);
            }
            $requiresReconnect = DB::connection('saas_landlord')->transaction(function () use ($request, $tenant, $adminId) {
                Tenant::whereKey($tenant->id)->lockForUpdate()->firstOrFail();
                $settings = app(\App\Services\MicrosoftReplyAppSettings::class);
                $before = $settings->forTenant($tenant);
                $metadata = $settings->metadata($tenant);
                $data = $request->validate([
                    'microsoft_client_id' => ['required', 'uuid'],
                    'microsoft_client_secret' => [Rule::requiredIf(!$metadata['has_secret'] || $before['client_secret'] === '' || $request->input('microsoft_client_id') !== $metadata['client_id']), 'nullable', 'string', 'max:8192'],
                ]);
                $appChanged = strtolower($before['client_id']) !== $data['microsoft_client_id'];
                $reconnect = false;
                if ($appChanged && \Illuminate\Support\Facades\Schema::hasTable('candidate_reply_mailboxes')) {
                    $reconnect = \App\CandidateReplyMailbox::whereKey(1)->delete() > 0;
                }
                PlatformSetting::updateOrCreate(['key' => 'microsoft_reply.'.$tenant->id], ['value' => json_encode([
                    'client_id' => $data['microsoft_client_id'],
                    'client_secret' => \Illuminate\Support\Facades\Crypt::encryptString(($data['microsoft_client_secret'] ?? null) ?: $before['client_secret']),
                ])]);
                AuditLog::create([
                    'tenant_id' => $tenant->id, 'admin_id' => $adminId, 'action' => 'email.microsoft_app_updated',
                    'reason' => 'Microsoft inbox app settings updated for this client.',
                    'after' => ['client_id' => $data['microsoft_client_id'], 'secret_updated' => !empty($data['microsoft_client_secret']), 'reconnect_required' => $reconnect],
                ]);
                return $reconnect;
            });
            return redirect()->route('superadmin.tenants.show', $tenant)->with('client_tab', 'outlook')
                ->with('status', $requiresReconnect
                    ? 'Microsoft app settings saved. The client must reconnect Outlook to use the new application.'
                    : 'Microsoft app settings saved for this client. They can connect Outlook from Workspace Integrations.');
        } finally {
            if ($lock && $locked) $lock->release();
            $previous ? $context->activate($previous) : $context->reset();
        }
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
