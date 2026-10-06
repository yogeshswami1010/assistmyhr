<?php

namespace App\Saas;

use Illuminate\Database\Eloquent\Model;

abstract class LandlordModel extends Model
{
    protected $connection = 'saas_landlord';
    protected $guarded = ['id'];
}
