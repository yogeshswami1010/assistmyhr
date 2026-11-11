<?php

namespace App\Http\Controllers;

use App\Onboard;
use App\Saas\Tenant;
use App\Saas\TenantContext;
use App\Services\ZohoSign;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ZohoSignWebhookController extends Controller
{
    public function __invoke(Request $request, ZohoSign $zoho): JsonResponse
    {
        $secret = config('zoho-sign.webhook_secret');
        abort_unless($zoho->enabled() && filled($secret), 404);

        $body = $request->getContent();
        $signature = base64_encode(hash_hmac('sha256', $body, $secret, true));
        abort_unless(hash_equals($signature, (string) $request->header('X-ZS-WEBHOOK-SIGNATURE')), 401);

        $payload = json_decode($body, true);
        $id = data_get($payload, 'requests.request_id');
        if (! is_string($id) || ! preg_match('/\A[0-9]{1,64}\z/', $id)) {
            return new JsonResponse(['received' => true]);
        }

        $status = match (data_get($payload, 'notifications.operation_type')) {
            'RequestCompleted' => 'completed',
            'RequestRejected' => 'declined',
            'RequestRecalled' => 'recalled',
            'RequestExpired' => 'expired',
            default => null,
        };
        if ($status) {
            $this->updateOffer($zoho, $id, $status);
        }

        return new JsonResponse(['received' => true]);
    }

    private function updateOffer(ZohoSign $zoho, string $id, string $status): void
    {
        $context = app(TenantContext::class);
        try {
            if (config('saas.enabled')) {
                $tenantId = DB::connection('saas_landlord')->table('saas_zoho_sign_requests')
                    ->where('request_id', $id)->value('tenant_id');
                $tenant = Tenant::find($tenantId);
                if (! $tenant) {
                    return;
                }
                $context->activate($tenant);
            }

            $offer = Onboard::where('zoho_sign_request_id', $id)->first();
            if ($offer) {
                $zoho->applyStatus($offer, $status);
            }
        } finally {
            if (config('saas.enabled')) {
                $context->reset();
            }
        }
    }
}
