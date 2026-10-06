<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class JobApiIntegration extends Model
{
    protected $hidden = ['token_hash'];

    protected $casts = ['enabled' => 'boolean'];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function regenerateToken(): string
    {
        if ($this->feed_scope === null && $this->company_id !== null) {
            $this->feed_scope = 'company:'.$this->company_id;
        }
        $token = 'jobs_'.bin2hex(random_bytes(32));
        $this->token_hash = hash('sha256', $token);
        $this->token_suffix = substr($token, -8);
        $this->save();

        return $token;
    }
}
