<?php
namespace App\Services;

use App\CandidateClientReview;
use App\CandidateClientReviewMessage;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class CandidateClientReviewService
{
    public function url(CandidateClientReview $review, string $action = 'show'): string
    {
        return URL::temporarySignedRoute('client-reviews.'.$action, $review->expires_at, tenant_parameters(['review' => $review->public_id]));
    }

    protected function mailer()
    {
        $settings = config('mail.ai_search_smtp');
        if (empty($settings['host']) || empty($settings['username']) || empty($settings['password']) || empty($settings['from']['address'])) {
            throw new \RuntimeException('AI Search SMTP is not configured.');
        }
        $mailer = Mail::build($settings);
        $mailer->alwaysFrom($settings['from']['address'], $settings['from']['name'] ?? null);
        return $mailer;
    }

    public function send(CandidateClientReview $review, CandidateClientReviewMessage $message, User $sender): void
    {
        DB::transaction(function () use ($review, $message, $sender) {
            $locked = CandidateClientReviewMessage::lockForUpdate()->findOrFail($message->id);
            if ($locked->direction !== 'outbound' || (int) $locked->candidate_client_review_id !== (int) $review->id) {
                throw new \LogicException('Only staff messages from this review can be emailed to the client.');
            }
            if ($locked->mail_status === 'sent') return;
            abort_if($review->revoked_at || !$review->expires_at->isFuture(), 410, 'This review link is no longer active.');
            $signature = CandidateEmailBody::render('', $sender->email_signature, $sender->email_signature_image_url, $sender->email_signature_html);
            $html = view('email.client-review-invitation', [
                'review' => $review, 'bodyHtml' => ClientReviewContent::clean((string) $locked->body_html),
                'reviewUrl' => $this->url($review), 'signatureHtml' => $signature,
            ])->render();
            $this->mailer()->html($html, function ($mail) use ($review, $sender) {
                $mail->to($review->client_email)->subject($review->subject);
                if (filter_var($sender->email, FILTER_VALIDATE_EMAIL)) $mail->replyTo($sender->email, $sender->name);
            });
            $locked->update(['mail_status' => 'sent']);
            if (!$review->sent_at) $review->update(['sent_at' => now()]);
        });
    }

    public function notify(CandidateClientReview $review, CandidateClientReviewMessage $message): void
    {
        DB::transaction(function () use ($review, $message) {
            $locked = CandidateClientReviewMessage::lockForUpdate()->findOrFail($message->id);
            if ($locked->direction !== 'inbound' || (int) $locked->candidate_client_review_id !== (int) $review->id) {
                throw new \LogicException('Only client feedback from this review can notify the ATS team.');
            }
            if ($locked->notification_sent_at || $locked->notification_skipped_at) return;
            $clientAddress = strtolower(trim($review->client_email));
            $recipient = trim((string) data_get(config('mail.ai_search_smtp'), 'from.address'));
            if (!filter_var($recipient, FILTER_VALIDATE_EMAIL) || strtolower($recipient) === $clientAddress) {
                // Feedback remains in ATS. Never send it back to the submitting client
                // or fall back to a team member's personal mailbox.
                $locked->update(['notification_skipped_at' => now()]);
                return;
            }
            $html = view('email.client-review-reply', [
                'review' => $review, 'message' => $locked,
                'atsUrl' => tenant_route('admin.job-applications.table', ['review_candidate' => $review->job_application_id]),
            ])->render();
            $this->mailer()->html($html, function ($mail) use ($review, $recipient) {
                $mail->to($recipient)->replyTo($review->client_email)->subject('Client review: '.$review->candidate_name);
            });
            $locked->update(['notification_sent_at' => now()]);
        });
    }
}
