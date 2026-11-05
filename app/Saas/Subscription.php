<?php

namespace App\Saas;

class Subscription extends LandlordModel
{
    public const TRIAL_USER_LIMIT = 3;

    protected $table = 'saas_subscriptions';
    protected $casts = ['starts_at' => 'datetime', 'expires_at' => 'datetime'];

    public function plan() { return $this->belongsTo(Plan::class); }
    public function tenant() { return $this->belongsTo(Tenant::class); }
    public function userLimit(): ?int
    {
        return $this->status === 'trialing' ? self::TRIAL_USER_LIMIT : $this->plan?->max_users;
    }

    public function hasAccess(): bool
    {
        return in_array($this->status, ['trialing', 'active'], true)
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }
}
