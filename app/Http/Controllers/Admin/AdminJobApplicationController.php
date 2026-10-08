<?php

namespace App\Http\Controllers\Admin;

use App\Services\CandidateSearchText;

use App\AiApiKey;
use App\ApplicationSetting;
use App\ApplicationStatus;
use App\Company;
use App\ConsortiumRegistration;
use App\Exports\JobApplicationExport;
use App\Helper\Files;
use App\Helper\Reply;
use App\Http\Requests\InterviewSchedule\StoreRequest;
use App\Http\Requests\StoreJobApplication;
use App\Http\Requests\UpdateJobApplication;
use App\InterviewSchedule;
use App\Job;
use App\JobApplication;
use App\JobApplicationAnswer;
use App\JobApplicationResumeHistory;
use App\JobJobLocation;
use App\JobLocation;
use App\Notifications\CandidateScheduleInterview;
use App\Notifications\CandidateStatusChange;
use App\Notifications\ScheduleInterview;
use App\Question;
use App\Services\ResumeTextExtractor;
use App\Services\ResumePdfConverter;
use App\Services\TelnyxSmsService;
use App\Skill;
use App\Support\RaDataTableHtml;
use App\Services\Zoom\ZoomApiClient;
use App\Traits\ZoomSettings;
use App\User;
use App\ZoomMeeting;
use App\ZoomSetting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Excel as ExcelExcel;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Currency;
use App\ApplicantNote;
use App\ApplicantSmsMessage;
use App\CandidateEmailMessage;
use App\SmsSetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
class AdminJobApplicationController extends AdminBaseController
{
    use ZoomSettings;

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = __('menu.jobApplications');
        $this->pageIcon = 'icon-user';
        $this->perPage = 10;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request)
    {
        abort_if(! $this->user->cans('view_job_applications'), 403);

        $date = Carbon::now();
        $this->type = ($request->has('type')) ? 'dash' : '';

        $startDate = $date->subDays(30)->format('Y-m-d');
        $endDate = Carbon::now()->format('Y-m-d');
        $this->jobs = Job::all();
        $this->companies = Company::all();
        $this->skills = Skill::all();
        $this->questions = Question::all();
        $boardColumns = ApplicationStatus::whereNull('job_id')->withCount(['applications as application_count' => function ($q) use ($request) {
         $q->where('job_applications.is_candidate', 0);
            if ($request->startDate !== null && $request->startDate != 'null' && $request->startDate != '') {
                $q = $q->where(DB::raw('DATE(job_applications.`created_at`)'), '>=', $request->startDate);
            } else {
            }

            if ($request->endDate !== null && $request->endDate != 'null' && $request->endDate != '') {
                $q = $q->where(DB::raw('DATE(job_applications.`created_at`)'), '<=', $request->endDate);
            } else {
            }

            // Filter By jobs
            if ($request->jobs != 'all' && $request->jobs != '') {
                $q = $q->where('job_applications.job_id', $request->jobs);
            }

            // Filter by EndDate
            if ($request->search != null && $request->search != '') {
                $q = $q->where('full_name', 'LIKE', '%'.$request->search.'%')
                    ->orWhere('email', 'LIKE', '%'.$request->search.'%')
                    ->orWhere('phone', 'LIKE', '%'.$request->search.'%');
            }

            // Filter  by Location
            if ($request->location != 'all' && $request->location != '') {
                $q->where('job_applications.location_id', '=', $request->location);
            }

            // Filter  by question
            if ($request->questions != 'all' && $request->questions != '') {

                $q->join('job_questions', 'job_questions.job_id', 'job_applications.job_id')
                    ->where('job_questions.question_id', '=', $request->questions);
            }

            if ($request->question_value != '' && $request->questions != 'all' && $request->questions != '') {

                $q->join('job_application_answers', 'job_application_answers.job_application_id', 'job_applications.id')
                    ->where('job_application_answers.question_id', $request->questions)
                    ->where('job_application_answers.answer', 'LIKE', '%'.$request->question_value.'%');
            }
            // Filter  by company
            if ($request->company != 'all' && $request->company != '') {
                $q = $q->join('jobs', 'jobs.id', 'job_applications.job_id')
                    ->where('jobs.company_id', '=', $request->company);
            }

            // Filter by skills
            if ($request->skill != 'all' && $request->skill != '') {
                foreach (explode(',', $request->skill) as $key => $skill) {
                    if ($key == 0) {
                        $q->whereJsonContains('skills', $skill);
                    } else {
                        $q->orWhereJsonContains('skills', $skill);
                    }
                }
            }
        }])
            ->with(['applications' => function ($r) use ($request) {
                $r = $r->select('job_applications.*');
                 $r->where('job_applications.is_candidate', 0); // ADD THIS
                if ($request->startDate !== null && $request->startDate != 'null' && $request->startDate != '') {
                    $r = $r->where(DB::raw('DATE(job_applications.`created_at`)'), '>=', $request->startDate);
                } else {
                }

                if ($request->endDate !== null && $request->endDate != 'null' && $request->endDate != '') {
                    $r = $r->where(DB::raw('DATE(job_applications.`created_at`)'), '<=', $request->endDate);
                } else {
                }

                // Filter By jobs
                if ($request->jobs != 'all' && $request->jobs != '') {
                    $r = $r->where('job_applications.job_id', $request->jobs);
                }

                // Filter by EndDate
                if ($request->search != null && $request->search != '') {
                    $r = $r->where('full_name', 'LIKE', '%'.$request->search.'%')
                        ->orWhere('email', 'LIKE', '%'.$request->search.'%')
                        ->orWhere('phone', 'LIKE', '%'.$request->search.'%');
                }

                // Filter By company
                if ($request->company != 'all' && $request->company != '') {
                    $r = $r->join('jobs', 'jobs.id', 'job_applications.job_id')
                        ->where('jobs.company_id', '=', $request->company);
                }

                // Filter  by Location
                if ($request->location != 'all' && $request->location != '') {
                    $r->where('job_applications.location_id', '=', $request->location);
                }

                if ($request->questions != 'all' && $request->questions != '') {

                    $r->join('job_questions', 'job_questions.job_id', 'job_applications.job_id')
                        ->where('job_questions.question_id', '=', $request->questions);
                }

                if ($request->question_value != '' && $request->questions != 'all' && $request->questions != '') {

                    $r->join('job_application_answers', 'job_application_answers.job_application_id', 'job_applications.id')
                        ->where('job_application_answers.question_id', $request->questions)
                        ->where('job_application_answers.answer', 'LIKE', '%'.$request->question_value.'%');
                }

                // Filter by skills
                if ($request->skill != 'all' && $request->skill != '') {
                    foreach (explode(',', $request->skill) as $key => $skill) {
                        if ($key == 0) {
                            $r->whereJsonContains('skills', $skill);
                        } else {
                            $r->orWhereJsonContains('skills', $skill);
                        }
                    }
                }
                $r->with(['schedule', 'job.category']);
            }, 'applications.schedule']);

        $this->boardColumns = $boardColumns->orderBy('position')->get()->map(function ($query) {
            $query->setRelation('applications', $query->applications->take($this->perPage));

            return $query;
        });

        $boardStracture = [];

        foreach ($this->boardColumns as $key => $column) {
            $boardStracture[$column->id] = [];

            foreach ($column->applications as $application) {
                $boardStracture[$column->id][] = $application->id;
            }
        }

        $this->boardStracture = json_encode($boardStracture);
        $this->currentDate = Carbon::now()->timestamp;
        $this->locations = JobLocation::all();
        $this->startDate = $startDate;
        $this->endDate = $endDate;

        if ($request->ajax()) {
            $view = view('admin.job-applications.board-data', $this->data)->render();

            return Reply::dataOnly(['view' => $view]);
        }

        $this->mailSetting = ApplicationSetting::select('id', 'mail_setting')->first();
        $this->applicantsForAiCompare = JobApplication::query()
            ->select('id', 'full_name', 'job_id')
            ->with(['job:id,title'])
            ->latest('id')
            ->limit(300)
            ->get();

        return view('admin.job-applications.board', $this->data);
    }

    

    public function create()
    {
        abort_if(! $this->user->cans('add_job_applications'), 403);

        $this->jobs = Job::activeJobs();

        $this->locations = JobJobLocation::select('job_job_locations.*')
            ->with(['job', 'location_data'])
            ->join('jobs', 'jobs.id', 'job_job_locations.job_id')
            ->get();

        $this->gender = [
            'male' => __('modules.front.male'),
            'female' => __('modules.front.female'),
            'others' => __('modules.front.others'),
        ];

        // Add this
        // $this->currencies = Currency::all();

        return view('admin.job-applications.create', $this->data);
    }

    /**
     * @return mixed
     *
     * @throws \Throwable
     */
    public function jobQuestion($jobID, $applicationId = null)
    {
        $this->jobLocations = JobJobLocation::findOrFail($jobID);

        $this->job = Job::findOrFail($this->jobLocations->job_id);

        $this->jobQuestion = $this->job->questions()
            ->where(function ($query) {
                $query->whereNull('questions.job_category_id')
                    ->orWhere('questions.job_category_id', (int) $this->job->category_id);
            })
            ->with([
                'answers' => function ($query) use ($jobID, $applicationId) {
                    $query->where(['job_application_id' => $applicationId, 'job_id' => $jobID]);
                },
            ])->get();
        $this->gender = [
            'male' => __('modules.front.male'),
            'female' => __('modules.front.female'),
            'others' => __('modules.front.others'),
        ];

        $view = view('admin.job-applications.job-question', $this->data)->render();

        $options = ['job' => $this->job, 'gender' => $this->gender];
        $sections = ['section_visibility' => $this->job->section_visibility];

        if ($applicationId) {
            $application = JobApplication::select('id', 'gender', 'dob', 'country', 'state', 'city')->where('id', $applicationId)->first();

            $options = Arr::add($options, 'application', $application);
            $sections = Arr::add($sections, 'application', $application);
        }

        $requiredColumnsView = view('admin.job-applications.required-columns', $options)->render();
        $requiredSectionsView = view('admin.job-applications.required-sections', $sections)->render();

        $count = count($this->jobQuestion);

        $data = ['status' => 'success', 'view' => $view, 'requiredColumnsView' => $requiredColumnsView, 'requiredSectionsView' => $requiredSectionsView, 'count' => $count, 'jobJobLocation' => $this->jobLocations];

        if ($applicationId) {
            $data = Arr::add($data, 'application', $application);
        }

        return Reply::dataOnly($data);
    }


    public function edit($id)
    {
        abort_if(! $this->user->cans('edit_job_applications'), 403);

        $this->application = JobApplication::find($id);
        $this->statuses = $this->application?->job_id
            ? ApplicationStatus::where('job_id', $this->application->job_id)->orderBy('position')->get()
            : collect();
        $this->jobQuestion = $this->application->job->questions;

        $this->jobs = Job::select(
            'id',
            'title',
            'location_id',
            'status',
            'start_date',
            'end_date',
            'section_visibility'
        )->with('location:id,location')->get();

        $this->locations = JobJobLocation::select('job_job_locations.*')
            ->with(['job', 'location'])
            ->join('jobs', 'jobs.id', 'job_job_locations.job_id')
            ->get();

        // Add this
        $this->currencies = Currency::all();

        return view('admin.job-applications.edit', $this->data);
    }

    public function data(Request $request)
    {
        abort_if(! $this->user->cans('view_job_applications'), 403);

        $jobApplications = JobApplication::select(
            'job_applications.id',
            'job_applications.job_id',
            'job_applications.status_id',
            'job_applications.full_name',
            'job_applications.skills',
            'job_applications.location_id',   // ← this was the ambiguous one
            'job_applications.created_at'
        )
            ->selectRaw("EXISTS (SELECT 1 FROM job_application_answers jaa INNER JOIN questions q ON q.id = jaa.question_id WHERE jaa.job_application_id = job_applications.id AND q.type = 'radio' AND q.is_knockout = 1 AND q.knockout_answer IS NOT NULL AND LOWER(TRIM(jaa.answer)) = LOWER(TRIM(q.knockout_answer))) as is_knockout")
            ->with([
                'location',
                'job.skills',
                'status:id,status,color',
            ]);

        $jobApplications = $jobApplications->where('job_applications.is_candidate', 0);

        // Keep normal duplicate-email applications merged, but always show
        // applications explicitly created by a Consortium job assignment.
        $jobApplications = $jobApplications->where(function ($visible) {
            $visible->whereIn('job_applications.id', function ($sub) {
                $sub->selectRaw('MAX(ja2.id)')
                    ->from('job_applications as ja2')
                    ->where('ja2.is_candidate', 0)
                    ->whereColumn('ja2.email', 'job_applications.email')
                    ->groupBy('ja2.email');
            })->orWhereExists(function ($moves) {
                $moves->selectRaw('1')
                    ->from('consortium_registration_job_moves as crjm')
                    ->whereColumn('crjm.job_application_id', 'job_applications.id');
            });
        });

        // Knockout filter
        // Knockout filter — show applicants who answered a knockout question with knockout answer
        if ($request->knockout == 1) {
            $jobApplications = $jobApplications->whereHas('answers', function ($q) {
                $q->whereHas('question', function ($qq) {
                    $qq->where('type', 'radio')
                    ->where('is_knockout', 1)
                    ->whereNotNull('knockout_answer');
                })->whereRaw('LOWER(TRIM(job_application_answers.answer)) = LOWER(TRIM((SELECT knockout_answer FROM questions WHERE questions.id = job_application_answers.question_id LIMIT 1)))');
            });
        }

        // Filter by status
        if ($request->filled('status_name')) {
            $statusName = mb_strtolower(trim($request->status_name));
            $jobApplications->whereHas('status', function ($query) use ($statusName) {
                $query->whereRaw('LOWER(TRIM(status)) = ?', [$statusName]);
            });
        } elseif ($request->status != 'all' && $request->status != '') {
            $jobApplications = $jobApplications->where('status_id', $request->status);
        }

        // Filter By jobs
        if ($request->jobs != 'all' && $request->jobs != '') {
            $jobApplications = $jobApplications->where('job_id', $request->jobs);
        }

        // Filter By company
        if ($request->company != 'all' && $request->company != '') {
            $jobApplications = $jobApplications->join('jobs', 'jobs.id', 'job_applications.job_id')
                ->where('jobs.company_id', '=', $request->company);
        }

        // Filter By skills
        if ($request->skill != 'all' && $request->skill != '') {
            foreach (explode(',', $request->skill) as $key => $skill) {
                if ($key == 0) {
                    $jobApplications = $jobApplications->whereJsonContains('skills', $skill);
                } else {
                    $jobApplications = $jobApplications->orWhereJsonContains('skills', $skill);
                }
            }
        }

        // Filter by location
       
        if ($request->location != 'all' && $request->location != '') {
            $jobApplications = $jobApplications->where('job_applications.location_id', $request->location);
        }

        if ($request->questions != 'all' && $request->questions != '' && ($request->question_value == '' || is_null($request->question_value))) {
            $jobApplications = $jobApplications->whereHas('job.questions', function ($query) use ($request) {
                $query->where('question_id', $request->questions);
            });
        }

        if ($request->question_value != '' && $request->questions != 'all' && $request->questions != '') {
            $jobApplications = $jobApplications->join('job_application_answers', 'job_application_answers.job_application_id', 'job_applications.id')
                ->where('job_application_answers.question_id', $request->questions)
                ->where('job_application_answers.answer', 'LIKE', '%' . $request->question_value . '%');
        }

        // Filter by StartDate
        if ($request->startDate != null && $request->startDate != '') {
            $jobApplications = $jobApplications->whereDate('job_applications.created_at', '>=', $request->startDate);
        }

        // Filter by EndDate
        if ($request->endDate != null && $request->endDate != '') {
            $jobApplications = $jobApplications->whereDate('job_applications.created_at', '<=', $request->endDate);
        }

        // Load statuses for the action column: global defaults always first,
        // then any job-specific custom statuses appended after.
        $jobFilterId    = (int) $request->input('jobs', 0);
        $actionStatuses = collect();
        if ($jobFilterId > 0) {
            $actionStatuses = ApplicationStatus::where('job_id', $jobFilterId)->orderBy('position')->get();
        }

        // Build next-stage map from action statuses
        $nextMap = [];
        foreach ($actionStatuses as $i => $s) {
            $nextStatus = $actionStatuses->get($i + 1);
            if ($nextStatus) {
                $nextMap[$s->status] = [
                    'label' => ucwords(str_replace('_', ' ', $nextStatus->status)),
                    'id'    => $nextStatus->id,
                    'slug'  => $nextStatus->status,
                ];
            }
        }

        // Find rejected & applied — check action statuses first, then fall back to global
        $rejectedStatus = $actionStatuses->first(fn ($status) => strcasecmp($status->status, 'rejected') === 0);
        $appliedStatus  = $actionStatuses->first();

        $canEdit   = $this->user->cans('edit_job_applications');
        $canView   = $this->user->cans('view_job_applications');
        $canDelete = $this->user->role_id === 1; // admin only

        return DataTables::of($jobApplications)
                ->addColumn('action', function ($row) use ($nextMap, $rejectedStatus, $appliedStatus, $canEdit, $canView, $canDelete) {
                $parts = [];
                $statusSlug = strtolower($row->status?->status ?? '');

                if ($canEdit) {

                // "Next stage" quick button — derived from the job's pipeline order
                if (isset($nextMap[$statusSlug]) && $statusSlug !== 'rejected') {
                    $nextLabel = $nextMap[$statusSlug]['label'];
                    $nextId    = $nextMap[$statusSlug]['id'];
                    $parts[] = '<button type="button" onclick="jaMoveOne(' . $row->id . ',' . $nextId . ')" class="ja-act-btn move" title="Move to ' . $nextLabel . '">' . $nextLabel . ' <i class="fa fa-arrow-right" style="font-size:10px;margin-left:2px;"></i></button>';
                }

                // "Move to any stage" dropdown — content is populated client-side
                // from jaStages so it always reflects the currently-selected job's pipeline.
                if (!in_array($statusSlug, ['rejected', 'hired'])) {
                    $dropdownId   = 'ja-move-drop-' . $row->id;
                    $dropdownHtml = '<div class="ja-move-wrap" style="position:relative;display:inline-flex;">';
                    $dropdownHtml .= '<button type="button" class="ja-act-btn" onclick="jaToggleDrop(\'' . $dropdownId . '\', this)" title="Move to stage" style="padding:5px 8px;border-radius:8px;"><i class="fa fa-chevron-down" style="font-size:10px;"></i></button>';
                    // Empty container — jaToggleDrop fills it from jaStages on open
                    $dropdownHtml .= '<div id="' . $dropdownId . '" class="ja-move-drop"'
                        . ' data-app-id="' . $row->id . '"'
                        . ' data-current-status="' . e($statusSlug) . '"'
                        . ' style="display:none;position:fixed;background:#fff;border:1.5px solid #E2DED8;border-radius:10px;box-shadow:0 8px 24px rgba(15,23,42,.13);z-index:99999;min-width:160px;max-height:220px;overflow-y:auto;">'
                        . '</div></div>';
                    $parts[] = $dropdownHtml;
                }

                // Reject button
                if (!in_array($statusSlug, ['rejected', 'hired']) && $rejectedStatus) {
                    $parts[] = '<button type="button" onclick="jaMoveOne(' . $row->id . ',' . $rejectedStatus->id . ')" class="ja-act-btn reject" title="Reject"><i class="fa fa-times"></i></button>';
                }

                // Restore button for rejected
                if ($statusSlug === 'rejected' && $appliedStatus) {
                    $parts[] = '<button type="button" onclick="jaMoveOne(' . $row->id . ',' . $appliedStatus->id . ')" class="ja-act-btn restore" title="Restore to Applied"><i class="fa fa-undo"></i></button>';
                }
                }

                // View detail (sidebar) — always shown
                $parts[] = '<a href="javascript:;" class="show-detail ja-act-btn" data-row-id="' . $row->id . '" title="View profile"><i class="fa fa-eye"></i></a>';

                return '<div class="ja-row-actions">' . implode('', $parts) . '</div>';
            })
            ->editColumn('full_name', function ($row) {
                $name = ucwords($row->full_name);
                $job  = ucfirst($row->job?->title ?? '—');
                $loc  = ucwords($row->location?->location ?? '—');
                return '
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-bold text-[13px] flex-shrink-0">'
                            . strtoupper(substr($row->full_name, 0, 1)) .
                        '</div>
                        <div>
                            <a href="javascript:;" class="show-detail block text-[13.5px] font-semibold text-[#1A1E2E] hover:text-blue-600" data-row-id="' . $row->id . '">' . $name . '</a>
                            <span class="text-[11.5px] text-[#8892A0]">' . $job . ' &middot; ' . $loc . '</span>
                        </div>
                    </div>';
            })
            ->editColumn('title', function ($row) {
                return ucfirst($row->job?->title ?? '—');
            })
            ->addColumn('location_id', function ($row) {
                return ucwords($row->location?->location ?? '—');
            })
            ->editColumn('status', function ($row) {
                if ((bool) $row->is_knockout) {
                    return '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11.5px] font-semibold text-white" style="background:#dc2626"><i class="fa fa-user-times" style="font-size:10px"></i> Knockout</span>';
                }
                $color = $row->status?->color ?? '#6B7280';
                $label = ucwords(str_replace('_', ' ', $row->status?->status ?? ''));
                return '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11.5px] font-semibold text-white" style="background:' . $color . '">' . $label . '</span>';
            })
            ->orderColumn('created_at', 'job_applications.created_at $1')  // ← add this
            ->rawColumns(['action', 'full_name', 'status'])
            ->addIndexColumn()
            ->make(true);
    }
    public function jobStatuses(Request $request)
    {
        $jobId = (int) $request->input('job_id', 0);

        // Always load global (default) statuses
        $global = ApplicationStatus::whereNull('job_id')->orderBy('position')->get();

        // If a job is selected, append its custom statuses after the global ones
        $statuses = $global;
        if ($jobId > 0) {
            try {
                $jobSpecific = ApplicationStatus::where('job_id', $jobId)->orderBy('position')->get();
                // Only append job-specific ones that are not already in global (by ID)
                $globalIds = $global->pluck('id');
                $extra     = $jobSpecific->filter(fn($s) => !$globalIds->contains($s->id));
                $statuses  = $jobSpecific;
            } catch (\Exception $e) {
                // job_id column missing — just use global
            }
        }

        return Reply::dataOnly([
            'statuses' => $statuses->map(fn ($s) => [
                'id'    => $s->id,
                'slug'  => $s->status,
                'label' => ucfirst($s->status),
                'color' => $s->color ?? '#2563eb',
            ])->values(),
        ]);
    }

    public function storeProfileStage(Request $request, int $application)
    {
        abort_if(!$this->user->cans('edit_job_applications') || !$this->user->cans('edit_jobs'), 403);

        if (is_string($request->input('status_name'))) {
            $request->merge(['status_name' => trim(preg_replace('/\s+/u', ' ', $request->input('status_name')))]);
        }
        $data = $request->validate([
            'status_name' => ['required', 'string', 'max:255', 'not_regex:/[<>]/'],
            'status_color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        $stage = DB::transaction(function () use ($application, $data) {
            $candidate = JobApplication::withTrashed()->lockForUpdate()->findOrFail($application);
            if (!$candidate->job_id) {
                throw ValidationException::withMessages(['status_name' => 'Assign this candidate to a job before adding a stage.']);
            }

            // Use the candidate's current job, never a job ID supplied by the browser.
            // Lock its pipeline owner so simultaneous profile requests cannot add duplicates.
            Job::whereKey($candidate->job_id)->lockForUpdate()->firstOrFail();
            $statuses = ApplicationStatus::where('job_id', $candidate->job_id)->get();
            $normalName = mb_strtolower($data['status_name']);
            if ($statuses->contains(fn ($status) => mb_strtolower(trim(preg_replace('/\s+/u', ' ', $status->status))) === $normalName)) {
                throw ValidationException::withMessages(['status_name' => 'This stage already exists for this job.']);
            }

            return ApplicationStatus::create([
                'job_id' => $candidate->job_id,
                'status' => $data['status_name'],
                'color' => $data['status_color'],
                'position' => ((int) $statuses->max('position')) + 1,
            ]);
        });

        return Reply::successWithData('Stage added to this job.', [
            'stage' => [
                'id' => $stage->id,
                'job_id' => $stage->job_id,
                'label' => ucwords(str_replace('_', ' ', $stage->status)),
                'color' => $stage->color,
                'position' => $stage->position,
            ],
        ]);
    }

    public function stageCounts(Request $request)
    {
        $company        = $request->input('company', 'all');
        $jobs           = $request->input('jobs', 'all');
        $location       = $request->input('location', 'all');
        $questions      = $request->input('questions', 'all');
        $questionValue  = $request->input('question_value', '');

        // ── Counts per stage ──────────────────────────────────────────
        $countQuery = JobApplication::select(
                'job_applications.status_id',
                DB::raw('COUNT(*) as cnt')
            )
            ->where('job_applications.is_candidate', 0);

        $countQuery->where(function ($visible) {
            $visible->whereIn('job_applications.id', function ($sub) {
                $sub->selectRaw('MAX(ja2.id)')->from('job_applications as ja2')
                    ->where('ja2.is_candidate', 0)
                    ->whereColumn('ja2.email', 'job_applications.email')
                    ->groupBy('ja2.email');
            })->orWhereExists(function ($moves) {
                $moves->selectRaw('1')
                    ->from('consortium_registration_job_moves as crjm')
                    ->whereColumn('crjm.job_application_id', 'job_applications.id');
            });
        });

        if ($company !== 'all' && $company !== '') {
            $countQuery->join('jobs as j_co', 'j_co.id', '=', 'job_applications.job_id')
                    ->where('j_co.company_id', $company);
        }
        if ($jobs !== 'all' && $jobs !== '') {
            $countQuery->where('job_applications.job_id', $jobs);
        }
        if ($location !== 'all' && $location !== '') {
            $countQuery->where('job_applications.location_id', $location);
        }
        if ($questions !== 'all' && $questions !== '') {
            $countQuery->whereHas('job.questions', function ($q) use ($questions) {
                $q->where('question_id', $questions);
            });
            if ($questionValue !== '') {
                $countQuery->whereHas('answers', function ($q) use ($questions, $questionValue) {
                    $q->where('question_id', $questions)
                    ->where('answer', 'LIKE', '%' . $questionValue . '%');
                });
            }
        }

        $counts = $countQuery
            ->groupBy('job_applications.status_id')
            ->pluck('cnt', 'job_applications.status_id');

        // ── KO count ──────────────────────────────────────────────────
        $koQuery = JobApplication::where('job_applications.is_candidate', 0)
            ->where(function ($visible) {
                $visible->whereIn('job_applications.id', function ($sub) {
                    $sub->selectRaw('MAX(ja2.id)')->from('job_applications as ja2')
                        ->where('ja2.is_candidate', 0)
                        ->whereColumn('ja2.email', 'job_applications.email')
                        ->groupBy('ja2.email');
                })->orWhereExists(function ($moves) {
                    $moves->selectRaw('1')
                        ->from('consortium_registration_job_moves as crjm')
                        ->whereColumn('crjm.job_application_id', 'job_applications.id');
                });
            })
            ->whereHas('answers', function ($q) {
                $q->whereHas('question', function ($qq) {
                    $qq->where('type', 'radio')
                    ->where('is_knockout', 1)
                    ->whereNotNull('knockout_answer');
                })->whereColumn(
                    DB::raw('LOWER(TRIM(job_application_answers.answer))'),
                    DB::raw('LOWER(TRIM((SELECT knockout_answer FROM questions WHERE questions.id = job_application_answers.question_id LIMIT 1)))')
                );
            });

        if ($company !== 'all' && $company !== '') {
            $koQuery->join('jobs as j_ko', 'j_ko.id', '=', 'job_applications.job_id')
                    ->where('j_ko.company_id', $company);
        }
        if ($jobs !== 'all' && $jobs !== '') {
            $koQuery->where('job_applications.job_id', $jobs);
        }
        if ($location !== 'all' && $location !== '') {
            $koQuery->where('job_applications.location_id', $location);
        }

        $koCount = $koQuery->count('job_applications.id');

        return Reply::dataOnly(['counts' => $counts, 'ko_count' => $koCount]);
    }

        public function bulkStatusUpdate(Request $request)
    {
        abort_if(!$this->user->cans('edit_job_applications'), 403);

        $status = ApplicationStatus::whereNotNull('job_id')->findOrFail((int) $request->status_id);
        $apps = JobApplication::whereIn('id', $request->ids)->get(['id', 'job_id', 'status_id']);
        if ($apps->contains(fn ($app) => (int) $app->job_id !== (int) $status->job_id)) {
            return Reply::error('The selected status belongs to a different job. Select one job before moving applicants.');
        }
        foreach ($apps as $app) {
            $this->logStatusChange($app->id, $app->status_id, (int) $request->status_id, $this->user->id);
        }

        JobApplication::whereIn('id', $request->ids)->update(['status_id' => $request->status_id]);
        return Reply::success(__('messages.updatedSuccessfully'));
    }

    public function bulkRestoreKnockout(Request $request)
    {
        abort_if(!$this->user->cans('edit_job_applications'), 403);

        $apps = JobApplication::whereIn('id', $request->ids)->get(['id', 'job_id', 'status_id']);
        foreach ($apps as $app) {
            if (!$app->job_id) continue;
            $initialStatus = ApplicationStatus::initialForJob((int) $app->job_id);
            if (!$initialStatus) continue;
            $this->logStatusChange($app->id, $app->status_id, $initialStatus->id, $this->user->id);
            $app->status_id = $initialStatus->id;
            $app->save();
        }

        return Reply::success(__('messages.updatedSuccessfully'));
    }

    public function toggleTempStaffing(Request $request, int $id)
    {
        abort_if(!$this->user->cans('edit_job_applications'), 403);
        $application = JobApplication::withTrashed()->findOrFail($id);
        $add = $request->boolean('add');
        $application->forceFill([
            'is_temp_staffing' => $add,
            'temp_staffing_at' => $add ? now() : null,
            'temp_staffing_by' => $add ? $this->user->id : null,
        ])->save();
        $application->tempStaffingHistories()->create([
            'user_id' => $this->user->id,
            'action' => $add ? 'added' : 'removed',
        ]);

        return Reply::successWithData($add ? 'Candidate added to Temp Staffing.' : 'Candidate removed from Temp Staffing.', [
            'is_temp_staffing' => $add,
        ]);
    }
    public function sendSms(Request $request, int $id, TelnyxSmsService $sms): mixed
    {
        abort_if(!$this->user->cans('edit_job_applications'), 403);
        $request->validate(['message' => ['required', 'string', 'max:1600']]);

        $application = JobApplication::withTrashed()->findOrFail($id);
        if (!$application->phone) return Reply::error('This applicant does not have a phone number.');

        try {
            $message = trim($request->message);
            $telnyxMessageId = $sms->send($application->phone, $message);
            $settings = SmsSetting::first();

            $storedMessage = ApplicantSmsMessage::create([
                'job_application_id' => $application->id,
                'user_id' => $this->user->id,
                'direction' => 'outbound',
                'from_number' => $sms->normalizePhone((string) $settings->telnyx_from_number),
                'to_number' => $sms->normalizePhone((string) $application->phone),
                'message' => $message,
                'telnyx_message_id' => $telnyxMessageId,
                'status' => 'sent',
            ]);
        } catch (\Throwable $e) {
            Log::warning('Applicant SMS failed.', [
                'job_application_id' => $application->id,
                'error' => $e->getMessage(),
            ]);
            return Reply::error($e->getMessage());
        }

        return Reply::successWithData('SMS sent successfully.', [
            'sms' => [
                'id' => $storedMessage->id,
                'direction' => $storedMessage->direction,
                'message' => $storedMessage->message,
                'time' => $storedMessage->created_at->copy()->timezone('America/Toronto')->format('M j, Y g:i A').' ET',
                'sender' => $this->user->name,
            ],
        ]);
    }
    public function createSchedule(Request $request, $id)
    {
        abort_if(! $this->user->cans('add_schedule'), 403);
        $this->candidates = JobApplication::all();
        $this->users = User::all();
        $this->zoom_setting = ZoomSetting::first();
        $this->scheduleDate = $request->date;
        $this->currentApplicant = JobApplication::findOrFail($id);

        return view('admin.job-applications.interview-create', $this->data)->render();
    }

    public function storeSchedule(StoreRequest $request)
    {
        abort_if(! $this->user->cans('add_schedule'), 403);

        $dateTime = $request->scheduleDate.' '.$request->scheduleTime;
        $dateTime = Carbon::createFromFormat('Y-m-d H:i', $dateTime);
        if ($request->interview_type == 'online') {
            $this->setZoomConfigs();
            $data = $request->all();
            $meeting = new ZoomMeeting;
            $data['meeting_name'] = $request->meeting_title;
            $data['start_date_time'] = $dateTime;
            $data['end_date_time'] = $request->end_date.' '.$request->end_time;
            $meeting = $meeting->create($data);
            $host = User::find($request->created_by);
            $zoom = app(ZoomApiClient::class);
            $meetings = $this->createMeeting($zoom, $meeting, null, null, $host);
        } else {
            $meetings = '';
        }
        // store Schedule
        $interviewSchedule = new InterviewSchedule;
        $interviewSchedule->interview_type = $request->interview_type;
        $interviewSchedule->meeting_id = ($meetings != '') ? $meetings->id : null;
        $interviewSchedule->job_application_id = $request->candidates[0];
        $interviewSchedule->schedule_date = $dateTime;
        $interviewSchedule->save();

        // Update Schedule Status
        $jobApplication = $interviewSchedule->jobApplication;
        $status = ApplicationStatus::findForJob((int) $jobApplication->job_id, 'interview');
        $jobApplication->status_id = $status?->id ?? $jobApplication->status_id;
        $jobApplication->save();

        if ($request->comment) {
            $scheduleComment = [
                'interview_schedule_id' => $interviewSchedule->id,
                'user_id' => $this->user->id,
                'comment' => $request->comment,
            ];

            $interviewSchedule->comments()->create($scheduleComment);
        }

        if (! empty($request->employees)) {
            $interviewSchedule->employees()->attach($request->employees);
        }
        if (! $request->interview_type) {
            $meeting = '';
        }
        return Reply::redirect(route('admin.interview-schedule.index'), __('menu.interviewSchedule').' '.__('messages.createdSuccessfully'));
    }

    public function createMeeting(ZoomApiClient $zoom, ZoomMeeting $meeting, $id, $meetingId = null, $host = null)
    {
        $this->setZoomConfigs();

        // create meeting using zoom API
        $commonSettings = [
            'type' => 2,
            'topic' => $meeting->meeting_name,
            'start_time' => Carbon::parse($meeting->start_date_time)->toIso8601String(),
            // Zoom requires a valid positive duration (minutes).
            'duration' => max(1, (int)$meeting->end_date_time->diffInMinutes($meeting->start_date_time)),
            'timezone' => $this->global->timezone,
            'agenda' => $meeting->description,
            'settings' => [
                'host_video' => $meeting->host_video == 1,
                'participant_video' => $meeting->participant_video == 1,
            ],
        ];

        if ($host) {
            $commonSettings['settings']['alternative_hosts'] = $host->email;
        }

        if (is_null($id)) {
            $savedMeeting = $zoom->createMeeting($commonSettings);

            $meeting->meeting_id = (string) ($savedMeeting['id'] ?? '');
            $meeting->start_link = (string) ($savedMeeting['start_url'] ?? '');
            $meeting->join_link = (string) ($savedMeeting['join_url'] ?? '');
            $meeting->password = (string) ($savedMeeting['password'] ?? '');

            $meeting->save();
        } else {
            $zoom->updateMeeting($meeting->meeting_id, $commonSettings);
        }

        return $meeting;
    }

    public function store(StoreJobApplication $request)
    {

        abort_if(! $this->user->cans('add_job_applications'), 403);
        // Save currency to jobs table
        if ($request->filled('currency_id')) {
         $job = Job::find($request->job_id);

            if ($job) {
                $job->currency_id = $request->currency_id;
                $job->save();
            }
        }

        $jobApplication = new JobApplication;
        $jobApplication->candidate_origin = 'internal';
        $jobApplication->origin_user_id = auth()->id();
        $jobApplication->full_name = collect(explode(' ', trim($request->full_name)))
        ->map(fn($word) => ucfirst(strtolower($word)))
        ->join(' ');
        $jobApplication->job_id = $request->job_id;
        $jobApplication->status_id = ($request->entry_type === 'candidate')
            ? null
            : ApplicationStatus::initialForJob((int) $request->job_id)?->id;
        $jobApplication->email = $request->email;
        $jobApplication->location_id = $request->location_id;
        $jobApplication->phone = $request->phone;
        $jobApplication->address = $request->address;
        $jobApplication->cover_letter = $request->cover_letter;
        $jobApplication->column_priority = 0;

        if ($request->entry_type === 'candidate') {
            $jobApplication->is_candidate = 1;  // add this column via migration
        }

        if ($request->has('gender')) {
            $jobApplication->gender = $request->gender;
        }
        if ($request->has('dob')) {
            $jobApplication->dob = $request->dob;
        }
        if ($request->has('country')) {
            $countriesArray = json_decode(file_get_contents(public_path('country-state-city/countries.json')), true)['countries'];
            $statesArray = json_decode(file_get_contents(public_path('country-state-city/states.json')), true)['states'];

            $jobApplication->country = $this->getName($countriesArray, $request->country);
            $jobApplication->state = $this->getName($statesArray, $request->state);
            $jobApplication->city = $request->city;
            $jobApplication->zip_code = $request->zip_code;
        }

        if ($request->hasFile('photo')) {
            $jobApplication->photo = Files::uploadLocalOrS3($request->photo, 'candidate-photos', null, null, false);
        }
        $jobApplication->save();

        if ($request->hasFile('resume')) {
            $hashname = Files::uploadLocalOrS3($request->resume, 'documents/'.$jobApplication->id, null, null, false);
            $jobApplication->documents()->create([
                'name' => 'Resume',
                'hashname' => $hashname,
                'original_name' => $request->resume->getClientOriginalName(),
            ]);
        }

        // Job Application Answer save
        if (isset($request->answer) && ! empty($request->answer)) {
            foreach ($request->answer as $key => $value) {
                $answer = new JobApplicationAnswer;
                $answer->job_application_id = $jobApplication->id;
                $answer->job_id = $request->job_id;
                $answer->question_id = $key;
                if ($request->hasFile('answer.'.$key)) {
                    $answer->file = Files::uploadLocalOrS3($value, 'documents');
                } else {
                    $answer->answer = $value;
                }
                $answer->save();
            }
        }
        
        // Auto-move to rejected if applicant answered the knockout answer
        // Check knockout by loading answers and comparing in PHP
        $knockoutTriggered = false;

        $answers = JobApplicationAnswer::where('job_application_id', $jobApplication->id)
            ->with(['question'])
            ->get();

        foreach ($answers as $ans) {
            $q = $ans->question;
            if (!$q) continue;
            if ($q->type !== 'radio') continue;
            if (!$q->is_knockout) continue;
            if (!$q->knockout_answer) continue;

            if (strtolower(trim($ans->answer)) === strtolower(trim($q->knockout_answer))) {
                $knockoutTriggered = true;
                break;
            }
        }

        if ($knockoutTriggered) {
            $rejectedStatus = ApplicationStatus::findForJob((int) $jobApplication->job_id, 'rejected');
            if ($rejectedStatus) {
                $oldStatusId = $jobApplication->status_id;
                $jobApplication->status_id = $rejectedStatus->id;
                $jobApplication->save();
                $this->logStatusChange($jobApplication->id, $oldStatusId, $rejectedStatus->id, null);
            }
        }
        // ── Process skills from bulk create form ──
        if ($request->filled('skills')) {
            $skillNames = array_filter(array_map('trim', explode(',', $request->skills)));
            $skillIds   = [];

            foreach ($skillNames as $name) {
                if (empty($name)) continue;
                $skill = \App\Skill::whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
                if (!$skill) {
                    $skill = \App\Skill::create(['name' => $name]);
                }
                $skillIds[] = (string) $skill->id;
            }

            if (!empty($skillIds)) {
                $jobApplication->skills = array_values(array_unique($skillIds));
                $jobApplication->save();
            }
        }
        // ── Save AI-structured CV data captured during bulk-parse ──
        if ($request->filled('cv_text')) {
            $jobApplication->cv_text = mb_substr($request->input('cv_text'), 0, 65000);
            $jobApplication->save();
        }

        if ($request->filled('parsed_cv_data')) {
            $decoded = json_decode($request->input('parsed_cv_data'), true);

            if (is_array($decoded)) {
                $years = (float) ($decoded['total_experience']['years'] ?? 0)
                    + ((float) ($decoded['total_experience']['months'] ?? 0) / 12);

                $location = array_filter([
                    $decoded['personal']['location']['city'] ?? null,
                    $decoded['personal']['location']['province'] ?? null,
                    $decoded['personal']['location']['country'] ?? null,
                ]);

                $jobApplication->parsed_cv_data      = json_encode($decoded);
                $jobApplication->cv_experience_years = round($years, 1);
                $jobApplication->cv_job_titles       = implode(', ', (array) ($decoded['job_titles'] ?? []));
                $jobApplication->cv_skills_text      = implode(', ', (array) ($decoded['skills'] ?? []));
                $jobApplication->cv_location_text    = implode(', ', $location);
                $jobApplication->cv_indexed_at       = now();
                $jobApplication->cv_index_failed     = false;
                $jobApplication->save();
            }
        }
        if ($request->filled('notes') && is_array($request->notes)) {
            foreach ($request->notes as $noteText) {
                if (trim($noteText)) {
                    $note = new \App\ApplicantNote();
                    $note->note_text = trim($noteText);
                    $note->user_id   = auth()->id();
                    $note->job_application_id = $jobApplication->id;
                    $note->save();
                }
            }
        }

            return Reply::successWithData(__('menu.jobApplications').' '.__('messages.createdSuccessfully'),
                $request->input('is_bulk') ? ['id' => $jobApplication->id] : ['redirect' => route('admin.job-applications.table')]
            );
            
    }

    public function update(UpdateJobApplication $request, $id)
    {
        abort_if(! $this->user->cans('edit_job_applications'), 403);

        $mailSetting = ApplicationSetting::select('id', 'mail_setting')->first()->mail_setting;

        $jobApplication = JobApplication::with(['documents'])->findOrFail($id);
        $selectedStatus = ApplicationStatus::where('job_id', (int) $request->job_id)
            ->findOrFail((int) $request->status_id);
        $jobApplication->full_name = collect(explode(' ', trim($request->full_name)))
        ->map(fn($word) => ucfirst(strtolower($word)))
        ->join(' ');
        $jobApplication->job_id = $request->job_id;
        $jobApplication->status_id = $selectedStatus->id;
        $jobApplication->location_id = $request->location_id;
        $jobApplication->email = $request->email;
        $jobApplication->phone = $request->phone;
        $jobApplication->address = $request->address;
        $jobApplication->cover_letter = $request->cover_letter;

        if ($request->filled('currency_id')) {
            Job::where('id', $request->job_id)
                ->update([
                    'currency_id' => $request->currency_id
                ]);
        }

        if ($request->has('gender')) {
            $jobApplication->gender = $request->gender;
        }
        if ($request->has('dob')) {
            $jobApplication->dob = $request->dob;
        }
        if ($request->has('country')) {
            $countriesArray = json_decode(file_get_contents(public_path('country-state-city/countries.json')), true)['countries'];
            $statesArray = json_decode(file_get_contents(public_path('country-state-city/states.json')), true)['states'];

            $jobApplication->country = $this->getName($countriesArray, $request->country);
            $jobApplication->state = $this->getName($statesArray, $request->state);
            $jobApplication->city = $request->city;
            $jobApplication->zip_code = $request->zip_code;
        }

        if ($request->hasFile('photo')) {
            $jobApplication->photo = Files::uploadLocalOrS3($request->photo, 'candidate-photos', null, null, false);
        }

        $isStatusDirty = $jobApplication->isDirty('status_id');

       $jobApplication->save();

        // ── Process skills from bulk create form ──
        if ($request->filled('skills')) {
            $skillNames = array_filter(array_map('trim', explode(',', $request->skills)));
            $skillIds   = [];

            foreach ($skillNames as $name) {
                if (empty($name)) continue;

                // Try to find existing skill by name (case-insensitive)
                $skill = \App\Skill::whereRaw('LOWER(name) = ?', [strtolower($name)])->first();

                if (!$skill) {
                    // Create it if it doesn't exist
                    $skill = \App\Skill::create(['name' => $name]);
                }

                $skillIds[] = (string) $skill->id;
            }

            if (!empty($skillIds)) {
                $jobApplication->skills = $skillIds;
                $jobApplication->save();
            }
        }

        if ($request->hasFile('resume')) {
            $hashname = Files::uploadLocalOrS3($request->resume, 'documents/'.$jobApplication->id, null, null, false);

            if ($jobApplication->resumeDocument) {
                $jobApplication->resumeHistories()->create([
                    'updated_by' => $this->user->id,
                    'hashname' => $jobApplication->resumeDocument->hashname,
                    'original_name' => $jobApplication->resumeDocument->original_name ?: $jobApplication->resumeDocument->hashname,
                ]);
            }
            $jobApplication->documents()->updateOrCreate(
                [
                    'documentable_type' => JobApplication::class,
                    'documentable_id' => $jobApplication->id,
                    'name' => 'Resume',
                ],
                [
                    'hashname' => $hashname,
                    'original_name' => $request->resume->getClientOriginalName(),
                ]
            );
        }
        // Job Application Answer save
        if (isset($request->answer) && count($request->answer) > 0) {
            foreach ($request->answer as $key => $value) {
                if ($request->hasFile('answer.'.$key)) {
                    $file = Files::upload($value, 'documents');
                } else {
                    $answer = $value;
                }
                JobApplicationAnswer::updateOrCreate([
                    'job_application_id' => $jobApplication->id,
                    'job_id' => $jobApplication->job_id,
                    'question_id' => $key,
                ], ['answer' => ! empty($answer) ? $answer : null, 'file' => ! empty($file) ? $file : null]);
                $answer = '';
            }
        }

        // if ($mailSetting[$request->status_id]['status'] && $isStatusDirty) {
        //     Notification::send($jobApplication, new CandidateStatusChange($jobApplication));
        // }
        if (false && $mailSetting[$request->status_id]['status'] && $isStatusDirty) {
            Notification::send($jobApplication, new CandidateStatusChange($jobApplication));
        }
        // Auto-move to rejected column if any radio question answered 'no'
        $knockoutTriggered = false;
        $answers = JobApplicationAnswer::where('job_application_id', $jobApplication->id)
            ->with(['question'])
            ->get();

        foreach ($answers as $ans) {
            $q = $ans->question;
            if (!$q) continue;
            if ($q->type !== 'radio') continue;
            if (!$q->is_knockout) continue;
            if (!$q->knockout_answer) continue;
            if (strtolower(trim($ans->answer)) === strtolower(trim($q->knockout_answer))) {
                $knockoutTriggered = true;
                break;
            }
        }

        if ($knockoutTriggered) {
            $rejectedStatus = ApplicationStatus::findForJob((int) $jobApplication->job_id, 'rejected');
            if ($rejectedStatus) {
                $jobApplication->status_id = $rejectedStatus->id;
                $jobApplication->save();
            }
            // DO NOT soft-delete — knockout is tracked by the answer, not deletion
        }
        return Reply::redirect(route('admin.job-applications.table'), __('menu.jobApplications').' '.__('messages.updatedSuccessfully'));
    }

    public function destroy($id)
    {
        abort_if(!$this->user->hasRole('admin'), 403);

        $jobApplication = JobApplication::findOrFail($id);

        // Keep the application and its files recoverable from Trash.
        $jobApplication->moved_to_trash_at = now();
        $jobApplication->save();
        $jobApplication->delete();

        return Reply::success(__('messages.recordDeleted'));
    }

    public function show($id)
    {
        $this->application = JobApplication::withTrashed()
            // Do not hydrate CV search/index payloads for the profile drawer.
            // They are large and are not rendered in the initial Details view.
            ->select([
                'id', 'full_name', 'email', 'phone', 'address', 'gender', 'dob',
                'country', 'state', 'city', 'photo', 'skills', 'skype_id',
                'cover_letter', 'job_id', 'status_id', 'location_id',
                'candidate_origin', 'origin_user_id',
                'is_marketing', 'marketing_label', 'is_temp_staffing', 'temp_staffing_at', 'temp_staffing_by', 'created_at', 'updated_at',
            ])
            ->with([
                'originUser:id,name',
                'onboard',
                'schedule',
                // The CV URL accessor uses this loaded relation. Without it,
                // rendering a profile performs repeated resume-document queries.
                'documents:id,documentable_id,documentable_type,name,hashname,original_name,updated_at',
                'status',
                'location',
                'job',
                'job.category',
                'job.company',
                'job.location',
                'job.currency', // used by the salary badge in the job-description modal
                'smsMessages.user:id,name',
            ])
            ->find($id);

        if (!$this->application) {
            return Reply::error('Application not found.');
        }

        // Keep the original registration information available after this
        // candidate has been moved into a normal job application.
        $this->consortiumRegistration = ConsortiumRegistration::withTrashed()
            ->whereHas('jobMoves', function ($query) {
                $query->where('job_application_id', $this->application->id);
            })
            ->first();

        // Rendering every skill as an <option> and initializing Select2 on it
        // blocks the browser when the skills catalogue is large. The profile
        // needs only its selected options; Select2 searches the rest by AJAX.
        $selectedSkillIds = collect((array) $this->application->skills)
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->values();
        $this->skills = $selectedSkillIds->isEmpty()
            ? collect()
            : Skill::select('id', 'name')->whereIn('id', $selectedSkillIds)->orderBy('name')->get();

        // Answers remain for the existing Q&A tab and resume-upload fallback.
        // History and notes are intentionally not part of this first response.
        $this->answers = JobApplicationAnswer::with(['question'])
            ->where('job_id', $this->application->job_id)
            ->where('job_application_id', $this->application->id)
            ->get();

        // Previous applications (same email) — eager load answers+question and
        // notes+user so the blade never issues a query per row.
        $this->previousApps = collect();
        $this->clientNotes = collect();

        // Pass pipeline statuses to the view to avoid querying them again while rendering.
        $this->allStatuses = $this->application->job_id
            ? ApplicationStatus::where('job_id', $this->application->job_id)->orderBy('position')->get()
            : ApplicationStatus::whereNull('job_id')->orderBy('position')->get();

        // Static-ish lookup lists used inside the blade. Cached for 5 minutes so
        // opening profiles back-to-back doesn't re-run these on every request.
        $this->jobOptions = \Illuminate\Support\Facades\Cache::remember('ja_job_options_with_company_locations_v3', 300, function () {
            return Job::select('id', 'title', 'company_id', 'location_id')
                ->with([
                    'company:id,company_name',
                    'location:id,location',
                    'jobLocation' => function ($query) {
                        $query->select('job_locations.id', 'job_locations.location');
                    },
                ])
                ->orderBy('title')
                ->get();
        });
        $this->allJobs = \Illuminate\Support\Facades\Cache::remember('ja_all_jobs_with_location_v1', 300, function () {
            return Job::orderBy('title')->with('location')->get();
        });
        $this->mentionUsers = \Illuminate\Support\Facades\Cache::remember('ja_mention_users_v1', 300, function () {
            return User::select('id', 'name')->orderBy('name')->get();
        });

        $view = view('admin.job-applications.show', $this->data)->render();

        return Reply::dataOnly(['status' => 'success', 'view' => $view]);
    }

    public function profileTab($id, $tab)
    {
        abort_if(! $this->user->cans('view_job_applications'), 403);
        $application = JobApplication::withTrashed()->findOrFail($id);

        if ($tab === 'client-notes') {
            $clientNotes = \App\JobClientNote::with('user:id,name')->where('job_id', $application->job_id)->latest()->limit(50)->get();
            return Reply::dataOnly(['status' => 'success', 'view' => view('admin.job-applications.partials.client-notes-list', compact('clientNotes'))->render()]);
        }
        if ($tab === 'notes') {
            $notes = ApplicantNote::with('user:id,name')
                ->where('job_application_id', $application->id)
                ->latest('id')
                ->get();

            return Reply::dataOnly([
                'status' => 'success',
                'view' => view('admin.job-applications.partials.applicant-notes-list', [
                    'notes' => $notes,
                    'user' => $this->user,
                ])->render(),
            ]);
        }
        if ($tab === 'sms') {
            $application->load(['smsMessages.user:id,name']);

            return Reply::dataOnly([
                'status' => 'success',
                'count' => $application->smsMessages->count(),
                'view' => view('admin.job-applications.partials.sms-messages-list', compact('application'))->render(),
            ]);
        }
        if ($tab === 'schedule' && $application->schedule) {
            $schedule = $application->schedule()->with(['employee.user:id,name', 'comments.user:id,name'])->first();
            return Reply::dataOnly(['status' => 'success', 'view' => view('admin.job-applications.partials.profile-schedule', compact('schedule'))->render()]);
        }
        if ($tab === 'history') {
            $statusHistories = $application->statusHistories()->latest()->limit(30)->with(['fromStatus', 'toStatus', 'user'])->get();
            $resumeHistories = $application->resumeHistories()->with('updatedBy:id,name')->get();
            $tempStaffingHistories = $application->tempStaffingHistories()->with('user:id,name')->get();
            $candidateCalls = \App\CandidateCall::where('job_application_id', $application->id)
                ->with('user:id,name')->latest('id')->limit(50)->get();
            $canCall = $this->user->cans('edit_job_applications');
        $currentResume = $application->documents->firstWhere('name', 'Resume');
            $previousApps = JobApplication::withTrashed()
                ->whereNull('moved_to_trash_at')
                ->where('email', $application->email)
                ->where('is_candidate', 0)
                ->where('id', '!=', $application->id)
                ->with([
                    'job:id,title,company_id',
                    'job.company:id,company_name',
                    'status:id,status,color',
                    'location:id,location',
                    'answers.question:id,question,type',
                    'documents:id,documentable_id,documentable_type,name,hashname,original_name,updated_at',
                ])
                ->latest()
                ->get();
            return Reply::dataOnly(['status' => 'success', 'view' => view('admin.job-applications.partials.profile-history', compact('application', 'statusHistories', 'resumeHistories', 'tempStaffingHistories', 'candidateCalls', 'canCall', 'previousApps') + ['user' => $this->user])->render()]);
        }
        return Reply::error('Tab not found.');
    }

    public function viewResume($id)
    {
        abort_if(! $this->user->cans('view_job_applications'), 403);
        $application = JobApplication::withTrashed()->with('resumeDocument')->findOrFail($id);
        $document = $application->resumeDocument;
        abort_unless($document, 404, 'No resume is attached to this applicant.');

        return $this->streamResume(
            'documents/'.$application->id.'/'.$document->hashname,
            $document->original_name ?: $document->hashname
        );
    }

    public function viewResumeHistory($history)
    {
        abort_if(! $this->user->cans('view_job_applications'), 403);
        $resume = JobApplicationResumeHistory::findOrFail($history);

        return $this->streamResume(
            'documents/'.$resume->job_application_id.'/'.$resume->hashname,
            $resume->original_name ?: $resume->hashname
        );
    }

    private function streamResume(string $path, string $filename)
    {
        abort_unless(Storage::exists($path), 404, 'The resume file could not be found in storage.');
        $safeName = str_replace(['"', "\r", "\n"], '', $filename);

        return Storage::response($path, $safeName, [
            'Content-Disposition' => 'inline; filename="'.$safeName.'"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
    public function updateResume(Request $request, $id, ResumePdfConverter $converter, ResumeTextExtractor $extractor)
    {
        abort_if(! $this->user->cans('edit_job_applications'), 403);

        $request->validate([
            'resume' => 'required|file|max:15360|mimes:pdf,doc,docx,rtf,txt,jpg,jpeg,png',
        ]);

        $application = JobApplication::withTrashed()->with('resumeDocument')->findOrFail($id);
        $uploaded = $request->file('resume');
        $converted = $uploaded;
        $newHashname = null;

        try {
            $converted = $converter->convert($uploaded);
        } catch (\Throwable $exception) {
            report($exception);
            throw ValidationException::withMessages([
                'resume' => 'The Word resume could not be converted to PDF. Please upload a valid DOC, DOCX, or PDF file.',
            ]);
        }

        // DOC/DOCX uploads are now PDFs. Store the converted filename so
        // every profile viewer serves and previews the active CV as PDF.
        $originalName = $converted->getClientOriginalName();

        try {
            $newHashname = Files::uploadLocalOrS3($converted, 'documents/'.$application->id, null, null, false);
            $resumeText = '';
            try {
                $resumeText = $extractor->extract($converted);
            } catch (\Throwable $ignored) {
                // The replacement remains valid when text extraction is unavailable.
            }

            DB::transaction(function () use ($application, $newHashname, $originalName, $resumeText) {
                $current = $application->resumeDocument;
                if ($current) {
                    $application->resumeHistories()->create([
                        'updated_by' => $this->user->id,
                        'hashname' => $current->hashname,
                        'original_name' => $current->original_name ?: $current->hashname,
                    ]);
                    $current->update(['hashname' => $newHashname, 'original_name' => $originalName]);
                } else {
                    $application->documents()->create([
                        'name' => 'Resume',
                        'hashname' => $newHashname,
                        'original_name' => $originalName,
                    ]);
                }

                if ($resumeText !== '') {
                    $application->forceFill([
                        'cv_text' => mb_substr($resumeText, 0, 65000),
                        'cv_indexed_at' => now(),
                        'cv_index_failed' => 0,
                    ])->save();
                }
            });
        } catch (\Throwable $exception) {
            if ($newHashname) Files::deleteFile($newHashname, 'documents/'.$application->id);
            throw $exception;
        } finally {
            $converter->cleanup($converted);
        }

        $application = $application->fresh(['documents']);
        $resumeHistories = $application->resumeHistories()->with('updatedBy:id,name')->get();
        $currentResume = $application->documents->firstWhere('name', 'Resume');

        return response()->json([
            'status' => 'success',
            'message' => 'Resume updated. The previous resume is available in History.',
            'resume_url' => route('admin.job-applications.resume.view', $application->id).'?v='.($currentResume?->updated_at?->timestamp ?? now()->timestamp),
            'history_html' => view('admin.job-applications.partials.resume-history', compact('resumeHistories'))->render(),
        ]);
    }
    public function profileSkillSearch(Request $request)
    {
        abort_if(! $this->user->cans('view_job_applications'), 403);

        $term = trim((string) $request->input('term', ''));
        $skills = Skill::query()
            ->select('id', 'name')
            ->when($term !== '', fn ($query) => $query->where('name', 'like', '%' . $term . '%'))
            ->orderBy('name')
            ->limit(25)
            ->get()
            ->map(fn ($skill) => ['id' => $skill->id, 'text' => $skill->name]);

        return response()->json(['results' => $skills]);
    }

    public function updateIndex(Request $request) 
    {
        $taskIds = $request->applicationIds;
        $boardColumnIds = $request->boardColumnIds;
        $priorities = $request->prioritys;
        $mailSetting = ApplicationSetting::select('id', 'mail_setting')->first()->mail_setting;

        $date = Carbon::now();
        $startDate = $request->startDate ?: $date->subDays(30)->format('Y-m-d');
        $endDate = $request->endDate ?: $date->format('Y-m-d');

        if ($request->has('applicationIds')) {
            foreach ($taskIds as $key => $taskId) {
                if (! is_null($taskId)) {

                    $task = JobApplication::find($taskId);
                    $oldStatusId = $task->status_id;
                    $task->column_priority = $priorities[$key];
                    $task->status_id = $boardColumnIds[$key];
                    $task->save();

                    $this->logStatusChange($task->id, $oldStatusId, (int) $boardColumnIds[$key], $this->user->id);
                }
            }

            // Send notification to candidate on update status
            // if ($mailSetting[$boardColumnIds[0]]['status'] && $request->draggedTaskId != 0) {
            //     $jobApplication = JobApplication::findOrFail($request->draggedTaskId);
            //     Notification::send($jobApplication, new CandidateStatusChange($jobApplication));
            // }
            if (false && $mailSetting[$boardColumnIds[0]]['status'] && $request->draggedTaskId != 0) {
                Notification::send($jobApplication, new CandidateStatusChange($jobApplication));
            }
        }

        $columnCountByIds = ApplicationStatus::select('id', 'color')
            ->withCount([
                'applications as status_count' => function ($query) use ($startDate, $endDate, $request) {
                    $query->withoutTrashed()->whereDate('created_at', '>=', $startDate)->whereDate('created_at', '<=', $endDate);
                    if ($request->jobs != 'all' && $request->jobs != '') {
                        $query->where('job_id', $request->jobs);
                    }
                    if ($request->search != '') {
                        $query->where('full_name', 'LIKE', '%'.$request->search.'%');
                    }
                },
            ])
            ->get()
            ->toArray();

        return Reply::dataOnly(['status' => 'success', 'columnCountByIds' => $columnCountByIds]);
    }

    public function table()
    {
        abort_if(! $this->user->cans('view_job_applications'), 403);

        // The unfiltered view uses the global labels only. Selecting a job
        // replaces these with that job's fully dynamic pipeline via AJAX.
        $this->boardColumns = ApplicationStatus::whereNull('job_id')->orderBy('position')->get();
        $this->locations = JobLocation::all();
        $this->jobs = Job::all();
        $this->skills = Skill::all();
        $this->questions = Question::all();
        $this->companies = Company::all();
        $this->applicantsForAiCompare = JobApplication::query()
            ->select('id', 'full_name', 'job_id')
            ->with(['job:id,title'])
            ->latest('id')
            ->limit(300)
            ->get();

        return view('admin.job-applications.index', $this->data);
    }

    public function aiCompare(Request $request)
    {
        abort_if(! $this->user->cans('view_job_applications'), 403);

        $validated = $request->validate([
            'job_id' => 'required|integer|exists:jobs,id',
            'application_ids' => 'required|array|min:1|max:300',
            'application_ids.*' => 'integer|exists:job_applications,id',
        ]);

        $job = Job::findOrFail((int) $validated['job_id']);
        $applications = JobApplication::query()
            ->where('job_id', (int) $job->id)
            ->whereIn('id', $validated['application_ids'])
            ->get();

        if ($applications->isEmpty()) {
            return Reply::error(__('messages.selectAtLeastOneCandidate'));
        }

        $apiCandidates = $this->collectAiApiCandidates();

        if ($apiCandidates === []) {
            return Reply::error(__('messages.aiApiKeyRequired'));
        }

        $jobProfile = trim(strip_tags((string) $job->job_description.' '.(string) $job->job_requirement));
        $jobSkillNames = $this->getJobSkillNames($job);
        $jobLocationIds = JobJobLocation::query()->where('job_id', $job->id)->pluck('location_id')->map(fn ($id) => (int) $id)->all();
        $candidatePayload = $applications->map(function (JobApplication $application) {
            return [
                'id' => $application->id,
                'full_name' => $application->full_name,
                'skills' => $this->getApplicationSkillNames($application),
                'location' => optional($application->location)->location,
                'location_id' => (int) ($application->location_id ?? 0),
                'inferred_experience_years' => $this->inferExperienceYears($application),
                'profile' => $this->buildApplicantProfileText($application),
            ];
        })->values()->all();

        $lastError = 'AI compare failed. Please try again.';
        foreach ($apiCandidates as $candidate) {
            try {
                $result = $this->compareApplicantsByProvider(
                    $candidate['provider'],
                    $candidate['api_key'],
                    $job->title,
                    $jobProfile,
                    $candidatePayload,
                    $jobSkillNames,
                    $jobLocationIds
                );

                $indexedApplications = $applications->keyBy('id');
                $indexedByName = $applications->keyBy(function (JobApplication $application) {
                    return Str::lower(trim((string) $application->full_name));
                });
                $matches = collect($result)
                    ->values()
                    ->map(function (array $row, int $index) use ($indexedApplications, $indexedByName, $applications) {
                        $rawId = $row['application_id'] ?? $row['applicant_id'] ?? $row['candidate_id'] ?? $row['id'] ?? null;
                        $id = is_numeric($rawId) ? (int) $rawId : 0;
                        $app = $id > 0 ? $indexedApplications->get($id) : null;

                        if (! $app && isset($row['full_name']) && is_string($row['full_name'])) {
                            $app = $indexedByName->get(Str::lower(trim($row['full_name'])));
                        }

                        // Last-resort fallback: preserve row by positional mapping.
                        if (! $app) {
                            $app = $applications->get($index);
                        }

                        if (! $app) {
                            return null;
                        }

                        return [
                            'application_id' => (int) $app->id,
                            'full_name' => $app->full_name,
                            'status_id' => (int) $app->status_id,
                            'match_score' => max(0, min(100, (int) ($row['match_score'] ?? $row['score'] ?? 0))),
                            'rank_tier' => (string) ($row['rank_tier'] ?? $this->scoreToTier((int) ($row['match_score'] ?? $row['score'] ?? 0))),
                            'summary' => (string) ($row['summary'] ?? $row['reason'] ?? $row['explanation'] ?? ''),
                            'strengths' => collect($row['strengths'] ?? [])->filter(fn ($v) => is_string($v))->values()->all(),
                            'gaps' => collect($row['gaps'] ?? [])->filter(fn ($v) => is_string($v))->values()->all(),
                        ];
                    })
                    ->filter()
                    ->sortByDesc('match_score')
                    ->values();

                return Reply::successWithData(__('messages.aiComparisonGeneratedSuccessfully'), [
                    'matches' => $matches,
                    'job_title' => $job->title,
                    'debug' => [
                        'selected_count' => $applications->count(),
                        'ai_rows_count' => count($result),
                        'mapped_count' => $matches->count(),
                    ],
                ]);
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
            }
        }

        return Reply::error(__('messages.aiCompareFailed').': '.$lastError);
    }

    public function aiParseResumeFromUpload(Request $request)
    {
        abort_if(! ($this->user->cans('add_job_applications') || $this->user->cans('edit_job_applications')), 403);

        $request->validate([
            'resume' => 'required|file|max:15360|mimes:pdf,doc,docx,xls,xlsx,rtf,txt,jpg,jpeg,png',
        ]);

        $file = $request->file('resume');
        if ($file === null) {
            return Reply::error(__('messages.resumeReadFailed'));
        }

        try {
            $text = app(ResumeTextExtractor::class)->extract($file);
        } catch (\InvalidArgumentException $e) {
            return Reply::error($e->getMessage());
        }

        $apiCandidates = $this->collectAiApiCandidates();
        if ($apiCandidates === []) {
            return Reply::error(__('messages.aiApiKeyRequired'));
        }

        $textForAi = mb_substr($text, 0, 15000);
        $systemPrompt = 'You extract structured applicant details from resume text and write a short professional cover letter. '
            .'Return strict minified JSON only. '
            .'Schema: {"full_name":string,"email":string,"phone":string,"address":string,"country":string,"state":string,"city":string,"zip_code":string,"cover_letter":string}. '
            .'Use empty string for any field you cannot determine. '
            .'For country and state return names (not numeric IDs). '
            .'Cover letter style: 2 short paragraphs, professional tone, no markdown, no bullet points. '
            .'No markdown, no code fences, no extra text.';

        $userPrompt = "Resume text:\n".$textForAi;

        $lastError = 'AI resume parsing failed. Please try again.';
        foreach ($apiCandidates as $candidate) {
            try {
                $decoded = $this->generateCoverLetterJsonByProvider(
                    (string) $candidate['provider'],
                    (string) $candidate['api_key'],
                    $systemPrompt,
                    $userPrompt
                );

                return Reply::successWithData(__('messages.resumeParsedSuccessfully'), [
                    'full_name' => trim((string) data_get($decoded, 'full_name', '')),
                    'email' => trim((string) data_get($decoded, 'email', '')),
                    'phone' => $this->normalizePhoneForForm((string) data_get($decoded, 'phone', '')),
                    'address' => trim((string) data_get($decoded, 'address', '')),
                    'country' => trim((string) data_get($decoded, 'country', '')),
                    'state' => trim((string) data_get($decoded, 'state', '')),
                    'city' => trim((string) data_get($decoded, 'city', '')),
                    'zip_code' => trim((string) data_get($decoded, 'zip_code', '')),
                    'cover_letter' => trim((string) data_get($decoded, 'cover_letter', '')),
                    'resume_text' => mb_substr($text, 0, 20000),
                ]);
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
            }
        }

        return Reply::error(__('messages.aiResumeParsingFailed').': '.$lastError);
    }

    public function aiGenerateCoverLetterAndDetails(Request $request)
    {
        abort_if(! ($this->user->cans('add_job_applications') || $this->user->cans('edit_job_applications')), 403);

        $validated = $request->validate([
            'job_id' => 'required|integer|exists:jobs,id',
            'location_id' => 'nullable|integer',
            'full_name' => 'nullable|string|max:191',
            'email' => 'nullable|email|max:191',
            'phone' => 'nullable|string|max:191',
            'address' => 'nullable|string|max:1000',
            'cover_letter' => 'nullable|string|max:20000',
            'resume_text' => 'nullable|string|max:20000',
        ]);

        $job = Job::with('company')->findOrFail((int) $validated['job_id']);
        $jobLocationText = '';
        if (! empty($validated['location_id'])) {
            $jobLocation = JobLocation::find((int) $validated['location_id']);
            $jobLocationText = trim((string) optional($jobLocation)->location);
        }

        $jobProfile = trim(strip_tags((string) $job->job_description.' '.(string) $job->job_requirement));
        $companyName = trim((string) optional($job->company)->company_name);

        $apiCandidates = $this->collectAiApiCandidates();

        if ($apiCandidates === []) {
            return Reply::error(__('messages.aiApiKeyRequired'));
        }

        $existingFullName = (string) ($validated['full_name'] ?? '');
        $existingEmail = (string) ($validated['email'] ?? '');
        $existingPhone = (string) ($validated['phone'] ?? '');
        $existingAddress = (string) ($validated['address'] ?? '');
        $existingCoverLetter = (string) ($validated['cover_letter'] ?? '');
        $resumeText = trim((string) ($validated['resume_text'] ?? ''));

        $systemPrompt = 'You are an expert assistant that writes professional cover letters for job applicants. '
            .'Return strict minified JSON only. '
            .'Schema: {"full_name":string,"email":string,"phone":string,"address":string,"cover_letter":string}. '
            .'Rules: No markdown, no code fences, no extra text.';

        $userPrompt = 'Job title: '.$job->title.'. '
            .($companyName !== '' ? 'Company: '.$companyName.'. ' : '')
            .($jobLocationText !== '' ? 'Location: '.$jobLocationText.'. ' : '')
            .'Job profile: '.$jobProfile.'. '
            .'Existing applicant fields (may be empty): '
            .'full_name="'.trim($existingFullName).'", '
            .'email="'.trim($existingEmail).'", '
            .'phone="'.trim($existingPhone).'", '
            .'address="'.trim($existingAddress).'". '
            .'Existing cover_letter (may be empty): '
            .'cover_letter="'.trim($existingCoverLetter).'". '
            .($resumeText !== '' ? 'Resume excerpt (from uploaded CV, for tailoring the letter): '.mb_substr($resumeText, 0, 12000).' ' : '')
            .'Task: If a field above is empty, generate an appropriate value. '
            .'If the field is NOT empty, copy it exactly. '
            .'Generate a tailored cover letter for the job. '
            .'Cover letter style: 2-3 short paragraphs, professional tone, no bullet points.';

        $lastError = 'AI cover letter generation failed. Please try again.';
        foreach ($apiCandidates as $candidate) {
            try {
                $decoded = $this->generateCoverLetterJsonByProvider(
                    (string) $candidate['provider'],
                    (string) $candidate['api_key'],
                    $systemPrompt,
                    $userPrompt
                );

                $aiFullName = (string) data_get($decoded, 'full_name', '');
                $aiEmail = (string) data_get($decoded, 'email', '');
                $aiPhone = (string) data_get($decoded, 'phone', '');
                $aiAddress = (string) data_get($decoded, 'address', '');
                $aiCoverLetter = (string) data_get($decoded, 'cover_letter', '');

                $finalFullName = trim($existingFullName) !== '' ? $existingFullName : $aiFullName;
                $finalEmail = trim($existingEmail) !== '' ? $existingEmail : $aiEmail;
                $finalPhone = trim($existingPhone) !== '' ? $existingPhone : $aiPhone;
                $finalAddress = trim($existingAddress) !== '' ? $existingAddress : $aiAddress;
                $finalCoverLetter = trim($existingCoverLetter) !== '' ? $existingCoverLetter : $aiCoverLetter;
                $finalPhone = $this->normalizePhoneForForm((string) $finalPhone);

                return Reply::successWithData(__('messages.coverLetterGeneratedSuccessfully'), [
                    'full_name' => $finalFullName,
                    'email' => $finalEmail,
                    'phone' => $finalPhone,
                    'address' => $finalAddress,
                    'cover_letter' => $finalCoverLetter,
                ]);
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
            }
        }

        return Reply::error(__('messages.aiCoverLetterGenerationFailed').': '.$lastError);
    }

    public function aiUpdateStatus(Request $request)
    {
        abort_if(! ($this->user->cans('edit_job_applications')), 403);

        $validated = $request->validate([
            'application_id' => 'required|integer|exists:job_applications,id',
            'status_id' => 'required|integer|exists:application_status,id',
        ]);

        $jobApplication = JobApplication::withTrashed()->findOrFail((int) $validated['application_id']);
        $selectedStatus = ApplicationStatus::where('job_id', (int) $jobApplication->job_id)
            ->findOrFail((int) $validated['status_id']);
        $oldStatusId = $jobApplication->status_id;
        $isStatusDirty = $jobApplication->isDirty('status_id');
        $jobApplication->status_id = $selectedStatus->id;
        $jobApplication->save();

        $this->logStatusChange($jobApplication->id, $oldStatusId, (int) $validated['status_id'], $this->user->id);

        $mailSetting = ApplicationSetting::select('id', 'mail_setting')->first()?->mail_setting ?? [];
        $statusId = (int) $validated['status_id'];

        // if ($isStatusDirty && is_array($mailSetting) && isset($mailSetting[$statusId]['status']) && $mailSetting[$statusId]['status'] === true) {
        //     Notification::send($jobApplication, new CandidateStatusChange($jobApplication));
        // }
        if (false && $isStatusDirty && is_array($mailSetting) && isset($mailSetting[$statusId]['status']) && $mailSetting[$statusId]['status'] === true) {
            Notification::send($jobApplication, new CandidateStatusChange($jobApplication));
        }

        return Reply::success(__('messages.updatedSuccessfully'));
    }

    public function aiCompareApplicants(Request $request)
    {
        abort_if(! $this->user->cans('view_job_applications'), 403);

        $validated = $request->validate([
            'job_id' => 'required|integer|exists:jobs,id',
        ]);

        $applications = JobApplication::query()
            ->select('id', 'full_name', 'job_id')
            ->where('job_id', (int) $validated['job_id'])
            ->orderBy('id', 'desc')
            ->limit(300)
            ->get();

        return Reply::dataOnly([
            'applications' => $applications->map(fn (JobApplication $a) => [
                'id' => (int) $a->id,
                'full_name' => (string) $a->full_name,
            ])->values()->all(),
        ]);
    }

    public function ratingSave(Request $request, $id)
    {
        abort_if(! $this->user->cans('edit_job_applications'), 403);

        $application = JobApplication::withTrashed()->findOrFail($id);
        $application->rating = $request->rating;
        $application->save();

        return Reply::success(__('messages.updatedSuccessfully'));
    }

    // Job Applications data Export
    public function export($status, $location, $startDate, $endDate, $jobs)
    {
        $filters = [
            'status' => $status,
            'location' => $location,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'jobs' => $jobs,
        ];

        $data = [
            'company' => $this->companyName,
        ];

        return Excel::download(new JobApplicationExport($filters, $data), 'job-applications.xlsx', ExcelExcel::XLSX);
    }

    public function getName($arr, $id)
    {
        $result = array_filter($arr, function ($value) use ($id) {
            return $value['id'] == $id;
        });

        return current($result)['name'];
    }

    private function buildApplicantProfileText(JobApplication $application): string
    {
        $skills = implode(', ', $this->getApplicationSkillNames($application));

        $answers = JobApplicationAnswer::query()
            ->where('job_application_id', $application->id)
            ->whereNotNull('answer')
            ->pluck('answer')
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->implode(' | ');

        return trim(implode("\n", array_filter([
            'Name: '.$application->full_name,
            'Email: '.((string) $application->email),
            'Phone: '.((string) $application->phone),
            'Location: '.((string) optional($application->location)->location),
            'Skills: '.$skills,
            'Inferred Experience Years: '.$this->inferExperienceYears($application),
            'Cover Letter: '.trim((string) $application->cover_letter),
            'Application Answers: '.$answers,
        ])));
    }

    /**
     * @return array<int, array{provider: string, api_key: string}>
     */
    private function collectAiApiCandidates(): array
    {
        $configuredKeys = AiApiKey::query()->active()->orderBy('sort_order')->orderBy('id')->get();
        $apiCandidates = [];
        foreach ($configuredKeys as $row) {
            $plainKey = $this->resolvePlainApiKey((string) $row->api_key);
            if ($plainKey === '') {
                continue;
            }
            $apiCandidates[] = [
                'provider' => $this->normalizeProvider((string) $row->provider),
                'api_key' => $plainKey,
            ];
        }

        return $apiCandidates;
    }

    private function resolvePlainApiKey(string $key): string
    {
        $key = trim($key);
        if ($key === '') {
            return '';
        }
        if (Str::startsWith($key, 'eyJpdiI6')) {
            try {
                $decrypted = Crypt::decryptString($key);
                if (is_string($decrypted) && trim($decrypted) !== '') {
                    return trim($decrypted);
                }
            } catch (\Throwable $e) {
            }
        }

        return $key;
    }

    private function normalizeProvider(string $provider): string
    {
        $provider = strtolower(trim($provider));
        if ($provider === '' || Str::contains($provider, 'openai') || Str::contains($provider, 'gpt')) {
            return 'openai';
        }
        if (Str::contains($provider, 'gemini') || Str::contains($provider, 'google')) {
            return 'gemini';
        }
        if (Str::contains($provider, 'anthropic') || Str::contains($provider, 'claude')) {
            return 'anthropic';
        }
        if (Str::contains($provider, 'groq') || Str::contains($provider, 'llama')) {
            return 'groq';
        }

        return 'openai';
    }

    private function extractOpenAiLikeContent(array $payload): string
    {
        $content = data_get($payload, 'choices.0.message.content');
        if (is_string($content) && trim($content) !== '') {
            return trim($content);
        }

        if (is_array($content)) {
            $combined = collect($content)
                ->map(fn ($part) => is_array($part) ? (string) ($part['text'] ?? $part['content'] ?? '') : (string) $part)
                ->filter(fn ($chunk) => trim($chunk) !== '')
                ->implode("\n");

            if (trim($combined) !== '') {
                return trim($combined);
            }
        }

        foreach ([data_get($payload, 'choices.0.text'), data_get($payload, 'output_text'), data_get($payload, 'output.0.content.0.text')] as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        return '';
    }

    private function compareApplicantsByProvider(
        string $provider,
        string $apiKey,
        string $jobTitle,
        string $jobProfile,
        array $applicants,
        array $jobSkillNames,
        array $jobLocationIds
    ): array {
        $provider = $this->normalizeProvider($provider);
        $systemPrompt = 'You are an expert technical recruiter. Return strict JSON only. '
            .'Schema: {"matches":[{"application_id":number,"match_score":number,"rank_tier":"best|better|good|not_match","summary":string,"strengths":[string],"gaps":[string]}]}. '
            .'Rules: match_score is integer 0-100. Include all application_ids. '
            .'Scoring must prioritize in order: skill overlap, relevant experience, location fit. '
            .'Do not score based on whether a resume/attachment is present or missing. '
            .'rank_tier mapping: best>=95, better>=75, good>=60.';

        $userPayload = [
            'job_title' => $jobTitle,
            'job_profile' => $jobProfile,
            'required_skills' => $jobSkillNames,
            'job_location_ids' => $jobLocationIds,
            'applicants' => $applicants,
        ];
        $userPrompt = 'Compare applicants against this job profile and rank best matches: '.json_encode($userPayload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $content = '';
        if ($provider === 'openai' || $provider === 'groq') {
            $url = $provider === 'groq' ? 'https://api.groq.com/openai/v1/chat/completions' : 'https://api.openai.com/v1/chat/completions';
            $model = $provider === 'groq' ? env('AI_JOB_GENERATOR_GROQ_MODEL', 'llama-3.1-8b-instant') : env('AI_JOB_GENERATOR_MODEL', 'gpt-5-nano');
            $payload = [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
            ];

            if ($provider === 'openai') {
                $payload['max_completion_tokens'] = 2000;
            } else {
                $payload['temperature'] = 0.2;
                $payload['max_tokens'] = 1600;
            }

            $response = Http::timeout(35)->withToken($apiKey)->post($url, $payload);
            if (! $response->successful()) {
                throw new \RuntimeException((string) data_get($response->json(), 'error.message', 'Provider request failed.'));
            }
            $responsePayload = (array) $response->json();
            $this->recordAiUsage($provider, $model, $responsePayload);
            $content = trim($this->extractOpenAiLikeContent($responsePayload));

            if ($content === '' && $provider === 'openai') {
                $retryPayload = $payload;
                $retryPayload['max_completion_tokens'] = max((int) ($retryPayload['max_completion_tokens'] ?? 2000), 2600);
                $retryResponse = Http::timeout(35)->withToken($apiKey)->post($url, $retryPayload);
                if ($retryResponse->successful()) {
                    $retryPayloadData = (array) $retryResponse->json();
                    $this->recordAiUsage($provider, $model, $retryPayloadData);
                    $content = trim($this->extractOpenAiLikeContent($retryPayloadData));
                }
            }
        } elseif ($provider === 'anthropic') {
            $response = Http::timeout(35)->withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
            ])->post('https://api.anthropic.com/v1/messages', [
                'model' => env('AI_JOB_GENERATOR_ANTHROPIC_MODEL', 'claude-3-5-sonnet-20241022'),
                'max_tokens' => 1600,
                'temperature' => 0.2,
                'system' => $systemPrompt,
                'messages' => [
                    ['role' => 'user', 'content' => $userPrompt],
                ],
            ]);
            if (! $response->successful()) {
                throw new \RuntimeException((string) data_get($response->json(), 'error.message', 'Provider request failed.'));
            }
            $responsePayload = (array) $response->json();
            $this->recordAiUsage($provider, env('AI_JOB_GENERATOR_ANTHROPIC_MODEL', 'claude-3-5-sonnet-20241022'), $responsePayload);
            $content = collect((array) data_get($responsePayload, 'content', []))
                ->map(fn ($part) => is_array($part) && ($part['type'] ?? '') === 'text' ? (string) ($part['text'] ?? '') : '')
                ->implode("\n");
        } else {
            $model = env('AI_JOB_GENERATOR_GEMINI_MODEL', 'gemini-2.0-flash');
            $url = 'https://generativelanguage.googleapis.com/v1beta/models/'.$model.':generateContent?key='.$apiKey;
            $response = Http::timeout(35)->post($url, [
                'contents' => [[
                    'parts' => [[
                        'text' => $systemPrompt."\n\n".$userPrompt,
                    ]],
                ]],
                'generationConfig' => ['temperature' => 0.2],
            ]);
            if (! $response->successful()) {
                throw new \RuntimeException((string) data_get($response->json(), 'error.message', 'Provider request failed.'));
            }
            $responsePayload = (array) $response->json();
            $this->recordAiUsage($provider, $model, $responsePayload);
            $content = (string) data_get($responsePayload, 'candidates.0.content.parts.0.text', '');
        }

        $content = trim($content);
        if (Str::startsWith($content, '```')) {
            $content = preg_replace('/^```(?:json)?\s*/', '', $content) ?? $content;
            $content = preg_replace('/\s*```$/', '', $content) ?? $content;
            $content = trim($content);
        }

        $decoded = json_decode($content, true);
        if (! is_array($decoded) && preg_match('/\{.*\}/s', $content, $matches)) {
            $decoded = json_decode($matches[0], true);
        }
        if (! is_array($decoded)) {
            throw new \RuntimeException('Invalid AI compare response.');
        }

        $matches = $decoded['matches'] ?? $decoded['rankings'] ?? $decoded['results'] ?? $decoded['candidates'] ?? $decoded;
        if (! is_array($matches)) {
            throw new \RuntimeException('AI compare response missing matches.');
        }

        return array_values(array_filter($matches, fn ($row) => is_array($row)));
    }

    private function extractJsonFromAiContent(string $content): array
    {
        $content = trim($content);
        if (Str::startsWith($content, '```')) {
            $content = preg_replace('/^```(?:json)?\s*/', '', $content) ?? $content;
            $content = preg_replace('/\s*```$/', '', $content) ?? $content;
            $content = trim($content);
        }

        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        // Fallback: extract the outermost JSON object boundaries.
        $start = strpos($content, '{');
        $end = strrpos($content, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $decoded = json_decode(substr($content, $start, $end - $start + 1), true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        throw new \RuntimeException('Invalid AI JSON response.');
    }

    private function generateCoverLetterJsonByProvider(
        string $provider,
        string $apiKey,
        string $systemPrompt,
        string $userPrompt
    ): array {
        $provider = $this->normalizeProvider($provider);

        $content = '';
        if ($provider === 'openai' || $provider === 'groq') {
            $url = $provider === 'groq' ? 'https://api.groq.com/openai/v1/chat/completions' : 'https://api.openai.com/v1/chat/completions';
            $model = $provider === 'groq' ? env('AI_JOB_GENERATOR_GROQ_MODEL', 'llama-3.1-8b-instant') : env('AI_JOB_GENERATOR_MODEL', 'gpt-5-nano');
            $payload = [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
            ];

            if ($provider === 'openai') {
                $payload['max_completion_tokens'] = 1300;
                $payload['response_format'] = ['type' => 'json_object'];
            } else {
                $payload['temperature'] = 0.3;
                $payload['max_tokens'] = 900;
            }

            $response = Http::timeout(35)->withToken($apiKey)->post($url, $payload);

            if (! $response->successful()) {
                throw new \RuntimeException((string) data_get($response->json(), 'error.message', 'Provider request failed.'));
            }

            $responsePayload = (array) $response->json();
            $this->recordAiUsage($provider, $model, $responsePayload);
            $content = trim($this->extractOpenAiLikeContent($responsePayload));

            if ($content === '' && $provider === 'openai') {
                $retryPayload = $payload;
                $retryPayload['max_completion_tokens'] = max((int) ($retryPayload['max_completion_tokens'] ?? 1300), 2200);
                $retryResponse = Http::timeout(35)->withToken($apiKey)->post($url, $retryPayload);
                if ($retryResponse->successful()) {
                    $retryPayloadData = (array) $retryResponse->json();
                    $this->recordAiUsage($provider, $model, $retryPayloadData);
                    $content = trim($this->extractOpenAiLikeContent($retryPayloadData));
                }
            }
        } elseif ($provider === 'anthropic') {
            $response = Http::timeout(35)->withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
            ])->post('https://api.anthropic.com/v1/messages', [
                'model' => env('AI_JOB_GENERATOR_ANTHROPIC_MODEL', 'claude-3-5-sonnet-20241022'),
                'max_tokens' => 1000,
                'temperature' => 0.3,
                'system' => $systemPrompt,
                'messages' => [
                    ['role' => 'user', 'content' => $userPrompt],
                ],
            ]);

            if (! $response->successful()) {
                throw new \RuntimeException((string) data_get($response->json(), 'error.message', 'Provider request failed.'));
            }

            $responsePayload = (array) $response->json();
            $this->recordAiUsage($provider, env('AI_JOB_GENERATOR_ANTHROPIC_MODEL', 'claude-3-5-sonnet-20241022'), $responsePayload);
            $content = collect((array) data_get($responsePayload, 'content', []))
                ->map(fn ($part) => is_array($part) && ($part['type'] ?? '') === 'text' ? (string) ($part['text'] ?? '') : '')
                ->implode("\n");
        } else {
            $model = env('AI_JOB_GENERATOR_GEMINI_MODEL', 'gemini-2.0-flash');
            $url = 'https://generativelanguage.googleapis.com/v1beta/models/'.$model.':generateContent?key='.$apiKey;

            $response = Http::timeout(35)->post($url, [
                'contents' => [[
                    'parts' => [[
                        'text' => $systemPrompt."\n\n".$userPrompt,
                    ]],
                ]],
                'generationConfig' => ['temperature' => 0.3],
            ]);

            if (! $response->successful()) {
                throw new \RuntimeException((string) data_get($response->json(), 'error.message', 'Provider request failed.'));
            }

            $responsePayload = (array) $response->json();
            $this->recordAiUsage($provider, $model, $responsePayload);
            $content = (string) data_get($responsePayload, 'candidates.0.content.parts.0.text', '');
        }

        $decoded = $this->extractJsonFromAiContent($content);
        if (! is_array($decoded)) {
            throw new \RuntimeException('AI response did not decode to JSON.');
        }

        return $decoded;
    }

    private function normalizePhoneForForm(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', trim($phone)) ?? '';

        // Common India format normalization: +91XXXXXXXXXX -> XXXXXXXXXX
        if (Str::startsWith($digits, '91') && strlen($digits) === 12) {
            return substr($digits, 2);
        }

        return $digits;
    }

    private function getApplicationSkillNames(JobApplication $application): array
    {
        return collect((array) $application->skills)
            ->map(function ($skillId) {
                $skill = Skill::find((int) $skillId);

                return $skill?->name;
            })
            ->filter()
            ->map(fn ($name) => trim((string) $name))
            ->values()
            ->all();
    }

    private function getJobSkillNames(Job $job): array
    {
        $skillIds = $job->skills()->pluck('skill_id')->map(fn ($id) => (int) $id)->all();

        if (empty($skillIds)) {
            return [];
        }

        return Skill::query()->whereIn('id', $skillIds)->pluck('name')
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->values()
            ->all();
    }

    private function inferExperienceYears(JobApplication $application): int
    {
        $text = Str::lower($this->buildRawCandidateExperienceText($application));
        preg_match_all('/(\d+)\s*(\+)?\s*(year|years|yr|yrs)/', $text, $matches);
        if (empty($matches[1])) {
            return 0;
        }

        return (int) max(array_map(fn ($value) => (int) $value, $matches[1]));
    }

    private function buildRawCandidateExperienceText(JobApplication $application): string
    {
        $answers = JobApplicationAnswer::query()
            ->where('job_application_id', $application->id)
            ->whereNotNull('answer')
            ->pluck('answer')
            ->implode(' ');

        return trim((string) $application->cover_letter.' '.$answers);
    }

    private function scoreToTier(int $score): string
    {
        if ($score >= 95) {
            return 'best';
        }
        if ($score >= 75) {
            return 'better';
        }
        if ($score >= 60) {
            return 'good';
        }

        return 'not_match';
    }

    public function archiveJobApplication(Request $request, JobApplication $application)
    {
        abort_if(! $this->user->cans('delete_job_applications'), 403);

        $application->delete();

        return Reply::success(__('messages.applicationArchivedSuccessfully'));
    }

    public function unarchiveJobApplication(Request $request, $application_id)
    {
        abort_if(! $this->user->cans('delete_job_applications'), 403);

        $application = JobApplication::select('id', 'deleted_at')->withTrashed()->where('id', $application_id)->first();

        $application->restore();

        return Reply::success(__('messages.applicationUnarchivedSuccessfully'));
    }

    public function addSkills(Request $request, $applicationId)
    {
        abort_if(! $this->user->cans('edit_job_applications'), 403);

        $application = JobApplication::withTrashed()->findOrFail($applicationId);

        $skillIds = [];

        foreach ((array) $request->skills as $val) {
            if (str_starts_with((string) $val, 'new:')) {
                $name  = trim(substr($val, 4));
                $skill = Skill::firstOrCreate(['name' => $name]);
                $skillIds[] = $skill->id;
            } else {
                $skillIds[] = $val;
            }
        }

        $application->skills = $skillIds;
        $application->save();

        return Reply::success(__('messages.skillsSavedSuccessfully'));
    }

    public function loadMore(Request $request)
    {

        $startDate = ($request->startDate != 'null') ? Carbon::parse($request->startDate)->toDateString() : null;
        $endDate = ($request->endDate != 'null') ? Carbon::parse($request->endDate)->toDateString() : null;
        $skip = $request->currentTotalRecords;

        $totalRecord = $request->totalRecord;

        $this->currentDate = Carbon::now()->timestamp;

        $applications = JobApplication::with(['status', 'job.category', 'schedule'])->select('job_applications.*')
            ->where('status_id', $request->columnId);
        $applications = $applications->where('job_applications.is_candidate', 0);
        if ($startDate !== null && $request->startDate != 'null' && $request->startDate != '') {
            $applications = $applications->where(DB::raw('DATE(job_applications.`created_at`)'), '>=', $startDate);
        } else {
        }

        if ($endDate !== null && $request->endDate != 'null' && $request->endDate != '') {
            $applications = $applications->where(DB::raw('DATE(job_applications.`created_at`)'), '<=', $endDate);
        } else {
        }

        // Filter By jobs
        if ($request->jobs != 'all' && $request->jobs != '') {
            $applications = $applications->where('job_applications.job_id', $request->jobs);
        }

        // Filter By company
        if ($request->company != 'all' && $request->company != '') {
            $applications = $applications->join('jobs', 'jobs.id', 'job_applications.job_id')
                ->where('jobs.company_id', '=', $request->company);
        }

        // Filter by EndDate
        if ($request->search != null && $request->search != '') {
            $applications = $applications->where('full_name', 'LIKE', '%'.$request->search.'%')
                ->orWhere('email', 'LIKE', '%'.$request->search.'%')
                ->orWhere('phone', 'LIKE', '%'.$request->search.'%');
        }

        // Filter  by Location
        if ($request->location != 'all' && $request->location != '') {
            $applications->leftJoin('jobs', 'jobs.id', 'job_applications.job_id')
                ->where('jobs.location_id', '=', $request->location);
        }

        if ($request->questions != 'all' && $request->questions != '') {

            $applications->join('job_questions', 'job_questions.job_id', 'job_applications.job_id')
                ->where('job_questions.question_id', '=', $request->questions);
        }

        if ($request->question_value != '' && $request->questions != 'all' && $request->questions != '') {

            $applications->join('job_application_answers', 'job_application_answers.job_application_id', 'job_applications.id')
                ->where('job_application_answers.question_id', $request->questions)
                ->where('job_application_answers.answer', 'LIKE', '%'.$request->question_value.'%');
        }

        // Filter by skills
        if ($request->skill != 'all' && $request->skill != '') {
            foreach (explode(',', $request->skill) as $key => $skill) {
                if ($key == 0) {
                    $applications->whereJsonContains('skills', $skill);
                } else {
                    $applications->orWhereJsonContains('skills', $skill);
                }
            }
        }

        $applications->orderBy('column_priority')->skip($skip)->take($this->perPage);
        $applications = $applications->get();

        $this->applications = $applications;

        if ($totalRecord <= ($skip + $this->perPage)) {
            $loadStatus = 'hide';
        } else {
            $loadStatus = 'show';
        }

        $view = view('admin.job-applications.load_more', $this->data)->render();

        return Reply::dataOnly(['view' => $view, 'load_more' => $loadStatus]);
    }

        public function getJobs(Request $request)
    {
        $companyId  = $request->companyId;
        $locationId = $request->locationId;

        $query = Job::query();

        if ($companyId && $companyId != 'all' && $companyId != '') {
            $query->where('company_id', $companyId);
        }

        if ($locationId && $locationId != 'all' && $locationId != '') {
            $query->where(function ($locationQuery) use ($locationId) {
                $locationQuery->whereIn('id', function ($sub) use ($locationId) {
                    $sub->select('job_id')
                        ->from('job_job_locations')
                        ->where('location_id', $locationId);
                })->orWhereIn('id', function ($sub) use ($locationId) {
                    $sub->select('job_id')
                        ->from('job_applications')
                        ->where('is_candidate', 0)
                        ->where('location_id', $locationId);
                });
            });
        }

        $jobs = $query->get();

        $html = '<option value="all">'.__('modules.jobApplication.allJobs').'</option>';
        foreach ($jobs as $job) {
            $html .= '<option title="'.ucfirst($job->title).'" value="'.$job->id.'">'.ucfirst($job->title).'</option>';
        }

        return Reply::dataOnly(['jobs' => $html]);
    }

        public function getLocations(Request $request)
    {
        $companyId = $request->input('companyId');

        // If specific company selected, only get locations linked to that company's jobs
        if ($companyId && $companyId !== 'all' && $companyId !== '') {
            $mappedLocationIds = DB::table('job_job_locations')
                ->join('jobs', 'jobs.id', '=', 'job_job_locations.job_id')
                ->where('jobs.company_id', $companyId)
                ->distinct()
                ->pluck('job_job_locations.location_id');

            $applicationLocationIds = DB::table('job_applications')
                ->join('jobs', 'jobs.id', '=', 'job_applications.job_id')
                ->where('jobs.company_id', $companyId)
                ->where('job_applications.is_candidate', 0)
                ->whereNotNull('job_applications.location_id')
                ->distinct()
                ->pluck('job_applications.location_id');

            $locationIds = $mappedLocationIds
                ->merge($applicationLocationIds)
                ->filter()
                ->unique()
                ->values();

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
        public function parseSkills(Request $request, $id)
    {
        \Log::info('parseSkills called for id: ' . $id);

        $application = JobApplication::with('job')->findOrFail($id);

        try {
            // Read resume file directly from disk
            $doc = $application->documents()->where('name', 'Resume')->first();
            if (!$doc || empty($doc->hashname)) {
                return response()->json(['status' => 'error', 'message' => 'No resume found for this applicant.']);
            }

            $filePath = tenant_upload_path('documents/' . $application->id . '/' . $doc->hashname);
            if (!is_readable($filePath)) {
                return response()->json(['status' => 'error', 'message' => 'Resume file not found on disk.']);
            }

            $ext = strtolower(pathinfo($doc->hashname, PATHINFO_EXTENSION)) ?: 'pdf';

            // Extract text from file
            $extractor = new \App\Services\ResumeTextExtractor();
            $cvText    = $extractor->extractFromPath($filePath, $ext);

            if (empty($cvText)) {
                return response()->json([
                    'status'         => 'success',
                    'matched_skills' => [],
                    'new_skills'     => [],
                    'warning'        => 'Could not read text from this resume (it may be a scanned or image-based PDF).',
                ]);
            }

            // Save to cv_text for AI search indexing
            if (empty($application->cv_text)) {
                $application->cv_text = mb_substr($cvText, 0, 65000);
                $application->save();
            }

            $allSkillNames = mb_substr(\App\Skill::pluck('name')->implode(', '), 0, 2000);

            $prompt = "You are a skill extractor. Read this resume and extract ALL skills mentioned anywhere including technical skills, software, tools, programming languages, soft skills.\n\n"
                . "Compare each skill against this list: [{$allSkillNames}]\n\n"
                . "Resume text:\n" . mb_substr($cvText, 0, 4000) . "\n\n"
                . "You MUST respond with ONLY a raw JSON object, no explanation, no markdown, no backticks:\n"
                . "{\"matched\": [\"exact name from list\"], \"new\": [\"skills not in list\"]}\n\n"
                . "If no skills found still return: {\"matched\": [], \"new\": []}";

            \Log::info('parseSkills calling DeepSeek');
            $text   = $this->callDeepSeek($prompt);
            $parsed = json_decode($text, true);

            \Log::info('parseSkills parsed: ' . json_encode($parsed));

            $matchedSkills = \App\Skill::whereIn('name', $parsed['matched'] ?? [])->get(['id', 'name']);

            return response()->json([
                'status'         => 'success',
                'matched_skills' => $matchedSkills,
                'new_skills'     => $parsed['new'] ?? [],
            ]);

        } catch (\Throwable $e) {
            \Log::error('parseSkills error: ' . $e->getMessage() . ' on line ' . $e->getLine() . ' in ' . $e->getFile());
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage() . ' (line ' . $e->getLine() . ')',
            ]);
        }
    }

    /**
     * Send a prompt to DeepSeek API and return the response text.
     * Uses the OpenAI-compatible chat completions endpoint.
     */
   private function callDeepSeek(string $prompt): string
    {
        $key   = config('services.deepseek.key');
        $model = config('services.deepseek.model', 'deepseek-chat');

        if (!$key) {
            throw new \RuntimeException('Configure an active DeepSeek key in AI Settings.');
        }

        $response = \Illuminate\Support\Facades\Http::timeout(45)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $key,
                'Content-Type'  => 'application/json',
            ])
            ->post('https://api.deepseek.com/chat/completions', [
                'model'       => $model,
                'max_tokens'  => 4000,
                'temperature' => 0.1,
                'messages'    => [
                    ['role' => 'system', 'content' => 'You are a precise CV data extraction engine. You only output valid JSON.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException('DeepSeek error: HTTP ' . $response->status() . ' — ' . $response->body());
        }

        $text = $response->json('choices.0.message.content') ?? '';

        // Strip markdown code fences
        $text = preg_replace('/^```(?:json)?\s*/i', '', trim($text)) ?? trim($text);
        $text = preg_replace('/\s*```\s*$/', '', $text) ?? $text;
        $text = trim($text);

        return $text;
    }
  
    private function parseCvStructured(string $cvText): ?array
    {
        $schema = '{"personal":{"name":"","email":"","phone":"","location":{"city":"","province":"","country":""}},"headline":"","total_experience":{"years":0,"months":0},"job_titles":[],"skills":[],"certifications":[],"education":[{"degree":"","field":"","school":""}],"employment":[{"company":"","title":"","start":"","end":"","duration_years":0}],"languages":[],"availability":{"notice_period":""},"resume_summary":""}';

        $prompt = "You are a CV parser API. Your ONLY output must be a single valid JSON object.\n\n"
            . "STRICT RULES:\n"
            . "1. Return ONLY raw JSON — no markdown fences (```), no explanations, no extra text\n"
            . "2. Match this exact schema structure:\n" . $schema . "\n"
            . "3. Use empty string \"\" for unknown fields\n"
            . "4. job_titles: array of ALL job titles from employment history\n"
            . "5. skills: array of ALL technical and soft skills found anywhere\n"
            . "6. total_experience: calculate from employment dates\n"
            . "7. personal.location: extract city, province/state, country\n"
            . "8. resume_summary: 2-3 sentence professional summary\n\n"
            . "CV TEXT TO PARSE:\n" . mb_substr($cvText, 0, 12000);

        try {
            \Log::info('parseCvStructured: Calling DeepSeek with ' . strlen($cvText) . ' chars');

            $text = $this->callDeepSeek($prompt);

            \Log::info('parseCvStructured: Raw response (first 500 chars): ' . mb_substr($text, 0, 500));

            // Strip any markdown fences that DeepSeek might add despite instructions
            $text = preg_replace('/^```(?:json)?\s*/i', '', trim($text)) ?? trim($text);
            $text = preg_replace('/\s*```\s*$/', '', $text) ?? $text;
            $text = trim($text);

            // Try direct decode
            $parsed = json_decode($text, true);

            // If that fails, try extracting JSON from the text
            if (!is_array($parsed)) {
                \Log::info('parseCvStructured: Direct decode failed, trying regex extraction');
                if (preg_match('/\{.*\}/s', $text, $matches)) {
                    $parsed = json_decode($matches[0], true);
                    \Log::info('parseCvStructured: Regex extracted JSON, decode result: ' . (is_array($parsed) ? 'success' : 'fail'));
                }
            }

            if (!is_array($parsed)) {
                \Log::warning('parseCvStructured: Could not decode JSON. Raw: ' . mb_substr($text, 0, 300));
                return null;
            }

            // Validate minimum required structure
            if (!isset($parsed['personal']) || !is_array($parsed['personal'])) {
                \Log::warning('parseCvStructured: Missing personal section. Keys: ' . implode(', ', array_keys($parsed)));
                return null;
            }

            // Ensure arrays exist even if empty
            $parsed['job_titles']    = (array) ($parsed['job_titles'] ?? []);
            $parsed['skills']         = (array) ($parsed['skills'] ?? []);
            $parsed['certifications'] = (array) ($parsed['certifications'] ?? []);
            $parsed['education']      = (array) ($parsed['education'] ?? []);
            $parsed['employment']     = (array) ($parsed['employment'] ?? []);
            $parsed['languages']      = (array) ($parsed['languages'] ?? []);

            \Log::info('parseCvStructured: SUCCESS — Name: ' . ($parsed['personal']['name'] ?? 'N/A')
                . ', Jobs: ' . count($parsed['job_titles'])
                . ', Skills: ' . count($parsed['skills']));

            return $parsed;

        } catch (\Throwable $e) {
            \Log::error('parseCvStructured: Exception: ' . $e->getMessage());
            return null;
        }
    }
    /**
     * Extract plain text from a downloaded resume file and save it to cv_text.
     * Called after parseSkills so the file bytes are already in memory.
     */
    private function saveCvTextFromBytes(JobApplication $application, string $fileBytes, string $ext): bool
    {
        try {
            $tmpPath = sys_get_temp_dir() . '/cv_idx_' . $application->id . '_' . time() . '.' . $ext;
            file_put_contents($tmpPath, $fileBytes);
            try {
                $extractor = new \App\Services\ResumeTextExtractor();
                $text = $extractor->extractFromPath($tmpPath, $ext);
                if ($text !== '') {
                    JobApplication::where('id', $application->id)
                        ->update(['cv_text' => mb_substr($text, 0, 65000)]);
                    return true;
                }
                return false;
            } finally {
                if (file_exists($tmpPath)) @unlink($tmpPath);
            }
        } catch (\Throwable $e) {
            \Log::warning('saveCvTextFromBytes failed for app ' . $application->id . ': ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Bulk index CV text for all applicants that have a resume but no cv_text yet.
     * Processes up to 20 at a time to avoid timeout.
     */
    public function indexCvs(Request $request)
{
    abort_if(!$this->user->cans('view_job_applications'), 403);

    $limit = min((int) $request->input('limit', 20), 50);

    $processed = 0;
    $failed = 0;
    $parsedCount = 0;
    $parseFailed = 0;

    /*
    |--------------------------------------------------------------------------
    | STEP 1 - Extract CV Text
    |--------------------------------------------------------------------------
    */
    $needsText = JobApplication::where(function ($q) {
            $q->whereNull('cv_text')
            ->orWhere('cv_text', '');
        })
        ->where('cv_index_failed', 0)          // ← ADD: skip permanently-failed rows
        ->whereHas('documents', function ($q) {
            $q->where('name', 'Resume');
        })
        ->limit($limit)
        ->get();

     foreach ($needsText as $app) {
        try {
            $doc = $app->documents()->where('name', 'Resume')->first();
            if (!$doc || empty($doc->hashname)) {
                $app->update(['cv_index_failed' => true, 'cv_indexed_at' => now()]); // ← ADD
                $failed++;
                continue;
            }

            $file = tenant_upload_path('documents/'.$app->id.'/'.$doc->hashname);
            if (!is_readable($file)) {
                $app->update(['cv_index_failed' => true, 'cv_indexed_at' => now()]); // ← ADD
                $failed++;
                continue;
            }

            $ext = strtolower(pathinfo($doc->hashname, PATHINFO_EXTENSION));
            if (!in_array($ext, ['pdf','doc','docx','txt','rtf','xls','xlsx'])) {
                $ext = 'pdf';
            }

            $bytes = file_get_contents($file);
            if (!$bytes) {
                $app->update(['cv_index_failed' => true, 'cv_indexed_at' => now()]); // ← ADD
                $failed++;
                continue;
            }

            $saved = $this->saveCvTextFromBytes($app, $bytes, $ext); // ← now returns bool

            if ($saved) {
                $processed++;
            } else {
                $app->update(['cv_index_failed' => true, 'cv_indexed_at' => now()]); // ← ADD
                $failed++;
            }

        } catch (\Throwable $e) {
            \Log::warning('CV text extraction failed for '.$app->id.' : '.$e->getMessage());
            $app->update(['cv_index_failed' => true, 'cv_indexed_at' => now()]); // ← ADD
            $failed++;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | STEP 2 - Parse CV using AI
    |--------------------------------------------------------------------------
    */
    $needsParse = JobApplication::whereNotNull('cv_text')
        ->where('cv_text','!=','')
        ->whereNull('parsed_cv_data')
        ->where('cv_index_failed',0)
        ->limit($limit)
        ->get();

    foreach ($needsParse as $app) {

        try {

            $data = $this->parseCvStructured($app->cv_text);

            if (!$data) {

                $app->update([
                    'cv_index_failed' => true,
                    'cv_indexed_at'   => now(),
                ]);

                $parseFailed++;
                continue;
            }

            $years =
                (float)($data['total_experience']['years'] ?? 0)
                +
                (
                    (float)($data['total_experience']['months'] ?? 0)
                    / 12
                );

            $location = array_filter([
                $data['personal']['location']['city'] ?? null,
                $data['personal']['location']['province'] ?? null,
                $data['personal']['location']['country'] ?? null,
            ]);

            $app->update([

                'parsed_cv_data' => json_encode($data),

                'cv_experience_years' => round($years,1),

                'cv_job_titles' => implode(', ',
                    (array)($data['job_titles'] ?? [])
                ),

                'cv_skills_text' => implode(', ',
                    (array)($data['skills'] ?? [])
                ),

                'cv_location_text' => implode(', ', $location),

                'cv_indexed_at' => now(),

                'cv_index_failed' => false,

            ]);

            $parsedCount++;

        } catch (\Throwable $e) {

            \Log::warning(
                'CV parse failed for '.$app->id.' : '.$e->getMessage()
            );

            $app->update([
                'cv_index_failed' => true,
                'cv_indexed_at'   => now(),
            ]);

            $parseFailed++;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Remaining
    |--------------------------------------------------------------------------
    */
    $remainingText = JobApplication::where(function ($q) {
            $q->whereNull('cv_text')
              ->orWhere('cv_text','');
        })
        ->where('cv_index_failed', 0)  
        ->whereHas('documents', function ($q) {
            $q->where('name','Resume');
        })
        ->count();

    $remainingParse = JobApplication::whereNotNull('cv_text')
        ->where('cv_text','!=','')
        ->whereNull('parsed_cv_data')
        ->where('cv_index_failed',0)
        ->count();

    return Reply::dataOnly([

        'processed' => $processed + $parsedCount,

        'failed' => $failed + $parseFailed,

        'remaining' => $remainingText + $remainingParse,

    ]);
}
    public function assignJob(Request $request, $id)
    {
        abort_if(! $this->user->cans('edit_job_applications'), 403);

        $request->validate(['job_id' => 'required|exists:jobs,id']);

        $application = JobApplication::with('job:id,title')->findOrFail($id);

        $newJobId = (int) $request->job_id;
        $oldJobId = (int) $application->job_id;

        // No-op — nothing to log or save
        if ($oldJobId === $newJobId) {
            return Reply::success('Job assigned successfully.');
        }

        $oldJobTitle = $application->job?->title ?? 'Unassigned';
        $newJobTitle = Job::find($newJobId)?->title ?? 'Unknown job';

        $application->job_id = $newJobId;
        $application->save();

        // Log into the History tab. Uses the applicant's current status for
        // both from/to (same trick as toggleMarketing) so the FK is satisfied
        // without needing a schema change — the notes field carries the message.
        if ($application->status_id) {
            \App\JobApplicationStatusHistory::create([
                'job_application_id' => $application->id,
                'from_status_id'     => $application->status_id,
                'to_status_id'       => $application->status_id,
                'user_id'            => $this->user->id,
                'notes'              => 'Switched job from "' . ucwords($oldJobTitle) . '" to "' . ucwords($newJobTitle) . '"',
            ]);
        }

        return Reply::success('Job assigned successfully.');
    }
    public function bulkParseResume(Request $request)
    {
        abort_if(! $this->user->cans('add_job_applications'), 403);

        if (!$request->hasFile('resume')) {
            return response()->json(['status' => 'error', 'message' => 'No file uploaded.']);
        }

        try {
            $file = $request->file('resume');

            // Extract text from the uploaded file (returns empty string on parse errors)
            $extractor = new \App\Services\ResumeTextExtractor();
            try {
                $cvText = $extractor->extract($file);
            } catch (\InvalidArgumentException $e) {
                return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
            }

            // If text extraction failed (malformed/scanned PDF), return empty result
            // so the user can fill in the form manually
           if (empty($cvText)) {
                return response()->json([
                    'status'         => 'success',
                    'full_name'      => '',
                    'email'          => '',
                    'phone'          => '',
                    'address'        => '',
                    'matched_skills' => [],
                    'new_skills'     => [],
                    'parsed_cv_data' => null,   // NEW
                    'resume_text'    => '',     // NEW
                    'warning'        => 'Could not read text from this file (it may be a scanned or image-based PDF). Please fill in the details manually.',
                ]);
            }
        

            $allSkillNames = mb_substr(\App\Skill::pluck('name')->implode(', '), 0, 2000);

            $prompt = "You are a CV parser. Extract the following fields from this resume text and extract skills.\n\n"
                . "Skills list to match against: [{$allSkillNames}]\n\n"
                . "Resume text:\n" . mb_substr($cvText, 0, 4000) . "\n\n"
                . "Respond ONLY with a raw JSON object, no explanation, no markdown, no backticks:\n"
                . "{\"full_name\": \"\", \"email\": \"\", \"phone\": \"\", \"address\": \"\", \"matched_skills\": [\"exact name from list\"], \"new_skills\": [\"skills not in list\"]}\n\n"
                . "Rules:\n"
                . "- full_name: person's full name in Title Case\n"
                . "- email: email address or empty string\n"
                . "- phone: phone number or empty string\n"
                . "- address: city/state/country or full address if found, or empty string\n"
                . "- matched_skills: skills that exactly match the provided list\n"
                . "- new_skills: skills found in CV but NOT in the list\n"
                . "- If a field is not found, use empty string or empty array";

            $text   = $this->callDeepSeek($prompt);
            $parsed = json_decode($text, true);

            if (!$parsed) {
                return response()->json(['status' => 'error', 'message' => 'AI could not parse the CV.']);
            }

            $matchedSkills = \App\Skill::whereIn('name', $parsed['matched_skills'] ?? [])->get(['id', 'name']);

            $structuredCvData = $this->parseCvStructured($cvText);

            return response()->json([
                'status'         => 'success',
                'full_name'      => $parsed['full_name']     ?? '',
                'email'          => $parsed['email']         ?? '',
                'phone'          => $parsed['phone']         ?? '',
                'address'        => $parsed['address']       ?? '',
                'matched_skills' => $matchedSkills,
                'new_skills'     => $parsed['new_skills']    ?? [],
                'parsed_cv_data' => $structuredCvData,          // NEW
                'resume_text'    => mb_substr($cvText, 0, 65000), // NEW
            ]);

        } catch (\Throwable $e) {
            \Log::error('bulkParseResume error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
    public function updateBasicInfo(Request $request, $id)
    {
        abort_if(!$this->user->cans('edit_job_applications'), 403);

        $request->validate([
            'full_name' => 'required|string|max:191',
            'email'     => 'required|email|max:191',
            'phone'     => 'nullable|string|max:50',
        ]);

        $application = JobApplication::findOrFail($id);
        $application->full_name = collect(explode(' ', trim($request->full_name)))
            ->map(fn($w) => ucfirst(strtolower($w)))->join(' ');
        $application->email = $request->email;
        $application->phone = $request->phone;
        $application->save();

        return Reply::successWithData(__('messages.updatedSuccessfully'), [
            'full_name' => $application->full_name,
            'email'     => $application->email,
            'phone'     => $application->phone,
        ]);
    }
    public function aiSearchPage()
    {
        abort_if(!$this->user->cans('view_job_applications'), 403);
        return view('admin.ai-search.index', $this->data);
    }

    /** Send a plain-text email to applicants selected from AI search results. */
    public function sendAiSearchEmail(Request $request)
    {
        abort_if(!$this->user->cans('view_job_applications'), 403);

        $data = $request->validate([
            'applicant_ids' => ['required', 'array', 'min:1', 'max:100'],
            'applicant_ids.*' => ['integer', 'distinct', 'exists:job_applications,id'],
            'subject' => ['required', 'string', 'max:191'],
            'message' => ['required', 'string', 'max:10000'],
        ]);

        $applications = JobApplication::whereIn('id', $data['applicant_ids'])
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->get(['id', 'full_name', 'email']);

        if ($applications->isEmpty()) {
            return Reply::error('None of the selected applicants has an email address.');
        }

        // Switch only this request to the dedicated AI Search SMTP configuration.
        // Reading from config (rather than env here) also works with config caching.
        $aiSearchMail = config('mail.ai_search_smtp');
        if (empty($aiSearchMail['host']) || empty($aiSearchMail['username']) || empty($aiSearchMail['password']) || empty($aiSearchMail['from']['address'])) {
            return Reply::error('AI Search email is not configured. Please add the Zoho SMTP credentials in the environment settings.');
        }

        $originalConfig = [
            'driver' => config('mail.driver'),
            'host' => config('mail.host'),
            'port' => config('mail.port'),
            'encryption' => config('mail.encryption'),
            'username' => config('mail.username'),
            'password' => config('mail.password'),
            'from' => config('mail.from'),
        ];

        config([
            'mail.driver' => 'smtp',
            'mail.host' => $aiSearchMail['host'],
            'mail.port' => $aiSearchMail['port'],
            'mail.encryption' => $aiSearchMail['encryption'],
            'mail.username' => $aiSearchMail['username'],
            'mail.password' => $aiSearchMail['password'],
            'mail.from' => $aiSearchMail['from'],
        ]);

        // Force the mailer to rebuild with the new config
        app()->forgetInstance('mailer');
        app()->forgetInstance('swift.mailer');
        app()->forgetInstance('swift.transport');

        $sent = 0;
        $failed = 0;
        foreach ($applications->unique('email') as $application) {
            try {
                $applicantName = $application->full_name ?: 'Applicant';
                $personalizedMessage = str_ireplace(
                    ['{{applicant_name}}', '[applicant_name]', '%applicant_name%'],
                    $applicantName,
                    $data['message']
                );

                $messageId = 'ats-'.\Illuminate\Support\Str::uuid().'@'.(explode('@', $aiSearchMail['from']['address'])[1] ?? 'localhost');
                Mail::html(
                    \App\Services\CandidateEmailBody::render($personalizedMessage, $this->user->email_signature, $this->user->email_signature_image_url, $this->user->email_signature_html),
                    function ($mail) use ($application, $data, $messageId) {
                        $mail->to($application->email, $application->full_name)
                            ->subject($data['subject']);
                        $mail->getSymfonyMessage()->getHeaders()->addIdHeader('Message-ID', $messageId);
                    }
                );
                CandidateEmailMessage::create([
                    'job_application_id' => $application->id,
                    'user_id' => $this->user->id,
                    'direction' => 'outbound',
                    'from_address' => $aiSearchMail['from']['address'],
                    'to_address' => $application->email,
                    'subject' => $data['subject'], 'message_id' => $messageId,
                    'body' => $personalizedMessage,
                    'received_at' => now(),
                ]);
                $sent++;
            } catch (\Throwable $e) {
                $failed++;
                Log::warning('AI search applicant email failed', [
                    'application_id' => $application->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // --- Restore original mail config ---
        config([
            'mail.driver' => $originalConfig['driver'],
            'mail.host' => $originalConfig['host'],
            'mail.port' => $originalConfig['port'],
            'mail.encryption' => $originalConfig['encryption'],
            'mail.username' => $originalConfig['username'],
            'mail.password' => $originalConfig['password'],
            'mail.from' => $originalConfig['from'],
        ]);
        app()->forgetInstance('mailer');
        app()->forgetInstance('swift.mailer');
        app()->forgetInstance('swift.transport');
 
        if ($sent === 0) {
            return Reply::error('Emails could not be sent. Please check the mail settings and try again.');
        }

        $message = "Email sent to {$sent} applicant".($sent === 1 ? '' : 's').'.';
        if ($failed > 0) {
            $message .= " {$failed} email".($failed === 1 ? '' : 's').' failed.';
        }

        return Reply::success($message);
    }

    /**
     * Parse a natural-language search query using Ollama (local AI).
     * Falls back to keyword passthrough if Ollama is unavailable.
     */
    public function aiParseQuery(Request $request)
    {
        abort_if(!$this->user->cans('view_job_applications'), 403);

        $request->validate(['query' => ['nullable', 'string', 'max:500']]);
        $query = CandidateSearchText::normalize((string) $request->input('query', ''));
        if (!$query) {
            return Reply::dataOnly(['skills' => [], 'keywords' => [], 'roles' => [], 'location' => '', 'min_experience' => 0]);
        }

        if (preg_match('/^\d{2,5}[a-z]{1,3}$/', $query)) {
            return Reply::dataOnly(['skills' => [], 'keywords' => [$query], 'roles' => [], 'location' => '', 'min_experience' => 0]);
        }
        $cacheKey = 'candidate-search-parse-v2:'.hash('sha256', $query);
        if ($cached = \Illuminate\Support\Facades\Cache::get($cacheKey)) return Reply::dataOnly($cached);

        $prompt = 'Parse this job candidate search query: "' . $query . "\"\n\n"
            . "Extract:\n"
            . "- skills: technical skills, tools, technologies the candidate would have\n"
            . "- keywords: other relevant keywords from their CV\n"
            . "- roles: job titles / roles\n"
            . "- location: city or region mentioned (empty string if none)\n"
            . "- min_experience: minimum years of experience as a number (0 if not mentioned)\n\n"
            . "Correct obvious spelling mistakes in job titles and skills. Preserve qualification codes exactly. Never invent a location or minimum experience. Include useful role/skill synonyms.\n"
            . "Return ONLY minified JSON, no explanation:\n"
            . '{"skills":["skill1"],"keywords":["kw1"],"roles":["role1"],"location":"toronto","min_experience":5}';

        try {
            $text   = $this->callDeepSeek($prompt);
            $parsed = json_decode($text, true);

            if (!is_array($parsed)) {
                throw new \RuntimeException('Invalid JSON from Ollama');
            }

            $result = [
                'skills'         => (array) ($parsed['skills']         ?? []),
                'keywords'       => (array) ($parsed['keywords']       ?? []),
                'roles'          => (array) ($parsed['roles']          ?? []),
                'location'       => (string) ($parsed['location']      ?? ''),
                'min_experience' => max(0, (int) ($parsed['min_experience'] ?? 0)),
            ];
            foreach (['skills', 'keywords', 'roles'] as $field) $result[$field] = CandidateSearchText::terms($result[$field]);
            $result['location'] = CandidateSearchText::normalize($result['location']);
            \Illuminate\Support\Facades\Cache::put($cacheKey, $result, 600);
            return Reply::dataOnly($result);
        } catch (\Throwable $e) {
            \Log::warning('DeepSeek aiParseQuery failed: ' . $e->getMessage());
            // Graceful fallback — treat raw query as keyword
            return Reply::dataOnly([
                'skills'         => [],
                'keywords'       => [$query],
                'roles'          => [],
                'location'       => '',
                'min_experience' => 0,
                'fallback'       => true,
            ]);
        }
    }



/** Match normalized queries against all candidate evidence before ranking. */
public function aiSearchResults(Request $request)
{
    abort_if(!$this->user->cans('view_job_applications'), 403);

    $request->validate(['query' => ['nullable', 'string', 'max:500'], 'terms' => ['nullable', 'array', 'max:50'], 'terms.*' => ['string', 'max:200'], 'roles' => ['nullable', 'array', 'max:30'], 'roles.*' => ['string', 'max:200'], 'location' => ['nullable', 'string', 'max:200'], 'min_experience' => ['nullable', 'numeric', 'min:0', 'max:100']]);
    $terms = CandidateSearchText::terms((array) $request->input('terms', []));
    $roles = CandidateSearchText::terms((array) $request->input('roles', []));
    $query = CandidateSearchText::normalize((string) $request->input('query', ''));
    $location = CandidateSearchText::normalize((string) $request->input('location', ''));
    $minExp = (float) $request->input('min_experience', 0);
    $requiredCodes = CandidateSearchText::codes($query);
    $searchTerms = CandidateSearchText::terms(array_merge($terms, $roles, [$query]));
    $roleTerms = $roles ?: $searchTerms;

    // Optional: let the front-end explicitly ask to restrict to active applicants only.
    // Defaults to false so AI Search covers your whole candidate pool by default.
    $onlyApplicants = (bool) $request->input('only_applicants', false);

    if (empty($terms) && empty($roles) && empty($query)) {
        return Reply::dataOnly(['results' => []]);
    }
 

    $applicantsQuery = \App\JobApplication::select(
            'job_applications.id',
            'job_applications.full_name',
            'job_applications.skills',
            'job_applications.status_id',
            'job_applications.job_id',
            'job_applications.location_id',
            'job_applications.address',
            'job_applications.city',
            'job_applications.state',
            'job_applications.country',
            'job_applications.cv_text',
            'job_applications.cv_experience_years',
            'job_applications.cv_job_titles',
            'job_applications.cv_skills_text',
            'job_applications.cv_location_text',
            'job_applications.cv_indexed_at',
            'job_applications.is_candidate',
            'job_applications.created_at'
        )
        ->with(['status:id,status,color', 'job:id,title', 'location:id,location'])
        ->whereNull('job_applications.deleted_at')
        ->whereNull('job_applications.moved_to_trash_at');

    if ($onlyApplicants) {
        $applicantsQuery->where('job_applications.is_candidate', 0);
    }

    // Score in chunks before ranking: literal SQL filters would discard typo/spacing matches.
    $applicants = $applicantsQuery->lazyById(250, 'job_applications.id', 'id');
    $allSkillsMap = \App\Skill::pluck('name', 'id');

    $locLower = $location !== '' ? strtolower($location) : '';

    $results = $applicants->map(function ($app) use ($searchTerms, $roleTerms, $allSkillsMap, $locLower, $minExp, $requiredCodes) {
        $score         = 0;
        $matchedSkills = [];
        $allSkills     = [];
        $cvJobTitles   = array_values(array_filter(array_map('trim', explode(',', (string) $app->cv_job_titles))));
        $cvSkillsList  = array_values(array_filter(array_map('trim', explode(',', (string) $app->cv_skills_text))));
        $hasStructured = !empty($app->cv_indexed_at);

        $evidence = implode(' ', array_merge([$app->cv_text ?? '', $app->cv_job_titles ?? '', $app->cv_skills_text ?? '', $app->full_name], array_map(fn ($id) => $allSkillsMap[(int) $id] ?? '', (array) $app->skills)));
        foreach ($requiredCodes as $code) {
            if (!CandidateSearchText::strength($evidence, $code)) return null;
        }

        // ── Role / job title match — strongest signal, up to 45 pts ──
        $roleMatched = false;
        if ($cvJobTitles) {
            foreach ($roleTerms as $term) {
                foreach ($cvJobTitles as $title) {
                    if (($strength = CandidateSearchText::strength($title, $term)) > 0) {
                        $score += 45 * $strength;
                        $roleMatched = true;
                        break 2;
                    }
                }
            }
        }
        // Also check the resume headline text even when structured, and always
        // as a fallback when CV hasn't been AI-indexed yet.
        if (!$roleMatched) {
            foreach ($roleTerms as $term) {
                if (($strength = CandidateSearchText::strength((string) $app->cv_text, $term)) > 0) {
                    $score += 18 * $strength;
                    $roleMatched = true;
                    break;
                }
            }
        }

        // ── Manually-tagged skills (Skill model, applicant-selected) — up to ~24 pts ──
        foreach ((array) $app->skills as $sid) {
            $name = $allSkillsMap[(int) $sid] ?? null;
            if (!$name) continue;
            $allSkills[] = $name;
            foreach ($searchTerms as $term) {
                if (($strength = CandidateSearchText::strength($name, $term)) > 0) {
                    $score += 8 * $strength;
                    if (!in_array($name, $matchedSkills)) $matchedSkills[] = $name;
                    break;
                }
            }
        }

        // ── Parsed CV skills, including existing fields without an index timestamp ──
        if ($cvSkillsList) {
            foreach ($searchTerms as $term) {
                foreach ($cvSkillsList as $skill) {
                    if (($strength = CandidateSearchText::strength($skill, $term)) > 0) {
                        $score += 6 * $strength;
                        if (!in_array($skill, $matchedSkills)) $matchedSkills[] = $skill;
                        $allSkills[] = $skill;
                        break;
                    }
                }
            }
        }

        // ── Name match — light weight, supporting evidence only ──
        foreach ($searchTerms as $term) {
            if (($strength = CandidateSearchText::strength($app->full_name, $term)) > 0) {
                $score += 4 * $strength;
                break;
            }
        }

        // Location/experience must not create a match without relevant candidate evidence.
        if ($score <= 0) return null;

        // ── Location — up to 20 pts, ONLY when a location was actually requested ──
        if ($locLower !== '') {
            $locationHaystack = strtolower(trim(
                ($app->cv_location_text ?? '') . ' ' . $app->city . ' ' . $app->state . ' ' . $app->country
                . ' ' . ($app->location?->location ?? '') . ' ' . $app->address
            ));
            if (CandidateSearchText::strength($locationHaystack, $locLower) > 0) {
                $score += 20;
            } else {
                // Location was explicitly asked for and this person doesn't match it —
                // retain the explicit location restriction, with typo-tolerant matching.
                return null;
            }
        }

        // ── Experience — up to 20 pts, ONLY when a minimum was actually requested ──
        if ($minExp > 0) {
            if ($hasStructured && $app->cv_experience_years !== null) {
                $years = (float) $app->cv_experience_years;
                if ($years >= $minExp) {
                    $score += 20;
                } elseif ($years >= max(0, $minExp - 1)) {
                    $score += 10; // close enough, still show but rank lower
                } else {
                    $score -= 8; // clearly under the bar
                }
            } elseif ($app->cv_text && preg_match_all('/(\d+)\s*\+?\s*(?:year|yr)s?/i', $app->cv_text, $m)) {
                $maxFound = max(array_map('intval', $m[1]));
                if ($maxFound >= $minExp) {
                    $score += 14;
                } elseif ($maxFound >= max(0, $minExp - 1)) {
                    $score += 6;
                } else {
                    $score -= 6;
                }
            }
            // If experience truly can't be determined at all, we neither reward nor
            // punish — the role/skill match still stands on its own.
        }

        // Require *some* real signal — role, skill, or name — to appear at all.
        // Pure location/experience-only "matches" with nothing else are noise.
        if ($score <= 0) {
            return null;
        }

        $score = min(99, max(1, (int) round($score)));

        return [
            'id'             => $app->id,
            'full_name'      => $app->full_name,
            'job_title'      => $cvJobTitles[0] ?? ($app->job?->title ?? '—'),
            'location'       => $app->cv_location_text ?: ($app->location?->location ?? trim("{$app->city}, {$app->state}", ', ')) ?: '—',
            'status'         => ucwords(str_replace('_', ' ', $app->status?->status ?? '—')),
            'status_color'   => $app->status?->color ?? '#6b7280',
            'score'          => $score,
            'matched_skills' => array_values(array_unique($matchedSkills)),
            'all_skills'     => array_values(array_unique($allSkills)),
            'has_cv'         => $hasStructured,
            'is_candidate'   => (bool) $app->is_candidate,
            'created_at'     => $app->created_at?->toDateString(),
        ];
    })
  ->filter()
->collect()
->sort(fn ($a, $b) => ($b['score'] <=> $a['score']) ?: ($b['id'] <=> $a['id']))
->values()
->toArray();

    return Reply::dataOnly(['results' => $results]);
}
    private function logStatusChange(int $jobApplicationId, ?int $fromStatusId, int $toStatusId, ?int $userId = null): void
    {
        if ($fromStatusId === $toStatusId) {
            return; // no-op move, don't log
        }

        \App\JobApplicationStatusHistory::create([
            'job_application_id' => $jobApplicationId,
            'from_status_id'     => $fromStatusId,
            'to_status_id'       => $toStatusId,
            'user_id'            => $userId,
        ]);
    }
    /**
     * Toggle an applicant's "Candidate Marketing" flag on/off.
     * Used by the button on the applicant profile (job-applications/show.blade.php).
     */
       /**
     * Toggle an applicant's "Candidate Marketing" flag on/off.
     */
    public function toggleMarketing(Request $request, $id)
    {
        abort_if(!$this->user->cans('edit_job_applications'), 403);

        $application = JobApplication::withTrashed()->findOrFail($id);
        $turningOn = !$application->is_marketing;
        $application->is_marketing = $turningOn ? 1 : 0;

        if ($turningOn) {
            $application->marketing_added_at = Carbon::now();
            if ($request->filled('marketing_label')) {
                $application->marketing_label = trim($request->marketing_label);
            }
        } else {
            $application->marketing_added_at = null;
        }

        $application->save();

        if ($application->status_id) {
            \App\JobApplicationStatusHistory::create([
                'job_application_id' => $application->id,
                'from_status_id'     => $application->status_id,
                'to_status_id'       => $application->status_id,
                'user_id'            => $this->user->id,
                'notes'              => $turningOn ? 'Added to Candidate Marketing' : 'Removed from Candidate Marketing',
            ]);
        }

        return Reply::successWithData(
            $turningOn ? 'Added to Candidate Marketing.' : 'Removed from Candidate Marketing.',
            [
                'is_marketing'    => $turningOn ? 1 : 0,
                'marketing_label' => (string) $application->marketing_label,
            ]
        );
    }

    /**
     * Save/update just the marketing label without toggling the flag.
     */
    public function updateMarketingLabel(Request $request, $id)
    {
        abort_if(!$this->user->cans('edit_job_applications'), 403);

        $application = JobApplication::withTrashed()->findOrFail($id);
        $application->marketing_label = trim((string) $request->marketing_label);
        $application->save();

        return Reply::success('Label updated.');
    }

    /**
     * Process ONE application at a time for CV text extraction + AI parsing.
     * Stays under Cloudflare's 120s timeout by doing only 1 item per request.
     */
    /**
     * Process ONE application at a time for CV text extraction + AI parsing.
     * Stays under Cloudflare's 120s timeout by doing only 1 item per request.
     */
        /**
     * Process ONE application at a time for CV text extraction + AI parsing.
     * Stays under Cloudflare's 120s timeout by doing only 1 item per request.
     */
    public function bulkParseAllCvs(Request $request)
    {
        abort_if(!$this->user->cans('edit_job_applications'), 403);

        $mode = $request->input('mode', 'auto');
        $dryRun = (bool) $request->input('dry_run', false);

        \Log::info('=== bulkParseAllCvs START === mode=' . $mode . ' dryRun=' . ($dryRun ? 'yes' : 'no'));

        $result = \DB::transaction(function () use ($mode, $dryRun) {
            $app = null;
            $phase = '';

            // Phase 1: Find one that needs text extraction + full parse
            if (in_array($mode, ['auto', 'text'])) {
                $app = JobApplication::where(function ($q) {
                        $q->whereNull('cv_text')->orWhere('cv_text', '');
                    })
                    ->whereNull('cv_indexed_at')
                    ->where('cv_index_failed', 0)
                    ->whereHas('documents', function ($q) {
                        $q->where('name', 'Resume');
                    })
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->first();
                if ($app) {
                    $phase = 'text_extract';
                    \Log::info('Found app #' . $app->id . ' for TEXT EXTRACTION + FULL PARSE');
                }
            }

            // Phase 2: If no text needed, find one that needs AI parsing (legacy/fallback)
            if (!$app && in_array($mode, ['auto', 'parse'])) {
                $app = JobApplication::whereNotNull('cv_text')
                    ->where('cv_text', '!=', '')
                    ->whereNull('parsed_cv_data')
                    ->where('cv_index_failed', 0)
                    ->where(function ($q) {
                        $q->whereNull('cv_indexed_at')
                          ->orWhereRaw('cv_indexed_at < DATE_SUB(NOW(), INTERVAL 5 MINUTE)');
                    })
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->first();
                if ($app) {
                    $phase = 'ai_parse';
                    \Log::info('Found app #' . $app->id . ' for AI PARSE. cv_text length=' . strlen($app->cv_text));
                }
            }

            if (!$app) {
                \Log::info('No more applications to process.');
                return [
                    'done'            => true,
                    'message'         => 'All applications processed!',
                    'total_done'      => JobApplication::whereNotNull('parsed_cv_data')->count(),
                    'total_failed'    => JobApplication::where('cv_index_failed', 1)->count(),
                    'remaining_text'  => JobApplication::where(function ($q) {
                                            $q->whereNull('cv_text')->orWhere('cv_text', '');
                                        })->whereNull('cv_indexed_at')->where('cv_index_failed', 0)->whereHas('documents', fn($q) => $q->where('name', 'Resume'))->count(),
                    'remaining_parse' => JobApplication::whereNotNull('cv_text')->where('cv_text', '!=', '')->whereNull('parsed_cv_data')->where('cv_index_failed', 0)->count(),
                ];
            }

            // Mark as processing immediately so it won't be picked again by another request
            if (!$dryRun) {
                $app->update(['cv_indexed_at' => now()]);
            }

            $result = [
                'id'       => $app->id,
                'name'     => $app->full_name,
                'phase'    => $phase,
                'status'   => 'skipped',
                'message'  => '',
            ];
            // ═══════════════════════════════════════════════════
            // PHASE 1: Extract PDF text, then send to DeepSeek for FULL parsing
            // ═══════════════════════════════════════════════════
            if ($phase === 'text_extract') {
                \Log::info('PHASE 1: Extract + DeepSeek full parse for app #' . $app->id);
                try {
                    $doc = $app->documents()->where('name', 'Resume')->first();
                    if (!$doc || empty($doc->hashname)) {
                        if (!$dryRun) {
                            $app->update(['cv_index_failed' => true, 'cv_indexed_at' => now()]);
                        }
                        $result['status'] = 'fail';
                        $result['message'] = 'No resume document found';
                        return $result;
                    }

                    $filePath = tenant_upload_path('documents/' . $app->id . '/' . $doc->hashname);
                    if (!is_readable($filePath)) {
                        if (!$dryRun) {
                            $app->update(['cv_index_failed' => true, 'cv_indexed_at' => now()]);
                        }
                        $result['status'] = 'fail';
                        $result['message'] = 'Resume file not found on disk';
                        return $result;
                    }

                    if ($dryRun) {
                        $result['status'] = 'dry_run';
                        $result['message'] = 'Would parse (' . filesize($filePath) . ' bytes)';
                        return $result;
                    }

                    // ── STEP 1: Extract text from PDF using pdftotext or similar ──
                    $ext = strtolower(pathinfo($doc->hashname, PATHINFO_EXTENSION)) ?: 'pdf';
                    $cvText = '';

                    try {
                        $extractor = new \App\Services\ResumeTextExtractor();
                        $cvText = $extractor->extractFromPath($filePath, $ext);
                    } catch (\Throwable $e) {
                        \Log::warning('App #' . $app->id . ' - Text extraction failed: ' . $e->getMessage());
                    }

                    // If text extraction returned nothing, try reading raw bytes as fallback
                    if (empty(trim($cvText))) {
                        $cvText = file_get_contents($filePath);
                        // Try to extract readable text from binary
                        $cvText = preg_replace('/[^\x20-\x7E\s]/', ' ', $cvText);
                        $cvText = preg_replace('/\s+/', ' ', $cvText);
                        $cvText = trim($cvText);
                    }

                    if (empty($cvText) || strlen($cvText) < 100) {
                        \Log::warning('App #' . $app->id . ' - Could not extract meaningful text from PDF');
                        $app->update(['cv_index_failed' => true, 'cv_indexed_at' => now()]);
                        $result['status'] = 'fail';
                        $result['message'] = 'Could not extract text from PDF (scanned/image PDF?)';
                        return $result;
                    }

                    \Log::info('App #' . $app->id . ' - Extracted ' . strlen($cvText) . ' chars from PDF');

                    // ── STEP 2: Send extracted text to DeepSeek for structured parsing ──
                    $systemPrompt = 'You are a CV parser API. Extract structured data from this resume text. '
                        . 'Return ONLY a raw JSON object. '
                        . 'Schema: {"personal":{"name":"","email":"","phone":"","location":{"city":"","province":"","country":""}},'
                        . '"headline":"","total_experience":{"years":0,"months":0},"job_titles":[],"skills":[],"'
                        . '"certifications":[],"education":[{"degree":"","field":"","school":""}],'
                        . '"employment":[{"company":"","title":"","start":"","end":"","duration_years":0}],'
                        . '"languages":[],"availability":{"notice_period":""},"resume_summary":""}. '
                        . 'Use empty string for unknown fields. '
                        . 'job_titles: array of ALL job titles from employment history. '
                        . 'skills: array of ALL technical and soft skills found anywhere. '
                        . 'total_experience: calculate from employment dates. '
                        . 'personal.location: extract city, province/state, country. '
                        . 'resume_summary: 2-3 sentence professional summary.';

                    $userPrompt = "Parse this resume text and extract all structured data:\n\n" . mb_substr($cvText, 0, 12000);

                    $text = $this->callDeepSeekWithImage($systemPrompt, $userPrompt);
                    $data = json_decode($text, true);

                    if (!is_array($data) || !isset($data['personal'])) {
                        \Log::warning('App #' . $app->id . ' - DeepSeek returned invalid JSON: ' . mb_substr($text, 0, 500));
                        $app->update(['cv_index_failed' => true, 'cv_indexed_at' => now()]);
                        $result['status'] = 'fail';
                        $result['message'] = 'AI returned invalid data';
                        return $result;
                    }

                    // ── Build raw text for search indexing ──
                    $rawText = implode(' ', array_filter([
                        $data['personal']['name'] ?? '',
                        $data['personal']['email'] ?? '',
                        $data['personal']['phone'] ?? '',
                        is_array($data['job_titles'] ?? null) ? implode(' ', $data['job_titles']) : '',
                        is_array($data['skills'] ?? null) ? implode(' ', $data['skills']) : '',
                        $data['resume_summary'] ?? '',
                    ]));

                    // Ensure cv_text is never empty
                    if (empty(trim($rawText))) {
                        // Fallback: use the extracted PDF text directly
                        $rawText = mb_substr($cvText, 0, 65000);
                    }

                    // ── Calculate structured fields from the JSON ──
                    $years = (float) ($data['total_experience']['years'] ?? 0)
                        + ((float) ($data['total_experience']['months'] ?? 0) / 12);

                    $location = array_filter([
                        $data['personal']['location']['city'] ?? null,
                        $data['personal']['location']['province'] ?? null,
                        $data['personal']['location']['country'] ?? null,
                    ]);

                    $jobTitles = (array) ($data['job_titles'] ?? []);
                    $skills    = (array) ($data['skills'] ?? []);

                    // ── Save EVERYTHING in one update ──
                    $updateData = [
                        'cv_text'             => mb_substr($rawText, 0, 65000),
                        'parsed_cv_data'      => json_encode($data),
                        'cv_experience_years' => round($years, 1),
                        'cv_job_titles'       => implode(', ', $jobTitles),
                        'cv_skills_text'      => implode(', ', $skills),
                        'cv_location_text'    => implode(', ', $location),
                        'cv_indexed_at'       => now(),
                        'cv_index_failed'     => false,
                    ];

                    \Log::info('App #' . $app->id . ' - Saved: ' . json_encode([
                        'experience_years' => $updateData['cv_experience_years'],
                        'job_titles_count' => count($jobTitles),
                        'skills_count'     => count($skills),
                        'location'         => $updateData['cv_location_text'],
                        'name'             => $data['personal']['name'] ?? 'N/A',
                    ]));

                    $app->update($updateData);

                    $result['status'] = 'ok';
                    $result['message'] = 'Parsed | ' . count($jobTitles) . ' jobs, ' . count($skills) . ' skills, ' . round($years, 1) . ' yrs';
                    return $result;

                } catch (\Throwable $e) {
                    \Log::error('App #' . $app->id . ' - Parse exception: ' . $e->getMessage());
                    if (!$dryRun) {
                        $app->update(['cv_index_failed' => true, 'cv_indexed_at' => now()]);
                    }
                    $result['status'] = 'fail';
                    $result['message'] = 'Exception: ' . $e->getMessage();
                    return $result;
                }
            }

            // ═══════════════════════════════════════════════════
            // PHASE 2: AI-parse CV text into structured data (LEGACY/FALLBACK)
            // Only runs for records that have cv_text but no parsed_cv_data
            // ═══════════════════════════════════════════════════
            if ($phase === 'ai_parse') {
                \Log::info('PHASE 2: AI parsing for app #' . $app->id);
                try {
                    if (empty($app->cv_text) || strlen($app->cv_text) < 50) {
                        \Log::warning('App #' . $app->id . ' - cv_text too short');
                        $result['status'] = 'skipped';
                        $result['message'] = 'cv_text too short (' . strlen($app->cv_text ?? '') . ' chars)';
                        return $result;
                    }

                    if ($dryRun) {
                        $result['status'] = 'dry_run';
                        $result['message'] = 'Would AI parse (' . strlen($app->cv_text) . ' chars)';
                        return $result;
                    }

                    \Log::info('App #' . $app->id . ' - Calling parseCvStructured...');
                    $data = $this->parseCvStructured($app->cv_text);
                    \Log::info('App #' . $app->id . ' - parseCvStructured returned: ' . ($data === null ? 'NULL' : 'VALID ARRAY'));

                    if (!$data || !is_array($data)) {
                        \Log::warning('App #' . $app->id . ' - AI returned null/invalid. Marking failed.');
                        $app->update([
                            'cv_index_failed' => true,
                            'cv_indexed_at'   => now(),
                        ]);
                        $result['status'] = 'fail';
                        $result['message'] = 'AI returned null or invalid data';
                        return $result;
                    }

                    if (!isset($data['personal']) || !isset($data['job_titles']) || !isset($data['skills'])) {
                        \Log::warning('App #' . $app->id . ' - AI JSON missing required keys');
                        $app->update([
                            'cv_index_failed' => true,
                            'cv_indexed_at'   => now(),
                        ]);
                        $result['status'] = 'fail';
                        $result['message'] = 'AI JSON missing required structure';
                        return $result;
                    }

                    $years = (float) ($data['total_experience']['years'] ?? 0)
                        + ((float) ($data['total_experience']['months'] ?? 0) / 12);

                    $location = array_filter([
                        $data['personal']['location']['city'] ?? null,
                        $data['personal']['location']['province'] ?? null,
                        $data['personal']['location']['country'] ?? null,
                    ]);

                    $jobTitles = (array) ($data['job_titles'] ?? []);
                    $skills    = (array) ($data['skills'] ?? []);

                    $updateData = [
                        'parsed_cv_data'      => json_encode($data),
                        'cv_experience_years' => round($years, 1),
                        'cv_job_titles'       => implode(', ', $jobTitles),
                        'cv_skills_text'      => implode(', ', $skills),
                        'cv_location_text'    => implode(', ', $location),
                        'cv_indexed_at'       => now(),
                        'cv_index_failed'     => false,
                    ];

                    \Log::info('App #' . $app->id . ' - Saving parsed data');
                    $app->update($updateData);

                    $result['status'] = 'ok';
                    $result['message'] = 'AI parsed | ' . count($jobTitles) . ' jobs, ' . count($skills) . ' skills, ' . round($years, 1) . ' yrs';
                    return $result;

                } catch (\Throwable $e) {
                    \Log::error('App #' . $app->id . ' - AI parse exception: ' . $e->getMessage());
                    if (!$dryRun) {
                        $app->update([
                            'cv_index_failed' => true,
                            'cv_indexed_at'   => now(),
                        ]);
                    }
                    $result['status'] = 'fail';
                    $result['message'] = 'Exception: ' . $e->getMessage();
                    return $result;
                }
            }

            return $result;
        });

        // Handle "done" response from transaction
        if (isset($result['done']) && $result['done']) {
            return Reply::dataOnly($result);
        }

        // Wrap normal result in standard response format
        return $this->bulkParseRespond($result);
    }

    /**
     * Call DeepSeek API with system and user prompts
     */
    protected function callDeepSeekWithImage(string $systemPrompt, string $userPrompt): string
    {
        $apiKey = config('services.deepseek.key');
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type'  => 'application/json',
        ])->post('https://api.deepseek.com/v1/chat/completions', [
            'model'    => config('services.deepseek.model', 'deepseek-chat'),
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => $userPrompt],
            ],
            'temperature' => 0.1,
            'max_tokens'  => 4000,
        ]);

        if ($response->failed()) {
            Log::error('DeepSeek API error: ' . $response->status() . ' - ' . $response->body());
            throw new \RuntimeException('DeepSeek API error: ' . $response->status());
        }

        $content = $response->json('choices.0.message.content', '');
        
        // Strip markdown code fences
        $content = preg_replace('/^```(?:json)?\s*/i', '', $content);
        return trim(preg_replace('/\s*```$/', '', $content));
    }

    /**
     * Helper to return consistent response format
     */
    private function bulkParseRespond(array $result)
    {
        $remainingText = JobApplication::where(function ($q) {
                $q->whereNull('cv_text')->orWhere('cv_text', '');
            })->whereNull('cv_indexed_at')->where('cv_index_failed', 0)->whereHas('documents', fn($q) => $q->where('name', 'Resume'))->count();

        $remainingParse = JobApplication::whereNotNull('cv_text')
            ->where('cv_text', '!=', '')
            ->whereNull('parsed_cv_data')
            ->where('cv_index_failed', 0)
            ->count();

        \Log::info('=== bulkParseAllCvs END === status=' . ($result['status'] ?? 'unknown') . ' msg=' . ($result['message'] ?? ''));

        return Reply::dataOnly([
            'done'            => false, 
            'result'          => $result,
            'total_done'      => JobApplication::whereNotNull('parsed_cv_data')->count(),
            'total_failed'    => JobApplication::where('cv_index_failed', 1)->count(),
            'remaining_text'  => $remainingText,
            'remaining_parse' => $remainingParse,
            'next_url'        => route('admin.job-applications.bulk-parse-all-cvs'),
        ]);
    }
    public function searchUsersForMention(Request $request)
{
    $query = trim($request->input('q', ''));
    $users = \App\User::select('id', 'name')
        ->where('id', '!=', $this->user->id)
        ->when($query !== '', fn ($q) => $q->where('name', 'LIKE', '%'.$query.'%'))
        ->limit(6)
        ->get();

    return Reply::dataOnly(['users' => $users]);
}
} // ← CLASS CLOSING BRACE — KEEP ONLY THIS ONE
