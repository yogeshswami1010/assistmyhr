<?php

namespace App\Saas;

class Tenant extends LandlordModel
{
    protected $table = 'saas_tenants';
    protected $hidden = ['database_name', 'google_subject'];

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
