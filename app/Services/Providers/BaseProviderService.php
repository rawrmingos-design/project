<?php

namespace App\Services\Providers;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class BaseProviderService
{
    protected string $baseUrl;
    protected array $headers;
    protected array $credentials;
    protected int $timeout = 30;

    public function __construct()
    {
        $this->initializeCredentials();
        $this->initializeHeaders();
    }

    /**
     * Initialize provider credentials from config
     */
    abstract protected function initializeCredentials(): void;

    /**
     * Initialize HTTP headers
     */
    abstract protected function initializeHeaders(): void;

    /**
     * Get provider name
     */
    abstract public function getProviderName(): string;

    /**
     * Get products from provider
     */
    abstract public function getProducts(): array;

    /**
     * Get product details by ID
     */
    abstract public function getProduct(string $productId): ?array;

    /**
     * Check product availability
     */
    abstract public function checkAvailability(string $productId): bool;

    /**
     * Get current product price
     */
    abstract public function getPrice(string $productId): ?float;

    /**
     * Process order/transaction
     */
    abstract public function processOrder(array $orderData): array;

    /**
     * Check order status
     */
    abstract public function checkOrderStatus(string $orderId): array;

    /**
     * Make HTTP request to provider API
     */
    protected function makeRequest(string $method, string $endpoint, array $data = []): Response
    {
        $url = $this->baseUrl . $endpoint;
        
        Log::debug("API Request to {$this->getProviderName()}", [
            'method' => $method,
            'url' => $url,
            'data' => $data
        ]);

        $response = Http::withHeaders($this->headers)
            ->timeout($this->timeout)
            ->{strtolower($method)}($url, $data);

        Log::debug("API Response from {$this->getProviderName()}", [
            'status' => $response->status(),
            'response' => $response->json()
        ]);

        return $response;
    }

    /**
     * Handle API errors
     */
    protected function handleError(Response $response): array
    {
        $error = [
            'success' => false,
            'message' => 'API request failed',
            'status_code' => $response->status(),
            'response' => $response->json()
        ];

        Log::error("API Error from {$this->getProviderName()}", $error);

        return $error;
    }

    /**
     * Format product data to standard format
     */
    protected function formatProductData(array $rawData): array
    {
        return [
            'provider_id' => $rawData['id'] ?? null,
            'name' => $rawData['name'] ?? null,
            'category' => $rawData['category'] ?? null,
            'price' => $rawData['price'] ?? null,
            'status' => $rawData['status'] ?? 'active',
            'description' => $rawData['description'] ?? null,
            'provider' => $this->getProviderName(),
            'raw_data' => $rawData
        ];
    }

    /**
     * Get provider configuration
     */
    protected function getConfig(string $key, $default = null)
    {
        return config("providers.{$this->getProviderName()}.{$key}", $default);
    }
}
