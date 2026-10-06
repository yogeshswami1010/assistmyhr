<?php

namespace App\Notifications;

use App\Traits\SmtpSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class NewJobOpening extends Notification
{
    use Queueable, SmtpSettings;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($job)
    {
        $this->job = $job;

        $this->setMailConfigs();
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

        // if ($this->smsSetting->nexmo_status == 'active') {
        //     array_push($via, 'vonage');
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
                    ->subject(__('email.newJobOpening.subject'))
                    ->greeting(__('email.hello').' ' . ucwords($notifiable->full_name) . '!')
                    ->line(__('email.newJobOpening.text'))
                    ->line(__('email.newJobOpening.jobTitle').' - '.$this->job->title)
                    ->line(__('email.newJobOpening.jobLocation').' - '.$this->job->location->location)
                    ->line(__('email.newJobOpening.moreDetails'))
                    ->action(__('email.newJobOpening.jobDetails'), tenant_route('jobs.jobDetail', [$this->job->slug]))
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
            'data' => $this->job->toArray()
        ];
    }
    
    /**
     * Get the Vonage / SMS representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\VonageMessage
     */
    // public function toVonage($notifiable)
    // {
    //     $link = '<a href="'.tenant_route('jobs.jobDetail', [$this->job->slug]).'">Link</a>';

    //     return (new VonageMessage)
    //                 ->content(
    //                     __('email.newJobOpening.text')."\n".
    //                     __('email.newJobOpening.phoneMessage')." ".$link
    //                 )->unicode();
    // }
}
