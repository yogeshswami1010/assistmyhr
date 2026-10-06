<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\ExpiredJob::class,
        Commands\EndDateStatus::class,
        Commands\PurgeCandidates::class,
        Commands\ImportCandidateEmailReplies::class,
        Commands\NotifyCandidateClientReviews::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        if (config('saas.enabled')) {
            $schedule->command('saas:maintenance minute')->everyMinute()->withoutOverlapping();
            $schedule->command('saas:maintenance daily')->dailyAt('02:00')->withoutOverlapping();
            return;
        }
        $schedule->command('candidate-emails:import-replies')->everyMinute()->withoutOverlapping();
        $schedule->command('client-reviews:notify')->everyMinute()->withoutOverlapping();
        // Moved to routes/console.php
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
