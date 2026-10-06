<?php

namespace App\Notifications;

use App\JobApplication;
use App\SmsSetting;
use App\Traits\SmsSettings;
use App\Traits\SmtpSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\VonageMessage;

class JobOffer extends Notification
{
    use Queueable, SmtpSettings;
//        , SmsSettings;

    /**
     * JobOffer constructor.
     * @param JobApplication $jobApplication
     */
    public function __construct(JobApplication $jobApplication)
    {
        $this->jobApplication = $jobApplication;
//        $this->smsSetting = SmsSetting::first();

        $this->setMailConfigs();
//        $this->setSmsConfigs();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        $via = ['mail'];

//        if ($this->smsSetting->nexmo_status == 'active' && $notifiable->mobile_verified == 1) {
//            array_push($via, 'vonage');
//        }

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
        return (new MailMessage)
            ->subject(__('email.jobOffer.subject'))
            ->greeting(__('email.hello').' ' . ucwords($this->jobApplication->full_name) . '!')
            ->line(__('email.jobOffer.text'))
            ->line(__('modules.jobs.jobTitle').' - ' . ucwords($this->jobApplication->job->title))
            ->action(__('email.viewOffer'), tenant_route('jobs.job-offer', $this->jobApplication->onboard->offer_code))
            ->line(__('email.thankyouNote'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        //
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
                        __($this->jobApplication->full_name).' '.__('email.jobOffer.text').' - ' . ucwords($this->jobApplication->job->title)
                    )->unicode();
    }
}
