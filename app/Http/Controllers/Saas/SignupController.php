<?php

namespace App\Http\Controllers\Saas;

use App\Saas\Plan;
use App\Saas\PlatformSetting;
use App\Saas\TenantContext;
use App\Saas\TenantProvisioner;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SignupController extends Controller
{
    public function pricing()
    {
        abort_unless(config('saas.enabled'), 404);
        return view('saas.pricing', ['plans' => Plan::where('enabled', true)->where('public', true)->get(), 'trialDays' => PlatformSetting::valueFor('trial_days', 14)]);
    }

    public function form()
    {
        abort_unless(config('saas.enabled') && PlatformSetting::valueFor('signup_enabled', '0') === '1', 403, 'New registrations are currently closed.');
        return view('saas.signup');
    }

    public function store(Request $request)
    {
        abort_unless(config('saas.enabled') && PlatformSetting::valueFor('signup_enabled', '0') === '1', 403);
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:150'], 'name' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'string', 'max:50', 'regex:/\A[a-z0-9][a-z0-9-]{1,49}\z/', Rule::notIn(['main', 'admin', 'superadmin', 'api', 'login', 'register']), Rule::unique('saas_landlord.saas_tenants', 'slug')],
            'email' => ['required', 'email', 'max:254'], 'password' => ['required', 'string', 'min:12', 'max:200', 'confirmed'],
        ]);
        $tenant = app(TenantProvisioner::class)->create($data);
        app(TenantContext::class)->activate($tenant);
        $request->session()->forget(['user', 'storage_setting', 'url.intended']);
        $request->session()->regenerate();
        $request->session()->put('saas_tenant_id', $tenant->id);
        $user = User::findOrFail(1);
        Auth::guard('web')->login($user);
        try { $user->sendEmailVerificationNotification(); }
        catch (\Throwable $e) { report($e); $request->session()->flash('status', 'Your workspace was created, but the verification email could not be sent. Contact the platform administrator.'); }
        return redirect('/email/verify');
    }

    public function workspace(string $slug)
    {
        abort_unless(config('saas.enabled'), 404);
        abort_unless(\App\Saas\Tenant::where('slug', $slug)->where('status', 'active')->exists(), 404);
        return redirect('/login?workspace='.rawurlencode($slug));
    }
}
