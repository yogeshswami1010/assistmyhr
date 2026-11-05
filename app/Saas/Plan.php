<?php

namespace App\Saas;

class Plan extends LandlordModel
{
    protected $table = 'saas_plans';
    protected $casts = ['enabled' => 'boolean', 'public' => 'boolean', 'duration_days' => 'integer'];

    public function durationDays(): int
    {
        return $this->duration_days ?? 30;
    }
}
