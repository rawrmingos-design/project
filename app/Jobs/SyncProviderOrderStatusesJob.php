<?php

namespace App\Jobs;

use App\Services\ProviderOrderStatusSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncProviderOrderStatusesJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $backoff = 60;

    public int $uniqueFor = 300;

    public function __construct(public string $provider)
    {
    }

    public function uniqueId(): string
    {
        return 'provider-order-status-sync:' . strtolower(trim($this->provider));
    }

    public function handle(ProviderOrderStatusSyncService $service): void
    {
        $result = $service->sync($this->provider);

        Log::info('Provider order status sync completed.', [
            'provider' => strtolower(trim($this->provider)),
            'updated' => $result['updated'],
            'failed' => $result['failed'],
        ]);
    }
}
