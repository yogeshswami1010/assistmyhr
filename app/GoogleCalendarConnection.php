<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GoogleCalendarConnection extends Model
{
    protected $guarded = ['id'];
    protected $hidden = ['access_token', 'refresh_token', 'google_subject'];
    protected $casts = [
        'access_token' => 'encrypted', 'refresh_token' => 'encrypted',
        'token_expires_at' => 'datetime',
    ];
}
