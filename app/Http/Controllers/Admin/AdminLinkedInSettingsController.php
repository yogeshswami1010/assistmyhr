<?php

namespace App\Http\Controllers\Admin;

use App\Helper\Reply;
use App\Http\Requests\Admin\LinkedInSetting\UpdateRequest;
use Illuminate\Http\Request;
use App\LinkedInSetting;

class AdminLinkedInSettingsController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = __('menu.linkedInSettings');
        $this->pageIcon = 'icon-settings';
    }

    public function index(){
        $this->linkedInSetting = LinkedInSetting::first();
        $this->linkedInSetting->callback_url = tenant_route('jobs.linkedinCallback', 'linkedin');
        return view('admin.linked-in-settings.edit', $this->data);
    }

    public function update(UpdateRequest $request, $id){
        $setting = LinkedInSetting::findOrFail($id);
        $setting->client_id = $request->input('client_id');
        $setting->client_secret = $request->input('client_secret');
        $setting->callback_url = $request->input('callback_url');
        $setting->status = $request->status;

        $setting->save();

        return Reply::success(__('menu.settings').' '.__('messages.updatedSuccessfully'));
    }
}
