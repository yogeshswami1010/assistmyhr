<?php

namespace App\Services;

class SmtpConfiguration
{
    public static function encryption(?string $value, int $port): ?string
    {
        // Port 465 requires implicit TLS, including old rows saved as None.
        if ($port === 465) { return 'ssl'; }
        $value = strtolower(trim((string) $value));
        return in_array($value, ['ssl', 'tls'], true) ? $value : null;
    }

    public static function inboxHost(?string $smtpHost): ?string
    {
        $host = strtolower(trim((string) $smtpHost));
        if ($host === 'smtp.gmail.com') { return 'imap.gmail.com'; }
        if (preg_match('/\A(smtppro|smtp)\.zoho\.(com|in|eu|com.au|jp|ca|sa)\z/', $host, $matches)) {
            return ($matches[1] === 'smtppro' ? 'imappro' : 'imap').'.zoho.'.$matches[2];
        }
        return null;
    }

    public static function transport($settings): array
    {
        $port = (int) ($settings?->mail_port ?: 587);
        $encryption = self::encryption(($settings?->mail_encryption ?? null), $port);
        return [
            'transport' => 'smtp', 'host' => ($settings?->mail_host ?? null), 'port' => $port,
            'encryption' => $encryption, 'scheme' => $encryption === 'ssl' ? 'smtps' : 'smtp',
            'auto_tls' => $encryption !== null, 'require_tls' => $encryption !== null,
            'username' => ($settings?->mail_username ?? null), 'password' => ($settings?->mail_password ?? null),
            'timeout' => 20,
            'from' => ['address' => ($settings?->mail_from_email ?? null), 'name' => ($settings?->mail_from_name ?? null)],
        ];
    }
}
