<?php

namespace App\Http\Controllers\Admin;

use App\Currency;
use App\Department;
use App\Designation;
use App\Helper\Files;
use App\Helper\Reply;
use App\Http\Requests\Onboard\StoreRequest;
use App\Http\Requests\Onboard\UpdateStatus;
use App\JobApplication;
use App\JobOfferQuestion;
use App\Notifications\JobOffer;
use App\Onboard;
use App\OnboardAnswer;
use App\OnboardFiles;
use App\User;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class AdminJobOnboardController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = __('menu.jobOnboard');
        $this->pageIcon = 'icon-user';
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request)
    {
        abort_if(! $this->user->cans('view_job_applications'), 403);

        return view('admin.job-onboard.index', $this->data);
    }

    public function create(Request $request)
    {
        abort_if(! $this->user->cans('add_job_applications'), 403);

        $this->application = JobApplication::with(['job', 'status', 'job.location', 'location'])->findOrFail($request->id);

        // Abort if requested application status is not hired.
        abort_if($this->application->status->status != 'hired', 403);

        $this->users = User::all();
        $this->departments = Department::all();
        $this->designations = Designation::all();
        $this->currencies = Currency::all();
        $this->questions = JobOfferQuestion::all();

        return view('admin.job-onboard.create', $this->data);
    }

    /**
     * @return array
     */
    public function store(StoreRequest $request)
    {
        // Save On Board Details
        $onBoard = new Onboard;
        $onBoard->job_application_id = $request->candidate_id;
        $onBoard->department_id = $request->department;
        $onBoard->designation_id = $request->designation;
        $onBoard->currency_id = $request->currency_id;
        $onBoard->salary_offered = $request->salary;
        $onBoard->joining_date = $request->join_date;
        $onBoard->reports_to_id = $request->report_to;
        $onBoard->employee_status = $request->employee_status;
        $onBoard->accept_last_date = $request->last_date;
        $onBoard->offer_code = Str::random(18);
        $onBoard->save();
        $onBoard->onboardQuestion()->sync($request->question);
        $names = $request->name;
        $files = $request->file;

        if ($request->has('file')) {
            foreach ($names as $key => $name) {
                // Files store in directory
                $fileName = Files::upload($files[$key], 'onboard-files', null, null, false);

                if (is_null($name)) {
                    $name = $files[$key]->getClientOriginalName();
                }

                // Files save
                OnboardFiles::create(
                    [
                        'on_board_detail_id' => $onBoard->id,
                        'name' => $name,
                        'file' => $files[$key]->getClientOriginalName(),
                        'hash_name' => $fileName,
                    ]
                );
            }
        }
        // Send Offer email
        if ($request->has('send_email')) {
            $this->sendOffer($request->candidate_id);
        }

        return Reply::redirect(route('admin.job-onboard.index'), __('app.onBoard').' '.__('messages.createdSuccessfully'));
    }

    /**
     * @return Factory|View
     */
    public function edit($id)
    {
        abort_if(! $this->user->cans('add_job_applications'), 403);

        // images format with icon
        $this->imageExt = [
            'png' => 'fa-file-image-o',  'jpe' => 'fa-file-image-o', 'jpeg' => 'fa-file-image-o', 'jpg' => 'fa-file-image-o',
            'gif' => 'fa-file-image-o',  'bmp' => 'fa-file-image-o', 'ico' => 'fa-file-image-o', 'tiff' => 'fa-file-image-o',
            'tif' => 'fa-file-image-o', 'svg' => 'fa-file-image-o', 'svgz' => 'fa-file-image-o',
        ];

        // adobe and ms office files format with icon
        $this->fileExt = [
            // adobe
            'pdf' => 'fa-file-pdf-o', 'psd' => 'fa-file-image-o', 'ai' => 'fa-file-o', 'eps' => 'fa-file-o',
            'ps' => 'fa-file-o',
            // ms office
            'doc' => 'fa-file-text', 'rtf' => 'fa-file-text', 'xls' => 'fa-file-excel-o', 'ppt' => 'fa-file-powerpoint-o',
            'docx' => 'fa-file-text', 'xlsx' => 'fa-file-excel-o', 'pptx' => 'fa-file-powerpoint-o',
            // open office
            'odt' => 'fa-file-text', 'ods' => 'fa-file-text',
        ];

        $this->onboard = Onboard::with(['reportto', 'files'])->findOrFail($id);
        $this->application = JobApplication::with(['job', 'status', 'job.location'])->findOrFail($this->onboard->job_application_id);
        $this->users = User::all();
        $this->departments = Department::all();
        $this->designations = Designation::all();
        $this->currencies = Currency::all();
        $this->questions = JobOfferQuestion::all();
        $this->onboardQuestion = $this->onboard->onboardQuestion->pluck('id')->toArray();

        return view('admin.job-onboard.edit', $this->data);
    }

    /**
     * @return mixed
     */
    public function data(Request $request)
    {
        abort_if(! $this->user->cans('view_job_applications'), 403);

        $jobApplications = Onboard::select('on_board_details.id', 'job_applications.id as application_id', 'job_applications.full_name', 'jobs.title', 'job_locations.location', 'on_board_details.joining_date', 'on_board_details.accept_last_date', 'on_board_details.hired_status')
            ->join('job_applications', 'job_applications.id', 'on_board_details.job_application_id')
            ->leftJoin('jobs', 'jobs.id', 'job_applications.job_id')
            ->leftjoin('job_locations', 'job_locations.id', 'jobs.location_id')
            ->leftjoin('application_status', 'application_status.id', 'job_applications.status_id');

        return DataTables::of($jobApplications)
            ->addColumn('action', function ($row) {
                $mi = 'block px-3 py-2 text-[13px] text-[#3D4A5C] no-underline hover:bg-[#F8F7F4]';
                $action = '<div class="relative inline-block text-left"><button aria-expanded="false" data-toggle="dropdown" class="inline-flex items-center gap-1 rounded-lg border border-[#E2DED8] bg-[#EFF6FF] px-3 py-1.5 text-[12.5px] font-semibold text-[#2563EB] shadow-sm transition hover:bg-[#DBEAFE]" id="dropdownMenuButton" type="button">'.e(__('app.action')).' <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg></button><div class="hidden absolute right-0 z-[60] mt-1 min-w-[12rem] rounded-xl border border-[#E8E6E1] bg-white py-1 shadow-lg dropdown-menu" aria-labelledby="dropdownMenuButton">';

                $action .= '<a href="'.route('admin.job-onboard.show', [$row->id]).'" class="'.$mi.'">'.e(__('app.view')).' '.e(__('app.offer')).'</a>';
                $action .= '<a href="'.route('admin.job-onboard.edit', [$row->id]).'" class="'.$mi.'">'.e(__('app.edit')).'</a>';
                $action .= '<a href="javascript:;" data-row-id="'.$row->application_id.'" class="'.$mi.' send-offer">'.e(__('app.send')).' '.e(__('app.offer')).'</a>';
                if ($row->hired_status != 'canceled') {
                    $action .= '<a href="javascript:;" data-row-id="'.$row->id.'" class="'.$mi.' sa-params">'.e(__('app.cancel')).'</a>';
                }
                $action .= '</div></div>';

                return $action;
            })
            ->editColumn('full_name', function ($row) {
                return '<a href="javascript:;" class="show-detail" data-row-id="'.$row->application_id.'">'.ucwords($row->full_name).'</a>';
            })
            ->editColumn('title', function ($row) {
                return ucfirst($row->title);
            })
            ->editColumn('location', function ($row) {
                return ucwords($row->location);
            })
            ->editColumn('joining_date', function ($row) {
                return (! is_null($row->joining_date)) ? $row->joining_date->format('d M Y') : '-';
            })
            ->editColumn('accept_last_date', function ($row) {
                return (! is_null($row->accept_last_date)) ? $row->accept_last_date->format('d M Y') : '-';
            })
            ->editColumn('hired_status', function ($row) {

                if ($row->hired_status == 'accepted') {
                    return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">'.__('app.accepted').'</span>';
                } elseif ($row->hired_status == 'offered') {
                    return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">'.__('app.offered').'</span>';
                } elseif ($row->hired_status == 'canceled') {
                    return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">'.__('app.canceled').'</span>';
                } else {
                    return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">'.__('app.rejected').'</span>';
                }
            })
            ->rawColumns(['action', 'full_name', 'hired_status'])
            ->addIndexColumn()
            ->make(true);
    }

    /**
     * @return array
     */
    public function update(StoreRequest $request, $id)
    {
        // update On Board Details
        $onBoard = Onboard::findOrFail($id);
        $onBoard->job_application_id = $request->candidate_id;
        $onBoard->department_id = $request->department;
        $onBoard->designation_id = $request->designation;
        $onBoard->currency_id = $request->currency_id;
        $onBoard->salary_offered = $request->salary;
        $onBoard->joining_date = $request->join_date;
        $onBoard->reports_to_id = $request->report_to;
        $onBoard->employee_status = $request->employee_status;
        $onBoard->accept_last_date = $request->last_date;
        $onBoard->hired_status = $request->status;

        if ($onBoard->hired_status == 'rejected' || $onBoard->hired_status == 'canceled') {
            $onBoard->hired_status = $request->reason;
        }

        $onBoard->save();
        $onBoard->onboardQuestion()->sync($request->question);

        $names = $request->name;
        $files = $request->file;

        if ($request->has('file')) {
            foreach ($names as $key => $name) {
                if (isset($files[$key])) {
                    // Files store in directory
                    $fileName = Files::upload($files[$key], 'onboard-files', null, null, false);

                    // Files save
                    OnboardFiles::create(
                        [
                            'on_board_detail_id' => $onBoard->id,
                            'name' => $name,
                            'file' => $files[$key]->getClientOriginalName(),
                            'hash_name' => $fileName,
                        ]
                    );
                }

            }
        }

        return Reply::redirect(route('admin.job-onboard.index'), __('app.onBoard').' '.__('messages.updatedSuccessfully'));
    }

    /**
     * @return array
     */
    public function destroy($id)
    {
        $onBoardFiles = OnboardFiles::findOrFail($id);
        File::delete(tenant_upload_path('onboard-files/'.$onBoardFiles->hashName));

        OnboardFiles::destroy($id);

        return Reply::success('messages.recordDeleted');
    }

    /**
     * @return Factory|View
     */
    public function show($id)
    {
        // images format with icon
        $this->imageExt = [
            'png' => 'fa-file-image-o',  'jpe' => 'fa-file-image-o', 'jpeg' => 'fa-file-image-o', 'jpg' => 'fa-file-image-o',
            'gif' => 'fa-file-image-o',  'bmp' => 'fa-file-image-o', 'ico' => 'fa-file-image-o', 'tiff' => 'fa-file-image-o',
            'tif' => 'fa-file-image-o', 'svg' => 'fa-file-image-o', 'svgz' => 'fa-file-image-o',
        ];

        // adobe and ms office files format with icon
        $this->fileExt = [
            // adobe
            'pdf' => 'fa-file-pdf-o', 'psd' => 'fa-file-image-o', 'ai' => 'fa-file-o', 'eps' => 'fa-file-o',
            'ps' => 'fa-file-o',
            // ms office
            'doc' => 'fa-file-text', 'rtf' => 'fa-file-text', 'xls' => 'fa-file-excel-o', 'ppt' => 'fa-file-powerpoint-o',
            'docx' => 'fa-file-text', 'xlsx' => 'fa-file-excel-o', 'pptx' => 'fa-file-powerpoint-o',
            // open office
            'odt' => 'fa-file-text', 'ods' => 'fa-file-text',
        ];
        $this->onboard = Onboard::with(['reportto', 'files', 'currency'])->findOrFail($id);
        $this->application = JobApplication::with(['job', 'status', 'job.location'])->findOrFail($this->onboard->job_application_id);
        $this->answers = OnboardAnswer::with(['question'])
            ->where('onboard_id', $this->onboard->id)
            ->get();

        // dd($this->answers);
        return view('admin.job-onboard.show', $this->data);
    }

    public function sendOffer($userID)
    {
        $jobApplication = JobApplication::select('id', 'job_id', 'full_name', 'email')->with(['job:id,title', 'onboard:id,offer_code,job_application_id,hired_status'])->where('id', $userID)->first();

        if ($jobApplication->onboard->hired_status !== 'offered') {
            $jobApplication->onboard->hired_status = 'offered';

            $jobApplication->onboard->save();
        }
        // Send Email Or Sms to applicant.Request
        $jobApplication->notify(new JobOffer($jobApplication));

        return Reply::success(__('messages.offer.offerSentSuccessfully'));
    }

    /**
     * @return array
     */
    public function updateStatus(UpdateStatus $request, $id)
    {
        $onboard = Onboard::findOrFail($id);
        $onboard->cancel_reason = $request->cancel_reason;
        $onboard->hired_status = 'canceled';
        $onboard->save();

        return Reply::success(__('messages.offer.updatedSuccessfully'));
    }
}
