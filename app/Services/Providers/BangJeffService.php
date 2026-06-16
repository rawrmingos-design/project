<?php

namespace App\Services\Providers;

use App\Models\SettingWeb;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class BangJeffService extends BaseProviderService
{
    private const BASE_URL_PRODUCTION = 'https://distribution-api.bangjeff.com';
    private const BASE_URL_SANDBOX = 'https://sandbox-api.bangjeff.com';

    private array $overrides = [];
    private string $sandboxBaseUrl = self::BASE_URL_SANDBOX;
    private string $region = 'ID';

    public function __construct(array $overrides = [])
    {
        $this->overrides = $overrides;
        parent::__construct();
    }

    protected function initializeCredentials(): void
    {
        $apiKey = $this->overrides['api_key']
            ?? $this->resolveAdminApiKey()
            ?? $this->overrides['fallback_api_key']
            ?? config('providers.bangjeff.api_key');

        $this->credentials = [
            'api_key' => (string) $apiKey,
        ];

        $this->region = strtoupper((string) ($this->overrides['region'] ?? config('providers.bangjeff.region', 'ID')));
        $this->sandboxBaseUrl = $this->normalizeBangJeffBaseUrl(
            (string) ($this->overrides['sandbox_endpoint'] ?? config('providers.bangjeff.sandbox_base_url', $this->sandboxBaseUrl)),
            self::BASE_URL_SANDBOX
        );

        $configuredBaseUrl = (string) ($this->overrides['endpoint'] ?? config('providers.bangjeff.base_url', self::BASE_URL_PRODUCTION));
        $this->baseUrl = $this->resolveBaseUrl($configuredBaseUrl);
    }

    private function resolveAdminApiKey(): ?string
    {
        try {
            if (! Schema::hasTable('setting_webs')) {
                return null;
            }

            $settingsApiKey = SettingWeb::query()->value('apikey_bangjeff');
        } catch (\Throwable $e) {
            Log::debug('BangJeff admin API key lookup skipped during bootstrap.', [
                'exception' => $e::class,
            ]);

            return null;
        }

        if ($settingsApiKey === null || trim((string) $settingsApiKey) === '') {
            return null;
        }

        return (string) $settingsApiKey;
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

    public function balance(): array
    {
        if ($this->shouldUseV4()) {
            return $this->requestV4('/api/v4/balance', [
                'region' => $this->region,
            ]);
        }

        return $this->requestV3('/api/v3/balance');
    }

    public function getProductsRaw(): array
    {
        if ($this->shouldUseV4()) {
            return $this->requestV4('/api/v4/product', [
                'region' => $this->region,
            ]);
        }

        return $this->requestV3('/api/v3/product');
    }

    public function listVariant(string $productCode = 'MLBB'): array
    {
        if ($this->shouldUseV4()) {
            return $this->requestV4('/api/v4/variant', [
                'region' => $this->region,
                'productCode' => $productCode,
            ]);
        }

        return $this->requestV3('/api/v3/variant', [
            'code' => $productCode,
        ]);
    }

    public function detailVariant(string $productCode): array
    {
        if ($this->shouldUseV4()) {
            return $this->requestV4('/api/v4/product/detail', [
                'region' => $this->region,
                'productCode' => $productCode,
            ]);
        }

        return $this->requestV3('/api/v3/variant/' . $productCode);
    }

    public function order(string $variantCode, string $referenceNumber, int $qty, array $inputs, ?array $price = null): array
    {
        if ($this->shouldUseV4()) {
            $payload = [
                'region' => $this->region,
                'variantCode' => $variantCode,
                'referenceNumber' => $referenceNumber,
                'qty' => max(1, $qty),
                'inputs' => $inputs,
            ];

            if (is_array($price) && isset($price['currency'], $price['value'])) {
                $payload['price'] = [
                    'currency' => (string) $price['currency'],
                    'value' => (int) $price['value'],
                ];
            }

            $response = $this->requestV4('/api/v4/checkout', $payload);

            if (! array_key_exists('error', $response) && array_key_exists('rc', $response)) {
                $response['error'] = ($response['rc'] ?? '') !== '00';
            }

            return $response;
        }

        return $this->requestV3('/api/v3/checkout', [
            'code' => $variantCode,
            'referenceNumber' => $referenceNumber,
            'qty' => max(1, $qty),
            'inputs' => $inputs,
        ]);
    }

    public function checkOrder(string $invoiceNumber): array
    {
        if ($this->shouldUseV4()) {
            return $this->requestV4('/api/v4/order/invoice-number', [
                'invoiceNumber' => $invoiceNumber,
            ]);
        }

        return $this->requestV3('/api/v3/order/' . $invoiceNumber);
    }

    public function checkOrderByReference(string $referenceNumber): array
    {
        if ($this->shouldUseV4()) {
            return $this->requestV4('/api/v4/order/reference-number', [
                'referenceNumber' => $referenceNumber,
            ]);
        }

        return $this->requestV3('/api/v3/order', [
            'referenceNumber' => $referenceNumber,
        ]);
    }

    public function go(string $url, array $data = []): array
    {
        return $this->requestV3($url, $data, true);
    }

    public function getProducts(): array
    {
        $response = $this->getProductsRaw();

        if (($response['rc'] ?? null) && ($response['rc'] !== '00')) {
            return [
                'success' => false,
                'message' => $response['message'] ?? 'BangJeff get products failed.',
                'response' => $response,
            ];
        }

        if (($response['error'] ?? false) === true) {
            return [
                'success' => false,
                'message' => $response['message'] ?? 'BangJeff get products failed.',
                'response' => $response,
            ];
        }

        $rows = $response['data'] ?? [];

        if (! is_array($rows)) {
            return [];
        }

        return array_map([$this, 'formatBangJeffProduct'], $rows);
    }

    public function getProduct(string $productId): ?array
    {
        $response = $this->detailVariant($productId);

        if (($response['error'] ?? false) === true || (($response['rc'] ?? null) && ($response['rc'] !== '00'))) {
            return null;
        }

        $data = $response['data'] ?? null;

        if (! is_array($data)) {
            return null;
        }

        if (array_is_list($data)) {
            $candidate = collect($data)->first(function (array $row) use ($productId): bool {
                return (string) ($row['variantCode'] ?? $row['code'] ?? $row['id'] ?? '') === $productId;
            }) ?? ($data[0] ?? null);

            return is_array($candidate) ? $this->formatBangJeffProduct($candidate) : null;
        }

        return $this->formatBangJeffProduct($data);
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
        $inputs = $orderData['inputs'] ?? [
            ['name' => 'ID', 'value' => $orderData['customer_id'] ?? ''],
        ];

        $response = $this->order(
            (string) ($orderData['product_id'] ?? ''),
            (string) ($orderData['order_id'] ?? ''),
            (int) ($orderData['qty'] ?? 1),
            is_array($inputs) ? $inputs : [],
            $orderData['price'] ?? null,
        );

        $statusCode = strtoupper((string) ($response['data']['statusCode'] ?? 'PROCESSING'));

        return [
            'success' => ((($response['error'] ?? null) === false) || (($response['rc'] ?? null) === '00')),
            'transaction_id' => $response['data']['invoiceNumber'] ?? ($orderData['order_id'] ?? null),
            'status' => $this->mapStatus($statusCode),
            'message' => $response['data']['statusDesc'] ?? ($response['message'] ?? 'Transaction processed'),
            'raw_response' => $response,
        ];
    }

    public function checkOrderStatus(string $orderId): array
    {
        $response = $this->checkOrder($orderId);
        $statusCode = strtoupper((string) ($response['data']['statusCode'] ?? 'PROCESSING'));

        return [
            'success' => ((($response['error'] ?? null) === false) || (($response['rc'] ?? null) === '00')),
            'status' => $this->mapStatus($statusCode),
            'message' => $response['data']['statusDesc'] ?? ($response['message'] ?? ''),
            'sn' => $response['data']['voucher'] ?? null,
            'raw_response' => $response,
        ];
    }

    private function formatBangJeffProduct(array $product): array
    {
        $rawStatus = strtoupper((string) ($product['status'] ?? $product['statusCode'] ?? 'ACTIVE'));
        $priceValue = $product['price']['value'] ?? $product['price'] ?? null;

        return [
            'provider_id' => $product['variantCode'] ?? $product['code'] ?? $product['id'] ?? null,
            'name' => $product['name'] ?? $product['variantName'] ?? null,
            'category' => $product['category'] ?? $product['productCode'] ?? null,
            'brand' => $product['brand'] ?? null,
            'price' => is_numeric($priceValue) ? (float) $priceValue : null,
            'status' => in_array($rawStatus, ['ACTIVE', 'AVAILABLE', 'SUCCESS', 'PROCESSING'], true) ? 'available' : 'unavailable',
            'description' => $product['note'] ?? $product['description'] ?? null,
            'provider' => 'bangjeff',
            'raw_data' => $product,
        ];
    }

    private function mapStatus(string $status): string
    {
        return match (strtolower($status)) {
            'success', 'sukses', 'done' => 'success',
            'pending', 'process', 'waiting' => 'pending',
            'error', 'gagal', 'failed', 'canceled', 'cancelled', 'refunded' => 'failed',
            'processing' => 'processing',
            default => 'unknown'
        };
    }

    private function shouldUseV4(): bool
    {
        return str_contains($this->baseUrl, 'distribution-api.bangjeff.com')
            || str_contains($this->baseUrl, 'sandbox-api.bangjeff.com')
            || str_contains($this->baseUrl, '/api/v4');
    }

    private function resolveBaseUrl(?string $configuredBaseUrl): string
    {
        $baseUrl = $this->normalizeBangJeffBaseUrl(
            (string) ($configuredBaseUrl ?: self::BASE_URL_PRODUCTION),
            self::BASE_URL_PRODUCTION
        );

        if ($this->shouldUseSandboxEndpoint($baseUrl)) {
            return $this->normalizeBangJeffBaseUrl($this->sandboxBaseUrl, self::BASE_URL_SANDBOX);
        }

        return $baseUrl;
    }

    private function shouldUseSandboxEndpoint(string $baseUrl): bool
    {
        $useSandboxOnLocal = (bool) config('providers.bangjeff.use_sandbox_on_local', true);

        if (! $useSandboxOnLocal) {
            return false;
        }

        if (! app()->environment(['local', 'testing'])) {
            return false;
        }

        if (str_contains($baseUrl, 'sandbox-api.bangjeff.com')) {
            return true;
        }

        return str_contains($baseUrl, 'distribution-api.bangjeff.com');
    }

    private function normalizeBangJeffBaseUrl(string $baseUrl, string $default): string
    {
        $raw = trim($baseUrl);
        if ($raw === '') {
            return rtrim($default, '/');
        }

        $normalized = rtrim($raw, '/');
        $host = strtolower((string) parse_url($normalized, PHP_URL_HOST));

        if ($host === '' && str_starts_with($normalized, 'sandbox-api.bangjeff.com')) {
            return self::BASE_URL_SANDBOX;
        }

        if ($host === '' && str_starts_with($normalized, 'distribution-api.bangjeff.com')) {
            return self::BASE_URL_PRODUCTION;
        }

        if ($host === 'sandbox-api.bangjeff.com') {
            return self::BASE_URL_SANDBOX;
        }

        if ($host === 'distribution-api.bangjeff.com') {
            return self::BASE_URL_PRODUCTION;
        }

        if (in_array($host, ['client.bangjeff.com', 'api.bangjeff.com'], true)) {
            Log::warning('BangJeff endpoint host legacy terdeteksi, dialihkan ke distribution-api.', [
                'configured' => $normalized,
            ]);

            return self::BASE_URL_PRODUCTION;
        }

        return $normalized;
    }

    private function requestV3(string $pathOrUrl, array $payload = [], bool $isAbsoluteUrl = false): array
    {
        $url = $isAbsoluteUrl
            ? $pathOrUrl
            : $this->buildVersionSafeUrl($this->baseUrl, $pathOrUrl);

        $response = Http::withToken((string) ($this->credentials['api_key'] ?? ''))->post($url, $payload);

        $decoded = $response->json();

        return is_array($decoded)
            ? $decoded
            : [
                'error' => true,
                'message' => 'Invalid BangJeff v3 response',
                'raw' => $response->body(),
            ];
    }

    private function buildVersionSafeUrl(string $baseUrl, string $pathOrUrl): string
    {
        $baseUrl = rtrim($baseUrl, '/');
        $path = '/' . ltrim($pathOrUrl, '/');

        $basePath = (string) parse_url($baseUrl, PHP_URL_PATH);
        if ($basePath !== '' && $basePath !== '/') {
            $normalizedBasePath = '/' . trim($basePath, '/');
            $normalizedPath = '/' . ltrim($path, '/');

            foreach (['/api/v2', '/api/v3', '/api/v4'] as $apiPrefix) {
                if (str_ends_with($normalizedBasePath, $apiPrefix) && str_starts_with($normalizedPath, $apiPrefix . '/')) {
                    $normalizedPath = substr($normalizedPath, strlen($apiPrefix));
                    if ($normalizedPath === false || $normalizedPath === '') {
                        $normalizedPath = '/';
                    }
                    break;
                }
            }

            return rtrim($baseUrl, '/') . '/' . ltrim($normalizedPath, '/');
        }

        return $baseUrl . '/' . ltrim($path, '/');
    }

    private function requestV4(string $path, array $payload = []): array
    {
        $apiKey = (string) ($this->credentials['api_key'] ?? '');

        if ($apiKey === '') {
            return [
                'rc' => '96',
                'error' => true,
                'message' => 'BangJeff API key belum diatur.',
            ];
        }

        $timestamp = now()->format('Y-m-d\TH:i:sP');
        $payloadJson = json_encode($payload, JSON_UNESCAPED_SLASHES);

        if ($payloadJson === false) {
            return [
                'rc' => '96',
                'error' => true,
                'message' => 'Invalid BangJeff payload JSON.',
            ];
        }

        $normalizedPath = ltrim(parse_url($path, PHP_URL_PATH) ?: $path, '/');
        $signaturePayload = 'POST:' . $normalizedPath . ':' . md5($payloadJson) . ':' . $timestamp;
        $signature = hash_hmac('sha256', $signaturePayload, $apiKey);
        $url = $this->baseUrl . '/' . ltrim($path, '/');

        $response = Http::withHeaders([
            'X-Client-Id' => $apiKey,
            'X-Request-Time' => $timestamp,
            'X-Signature' => $signature,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post($url, $payload);

        Log::debug('BangJeff v4 request', [
            'url' => $url,
            'path' => '/' . $normalizedPath,
            'status' => $response->status(),
            'payload' => $payload,
        ]);

        return $this->normalizeV4Response($response);
    }

    private function normalizeV4Response(Response $response): array
    {
        $decoded = $response->json();

        if (! is_array($decoded)) {
            return [
                'rc' => '96',
                'error' => true,
                'message' => 'Invalid BangJeff v4 response',
                'raw' => $response->body(),
            ];
        }

        if (! array_key_exists('error', $decoded)) {
            $decoded['error'] = ($decoded['rc'] ?? '') !== '00';
        }

        return $decoded;
    }
}
