<?php

namespace App\Services;

use App\Mail\WebsiteEnquiryReceived;
use App\Saas\WebsiteEnquiry;
use Illuminate\Support\Facades\Mail;

class WebsiteEnquiryNotifier
{
    public const RECIPIENT = 'support@assistmyhr.com';

    public function send(WebsiteEnquiry $enquiry): void
    {
        $legacyDriver = config('mail.driver');
        $platform = config('saas.enabled');
        try {
            if ($platform) {
                // Legacy mail.driver overrides named mailers unless temporarily cleared.
                config(['mail.driver' => null]);
                app('mail.manager')->purge('platform');
            }
            $mail = new WebsiteEnquiryReceived($enquiry);
            $from = config($platform ? 'mail.mailers.platform.from' : 'mail.from');
            $mail->from($from['address'], $from['name'] ?? 'AssistMyHR');
            Mail::mailer($platform ? 'platform' : null)->to(self::RECIPIENT)->send($mail);
            $enquiry->update(['notification_status' => 'sent', 'notified_at' => now()]);
        } catch (\Throwable $exception) {
            report($exception);
            // Keep the saved request available even when SMTP is unavailable.
            $enquiry->update(['notification_status' => 'failed']);
        } finally {
            if ($platform) {
                config(['mail.driver' => $legacyDriver]);
                app('mail.manager')->purge('platform');
            }
        }
    }
}
