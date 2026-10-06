<?php

namespace App\Http\Controllers\Admin;

use App\AiApiKey;
use App\ApplicationStatus;
use App\Company;
use App\Events\JobAlertEvent;
use App\Helper\Reply;
use App\Http\Requests\StoreJob;
use App\Http\Requests\UpdateJob;
use App\Job;
use App\JobApplication;
use App\JobCategory;
use App\JobJobLocation;
use App\JobLocation;
use App\JobSkill;
use App\JobType;
use App\Notifications\NewJobOpening;
use App\Question;
use App\Skill;
use App\Support\RaDataTableHtml;
use App\WorkExperience;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class AdminJobsController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = __('menu.jobs');
        $this->pageIcon = 'icon-badge';
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        abort_if(! $this->user->cans('view_jobs'), 403);
        $this->companies = Company::all();
        $this->locations = JobLocation::all();
        $this->totalJobs = Job::count();
        $this->activeJobs = Job::where('status', 'active')->count();
        $this->inactiveJobs = Job::where('status', 'inactive')->count();
        $this->expiredJobs = Job::where('status', 'expired')->count();
        $this->totalOpenings = (int) Job::sum('total_positions');

        $jobsQuery = Job::query()
            ->with(['jobLocation.country', 'company', 'category', 'jobType'])
            ->join('job_job_locations', 'job_job_locations.job_id', 'jobs.id')
            ->select('jobs.id as jobs_id', 'job_job_locations.location_id as locations_id', 'jobs.*')
            ->where('jobs.id', '>', 0)
            ->groupBy('jobs_id');

        $jobs = $jobsQuery->get();

        $this->jobsPayload = $jobs->map(function (Job $job) {
            $firstLoc = $job->jobLocation->first();
            $locId = $firstLoc?->id;
            $locNames = $job->jobLocation->map(fn ($l) => ucfirst((string) $l->location))->filter()->values()->all();

            return [
                'id' => $job->id,
                'title' => $job->title,
                'company' => $job->company ? ucwords($job->company->company_name) : '—',
                'companyId' => (int) $job->company_id,
                'companyLocation' => $job->company_location ?? '',
                'category' => $job->category ? ucfirst($job->category->name) : '—',
                'jobType' => $job->jobType ? $job->jobType->job_type : '—',
                'openings' => (int) $job->total_positions,
                'locations' => $locNames,
                'locationsStr' => implode(', ', $locNames),
                'locationIds' => $job->jobLocation->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
                'start' => $job->start_date?->format('d M, Y') ?? '—',
                'end' => $job->end_date?->format('d M, Y') ?? '—',
                'status' => $job->status,
                'copyUrl' => route('jobs.jobDetail', array_filter([$job->slug, $locId])),
                'editUrl' => route('admin.jobs.edit', $job->id),
                'duplicateUrl' => route('admin.jobs.create', ['duplicate_job' => $job->id]),
                'destroyUrl' => route('admin.jobs.destroy', $job->id),
            ];
        })->values();

        return view('admin.jobs.index', $this->data);
    }

    public function toggleStatus(Job $job)
    {
        abort_if(! $this->user->cans('edit_jobs'), 403);

        if ($job->status === 'expired') {
            return Reply::error(__('errors.expiredJobNoToggle'));
        }

        $job->status = $job->status === 'active' ? 'inactive' : 'active';
        $job->save();

        return Reply::successWithData(__('messages.updatedSuccessfully'), ['new_status' => $job->status]);
    }

    public function bulkDestroy(Request $request)
    {
        abort_if(! $this->user->cans('delete_jobs'), 403);

        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:jobs,id',
        ]);

        Job::whereIn('id', $validated['ids'])->delete();

        return Reply::success(__('messages.recordDeleted'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        abort_if(! $this->user->cans('add_jobs'), 403);
        $this->job = (request()['duplicate_job'] ? Job::with(['category', 'skills', 'questions'])->findOrFail(request()['duplicate_job']) : null);
        $selectedCategoryId = null;
        if (! is_null($this->job)) {
            $this->jobQuestion = $this->job->questions->pluck('id')->toArray();
            $this->skills = Skill::where('category_id', $this->job->category_id)->get();
            $this->jobLocation = $this->job->jobLocation->pluck('id')->toArray();
            $selectedCategoryId = (int) $this->job->category_id;
        }
        if (! $selectedCategoryId && request()->filled('category_id')) {
            $selectedCategoryId = (int) request('category_id');
        }
        $this->categories = JobCategory::all();
        $this->locations = JobLocation::all();
        $this->jobTypes = JobType::all();
        $this->workExperiences = WorkExperience::all();
        $this->questions = $this->getQuestionsForCategory($selectedCategoryId);
        $this->jobUsageCounts = $this->getQuestionJobUsageCounts($this->questions->pluck('id')->toArray()); 
        $this->companies = Company::all();
        $this->jobStatuses = collect(ApplicationStatus::DEFAULT_PIPELINE)->map(function ($status, $index) {
            return new ApplicationStatus([
                'status' => $status['status'],
                'color' => $status['color'],
                'position' => $index + 1,
            ]);
        });

        return view('admin.jobs.create', $this->data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(StoreJob $request)
    {
        abort_if(! $this->user->cans('add_jobs'), 403);

        $required_columns = [
            'gender' => false,
            'dob' => false,
            'country' => false,
            'address' => false,
        ];

        foreach ($required_columns as $key => $value) {
            if ($request->has($key)) {
                $required_columns[$key] = true;
            }
        }

        $section_visibility = [
            'profile_image' => 'no',
            'resume' => 'no',
            'cover_letter' => 'no',
            'terms_and_conditions' => 'no',
        ];

        foreach ($section_visibility as $key => $value) {
            if ($request->has($key)) {
                $section_visibility[$key] = 'yes';
            }
        }

        $job = new Job;
        $job->slug = null;
        
        // Handle manual company entry
        if ($request->filled('company_new')) {
            $company = Company::firstOrCreate(
                ['company_name' => trim($request->company_new)]
            );
            $job->company_id = $company->id;
        } else {
            $job->company_id = $request->company;
        }
        $job->company_location = $request->company_location;
        $job->title = $request->title;
        $job->job_code = $request->job_code;
        $job->job_description = $request->job_description;
        $job->total_positions = $request->total_positions;
        // Handle manual category entry
        if ($request->filled('category_new')) {
            $category = JobCategory::firstOrCreate(
                ['name' => trim($request->category_new)]
            );
            $job->category_id = $category->id;
        } else {
            $job->category_id = $request->category_id;
        }
        $job->start_date = $request->start_date;
        $job->end_date = $request->end_date;
        $job->status = $request->status;
        $job->job_type_id = $request->job_type_id;
        $job->work_experience_id = $request->work_experience_id;
        $job->pay_type = $request->pay_type;
        $job->pay_according = $request->pay_according;
        $job->starting_salary = $request->starting_salary;
        $job->maximum_salary = $request->maximum_salary;
        $job->currency_id = $request->currency_id;
        $job->required_columns = $required_columns;
        $job->section_visibility = $section_visibility;
        $job->meta_details = [
            'title' => $request->meta_title ?: $request->title,
            'description' => $request->meta_description ?: strip_tags(Str::substr(html_entity_decode($request->job_description), 0, 150)),
        ];

        if ($request->show_job_type == 'yes') {
            $job->show_job_type = true;
        }

        if ($request->show_work_experience == 'yes') {
            $job->show_work_experience = true;
        }

        if ($request->show_salary == 'yes') {
            $job->show_salary = true;
        }

        $jobData = $job->save();

        if (! is_null($request->skill_id)) {
            JobSkill::where('job_id', $job->id)->delete();

            foreach ($request->skill_id as $skill) {
                $jobSkill = new JobSkill;
                $jobSkill->skill_id = $skill;
                $jobSkill->job_id = $job->id;
                $jobSkill->save();
            }
        }

        // Save Question for job
        $job->questions()->sync($this->jobQuestionsWithResume($request->question));

      // Handle manual location entry
        if ($request->filled('location_new')) {
            $newLocation = JobLocation::firstOrCreate(
                ['location' => trim($request->location_new)]
            );
            $locationIds = [$newLocation->id];
        } else {
            $locationIds = $request->location_id ?? [];
        }

        foreach ($locationIds as $location) {
            $jobLocation = new JobJobLocation;
            $jobLocation->job_id = $job->id;
            $jobLocation->location_id = $location;
            $jobLocation->save();
        }

        $this->syncJobStatuses($job->id, $request);

        event(new JobAlertEvent($job));

        return Reply::redirect(route('admin.jobs.index'), __('menu.jobs').' '.__('messages.createdSuccessfully'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        $this->job = Job::find($id);

        return $this->job;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        abort_if(! $this->user->cans('edit_jobs'), 403);
        $this->job = Job::find($id);
        $this->categories = JobCategory::all();
        $this->locations = JobLocation::all();
        $this->jobLocationData = $this->job->jobLocation->pluck('id')->toArray();
        $this->skills = Skill::where('category_id', $this->job->category_id)->get();
        $this->jobQuestion = $this->job->questions->pluck('id')->toArray();
        $this->questions = $this->getQuestionsForCategory((int) $this->job->category_id);
        $this->jobUsageCounts = $this->getQuestionJobUsageCounts($this->questions->pluck('id')->toArray());
        $this->companies = Company::all();
        $this->jobTypes = JobType::all();
        $this->workExperiences = WorkExperience::all();
        ApplicationStatus::ensureAppliedForJob((int) $id);
        $this->jobStatuses = ApplicationStatus::where('job_id', $id)->orderBy('position')->get();

        return view('admin.jobs.edit', $this->data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return Response
     */
    public function update(StoreJob $request, $id)
    {
        abort_if(! $this->user->cans('edit_jobs'), 403);

        $required_columns = [
            'gender' => false,
            'dob' => false,
            'country' => false,
            'address' => false,
        ];

        foreach ($required_columns as $key => $value) {
            if ($request->has($key)) {
                $required_columns[$key] = true;
            }
        }

        $section_visibility = [
            'profile_image' => 'no',
            'resume' => 'no',
            'cover_letter' => 'no',
            'terms_and_conditions' => 'no',
        ];

        foreach ($section_visibility as $key => $value) {
            if ($request->has($key)) {
                $section_visibility[$key] = 'yes';
            }
        }

        $job = Job::find($id);
        // Handle manual company entry
        if ($request->filled('company_new')) {
            $company = Company::firstOrCreate(
                ['company_name' => trim($request->company_new)]
            );
            $job->company_id = $company->id;
        } else {
            $job->company_id = $request->company;
        }
        $job->company_location = $request->company_location;
        $job->title = $request->title;
        $job->job_code = $request->job_code;
        $job->job_description = $request->job_description;
       
        $job->total_positions = $request->total_positions;
        // Handle manual category entry
        if ($request->filled('category_new')) {
            $category = JobCategory::firstOrCreate(
                ['name' => trim($request->category_new)]
            );
            $job->category_id = $category->id;
        } else {
            $job->category_id = $request->category_id;
        }
        $job->start_date = $request->start_date;
        $job->end_date = $request->end_date;
        $job->status = $request->status;
        $job->job_type_id = $request->job_type_id;
        $job->work_experience_id = $request->work_experience_id;
        $job->pay_type = $request->pay_type;
        $job->pay_according = $request->pay_according;
        $job->starting_salary = $request->starting_salary;
        $job->maximum_salary = $request->maximum_salary;
        $job->currency_id = $request->currency_id;
        $job->required_columns = $required_columns;
        $job->section_visibility = $section_visibility;
        $job->meta_details = [
            'title' => $request->meta_title ?: $job->title,
            'description' => $request->meta_description ?: strip_tags(Str::substr(html_entity_decode($job->job_description), 0, 150)),
        ];

        if ($request->show_job_type == 'yes') {
            $job->show_job_type = true;
        } else {
            $job->show_job_type = false;
        }

        if ($request->show_work_experience == 'yes') {
            $job->show_work_experience = true;
        } else {
            $job->show_work_experience = false;
        }

        if ($request->show_salary == 'yes') {
            $job->show_salary = true;
        } else {

            $job->show_salary = false;
        }

        $job->save();

        if (! is_null($request->skill_id)) {
            JobSkill::where('job_id', $job->id)->delete();

            foreach ($request->skill_id as $skill) {
                $jobSkill = new JobSkill;
                $jobSkill->skill_id = $skill;
                $jobSkill->job_id = $job->id;
                $jobSkill->save();
            }
        }

        // Update Question for job
        $job->questions()->sync($this->jobQuestionsWithResume($request->question));

        JobJobLocation::where('job_id', $job->id)->delete();
       // Handle manual location entry
        if ($request->filled('location_new')) {
            $newLocation = JobLocation::firstOrCreate(
                ['location' => trim($request->location_new)]
            );
            $locationIds = [$newLocation->id];
        } else {
            $locationIds = $request->location_id ?? [];
        }

        foreach ($locationIds as $location) {
            $jobLocation = new JobJobLocation;
            $jobLocation->job_id = $job->id;
            $jobLocation->location_id = $location;
            $jobLocation->save();
        }

        $this->syncJobStatuses($id, $request);

        return Reply::redirect(route('admin.jobs.index'), __('menu.jobs').' '.__('messages.updatedSuccessfully'));
    }

    private function syncJobStatuses(int $jobId, $request): void
    {
        $names  = $request->input('job_status_name', []);
        $colors = $request->input('job_status_color', []);
        $ids    = $request->input('job_status_id', []);

        $existingApplied = ApplicationStatus::where('job_id', $jobId)
            ->whereRaw('LOWER(TRIM(status)) = ?', ['applied'])
            ->first();
        $editableRows = collect($names)->map(function ($name, $index) use ($colors, $ids) {
            return [
                'name' => trim((string) $name),
                'color' => $colors[$index] ?? '#2563EB',
                'id' => $ids[$index] ?? null,
            ];
        })->reject(fn ($row) => mb_strtolower($row['name']) === 'applied')->values();

        $names = collect(['Applied'])->concat($editableRows->pluck('name'))->all();
        $colors = collect(['#2563EB'])->concat($editableRows->pluck('color'))->all();
        $ids = collect([$existingApplied?->id])->concat($editableRows->pluck('id'))->all();

        if (count(array_filter($names, fn ($name) => trim((string) $name) !== '')) === 0) {
            ApplicationStatus::ensureDefaultsForJob($jobId);
            return;
        }

        $normalizedNames = array_map(fn ($name) => mb_strtolower(trim((string) $name)), array_filter($names, fn ($name) => trim((string) $name) !== ''));
        if (count($normalizedNames) !== count(array_unique($normalizedNames))) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'job_status_name' => 'Pipeline status names must be unique within a job.',
            ]);
        }

        // Delete rows that were removed in the form
        $submittedIds = array_values(array_filter($ids, fn ($v) => is_numeric($v) && (int) $v > 0));

        $removedStatuses = ApplicationStatus::where('job_id', $jobId)
            ->when(count($submittedIds), fn ($q) => $q->whereNotIn('id', $submittedIds))
            ->get();

        foreach ($removedStatuses as $removedStatus) {
            if ($removedStatus->applications()->withTrashed()->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'job_status_name' => 'The status "'.$removedStatus->status.'" cannot be removed because one or more applicants are currently assigned to it. Move those applicants first.',
                ]);
            }
        }

        try {
            DB::transaction(function () use ($removedStatuses, $names, $colors, $ids, $jobId) {
                $removedIds = $removedStatuses->pluck('id');
                DB::table('job_application_status_histories')
                    ->whereIn('from_status_id', $removedIds)
                    ->orWhereIn('to_status_id', $removedIds)
                    ->delete();
                ApplicationStatus::whereIn('id', $removedIds)->delete();

                foreach ($names as $index => $name) {
                    $name = trim((string) $name);
                    if ($name === '') continue;

                    $color    = $colors[$index] ?? '#2563EB';
                    $statusId = $ids[$index] ?? null;

                    if (is_numeric($statusId) && (int) $statusId > 0) {
                        ApplicationStatus::where('id', (int) $statusId)
                            ->where('job_id', $jobId)
                            ->update(['status' => $name, 'color' => $color, 'position' => $index + 1]);
                    } else {
                        ApplicationStatus::create([
                            'job_id'   => $jobId,
                            'status'   => $name,
                            'color'    => $color,
                            'position' => $index + 1,
                        ]);
                    }
                }
            });
        } catch (\Exception $e) {
            report($e);
            throw \Illuminate\Validation\ValidationException::withMessages([
                'job_status_name' => 'The pipeline could not be saved. No applicant statuses were changed.',
            ]);
            // job_id column not yet migrated — skip silently
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        abort_if(! $this->user->cans('delete_jobs'), 403);

        Job::destroy($id);

        return Reply::success(__('messages.recordDeleted'));
    }

    public function data()
    {
        abort_if(! $this->user->cans('view_jobs'), 403);

        $categories = Job::with(['jobLocation', 'location'])
            ->join('job_job_locations', 'job_job_locations.job_id', 'jobs.id')
            ->select('jobs.id as jobs_id', 'job_job_locations.location_id as locations_id', 'jobs.*')
            ->where('jobs.id', '>', '0')
            ->groupBy('jobs_id');

        if (\request('filter_company') != '') {
            $categories->where('company_id', \request('filter_company'));
        }

        if (\request('filter_status') != '') {
            $categories->where('status', \request('filter_status'));
        }

        if (\request('filter_location') != '') {
            $categories->where('job_job_locations.location_id', \request('filter_location'));
        }

        $categories = $categories->get();

        return DataTables::of($categories)
            ->addColumn('action', function ($row) {
                $locationID = isset($row->jobLocation[0]->id) ? $row->jobLocation[0]->id : null;
                $params = [$row->slug, $locationID];
                $parts = [];

                if ($this->user->cans('edit_jobs')) {
                    $parts[] = RaDataTableHtml::edit(route('admin.jobs.edit', [$row->id]));
                }

                $parts[] = RaDataTableHtml::js(
                    RaDataTableHtml::SVG_COPY,
                    'jc-btn-violet open-url',
                    ['data-row-open-url' => route('jobs.jobDetail', $params)],
                    __('app.copyUrl')
                );

                if ($this->user->cans('delete_jobs')) {
                    $parts[] = RaDataTableHtml::deleteSaParams($row->id);
                }
                if ($row->status == 'expired') {
                    $parts[] = RaDataTableHtml::js(
                        RaDataTableHtml::SVG_REFRESH,
                        'jc-btn-amber expire_modal',
                        ['data-row-id' => (string) $row->id],
                        __('app.refresh')
                    );
                }

                $parts[] = RaDataTableHtml::linkIcon(
                    route('admin.jobs.create').'?duplicate_job='.$row->id,
                    RaDataTableHtml::SVG_CLONE,
                    'jc-btn-amber duplicate_job',
                    __('app.duplicate')
                );

                return RaDataTableHtml::wrap(implode('', $parts));
            })
            ->editColumn('title', function ($row) {
                return ucfirst($row->title);
            })
            ->editColumn('company_id', function ($row) {
                return ucwords($row->company->company_name);
            })
            ->editColumn('location_id', function ($row) {
                $locations = '<ul>';
                foreach ($row->jobLocation as $value) {

                    $locations .= '<li><a href="'.route('jobs.jobDetail', [$row->slug, $value->id]).'">'.ucfirst($value->location).'</a></li>';
                }
                $locations .= '</ul>';

                return $locations;
            })
            ->editColumn('start_date', function ($row) {
                return $row->start_date ? $row->start_date->format('d M, Y') : '';
            })
            ->editColumn('end_date', function ($row) {
                return $row->end_date ? $row->end_date->format('d M, Y') : '';
            })
            ->editColumn('status', function ($row) {
                if ($row->status == 'active') {
                    return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">'.__('app.active').'</span>';
                }
                if ($row->status == 'inactive') {
                    return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">'.__('app.inactive').'</span>';
                }
                if ($row->status == 'expired') {
                    return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium text-white" style="background: #FF8C00;">'.__('app.expired').'</span>';
                }
            })
            ->rawColumns(['status', 'action', 'location_id'])
            ->addIndexColumn()
            ->make(true);
    }

    public function sendEmail(Request $request)
    {
        abort_if(! $this->user->cans('add_jobs'), 403);

        $this->boardColumns = ApplicationStatus::where('status', '!=', 'hired')->get();
        $this->locations = JobLocation::all();
        $this->jobs = Job::all();
        $this->skills = Skill::all();

        return view('admin.jobs.send-email', $this->data);
    }

    public function applicationData(Request $request)
    {
        abort_if(! $this->user->cans('view_job_applications'), 403);

        return DataTables::of($this->filterJobApplications($request))
            ->editColumn('full_name', function ($row) {
                return '<a href="javascript:;" class="show-detail" data-row-id="'.$row->id.'">'.ucwords($row->full_name).'</a>';
            })
            ->editColumn('title', function ($row) {
                return ucfirst($row->title);
            })
            ->editColumn('location', function ($row) {
                return ucwords($row->location);
            })
            ->editColumn('status', function ($row) {
                return '<span>'.ucwords($row->status).'</span>
                 <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium text-white" style= "margin-bottom: -3px; height: 15px; background:'.$row->color.'"> </span>';
            })
            ->addColumn('mail_status', function ($row) use ($request) {
                return $row->jobs()->where('job_id', $request->jobId)->count() == 0 ? '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">'.__('modules.newJobEmail.mailNotSent').'</span>' : '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">'.__('modules.newJobEmail.mailSent').'</span>';
            })
            ->addColumn('checkbox', function ($row) {
                return '
                    <div class="flex items-center">
                        <input id="'.$row->id.'" type="checkbox" value="'.$row->id.'" class="rounded border-gray-300 text-primary focus:ring-primary mail-sent" >
                        <label for="'.$row->id.'" class="ml-2"></label>
                    </div>
                ';
            })
            ->rawColumns(['action', 'resume', 'full_name', 'checkbox', 'mail_status', 'status'])
            ->addIndexColumn()
            ->make(true);
    }

    public function sendEmails(Request $request)
    {
        // Please select job for which the emails will be sent
        if ($request->allSelected == 'false') {
            if (! $request->has('selectedIds')) {
                return Reply::error(__('messages.selectApplicantsForEmail'));
            }

            $jobApplications = JobApplication::whereIn('id', $request->selectedIds)->with('jobs');
        } else {
            $jobApplications = JobApplication::with('jobs');
        }

        // get jobApplication
        $job = Job::findOrFail($request->job_for_email);

        if ($request->excludeSent == 'true') {
            $jobApplicationsCopy = clone $jobApplications;

            $jobApplicationIds = $jobApplicationsCopy->whereHas('jobs', function ($q) use ($request) {
                $q->where('job_id', $request->job_for_email);
            })->get()->map(function ($jobApplication) {
                return $jobApplication->id;
            })->toArray();

            $jobApplications = $jobApplications->whereNotIn('job_applications.id', $jobApplicationIds);
        }

        $jobApplications = $jobApplications->get();

        $jobApplicationIds = $jobApplications->map(function ($jobApplication) {
            return $jobApplication->id;
        })->toArray();

        $job->applications()->syncWithoutDetaching($jobApplicationIds);

        $uniqueEmailJobs = $jobApplications->unique(function ($job) {
            return $job['email'];
        });

        if ($jobApplications->count() > 0) {
            Notification::send($uniqueEmailJobs, new NewJobOpening($job));
        }

        return Reply::success(__('messages.emailsSentSuccessfully'));
    }

    public function filterJobApplications($request)
    {

        $jobApplicationRec = JobApplication::select('job_applications.id as application')
            ->with('jobs')
            ->join('jobs', 'jobs.id', 'job_applications.job_id')
            ->leftjoin('job_skills', 'jobs.id', 'job_skills.job_id')
            ->leftjoin('job_locations', 'job_locations.id', 'jobs.location_id')
            ->leftjoin('application_status', 'application_status.id', 'job_applications.status_id')
            ->distinct()
            ->where('job_applications.job_id', $request->jobId)
            ->whereRaw('LOWER(application_status.status) != ?', ['hired'])
            ->where('application_status.status', '!=', 'rejected')
            ->pluck('application')->toArray();

        $jobApplications = JobApplication::select('job_applications.id', 'job_applications.full_name', 'job_applications.email', 'jobs.title', 'job_locations.location', 'application_status.status', 'application_status.color')
            ->with('jobs')
            ->join('jobs', 'jobs.id', 'job_applications.job_id')
            ->leftjoin('job_skills', 'jobs.id', 'job_skills.job_id')
            ->leftjoin('job_locations', 'job_locations.id', 'jobs.location_id')
            ->leftjoin('application_status', 'application_status.id', 'job_applications.status_id')
            ->whereRaw('LOWER(application_status.status) != ?', ['hired'])
            ->distinct();

        // Filter by status
        if ($request->status != 'all' && $request->status != '') {
            $jobApplications = $jobApplications->where('job_applications.status_id', $request->status);
        }

        // Filter By jobs
        if ($request->jobId != 'all' && $request->jobId != '') {
            $jobApplications = $jobApplications->whereNotIn('job_applications.id', $jobApplicationRec);
        }

        // Filter By skills
        if ($request->skill != 'all' && $request->skill != '') {
            $jobApplications = $jobApplications->whereIn('job_skills.skill_id', gettype($request->skill) == 'array' ? $request->skill : explode(',', $request->skill));
        }

        // Filter by location
        if ($request->location != 'all' && $request->location != '') {
            $jobApplications = $jobApplications->where('jobs.location_id', $request->location);
        }

        // Filter by StartDate
        if ($request->startDate != null && $request->startDate != '') {
            $jobApplications = $jobApplications->where(DB::raw('DATE(job_applications.`created_at`)'), '>=', "$request->startDate");
        }

        // Filter by EndDate
        if ($request->endDate != null && $request->endDate != '') {
            $jobApplications = $jobApplications->where(DB::raw('DATE(job_applications.`created_at`)'), '<=', "$request->endDate");
        }

        // Filter by MailStatus
        if ($request->mailStatus != null && $request->mailStatus != '') {
            if ($request->mailStatus !== 'all') {
                if ($request->mailStatus == 'sent') {
                    $jobApplications = $jobApplications->whereHas('jobs', function ($q) use ($request) {
                        $q->where('job_id', $request->jobId);
                    });
                } else {
                    $jobApplicationsCopy = clone $jobApplications;

                    $jobApplicationIds = $jobApplicationsCopy->whereHas('jobs', function ($q) use ($request) {
                        $q->where('job_id', $request->jobId);
                    })->get()->map(function ($jobApplication) {
                        return $jobApplication->id;
                    })->toArray();

                    $jobApplications = $jobApplications->whereNotIn('job_applications.id', $jobApplicationIds);
                }
            }
        }

        $jobApplications = $jobApplications->groupBy('job_applications.id');

        return $jobApplications;
    }

    // Refresh Expire Date
    public function refreshDate(UpdateJob $request)
    {
        $job = Job::find($request->id);
        $job->start_date = $request->start_date;
        $job->end_date = $request->end_date;
        $job->status = 'active';
        $job->save();

        return Reply::success(__('messages.updatedSuccessfully'));
    }

    public function aiGenerateContent(Request $request)
    {
        abort_if(! ($this->user->cans('add_jobs') || $this->user->cans('edit_jobs')), 403);

        $validated = $request->validate([
            'title' => 'required|string|max:191',
            'category_id' => 'required|integer|exists:job_categories,id',
        ]);

        $configuredKeys = AiApiKey::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $category = JobCategory::find($validated['category_id']);
        if (! $category) {
            return Reply::error(__('messages.invalidCategorySelected'));
        }

        $categorySkills = Skill::query()
            ->where('category_id', (int) $validated['category_id'])
            ->limit(40)
            ->pluck('name')
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->values()
            ->all();

        $systemPrompt = 'You are a senior recruitment content strategist writing high-quality, realistic job posts for modern companies. '
            .'Return ONLY valid minified JSON with keys: description_html, job_requirement_html, skills, meta_title, meta_description. '
            .'Rules: '
            .'(1) description_html: 2 short paragraphs + one bullet list (5 to 7 bullets), professional and compelling, no fluff. '
            .'(2) job_requirement_html: one short intro paragraph + bullet list (8 to 12 concrete requirement bullets), action-oriented and specific. '
            .'Use the exact job title in the intro and in multiple bullets. '
            .'Do NOT use generic placeholders; content must be specifically relevant to provided title and category. '
            .'(3) skills: array of 8 to 12 short skill names, prioritize skill names from provided allowed_skills list when relevant. '
            .'(4) meta_title: SEO friendly, role + category context, max 60 chars. '
            .'(5) meta_description: plain text, 140-155 chars, include role impact/value. '
            .'(6) Output HTML with only <p>, <ul>, <li>, <strong>. '
            .'(7) Do not include markdown fences or extra keys.';

        $userPrompt = 'Generate job content with this input: '
            .json_encode([
                'job_title' => $validated['title'],
                'category' => $category->name,
                'allowed_skills' => $categorySkills,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $candidates = [];
        foreach ($configuredKeys as $row) {
            $plainKey = $this->resolvePlainApiKey((string) $row->api_key);
            if ($plainKey === '') {
                continue;
            }

            $candidates[] = [
                'source' => 'settings',
                'name' => (string) $row->name,
                'provider' => $this->normalizeProvider((string) $row->provider),
                'api_key' => $plainKey,
                'masked_key' => $this->maskApiKey($plainKey),
            ];
        }

        $candidates = collect($candidates)
            ->unique(fn ($item) => $item['provider'].'|'.$item['api_key'])
            ->values()
            ->all();

        if (empty($candidates)) {
            return Reply::error(__('messages.aiApiKeyRequired'));
        }

        $lastError = 'Unable to generate quality AI content right now. Please try again.';
        $quotaExhausted = [];

        foreach ($candidates as $candidate) {
            try {
                $generated = $this->generateJobContentByProvider(
                    $candidate['provider'],
                    $candidate['api_key'],
                    $systemPrompt,
                    $userPrompt,
                    $validated['title'],
                    $category->name
                );

                return Reply::successWithData(__('messages.aiContentGeneratedSuccessfully'), [
                    'data' => $generated,
                ]);
            } catch (\Throwable $e) {
                $lastError = 'AI generation failed: '.$e->getMessage();
                if ($this->isQuotaExceededError($e->getMessage())) {
                    $quotaExhausted[] = sprintf(
                        '%s (%s) key %s',
                        $candidate['name'],
                        $candidate['provider'],
                        $candidate['masked_key']
                    );
                }

                if (! $this->shouldRetryWithAnotherProvider($e->getMessage())) {
                    break;
                }
            }
        }

        if (! empty($quotaExhausted)) {
            $lastError .= ' | Quota exhausted keys: '.implode(', ', array_unique($quotaExhausted));
        }

        return Reply::error($lastError);
    }

    private function getQuestionsForCategory(?int $categoryId)
    {
        return Question::query()
            ->where(function ($query) use ($categoryId) {
                $query->whereNull('job_category_id');
                if (! empty($categoryId)) {
                    $query->orWhere('job_category_id', (int) $categoryId);
                }
            })
            ->orderBy('id', 'desc')
            ->get();
    }
    private function getQuestionJobUsageCounts(array $questionIds): array
    {
        if (empty($questionIds)) {
            return [];
        }

        return DB::table('job_questions')
            ->select('question_id', DB::raw('COUNT(DISTINCT job_id) as job_count'))
            ->whereIn('question_id', $questionIds)
            ->groupBy('question_id')
            ->pluck('job_count', 'question_id')
            ->toArray();
    }
    private function resolvePlainApiKey(string $key): string
    {
        $key = trim($key);
        if ($key === '') {
            return '';
        }

        // If key was accidentally stored as Laravel encrypted payload, try decrypting.
        if (Str::startsWith($key, 'eyJpdiI6')) {
            try {
                $decrypted = Crypt::decryptString($key);
                if (is_string($decrypted) && trim($decrypted) !== '') {
                    return trim($decrypted);
                }
            } catch (\Throwable $e) {
                // keep original value
            }
        }

        return $key;
    }

    private function normalizeProvider(string $provider): string
    {
        $provider = strtolower(trim($provider));
        $compact = str_replace([' ', '_'], '-', $provider);

        if ($compact === '' || $compact === 'gpt' || $compact === 'chatgpt') {
            return 'openai';
        }

        if (
            Str::contains($compact, 'openai')
            || Str::startsWith($compact, 'gpt-')
            || in_array($compact, ['gpt4', 'gpt-4', 'gpt4o', 'gpt-4o', 'o1', 'o3', 'o4-mini'], true)
        ) {
            return 'openai';
        }

        if (Str::contains($compact, 'groq') || Str::contains($compact, 'llama')) {
            return 'groq';
        }

        if (
            Str::contains($compact, 'anthropic')
            || Str::contains($compact, 'claude')
        ) {
            return 'anthropic';
        }

        if (
            Str::contains($compact, 'gemini')
            || Str::contains($compact, 'google')
        ) {
            return 'gemini';
        }

        return 'openai';
    }

    /**
     * @throws \RuntimeException
     */
    private function generateJobContentByProvider(
        string $provider,
        string $apiKey,
        string $systemPrompt,
        string $userPrompt,
        string $title,
        string $categoryName
    ): array {
        $provider = $this->normalizeProvider($provider);
        $content = '';

        if ($provider === 'openai') {
            $response = Http::timeout(25)
                ->withToken($apiKey)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => env('AI_JOB_GENERATOR_MODEL', 'gpt-4o-mini'),
                    'temperature' => 0.35,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userPrompt],
                    ],
                ]);

            if (! $response->successful()) {
                $message = (string) data_get($response->json(), 'error.message', '');
                throw new \RuntimeException('OpenAI request failed'.($message ? ': '.$message : '.'));
            }

            $responsePayload = (array) $response->json();
            $this->recordAiUsage($provider, env('AI_JOB_GENERATOR_MODEL', 'gpt-4o-mini'), $responsePayload);
            $content = $this->extractOpenAiLikeContent(data_get($responsePayload, 'choices.0.message.content', ''));
        } elseif ($provider === 'groq') {
            $response = Http::timeout(25)
                ->withToken($apiKey)
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => env('AI_JOB_GENERATOR_GROQ_MODEL', 'llama-3.1-8b-instant'),
                    'temperature' => 0.35,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userPrompt],
                    ],
                ]);

            if (! $response->successful()) {
                $message = (string) data_get($response->json(), 'error.message', '');
                throw new \RuntimeException('Groq request failed'.($message ? ': '.$message : '.'));
            }

            $responsePayload = (array) $response->json();
            $this->recordAiUsage($provider, env('AI_JOB_GENERATOR_GROQ_MODEL', 'llama-3.1-8b-instant'), $responsePayload);
            $content = $this->extractOpenAiLikeContent(data_get($responsePayload, 'choices.0.message.content', ''));
        } elseif ($provider === 'anthropic' || $provider === 'claude') {
            $response = Http::timeout(25)
                ->withHeaders([
                    'x-api-key' => $apiKey,
                    'anthropic-version' => '2023-06-01',
                ])
                ->post('https://api.anthropic.com/v1/messages', [
                    'model' => env('AI_JOB_GENERATOR_ANTHROPIC_MODEL', 'claude-3-5-sonnet-20241022'),
                    'max_tokens' => 1200,
                    'temperature' => 0.35,
                    'system' => $systemPrompt,
                    'messages' => [
                        ['role' => 'user', 'content' => $userPrompt],
                    ],
                ]);

            if (! $response->successful()) {
                $message = (string) data_get($response->json(), 'error.message', '');
                throw new \RuntimeException('Anthropic request failed'.($message ? ': '.$message : '.'));
            }

            $responsePayload = (array) $response->json();
            $this->recordAiUsage($provider, env('AI_JOB_GENERATOR_ANTHROPIC_MODEL', 'claude-3-5-sonnet-20241022'), $responsePayload);
            $parts = data_get($responsePayload, 'content', []);
            $content = collect(is_array($parts) ? $parts : [])
                ->map(function ($part) {
                    if (is_array($part) && ($part['type'] ?? '') === 'text') {
                        return (string) ($part['text'] ?? '');
                    }

                    return '';
                })
                ->implode("\n");
        } elseif ($provider === 'gemini') {
            $configuredModel = trim((string) env('AI_JOB_GENERATOR_GEMINI_MODEL', 'gemini-2.0-flash'));

            $fallbackModels = [
                'gemini-2.0-flash',
                'gemini-2.0-flash-lite',
                'gemini-1.5-flash',
                'gemini-1.5-pro',
            ];

            $models = collect(array_merge([$configuredModel], $fallbackModels))
                ->map(fn ($m) => trim((string) $m))
                ->filter()
                ->unique()
                ->values()
                ->all();

            $lastError = '';

            foreach ($models as $model) {
                $url = 'https://generativelanguage.googleapis.com/v1beta/models/'.$model.':generateContent?key='.$apiKey;
                $response = Http::timeout(25)->post($url, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $systemPrompt."\n\n".$userPrompt],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.35,
                    ],
                ]);

                if ($response->successful()) {
                    $responsePayload = (array) $response->json();
                    $this->recordAiUsage($provider, $model, $responsePayload);
                    $content = (string) data_get($responsePayload, 'candidates.0.content.parts.0.text', '');
                    break;
                }

                $lastError = (string) data_get($response->json(), 'error.message', '');
                $isModelNotFound = Str::contains(Str::lower($lastError), ['not found', 'not supported for generatecontent']);
                if (! $isModelNotFound) {
                    throw new \RuntimeException('Gemini request failed'.($lastError ? ': '.$lastError : '.'));
                }
            }

            if ($content === '') {
                throw new \RuntimeException('Gemini request failed'.($lastError ? ': '.$lastError : '.'));
            }
        } else {
            throw new \RuntimeException('Unsupported provider.');
        }

        return $this->parseGeneratedJobContent($content, $title, $categoryName);
    }

    private function extractOpenAiLikeContent($rawContent): string
    {
        if (is_array($rawContent)) {
            return collect($rawContent)
                ->map(function ($part) {
                    if (is_array($part) && isset($part['text'])) {
                        return (string) $part['text'];
                    }

                    return is_string($part) ? $part : '';
                })
                ->implode("\n");
        }

        return (string) $rawContent;
    }

    /**
     * @throws \RuntimeException
     */
    private function parseGeneratedJobContent(string $content, string $title, string $categoryName): array
    {
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
            throw new \RuntimeException('Invalid JSON from AI.');
        }

        $description = (string) ($decoded['description_html'] ?? $decoded['description'] ?? '');
        $requirements = (string) (
            $decoded['job_requirement_html']
            ?? $decoded['requirements_html']
            ?? $decoded['job_requirements']
            ?? $decoded['requirements']
            ?? ''
        );
        $skills = $decoded['skills'] ?? [];
        $metaTitle = trim((string) ($decoded['meta_title'] ?? ''));
        $metaDescription = trim((string) ($decoded['meta_description'] ?? ''));

        if ($description === '' || $requirements === '' || ! is_array($skills)) {
            throw new \RuntimeException('Incomplete AI response.');
        }

        $skills = collect($skills)
            ->filter(fn ($skill) => is_string($skill) && trim($skill) !== '')
            ->map(fn ($skill) => trim($skill))
            ->values()
            ->all();

        if ($metaTitle === '') {
            $metaTitle = Str::limit(trim(strip_tags($description)), 60, '');
        }
        if ($metaDescription === '') {
            $metaDescription = Str::limit(trim(strip_tags($description)), 155, '');
        }

        return [
            'description_html' => $description,
            'job_requirement_html' => $requirements,
            'skills' => $skills,
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescription,
        ];
    }

    private function shouldRetryWithAnotherProvider(string $errorMessage): bool
    {
        $message = Str::lower($errorMessage);

        return Str::contains($message, [
            'quota',
            'rate limit',
            'too many requests',
            'insufficient_quota',
            'billing',
            'not found',
            'not supported',
            'timed out',
            'timeout',
            'temporarily unavailable',
            'service unavailable',
        ]);
    }

    private function isQuotaExceededError(string $errorMessage): bool
    {
        $message = Str::lower($errorMessage);

        return Str::contains($message, [
            'quota',
            'insufficient_quota',
            'billing',
            'rate limit',
            'free_tier_requests',
            'free_tier_input_token_count',
        ]);
    }

    /** Always attach the required Upload Resume question to every job. */
    private function jobQuestionsWithResume($questionIds): array
    {
        $questionIds = array_filter((array) $questionIds);
        $resumeQuestionId = Question::whereRaw('LOWER(TRIM(question)) = ?', ['upload resume'])->value('id');

        if ($resumeQuestionId) {
            $questionIds[] = $resumeQuestionId;
        }

        return array_values(array_unique($questionIds));
    }

    private function maskApiKey(string $key): string
    {
        $key = trim($key);
        if ($key === '') {
            return '';
        }

        if (strlen($key) <= 8) {
            return str_repeat('*', strlen($key));
        }

        return substr($key, 0, 4).'...'.substr($key, -4);
    }
}
