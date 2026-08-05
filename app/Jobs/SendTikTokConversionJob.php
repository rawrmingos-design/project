<?php

namespace App\Jobs;

use App\Services\TikTokDeliveryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SendTikTokConversionJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 20;

    /** @var array<int, int> */
    public array $backoff = [60, 300];

    /**
     * Create a new job instance.
     */
    public function __construct(public int $deliveryId)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(TikTokDeliveryService $service): void
    {
        $service->executeDelivery($this->deliveryId);
    }

    public function failed(?Throwable $exception): void
    {
        app(TikTokDeliveryService::class)->markPermanentlyFailed(
            $this->deliveryId,
            $exception,
        );
    }
}
