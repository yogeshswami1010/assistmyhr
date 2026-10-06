<?php

namespace App\Saas;

class PlatformSetting extends LandlordModel
{
    protected $table = 'saas_platform_settings';

    public static function valueFor(string $key, $default = null)
    {
        return static::where('key', $key)->value('value') ?? $default;
    }
}
