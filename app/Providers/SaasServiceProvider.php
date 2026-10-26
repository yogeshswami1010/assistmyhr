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
            config(['auth.guards.web.driver' => 'tenant-session', 'auth.guards.web.remember' => 60 * 24 * 30]);
            config(['mail.mailers.platform' => [
                'transport' => config('mail.driver', 'smtp'),
                'host' => config('mail.host'), 'port' => config('mail.port'),
                'encryption' => config('mail.encryption'),
                'scheme' => config('mail.encryption') === 'ssl' ? 'smtps' : 'smtp',
                'username' => config('mail.username'), 'password' => config('mail.password'),
                'timeout' => 20,
                'from' => config('mail.from'),
            ]]);
            config(['session.connection' => 'saas_landlord', 'queue.default' => 'sync', 'cache.default' => 'file']);
        }
    }

    public function boot(): void
    {
        // Resolve the limiter after tenant selection so existing ATS limits keep
        // their tenant-specific cache; the public enquiry route uses the platform cache.
        $this->app->afterResolving(\Illuminate\Cache\RateLimiter::class, function ($limiter) {
            $limiter->for('website-enquiries', fn ($request) =>
                \Illuminate\Cache\RateLimiting\Limit::perMinutes(10, 3)->by($request->ip())
                    ->response(fn ($request, $headers) => response()->json(['success' => false, 'message' => 'Too many attempts. Please wait a few minutes or email support@assistmyhr.com.'], 429, $headers))
            );
        });
        if (!config('saas.enabled')) { return; }
        $this->app['auth']->extend('tenant-session', function ($app, $name, $config) {
            $guard = new \App\Saas\TenantSessionGuard(
                $name,
                $app['auth']->createUserProvider($config['provider'] ?? null),
                $app['session.store'],
                rehashOnLogin: $app['config']->get('hashing.rehash_on_login', true),
                timeboxDuration: $app['config']->get('auth.timebox_duration', 200000),
                hashKey: $app['config']->get('app.key'),
            );
            $guard->setCookieJar($app['cookie']);
            $guard->setDispatcher($app['events']);
            $guard->setRequest($app->refresh('request', $guard, 'setRequest'));
            $guard->setRememberDuration($config['remember']);

            return $guard;
        });
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
