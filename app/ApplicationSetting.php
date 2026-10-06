<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ApplicationSetting extends Model
{
    public function getLegalTermAttribute($value) { return tenant_html($value); }
    protected $casts = [
        'mail_setting' => 'array'
    ];
}
