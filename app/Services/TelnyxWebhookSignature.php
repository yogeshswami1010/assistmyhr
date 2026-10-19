<?php

namespace App\Services;

use Illuminate\Http\Request;

class TelnyxWebhookSignature
{
    public static function verify(Request $request, string $configuredKey): bool
    {
        if (!function_exists('sodium_crypto_sign_verify_detached')) return false;

        $signature = base64_decode((string) $request->header('telnyx-signature-ed25519'), true);
        $timestamp = (string) $request->header('telnyx-timestamp');
        if (!is_string($signature) || strlen($signature) !== SODIUM_CRYPTO_SIGN_BYTES
            || !ctype_digit($timestamp) || abs(time() - (int) $timestamp) > 300) return false;

        $configuredKey = trim($configuredKey);
        $key = ctype_xdigit($configuredKey) && strlen($configuredKey) === 64
            ? hex2bin($configuredKey) : base64_decode($configuredKey, true);

        return is_string($key) && strlen($key) === SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES
            && sodium_crypto_sign_verify_detached($signature, $timestamp.'|'.$request->getContent(), $key);
    }
}
