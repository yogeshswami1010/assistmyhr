<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class PlatformMailCheck extends Command
{
    protected $signature = 'saas:mail-check {--to= : Optional recipient for a real test email}';
    protected $description = 'Check platform verification SMTP configuration without displaying credentials';

    public function handle(): int
    {
        $config = config('mail.mailers.platform', []);
        $this->table(['Setting', 'Value'], [
            ['Transport', $config['transport'] ?? 'Not configured'],
            ['Host', $config['host'] ?? 'Not configured'],
            ['Port', $config['port'] ?? 'Not configured'],
            ['Encryption', $config['scheme'] ?? 'Not configured'],
            ['Username configured', filled($config['username'] ?? null) ? 'Yes' : 'No'],
            ['Password configured', filled($config['password'] ?? null) ? 'Yes' : 'No'],
            ['Sender configured', filled($config['from']['address'] ?? null) ? 'Yes' : 'No'],
        ]);
        if (($config['transport'] ?? null) !== 'smtp' || blank($config['username'] ?? null)
            || blank($config['password'] ?? null) || blank($config['from']['address'] ?? null)) {
            $this->error('Configure platform MAIL_* SMTP settings and refresh the configuration cache.');
            return self::FAILURE;
        }
        if (!$this->option('to')) {
            $this->info('Settings are present. Authentication and delivery have not been tested. Use --to=your@email.com to send a test.');
            return self::SUCCESS;
        }
        if (!filter_var($this->option('to'), FILTER_VALIDATE_EMAIL)) {
            $this->error('Enter a valid recipient email address.');
            return self::FAILURE;
        }
        try {
            Mail::mailer('platform')->raw('AssistMyHR platform SMTP test.', function ($message) use ($config) {
                $message->from($config['from']['address'], $config['from']['name'] ?? 'AssistMyHR')
                    ->to($this->option('to'))->subject('AssistMyHR email delivery test');
            });
        } catch (\Throwable $exception) {
            report($exception);
            $this->error('Email failed to send. Check storage/logs/laravel.log for the SMTP error.');
            return self::FAILURE;
        }
        $this->info('SMTP server accepted the test email. Check the recipient inbox and spam folder.');
        return self::SUCCESS;
    }
}
