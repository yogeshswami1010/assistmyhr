<?php

namespace App\Http\Controllers\Admin;

use App\Helper\Reply;
use App\Http\Requests\Admin\SmsSetting\UpdateRequest;
use App\Package;
use App\SmsSetting;

class AdminSmsSettingsController extends AdminBaseController
{
    public function __construct() {
        parent::__construct();
        $this->pageTitle = __('app.sms.smsCredential');
        $this->pageIcon = 'icon-settings';
    }

    public function index() {
        abort_if(config('saas.enabled'),403,'Calling and SMS settings are managed by the super admin.');
        $this->credentials = SmsSetting::first();
        return view('admin.sms-setting.index', $this->data);
    }

    public function update(UpdateRequest $request) {
        abort_if(config('saas.enabled'),403,'Calling and SMS settings are managed by the super admin.');
        $smsSetting = SmsSetting::first();

        // Save SMS Credentials
        $smsSetting->nexmo_status = $request->nexmo_status;
        $smsSetting->sms_provider = $request->sms_provider;
        $smsSetting->nexmo_key = $request->nexmo_key;
        $smsSetting->nexmo_secret = $request->nexmo_secret;
        $smsSetting->nexmo_from = $request->nexmo_from;
        $smsSetting->telnyx_api_key = $request->telnyx_api_key;
        $smsSetting->telnyx_from_number = $request->telnyx_from_number;
        $smsSetting->telnyx_public_key = $request->telnyx_public_key;

        $smsSetting->save();
        
        return Reply::success(__('menu.settings').' '.__('messages.updatedSuccessfully'));
    }
}
