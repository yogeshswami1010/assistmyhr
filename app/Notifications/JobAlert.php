<?php

namespace App\Notifications;

use App\Job;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class JobAlert extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Job $job)
    {
        $this->job = $job;
       
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

        // if ($this->smsSetting->nexmo_status == 'active' && $notifiable->mobile_verified == 1) {
        //     array_push($via, 'nexmo');   
        // }

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
                    ->subject(__('email.newJobAlert.subject'))
                    ->greeting(__('email.hello'))
                    ->line(__('email.newJobAlert.text').' - ' . ucwords($this->job->title))
                    ->action(__('email.newJobAlert.applyNow'), tenant_route('jobs.jobDetail', [$this->job->slug, $notifiable->hash]))
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
            //
        ];
    }
}
