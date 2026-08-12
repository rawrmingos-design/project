<?php

namespace App\Jobs;

use App\Models\ResellerCallbackDelivery;
use App\Services\ResellerCallbackDeliveryService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class DeliverResellerWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 5;

    /**
     * The delivery record being processed.
     *
     * @var \App\Models\ResellerCallbackDelivery
     */
    protected $delivery;

    /**
     * Create a new job instance.
     */
    public function __construct(ResellerCallbackDelivery $delivery)
    {
        $this->delivery = $delivery;
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [
            60,       // 1 Minute
            300,      // 5 Minutes
            900,      // 15 Minutes
            3600,     // 1 Hour
        ];
    }

    /**
     * Execute the job.
     */
    public function handle(ResellerCallbackDeliveryService $service): void
    {
        $this->delivery->refresh();

        if ($this->delivery->status === 'delivered') {
            return; // Already delivered, skip.
        }

        // Increment attempt_count explicitly for DB visibility
        $this->delivery->increment('attempt_count');
        $this->delivery->last_attempted_at = now();
        $this->delivery->save();

        // Call the service to redeliver
        $result = $service->redeliver($this->delivery);

        if ($result['status'] === 'failed') {
            throw new Exception('Webhook delivery failed: callback delivery did not succeed.');
        }
    }

    /**
     * Handle a job failure after all retries are exhausted.
     */
    public function failed(?Throwable $exception): void
    {
        unset($exception);

        Log::error('Reseller webhook delivery permanently failed after all retries.', [
            'delivery_id' => $this->delivery->getKey(),
            'order_id'    => $this->delivery->order_id,
            'category'    => 'delivery_failed',
        ]);
    }
}
