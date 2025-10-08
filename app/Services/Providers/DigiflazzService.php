<?php

namespace App\Services\Providers;

use Illuminate\Support\Facades\Log;

class DigiflazzService extends BaseProviderService
{
    protected function initializeCredentials(): void
    {
        $this->baseUrl = config('providers.digiflazz.base_url', 'https://api.digiflazz.com/v1/');
        $this->credentials = [
            'username' => config('providers.digiflazz.username'),
            'api_key' => config('providers.digiflazz.api_key'),
        ];
    }

    protected function initializeHeaders(): void
    {
        $this->headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    public function getProviderName(): string
    {
        return 'digiflazz';
    }

    public function getProducts(): array
    {
        try {
            $response = $this->makeRequest('POST', 'price-list', [
                'cmd' => 'prepaid',
                'username' => $this->credentials['username'],
                'sign' => $this->generateSignature('pricelist')
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['result'] ?? false) {
                    return array_map([$this, 'formatDigiflazzProduct'], $data['data'] ?? []);
                }
            }

            return $this->handleError($response);
        } catch (\Exception $e) {
            Log::error('Digiflazz getProducts error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getProduct(string $productId): ?array
    {
        $products = $this->getProducts();
        
        if (!$products['success'] ?? true) {
            return null;
        }

        foreach ($products as $product) {
            if ($product['provider_id'] === $productId) {
                return $product;
            }
        }

        return null;
    }

    public function checkAvailability(string $productId): bool
    {
        $product = $this->getProduct($productId);
        return $product && ($product['status'] ?? '') === 'available';
    }

    public function getPrice(string $productId): ?float
    {
        $product = $this->getProduct($productId);
        return $product['price'] ?? null;
    }

    public function processOrder(array $orderData): array
    {
        try {
            $response = $this->makeRequest('POST', 'transaction', [
                'username' => $this->credentials['username'],
                'buyer_sku_code' => $orderData['product_id'],
                'customer_no' => $orderData['customer_id'],
                'ref_id' => $orderData['order_id'],
                'sign' => $this->generateSignature($orderData['order_id'])
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                return [
                    'success' => true,
                    'transaction_id' => $data['data']['trx_id'] ?? null,
                    'status' => $this->mapStatus($data['data']['status'] ?? ''),
                    'message' => $data['data']['message'] ?? 'Transaction processed',
                    'raw_response' => $data
                ];
            }

            return $this->handleError($response);
        } catch (\Exception $e) {
            Log::error('Digiflazz processOrder error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function checkOrderStatus(string $orderId): array
    {
        try {
            $response = $this->makeRequest('POST', 'transaction', [
                'username' => $this->credentials['username'],
                'buyer_sku_code' => 'status',
                'customer_no' => $orderId,
                'ref_id' => $orderId,
                'sign' => $this->generateSignature($orderId)
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                return [
                    'success' => true,
                    'status' => $this->mapStatus($data['data']['status'] ?? ''),
                    'message' => $data['data']['message'] ?? '',
                    'sn' => $data['data']['sn'] ?? null,
                    'raw_response' => $data
                ];
            }

            return $this->handleError($response);
        } catch (\Exception $e) {
            Log::error('Digiflazz checkOrderStatus error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Generate signature for Digiflazz API
     */
    private function generateSignature(string $refId): string
    {
        return md5($this->credentials['username'] . $this->credentials['api_key'] . $refId);
    }

    /**
     * Format Digiflazz product to standard format
     */
    private function formatDigiflazzProduct(array $product): array
    {
        return [
            'provider_id' => $product['buyer_sku_code'] ?? null,
            'name' => $product['product_name'] ?? null,
            'category' => $product['category'] ?? null,
            'brand' => $product['brand'] ?? null,
            'price' => $product['price'] ?? null,
            'status' => $product['buyer_product_status'] ? 'available' : 'unavailable',
            'description' => $product['desc'] ?? null,
            'provider' => 'digiflazz',
            'raw_data' => $product
        ];
    }

    /**
     * Map Digiflazz status to standard status
     */
    private function mapStatus(string $status): string
    {
        return match(strtolower($status)) {
            'sukses' => 'success',
            'pending' => 'pending',
            'gagal' => 'failed',
            'proses' => 'processing',
            default => 'unknown'
        };
    }
}
