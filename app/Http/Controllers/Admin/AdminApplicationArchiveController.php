<?php

namespace App\Http\Controllers\Admin;

use App\Exports\JobApplicationArchiveExport;
use App\Helper\Reply;
use App\JobApplication;
use App\JobApplicationAnswer;
use App\Skill;
use App\Job;
use App\JobLocation;
use App\Company;
use App\Question;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Excel as ExcelExcel;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;
use App\ApplicationStatus;
class AdminApplicationArchiveController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = __('menu.candidateDatabase');
        $this->pageIcon = 'icon-drawer';
    }

    public function index(Request $request)
    {
        abort_if(! $this->user->cans('view_job_applications'), 403);

        $this->companies = Company::select('id', 'company_name')->orderBy('company_name')->get();
        $this->jobs      = Job::select('id', 'title')->orderBy('title')->get();
        $this->locations = JobLocation::select('id', 'location')->orderBy('location')->get();
        $this->statuses = ApplicationStatus::whereNotNull('job_id')->select('id', 'job_id', 'status')->orderBy('position')->get();


        $this->questions = Question::select('id', 'question')->orderBy('question')->get();

        return view('admin.applications-archive.index', $this->data);
    }

    public function data(Request $request)
    {
        abort_if(! $this->user->cans('view_job_applications'), 403);

        $jobApplications = JobApplication::withTrashed()
            ->whereNull('moved_to_trash_at')
            ->with([
                'job',
                'location',
                'status'
            ])
            ->orderBy('created_at', 'desc');  
        // ── Skill filter ───────────────────────────────────────────────
        if ($request->filled('skill')) {
            $skill = Skill::select('id', 'name')
                ->where('name', 'LIKE', '%' . strtolower($request->skill) . '%')
                ->first();

            if ($skill) {
                $jobApplications->whereJsonContains('skills', (string) $skill->id);
            } else {
                // No matching skill — return empty result
                $jobApplications->whereRaw('1 = 0');
            }
        }

        // ── Company filter ─────────────────────────────────────────────
        if ($request->filled('company') && $request->company !== 'all') {
            $jobApplications->whereHas('job', function ($q) use ($request) {
                $q->where('company_id', $request->company);
            });
        }

        // ── Job filter ────────────────────────────────────────────────
        if ($request->filled('jobs') && $request->jobs !== 'all') {
            $jobApplications->where('job_id', $request->jobs);
        }

        // ── Location filter ───────────────────────────────────────────
        if ($request->filled('location') && $request->location !== 'all') {
            $jobApplications->where('location_id', $request->location);
        }

        // ── Question / answer filter ──────────────────────────────────
        if ($request->filled('questions') && $request->questions !== 'all') {
            $jobApplications->whereHas('answers', function ($q) use ($request) {
                $q->where('question_id', $request->questions);
                if ($request->filled('question_value')) {
                    $q->where('answer', 'LIKE', '%' . $request->question_value . '%');
                }
            });
        }

        // ── Date range filter ─────────────────────────────────────────
        if ($request->filled('start_date') && $request->start_date != '0') {
            $jobApplications->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date') && $request->end_date != '0') {
            $jobApplications->whereDate('created_at', '<=', $request->end_date);
        }

        return DataTables::of($jobApplications)

        ->addColumn('select_orders', function ($row) {
            return '<input type="checkbox" name="check[]" class="checkBoxClass" value="'.$row->id.'">';
        })

        ->editColumn('full_name', function ($row) {
            $name = collect(explode(' ', trim($row->full_name)))
                ->map(fn($word) => ucfirst(strtolower($word)))
                ->join(' ');

            return '<a href="javascript:;" class="show-detail" data-row-id="'.$row->id.'">'
                . $name .
                '</a>';
        })

        ->addColumn('title', function ($row) {
            return \App\Support\CandidateJobLabel::html($row->job);
        })

        ->addColumn('location', function ($row) {
            return optional($row->location)->location; 
        })

        ->addColumn('status', function ($row) {

            if ($row->trashed()) {
                return '<span class="label label-danger">Archived</span>';
            }

            // Show actual status if assigned
            if ($row->status_id && $row->status) {
                return '<span class="label label-success">'
                    . e(ucfirst($row->status->status))
                    . '</span>';
            }

            // Candidate with no status assigned
            if ($row->is_candidate) {
                return '<span class="label">Internal</span>';
            }

            return '<span class="label label-default">No Status</span>';
        })

        ->addColumn('action', function ($row) {

            $buttons = '';

            if ($this->user->cans('view_job_applications')) {
                $buttons .= '
                    <a href="javascript:;"
                    class="btn btn-info btn-circle show-detail"
                    data-row-id="'.$row->id.'">
                        <i class="fa fa-search"></i>
                    </a>';
            }

            return '
                <div class="btn-group">
                    '.$buttons.'
                </div>';
        })

        ->rawColumns([
            'title',
            'select_orders',
            'full_name',
            'status',
            'action'
        ])
        ->addColumn('created_at', function ($row) {        // ← ADD THIS
            return $row->created_at->format('Y-m-d H:i:s');
        })
        ->addIndexColumn()
        ->make(true);
    }

    public function destroy($id)
    {
        abort_if(! $this->user->cans('delete_job_applications'), 403);

        $jobApplication = JobApplication::withTrashed()->findOrFail($id);

        $jobApplication->moved_to_trash_at = now();
        $jobApplication->save();
        $jobApplication->delete();

        return Reply::success(__('messages.recordDeleted'));
    }

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

        $this->allJobs     = \App\Job::orderBy('title')->with('location')->get();
        $this->zoom_setting = \App\ZoomSetting::first();

        $view = view('admin.applications-archive.show', $this->data)->render();

        return Reply::dataOnly(['status' => 'success', 'view' => $view]);
    }

    public function export($skill)
    {
        $filters = ['skill' => $skill];
        $data    = ['company' => $this->companyName];

        return Excel::download(
            new JobApplicationArchiveExport($filters, $data),
            __('modules.applicationArchive.exportFileName') . '.xlsx',
            ExcelExcel::XLSX
        );
    }

    public function deleteRecords(Request $request, $id)
    {
        JobApplication::withTrashed()
            ->whereIn('id', explode(',', $id))
            ->update(['moved_to_trash_at' => now(), 'deleted_at' => now()]);

        return Reply::success(__('messages.recordDeleted'));
    }
        public function getLocations(Request $request)
    {
        $companyId = $request->input('companyId');

        // If specific company selected, only get locations linked to that company's jobs
        if ($companyId && $companyId !== 'all' && $companyId !== '') {
            $locationIds = \DB::table('job_job_locations')
                ->join('jobs', 'jobs.id', '=', 'job_job_locations.job_id')
                ->where('jobs.company_id', $companyId)
                ->distinct()
                ->pluck('job_job_locations.location_id');

            $locations = JobLocation::whereIn('id', $locationIds)
                ->orderBy('location')
                ->get();
        } else {
            // No company filter - show all locations
            $locations = JobLocation::orderBy('location')->get();
        }

        $html = '<option value="all">' . __('modules.jobApplication.allLocation') . '</option>';
        foreach ($locations as $location) {
            $html .= '<option value="' . $location->id . '">' . ucfirst($location->location) . '</option>';
        }

        return response()->json(['locations' => $html]);
    }
}
