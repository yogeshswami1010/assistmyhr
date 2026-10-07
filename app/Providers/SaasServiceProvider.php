<?php

namespace App\Providers;

use App\JobApiIntegration;
use App\Saas\ApiKey;
use App\Saas\QuotaService;
use App\Saas\TenantContext;
use Illuminate\Support\ServiceProvider;

class SaasServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        config(['database.connections.saas_landlord' => config('database.connections.'.config('database.default'))]);
        $this->app->singleton(TenantContext::class);
        if (config('saas.enabled')) {
            config(['mail.mailers.platform' => [
                'transport' => config('mail.driver', 'smtp'),
                'host' => config('mail.host'), 'port' => config('mail.port'),
                'encryption' => config('mail.encryption'),
                'scheme' => config('mail.encryption') === 'ssl' ? 'smtps' : 'smtp',
                'username' => config('mail.username'), 'password' => config('mail.password'),
                'from' => config('mail.from'),
            ]]);
            config(['session.connection' => 'saas_landlord', 'queue.default' => 'sync', 'cache.default' => 'file']);
        }
    }

    public function boot(): void
    {
        if (!config('saas.enabled')) { return; }
        \Illuminate\Auth\Notifications\VerifyEmail::createUrlUsing(fn ($user) => \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'verification.verify', now()->addMinutes(60), tenant_parameters(['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())])
        ));
        \Illuminate\Auth\Notifications\VerifyEmail::toMailUsing(fn ($user, $url) => (new \Illuminate\Notifications\Messages\MailMessage)
            ->mailer('platform')
            ->from(config('mail.mailers.platform.from.address'), config('mail.mailers.platform.from.name'))
            ->subject('Verify your email address')
            ->line('Please verify your email address to start using your ATS.')
            ->action('Verify email address', $url)
            ->line('If you did not create an account, no action is required.'));
        \Illuminate\Auth\Notifications\ResetPassword::createUrlUsing(fn ($user, $token) => tenant_route('password.reset', ['token' => $token, 'email' => $user->getEmailForPasswordReset()]));
        foreach ([\App\User::class => 'users', \App\Job::class => 'jobs', \App\JobApplication::class => 'job_applications'] as $model => $table) {
            $model::creating(fn () => app(QuotaService::class)->assertCanCreate($table));
        }
        JobApiIntegration::saved(function ($integration) {
            $tenant = app(TenantContext::class)->current();
            if ($tenant) {
                ApiKey::updateOrCreate(['tenant_id' => $tenant->id, 'integration_id' => $integration->id], ['token_hash' => $integration->token_hash]);
            }
        });
        JobApiIntegration::deleted(function ($integration) {
            $tenant = app(TenantContext::class)->current();
            if ($tenant) { ApiKey::where('tenant_id', $tenant->id)->where('integration_id', $integration->id)->delete(); }
        });
    }
}
