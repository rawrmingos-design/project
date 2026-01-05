<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DigiFlazzController extends Controller
{
    public function order($uid, $zone, $service, $order_id)
    {
        $api = DB::table('setting_webs')->where('id', 1)->first();
        $username = trim($api->username_digi);
        $apiKey = trim($api->api_key_digi);

        $target = $uid . $zone;
        $sign = md5($username . $apiKey . strval($order_id));
        $api_postdata = [
            'username' => $api->username_digi,
            'buyer_sku_code' => $service,
            'customer_no' => $target,
            'ref_id' => strval($order_id),
            'testing' => env('APP_ENV') === 'local',
            'sign' => $sign,
        ];

        return $this->connect("/v1/transaction", $api_postdata);
    }

    public function status($poid, $pid, $uid, $zone)
    {
        $api = DB::table('setting_webs')->where('id', 1)->first();

        $target = $uid . $zone;
        $username = trim($api->username_digi);
        $apiKey = trim($api->api_key_digi);
        $sign = md5($username . $apiKey . $poid);
        $data = [
            'command' => 'status-pasca',
            'username' => $username,
            'buyer_sku_code' => $pid,
            'customer_no' => $target,
            'ref_id' => $poid,
            'sign' => $sign,

        ];

        return $this->connect("/v1/transaction", $data);
    }

    public function harga()
    {
        $api = DB::table('setting_webs')->where('id', 1)->first();
        $username = trim($api->username_digi);
        $apiKey = trim($api->api_key_digi);

        $sign = md5($username . $apiKey . "pricelist");
        $data = [
            'username' => $username,
            'sign' => $sign,

        ];

        return $this->connect('/v1/price-list', $data);
    }

    public function cekSaldo()
    {
        $api = DB::table('setting_webs')->where('id', 1)->first();
        $username = trim($api->username_digi);
        $apiKey = trim($api->api_key_digi);

        $sign = md5($username . $apiKey . "depositsaldo");
        $data = [
            'username' => $username,
            'cmd' => 'deposit',
            'sign' => $sign,
            

        ];

        return $this->connect('/v1/cek-saldo', $data);
    }

    public function cekSaldoManual()
    {
        $api = DB::table('setting_webs')->where('id', 1)->first();

        $username = trim($api->username_digi);
        $apiKey = trim($api->api_key_digi);

        $sign = md5($username . $apiKey . "manual");
        $data = [
            'username' => $username,
            'cmd' => 'manual',
            'testing' => true,
            'sign' => $sign,
        ];

        return $this->connect('/v1/cek-saldo', $data);
    }

    public function cekProduk()
    {
        $api = DB::table('setting_webs')->where('id', 1)->first();
        $username = trim($api->username_digi);
        $apiKey = trim($api->api_key_digi);

        $sign = md5($username . $apiKey . "pricelist");
        $data = [
            'username' => $username,
            'sign' => $sign,

        ];

        return $this->connect('/v1/price-list', $data);
    }

    public function depositSaldo($bank, $amount, $deposit_id)
    {
        $api = DB::table('setting_webs')->where('id', 1)->first();

        $username = trim($api->username_digi);
        $apiKey = trim($api->api_key_digi);

        $sign = md5($username . $apiKey . strval($deposit_id));
        $data = [
            'username' => $username,
            'amount' => $amount,
            'bank' => $bank,
            'ref_id' => $deposit_id,
            'sign' => $sign,

        ];

        return $this->connect('/v1/deposit', $data);
    }

    public function connect($url, $data)
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post("https://api.digiflazz.com$url", $data);

        return $response->json();
    }
}
