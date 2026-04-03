<?php

namespace App\Http\Controllers\provider;

use App\Http\Controllers\Controller;
use App\Models\SettingWeb;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VipResellerController extends Controller
{
    protected string $apiId = '';

    protected string $apiKey = '';

    protected string $apiSign = '';

    protected string $endpoint = 'https://vip-reseller.co.id/api/game-feature';
    
    protected string $profileEndpoint = 'https://vip-reseller.co.id/api/profile';

    public function __construct(array $config = [])
    {
        if (! empty($config)) {
            $this->apiId = (string) ($config['api_id'] ?? $config['vip_apiid'] ?? '');
            $this->apiKey = (string) ($config['api_key'] ?? $config['vip_apikey'] ?? '');
            $this->apiSign = (string) ($config['api_sign'] ?? $config['vip_sign'] ?? '');
            $this->endpoint = (string) ($config['endpoint'] ?? $this->endpoint);
            $this->profileEndpoint = (string) ($config['profile_endpoint'] ?? $this->profileEndpoint);

            return;
        }

        $api = SettingWeb::query()->where('id', 1)->first();

        $this->apiId = (string) ($api->vip_apiid ?? '');
        $this->apiKey = (string) ($api->vip_apikey ?? '');
        $this->apiSign = (string) ($api->vip_sign ?? '');
    }

    public function order(
        $uid = null,
        $zone = null,
        $service = null,
        ?string $postAdditionalData = null,
        ?string $additionalData = null,
        ?int $quantity = null
    ): array
    {
        $payload = [
            'type' => 'order',
            'service' => $service,
            'data_no' => $uid,
        ];

        if (filled($zone)) {
            $payload['data_zone'] = $zone;
        }

        if (filled($postAdditionalData)) {
            $payload['post_additional_data'] = $postAdditionalData;
        }

        if (filled($additionalData)) {
            $payload['additional_data'] = $additionalData;
        }

        if ($quantity !== null && $quantity > 0) {
            $payload['quantity'] = $quantity;
        }

        return $this->request($payload);
    }

    public function status($trxid = null, ?int $limit = null): array
    {
        $payload = [
            'type' => 'status',
        ];

        if (filled($trxid)) {
            $payload['trxid'] = $trxid;
        } elseif ($limit !== null) {
            $payload['limit'] = $limit;
        }

        return $this->request($payload);
    }

    public function services(?string $filterGame = null, ?string $filterStatus = null): array
    {
        $payload = [
            'type' => 'services',
        ];

        if (filled($filterGame)) {
            $payload['filter_game'] = $filterGame;
        }

        if (filled($filterStatus)) {
            $payload['filter_status'] = $filterStatus;
        }

        return $this->request($payload);
    }

    public function serviceStock(string $service): array
    {
        return $this->request([
            'type' => 'service-stock',
            'service' => $service,
        ]);
    }

    public function getNickname($code = null, $target = null, $additionalTarget = null): array
    {
        $payload = [
            'type' => 'get-nickname',
            'code' => $code,
            'target' => $target,
        ];

        if (filled($additionalTarget)) {
            $payload['additional_target'] = $additionalTarget;
        }

        return $this->request($payload);
    }

    public function username($uid = null, $zone = null, $service = null): array
    {
        return $this->getNickname($service, $uid, $zone);
    }

    public function profile(): array
    {
        // Official profile endpoint:
        // POST https://vip-reseller.co.id/api/profile
        // payload: key, sign (md5(api_id + api_key))
        return $this->requestToEndpoint([], $this->profileEndpoint);
    }

    public static function expectedSignature(string $apiId, string $apiKey): string
    {
        return md5($apiId . $apiKey);
    }

    public static function resolveSignature(?string $configuredSign, string $apiId, string $apiKey): string
    {
        $configuredSign = trim((string) $configuredSign);

        if ($configuredSign !== '') {
            return $configuredSign;
        }

        return self::expectedSignature($apiId, $apiKey);
    }

    public static function normalizeStatusMeta(?string $status): array
    {
        $raw = strtolower(trim((string) $status));

        $meta = match ($raw) {
            'waiting' => [
                'internal_status' => 'Pending',
                'is_final' => false,
                'should_refund' => false,
                'is_partial' => false,
            ],
            'processing', 'proccessing' => [
                'internal_status' => 'Processing',
                'is_final' => false,
                'should_refund' => false,
                'is_partial' => false,
            ],
            'success' => [
                'internal_status' => 'Sukses',
                'is_final' => true,
                'should_refund' => false,
                'is_partial' => false,
            ],
            'error', 'canceled', 'cancelled' => [
                'internal_status' => 'Gagal',
                'is_final' => true,
                'should_refund' => true,
                'is_partial' => false,
            ],
            'partial' => [
                'internal_status' => 'Processing',
                'is_final' => false,
                'should_refund' => false,
                'is_partial' => true,
            ],
            default => [
                'internal_status' => 'Pending',
                'is_final' => false,
                'should_refund' => false,
                'is_partial' => false,
            ],
        };

        $meta['raw_status'] = $raw === '' ? 'unknown' : $raw;

        return $meta;
    }

    protected function request(array $payload): array
    {
        return $this->requestToEndpoint($payload, $this->endpoint);
    }

    protected function requestToEndpoint(array $payload, string $endpoint): array
    {
        if ($this->apiId === '' || $this->apiKey === '') {
            return [
                'result' => false,
                'message' => 'Konfigurasi VIP Reseller belum lengkap.',
            ];
        }

        $payload = array_merge([
            'key' => $this->apiKey,
            'sign' => self::resolveSignature($this->apiSign, $this->apiId, $this->apiKey),
        ], array_filter($payload, static fn ($value) => $value !== null && $value !== ''));

        $response = Http::asForm()
            ->timeout(30)
            ->post($endpoint, $payload);

        Log::debug('VipReseller request', [
            'endpoint' => $endpoint,
            'payload' => array_diff_key($payload, ['sign' => true, 'key' => true]),
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        if (! $response->successful()) {
            return [
                'result' => false,
                'message' => 'VIP Reseller HTTP ' . $response->status(),
                'raw' => $response->body(),
            ];
        }

        $decoded = $response->json();

        if (! is_array($decoded)) {
            return [
                'result' => false,
                'message' => 'Invalid VIP Reseller response.',
                'raw' => $response->body(),
            ];
        }

        return $decoded;
    }
}
