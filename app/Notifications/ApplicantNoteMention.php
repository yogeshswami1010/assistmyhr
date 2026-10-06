<?php

namespace App\Notifications;

use App\ApplicantNote;
use App\Traits\SmtpSettings;
use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ApplicantNoteMention extends Notification
{
    use Queueable, SmtpSettings;

    public $note;
    private string $mentionedBy;

    public function __construct(ApplicantNote $note, ?User $actor = null)
    {
        $this->note = $note;
        $this->mentionedBy = $actor?->name ?? $note->user?->name ?? 'A team member';
        $this->setMailConfigs();
    }

    public function via($notifiable)
    {
        return $notifiable->email ? ['database', 'mail'] : ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'note_id'          => $this->note->id,
            'note_text'        => $this->note->note_text,
            'mentioned_by'     => $this->mentionedBy,
            'application_id'   => $this->note->job_application_id,
            'message'          => $this->mentionedBy . ' mentioned you in an applicant note.',
        ];
    }

    public function toMail($notifiable)
    {
        $application = $this->note->jobApplication;
        $applicantName = $application?->full_name ?? 'an applicant';
        $jobTitle = $application?->job?->title ?? 'Unassigned job';
        $profileUrl = tenant_route('admin.job-applications.table', [
            'jobs' => $application?->job_id ?: 'all',
            'open' => $this->note->job_application_id,
        ]);

        return (new MailMessage)
            ->subject('You were mentioned on ' . $applicantName . "'s application")
            ->greeting('Hello ' . ucwords($notifiable->name) . ',')
            ->line($this->mentionedBy . ' mentioned you in an applicant note.')
            ->line('Applicant: ' . $applicantName)
            ->line('Job: ' . $jobTitle)
            ->line('"' . $this->note->note_text . '"')
            ->action('Open Applicant Profile', $profileUrl)
            ->line('Sign in to the ATS to view the complete applicant profile and respond.');
    }
}
