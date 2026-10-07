<?php

namespace App\Http\Controllers\Admin;

use App\EmailSetting;
use App\InterviewSchedule;
use App\Job;
use App\JobApplication;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Company;
use App\SmsSetting;
use App\User;

class AdminDashboardController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct();
        
        $this->pageIcon = 'icon-speedometer';
        $this->pageTitle = __('menu.dashboard');
    }

    public function index()
    {        
        $this->smsSettings = SmsSetting::first();
        $this->teamMembers = User::with('role.role')->orderBy('name')->get();
        $this->onlineTeamMemberIds = User::query()
            ->where('last_seen_at', '>=', now()->subSeconds(75))
            ->pluck('id')
            ->push((int) $this->user->id)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        $this->totalOpenings = Job::where('status', 'active')->count();
        
        $this->totalCompanies = Company::count();
        $this->totalApplications = JobApplication::withTrashed()
            ->whereNull('moved_to_trash_at')
            ->count();
        $this->totalHired = JobApplication::join('application_status', 'application_status.id', '=', 'job_applications.status_id')
            ->where('application_status.status', 'hired')
            ->count();
            $this->totalRejected = JobApplication::join('application_status', 'application_status.id', '=', 'job_applications.status_id')
            ->where('application_status.status', 'rejected')
            ->count();
            $this->newApplications = JobApplication::join('application_status', 'application_status.id', '=', 'job_applications.status_id')
            ->where('application_status.status', 'applied')
            ->count();
        $this->candidateMarketing = JobApplication::where('is_marketing', 1)->count();

        $currentDate = Carbon::now()->format('Y-m-d');

        $this->totalTodayInterview = InterviewSchedule::where(DB::raw('DATE(`schedule_date`)'), "$currentDate")
            ->count();

        $this->progressPercent = $this->progressbarPercent();
        $this->todoItemsView = $this->generateTodoView();

        return view('admin.dashboard.index', $this->data);
    }

    private function progressbarPercent()
    {
        $totalItems = 4;
        $completedItem = 1;
        $progress = [];
        $progress['progress_completed'] = false;

        $smtpSetting = EmailSetting::first();
        if ($this->global->company_email != 'company@example.com') {
            $completedItem++;
            $progress['company_setting_completed'] = true;
        }

        if ($smtpSetting->verified !== 0 || $smtpSetting->mail_driver == 'mail') {
            $progress['smtp_setting_completed'] = true;

            $completedItem++;
        }

        if ($this->user->email != 'admin@example.com') {
            $progress['profile_setting_completed'] = true;

            $completedItem++;
        }


        if ($totalItems == $completedItem) {
            $progress['progress_completed'] = true;
        }

        $this->progress = $progress;


        return ($completedItem / $totalItems) * 100;

    }
}
