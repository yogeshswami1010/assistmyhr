<?php
namespace App\Http\Controllers\Admin;

use App\CandidateClientReview;
use App\CandidateClientReviewMessage;
use App\JobApplication;
use App\Services\CandidateClientReviewService;
use App\Services\CandidateEmailContent;
use App\Services\CandidateEmailFailure;
use App\Services\ClientReviewContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CandidateClientReviewController extends AdminBaseController
{
    private function authorizeReview(bool $edit = false): void
    {
        abort_unless($this->user->cans('view_job_applications') && (!$edit || $this->user->cans('edit_job_applications')), 403);
    }

    private function candidate(int $id): JobApplication
    {
        return JobApplication::withTrashed()->whereNull('moved_to_trash_at')
            ->select(['id', 'full_name', 'job_id'])->with(['resumeDocument', 'job:id,title'])->findOrFail($id);
    }

    public function index(int $application)
    {
        $this->authorizeReview();
        $this->candidate($application);
        $reviews = CandidateClientReview::with(['user:id,name', 'messages.user:id,name'])
            ->where('job_application_id', $application)->latest('id')->get();
        $ids = $reviews->flatMap(fn ($review) => $review->messages->pluck('id'));
        CandidateClientReviewMessage::whereIn('id', $ids)->where('direction', 'inbound')->whereNull('read_at')->update(['read_at' => now()]);
        return response()->json(['view' => view('admin.job-applications.partials.client-review-conversation', [
            'reviews' => $reviews, 'canEdit' => $this->user->cans('edit_job_applications'), 'applicationId' => $application,
        ])->render(), 'unread' => 0])->header('Cache-Control', 'no-store');
    }

    public function unread(int $application)
    {
        $this->authorizeReview();
        return response()->json(['unread' => CandidateClientReviewMessage::whereHas('review', fn ($query) => $query->where('job_application_id', $application))
            ->where('direction', 'inbound')->whereNull('read_at')->count()])->header('Cache-Control', 'no-store');
    }

    public function send(Request $request, int $application, CandidateClientReviewService $service)
    {
        $this->authorizeReview(true);
        $data = $request->validate([
            'client_email' => ['required', 'email:rfc', 'max:255'],
            'subject' => ['required', 'string', 'max:191'],
            'message_payload' => ['required', 'string', 'max:70000'],
            'client_message' => ['nullable', 'string', 'max:10000'],
            'submission_id' => ['required', 'uuid'],
        ]);
        $bodyHtml = ClientReviewContent::decode($data['message_payload']);
        $clientMessage = isset($data['client_message']) ? trim($data['client_message']) : null;
        $candidate = $this->candidate($application);
        $document = $candidate->resumeDocument;
        if (!$document || !Storage::exists('documents/'.$candidate->id.'/'.basename($document->hashname))) {
            throw ValidationException::withMessages(['client_email' => 'Attach a CV to this candidate before sending it for review.']);
        }
        $review = CandidateClientReview::firstOrCreate(['public_id' => $data['submission_id']], [
            'job_application_id' => $candidate->id, 'user_id' => $this->user->id,
            'client_email' => strtolower(trim($data['client_email'])), 'subject' => $data['subject'],
            'candidate_name' => $candidate->full_name, 'job_title' => $candidate->job?->title,
            'resume_hashname' => basename($document->hashname), 'resume_original_name' => $document->original_name ?: $document->hashname,
            'body_html' => $bodyHtml, 'client_message' => $clientMessage !== '' ? $clientMessage : null,
            'expires_at' => now()->addDays(30),
        ]);
        abort_unless((int) $review->job_application_id === $application && (int) $review->user_id === (int) $this->user->id
            && $review->client_email === strtolower(trim($data['client_email'])), 409);
        $message = $review->messages()->firstOrCreate(['submission_id' => $data['submission_id']], [
            'user_id' => $this->user->id, 'direction' => 'outbound', 'body_html' => $review->body_html,
            'body_text' => CandidateEmailContent::plain($review->body_html), 'mail_status' => 'pending',
        ]);
        return $this->deliver($review, $message, $service);
    }

    public function reply(Request $request, int $application, CandidateClientReview $review, CandidateClientReviewService $service)
    {
        $this->authorizeReview(true);
        $this->scope($application, $review);
        abort_unless($review->isAvailable(), 410, 'This review link is no longer active. Send a new invitation.');
        $data = $request->validate(['message_payload' => ['required', 'string', 'max:70000'], 'submission_id' => ['required', 'uuid']]);
        $html = ClientReviewContent::decode($data['message_payload']);
        $message = $review->messages()->firstOrCreate(['submission_id' => $data['submission_id']], [
            'user_id' => $this->user->id, 'direction' => 'outbound', 'body_html' => $html,
            'body_text' => CandidateEmailContent::plain($html), 'mail_status' => 'pending',
        ]);
        abort_unless($message->direction === 'outbound' && (int) $message->user_id === (int) $this->user->id, 409);
        return $this->deliver($review, $message, $service);
    }

    public function retry(int $application, CandidateClientReview $review, int $message, CandidateClientReviewService $service)
    {
        $this->authorizeReview(true);
        $this->scope($application, $review);
        $message = $review->messages()->where('direction', 'outbound')->findOrFail($message);
        return $this->deliver($review, $message, $service);
    }

    public function revoke(int $application, CandidateClientReview $review)
    {
        $this->authorizeReview(true);
        $this->scope($application, $review);
        $review->update(['revoked_at' => $review->revoked_at ?? now()]);
        return response()->json(['message' => 'Client access revoked.']);
    }

    private function scope(int $application, CandidateClientReview $review): void
    {
        $this->candidate($application);
        abort_unless((int) $review->job_application_id === $application, 404);
    }

    private function deliver(CandidateClientReview $review, CandidateClientReviewMessage $message, CandidateClientReviewService $service)
    {
        try {
            $service->send($review, $message, $message->user ?? $this->user);
        } catch (\Throwable $error) {
            if ($error instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) throw $error;
            $message->refresh();
            if ($message->mail_status !== 'sent') $message->update(['mail_status' => 'failed']);
            report($error);
            return response()->json(['message' => CandidateEmailFailure::message($error), 'saved' => true], 422);
        }
        return response()->json(['message' => 'Profile review email sent to '.$review->client_email.'.', 'review_id' => $review->id]);
    }
}
