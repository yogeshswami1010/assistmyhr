<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    public function getWebsiteAttribute($value) { return tenant_external_url($value); }
    protected $fillable = [
        'company_name',
        'company_email',
        'company_phone',
        'website',
        'address',
        'timezone',
        'locale',
        'latitude',
        'longitude',
        'logo',
        'system_update',
        'front_language',
        'job_alert_status',
        'currency_id'
    ];

    protected $casts = [
        'candidate_calls_enabled' => 'boolean',
        'supported_until' => 'datetime',
        'last_license_verified_at' => 'datetime',
    ];

    protected $appends = ['logo_url'];

    public function getLogoUrlAttribute()
    {
        if (empty($this->logo)) {
            return asset('assishrlogo.webp');
        }
        return asset_url('app-logo/' . $this->logo);
    }

    public function getFaviconUrlAttribute()
    {
        if (empty($this->favicon)) {
            return asset('favicon/assistmyhr.svg');
        }

        return asset_url('favicon/' . $this->favicon);
    }
}
