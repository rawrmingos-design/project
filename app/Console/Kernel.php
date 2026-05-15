<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
     
   
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        
        //  $schedule->command('moogold:cek-status')->everyMinute();
        
        //  $schedule->command('Service')->everyMinute();
        $schedule->job(new \App\Jobs\DigiflazzSyncJob)->hourly();

        if (config('h2h.schedule.callback_healthcheck_enabled', true)) {
            $schedule->command('h2h:callback-healthcheck --only-enabled')
                ->cron((string) config('h2h.schedule.callback_healthcheck_cron', '*/15 * * * *'))
                ->withoutOverlapping()
                ->runInBackground();
        }

        if (config('h2h.schedule.credential_healthcheck_enabled', true)) {
            $schedule->command('h2h:credential-healthcheck --only-active')
                ->cron((string) config('h2h.schedule.credential_healthcheck_cron', '17 * * * *'))
                ->withoutOverlapping()
                ->runInBackground();
        }

        $schedule->command('affiliate:audit --warn-hours=24')
            ->hourlyAt(13)
            ->withoutOverlapping()
            ->runInBackground();
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
