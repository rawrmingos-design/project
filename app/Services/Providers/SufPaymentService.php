<?php

namespace App\Services\Providers;

use Illuminate\Support\Facades\Http;

class SufPaymentService
{
    public function __construct(private readonly array $config = [])
    {
    }

    /**
     * @return array{success:bool,balance:float|null,message:string}
     */
    public function balance(): array
    {
        $missing = $this->missingCredentials(['api_id', 'api_key', 'secret_key']);

        if ($missing !== []) {
            return [
                'success' => false,
                'balance' => null,
                'message' => 'Credential SufPayment belum lengkap: ' . implode(', ', $missing),
            ];
        }

        try {
            $response = Http::asForm()
                ->timeout($this->timeout())
                ->post($this->endpoint('/account'), [
                    'api_id' => $this->config['api_id'],
                    'api_key' => $this->config['api_key'],
                    'secret_key' => $this->config['secret_key'],
                ]);
        } catch (\Throwable $exception) {
            return [
                'success' => false,
                'balance' => null,
                'message' => 'SufPayment transport error: ' . $exception->getMessage(),
            ];
        }

        if (! $response->successful()) {
            return [
                'success' => false,
                'balance' => null,
                'message' => 'SufPayment HTTP error: ' . $response->status(),
            ];
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            return [
                'success' => false,
                'balance' => null,
                'message' => 'SufPayment mengembalikan response JSON tidak valid.',
            ];
        }

        if (($payload['response'] ?? false) !== true) {
            return [
                'success' => false,
                'balance' => null,
                'message' => trim((string) ($payload['data']['msg'] ?? 'SufPayment gagal mengembalikan saldo akun.')),
            ];
        }

        $balance = $payload['data']['balance'] ?? null;

        return [
            'success' => true,
            'balance' => is_numeric($balance) ? (float) $balance : null,
            'message' => 'Saldo SufPayment berhasil diperbarui.',
        ];
    }

    public function order(mixed $uid = null, mixed $zone = null, mixed $service = null): array
    {
        $payload = $this->authenticatedPayload([
            'service' => trim((string) $service),
            'target' => $this->target($uid, $zone),
        ]);

        $orderCommand = trim((string) ($this->config['order_cmd'] ?? config('providers.sufpayment.order_cmd', '')));
        if ($orderCommand !== '') {
            $payload['cmd'] = $orderCommand;
        }

        return $this->post('/orders', $payload, 'order');
    }

    public function status(mixed $orderId): array
    {
        $payload = $this->authenticatedPayload([
            'id' => trim((string) $orderId),
        ]);

        $statusCommand = trim((string) ($this->config['status_cmd'] ?? config('providers.sufpayment.status_cmd', '')));
        if ($statusCommand !== '') {
            $payload['cmd'] = $statusCommand;
        }

        return $this->post('/status', $payload, 'status');
    }

    public static function normalizeStatusMeta(mixed $status): array
    {
        $raw = strtolower(trim((string) $status));

        $meta = match ($raw) {
            'success', 'sukses', 'completed', 'complete' => [
                'internal_status' => 'Sukses',
                'is_final' => true,
                'should_refund' => false,
            ],
            'processing', 'process', 'proses' => [
                'internal_status' => 'Processing',
                'is_final' => false,
                'should_refund' => false,
            ],
            'failed', 'fail', 'gagal', 'error', 'refunded', 'refund' => [
                'internal_status' => 'Gagal',
                'is_final' => true,
                'should_refund' => true,
            ],
            'cancel', 'canceled', 'cancelled', 'batal' => [
                'internal_status' => 'Batal',
                'is_final' => true,
                'should_refund' => true,
            ],
            default => [
                'internal_status' => 'Pending',
                'is_final' => false,
                'should_refund' => false,
            ],
        };

        $meta['raw_status'] = $raw === '' ? 'unknown' : $raw;

        return $meta;
    }

    private function post(string $path, array $payload, string $context): array
    {
        $missing = $this->missingCredentials(['api_id', 'api_key', 'secret_key']);

        if ($missing !== []) {
            return [
                'result' => false,
                'transport_error' => false,
                'message' => 'Credential SufPayment belum lengkap: ' . implode(', ', $missing),
            ];
        }

        try {
            $response = Http::asForm()
                ->timeout($this->timeout())
                ->post($this->endpoint($path), $payload);
        } catch (\Throwable $exception) {
            return [
                'result' => false,
                'transport_error' => true,
                'message' => 'SufPayment transport error: ' . $exception->getMessage(),
            ];
        }

        if (! $response->successful()) {
            return [
                'result' => false,
                'transport_error' => true,
                'message' => 'SufPayment HTTP error: ' . $response->status(),
                'raw' => $response->body(),
            ];
        }

        $decoded = $response->json();

        if (! is_array($decoded)) {
            return [
                'result' => false,
                'transport_error' => true,
                'message' => 'SufPayment mengembalikan response JSON tidak valid.',
                'raw' => $response->body(),
            ];
        }

        $data = is_array($decoded['data'] ?? null) ? $decoded['data'] : [];
        $message = trim((string) ($data['msg'] ?? $data['message'] ?? $decoded['message'] ?? ''));
        $statusMeta = self::normalizeStatusMeta($data['status'] ?? $data['order_status'] ?? null);
        $transactionId = $data['id']
            ?? $data['trxid']
            ?? $data['trx_id']
            ?? $data['transaction_id']
            ?? null;

        $decoded['result'] = ($decoded['response'] ?? false) === true;
        $decoded['success'] = $decoded['result'];
        $decoded['transport_error'] = false;
        $decoded['data'] = $data;
        $decoded['order_status'] = $statusMeta['internal_status'];
        $decoded['provider_status'] = $statusMeta['raw_status'];
        $decoded['transaction_id'] = $transactionId;
        $decoded['raw'] = [
            'response' => $decoded['response'] ?? null,
            'data' => $data,
            'message' => $decoded['message'] ?? null,
        ];
        $decoded['message'] = $message !== '' ? $message : ($decoded['result'] ? 'SufPayment ' . $context . ' accepted.' : 'SufPayment ' . $context . ' failed.');

        return $decoded;
    }

    private function authenticatedPayload(array $payload): array
    {
        return array_merge([
            'api_id' => $this->config['api_id'] ?? null,
            'api_key' => $this->config['api_key'] ?? null,
            'secret_key' => $this->config['secret_key'] ?? null,
        ], array_filter($payload, static fn ($value): bool => $value !== null && $value !== ''));
    }

    private function target(mixed $uid, mixed $zone): string
    {
        $uid = trim((string) $uid);
        $zone = trim((string) ($zone ?? ''));

        if ($zone === '') {
            return $uid;
        }

        $separator = (string) ($this->config['target_separator'] ?? config('providers.sufpayment.target_separator', ''));

        return $separator !== '' ? $uid . $separator . $zone : $uid;
    }

    private function endpoint(string $path): string
    {
        $baseUrl = rtrim((string) ($this->config['endpoint'] ?? config('providers.sufpayment.base_url', 'https://sufpayment.com/api/v1')), '/');

        return $baseUrl . '/' . ltrim($path, '/');
    }

    private function timeout(): int
    {
        return (int) ($this->config['timeout'] ?? config('providers.sufpayment.timeout', 15));
    }

    /**
     * @param array<int, string> $keys
     * @return array<int, string>
     */
    private function missingCredentials(array $keys): array
    {
        return array_values(array_filter(
            $keys,
            fn (string $key): bool => trim((string) ($this->config[$key] ?? '')) === ''
        ));
    }
}
