<?php

namespace App\Services;

use App\Saas\PlatformSetting;
use App\Saas\Tenant;
use App\Saas\TenantContext;
use Illuminate\Support\Facades\Crypt;

class MicrosoftReplyAppSettings
{
    public function current(): array
    {
        $tenant = config('saas.enabled') ? app(TenantContext::class)->current() : null;
        return $tenant ? $this->forTenant($tenant) : $this->defaults();
    }

    public function forTenant(Tenant $tenant): array
    {
        $value = PlatformSetting::valueFor('microsoft_reply.'.$tenant->id);
        if ($value === null) return $this->defaults();
        $data = json_decode($value, true);
        $secret = '';
        try {
            if (!empty($data['client_secret'])) $secret = Crypt::decryptString($data['client_secret']);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            // A broken override must never fall back to another application's secret.
        }
        return ['client_id' => (string) ($data['client_id'] ?? ''), 'client_secret' => $secret];
    }

    /** Public metadata only, suitable for the Super Admin form. */
    public function metadata(Tenant $tenant): array
    {
        $value = PlatformSetting::valueFor('microsoft_reply.'.$tenant->id);
        $data = json_decode($value ?? '{}', true);
        $effective = $this->forTenant($tenant);
        return [
            'client_id' => (string) ($data['client_id'] ?? ''),
            'has_secret' => !empty($data['client_secret']),
            'has_override' => $value !== null,
            'available' => $effective['client_id'] !== '' && $effective['client_secret'] !== '',
        ];
    }

    private function defaults(): array
    {
        return [
            'client_id' => trim((string) config('services.microsoft_reply.client_id')),
            'client_secret' => trim((string) config('services.microsoft_reply.client_secret')),
        ];
    }
}
