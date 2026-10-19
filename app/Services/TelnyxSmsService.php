<?php

namespace App\Services;

use App\SmsSetting;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TelnyxSmsService
{
    public function send(string $phone, string $message): string
    {
        $settings = \App\Services\PlatformTelephony::settings();
        if (!$settings || $settings->nexmo_status !== 'active' || $settings->sms_provider !== 'telnyx') {
            throw new RuntimeException('SMS is not enabled. Contact the platform administrator.');
        }
        if (!trim((string) $settings->telnyx_api_key)) {
            throw new RuntimeException(config('saas.enabled')
                ? 'SMS setup incomplete: save the Telnyx API key in Super Admin > Settings > Calling and SMS provider.'
                : 'Telnyx API key is required.');
        }
        if (!trim((string) $settings->telnyx_from_number)) {
            throw new RuntimeException(config('saas.enabled')
                ? "SMS setup incomplete: assign an SMS sender number in Super Admin > Clients > Manage > Calling & SMS."
                : 'Telnyx SMS sender number is required.');
        }

        $response = Http::withToken($settings->telnyx_api_key)->acceptJson()->timeout(20)
            ->post('https://api.telnyx.com/v2/messages', [
                'from' => $this->normalizePhone($settings->telnyx_from_number),
                'to' => $this->normalizePhone($phone),
                'text' => $message,
            ]);

        if (!$response->successful()) {
            $error = $response->json('errors.0.detail') ?? $response->json('errors.0.title')
                ?? 'Telnyx rejected the SMS request.';
            throw new RuntimeException($error);
        }

        return (string) $response->json('data.id');
    }

    public function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', trim($phone));
        if (strlen($digits) === 10) $digits = '1'.$digits;
        if (strlen($digits) !== 11 || !str_starts_with($digits, '1')) {
            throw new RuntimeException('Use a valid Canadian or US phone number with country code.');
        }
        return '+'.$digits;
    }
}
