<?php

namespace App\Saas;

class Subscription extends LandlordModel
{
    public const TRIAL_USER_LIMIT = 3;
    public const TRIAL_DAYS = 30;

    protected $table = 'saas_subscriptions';
    protected $casts = ['starts_at' => 'datetime', 'expires_at' => 'datetime', 'seat_count' => 'integer'];

    public function plan() { return $this->belongsTo(Plan::class); }
    public function tenant() { return $this->belongsTo(Tenant::class); }
    public function userLimit(): ?int
    {
        return $this->status === 'trialing' ? self::TRIAL_USER_LIMIT : ($this->seat_count ?? self::TRIAL_USER_LIMIT);
    }

    public function hasAccess(): bool
    {
        return in_array($this->status, ['trialing', 'active'], true)
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }
}
