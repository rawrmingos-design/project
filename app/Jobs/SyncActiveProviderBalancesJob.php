<?php

namespace App\Jobs;

use App\Models\Provider;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncActiveProviderBalancesJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $uniqueFor = 55;

    public function uniqueId(): string
    {
        return 'sync-active-provider-balances';
    }

    public function handle(): void
    {
        Provider::query()
            ->where('is_active', true)
            ->whereIn('code', ['digiflazz', 'bangjeff', 'vip', 'vip_reseller', 'apigames'])
            ->select(['id'])
            ->orderBy('id')
            ->chunkById(100, function ($providers): void {
                foreach ($providers as $provider) {
                    CheckProviderBalanceJob::dispatch($provider->id);
                }
            });
    }
}
