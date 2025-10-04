<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class DigiFlazzController extends Controller
{
    public function order($uid, $zone, $service, $order_id)
    {
        $api = DB::table('setting_webs')->where('id', 1)->first();

        $target = $uid . $zone;
        $sign = md5($api->username_digi . $api->api_key_digi . strval($order_id));
        $api_postdata = [
            'username' => $api->username_digi,
            'buyer_sku_code' => $service,
            'customer_no' => $target,
            'ref_id' => strval($order_id),
            'sign' => $sign,
        ];

        return $this->connect("/v1/transaction", $api_postdata);
    }

    public function status($poid, $pid, $uid, $zone)
    {
        $api = DB::table('setting_webs')->where('id', 1)->first();

        $target = $uid . $zone;
        $sign = md5($api->username_digi . $api->api_key_digi . strval($poid));
        $data = [
            'command' => 'status-pasca',
            'username' => $api->username_digi,
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

        $sign = md5($api->username_digi . $api->api_key_digi . "pricelist");
        $data = [
            'username' => $api->username_digi,
            'sign' => $sign,

        ];

        return $this->connect('/v1/price-list', $data);
    }

    public function cekSaldo()
    {
        $api = DB::table('setting_webs')->where('id', 1)->first();

        $sign = md5($api->username_digi . $api->api_key_digi . "depositsaldo");
        $data = [
            'username' => $api->username_digi,
            'cmd' => 'deposit',
            'sign' => $sign,
            

        ];

        return $this->connect('/v1/cek-saldo', $data);
    }

    public function cekSaldoManual()
    {
        $api = DB::table('setting_webs')->where('id', 1)->first();

        $sign = md5($api->username_digi . $api->api_key_digi . "manual");
        $data = [
            'username' => $api->username_digi,
            'cmd' => 'manual',
            'testing' => true,
            'sign' => $sign,
        ];

        return $this->connect('/v1/cek-saldo', $data);
    }

    public function cekProduk()
    {
        $api = DB::table('setting_webs')->where('id', 1)->first();

        $sign = md5($api->username_digi . $api->api_key_digi . "pricelist");
        $data = [
            'username' => $api->username_digi,
            'sign' => $sign,

        ];

        return $this->connect('/v1/price-list', $data);
    }

    public function depositSaldo($bank, $amount, $deposit_id)
    {
        $api = DB::table('setting_webs')->where('id', 1)->first();

        $sign = md5($api->username_digi . $api->api_key_digi . strval($deposit_id));
        $data = [
            'username' => $api->username_digi,
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
