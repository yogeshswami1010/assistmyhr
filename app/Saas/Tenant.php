<?php

namespace App\Saas;

class Tenant extends LandlordModel
{
    protected $table = 'saas_tenants';
    protected $hidden = ['database_name'];

    public function subscription() { return $this->hasOne(Subscription::class, 'tenant_id'); }
    public function auditLogs() { return $this->hasMany(AuditLog::class, 'tenant_id')->latest('id'); }
    public function hasAccess(): bool
    {
        return $this->status === 'active' && $this->subscription?->hasAccess() === true;
    }
}
