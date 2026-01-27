<?php

namespace App\Services;

use App\Models\Layanan;
use App\Models\SettingWeb;
use App\Models\ProviderPath;
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
        // 1. Try Multi-Provider Paths
        $paths = $layanan->provider_paths()
            ->where('status', 'available')
            ->orderBy('priority', 'asc') // 1 is highest priority
            ->orderBy('modal_price', 'asc') // Cheaper is better as tie-breaker
            ->get();

        if ($paths->isNotEmpty()) {
            $bestPath = $paths->first();
            return $this->formatProviderResult($bestPath->provider_code, $bestPath->provider_sku);
        }

        // 2. Fallback to Legacy Fields (provider_id column in layanans table stores the code, e.g., 'digiflazz')
        // And provider_nomimal stores the SKU.
        if (!empty($layanan->provider_id) && !empty($layanan->provider_nomimal)) {
            // Check if legacy provider is valid/active? 
            // For now assume available if set.
            return $this->formatProviderResult($layanan->provider_id, $layanan->provider_nomimal);
        }

        // 3. No provider found
        return null;
    }

    /**
     * Format the result and attach credentials.
     */
    private function formatProviderResult($providerCode, $sku)
    {
        $settings = SettingWeb::first();
        $credentials = [];

        // Normalize provider code
        $code = strtolower($providerCode);

        switch ($code) {
            case 'digiflazz':
                $credentials = [
                    'username' => $settings->username_digi,
                    'api_key' => $settings->api_key_digi,
                    'endpoint' => 'https://api.digiflazz.com/v1/transaction',
                ];
                break;

            case 'bangjeff':
                $credentials = [
                    'api_key' => $settings->apikey_bangjeff,
                    'endpoint' => 'https://client.bangjeff.com/api/v2/order', // Verify endpoint later
                ];
                break;

            case 'vip':
            case 'vip_reseller':
                $credentials = [
                    'api_id' => $settings->vip_apiid,
                    'api_key' => $settings->vip_apikey,
                    'endpoint' => 'https://vip-reseller.co.id/api/game-feature',
                ];
                break;
            
            case 'apigames':
                $credentials = [
                    'merchant_id' => $settings->apigames_merchant,
                    'secret_key' => $settings->apigames_secret,
                    'endpoint' => 'https://v1.apigames.id/transaksi',
                ];
                break;
                
            case 'manual':
            case 'joki':
                $credentials = [
                    'type' => 'manual'
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
