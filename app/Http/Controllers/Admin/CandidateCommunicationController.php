<?php

namespace App\Http\Controllers\Admin;

use App\CandidateEmailTemplate;
use App\ConsortiumRegistration;
use App\JobApplication;
use App\ApplicantSmsMessage;
use App\CandidateEmailMessage;
use App\SmsSetting;
use App\Services\TelnyxSmsService;
use App\Services\CandidateEmailFailure;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CandidateCommunicationController extends AdminBaseController
{
    private function authorizeMessaging(): void
    {
        abort_unless($this->user->cans('view_job_applications') && $this->user->cans('edit_job_applications'), 403);
    }


    protected function mailer(string $source)
    {
        // All candidate email entry points use the same SMTP account as AI Search.
        $settings = config('mail.ai_search_smtp');
        if (empty($settings['host']) || empty($settings['username']) || empty($settings['password']) || empty($settings['from']['address'])) {
            throw new \RuntimeException('AI Search SMTP is not configured.');
        }
        $mailer = Mail::build($settings);
        $mailer->alwaysFrom($settings['from']['address'], $settings['from']['name'] ?? null);
        return $mailer;
    }

    public function templates()
    {
        $this->authorizeMessaging();
        return response()->json(['templates' => CandidateEmailTemplate::where('user_id', $this->user->id)
            ->orderBy('name')->get(['id', 'name', 'subject', 'message']),
            'signature_html' => \App\Services\EmailSignatureHtml::clean($this->user->email_signature_html),
            'signature_image_url' => $this->user->email_signature_image_url,
            'signature' => $this->user->email_signature ?? '']);
    }

    public function emailConversation(JobApplication $application)
    {
        $this->authorizeMessaging();
        $messages = CandidateEmailMessage::with('user:id,name')->where('job_application_id', $application->id)->orderBy('received_at')->orderBy('id')->get();
        CandidateEmailMessage::whereIn('id', $messages->pluck('id'))->where('direction', 'inbound')->whereNull('read_at')->update(['read_at' => now()]);
        $displayMessages = $messages->map(function ($message) {
            $data = $message->toArray();
            $content = \App\Services\CandidateEmailContent::presentation((string) $message->body, $message->direction === 'inbound');
            $data['display_body'] = $content['reply'];
            $data['quoted_body'] = $content['quoted'];
            return $data;
        });
        return response()->json(['messages' => $displayMessages, 'unread' => 0])->header('Cache-Control', 'no-store');
    }

    public function emailUnread(JobApplication $application)
    {
        $this->authorizeMessaging();
        return response()->json(['unread' => CandidateEmailMessage::where('job_application_id', $application->id)->where('direction', 'inbound')->whereNull('read_at')->count()])->header('Cache-Control', 'no-store');
    }

    public function saveTemplate(Request $request)
    {
        $this->authorizeMessaging();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'subject' => ['required', 'string', 'max:191'],
            'message' => ['required', 'string', 'max:10000'],
        ]);
        $template = CandidateEmailTemplate::updateOrCreate(
            ['user_id' => $this->user->id, 'name' => $data['name']],
            ['subject' => $data['subject'], 'message' => $data['message']]
        );
        return response()->json(['template' => $template, 'message' => 'Email template saved.']);
    }


    public function preview(Request $request, TelnyxSmsService $sms)
    {
        $this->authorizeMessaging();
        $data = $request->validate([
            'channel' => ['required', 'in:email,sms'],
            'recipients' => ['required', 'array', 'min:1', 'max:100'],
            'recipients.*.type' => ['required', 'in:application,registration'],
            'recipients.*.id' => ['required', 'integer', 'min:1'],
        ]);
        $seen = [];
        $recipients = [];
        foreach ($data['recipients'] as $recipient) {
            $registration = $recipient['type'] === 'registration';
            $candidate = $registration ? ConsortiumRegistration::find($recipient['id']) : JobApplication::withTrashed()->whereNull('moved_to_trash_at')->find($recipient['id']);
            $name = $candidate ? ($registration ? trim($candidate->first_name.' '.$candidate->last_name) : $candidate->full_name) : 'Unavailable candidate';
            $reason = null;
            $address = trim((string) ($data['channel'] === 'email' ? $candidate?->email : $candidate?->phone));
            try {
                if (!$candidate) $reason = 'Candidate is no longer available.';
                elseif ($data['channel'] === 'sms' && $registration && !$candidate->sms_consent) $reason = 'No SMS consent.';
                elseif (!$address) $reason = 'Missing contact details.';
                elseif ($data['channel'] === 'email' && !filter_var($address, FILTER_VALIDATE_EMAIL)) $reason = 'Invalid email address.';
                else {
                    $address = $data['channel'] === 'sms' ? $sms->normalizePhone($address) : strtolower($address);
                    if (isset($seen[$address])) $reason = 'Duplicate contact.';
                    $seen[$address] = true;
                }
            } catch (\RuntimeException $e) {
                $reason = 'Invalid phone number.';
            }
            $recipients[] = $recipient + ['name' => $name, 'address' => $address, 'reason' => $reason];
        }
        return response()->json(['recipients' => $recipients]);
    }

    public function send(Request $request, TelnyxSmsService $sms)
    {
        $this->authorizeMessaging();
        $data = $request->validate([
            'channel' => ['required', 'in:email,sms'],
            'recipients' => ['required', 'array', 'min:1', 'max:100'],
            'recipients.*.type' => ['required', 'in:application,registration'],
            'recipients.*.id' => ['required', 'integer', 'min:1'],
            'source' => ['nullable', 'in:ai-search,candidates'],
            'subject' => ['required_if:channel,email', 'nullable', 'string', 'max:191'],
            'message' => ['required', 'string', $request->input('channel') === 'sms' ? 'max:1600' : 'max:10000'],
        ]);
        $results = [];
        $seen = [];
        foreach ($data['recipients'] as $recipient) {
            $registration = $recipient['type'] === 'registration';
            $candidate = $registration ? ConsortiumRegistration::find($recipient['id']) : JobApplication::withTrashed()->whereNull('moved_to_trash_at')->find($recipient['id']);
            $result = $recipient + ['status' => 'skipped', 'reason' => 'Candidate is no longer available.'];
            if (!$candidate) { $results[] = $result; continue; }
            if ($data['channel'] === 'sms' && $registration && !$candidate->sms_consent) {
                $result['reason'] = 'SMS consent has not been given.';
                $results[] = $result;
                continue;
            }
            $address = trim((string) ($data['channel'] === 'email' ? $candidate->email : $candidate->phone));
            if (!$address || ($data['channel'] === 'email' && !filter_var($address, FILTER_VALIDATE_EMAIL))) {
                $result['reason'] = 'Missing or invalid '.$data['channel'].' contact.';
                $results[] = $result;
                continue;
            }
            try {
                $address = $data['channel'] === 'sms' ? $sms->normalizePhone($address) : strtolower($address);
                if (isset($seen[$address])) {
                    $result['reason'] = 'Duplicate contact.';
                    $results[] = $result;
                    continue;
                }
                $seen[$address] = true;
                $name = $registration ? trim($candidate->first_name.' '.$candidate->last_name) : $candidate->full_name;
                $personalize = fn ($text) => str_ireplace(['{{applicant_name}}', '[applicant_name]', '%applicant_name%'], $name ?: 'Applicant', $text);
                $message = $personalize($data['message']);
                if ($data['channel'] === 'email') {
                    $subject = $personalize($data['subject']);
                    $from = data_get(config('mail.ai_search_smtp'), 'from.address', config('mail.from.address'));
                    $messageId = 'ats-'.Str::uuid().'@'.(explode('@', $from)[1] ?? 'localhost');
                    $this->mailer($data['source'] ?? 'candidates')->html(\App\Services\CandidateEmailBody::render($message, $this->user->email_signature, $this->user->email_signature_image_url, $this->user->email_signature_html), function ($mail) use ($address, $name, $subject, $messageId) {
                        $mail->to($address, $name)->subject($subject);
                        $mail->getSymfonyMessage()->getHeaders()->addIdHeader('Message-ID', $messageId);
                    });
                    if (!$registration) CandidateEmailMessage::create(['job_application_id' => $candidate->id, 'user_id' => $this->user->id, 'direction' => 'outbound', 'from_address' => $from, 'to_address' => $address, 'subject' => $subject, 'body' => $message, 'message_id' => $messageId, 'received_at' => now()]);
                } else {
                    if (mb_strlen($message) > 1600) {
                        throw new \RuntimeException('Personalized SMS exceeds 1600 characters.');
                    }
                    $messageId = $sms->send($address, $message);
                    if (!$registration) {
                        // A history write failure must not report an accepted SMS as unsent.
                        try {
                            ApplicantSmsMessage::create([
                                'job_application_id' => $candidate->id, 'user_id' => $this->user->id,
                                'direction' => 'outbound', 'from_number' => $sms->normalizePhone((string) \App\Services\PlatformTelephony::settings()->telnyx_from_number),
                                'to_number' => $address, 'message' => $message,
                                'telnyx_message_id' => $messageId, 'status' => 'sent',
                            ]);
                        } catch (\Throwable $e) {
                            Log::warning('Bulk SMS history could not be stored.', ['candidate_id' => $candidate->id]);
                        }
                    }
                }
                $result['status'] = 'sent';
                $result['reason'] = 'Accepted by the messaging provider.';
            } catch (\Throwable $e) {
                $reference = (string) Str::uuid();
                Log::warning('Candidate bulk message failed.', ['reference' => $reference, 'channel' => $data['channel'], 'source' => $data['source'] ?? 'candidates', 'type' => $recipient['type'], 'id' => $candidate->id, 'error' => $e->getMessage()]);
                $result['status'] = 'failed';
                $result['reason'] = ($data['channel'] === 'email' ? CandidateEmailFailure::message($e) : 'Could not send SMS. Check the phone number and SMS settings.').' Reference: '.$reference;
            }
            $results[] = $result;
        }
        return response()->json(['results' => $results]);
    }
}
