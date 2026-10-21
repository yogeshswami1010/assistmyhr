<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CandidateReplyMailbox extends Model
{
    public $incrementing = false;
    protected $guarded = [];
    protected $hidden = ['access_token', 'refresh_token', 'sync_cursor'];
    protected $casts = [
        'access_token' => 'encrypted', 'refresh_token' => 'encrypted',
        'sync_cursor' => 'encrypted', 'token_expires_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];
}
