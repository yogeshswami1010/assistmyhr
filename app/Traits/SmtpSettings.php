<?php
/**
 * Created by PhpStorm.
 * User: DEXTER
 * Date: 24/05/17
 * Time: 11:29 PM
 */

namespace App\Traits;

use App\CompanySetting;
use App\EmailSetting;
use Illuminate\Mail\MailServiceProvider;
use Illuminate\Support\Facades\Config;

trait SmtpSettings
{

    public function setMailConfigs()
    {
        $smtpSetting = EmailSetting::first();
        $settings = CompanySetting::first();
        
        // Ignore incase of development
        if (\config('app.env') !== 'development') {
            Config::set('mail.driver', $smtpSetting->mail_driver);
            Config::set('mail.host', $smtpSetting->mail_host);
            Config::set('mail.port', $smtpSetting->mail_port);
            Config::set('mail.username', $smtpSetting->mail_username);
            Config::set('mail.password', $smtpSetting->mail_password);
            Config::set('mail.encryption', $smtpSetting->mail_encryption);

            Config::set('mail.from.name', $smtpSetting->mail_from_name);
            Config::set('mail.from.address', $smtpSetting->mail_from_email);
        }
        Config::set('app.name', $settings->company_name);
        Config::set('mail.from.name', $settings->company_name);
        if (config('saas.enabled')) {
            $transport = [
                'transport' => 'smtp', 'host' => $smtpSetting?->mail_host,
                'port' => $smtpSetting?->mail_port ?: 587,
                'encryption' => $smtpSetting?->mail_encryption,
                'scheme' => $smtpSetting?->mail_encryption === 'ssl' ? 'smtps' : 'smtp',
                'username' => $smtpSetting?->mail_username, 'password' => $smtpSetting?->mail_password,
                'from' => ['address' => $smtpSetting?->mail_from_email, 'name' => $smtpSetting?->mail_from_name],
            ];
            config(['mail.default' => 'tenant', 'mail.mailers.tenant' => $transport,
                'mail.ai_search_smtp' => $transport, 'mail.from' => $transport['from']]);
            if (app()->bound('mail.manager')) { app('mail.manager')->forgetMailers(); }
        } else {
            (new MailServiceProvider(app()))->register();
        }
    }

}