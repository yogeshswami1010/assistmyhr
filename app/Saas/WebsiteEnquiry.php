<?php

namespace App\Saas;

class WebsiteEnquiry extends LandlordModel
{
    protected $table = 'saas_website_enquiries';
    protected $casts = ['read_at' => 'datetime', 'notified_at' => 'datetime'];

    public const TOPICS = ['Live demo', 'Pricing', 'AI CV Search', 'Temp Staffing', 'Something else'];

    public function getKindLabelAttribute(): string
    {
        return $this->kind === 'demo' ? 'Demo request' : 'Contact enquiry';
    }
}
