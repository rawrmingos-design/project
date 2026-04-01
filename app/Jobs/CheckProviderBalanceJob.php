<?php

namespace App\Jobs;

use App\Models\Provider;
use App\Services\ProviderBalanceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckProviderBalanceJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 2;

    public int $backoff = 20;

    public int $uniqueFor = 30;

    public function __construct(public int $providerId)
    {
    }

    public function uniqueId(): string
    {
        return 'check-provider-balance:' . $this->providerId;
    }

    public function handle(ProviderBalanceService $providerBalanceService): void
    {
        $provider = Provider::query()->find($this->providerId);

        if (! $provider) {
            Log::warning('CheckProviderBalanceJob: provider not found.', [
                'provider_id' => $this->providerId,
            ]);

            return;
        }

        try {
            $result = $providerBalanceService->sync($provider);

            if (!($result['success'] ?? false)) {
                Log::warning('CheckProviderBalanceJob skipped/failed gracefully.', [
                    'provider_id' => $provider->id,
                    'provider_code' => $provider->code,
                    'message' => $result['message'] ?? 'Unknown provider balance issue.',
                ]);
            }
        } catch (\Throwable $exception) {
            Log::error('CheckProviderBalanceJob failed.', [
                'provider_id' => $provider->id,
                'provider_code' => $provider->code,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
