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
