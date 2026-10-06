<?php

namespace App\Http\Controllers\Front;

use App\Job;
use App\User;
use App\Skill;
use App\Company;
use App\JobType;
use App\JobAlert;
use Carbon\Carbon;
use App\JobCategory;
use App\JobLocation;
use App\Helper\Files;
use App\Helper\Reply;
use App\FooterSetting;
use App\JobApplication;
use App\WorkExperience;
use App\LanguageSetting;
use App\LinkedInSetting;
use App\ApplicationSetting;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\JobApplicationAnswer;
use App\Mail\ReceivedApplication;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\StoreJobAlert;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use App\Notifications\NewJobApplication;
use Laravel\Socialite\Facades\Socialite;
use App\Http\Requests\FrontJobApplication;
use Illuminate\Support\Facades\Notification;
use App\Http\Controllers\Front\FrontBaseController;
use App\JobJobLocation;
use App\Services\ResumePdfConverter;
use App\Services\ResumeTextExtractor;
use App\Jobs\ParseApplicantResume;
use Illuminate\Validation\ValidationException;

class FrontJobsController extends FrontBaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = __('modules.front.jobOpenings');

        $linkedinSetting = LinkedInSetting::where('status', 'enable')->first();
        $this->linkedinGlobal = LinkedInSetting::first();
        $this->perPage = 6;

        if ($linkedinSetting) {
            Config::set('services.linkedin.client_id',     $linkedinSetting->client_id);
            Config::set('services.linkedin.client_secret', $linkedinSetting->client_secret);
            Config::set('services.linkedin.redirect',      $linkedinSetting->callback_url);
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  Shared query builder — avoids repeating the base join block
    // ─────────────────────────────────────────────────────────────
    private function baseJobLocationQuery()
    {
        $query = JobJobLocation::select(
                'job_job_locations.id as id',
                'jobs.id as job_id',
                'job_locations.id as location_id'
            )
            ->with(['job', 'location'])
            ->join('job_locations', 'job_locations.id', '=', 'job_job_locations.location_id')
            ->join('jobs', 'jobs.id', '=', 'job_job_locations.job_id')
            ->where('jobs.status', 'active')
            ->whereDate('jobs.start_date', '<=', Carbon::now()->format('Y-m-d'))
            ->where(function ($q) {
                $q->where('jobs.end_date', '>=', Carbon::now()->format('Y-m-d'))
                  ->orWhereNull('jobs.end_date');
            });

        return $query;
    }

    // ─────────────────────────────────────────────────────────────
    //  Apply search filters onto an existing query (shared by both
    //  moreData() and searchJob())
    // ─────────────────────────────────────────────────────────────
    private function applySearchFilters($query, Request $request)
    {
        if ($request->location_id !== '' && $request->location_id !== 'all') {
            $query->where('job_job_locations.location_id', $request->location_id);
        }

        if ($request->category !== '' && $request->category !== 'all') {
            $query->where('jobs.category_id', $request->category);
        }

        if ($request->company !== '' && $request->company !== 'all') {
            $query->where('jobs.company_id', $request->company);
        }

        if ($request->skill !== '' && $request->skill !== 'all') {
            $query->leftJoin('job_skills', 'job_skills.job_id', '=', 'jobs.id')
                  ->where('job_skills.skill_id', $request->skill);
        }

        return $query;
    }

    // ─────────────────────────────────────────────────────────────
    //  Public job board
    // ─────────────────────────────────────────────────────────────
    public function jobOpenings()
    {
        $metaDetails = json_decode($this->global->meta_details);

        $this->metaTitle       = isset($metaDetails->title)       ? $metaDetails->title       : '';
        $this->metaDescription = isset($metaDetails->description) ? $metaDetails->description : '';
        $this->metaImage       = $this->global->logo_url;

        $this->locations   = JobLocation::all();
        $this->categories  = JobCategory::all();
        $this->skills      = Skill::all();
        $this->companies   = Company::all();

        // All currently open jobs, independent of the retired board flags.
        $jobLocations       = $this->baseJobLocationQuery();
        $this->jobCount     = $jobLocations->count();
        $this->jobLocations = (clone $jobLocations)->take($this->perPage)->get();
        $this->perPage      = $this->perPage;

        return view('front.job-openings', $this->data);
    }

    // ─────────────────────────────────────────────────────────────
    //  ASSISTMYDAY homepage
    // ─────────────────────────────────────────────────────────────
    public function assistMyDay()
    {
        $this->pageTitle = 'AssistMyDay - Job Openings';

        $this->locations  = JobLocation::all();
        $this->categories = JobCategory::all();
        $this->skills     = Skill::all();
        $this->companies  = Company::all();

        // Legacy renderer uses the same publication rules.
        $jobLocations       = $this->baseJobLocationQuery();
        $this->jobCount     = $jobLocations->count();
        $this->jobLocations = (clone $jobLocations)->take($this->perPage)->get();
        $this->perPage      = $this->perPage;

        return view('front.assistmyday', $this->data);
    }

    // ─────────────────────────────────────────────────────────────
    //  LOAD MORE — serves both pages via the same AJAX route.
    // ─────────────────────────────────────────────────────────────
    public function moreData(Request $request)
    {
        if (!$request->ajax()) {
            abort(403);
        }

        $this->locations  = JobLocation::all();
        $this->categories = JobCategory::all();

        $jobLocations = $this->baseJobLocationQuery();
        $jobLocations = $this->applySearchFilters($jobLocations, $request);

        $this->jobLocationCount = $jobLocations->count();

        $totalCurrentData   = (int) $request->totalCurrentData;
        $this->jobLocations = $jobLocations->get()->skip($totalCurrentData)->take($this->perPage);

        $this->job_current_count = $totalCurrentData + $this->perPage;
        $this->hideButton        = $this->job_current_count > $this->jobLocationCount ? 'yes' : 'no';

        $view = view('front.more_data', $this->data)->render();
        return Reply::dataOnly([
            'status' => 'success',
            'view'   => $view,
            'data'   => $this->data,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    //  SEARCH — also serves both pages via the same AJAX route.
    //  Uses the same publication rules as moreData().
    // ─────────────────────────────────────────────────────────────
    public function searchJob(Request $request)
    {
        $this->locations  = JobLocation::all();
        $this->categories = JobCategory::all();
        $this->skills     = Skill::all();
        $this->companies  = Company::all();

        $jobLocations = $this->baseJobLocationQuery();
        $jobLocations = $this->applySearchFilters($jobLocations, $request);

        $totalCurrentData       = (int) ($request->totalCurrentData ?? 0);
        $this->jobLocationCount = $jobLocations->count();
        $this->jobLocations     = $jobLocations->get()->skip($totalCurrentData)->take($this->perPage);
        $this->job_current_count = $totalCurrentData + $this->perPage;

        $this->hideButton = $this->job_current_count > $this->jobLocationCount ? 'yes' : 'no';

        $view = view('front.more_data', $this->data)->render();
        return Reply::dataOnly([
            'status' => 'success',
            'view'   => $view,
            'data'   => $this->data,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    //  Kept for internal use only (old helper, still needed by
    //  any legacy call sites you may have)
    // ─────────────────────────────────────────────────────────────
    public function data($request)
    {
        $jobs = Job::where('status', 'active')
            ->where('start_date', '<=', Carbon::now()->format('Y-m-d'))
            ->where('end_date', '>=', Carbon::now()->format('Y-m-d'));

        if ($request->category !== null && $request->category != 'all') {
            $jobs = $jobs->where('category_id', $request->category);
        }

        if ($request->location_id !== null && $request->location_id != 'all') {
            $jobs = $jobs->where('location_id', $request->location_id);
        }

        if ($request->skill !== null && $request->skill != 'all') {
            $jobs = $jobs->join('job_skills', 'job_skills.job_id', 'jobs.id')
                         ->where('job_skills.skill_id', $request->skill);
        }

        if ($request->company !== null && $request->company != 'all') {
            $jobs = $jobs->where('company_id', $request->company);
        }

        return $jobs->get();
    }

    // ─────────────────────────────────────────────────────────────
    //  All remaining methods — unchanged from your original
    // ─────────────────────────────────────────────────────────────

    public function customPage($slug)
    {
        $this->customPage = FooterSetting::where('slug', $slug)->where('status', 'active')->first();

        if (is_null($this->customPage)) {
            abort(404);
        }

        $this->pageTitle       = ucfirst($this->customPage->name);
        $this->metaTitle       = $this->customPage->name;
        $this->metaDescription = $this->customPage->description;

        return view('front.custom-page', $this->data);
    }

    public function jobDetail($slug, $location = null)
    {
        $this->job = Job::with(['workExperience', 'jobType'])
            ->where('slug', $slug)
            ->whereDate('start_date', '<=', Carbon::now())
            ->where(function ($query) {
                $query->whereNull('end_date')->orWhereDate('end_date', '>=', Carbon::now());
            })
            ->where('status', 'active')
            ->firstOrFail();

        Session::put('lastPageUrl', $slug);

        $locationId       = JobJobLocation::where('job_id', $this->job->id)->where('location_id', $location)->first()?->id;
        $this->location   = JobJobLocation::withoutGlobalScope('company')->find($locationId);
        $this->locations  = ($this->location && $this->location->location_id)
            ? JobLocation::withoutGlobalScope('company')->where('id', $this->location->location_id)->first()
            : null;

        $this->linkedinGlobal = LinkedInSetting::first();
        Session::put('slug', $slug);

        $this->pageTitle       = $this->job->title . ' - ' . $this->companyName;
        $this->metaTitle       = $this->job->meta_details['title']       ?? '';
        $this->metaDescription = $this->job->meta_details['description'] ?? '';
        $this->metaImage       = $this->job->company->logo_url;
        $this->pageUrl         = request()->url();

        return view('front.job-detail', $this->data);
    }

    public function callback($provider, Request $request)
    {
        if ($request->error) {
            $this->errorCode = $request->error;
            $this->error     = $request->error_description;
            return view('errors.linkedin', $this->data);
        }

        $this->user       = Socialite::driver($provider)->user();
        $this->lastPageUrl = Session::get('lastPageUrl');
        Session::put('accessToken', $this->user->token);
        Session::put('expiresIn',   $this->user->expiresIn);

        return redirect()->route('jobs.jobApply', $this->lastPageUrl);
    }

    public function redirect($provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    public function jobApply($slug, $location = null)
    {
        $this->job = Job::where('slug', $slug)->where('status', 'active')->first();
        abort_if(!$this->job, 404);

        $this->metaTitle       = $this->job->meta_details['title']       ?? '';
        $this->metaDescription = $this->job->meta_details['description'] ?? '';
        $this->metaImage       = $this->job->company->logo_url;

        $this->location = JobLocation::where('id', $location)->first()
            ?? JobLocation::withoutGlobalScope('company')->where('id', $location)->first();

        $this->accessToken = Session::get('accessToken');
        $this->user        = $this->accessToken
            ? Socialite::driver('linkedin')->userFromToken($this->accessToken)
            : [];

        $this->jobQuestion        = $this->job->questions;
        $this->applicationSetting = ApplicationSetting::first();
        $this->pageTitle          = $this->job->title . ' - ' . $this->companyName;

        return view('front.job-apply', $this->data);
    }

    public function saveApplication(FrontJobApplication $request)
    {
        $resumeUpload = $request->file('resume');
        $resumeConverter = app(ResumePdfConverter::class);
        $resumeText = '';
        $hasResumeUpload = (bool) $resumeUpload;
        if ($resumeUpload) {
            try {
                $resumeUpload = $resumeConverter->convert($resumeUpload);
            } catch (\Throwable $e) {
                report($e);
                throw ValidationException::withMessages([
                    'resume' => 'The Word resume could not be converted to PDF. Please upload a valid DOC, DOCX, or PDF file.',
                ]);
            }
            try {
                $resumeText = app(ResumeTextExtractor::class)->extract($resumeUpload);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        // Some job forms collect the CV through a file-type custom question
        // instead of the dedicated resume field. Convert those uploads too.
        $questionUploads = [];
        try {
            foreach ((array) $request->file('answer', []) as $questionId => $upload) {
                if (!$upload) continue;
                $questionUploads[$questionId] = $resumeConverter->convert($upload);
            }
        } catch (\Throwable $e) {
            foreach ($questionUploads as $convertedUpload) $resumeConverter->cleanup($convertedUpload);
            report($e);
            throw ValidationException::withMessages([
                'answer.'.$questionId => 'The uploaded Word document could not be converted to PDF. Please upload a valid DOC, DOCX, or PDF file.',
            ]);
        }

        $uploadQuestions = empty($questionUploads)
            ? collect()
            : \App\Question::whereIn('id', array_keys($questionUploads))->get()->keyBy('id');

        $jobApplication           = new JobApplication();
        $jobApplication->candidate_origin = 'job_application';
        $jobApplication->full_name = $request->full_name;
        $jobApplication->job_id   = $request->job_id;

        $jobLocationData = JobJobLocation::where('job_id', $request->job_id)->first();
        if ($jobLocationData) {
            $jobApplication->location_id = $jobLocationData->location_id;
        }

        $jobApplication->status_id = \App\ApplicationStatus::initialForJob((int) $request->job_id)?->id;
        $jobApplication->email     = $request->email;
        $jobApplication->phone     = $request->phone;

        if ($request->has('gender'))  $jobApplication->gender  = $request->gender;
        if ($request->has('address')) $jobApplication->address = $request->address;
        if ($request->has('dob'))     $jobApplication->dob     = $request->dob;

        if ($request->has('country')) {
            $countriesArray = json_decode(file_get_contents(public_path('country-state-city/countries.json')), true)['countries'];
            $statesArray    = json_decode(file_get_contents(public_path('country-state-city/states.json')),    true)['states'];

            $jobApplication->country  = $this->getName($countriesArray, $request->country);
            $jobApplication->state    = $this->getName($statesArray,    $request->state);
            $jobApplication->city     = $request->city;
            $jobApplication->zip_code = $request->zip_code;
        }

        $jobApplication->cover_letter    = $request->cover_letter;
        $jobApplication->column_priority = 0;

        if ($request->hasFile('photo')) {
            $jobApplication->photo = Files::uploadLocalOrS3($request->photo, 'candidate-photos');
        }

        $jobApplication->save();

        if ($resumeUpload) {
            try {
                $hashname = Files::uploadLocalOrS3($resumeUpload, 'documents/' . $jobApplication->id, null, null, false);
                $jobApplication->documents()->create([
                    'name'     => 'Resume',
                    'hashname' => $hashname,
                ]);
            } finally {
                $resumeConverter->cleanup($resumeUpload);
            }
        }

        if ($request->linkedinPhoto) {
            $contents    = file_get_contents($request->linkedinPhoto);
            $filename    = $jobApplication->id . str_replace(' ', '_', $request->full_name) . '.png';
            Storage::put('candidate-photos/' . $filename, $contents);
            $jobApplication->photo = $filename;
            $jobApplication->save();
        }

        if (!empty($request->answer)) {
            foreach ($request->answer as $key => $value) {
                $answer                     = new JobApplicationAnswer();
                $answer->job_application_id = $jobApplication->id;
                $answer->job_id             = $jobApplication->job_id;
                $answer->question_id        = $key;

                if ($request->hasFile('answer.' . $key)) {
                    $questionUpload = $questionUploads[$key] ?? $value;
                    $questionText = (string) optional($uploadQuestions->get($key))->question;
                    $isResumeQuestion = (bool) preg_match('/\b(resume|cv|curriculum\s+vitae)\b/i', $questionText);

                    try {
                        if ($isResumeQuestion) {
                            $hasResumeUpload = true;
                            if ($resumeText === '') {
                                try {
                                    $resumeText = app(ResumeTextExtractor::class)->extract($questionUpload);
                                } catch (\Throwable $e) {
                                    report($e);
                                }
                            }
                            $hashname = Files::uploadLocalOrS3($questionUpload, 'documents/'.$jobApplication->id);
                            $answer->file = $jobApplication->id.'/'.$hashname;
                            $jobApplication->documents()->updateOrCreate(
                                ['name' => 'Resume'],
                                ['hashname' => $hashname]
                            );
                        } else {
                            $answer->file = Files::uploadLocalOrS3($questionUpload, 'documents');
                        }
                    } finally {
                        $resumeConverter->cleanup($questionUpload);
                    }
                } else {
                    $answer->answer = $value;
                }
                $answer->save();
            }
        }

        if ($resumeText !== '') {
            $jobApplication->forceFill([
                'cv_text' => mb_substr($resumeText, 0, 65000),
                'cv_index_failed' => false,
            ])->save();
            ParseApplicantResume::dispatchAfterResponse($jobApplication->id);
        } elseif ($hasResumeUpload) {
            $jobApplication->forceFill([
                'cv_indexed_at' => now(),
                'cv_index_failed' => true,
            ])->save();
        }

        $linkedin = $request->has('apply_type');
        $users    = User::allAdmins();
        $global   = $this->global;

        try {
            Notification::send($users, new NewJobApplication($jobApplication, $linkedin));
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
        }

        try {
            Mail::send(new ReceivedApplication($jobApplication, $global));
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
        }

        return Reply::dataOnly(['status' => 'success', 'msg' => __('modules.front.applySuccessMsg')]);
    }

    public function fetchCountryState(Request $request)
    {
        $responseArr = [];

        switch ($request->type) {
            case 'getCountries':
                $countriesArray = json_decode(file_get_contents(public_path('country-state-city/countries.json')), true)['countries'];
                foreach ($countriesArray as $country) {
                    $responseArr = Arr::add($responseArr, $country['id'], $country['name']);
                }
                break;

            case 'getStates':
                $statesArray = json_decode(file_get_contents(public_path('country-state-city/states.json')), true)['states'];
                $countryId   = $request->countryId;
                $filtered    = array_filter($statesArray, fn($v) => $v['country_id'] == $countryId);
                foreach ($filtered as $state) {
                    $responseArr = Arr::add($responseArr, $state['id'], $state['name']);
                }
                break;
        }

        return response()->json([
            'status' => 'success',
            'tp'     => 1,
            'msg'    => 'Countries fetched successfully.',
            'result' => $responseArr,
        ]);
    }

    public function getName($arr, $id)
    {
        $result = array_filter($arr, fn($v) => $v['id'] == $id);
        return current($result)['name'];
    }

    public function changeLanguage($code)
    {
        $language = LanguageSetting::where('language_code', $code)->first();
        if (!$language) {
            return Reply::error('invalid language code');
        }
        return response(Reply::success(__('messages.languageChangedSuccessfully')))->cookie('language_code', $code);
    }

    public function jobAlert()
    {
        $this->jobCategorys    = JobCategory::all();
        $this->locations       = JobLocation::all();
        $this->workExperiences = WorkExperience::all();
        $this->jobTypes        = JobType::all();
        return view('front.job-alert', $this->data);
    }

    public function saveJobAlert(StoreJobAlert $request)
    {
        $jobAlert                   = new JobAlert();
        $jobAlert->email            = $request->email;
        $jobAlert->work_experience_id = $request->workExperience;
        $jobAlert->job_type_id      = $request->jobType;
        $jobAlert->status           = 'active';
        $jobAlert->hash             = str_random(16);
        $jobAlert->save();

        $jobAlert->alertCategory()->sync($request->jobCategory);
        $jobAlert->alertLocation()->sync($request->location);

        return Reply::success(__('messages.jobAlert'));
    }

    public function disableJobAlert()
    {
        JobAlert::where('id', request()->id)->update(['status' => 'inactive']);
        return Reply::redirect(route('jobs.jobOpenings'), __('messages.disableJobAlert'));
    }
    public function assistMyDayJobDetail($slug, $location = null)
    {
        $this->job = Job::with(['workExperience', 'jobType'])
            ->where('slug', $slug)
            ->whereDate('start_date', '<=', Carbon::now())
            ->where(function ($query) {
                $query->whereNull('end_date')->orWhereDate('end_date', '>=', Carbon::now());
            })
            ->where('status', 'active')
            ->firstOrFail();

        Session::put('lastPageUrl', $slug);

        $locationId     = JobJobLocation::where('job_id', $this->job->id)
                            ->where('location_id', $location)->first()?->id;
        $this->location  = JobJobLocation::withoutGlobalScope('company')->find($locationId);
        $this->locations = ($this->location && $this->location->location_id)
            ? JobLocation::withoutGlobalScope('company')->where('id', $this->location->location_id)->first()
            : null;

        $this->linkedinGlobal  = LinkedInSetting::first();
        $this->pageTitle       = $this->job->title . ' - AssistMyDay';
        $this->metaTitle       = $this->job->meta_details['title']       ?? '';
        $this->metaDescription = $this->job->meta_details['description'] ?? '';
        $this->metaImage       = $this->job->company->logo_url;
        $this->pageUrl         = request()->url();

        Session::put('slug', $slug);

        return view('front.assistmyday-job-detail', $this->data);
    }
    public function checkApplicantEmail(Request $request)
    {
        $email = trim($request->input('email', ''));
        $jobId = (int) $request->input('job_id', 0);

        if (!$email || !$jobId) {
            return response()->json(['exists' => false]);
        }

        $exists = \App\JobApplication::where('email', $email)
            ->where('is_candidate', 0)
            ->exists();

        return response()->json(['exists' => $exists]);
    }
}
