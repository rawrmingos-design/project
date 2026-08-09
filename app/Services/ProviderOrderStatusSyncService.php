<?php

namespace App\Services;

use App\Libraries\Provider\ElitediasProvider;
use App\Libraries\Provider\GameShopProvider;
use App\Libraries\Provider\StrleyaShopProvider;
use App\Libraries\Provider\YezzpayProvider;
use App\Models\Pembelian;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class ProviderOrderStatusSyncService
{
    public function sync(string $provider): array
    {
        $provider = strtolower(trim($provider));
        $client = $this->providerClient($provider);
        $updated = 0;
        $failed = 0;

        Pembelian::query()
            ->where('status', 'Proses')
            ->where('provider', $provider)
            ->orderBy('id')
            ->chunkById(100, function ($orders) use ($client, $provider, &$updated, &$failed): void {
                foreach ($orders as $order) {
                    $response = $client->status($order->provider_order_id);
                    $status = $this->normalizedStatus($provider, $response);

                    if ($status === null) {
                        $failed++;
                        Log::warning('Provider status sync returned an invalid response.', [
                            'provider' => $provider,
                            'pembelian_id' => $order->id,
                            'response_type' => get_debug_type($response),
                        ]);

                        continue;
                    }

                    $order->update([
                        'status' => $status,
                        'log' => json_encode([
                            'provider' => $provider,
                            'order_id' => $order->provider_order_id,
                            'status' => $status,
                            'response' => $response,
                        ], JSON_PRETTY_PRINT),
                    ]);
                    $updated++;
                }
            });

        return ['updated' => $updated, 'failed' => $failed];
    }

    private function providerClient(string $provider): object
    {
        return match ($provider) {
            'gameshop' => new GameShopProvider,
            'strleyashop' => new StrleyaShopProvider,
            'elitedias' => new ElitediasProvider,
            'yezzpay' => new YezzpayProvider,
            default => throw new InvalidArgumentException('Unsupported provider status sync: ' . $provider),
        };
    }

    private function normalizedStatus(string $provider, mixed $response): ?string
    {
        if (! is_array($response)) {
            return null;
        }

        $rawStatus = match ($provider) {
            'gameshop' => $response['data']['status'] ?? null,
            'yezzpay' => $response['data']['order_status'] ?? null,
            default => $response['order_status'] ?? null,
        };
        $status = strtolower(trim((string) $rawStatus));

        return match ($provider) {
            'gameshop' => match ($status) {
                '5' => 'Sukses',
                '6', '0' => 'Gagal',
                '1', '2', '3', '4' => 'Proses',
                default => null,
            },
            'strleyashop' => match ($status) {
                'successful' => 'Sukses',
                'error' => 'Gagal',
                'pending' => 'Proses',
                default => null,
            },
            'elitedias' => match ($status) {
                'successful', 'success' => 'Sukses',
                'failed', 'error', 'cancelled' => 'Gagal',
                'pending', 'processing' => 'Proses',
                default => null,
            },
            'yezzpay' => match ($status) {
                'success' => 'Sukses',
                'failed' => 'Gagal',
                'pending', 'processing' => 'Proses',
                default => null,
            },
            default => null,
        };
    }
}
