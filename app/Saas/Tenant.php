<?php

namespace App\Saas;

use Illuminate\Database\Eloquent\Builder;

class Tenant extends LandlordModel
{
    protected $table = 'saas_tenants';
    protected $hidden = ['database_name', 'google_subject'];

    public function scopeWithEnabledFeature(Builder $query, string $feature): Builder
    {
        return $query->whereExists(function ($features) use ($feature) {
            $features->selectRaw('1')
                ->from('tenant_features')
                ->whereColumn('tenant_features.tenant_id', 'saas_tenants.id')
                ->where('tenant_features.feature_key', $feature)
                ->where('tenant_features.is_enabled', true);
        });
    }

    public function linkGoogleSubject(string $subject): bool
    {
        try {
            $this->newQuery()->whereKey($this->id)->whereNull('google_subject')->update(['google_subject' => $subject]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
            return false;
        }

        return $this->refresh()->google_subject === $subject;
    }

    public function subscription() { return $this->hasOne(Subscription::class, 'tenant_id'); }
    public function auditLogs() { return $this->hasMany(AuditLog::class, 'tenant_id')->latest('id'); }
    public function hasAccess(): bool
    {
        return $this->status === 'active' && $this->subscription?->hasAccess() === true;
    }
}
