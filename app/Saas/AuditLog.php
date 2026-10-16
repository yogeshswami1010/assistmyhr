<?php

namespace App\Saas;

class AuditLog extends LandlordModel
{
    protected $table = 'saas_audit_logs';
    protected $casts = ['before' => 'array', 'after' => 'array'];
    public function admin() { return $this->belongsTo(PlatformAdmin::class, 'admin_id'); }
    public function tenant() { return $this->belongsTo(Tenant::class, 'tenant_id'); }
    public function getActionLabelAttribute(): string
    {
        return [
            'tenant.created' => 'Client account created',
            'tenant.provisioning_failed' => 'Client account setup failed',
            'tenant.deleted' => 'Client account deleted',
            'tenant.status_changed' => 'Client access updated',
            'owner.email_verified_by_admin' => 'Email verified by administrator',
            'subscription.change_requested' => 'Plan or renewal requested',
            'subscription.extended' => 'Subscription extended',
            'subscription.plan_changed' => 'Subscription plan changed',
            'plan.saved' => 'Plan saved',
            'admin.profile_updated' => 'Administrator profile updated',
            'admin.password_changed' => 'Administrator password changed',
            'admin.created' => 'Super administrator added',
            'platform.settings_changed' => 'Platform settings updated',
            'telephony.provider_updated' => 'Calling and SMS provider updated',
            'telephony.client_updated' => 'Client calling and SMS settings updated',
        ][$this->action] ?? ucfirst(str_replace(['.', '_'], ' ', $this->action ?? 'Activity recorded'));
    }
}
