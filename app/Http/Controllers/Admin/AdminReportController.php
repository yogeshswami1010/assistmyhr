<?php

namespace App\Http\Controllers\Admin;

use App\InterviewSchedule;
use App\Job;
use App\JobApplication;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AdminReportController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = __('menu.reports');
        $this->pageIcon = 'icon-film';
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $now = Carbon::now();

        $this->jobApplication = JobApplication::count();
        $this->job = Job::count();
        $this->activeJobs = Job::open()->count();

        $countForStatus = function (string $status) {
            return JobApplication::query()
                ->join('application_status', 'application_status.id', '=', 'job_applications.status_id')
                ->where('application_status.status', $status)
                ->count();
        };

        $applied = $countForStatus('applied');
        $shortlisted = $countForStatus('phone screen') + $countForStatus('interview');
        $this->candidatesHired = $countForStatus('hired');
        $rejected = $countForStatus('rejected');

        $this->interviewTotal = InterviewSchedule::count();
        $this->interviewsToday = InterviewSchedule::whereDate('schedule_date', $now->toDateString())->count();

        $this->hireRate = $this->jobApplication > 0
            ? (int) round(($this->candidatesHired / $this->jobApplication) * 100)
            : 0;

        $appsThisMonth = JobApplication::where('created_at', '>=', $now->copy()->startOfMonth())->count();
        $appsLastMonth = JobApplication::whereBetween('created_at', [
            $now->copy()->subMonthNoOverflow()->startOfMonth(),
            $now->copy()->subMonthNoOverflow()->endOfMonth(),
        ])->count();

        if ($appsLastMonth > 0) {
            $this->applicationsMomPercent = (int) round((($appsThisMonth - $appsLastMonth) / $appsLastMonth) * 100);
        } elseif ($appsThisMonth > 0) {
            $this->applicationsMomPercent = 100;
        } else {
            $this->applicationsMomPercent = null;
        }

        $chartStart = $now->copy()->subMonths(11)->startOfMonth();
        $monthRows = JobApplication::query()
            ->join('application_status', 'application_status.id', '=', 'job_applications.status_id')
            ->where('job_applications.created_at', '>=', $chartStart)
            ->selectRaw('YEAR(job_applications.created_at) as y')
            ->selectRaw('MONTH(job_applications.created_at) as m')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN application_status.status IN ('phone screen', 'interview') THEN 1 ELSE 0 END) as shortlisted")
            ->selectRaw("SUM(CASE WHEN application_status.status = 'hired' THEN 1 ELSE 0 END) as hired")
            ->groupBy('y', 'm')
            ->get()
            ->keyBy(fn ($r) => sprintf('%04d-%02d', (int) $r->y, (int) $r->m));

        $barFull = [];
        for ($i = 11; $i >= 0; $i--) {
            $dt = $now->copy()->startOfMonth()->subMonths($i);
            $key = $dt->format('Y-m');
            $row = $monthRows->get($key);
            $barFull[] = [
                'label' => $dt->translatedFormat('M'),
                'count' => $row ? (int) $row->total : 0,
            ];
        }

        $sliceBar = function (array $full, int $n): array {
            $slice = array_slice($full, -$n);

            return [
                'labels' => array_column($slice, 'label'),
                'data' => array_column($slice, 'count'),
            ];
        };

        $weekBars = [];
        for ($i = 3; $i >= 0; $i--) {
            $startW = $now->copy()->subWeeks($i)->startOfWeek();
            $endW = $now->copy()->subWeeks($i)->endOfWeek();
            $weekBars[] = [
                'label' => 'W'.(4 - $i),
                'count' => JobApplication::whereBetween('created_at', [$startW, $endW])->count(),
            ];
        }

        $this->barDatasets = [
            '6m' => $sliceBar($barFull, 6),
            '3m' => $sliceBar($barFull, 3),
            '1m' => [
                'labels' => array_column($weekBars, 'label'),
                'data' => array_column($weekBars, 'count'),
            ],
        ];

        $lineLabels = [];
        $lineApplied = [];
        $lineShortlisted = [];
        $lineHired = [];
        for ($i = 5; $i >= 0; $i--) {
            $dt = $now->copy()->startOfMonth()->subMonths($i);
            $key = $dt->format('Y-m');
            $row = $monthRows->get($key);
            $lineLabels[] = $dt->translatedFormat('M');
            $lineApplied[] = $row ? (int) $row->total : 0;
            $lineShortlisted[] = $row ? (int) $row->shortlisted : 0;
            $lineHired[] = $row ? (int) $row->hired : 0;
        }

        $this->lineChart = [
            'labels' => $lineLabels,
            'applied' => $lineApplied,
            'shortlisted' => $lineShortlisted,
            'hired' => $lineHired,
        ];

        $this->donutTotal = $this->jobApplication;
        $this->donutSegments = [
            [
                'label' => __('modules.reportPage.donutNew'),
                'count' => $applied,
                'color' => '#2563EB',
            ],
            [
                'label' => __('modules.reportPage.donutShortlisted'),
                'count' => $shortlisted,
                'color' => '#059669',
            ],
            [
                'label' => __('modules.reportPage.donutHired'),
                'count' => $this->candidatesHired,
                'color' => '#7C3AED',
            ],
            [
                'label' => __('modules.reportPage.donutRejected'),
                'count' => $rejected,
                'color' => '#F97316',
            ],
        ];

        $categoryRows = JobApplication::query()
            ->join('jobs', 'jobs.id', '=', 'job_applications.job_id')
            ->join('job_categories', 'job_categories.id', '=', 'jobs.category_id')
            ->selectRaw('job_categories.name as category_name')
            ->selectRaw('COUNT(job_applications.id) as cnt')
            ->groupBy('job_categories.id', 'job_categories.name')
            ->orderByDesc('cnt')
            ->limit(6)
            ->get();

        $maxCat = $categoryRows->max('cnt') ?: 1;
        $catColors = ['#2563EB', '#7C3AED', '#059669', '#F97316', '#EF4444', '#6366F1'];
        $this->categoryBreakdown = $categoryRows->values()->map(function ($row, $i) use ($maxCat, $catColors) {
            $cnt = (int) $row->cnt;

            return [
                'name' => $row->category_name,
                'count' => $cnt,
                'width' => (int) round(($cnt / $maxCat) * 100),
                'color' => $catColors[$i % count($catColors)],
            ];
        });

        $this->recentApplications = JobApplication::with(['job.category', 'status'])
            ->latest()
            ->take(5)
            ->get();

        $this->dateRangeLabel = $now->copy()->startOfMonth()->format('M j')
            .' – '.$now->format('M j, Y');

        $this->jobApplicationsIndexUrl = route('admin.job-applications.index');

        return view('admin.report.index', $this->data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }
}
