<?php

namespace App\Saas;

class Subscription extends LandlordModel
{
    protected $table = 'saas_subscriptions';
    protected $casts = ['starts_at' => 'datetime', 'expires_at' => 'datetime'];

    public function plan() { return $this->belongsTo(Plan::class); }
    public function tenant() { return $this->belongsTo(Tenant::class); }
    public function hasAccess(): bool
    {
        return in_array($this->status, ['trialing', 'active'], true)
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }
}
