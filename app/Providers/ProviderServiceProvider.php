<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\ProviderManager;
use Illuminate\Console\Scheduling\Schedule;

class ProviderServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(ProviderManager::class, fn (): ProviderManager => new ProviderManager());
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Schedule automated tasks
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            
            // Auto sync products every hour if enabled
            if (config('providers.sync.auto_sync_enabled')) {
                $schedule->command('provider:sync-products --update-existing')
                    ->hourly()
                    ->withoutOverlapping()
                    ->runInBackground();
            }
            
            // Auto update prices every 30 minutes if enabled
            if (config('providers.pricing.auto_update_prices')) {
                $schedule->job(\App\Jobs\UpdateProviderPrices::class, 'digiflazz')
                    ->everyThirtyMinutes()
                    ->withoutOverlapping();
                    
                $schedule->job(\App\Jobs\UpdateProviderPrices::class, 'bangjeff')
                    ->everyThirtyMinutes()
                    ->withoutOverlapping();
            }

            // Poll paid, non-final Digiflazz orders when a callback is delayed or missed.
            if (config('providers.digiflazz.status_polling_enabled', true)) {
                $schedule->job(new \App\Jobs\SyncProviderOrderStatusesJob('digiflazz'), 'default')
                    ->everyMinute()
                    ->withoutOverlapping();
            }

            // Auto refresh provider balances (near real-time dashboard)
            if (config('providers.balance.auto_refresh', true)) {
                $schedule->job(\App\Jobs\SyncActiveProviderBalancesJob::class, 'default')
                    ->everyMinute()
                    ->withoutOverlapping();
            }

            $schedule->command('payments:expire-pending --batch=100')
                ->everyMinute()
                ->withoutOverlapping()
                ->runInBackground();
        });
    }
}
