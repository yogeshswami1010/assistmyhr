<?php

namespace App\Http\Controllers;

use App\ApplicantSmsMessage;
use App\JobApplication;
use App\Notifications\ApplicantSmsReply;
use App\Services\TelnyxSmsService;
use App\SmsSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class TelnyxWebhookController extends Controller
{
    public function __invoke(Request $request, TelnyxSmsService $sms): Response
    {
        $settings = \App\Services\PlatformTelephony::settings();
        $publicKey = trim((string) optional($settings)->telnyx_public_key);

        if ($publicKey === '' || ! $this->hasValidSignature($request, $publicKey)) {
            Log::warning('Rejected an unsigned or invalid Telnyx webhook.');
            return response('Invalid signature', 401);
        }

        $eventType = (string) $request->input('data.event_type');
        $payload = (array) $request->input('data.payload', []);
        $messageId = (string) ($payload['id'] ?? '');

        if ($eventType !== 'message.received' || $messageId === '') {
            return response('OK');
        }

        if (ApplicantSmsMessage::where('telnyx_message_id', $messageId)->exists()) {
            return response('OK');
        }

        $from = (string) data_get($payload, 'from.phone_number', '');
        $to = (string) data_get($payload, 'to.0.phone_number', '');
        $message = trim((string) ($payload['text'] ?? ''));

        try {
            $normalizedFrom = $sms->normalizePhone($from);
            $normalizedTo = $sms->normalizePhone($to);
        } catch (\Throwable $e) {
            Log::warning('Telnyx reply contains an invalid phone number.', ['message_id' => $messageId]);
            return response('OK');
        }

        if (config('saas.enabled') && ($settings->nexmo_status !== 'active' || $normalizedTo !== $settings->telnyx_from_number)) { return response('Recipient is unavailable',403); }

        // Prefer the exact conversation that most recently sent to this
        // number. This matters when one candidate has applications for
        // multiple jobs.
        $lastOutboundMessage = ApplicantSmsMessage::query()
            ->where('direction', 'outbound')
            ->where('to_number', $normalizedFrom)
            ->whereNotNull('user_id')
            ->latest('id')
            ->first();

        $application = $lastOutboundMessage?->application;
        if (! $application) {
            $application = JobApplication::withTrashed()
                ->whereNotNull('phone')
                ->latest('id')
                ->get(['id', 'phone'])
                ->first(function (JobApplication $candidate) use ($sms, $normalizedFrom) {
                    try {
                        return $sms->normalizePhone((string) $candidate->phone) === $normalizedFrom;
                    } catch (\Throwable $e) {
                        return false;
                    }
                });
        }

        if (! $application) {
            Log::notice('Telnyx reply could not be matched to an applicant.', [
                'from' => $normalizedFrom,
                'message_id' => $messageId,
            ]);
            return response('OK');
        }

        $inboundMessage = ApplicantSmsMessage::create([
            'job_application_id' => $application->id,
            'direction' => 'inbound',
            'from_number' => $normalizedFrom,
            'to_number' => $normalizedTo,
            'message' => $message,
            'telnyx_message_id' => $messageId,
            'status' => 'received',
            'received_at' => now(),
        ]);

        // A reply belongs to the team member who most recently sent an SMS
        // to this applicant. Do not notify every ATS team member.
        $replyOwner = $lastOutboundMessage?->user;
        if ($replyOwner) {
            $inboundMessage->loadMissing('application.job');
            app()->terminating(function () use ($replyOwner, $inboundMessage) {
                try {
                    $replyOwner->notify(new ApplicantSmsReply($inboundMessage));
                } catch (\Throwable $e) {
                    Log::error('Applicant SMS reply email failed.', [
                        'sms_message_id' => $inboundMessage->id,
                        'user_id' => $replyOwner->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            });
        }

        return response('OK');
    }

    private function hasValidSignature(Request $request, string $configuredKey): bool
    {
        if (! function_exists('sodium_crypto_sign_verify_detached')) {
            Log::error('PHP Sodium is required to verify Telnyx webhooks.');
            return false;
        }

        $signature = base64_decode((string) $request->header('telnyx-signature-ed25519'), true);
        $timestamp = (string) $request->header('telnyx-timestamp');
        if ($signature === false || $timestamp === '' || abs(time() - (int) $timestamp) > 300) {
            return false;
        }

        $key = ctype_xdigit($configuredKey) && strlen($configuredKey) === 64
            ? hex2bin($configuredKey)
            : base64_decode($configuredKey, true);

        return is_string($key)
            && strlen($key) === SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES
            && sodium_crypto_sign_verify_detached($signature, $timestamp.'|'.$request->getContent(), $key);
    }
}
