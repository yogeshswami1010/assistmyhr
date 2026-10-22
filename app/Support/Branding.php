<?php

namespace App\Support;

class Branding
{
    public static function displayName(?string $name): ?string
    {
        if ($name !== null && preg_match('/\Aconsortium\s+staffing(?:\s+solutions?)?\z/i', trim($name))) {
            return 'AssistMyHR';
        }

        return $name;
    }
}
