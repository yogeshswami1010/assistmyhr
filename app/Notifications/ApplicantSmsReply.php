<?php

namespace App\Notifications;

use App\ApplicantSmsMessage;
use App\Traits\SmtpSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicantSmsReply extends Notification
{
    use Queueable, SmtpSettings;

    public function __construct(private ApplicantSmsMessage $smsMessage)
    {
        $this->setMailConfigs();
    }

    public function via($notifiable): array
    {
        return $notifiable->email ? ['database', 'mail'] : ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $application = $this->smsMessage->application;

        return [
            'message' => ($application?->full_name ?? 'An applicant').' replied to your SMS.',
            'sms_message' => $this->smsMessage->message,
            'application_id' => $this->smsMessage->job_application_id,
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        $application = $this->smsMessage->application;
        $applicantName = $application?->full_name ?? 'Applicant';
        $jobTitle = $application?->job?->title ?? 'Unassigned job';
        $profileUrl = tenant_route('admin.job-applications.table', [
            'jobs' => $application?->job_id ?: 'all',
            'open' => $this->smsMessage->job_application_id,
            'tab' => 'sms',
        ]);

        return (new MailMessage)
            ->subject($applicantName.' replied to your SMS')
            ->greeting('Hello '.ucwords($notifiable->name).',')
            ->line($applicantName.' replied to the SMS conversation for '.$jobTitle.'.')
            ->line('Message:')
            ->line('"'.$this->smsMessage->message.'"')
            ->action('Open SMS Conversation', $profileUrl)
            ->line('This notification was sent only to the team member who last messaged this applicant.');
    }
}
