<?php

namespace App\Services\Providers;

use App\Models\SettingWeb;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApiGamesService
{
    protected string $merchantId = '';
    protected string $secretKey = '';
    protected string $baseUrl = 'https://v1.apigames.id/v2';

    public function __construct(array $config = [])
    {
        if ($config !== []) {
            $this->merchantId = (string) ($config['merchant_id'] ?? '');
            $this->secretKey = (string) ($config['secret_key'] ?? '');
            $this->baseUrl = rtrim((string) ($config['endpoint'] ?? $this->baseUrl), '/');

            return;
        }

        $settings = SettingWeb::query()->first();

        $this->merchantId = (string) ($settings->apigames_merchant ?? '');
        $this->secretKey = (string) ($settings->apigames_secret ?? '');
    }

    public function order(mixed $uid = null, mixed $zone = null, mixed $service = null, mixed $referenceId = null): array
    {
        $refId = trim((string) $referenceId);

        return $this->post('/transaksi', [
            'ref_id' => $refId,
            'merchant_id' => $this->merchantId,
            'produk' => trim((string) $service),
            'tujuan' => trim((string) $uid),
            'server_id' => trim((string) ($zone ?? '')),
            'signature' => $this->signatureFor($refId),
        ]);
    }

    public function status(mixed $referenceId): array
    {
        $refId = trim((string) $referenceId);

        return $this->post('/transaksi/status', [
            'ref_id' => $refId,
            'merchant_id' => $this->merchantId,
            'signature' => $this->signatureFor($refId),
        ]);
    }

    public function verifyWebhookSignature(string $refId, ?string $signature): bool
    {
        $signature = trim((string) $signature);

        if ($signature === '' || $refId === '' || $this->merchantId === '' || $this->secretKey === '') {
            return false;
        }

        return hash_equals($this->signatureFor($refId), $signature);
    }

    public static function normalizeStatusMeta(?string $status): array
    {
        $raw = strtolower(trim((string) $status));

        $meta = match ($raw) {
            'pending' => [
                'internal_status' => 'Pending',
                'is_final' => false,
                'should_refund' => false,
                'is_partial' => false,
                'is_provider_validation' => false,
            ],
            'proses', 'process', 'processing' => [
                'internal_status' => 'Processing',
                'is_final' => false,
                'should_refund' => false,
                'is_partial' => false,
                'is_provider_validation' => false,
            ],
            'sukses', 'success' => [
                'internal_status' => 'Sukses',
                'is_final' => true,
                'should_refund' => false,
                'is_partial' => false,
                'is_provider_validation' => false,
            ],
            'gagal', 'failed' => [
                'internal_status' => 'Gagal',
                'is_final' => true,
                'should_refund' => true,
                'is_partial' => false,
                'is_provider_validation' => false,
            ],
            'sukses sebagian' => [
                'internal_status' => 'Processing',
                'is_final' => false,
                'should_refund' => false,
                'is_partial' => true,
                'is_provider_validation' => false,
            ],
            'validasi provider' => [
                'internal_status' => 'Processing',
                'is_final' => false,
                'should_refund' => false,
                'is_partial' => false,
                'is_provider_validation' => true,
            ],
            default => [
                'internal_status' => 'Pending',
                'is_final' => false,
                'should_refund' => false,
                'is_partial' => false,
                'is_provider_validation' => false,
            ],
        };

        $meta['raw_status'] = $raw === '' ? 'unknown' : $raw;

        return $meta;
    }

    protected function signatureFor(string $refId): string
    {
        return md5($this->merchantId . ':' . $this->secretKey . ':' . $refId);
    }

    protected function post(string $path, array $payload): array
    {
        if ($this->merchantId === '' || $this->secretKey === '') {
            return [
                'result' => false,
                'transport_error' => false,
                'message' => 'Konfigurasi API Games belum lengkap.',
            ];
        }

        try {
            $response = Http::acceptJson()
                ->timeout(30)
                ->post($this->baseUrl . $path, $payload);
        } catch (ConnectionException $exception) {
            Log::warning('ApiGames connection error', [
                'path' => $path,
                'message' => $exception->getMessage(),
            ]);

            return [
                'result' => false,
                'transport_error' => true,
                'message' => $exception->getMessage(),
            ];
        }

        $decoded = $response->json();

        Log::debug('ApiGames request completed', [
            'path' => $path,
            'http_status' => $response->status(),
            'provider_status' => is_array($decoded) ? ($decoded['data']['status'] ?? $decoded['status'] ?? null) : null,
        ]);

        if (! is_array($decoded)) {
            return [
                'result' => false,
                'transport_error' => ! $response->successful(),
                'message' => 'Invalid API Games response.',
                'raw' => $response->body(),
            ];
        }

        $decoded['result'] = (int) ($decoded['status'] ?? 0) === 1;
        $decoded['transport_error'] = ! $response->successful();
        $decoded['message'] = (string) ($decoded['error_msg'] ?? $decoded['message'] ?? '');

        return $decoded;
    }
}
