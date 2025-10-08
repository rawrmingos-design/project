<?php

namespace App\Services\Providers;

use Illuminate\Support\Facades\Log;

class BangJeffService extends BaseProviderService
{
    protected function initializeCredentials(): void
    {
        $this->baseUrl = config('providers.bangjeff.base_url', 'https://bangjeff.com/api/');
        $this->credentials = [
            'api_id' => config('providers.bangjeff.api_id'),
            'api_key' => config('providers.bangjeff.api_key'),
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
        return 'bangjeff';
    }

    public function getProducts(): array
    {
        try {
            $response = $this->makeRequest('POST', 'services', [
                'api_id' => $this->credentials['api_id'],
                'api_key' => $this->credentials['api_key']
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['status'] ?? false) {
                    return array_map([$this, 'formatBangJeffProduct'], $data['data'] ?? []);
                }
            }

            return $this->handleError($response);
        } catch (\Exception $e) {
            Log::error('BangJeff getProducts error: ' . $e->getMessage());
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
            $response = $this->makeRequest('POST', 'order', [
                'api_id' => $this->credentials['api_id'],
                'api_key' => $this->credentials['api_key'],
                'service' => $orderData['product_id'],
                'data' => $orderData['customer_id'],
                'order_id' => $orderData['order_id']
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                return [
                    'success' => $data['status'] ?? false,
                    'transaction_id' => $data['data']['id'] ?? null,
                    'status' => $this->mapStatus($data['data']['status'] ?? ''),
                    'message' => $data['data']['message'] ?? 'Transaction processed',
                    'raw_response' => $data
                ];
            }

            return $this->handleError($response);
        } catch (\Exception $e) {
            Log::error('BangJeff processOrder error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function checkOrderStatus(string $orderId): array
    {
        try {
            $response = $this->makeRequest('POST', 'status', [
                'api_id' => $this->credentials['api_id'],
                'api_key' => $this->credentials['api_key'],
                'id' => $orderId
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                return [
                    'success' => $data['status'] ?? false,
                    'status' => $this->mapStatus($data['data']['status'] ?? ''),
                    'message' => $data['data']['message'] ?? '',
                    'sn' => $data['data']['sn'] ?? null,
                    'raw_response' => $data
                ];
            }

            return $this->handleError($response);
        } catch (\Exception $e) {
            Log::error('BangJeff checkOrderStatus error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Format BangJeff product to standard format
     */
    private function formatBangJeffProduct(array $product): array
    {
        return [
            'provider_id' => $product['id'] ?? null,
            'name' => $product['name'] ?? null,
            'category' => $product['category'] ?? null,
            'brand' => $product['brand'] ?? null,
            'price' => $product['price'] ?? null,
            'status' => ($product['status'] ?? 'available') === 'available' ? 'available' : 'unavailable',
            'description' => $product['note'] ?? null,
            'provider' => 'bangjeff',
            'raw_data' => $product
        ];
    }

    /**
     * Map BangJeff status to standard status
     */
    private function mapStatus(string $status): string
    {
        return match(strtolower($status)) {
            'success', 'sukses' => 'success',
            'pending', 'process' => 'pending',
            'error', 'gagal' => 'failed',
            'processing' => 'processing',
            default => 'unknown'
        };
    }
}
