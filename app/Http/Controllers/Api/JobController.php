<?php

namespace App\Http\Controllers\Api;

use App\Job;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class JobController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);
        $jobs = $this->jobsQuery($request)
            ->orderByDesc('created_at')->orderByDesc('id')
            ->paginate($data['per_page'] ?? 20);

        return response()->json([
            'status' => true,
            'total_jobs' => $jobs->total(),
            'jobs' => $jobs->getCollection()->map(fn (Job $job) => $this->formatJob($job))->values(),
            'pagination' => [
                'current_page' => $jobs->currentPage(),
                'per_page' => $jobs->perPage(),
                'last_page' => $jobs->lastPage(),
                'has_more' => $jobs->hasMorePages(),
            ],
        ]);
    }

    public function show(Request $request, int $id)
    {
        $job = $this->jobsQuery($request)->whereKey($id)->firstOrFail();

        return response()->json(['status' => true, 'job' => $this->formatJob($job)]);
    }

    private function jobsQuery(Request $request)
    {
        $companyId = $request->attributes->get('job_api_company_id');
        $allJobs = $request->attributes->get('job_api_all_jobs') === true;
        abort_unless($allJobs || (is_numeric($companyId) && (int) $companyId > 0), 401);

        return Job::published()
            ->when(!$allJobs, fn ($query) => $query->where('company_id', (int) $companyId))
            ->whereHas('company', fn ($query) => $query->where('status', 'active'))
            ->with(['company', 'category', 'jobType', 'workExperience', 'jobLocation', 'currency']);
    }

    private function formatJob(Job $job): array
    {
        $salary = null;
        if ($job->show_salary) {
            $symbol = $job->currency?->currency_symbol ?? '';
            $salary = match ($job->pay_type) {
                'Range' => $symbol.number_format($job->starting_salary).' - '.$symbol.number_format($job->maximum_salary),
                'Maximum' => $symbol.number_format($job->maximum_salary),
                default => $symbol.number_format($job->starting_salary),
            };
            if ($job->pay_according) {
                $salary .= ' / '.$job->pay_according;
            }
        }
        $location = $job->jobLocation->first();

        // Whitelist public fields rather than serialize ATS models.
        return [
            'id' => $job->id,
            'job_code' => $job->job_code,
            'job_title' => $job->title,
            'slug' => $job->slug,
            'description' => trim(html_entity_decode(strip_tags($job->job_description ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8')),
            'requirements' => trim(html_entity_decode(strip_tags($job->job_requirement ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8')),
            'location' => $job->jobLocation->pluck('location')->implode(', '),
            'category' => $job->category?->name ?? '',
            'company' => $job->company?->show_in_frontend === 'true' ? $job->company->company_name : '',
            'job_type' => $job->jobType?->job_type ?? '',
            'salary' => $salary,
            'experience' => $job->workExperience?->work_experience ?? '',
            'start_date' => $job->start_date?->format('Y-m-d'),
            'end_date' => $job->end_date?->format('Y-m-d'),
            'detail_url' => tenant_route('jobs.jobDetail', [$job->slug, $location?->id]),
            'api_detail_url' => url('/api/jobs/'.$job->id),
            'apply_url' => tenant_route('jobs.jobApply', [$job->slug, $location?->id]),
        ];
    }
}
