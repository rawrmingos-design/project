<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
            $this->username = trim($api->username_digi);
            $this->apiKey = trim($api->api_key_digi);
            $this->endpoint = 'https://api.digiflazz.com';
        }
    }

    public function order($uid, $zone, $service, $order_id)
    {
        $target = $uid . $zone;
        $sign = md5($this->username . $this->apiKey . strval($order_id));
        $api_postdata = [
            'username' => $this->username,
            'buyer_sku_code' => $service,
            'customer_no' => $target,
            'testing' => env('APP_ENV') === 'local',
            'ref_id' => strval($order_id),
            'sign' => $sign,
            'cb_url' => env('APP_URL_CALLBACK') . '/wejizy/digi/payload',
        ];

        return $this->connect("/v1/transaction", $api_postdata);
    }

    public function status($poid, $pid, $uid, $zone)
    {
        $target = $uid . $zone;
        $sign = md5($this->username . $this->apiKey . $poid);
        $data = [
            'command' => 'status-pasca',
            'username' => $this->username,
            'buyer_sku_code' => $pid,
            'customer_no' => $target,
            'ref_id' => $poid,
            'sign' => $sign,
            'cb_url' => env('APP_URL_CALLBACK') . '/wejizy/digi/payload',
        ];

        return $this->connect("/v1/transaction", $data);
    }

    public function harga()
    {
        $sign = md5($this->username . $this->apiKey . "pricelist");
        $data = [
            'username' => $this->username,
            'sign' => $sign,
        ];

        return $this->connect('/v1/price-list', $data);
    }

    public function cekSaldo()
    {
        $sign = md5($this->username . $this->apiKey . "depo");
        $data = [
            'username' => $this->username,
            'cmd' => 'deposit',
            'sign' => $sign,
        ];

        return $this->connect('/v1/cek-saldo', $data);
    }

    public function cekSaldoManual()
    {
        $sign = md5($this->username . $this->apiKey . "manual");
        $data = [
            'username' => $this->username,
            'cmd' => 'manual',
            'sign' => $sign,
        ];

        return $this->connect('/v1/cek-saldo', $data);
    }

    public function cekProduk()
    {
        $sign = md5($this->username . $this->apiKey . "pricelist");
        $data = [
            'username' => $this->username,
            'sign' => $sign,
        ];

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

            Log::debug("DigiFlazz Request to $url", [
                'status' => $response->status(),
                'payload_meta' => [
                    'ref_id' => $data['ref_id'] ?? null,
                    'buyer_sku_code' => $data['buyer_sku_code'] ?? null,
                    'command' => $data['command'] ?? null,
                ],
                'body' => $response->body(),
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error("DigiFlazz Connection Error: " . $e->getMessage(), ['url' => $this->endpoint . $url, 'data' => $data]);
            return ['data' => ['status' => 'Gagal', 'message' => 'Connection Error: ' . $e->getMessage()]];
        }
    }
}
