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
        $this->app->singleton(ProviderManager::class, function ($app) {
            return new ProviderManager();
        });
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
        });
    }
}
