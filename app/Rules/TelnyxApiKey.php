<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class TelnyxApiKey implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Check pasted token syntax only. Telnyx still validates authenticity.
        if (!is_string($value) || !preg_match('~\A[A-Za-z0-9._+/=\x7e-]+\z~', $value)
            || preg_match('/\Abearer\b/i', $value) || str_contains($value, '...')) {
            $fail('Paste the complete Telnyx API v2 secret key only, without Bearer, quotes, spaces, or masked characters.');
        }
    }
}
