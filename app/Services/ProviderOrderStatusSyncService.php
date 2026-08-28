<?php

namespace App\Services;

use App\Http\Controllers\DigiFlazzController;
use App\Libraries\Provider\ElitediasProvider;
use App\Libraries\Provider\GameShopProvider;
use App\Libraries\Provider\StrleyaShopProvider;
use App\Libraries\Provider\YezzpayProvider;
use App\Models\Pembelian;
use App\Services\ProviderStatusUpdateService;
use App\Support\PembelianStatus;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class ProviderOrderStatusSyncService
{
    public function sync(string $provider): array
    {
        $provider = strtolower(trim($provider));
        $statusUpdater = app(ProviderStatusUpdateService::class);
        $client = $provider === 'digiflazz' ? null : $this->providerClient($provider);
        $updated = 0;
        $failed = 0;

        Pembelian::query()
            ->with(['activeLayanan', 'pembayaran'])
            ->whereIn('status', PembelianStatus::pendingLabels())
            ->where('active_provider_code', $provider)
            ->whereNotNull('provider_order_id')
            ->whereHas('pembayaran', fn ($query) => $query->whereIn('status', ['Lunas', 'PAID', 'Paid', 'Success']))
            ->orderBy('id')
            ->chunkById(100, function ($orders) use ($client, $provider, $statusUpdater, &$updated, &$failed): void {
                foreach ($orders as $order) {
                    $response = $provider === 'digiflazz'
                        ? (new DigiFlazzController())->status(
                            (string) $order->provider_order_id,
                            (string) $order->active_provider_sku,
                            (string) $order->user_id,
                            (string) $order->zone,
                        )
                        : $client->status($order->provider_order_id);
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

                    $statusUpdater->apply($order, [
                        'success' => true,
                        'order_status' => $status,
                        'transaction_id' => $order->provider_order_id,
                        'provider_status' => $status,
                        'message' => data_get($response, 'data.message', ''),
                        'sn' => data_get($response, 'data.sn', ''),
                        'raw' => $response,
                    ], 'provider_status_polling');
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
            'digiflazz' => $response['data']['status'] ?? null,
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
            'digiflazz' => match ($status) {
                'success', 'sukses' => 'Sukses',
                'failed', 'gagal', 'error', 'cancelled', 'canceled' => 'Gagal',
                'pending', 'proses', 'processing' => 'Proses',
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
