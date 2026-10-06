<?php

namespace App\Notifications;

use App\JobApplication;
use App\SmsSetting;
use App\Traits\SmsSettings;
use App\Traits\SmtpSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\VonageMessage;

class ScheduleInterviewStatus extends Notification
{
    use Queueable, SmtpSettings, SmsSettings;

    protected $jobApplication;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($jobApplication)
    {
        $this->jobApplication = $jobApplication;
        $this->smsSetting = SmsSetting::first();

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
        return (new MailMessage)
            ->subject(__('email.interviewScheduleStatus.subject'))
            ->greeting(__('email.hello').' ' . ucwords($notifiable->name) . '!')
            ->line($this->jobApplication->full_name.' '.__('email.interviewScheduleStatus.text').' - ' . ucwords($this->jobApplication->job->title) .' ' .__('email.interviewScheduleStatus.statusChangesTo'). '  ' . ucFirst($this->jobApplication->status->status))
            ->action(__('email.ScheduleStatusCandidate.forCheckDetails').' '.__('email.loginDashboard'), tenant_route('login'))
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
                        $this->jobApplication->full_name.' '.__('email.interviewScheduleStatus.text').' - ' . ucwords($this->jobApplication->job->title) .' ' .__('email.interviewScheduleStatus.statusChangesTo'). '  ' . ucFirst($this->jobApplication->status->status)
                    )->unicode();
    }
}
