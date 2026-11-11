<?php

namespace App\Http\Controllers\Front;

use App\Job;
use App\User;
use App\Onboard;
use App\JobCategory;
use App\JobLocation;
use App\JobQuestion;
use App\Helper\Files;
use App\Helper\Reply;
use App\OnboardFiles;
use App\OnboardAnswer;
use App\JobApplication;
use App\LinkedInSetting;
use Illuminate\Http\Request;
use App\JobApplicationAnswer;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Config;
use App\Http\Requests\StoreOfferLetter;
use App\Notifications\JobOfferAccepted;
use App\Notifications\JobOfferRejected;
use App\Services\ZohoSign;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use App\Notifications\NewJobApplication;
use Laravel\Socialite\Facades\Socialite;
use App\Http\Requests\FrontJobApplication;
use Illuminate\Support\Facades\Notification;

class FrontJobOfferController extends FrontBaseController
{


    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = __('app.jobOffer');

        $linkedinSetting = LinkedInSetting::where('status', 'enable')->first();
        $this->linkedinGlobal = LinkedInSetting::first();

        if ($linkedinSetting)
        {
            Config::set('services.linkedin.client_id', $linkedinSetting->client_id);
            Config::set('services.linkedin.client_secret', $linkedinSetting->client_secret);
            Config::set('services.linkedin.redirect', $linkedinSetting->callback_url);
        }
    }

    public function index($offerCode)
    {
        // images format with icon
        $this->imageExt = [
            'png' => 'fa-file-image-o',
            'jpe' => 'fa-file-image-o',
            'jpeg' => 'fa-file-image-o',
            'jpg' => 'fa-file-image-o',
            'gif' => 'fa-file-image-o',
            'bmp' => 'fa-file-image-o',
            'ico' => 'fa-file-image-o',
            'tiff' => 'fa-file-image-o',
            'tif' => 'fa-file-image-o',
            'svg' => 'fa-file-image-o',
            'svgz' => 'fa-file-image-o',


        ];

        // adobe and ms office files format with icon
        $this->fileExt = [
            // adobe
            'pdf' => 'fa-file-pdf-o',
            'psd' => 'fa-file-image-o',
            'ai' => 'fa-file-o',
            'eps' => 'fa-file-o',
            'ps' => 'fa-file-o',

            // ms office
            'doc' => 'fa-file-text',
            'rtf' => 'fa-file-text',
            'xls' => 'fa-file-excel-o',
            'ppt' => 'fa-file-powerpoint-o',
            'docx' => 'fa-file-text',
            'xlsx' => 'fa-file-excel-o',
            'pptx' => 'fa-file-powerpoint-o',

            // open office
            'odt' => 'fa-file-text',
            'ods' => 'fa-file-text',
        ];

        $this->offer = Onboard::with(['applications','files','applications.job', 'department', 'designation', 'onboardQuestion'])
        ->where('offer_code', $offerCode)
        ->firstOrFail();
        $this->job   = Job::with(['category','location', 'skills', 'company'])->where('id', $this->offer->applications->job->id)->first();
        
        return view('front.job-offer', $this->data);
    }

    /**
     * @param Request $request
     * @return array
     */
    public function saveOffer(Request $request, ZohoSign $zoho)
    {
        $request->validate([
            'code' => 'required|string',
            'type' => 'required|in:accept,rejected',
            'answer' => 'sometimes|array',
        ]);

        return DB::transaction(fn () => $this->saveOfferResponse($request, $zoho));
    }

    private function saveOfferResponse(Request $request, ZohoSign $zoho)
    {
        $offer = Onboard::where('offer_code', $request->code)->lockForUpdate()->firstOrFail();
        if ($offer->hired_status !== 'offered') {
            return Reply::error('This offer has already been resolved.');
        }
        if ($offer->zoho_sign_file_id && $request->type === 'accept') {
            return $this->startZohoSigning($request, $offer, $zoho);
        }
        if ($offer->zoho_sign_request_id) {
            return Reply::error('Please respond to the signing request in Zoho Sign.');
        }

        $jobApplication = JobApplication::findOrFail($offer->applications->id);

        if($request->type == 'accept'){
            $this->saveAnswers($request->input('answer', []) + $request->file('answer', []), $offer);

            $image     = $request->signature;  // your base64 encoded
            $image     = str_replace('data:image/png;base64,', '', $image);
            $image     = str_replace(' ', '+', $image);
            $imageName = str_random(32).'.'.'jpg';

            if (!\File::exists(tenant_upload_path('offer/sign'))) {
                $result = \File::makeDirectory(tenant_upload_path('offer/sign'), 0777, true);
            }


            if (config('filesystems.default') === 'local') {
            
                if (config('saas.enabled')) { app(\App\Saas\QuotaService::class)->assertUploadFits(strlen(base64_decode($image))); }
                \File::put(tenant_upload_path('offer/sign/'.$imageName), base64_decode($image));

                // self::storeSize($uploadedFile, $dir, $fileName);
            }
            else{
                Storage::disk('s3')->putFileAs('offer/sign',  base64_decode($image), $imageName);
            }
            // self::storeSize($uploadedFile, $dir, $newName);
            
            // We have given 2 options of upload for now s3 and local
            // Storage::disk('s3')->putFileAs($dir, $uploadedFile, $newName, 'public');
    
            $offer->sign         =  $imageName;
            $offer->hired_status = 'accepted';
        }
        else{
            $offer->reject_reason = $request->reason;
            $offer->hired_status  = 'rejected';
        }

        $offer->save();

        // All admins data for send mail.
        $admins = User::allAdmins();

        if($request->type == 'accept'){
            // Send Email Or SMS to admin on accept.
            Notification::send($admins, new JobOfferAccepted($jobApplication));
        }else{
            // Send Email Or SMS to admin on reject.
            Notification::send($admins, new JobOfferRejected($jobApplication));
        }

        return Reply::success(__('messages.thankyouForYourResponse'));
    }

    private function startZohoSigning(Request $request, Onboard $offer, ZohoSign $zoho): array
    {
        if (! $zoho->enabled()) {
            return Reply::error('Zoho Sign is unavailable. Please contact your recruiter.');
        }
        if (! $offer->zoho_sign_request_id) {
            if ($offer->accept_last_date->copy()->endOfDay()->isPast()) {
                return Reply::error('The deadline for this offer has passed.');
            }

            $rules = ['answer' => 'sometimes|array'];
            foreach ($offer->onboardQuestion as $question) {
                $rules['answer.'.$question->id] = $question->type === 'text'
                    ? 'required|string|max:5000'
                    : 'required|file|mimes:png,jpg,jpeg,pdf,doc,docx,xls,xlsx,rtf|max:10240';
            }
            $answers = $request->validate($rules)['answer'] ?? [];

            try {
                $offer->zoho_sign_request_id = $zoho->createDraft($offer);
            } catch (\RuntimeException | ConnectionException $exception) {
                report($exception);
                return Reply::error('The signing request could not be prepared. Please try again or contact your recruiter.');
            }
            $offer->zoho_sign_status = 'draft';
            $offer->save();
            $this->saveAnswers($answers, $offer);
        }

        try {
            $zoho->submit($offer);
        } catch (\RuntimeException | ConnectionException $exception) {
            report($exception);
            return Reply::error('The signing request could not be confirmed. Please try again or contact your recruiter.');
        }

        return Reply::redirect(tenant_route('jobs.job-offer', ['slug' => $offer->offer_code]));
    }

    private function saveAnswers(array $answers, Onboard $offer): void
    {
        foreach ($offer->onboardQuestion as $question) {
            if (! array_key_exists($question->id, $answers)) {
                continue;
            }

            $value = $answers[$question->id];
            $answer = new OnboardAnswer;
            $answer->onboard_id = $offer->id;
            $answer->question_id = $question->id;
            if ($value instanceof \Illuminate\Http\UploadedFile) {
                $answer->file = Files::uploadLocalOrS3($value, 'documents');
            } else {
                $answer->answer = $value;
            }
            $answer->save();
        }
    }
}
