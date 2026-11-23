<?php

namespace App\Http\Controllers\Admin;

use App\ConsortiumRegistration;
use App\ApplicationStatus;
use App\Job;
use App\JobApplication;
use App\JobApplicationStatusHistory;
use App\JobJobLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class AdminConsortiumRegistrationController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'Consortium Registrations';
        $this->pageIcon = 'icon-user-follow';
    }

    public function index(Request $request)
    {
        $multiFilterNames = ['month', 'year', 'gender', 'city', 'job_type', 'available_weekends', 'night_shifts'];
        foreach ($multiFilterNames as $filterName) {
            $values = array_values(array_filter((array) $request->input($filterName, []), fn ($value) => $value !== null && $value !== ''));
            $request->merge([$filterName => $values]);
        }

        $filters = $request->validate([
            'month' => ['nullable', 'array'],
            'month.*' => ['integer', 'between:1,12'],
            'year' => ['nullable', 'array'],
            'year.*' => ['integer', 'between:2000,2100'],
            'gender' => ['nullable', 'array'],
            'gender.*' => ['string', 'max:40'],
            'city' => ['nullable', 'array'],
            'city.*' => ['string', 'max:100'],
            'job_type' => ['nullable', 'array'],
            'job_type.*' => ['string', 'max:100'],
            'available_weekends' => ['nullable', 'array'],
            'available_weekends.*' => ['in:0,1'],
            'night_shifts' => ['nullable', 'array'],
            'night_shifts.*' => ['in:0,1'],
        ]);

        $query = ConsortiumRegistration::query()
            ->when(!empty($filters['month']), fn ($q) => $q->whereIn(DB::raw('MONTH(created_at)'), array_map('intval', $filters['month'])))
            ->when(!empty($filters['year']), fn ($q) => $q->whereIn(DB::raw('YEAR(created_at)'), array_map('intval', $filters['year'])))
            ->when(!empty($filters['gender']), fn ($q) => $q->whereIn('gender', $filters['gender']))
            ->when(!empty($filters['city']), fn ($q) => $q->whereIn('city', $filters['city']))
            ->when(!empty($filters['job_type']), fn ($q) => $q->whereIn('preferred_job_type', $filters['job_type']))
            ->when(!empty($filters['available_weekends']), fn ($q) => $q->whereIn('available_weekends', array_map('intval', $filters['available_weekends'])))
            ->when(!empty($filters['night_shifts']), fn ($q) => $q->whereIn('available_night_shifts', array_map('intval', $filters['night_shifts'])));

        $this->registrations = $query->latest()->paginate(25)->withQueryString();
        $this->unreviewedCount = ConsortiumRegistration::whereNull('reviewed_at')->count();
        $this->filterMonths = ConsortiumRegistration::selectRaw('MONTH(created_at) AS month')->distinct()->orderBy('month')->pluck('month');
        $this->filterYears = ConsortiumRegistration::selectRaw('YEAR(created_at) AS year')->distinct()->orderByDesc('year')->pluck('year');
        $this->filterGenders = ConsortiumRegistration::whereNotNull('gender')->where('gender', '<>', '')->distinct()->orderBy('gender')->pluck('gender');
        $this->filterCities = ConsortiumRegistration::whereNotNull('city')->where('city', '<>', '')->distinct()->orderBy('city')->pluck('city');
        $this->filterJobTypes = ConsortiumRegistration::whereNotNull('preferred_job_type')->where('preferred_job_type', '<>', '')->distinct()->orderBy('preferred_job_type')->pluck('preferred_job_type');

        return view('admin.consortium-registrations.index', $this->data);
    }

    public function show(Request $request, ConsortiumRegistration $registration)
    {
        if (!$registration->reviewed_at) {
            $registration->update(['reviewed_at' => now()]);
        }
        $this->registration = $registration;

        $asArray = fn ($name) => array_values(array_filter((array) $request->input($name, []), fn ($value) => $value !== null && $value !== ''));
        $months = array_map('intval', $asArray('month'));
        $years = array_map('intval', $asArray('year'));
        $genders = $asArray('gender');
        $cities = $asArray('city');
        $jobTypes = $asArray('job_type');
        $weekends = array_map('intval', $asArray('available_weekends'));
        $nightShifts = array_map('intval', $asArray('night_shifts'));

        $this->registrationNavigationIds = ConsortiumRegistration::query()
            ->when($months, fn ($query) => $query->whereIn(DB::raw('MONTH(created_at)'), $months))
            ->when($years, fn ($query) => $query->whereIn(DB::raw('YEAR(created_at)'), $years))
            ->when($genders, fn ($query) => $query->whereIn('gender', $genders))
            ->when($cities, fn ($query) => $query->whereIn('city', $cities))
            ->when($jobTypes, fn ($query) => $query->whereIn('preferred_job_type', $jobTypes))
            ->when($weekends, fn ($query) => $query->whereIn('available_weekends', $weekends))
            ->when($nightShifts, fn ($query) => $query->whereIn('available_night_shifts', $nightShifts))
            ->latest()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        $this->jobs = Job::with(['company:id,company_name', 'location:id,location', 'jobLocation'])
            ->orderBy('title')
            ->get();
        $this->jobMoves = $registration->jobMoves()
            ->with(['job:id,title,company_id', 'job.company:id,company_name', 'application:id,job_id,status_id', 'movedBy:id,name'])
            ->get();

        $this->tempStaffingHistories = $registration->tempStaffingHistories()
            ->with('user:id,name')
            ->get();

        $profileApplication = $this->jobMoves->first()?->application;
        if (!$profileApplication) {
            $profileApplication = JobApplication::withTrashed()
                ->where('is_candidate', 1)
                ->whereRaw('LOWER(email) = ?', [mb_strtolower(trim((string) $registration->email))])
                ->latest('id')
                ->first();
        }
        if (!$profileApplication) {
            $profileApplication = new JobApplication;
            $profileApplication->candidate_origin = 'registration';
            $profileApplication->full_name = trim($registration->first_name.' '.$registration->last_name);
            $profileApplication->email = $registration->email;
            $profileApplication->phone = $registration->phone;
            $profileApplication->address = $registration->street_address;
            $profileApplication->city = $registration->city;
            $profileApplication->gender = $registration->gender;
            $profileApplication->dob = $registration->date_of_birth;
            $profileApplication->cover_letter = $registration->additional_information ?: '';
            $profileApplication->is_candidate = 1;
            $profileApplication->column_priority = 0;
            $profileApplication->save();
        }
        if ($registration->resume_file && !$profileApplication->documents()->where('name', 'Resume')->exists()) {
            $source = 'registration-resumes/'.$registration->resume_file;
            $destination = 'documents/'.$profileApplication->id.'/'.$registration->resume_file;
            if (Storage::exists($source) && Storage::copy($source, $destination)) {
                $profileApplication->documents()->create([
                    'name' => 'Resume',
                    'hashname' => $registration->resume_file,
                    'original_name' => $registration->resume_original_name ?: $registration->resume_file,
                ]);
            }
        }
        $this->profileApplicationId = $profileApplication->id;
        return view('admin.consortium-registrations.show', $this->data);
    }

    public function resume(Request $request, ConsortiumRegistration $registration)
    {
        abort_unless($this->user->cans('view_job_applications'), 403);
        $file = $registration->locateResume();
        abort_unless($file, 404, 'The registration résumé could not be found in storage.');
        $name = $registration->resume_original_name ?: $registration->resume_file;

        return $file['disk']->response($file['path'], $name, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ], $request->boolean('inline') ? 'inline' : 'attachment');
    }

    public function moveToJob(Request $request, ConsortiumRegistration $registration)
    {
        $validated = $request->validate([
            'job_id' => ['required', 'integer', 'exists:jobs,id'],
        ]);
        $job = Job::findOrFail($validated['job_id']);

        if ($registration->jobMoves()->where('job_id', $job->id)->exists()) {
            return back()->with('error', 'This candidate has already been moved to the selected job.');
        }

        $appliedStatus = ApplicationStatus::ensureAppliedForJob((int) $job->id);
        $jobLocations = JobJobLocation::query()
            ->where('job_id', $job->id)
            ->whereNotNull('location_id')
            ->with('location_data:id,location')
            ->orderBy('id')
            ->get()
            ->pluck('location_data')
            ->filter();
        $matchingLocation = $jobLocations->first(function ($location) use ($registration) {
            return mb_strtolower(trim((string) $location->location)) === mb_strtolower(trim((string) $registration->city));
        });
        $locationId = $matchingLocation?->id ?: ($jobLocations->first()?->id ?: $job->location_id);

        if (!$locationId) {
            return back()->with('error', 'The selected job has no location configured. Add a job location before moving this candidate.');
        }
        $copiedResumePath = null;

        try {
            $application = DB::transaction(function () use ($registration, $job, $appliedStatus, $locationId, &$copiedResumePath) {
                $application = new JobApplication;
            $application->candidate_origin = 'registration';
            $application->origin_user_id = auth()->id();
                $application->full_name = trim($registration->first_name.' '.$registration->last_name);
                $application->email = $registration->email;
                $application->phone = $registration->phone;
                $application->job_id = $job->id;
                $application->status_id = $appliedStatus->id;
                $application->location_id = $locationId;
                $application->address = $registration->street_address;
                $application->city = $registration->city;
                $application->gender = $registration->gender;
                $application->dob = $registration->date_of_birth;
                $application->cover_letter = $registration->additional_information ?: '';
                $application->column_priority = 0;
                $application->is_candidate = 0;
                $application->save();

                if ($registration->resume_file) {
                    $source = 'registration-resumes/'.$registration->resume_file;
                    $copiedResumePath = 'documents/'.$application->id.'/'.$registration->resume_file;
                    if (! Storage::exists($source) || ! Storage::copy($source, $copiedResumePath)) {
                        throw new \RuntimeException('The registration resume could not be copied to the job application.');
                    }
                    $application->documents()->create([
                        'name' => 'Resume',
                        'hashname' => $registration->resume_file,
                        'original_name' => $registration->resume_original_name ?: $registration->resume_file,
                    ]);
                }

                JobApplicationStatusHistory::create([
                    'job_application_id' => $application->id,
                    'from_status_id' => null,
                    'to_status_id' => $appliedStatus->id,
                    'user_id' => $this->user->id,
                    'notes' => 'Moved from Consortium Registration to '.$job->title,
                ]);

                $registration->jobMoves()->create([
                    'job_application_id' => $application->id,
                    'job_id' => $job->id,
                    'moved_by' => $this->user->id,
                ]);

                return $application;
            });
        } catch (\Throwable $exception) {
            if ($copiedResumePath) Storage::delete($copiedResumePath);
            report($exception);
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Candidate moved to '.$job->title.' and added to Job Applications.');
    }
    public function tempStaffingIndex(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $this->pageTitle = 'Temp Staffing';
        $this->applications = JobApplication::query()
            ->where('is_temp_staffing', true)
            ->whereNull('moved_to_trash_at')
            ->with(['job:id,title,company_id', 'job.company:id,company_name', 'status:id,status,color'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('full_name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%')
                        ->orWhere('city', 'like', '%'.$search.'%');
                });
            })
            ->latest('temp_staffing_at')
            ->paginate(25, ['*'], 'application_page')
            ->withQueryString();
        $this->registrations = null;
        if (app(\App\Saas\TenantContext::class)->featureEnabled('consortium_registration')) {
            $this->registrations = ConsortiumRegistration::query()
                ->where('is_temp_staffing', true)
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($inner) use ($search) {
                        $inner->where('first_name', 'like', '%'.$search.'%')
                            ->orWhere('last_name', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%')
                            ->orWhere('phone', 'like', '%'.$search.'%')
                            ->orWhere('city', 'like', '%'.$search.'%');
                    });
                })
                ->latest('temp_staffing_at')
                ->paginate(25)
                ->withQueryString();
        }

        return view('admin.temp-staffing.index', $this->data);
    }

    public function toggleTempStaffing(Request $request, ConsortiumRegistration $registration)
    {
        $add = $request->boolean('add');
        $registration->forceFill([
            'is_temp_staffing' => $add,
            'temp_staffing_at' => $add ? now() : null,
            'temp_staffing_by' => $add ? $this->user->id : null,
        ])->save();

        $registration->tempStaffingHistories()->create([
            'user_id' => $this->user->id,
            'action' => $add ? 'added' : 'removed',
        ]);

        return back()->with('success', $add
            ? 'Candidate added to Temp Staffing.'
            : 'Candidate removed from Temp Staffing.');
    }
    public function destroy(ConsortiumRegistration $registration)
    {
        abort_if(! auth()->user()->hasRole('admin'), 403);
        $registration->delete();

        return redirect()->route('admin.consortium-registrations.index')
            ->with('success', 'Registration moved to Trash.');
    }
}
