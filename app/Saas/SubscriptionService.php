<?php

namespace App\Saas;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    public function extend(Tenant $tenant, PlatformAdmin $admin, array $data): Subscription
    {
        return DB::connection('saas_landlord')->transaction(function () use ($tenant, $admin, $data) {
            Tenant::whereKey($tenant->id)->lockForUpdate()->firstOrFail();
            $subscription = Subscription::where('tenant_id', $tenant->id)->lockForUpdate()->firstOrFail();
            $before = $subscription->only(['expires_at', 'status', 'plan_id']);
            $oldExpiry = $subscription->expires_at;
            $base = $oldExpiry && $oldExpiry->isFuture() ? $oldExpiry->copy() : now();
            $expiry = !empty($data['expires_at'])
                ? Carbon::parse($data['expires_at'])->endOfDay()
                : $base->addDays((int) $data['days']);
            if (!$expiry->isFuture() || ($oldExpiry && $expiry->lt($oldExpiry))) {
                throw \Illuminate\Validation\ValidationException::withMessages(['expires_at' => 'An extension must be in the future and cannot shorten the existing subscription.']);
            }
            $subscription->expires_at = $expiry;
            $subscription->status = 'active';
            if (!empty($data['plan_id'])) { $subscription->plan_id = $data['plan_id']; }
            $subscription->save();
            AuditLog::create([
                'tenant_id' => $tenant->id, 'admin_id' => $admin->id, 'action' => 'subscription.extended',
                'reason' => $data['reason'], 'before' => $before,
                'after' => $subscription->only(['expires_at', 'status', 'plan_id']),
            ]);
            return $subscription;
        });
    }
}
