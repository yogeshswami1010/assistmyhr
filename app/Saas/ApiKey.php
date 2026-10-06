<?php

namespace App\Saas;

class ApiKey extends LandlordModel
{
    protected $table = 'saas_api_keys';
    protected $hidden = ['token_hash'];
    public function tenant() { return $this->belongsTo(Tenant::class); }
}
