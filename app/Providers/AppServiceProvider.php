<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (env('REDIRECT_HTTPS')) {
            \URL::forceScheme('https');
        }
        Schema::defaultStringLength(191);

        \Illuminate\Filesystem\AwsS3V3Adapter::macro('getClient', fn() => $this->client);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Keep the database migration check without vendor purchase or updater routes.
        $this->commands([\Froiden\Envato\Commands\MigrateCheckCommand::class]);
    }
}
