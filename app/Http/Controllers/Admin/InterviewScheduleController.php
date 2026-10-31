<?php

namespace App\Http\Controllers\Admin;

use App\ApplicationStatus;
use App\Helper\Reply;
use App\Http\Requests\InterviewSchedule\StoreRequest;
use App\Http\Requests\InterviewSchedule\UpdateRequest;
use App\InterviewSchedule;
use App\InterviewScheduleEmployee;
use App\JobApplication;
use App\Notifications\CandidateNotify;
use App\Notifications\CandidateReminder;
use App\Notifications\CandidateScheduleInterview;
use App\Notifications\EmployeeResponse;
use App\Notifications\ScheduleInterview;
use App\Notifications\ScheduleInterviewStatus;
use App\Notifications\ScheduleStatusCandidate;
use App\ScheduleComments;
use App\Support\RaDataTableHtml;
use App\Services\Zoom\ZoomApiClient;
use App\Traits\ZoomSettings;
use App\User;
use App\ZoomMeeting;
use App\ZoomSetting;
use Carbon\Carbon;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class InterviewScheduleController extends AdminBaseController
{
    use ZoomSettings;

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = __('menu.interviewSchedule');
        $this->pageIcon = 'icon-calender';
    }

    /**
     * @return Factory|View|mixed
     *
     * @throws \Throwable
     */
    public function index(Request $request)
    {
        abort_if(! $this->user->cans('view_schedule'), 403);

        $this->loadGoogleCalendarConnection();

        $currentDate = Carbon::now()->format('Y-m-d'); // Current Date

        // Get All schedules
        $this->schedules = InterviewSchedule::select('schedule_date', 'interview_schedules.id', 'interview_schedules.job_application_id', 'interview_schedules.schedule_date', 'interview_schedules.status')
            ->with(['employees', 'meeting', 'jobApplication:id,job_id,full_name', 'jobApplication.job:id,title'])
            ->join('job_applications', 'job_applications.id', 'interview_schedules.job_application_id')
            ->where('status', 'pending')
            ->whereNull('job_applications.deleted_at')
            ->orderBy('schedule_date')
            ->get();

        // Filter upcoming schedule
        $upComingSchedules = $this->schedules->filter(function ($value, $key) use ($currentDate) {
            return $value->schedule_date >= $currentDate;
        });

        $upcomingData = [];

        // Set array for upcoming schedule
        foreach ($upComingSchedules as $upComingSchedule) {
            $dt = $upComingSchedule->schedule_date->format('Y-m-d');
            $upcomingData[$dt][] = $upComingSchedule;
        }

        $this->upComingSchedules = $upcomingData;

        if ($request->ajax()) {
            $viewData = view('admin.interview-schedule.upcoming-schedule', $this->data)->render();

            return Reply::dataOnly(['data' => $viewData, 'scheduleData' => $this->schedules]);
        }

        return view('admin.interview-schedule.index', $this->data);
    }

    /**
     * @return string
     *
     * @throws \Throwable
     */
    public function create(Request $request)
    {
        abort_if(! $this->user->cans('add_schedule'), 403);
        $this->candidates = JobApplication::all();
        $this->zoom_setting = ZoomSetting::first();
        $this->users = User::all();
        $this->scheduleDate = $request->date;

        return view('admin.interview-schedule.create', $this->data)->render();
    }

    /**
     * @return string
     *
     * @throws \Throwable
     */
    public function table(Request $request)
    {

        abort_if(! $this->user->cans('view_schedule'), 403);
        $this->loadGoogleCalendarConnection();
        $this->candidates = JobApplication::all();
        $this->users = User::all();

        return view('admin.interview-schedule.table', $this->data);
    }

    /**
     * @return mixed
     *
     * @throws \Exception
     */
    public function data(Request $request)
    {
        abort_if(! $this->user->cans('view_schedule'), 403);

        $shedule = InterviewSchedule::select('interview_schedules.id', 'interview_schedules.interview_type', 'interview_schedule_employees.user_id as employee_id', 'job_applications.full_name', 'interview_schedules.status', 'zoom_meetings.created_by', 'zoom_meetings.start_link', 'interview_schedules.schedule_date')
            ->leftjoin('job_applications', 'job_applications.id', 'interview_schedules.job_application_id')
            ->leftjoin('interview_schedule_employees', 'interview_schedule_employees.interview_schedule_id', 'interview_schedules.id')
            ->leftjoin('zoom_meetings', 'zoom_meetings.id', 'interview_schedules.meeting_id')
            ->whereNull('job_applications.deleted_at');
        $this->zoomSetting = ZoomSetting::first();
        // Filter by status
        if ($request->status != 'all' && $request->status != '') {
            $shedule = $shedule->where('interview_schedules.status', $request->status);
        }

        // Filter By candidate
        if ($request->applicationID != 'all' && $request->applicationID != '') {
            $shedule = $shedule->where('job_applications.id', $request->applicationID);
        }

        // Filter by StartDate
        if ($request->startDate !== null && $request->startDate != 'null') {
            $shedule = $shedule->where(DB::raw('DATE(interview_schedules.`schedule_date`)'), '>=', "$request->startDate");
        }

        // Filter by EndDate
        if ($request->endDate !== null && $request->endDate != 'null') {
            $shedule = $shedule->where(DB::raw('DATE(interview_schedules.`schedule_date`)'), '<=', "$request->endDate");
        }

        return DataTables::of($shedule)
            ->addColumn('action', function ($row) {
                if ($this->zoomSetting->meeting_app == 'in_app') {
                    $url = $row->start_link;
                } else {
                    $url = $this->user->id == $row->created_by ? $row->start_link : $row->end_link;
                }
                $parts = [];
                if ($this->user->cans('view_schedule')) {
                    $parts[] = RaDataTableHtml::js(RaDataTableHtml::SVG_EYE, 'jc-btn-slate view-data', ['data-row-id' => (string) $row->id], __('app.view'));
                }
                if ($this->user->cans('edit_schedule')) {
                    $parts[] = RaDataTableHtml::js(RaDataTableHtml::SVG_EDIT, 'jc-btn-edit edit-data', ['data-row-id' => (string) $row->id], __('app.edit'));
                }

                if ($this->user->cans('delete_schedule')) {
                    $parts[] = RaDataTableHtml::deleteSaParams($row->id);
                }
                if ($row->interview_type == 'online' && $this->user->id == $row->created_by && $row->employee_id == $this->user->id) {
                    $parts[] = RaDataTableHtml::externalLink($url, RaDataTableHtml::SVG_PLAY, 'jc-btn-emerald', __('modules.zoommeeting.startMeeting'));
                }

                return RaDataTableHtml::wrap(implode('', $parts));
            })
            ->addColumn('checkbox', function ($row) {
                return '
                    <div class="flex items-center">
                        <input id="schedule-row-'.$row->id.'" type="checkbox" name="id[]" class="schedule-row-checkbox rounded border-gray-300 text-primary focus:ring-primary" value="'.$row->id.'" >
                        <label for="schedule-row-'.$row->id.'" class="ml-2"></label>
                    </div>
                ';
            })
            ->editColumn('full_name', function ($row) {
                return ucwords($row->full_name);
            })
            ->editColumn('schedule_date', function ($row) {
                return Carbon::parse($row->schedule_date)->format('d F, Y H:i a');
            })
            ->editColumn('status', function ($row) {
                if ($row->status == 'pending') {
                    return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">'.__('app.pending').'</span>';
                }
                if ($row->status == 'hired') {
                    return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">'.__('app.hired').'</span>';
                }
                if ($row->status == 'canceled') {
                    return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">'.__('app.canceled').'</span>';
                }
                if ($row->status == 'rejected') {
                    return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">'.__('app.rejected').'</span>';
                }
            })
            ->rawColumns(['action', 'status', 'full_name', 'checkbox'])
            ->make(true);
    }

    /**
     * @return string
     *
     * @throws \Throwable
     */
    public function edit($id)
    {
        abort_if(! $this->user->cans('edit_schedule'), 403);

        $this->candidates = JobApplication::all();
        $this->users = User::all();
        $this->zoom_setting = ZoomSetting::first();
        $this->schedule = InterviewSchedule::with(['jobApplication', 'user', 'meeting'])->find($id);
        $this->comment = ScheduleComments::where('interview_schedule_id', $this->schedule->id)
            ->where('user_id', $this->user->id)->first();
        $this->employeeList = json_encode($this->schedule->employee->pluck('user_id')->toArray());

        return view('admin.interview-schedule.edit', $this->data)->render();
    }

    /**
     * @return array
     */
    public function store(StoreRequest $request)
    {
        abort_if(! $this->user->cans('add_schedule'), 403);
        $this->setZoomConfigs();
        $dateTime = $request->scheduleDate.' '.$request->scheduleTime;
        $dateTime = Carbon::createFromFormat('Y-m-d H:i', $dateTime);

        if ($request->interview_type == 'online') {
            $data = $request->all();
            $meeting = new ZoomMeeting;
            $data['meeting_name'] = $request->meeting_title;
            $data['start_date_time'] = $dateTime;
            $data['end_date_time'] = $request->end_date.' '.$request->end_time;
            $meeting = $meeting->create($data);
            $host = User::find($request->create_by);
            $zoom = app(ZoomApiClient::class);
            $meetings = $this->createMeeting($zoom, $meeting, null, null, $host);
        } else {
            $meetings = '';
        }
        // store Schedule
        $interviewSchedule = new InterviewSchedule;
        $interviewSchedule->job_application_id = $request->candidates;
        $interviewSchedule->schedule_date = $dateTime;
        $interviewSchedule->interview_type = ($request->has('interview_type')) ? $request->interview_type : 'offline';
        // $interviewSchedule->interview_type = $request->interview_type;
        $interviewSchedule->meeting_id = ($meetings != '') ? $meetings->id : null;
        $interviewSchedule->save();

        // Update Schedule Status
        $jobApplication = $interviewSchedule->jobApplication;
        $jobApplication->status_id = ApplicationStatus::findForJob((int) $jobApplication->job_id, 'interview')?->id
            ?? $jobApplication->status_id;
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

            // Mail to employee for inform interview schedule
            Notification::send($interviewSchedule->employees, new ScheduleInterview($jobApplication, $meetings));
        }

        // mail to candidate for inform interview schedule
        Notification::send($jobApplication, new CandidateScheduleInterview($jobApplication, $interviewSchedule, $meetings));

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

    public function changeStatus(Request $request)
    {
        abort_if(! $this->user->cans('add_schedule'), 403);

        $this->commonChangeStatusFunction($request->id, $request);

        return Reply::success(__('messages.interviewScheduleStatus'));
    }

    /**
     * @return array
     */
    public function update(UpdateRequest $request, $id)
    {
        abort_if(! $this->user->cans('add_schedule'), 403);
        $this->setZoomConfigs();
        $dateTime = $request->scheduleDate.' '.$request->scheduleTime;
        $dateTime = Carbon::createFromFormat('Y-m-d H:i', $dateTime);

        // Update interview Schedule
        $interviewSchedule = InterviewSchedule::select('id', 'job_application_id', 'interview_type', 'schedule_date', 'status')
            ->with([
                'jobApplication:id,full_name,email,job_id,status_id',
                'employees',
                'comments',
            ])
            ->where('id', $id)->first();
        $interviewSchedule->schedule_date = $dateTime;
        if (! is_null($request->interview_type)) {
            $interviewSchedule->interview_type = $request->interview_type;

        } else {
            $interviewSchedule->interview_type = $interviewSchedule->interview_type;

        }

        if ($request->interview_type == 'offline') {
            $interviewSchedule->meeting_id = null;
            ZoomMeeting::where('id', $interviewSchedule->meeting_id)->delete();
            $meeting = '';
        }
        $interviewSchedule->save();

        if ($request->comment) {
            $scheduleComment = [
                'comment' => $request->comment,
            ];

            $interviewSchedule->comments()->updateOrCreate([
                'interview_schedule_id' => $interviewSchedule->id,
                'user_id' => $this->user->id,
            ], $scheduleComment);
        }

        $jobApplication = $interviewSchedule->jobApplication;
        // zoom meeting update
        $host = User::find($request->create_by);

        if ($request->interview_type == 'online') {
            $zoom = app(ZoomApiClient::class);

            $meeting = is_null($interviewSchedule->meeting_id) ? new ZoomMeeting : ZoomMeeting::find($interviewSchedule->meeting_id);
            $data = $request->all();
            $data['meeting_name'] = $request->meeting_title;
            $data['start_date_time'] = $request->start_date.' '.$request->start_time;
            $data['end_date_time'] = $request->end_date.' '.$request->end_time;
            if (is_null($interviewSchedule->meeting_id)) {
                $meeting = $meeting->create($data);
            } else {
                $meeting->update($data);

            }

            $meetings = $this->createMeeting($zoom, $meeting, $interviewSchedule->meeting_id, null, $host);
            $interviewSchedule->meeting_id = $meetings->id;
            $interviewSchedule->save();
        }
        if (! empty($request->employee)) {
            $interviewSchedule->employees()->sync($request->employee);
            if (! ($request->interview_type)) {
                $meeting = '';
            }
            // Mail to employee for inform interview schedule
            Notification::send($interviewSchedule->employees, new ScheduleInterview($jobApplication, $meeting));

        }

        return Reply::redirect(route('admin.interview-schedule.index'), __('menu.interviewSchedule').' '.__('messages.updatedSuccessfully'));
    }

    /**
     * @return array
     */
    public function destroy($id)
    {
        abort_if(! $this->user->cans('delete_schedule'), 403);

        $meeting_id = InterviewSchedule::select('meeting_id')->where('id', $id)->get();
        InterviewSchedule::destroy($id);
        $this->setZoomConfigs();
        ZoomMeeting::destroy($meeting_id[0]->meeting_id);

        return Reply::success(__('messages.recordDeleted'));
    }

    /**
     * @return string
     *
     * @throws \Throwable
     */
    public function show(Request $request, $id)
    {
        abort_if(! $this->user->cans('view_schedule'), 403);
        $this->schedule = InterviewSchedule::with([
            'jobApplication.job.location',
            'user',
            'meeting',
            'employee.user',
            'comments.user',
        ])->findOrFail($id);
        $this->currentDateTimestamp = Carbon::now()->timestamp;
        $this->tableData = null;
        $this->zoom_setting = ZoomSetting::first();

        if ($request->has('table')) {
            $this->tableData = 'yes';
        }

        return view('admin.interview-schedule.show', $this->data)->render();
    }

    // notify and reminder to candidate on interview schedule
    public function notify($id, $type)
    {

        $jobApplication = JobApplication::find($id);

        if ($type == 'notify') {
            // mail to candidate for hiring notify
            Notification::send($jobApplication, new CandidateNotify);

            return Reply::success(__('messages.notificationForHire'));
        } else {
            // mail to candidate for interview reminder
            Notification::send($jobApplication, new CandidateReminder($jobApplication->schedule));

            return Reply::success(__('messages.notificationForReminder'));
        }
    }

    // Employee response on interview schedule
    public function employeeResponse($id, $res)
    {

        $scheduleEmployee = InterviewScheduleEmployee::find($id);
        $users = User::allAdmins(); // Get All admins for mail
        $type = 'refused';

        if ($res == 'accept') {
            $type = 'accepted';
        }

        $scheduleEmployee->user_accept_status = $res;

        // mail to admin for employee response on refuse or accept
        Notification::send($users, new EmployeeResponse($scheduleEmployee->schedule, $type, $this->user));

        $scheduleEmployee->save();

        return Reply::success(__('messages.responseAppliedSuccess'));
    }

    public function changeStatusMultiple(Request $request)
    {
        abort_if(! $this->user->cans('edit_schedule'), 403);
        foreach ($request->id as $ids) {
            $this->commonChangeStatusFunction($ids, $request);
        }

        return Reply::success(__('messages.interviewScheduleStatus'));
    }

    public function commonChangeStatusFunction($id, $request)
    {
        // store Schedule
        $interviewSchedule = InterviewSchedule::select('id', 'job_application_id', 'status')
            ->with([
                'jobApplication:id,full_name,email,job_id,status_id',
                'employees',
            ])
            ->where('id', $id)->first();
        $interviewSchedule->status = $request->status;
        $interviewSchedule->save();

        $application = $interviewSchedule->jobApplication;
        $status = ApplicationStatus::select('id', 'status');

        if (in_array($request->status, ['rejected', 'canceled'])) {
            $applicationStatus = $status->status('rejected');
        }
        if ($request->status === 'hired') {
            $applicationStatus = $status->status('hired');
        }
        if ($request->status === 'pending') {
            $applicationStatus = $status->status('interview');
        }

        $application->status_id = $applicationStatus->id;

        $application->save();

        $employees = $interviewSchedule->employees;
        $admins = User::allAdmins();

        $users = $employees->merge($admins);

        if ($users && $request->mailToCandidate == 'yes') {
            // Mail to employee for inform interview schedule
            Notification::send($users, new ScheduleInterviewStatus($application));
        }

        if ($request->mailToCandidate == 'yes') {
            // mail to candidate for inform interview schedule status
            Notification::send($application, new ScheduleStatusCandidate($application, $interviewSchedule));
        }

    }
    private function loadGoogleCalendarConnection(): void
    {
        $this->googleCalendarReady = \Illuminate\Support\Facades\Schema::hasTable('google_calendar_connections');
        $this->googleCalendarConfigured = app(\App\Services\GoogleCalendarService::class)->configured();
        $this->googleCalendarEmail = $this->googleCalendarReady
            ? \App\GoogleCalendarConnection::where('user_id', $this->user->id)->value('email') : null;
    }

}
