<?php

namespace App\Http\Controllers\provider;

use App\Http\Controllers\Controller;
use App\Models\Pembelian;
use App\Models\SettingWeb;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BangJeffController extends Controller
{
    private string $api = '';
    private string $url = 'https://distribution-api.bangjeff.com';
    private string $region = 'ID';
    
    public function __construct($config = [])
    {
        if (!empty($config)) {
            $this->api = $config['api_key'] ?? '';
            $this->url = rtrim((string) ($config['endpoint'] ?? $this->url), '/');
            $this->region = strtoupper((string) ($config['region'] ?? $this->region));
        } else {
            $api = SettingWeb::query()->find(1);
            $this->api = (string) ($api->apikey_bangjeff ?? '');
            $this->url = rtrim((string) config('providers.bangjeff.base_url', $this->url), '/');
            $this->region = strtoupper((string) config('providers.bangjeff.region', $this->region));
        }
    }
    
    public function balance()
    {
        if ($this->shouldUseV4()) {
            return $this->requestV4('/api/v4/balance', [
                'region' => $this->region,
            ]);
        }

        return $this->requestV3('/api/v3/balance');
    }
    
    public function getProduct()
    {
        if ($this->shouldUseV4()) {
            return $this->requestV4('/api/v4/product', [
                'region' => $this->region,
            ]);
        }

        return $this->requestV3('/api/v3/product');
    }
    
    
     public function listVariant(string $productCode = 'MLBB')
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
    
    public function detailVariant($productCode)
    {
        if ($this->shouldUseV4()) {
            return $this->requestV4('/api/v4/product/detail', [
                'region' => $this->region,
                'productCode' => $productCode,
            ]);
        }

        return $this->requestV3('/api/v3/variant/'.$productCode);
    }
    
    
     public function order($code,$ref,$qty,$input, ?array $price = null)
    {
        if ($this->shouldUseV4()) {
            $payload = [
                'region' => $this->region,
                'variantCode' => $code,
                'referenceNumber' => $ref,
                'qty' => max(1, (int) $qty),
                'inputs' => is_array($input) ? $input : [],
            ];

            if (is_array($price) && isset($price['currency'], $price['value'])) {
                $payload['price'] = [
                    'currency' => (string) $price['currency'],
                    'value' => (int) $price['value'],
                ];
            }

            $response = $this->requestV4('/api/v4/checkout', $payload);

            // Keep backward compatibility for older code paths.
            if (! array_key_exists('error', $response) && array_key_exists('rc', $response)) {
                $response['error'] = ($response['rc'] ?? '') !== '00';
            }

            return $response;
        }

        return $this->requestV3('/api/v3/checkout',[
            'code' => $code,
            'referenceNumber' => $ref,
            'qty' => $qty,
            'inputs' => $input,
        ]);
    }
    
    
    public function checkOrder($invoice)
    {
        if ($this->shouldUseV4()) {
            return $this->requestV4('/api/v4/order/invoice-number', [
                'invoiceNumber' => $invoice,
            ]);
        }

        return $this->requestV3("/api/v3/order/{$invoice}");
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
    
    public function go($url,$data = [])
    {
        // Legacy compatibility call.
        return $this->requestV3($url, $data, true);
    }
    
  public function handleCallback(Request $request)
  {
    $json = $request->getContent();
    $data = json_decode($json, true);

    $poid = $data['invoice_number'];
    $voucher = $data['voucher'];
    $statusCode = $data['status_code'];

    if ($statusCode === "SUCCESS") {
        $statusCode = "Sukses";
    }

    \Log::info(json_encode($data));

    $pembelian = Pembelian::where('provider_order_id', $poid)->first();

    // $buka = fopen(storage_path('logging.txt'), 'w');
    // fwrite($buka, 'test ' . json_encode($pembelian));

    if ($pembelian) {
        $updateData = [
            'status' => $statusCode
        ];

        if ($pembelian->tipe_transaksi == "voucher") {
            $updateData['voucher'] = $voucher;
        }

        $pembelian->update($updateData);
    }
}

    private function shouldUseV4(): bool
    {
        return str_contains($this->url, 'distribution-api.bangjeff.com')
            || str_contains($this->url, 'sandbox-api.bangjeff.com')
            || str_contains($this->url, '/api/v4');
    }

    private function requestV3(string $pathOrUrl, array $payload = [], bool $isAbsoluteUrl = false): array
    {
        $url = $isAbsoluteUrl ? $pathOrUrl : $this->url . '/' . ltrim($pathOrUrl, '/');

        $response = Http::withToken($this->api)->post($url, $payload);

        $decoded = $response->json();

        return is_array($decoded)
            ? $decoded
            : [
                'error' => true,
                'message' => 'Invalid BangJeff v3 response',
                'raw' => $response->body(),
            ];
    }

    private function requestV4(string $path, array $payload = []): array
    {
        if ($this->api === '') {
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
        $hashedPayload = md5($payloadJson);
        $signaturePayload = 'POST:' . $normalizedPath . ':' . $hashedPayload . ':' . $timestamp;
        $signature = hash_hmac('sha256', $signaturePayload, $this->api);

        $url = $this->url . '/' . ltrim($path, '/');

        $response = Http::withHeaders([
            'X-Client-Id' => $this->api,
            'X-Request-Time' => $timestamp,
            'X-Signature' => $signature,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post($url, $payload);

        Log::info('BangJeff v4 request', [
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
