<?php

namespace App\Http\Controllers\Admin;

use App\Job;
use App\JobApplication;
use Carbon\Carbon;

class AdminAtsOverviewController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct();

        $this->pageIcon = 'icon-chart';
        $this->pageTitle = 'ATS Overview';
    }

    public function index()
    {
        abort_if(! $this->user->cans('view_job_applications'), 403);

        $today = Carbon::today()->toDateString();
        $jobs = Job::query()
            ->select('id', 'title', 'company_id', 'location_id', 'start_date','hiring_status','status')
            ->with([
                'company:id,company_name',
                'location:id,location',
                'jobLocation' => function ($query) {
                    $query->select('job_locations.id', 'job_locations.location');
                },
                'statuses' => function ($query) {
                    $query->select('id', 'job_id', 'status', 'color', 'position')
                        ->orderBy('position');
                },
            ])
           ->where('status', 'open')
            ->whereDate('start_date', '<=', $today)
            ->orderBy('title')
            ->get();

        $jobIds = $jobs->pluck('id');
        $counts = collect();
        $applicantsByStatus = collect();

        if ($jobIds->isNotEmpty()) {
            // Match the table view's merged-profile rule: only the newest
            // application for each email is shown and counted.
            $counts = JobApplication::query()
                ->selectRaw('job_id, status_id, COUNT(*) as applicant_count')
                ->where('is_candidate', 0)
                ->whereIn('job_id', $jobIds)
                ->whereIn('id', function ($query) {
                    $query->selectRaw('MAX(ja2.id)')
                        ->from('job_applications as ja2')
                        ->where('ja2.is_candidate', 0)
                        ->whereNull('ja2.deleted_at')
                        ->groupBy('ja2.email');
                })
                ->groupBy('job_id', 'status_id')
                ->get()
                ->groupBy('job_id');

            $applicantsByStatus = JobApplication::query()
                ->select('id', 'job_id', 'status_id', 'full_name')
                ->where('is_candidate', 0)
                ->whereIn('job_id', $jobIds)
                ->whereIn('id', function ($query) {
                    $query->selectRaw('MAX(ja2.id)')
                        ->from('job_applications as ja2')
                        ->where('ja2.is_candidate', 0)
                        ->whereNull('ja2.deleted_at')
                        ->groupBy('ja2.email');
                })
                ->orderBy('full_name')
                ->get()
                ->groupBy(['job_id', 'status_id']);
        }

        $jobs->each(function (Job $job) use ($counts, $applicantsByStatus) {
            $jobCounts = $counts->get($job->id, collect())->keyBy('status_id');
            $jobApplicants = $applicantsByStatus->get($job->id, collect());
            $overviewLocations = $job->jobLocation->pluck('location')->filter()->unique()->values();
            if ($overviewLocations->isEmpty() && optional($job->location)->location) {
                $overviewLocations = collect([$job->location->location]);
            }
            $job->overview_locations = $overviewLocations;
            $overviewLocationIds = $job->jobLocation->pluck('id')->map(fn ($id) => (int) $id);
            if ($overviewLocationIds->isEmpty() && $job->location_id) {
                $overviewLocationIds = collect([(int) $job->location_id]);
            }
            $job->overview_location_ids = $overviewLocationIds->unique()->values();

            $job->statuses->each(function ($status) use ($jobCounts, $jobApplicants) {
                $status->applicant_count = (int) optional($jobCounts->get($status->id))->applicant_count;
                $status->applicants = $jobApplicants->get($status->id, collect())
                    ->filter(fn ($applicant) => trim((string) $applicant->full_name) !== '')
                    ->values();
            });

            $job->applicant_count = (int) $job->statuses->sum('applicant_count');
        });

        $this->jobs = $jobs;
        $this->activeJobCount = $jobs->count();
        $this->activeApplicantCount = (int) $jobs->sum('applicant_count');
        $this->filterCompanies = $jobs->pluck('company')
            ->filter()
            ->unique('id')
            ->sortBy('company_name')
            ->values();
        $this->filterLocations = $jobs->flatMap(function (Job $job) {
            $locations = $job->jobLocation;
            if ($locations->isEmpty() && $job->location) {
                $locations = collect([$job->location]);
            }
            return $locations;
        })->filter()->unique('id')->sortBy('location')->values();

        return view('admin.ats-overview.index', $this->data);
    }
}
