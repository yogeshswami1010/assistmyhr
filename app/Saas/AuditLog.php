<?php

namespace App\Saas;

class AuditLog extends LandlordModel
{
    protected $table = 'saas_audit_logs';
    protected $casts = ['before' => 'array', 'after' => 'array'];
    public function admin() { return $this->belongsTo(PlatformAdmin::class, 'admin_id'); }
}
