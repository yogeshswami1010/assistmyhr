<?php

namespace App\Http\Controllers;

use App\Onboard;
use App\Services\ZohoSign;
use Illuminate\Http\Request;

class ZohoSignWebhookController extends Controller
{
    public function __invoke(Request $request, ZohoSign $zoho)
    {
        $secret = config('zoho-sign.webhook_secret');
        abort_unless($zoho->enabled() && filled($secret), 404);

        $body = $request->getContent();
        $signature = base64_encode(hash_hmac('sha256', $body, $secret, true));
        abort_unless(hash_equals($signature, (string) $request->header('X-ZS-WEBHOOK-SIGNATURE')), 401);

        $payload = json_decode($body, true);
        $id = data_get($payload, 'requests.request_id');
        if (! is_string($id) || ! preg_match('/\A[0-9]{1,64}\z/', $id)) {
            return response()->noContent();
        }

        $status = match (data_get($payload, 'notifications.operation_type')) {
            'RequestCompleted' => 'completed',
            'RequestRejected' => 'declined',
            'RequestRecalled' => 'recalled',
            'RequestExpired' => 'expired',
            default => null,
        };
        if ($status && ($offer = Onboard::where('zoho_sign_request_id', $id)->first())) {
            $zoho->applyStatus($offer, $status);
        }

        return response()->noContent();
    }
}
