<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FooterSetting extends Model
{
    public function getDescriptionAttribute($value) { return tenant_html($value); }
    public function getExternalUrlAttribute($value) { return tenant_external_url($value); }
}
