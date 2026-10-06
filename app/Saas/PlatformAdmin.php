<?php

namespace App\Saas;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class PlatformAdmin extends Authenticatable
{
    use Notifiable;
    protected $connection = 'saas_landlord';
    protected $table = 'saas_platform_admins';
    protected $guarded = ['id'];
    protected $hidden = ['password', 'remember_token'];
}
