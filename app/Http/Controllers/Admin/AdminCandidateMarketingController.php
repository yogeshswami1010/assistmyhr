<?php

namespace App\Http\Controllers\Admin;

use App\JobApplication;
use App\JobApplicationAnswer;
use App\Job;
use App\Company;
use App\Skill;
use App\ZoomSetting;
use App\Helper\Reply;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class AdminCandidateMarketingController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'Candidate Marketing';
        $this->pageIcon = 'icon-bullhorn';
        $this->perPage = 10;
    }

    /**
     * Listing page shell. Loads filters; actual rows come via data().
     */
    public function index()
    {
        abort_if(!$this->user->cans('view_job_applications'), 403);

        $this->jobs = Job::all();
        $this->companies = Company::all();

        return view('admin.candidate-marketing.index', $this->data);
    }

    /**
     * DataTables source — only applicants with is_marketing = 1.
     */
    public function data(Request $request)
    {
        abort_if(!$this->user->cans('view_job_applications'), 403);

        $applications = JobApplication::select(
                'job_applications.id',
                'job_applications.job_id',
                'job_applications.status_id',
                'job_applications.full_name',
                'job_applications.email',
                'job_applications.phone',
                'job_applications.marketing_label',
                'job_applications.marketing_added_at',
                'job_applications.created_at'
            )
            ->where('job_applications.is_marketing', 1)
            ->with(['job:id,title,deleted_at', 'status:id,status,color']);

        if ($request->jobs != 'all' && $request->jobs != '') {
            $applications->where('job_applications.job_id', $request->jobs);
        }

        // Filter by company without an explicit join — avoids ambiguous
        // column errors (job_applications.id vs jobs.id) that a join would
        // introduce alongside the already-selected columns above.
        if ($request->company != 'all' && $request->company != '') {
            $applications->whereIn('job_applications.job_id', function ($sub) use ($request) {
                $sub->select('id')
                    ->from('jobs')
                    ->where('company_id', $request->company);
            });
        }

        if ($request->search_label != null && $request->search_label != '') {
            $applications->where('job_applications.marketing_label', 'LIKE', '%' . $request->search_label . '%');
        }

        return DataTables::of($applications)
            ->editColumn('full_name', function ($row) {
                $name = ucwords($row->full_name);
                return '<a href="javascript:;" class="show-detail font-semibold text-[13.5px] text-[#1A1E2E] hover:text-blue-600" data-row-id="' . $row->id . '">' . $name . '</a>
                        <div class="text-[11.5px] text-[#8892A0]">' . $row->email . '</div>';
            })
            ->editColumn('title', function ($row) {
                return \App\Support\CandidateJobLabel::html($row->job);
            })
            ->editColumn('marketing_label', function ($row) {
                return $row->marketing_label
                    ? '<span style="display:inline-flex;align-items:center;padding:3px 10px;border-radius:20px;font-size:11.5px;font-weight:600;background:#FFF7ED;color:#C2410C">' . e($row->marketing_label) . '</span>'
                    : '<span style="color:#B0B8C4;font-size:12px">—</span>';
            })
            ->editColumn('status', function ($row) {
                if (!$row->status_id || !$row->status) {
                    return '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11.5px] font-semibold text-white" style="background:#6366F1">Internal</span>';
                }

                $color = $row->status->color ?? '#6B7280';
                $label = ucwords(str_replace('_', ' ', $row->status->status ?? ''));
                return '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11.5px] font-semibold text-white" style="background:' . $color . '">' . $label . '</span>';
            })
            ->editColumn('marketing_added_at', function ($row) {
                if (empty($row->marketing_added_at)) {
                    return '—';
                }

                // marketing_added_at may arrive as a raw string (no $casts entry
                // on the model) or already as a Carbon instance — handle both.
                $date = $row->marketing_added_at instanceof \Carbon\Carbon
                    ? $row->marketing_added_at
                    : \Carbon\Carbon::parse($row->marketing_added_at);

                return $date->format('d M Y');
            })
            ->addColumn('action', function ($row) {
                return '<div class="ja-row-actions">
                    <a href="javascript:;" class="show-detail ja-act-btn" data-row-id="' . $row->id . '" title="View profile"><i class="fa fa-eye"></i></a>
                    <button type="button" onclick="cmRemoveFromMarketing(' . $row->id . ')" class="ja-act-btn reject" title="Remove from Candidate Marketing"><i class="fa fa-times"></i></button>
                </div>';
            })
            ->rawColumns(['full_name', 'marketing_label', 'status', 'action', 'title'])
            ->addIndexColumn()
            ->make(true);
    }

    /**
     * Remove an applicant from marketing directly from the listing page
     * (mirrors the toggle button on the profile, without needing to open it).
     */
    public function remove(Request $request, $id)
    {
        abort_if(!$this->user->cans('edit_job_applications'), 403);

        $application = JobApplication::withTrashed()->findOrFail($id);
        $application->is_marketing = 0;
        $application->marketing_added_at = null;
        $application->save();

        return Reply::success('Removed from Candidate Marketing.');
    }

    /**
     * Dedicated profile view for Candidate Marketing — separate from both
     * job-applications.show and applications-archive.show so that stage-move
     * / prev-next actions inside it can never silently fall back to either
     * of those older views (which caused null-status crashes for
     * candidate-database-origin applicants).
     */
    public function show($id)
    {
        $this->application = JobApplication::with([
            'documents',
            'schedule',
            'notes',
            'onboard',
            'status',
            'schedule.employee',
            'schedule.comments.user',
            'statusHistories.fromStatus',
            'statusHistories.toStatus',
            'statusHistories.user',
            'resumeHistories.updatedBy:id,name',
            'smsMessages.user:id,name',
        ])->withTrashed()->find($id);

        $this->skills = Skill::select('id', 'name')->get();

        $this->answers = $this->application->job_id
            ? JobApplicationAnswer::with(['question'])
                ->where('job_id', $this->application->job_id)
                ->where('job_application_id', $this->application->id)
                ->get()
            : collect();

        $this->allJobs = Job::orderBy('title')->with('location')->get();
        $this->zoom_setting = ZoomSetting::first();

        $view = view('admin.candidate-marketing.show', $this->data)->render();

        return Reply::dataOnly(['status' => 'success', 'view' => $view]);
    }
}
