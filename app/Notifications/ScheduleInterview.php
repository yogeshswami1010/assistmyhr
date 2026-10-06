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

class ScheduleInterview extends Notification
{
    use Queueable, SmtpSettings, SmsSettings;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(JobApplication $jobApplication , $meetings)
    {
        $this->jobApplication = $jobApplication;
        $this->smsSetting = SmsSetting::first();
        $this->meetings = $meetings;

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
        $emailContent = (new MailMessage)
            ->subject(__('email.interviewSchedule.subject'))
            ->greeting(__('email.hello').' ' . ucwords($notifiable->name) . '!')
            ->line(__($this->jobApplication->full_name).' '.__('email.interviewSchedule.text').' - ' . ucwords($this->jobApplication->job->title))
            ->action(__('email.interviewSchedule.response').' '.__('email.loginDashboard'), tenant_route('login'));
            if($this->meetings != null){
                if( $notifiable->id == $this->meetings->created_by){
                    $emailContent = $emailContent->line(__('modules.zoommeeting.meetingPassword') . ' - ' . $this->meetings->password);
                    $emailContent = $emailContent->action(__('modules.zoommeeting.startUrl'), url($this->meetings->start_link));
                }else{
                    $emailContent = $emailContent->line(__('modules.zoommeeting.meetingPassword') . ' - ' . $this->meetings->password);
                    $emailContent = $emailContent->action(__('modules.zoommeeting.joinUrl'), url($this->meetings->join_link));
                }

            }else{
                $emailContent =  $emailContent->line(__('modules.interviewSchedule.interviewType').' - ' . __('modules.meetings.offline'));

            }
            $emailContent = $emailContent->line(__('email.thankyouNote'));
            return $emailContent;
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
                        __($this->jobApplication->full_name).' '.__('email.interviewSchedule.text').' - ' . ucwords($this->jobApplication->job->title)
                    )->unicode();
    }
}
