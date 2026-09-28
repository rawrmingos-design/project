<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DigiFlazzController extends Controller
{
    protected $username;
    protected $apiKey;
    protected $endpoint;

    public function __construct($config = [])
    {
        if (!empty($config)) {
            $this->username = $config['username'] ?? '';
            $this->apiKey = $config['api_key'] ?? '';
            $this->endpoint = $config['endpoint'] ?? 'https://api.digiflazz.com'; // Default or from config
        } else {
            // Fallback to DB if no config provided (Backward Compatibility)
            $api = DB::table('setting_webs')->where('id', 1)->first();
            if ($api) {
                $this->username = trim($api->username_digi);
                $this->apiKey = trim($api->api_key_digi);
            } else {
                // Default values when no settings found (e.g., in tests)
                $this->username = '';
                $this->apiKey = '';
            }
            $this->endpoint = 'https://api.digiflazz.com';
        }
    }

    /**
     * Akun Digiflazz ber-mode TESTING (sandbox) HANYA menerima request yang
     * membawa `testing=true`. Dulu hanya jalur `order()` yang mengirim flag
     * ini, sedangkan jalur `status()` tidak — sehingga Digiflazz membalas
     * rc=41 "Signature Anda salah" saat polling status order di staging,
     * dan order yang SUDAH dibayar ikut ditandai Gagal.
     *
     * Nilai default sengaja meniru ekspresi lama (`APP_ENV === 'local'`)
     * supaya perilaku produksi identik dengan sebelumnya.
     */
    public function isTestingMode(): bool
    {
        return (bool) config('providers.digiflazz.testing', config('app.env') === 'local');
    }

    /**
     * Semua jalur mengirim key `testing` dengan nilai yang sama — persis
     * seperti yang selalu dilakukan jalur `order()` (yang terbukti jalan di
     * produksi). Tidak ada jalur yang boleh punya bentuk payload berbeda.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function withTestingFlag(array $data): array
    {
        $data['testing'] = $this->isTestingMode();

        return $data;
    }

    public function order($uid, $zone, $service, $order_id)
    {
        $target = $uid . $zone;
        $sign = md5($this->username . $this->apiKey . strval($order_id));
        $api_postdata = $this->withTestingFlag([
            'username' => $this->username,
            'buyer_sku_code' => $service,
            'customer_no' => $target,
            'ref_id' => strval($order_id),
            'sign' => $sign,
            'cb_url' => env('APP_URL_CALLBACK') . '/wejizy/digi/payload',
        ]);

        return $this->connect("/v1/transaction", $api_postdata);
    }

    public function status($poid, $pid, $uid, $zone)
    {
        $target = $uid . $zone;
        $sign = md5($this->username . $this->apiKey . $poid);
        $data = $this->withTestingFlag([
            'command' => 'status-pasca',
            'username' => $this->username,
            'buyer_sku_code' => $pid,
            'customer_no' => $target,
            'ref_id' => $poid,
            'sign' => $sign,
            'cb_url' => env('APP_URL_CALLBACK') . '/wejizy/digi/payload',
        ]);

        return $this->connect("/v1/transaction", $data);
    }

    public function harga()
    {
        $sign = md5($this->username . $this->apiKey . "pricelist");
        $data = $this->withTestingFlag([
            'username' => $this->username,
            'sign' => $sign,
        ]);

        return $this->connect('/v1/price-list', $data);
    }

    public function cekSaldo()
    {
        $sign = md5($this->username . $this->apiKey . "depo");
        $data = $this->withTestingFlag([
            'username' => $this->username,
            'cmd' => 'deposit',
            'sign' => $sign,
        ]);

        return $this->connect('/v1/cek-saldo', $data);
    }

    public function cekSaldoManual()
    {
        $sign = md5($this->username . $this->apiKey . "manual");
        $data = $this->withTestingFlag([
            'username' => $this->username,
            'cmd' => 'manual',
            'sign' => $sign,
        ]);

        return $this->connect('/v1/cek-saldo', $data);
    }

    public function cekProduk()
    {
        $sign = md5($this->username . $this->apiKey . "pricelist");
        $data = $this->withTestingFlag([
            'username' => $this->username,
            'sign' => $sign,
        ]);

        return $this->connect('/v1/price-list', $data);
    }

    public function depositSaldo($bank, $amount, $deposit_id)
    {
        $sign = md5($this->username . $this->apiKey . strval($deposit_id));
        $data = [
            'username' => $this->username,
            'amount' => $amount,
            'bank' => $bank,
            'ref_id' => $deposit_id,
            'sign' => $sign,

        ];

        return $this->connect('/v1/deposit', $data);
    }

    public function connect($url, $data)
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($this->endpoint . $url, $data);

            $responseData = $response->json();

            // Log all responses to keep track of Digiflazz API calls
            Log::info("Digiflazz API Response: " . $url, [
                'request_ref_id' => $data['ref_id'] ?? null,
                'request_sku' => $data['buyer_sku_code'] ?? null,
                'status_code' => $response->status(),
                'response_status' => $responseData['data']['status'] ?? 'unknown',
                'response_sn' => $responseData['data']['sn'] ?? null,
                'response_message' => $responseData['data']['message'] ?? null,
                'response_body' => $responseData,
            ]);

            return $responseData;
        } catch (\Exception $e) {
            Log::error("DigiFlazz Connection Error: " . $e->getMessage(), ['url' => $this->endpoint . $url, 'data' => $data]);
            return ['data' => ['status' => 'Gagal', 'message' => 'Connection Error: ' . $e->getMessage()]];
        }
    }
}
