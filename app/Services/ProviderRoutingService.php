<?php

namespace App\Services;

use App\Models\Layanan;
use App\Models\Provider;
use App\Models\SettingWeb;
use App\Support\ProviderRetirement;
use Illuminate\Support\Facades\Log;

class ProviderRoutingService
{
    /**
     * Determine the best provider for a given service (Layanan).
     * logic: 
     * 1. Check 'provider_paths' (Multi-provider).
     * 2. Filter status='available'.
     * 3. Sort by priority (ASC), then modal_price (ASC).
     * 4. If no multi-provider found, fallback to legacy fields.
     */
    public function findBestProvider(Layanan $layanan)
    {
        $paths = $layanan->provider_paths()
            ->where('status', 'available')
            ->orderBy('priority', 'asc')
            ->orderBy('modal_price', 'asc')
            ->get()
            ->filter(fn ($path): bool => $this->isRoutableProvider($path->provider_code));

        if ($paths->isNotEmpty()) {
            $bestPath = $paths->first();

            return $this->formatProviderResult($bestPath->provider_code, $bestPath->provider_sku);
        }

        if (! empty($layanan->provider_id)
            && ! empty($layanan->provider)
            && $this->isRoutableProvider($layanan->provider)) {
            return $this->formatProviderResult($layanan->provider, $layanan->provider_id);
        }

        Log::warning("ProviderRoutingService: No provider found for Service ID {$layanan->id} ({$layanan->layanan}).", [
            'provider_paths_count' => $paths->count(),
            'legacy_provider' => $layanan->provider,
            'legacy_provider_sku' => $layanan->provider_id,
        ]);

        return null;
    }

    public function resolveExplicitProvider(string $providerCode, string $sku): ?array
    {
        if (! $this->isRoutableProvider($providerCode)) {
            Log::warning('ProviderRoutingService: Explicit provider route rejected.', [
                'provider_code' => ProviderRetirement::normalizeCode($providerCode),
            ]);

            return null;
        }

        return $this->formatProviderResult($providerCode, $sku);
    }

    public function isRoutableProvider(string $providerCode): bool
    {
        $code = ProviderRetirement::normalizeCode($providerCode);

        if ($code === '' || ProviderRetirement::isRetired($code)) {
            return false;
        }

        if (ProviderRetirement::isInternal($code)) {
            return true;
        }

        $canonicalCode = ProviderRetirement::canonicalCode($code);
        $provider = Provider::query()
            ->whereRaw('LOWER(code) = ?', [$canonicalCode])
            ->first();

        return $provider === null || $provider->is_active;
    }

    /**
     * Format the result and attach credentials.
     */
    private function formatProviderResult($providerCode, $sku)
    {
        $settings = SettingWeb::query()->first() ?? new SettingWeb();
        $credentials = [];

        // Normalize provider code
        $code = strtolower($providerCode);

        switch ($code) {
            case 'digiflazz':
                $credentials = [
                    'username' => $settings->username_digi,
                    'api_key' => $settings->api_key_digi,
                    'endpoint' => 'https://api.digiflazz.com',
                ];
                break;

            case 'bangjeff':
                $credentials = [
                    'api_key' => $settings->apikey_bangjeff,
                    'endpoint' => rtrim((string) config('providers.bangjeff.base_url', 'https://distribution-api.bangjeff.com'), '/'),
                    'region' => (string) config('providers.bangjeff.region', 'ID'),
                ];
                break;

            case 'vip':
            case 'vip_reseller':
                $credentials = [
                    'api_id' => $settings->vip_apiid,
                    'api_key' => $settings->vip_apikey,
                    'api_sign' => $settings->vip_sign,
                    'endpoint' => 'https://vip-reseller.co.id/api/game-feature',
                ];
                break;
            
            case 'apigames':
                $credentials = [
                    'merchant_id' => $settings->apigames_merchant,
                    'secret_key' => $settings->apigames_secret,
                    'endpoint' => 'https://v1.apigames.id/v2',
                ];
                break;

            case 'sufpayment':
                $credentials = [
                    'api_id' => $settings->sufpayment_api_id,
                    'api_key' => $settings->sufpayment_api_key,
                    'secret_key' => $settings->sufpayment_secret_key,
                    'endpoint' => rtrim((string) config('providers.sufpayment.base_url', 'https://sufpayment.com/api/v1'), '/'),
                    'order_cmd' => (string) config('providers.sufpayment.order_cmd', ''),
                    'status_cmd' => (string) config('providers.sufpayment.status_cmd', ''),
                    'target_separator' => (string) config('providers.sufpayment.target_separator', ''),
                    'timeout' => (int) config('providers.sufpayment.timeout', 15),
                ];
                break;

            case 'manual':
            case 'joki':
            case 'jokigendong':
            case 'vilogml':
            case 'giftskin':
                $credentials = [
                    'type' => 'manual',
                ];
                break;

            default:
                Log::warning("Provider credentials not found for code: {$code}");
                break;
        }

        return [
            'provider_code' => $code,
            'sku' => $sku,
            'credentials' => $credentials,
        ];
    }
}
