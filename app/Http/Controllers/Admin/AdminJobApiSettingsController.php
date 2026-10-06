<?php

namespace App\Http\Controllers\Admin;

use App\Company;
use App\JobApiIntegration;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminJobApiSettingsController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'Jobs API';
        $this->pageIcon = 'icon-settings';
        $this->middleware(function ($request, $next) {
            abort_unless($request->user()->cans('manage_settings'), 403);

            return $next($request);
        });
    }

    public function index()
    {
        $this->companies = Company::where('status', 'active')->orderBy('company_name')->get();
        $this->integrations = JobApiIntegration::with('company')->orderByDesc('id')->get();
        $this->endpoint = url('/api/jobs');

        return response()->view('admin.job-api-settings.index', $this->data)
            ->header('Cache-Control', 'no-store, private');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'company_id' => [
                'required', 'integer', Rule::exists('companies', 'id')->where('status', 'active'),
                Rule::unique('job_api_integrations', 'company_id'),
            ],
        ]);
        $integration = new JobApiIntegration;
        $integration->company_id = $data['company_id'];
        $integration->enabled = true;

        return $this->tokenResponse($integration);
    }

    public function regenerate(JobApiIntegration $integration)
    {
        return $this->tokenResponse($integration);
    }

    public function update(Request $request, JobApiIntegration $integration)
    {
        $data = $request->validate(['enabled' => ['required', 'boolean']]);
        $integration->enabled = (bool) $data['enabled'];
        $integration->save();

        return redirect()->route('admin.job-api-settings.index')->with('status', 'API access updated.');
    }

    public function destroy(JobApiIntegration $integration)
    {
        $integration->delete();

        return redirect()->route('admin.job-api-settings.index')->with('status', 'API key revoked.');
    }

    private function tokenResponse(JobApiIntegration $integration)
    {
        $token = $integration->regenerateToken();

        return redirect()->route('admin.job-api-settings.index')
            ->with('new_job_api_token', $token)
            ->with('new_job_api_company', $integration->company->company_name)
            ->with('status', 'API key created. Copy it now; it will not be shown again.');
    }
}
