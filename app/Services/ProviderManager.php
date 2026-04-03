<?php

namespace App\Services;

use App\Services\Providers\DigiflazzService;
use App\Services\Providers\BangJeffService;
use App\Services\Providers\BaseProviderService;
use Illuminate\Support\Facades\Log;

class ProviderManager
{
    private array $providers = [];

    public function __construct()
    {
        $this->initializeProviders();
    }

    /**
     * Initialize all available providers
     */
    private function initializeProviders(): void
    {
        $this->providers = [
            'digiflazz' => new DigiflazzService(),
            'bangjeff' => new BangJeffService(),
            // Add more providers here as needed
        ];
    }

    /**
     * Get provider instance by name
     */
    public function getProvider(string $providerName): ?BaseProviderService
    {
        return $this->providers[$providerName] ?? null;
    }

    /**
     * Get all available providers
     */
    public function getAllProviders(): array
    {
        return $this->providers;
    }

    /**
     * Get provider names
     */
    public function getProviderNames(): array
    {
        return array_keys($this->providers);
    }

    /**
     * Sync products from all providers
     */
    public function syncAllProducts(): array
    {
        $results = [];

        foreach ($this->providers as $providerName => $provider) {
            try {
                Log::debug("Starting product sync for provider: {$providerName}");
                
                $products = $provider->getProducts();
                $results[$providerName] = [
                    'success' => true,
                    'products_count' => count($products),
                    'products' => $products
                ];

                Log::debug("Completed product sync for {$providerName}", [
                    'products_count' => count($products)
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to sync products for {$providerName}: " . $e->getMessage());
                
                $results[$providerName] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'products_count' => 0
                ];
            }
        }

        return $results;
    }

    /**
     * Sync products from specific provider
     */
    public function syncProviderProducts(string $providerName): array
    {
        $provider = $this->getProvider($providerName);
        
        if (!$provider) {
            return [
                'success' => false,
                'message' => "Provider {$providerName} not found"
            ];
        }

        try {
            Log::debug("Starting product sync for provider: {$providerName}");
            
            $products = $provider->getProducts();
            
            Log::debug("Completed product sync for {$providerName}", [
                'products_count' => count($products)
            ]);

            return [
                'success' => true,
                'provider' => $providerName,
                'products_count' => count($products),
                'products' => $products
            ];
        } catch (\Exception $e) {
            Log::error("Failed to sync products for {$providerName}: " . $e->getMessage());
            
            return [
                'success' => false,
                'provider' => $providerName,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Process order through specific provider
     */
    public function processOrder(string $providerName, array $orderData): array
    {
        $provider = $this->getProvider($providerName);
        
        if (!$provider) {
            return [
                'success' => false,
                'message' => "Provider {$providerName} not found"
            ];
        }

        try {
            Log::debug("Processing order through {$providerName}", [
                'order_keys' => array_keys($orderData),
                'order_id' => $orderData['order_id'] ?? null,
                'product_id' => $orderData['product_id'] ?? null,
            ]);
            
            $result = $provider->processOrder($orderData);
            
            Log::debug("Order processed through {$providerName}", [
                'success' => $result['success'] ?? false,
                'transaction_id' => $result['transaction_id'] ?? null
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::error("Failed to process order through {$providerName}: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Check order status through specific provider
     */
    public function checkOrderStatus(string $providerName, string $orderId): array
    {
        $provider = $this->getProvider($providerName);
        
        if (!$provider) {
            return [
                'success' => false,
                'message' => "Provider {$providerName} not found"
            ];
        }

        try {
            return $provider->checkOrderStatus($orderId);
        } catch (\Exception $e) {
            Log::error("Failed to check order status through {$providerName}: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get best price for a product across all providers
     */
    public function getBestPrice(string $productName): array
    {
        $prices = [];

        foreach ($this->providers as $providerName => $provider) {
            try {
                $products = $provider->getProducts();
                
                foreach ($products as $product) {
                    if (stripos($product['name'], $productName) !== false) {
                        $prices[] = [
                            'provider' => $providerName,
                            'product_id' => $product['provider_id'],
                            'name' => $product['name'],
                            'price' => $product['price'],
                            'status' => $product['status']
                        ];
                    }
                }
            } catch (\Exception $e) {
                Log::error("Error getting prices from {$providerName}: " . $e->getMessage());
            }
        }

        // Sort by price
        usort($prices, function($a, $b) {
            return $a['price'] <=> $b['price'];
        });

        return $prices;
    }
}
