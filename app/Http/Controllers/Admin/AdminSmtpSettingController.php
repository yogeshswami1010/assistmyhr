<?php

namespace App\Http\Controllers\Admin;

use App\EmailSetting;
use App\Helper\Reply;
use App\Http\Requests\SmtpSetting\UpdateSmtpSetting;
use App\Notifications\TestEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class AdminSmtpSettingController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = __('menu.mailSetting');
        $this->pageIcon = 'ti-user';
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        $this->smtpSetting = EmailSetting::first();

        return view('admin.mail-setting.index', $this->data);
    }

    /**
     * @return array
     */
    public function update(UpdateSmtpSetting $request)
    {
        $smtp = EmailSetting::first();

        abort_if(! $this->user->cans('manage_settings'), 403);
        $data = $request->validated();
        $data['mail_encryption'] = \App\Services\SmtpConfiguration::encryption($data['mail_encryption'] ?? null, (int) ($data['mail_port'] ?? 587));
        if (empty($data['mail_password'])) { unset($data['mail_password']); }

        $smtp->update($data);
        $smtp->refresh();

        cache()->forget('smtp_setting');
        session()->forget('smtp_setting');

        $response = $smtp->verifySmtp();

        if ($smtp->mail_driver == 'mail') {
            return Reply::success(__('messages.updatedSuccessfully'));
        }

        if ($response['success']) {
            return Reply::success($response['message']);
        }

        $message = 'SMTP connection failed. Check your provider host, port and encryption, app password or SMTP authorization, and VPS outbound mail access.';
        if ($smtp->mail_host === 'smtp.gmail.com') {
            $message .= ' Gmail requires an app password for this password-based connection.';
        }
        return Reply::error($message.'<br>'.e($response['message']));
    }

    public function sendTestEmail(Request $request)
    {
        abort_if(! $this->user->cans('manage_settings'), 403);
        $request->validate([
            'test_email' => 'required|email',
        ]);

        $smtp = EmailSetting::first();
        $response = $smtp->verifySmtp();

        if (! $response['success']) {
            return Reply::error($response['message']);
        }

        try {
            Notification::route('mail', $request->input('test_email'))->notify(new TestEmail());

            return Reply::success('Test mail sent successfully');
        } catch (\Exception $e) {
            return Reply::error('Failed to send test email: '.$e->getMessage());
        }
    }
}
