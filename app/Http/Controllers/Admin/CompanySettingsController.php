<?php

namespace App\Http\Controllers\Admin;

use DateTimeZone;
use App\Helper\Files;
use App\Helper\Reply;
use App\CompanySetting;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use App\Http\Requests\Company\UpdateCompany;
use App\Http\Controllers\Admin\AdminBaseController;

class CompanySettingsController extends AdminBaseController
{

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = __('menu.businessSettings');
        $this->pageIcon = 'icon-settings';
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        abort_if(!$this->user->cans('manage_settings'), 403);

        $this->timezones = \DateTimeZone::listIdentifiers(\DateTimeZone::ALL);
        $setting = CompanySetting::first();

        if (!$setting) {
            abort(404);
        }

        $this->voiceSettings = \App\Services\CandidateCallService::voiceSettings($setting);

        return view('admin.settings.index', $this->data);
    }


    public function update(UpdateCompany $request, $id)
    {
        $data = $request->all();

        $data = CompanySetting::first();

        if($request->front_logo != null){

            $logo_path = base_path('app-logo' . $data->logo);

            if(File::exists($logo_path)) {
                File::delete($logo_path);
            }
        }

        if($request->favicon != null){

            $favicon_path = base_path('/favicon/apple-icon-72x72.png' . $data->favicon);

            if(File::exists($favicon_path)) {
                File::delete($favicon_path);
            }
        }
        
        $data->company_name = $request->company_name;

        if (!config('saas.enabled')) {
        $data->system_update = $request->has('system_update') && $request->input('system_update') == 'on' ? 1 : 0;
        }
        
        if (!config('saas.enabled')) {
        $data->front_language = $request->has('front_language') && $request->input('front_language') == '1' ? 1 : 0;
        }
        
        $data->currency_id = $request->has('currency_id') ? $request->input('currency_id') : '1';

        if (!config('saas.enabled')) {
        $data->job_alert_status = $request->has('job_alert_status') && $request->input('job_alert_status') == '1' ? 1 : 0;
        }

        $data->company_phone = $request->company_phone;

        $data->company_email = $request->company_email;

        $data->address = $request->address;

        $data->timezone = $request->timezone;

        $data->locale = $request->locale;

        $data->website = $request->website;

        if (!config('saas.enabled') && $request->has('candidate_calls_enabled')) {
            $previousConnectionId = trim((string) $data->telnyx_voice_credential_id);
            $newConnectionId = trim((string) $request->input('telnyx_voice_credential_id'));
            $data->candidate_calls_enabled = $request->boolean('candidate_calls_enabled');
            $data->telnyx_voice_credential_id = $newConnectionId;
            if ($previousConnectionId !== $newConnectionId) {
                $data->telnyx_webrtc_credential_id = null;
            }
            $data->telnyx_voice_from_number = $request->input('telnyx_voice_from_number');
        }

        if ($request->hasFile('logo')) {
            $data->logo = Files::upload($request->logo, 'app-logo');
        }
        if ($request->hasFile('favicon')) {
            $data->favicon = Files::upload($request->favicon, 'favicon');
        }

        $data->save();

            return Reply::redirect(route('admin.settings.index'));
        
    }

}
