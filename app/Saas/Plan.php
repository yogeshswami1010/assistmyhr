<?php

namespace App\Saas;

class Plan extends LandlordModel
{
    protected $table = 'saas_plans';
    protected $casts = ['enabled' => 'boolean', 'public' => 'boolean'];
}
