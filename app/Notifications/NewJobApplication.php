<?php

namespace App\Notifications;

use App\JobApplication;
use App\SmsSetting;
use App\Traits\SmsSettings;
use App\Traits\SmtpSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Action;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\VonageMessage;

class NewJobApplication extends Notification
{
    use Queueable, SmtpSettings, SmsSettings;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(JobApplication $jobApplication, $linkedin)
    {
        $this->jobApplication = $jobApplication;
        $this->smsSetting = SmsSetting::first();
        $this->linkedin = $linkedin;

        $this->setMailConfigs();
        $this->setSmsConfigs();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        $via = ['mail', 'database'];

        if ($this->smsSetting->nexmo_status == 'active' && $notifiable->mobile_verified == 1) {
            array_push($via, 'vonage');
        }

        return $via;
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $buttonUrl = '';
        if ($this->linkedin){
            $buttonUrl = url('https://www.linkedin.com/sales/gmail/profile/proxy/' . $this->jobApplication->email);
        }
        $content = __($this->jobApplication->full_name).' ('.$this->jobApplication->email.') '.__('email.newJobApplication.text').' - ' . ucwords($this->jobApplication->job->title);

        return (new MailMessage)
            ->subject(__('email.newJobApplication.subject'))
            ->greeting(__('email.hello').' ' . ucwords($notifiable->name) . '!')
            ->markdown('email.job-apply', ['url' => tenant_route('login'),'buttonText' => __('email.loginDashboard'),'buttonUrl' => $buttonUrl,'extraButtonText' => 'LinkedIn Profile', 'content' => $content]);
    }

    /**
     * Get the array representation of the notification.
     * 
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'data' => $this->jobApplication->toArray()
        ];
    }

    /**
     * Get the Vonage / SMS representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return VonageMessage
     */
    public function toVonage($notifiable)
    {
        return (new VonageMessage)
            ->content(
                __($this->jobApplication->full_name).' '.__('email.newJobApplication.text').' - ' . ucwords($this->jobApplication->job->title)
            )->unicode();
    }
}
