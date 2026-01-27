<?php

namespace App\Http\Controllers\provider;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ApiGamesController extends Controller
{
    protected $merchantId;
    protected $secretKey;

    public function __construct($config = [])
    {
        if (!empty($config)) {
            $this->merchantId = $config['merchant_id'] ?? '';
            $this->secretKey = $config['secret_key'] ?? '';
        } else {
            // Fallback
            $api = \DB::table('setting_webs')->where('id', 1)->first();
            $this->merchantId = $api->apigames_merchant;
            $this->secretKey = $api->apigames_secret;
        }
    }

    public function order($uid = null, $zone = null, $service = null, $order_id = null)
    {
        $target = $uid . $zone;
        $sign = md5($this->secretKey . $this->merchantId . $order_id . $service . $target);
        $api_postdata = array(
            'ref_id' => $order_id,
            'merchant_id' => $this->merchantId,
            'produk' => "$service",
            'tujuan' => $target,
            'signature' => $sign,
        );

        $header = array(
            'Content-Type: application/json',
        );

        return $this->connect("/transaksi", $api_postdata, $header);
    }

    public function status($poid)
    {
        return $this->connect("/merchant/" . $this->merchantId . "/cektrx/$poid");
    }

    public function connect($url, $data = null, $header = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://v1.apigames.id" . $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

        if ($data) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } else {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        $chresult = curl_exec($ch);
        curl_close($ch);
        $json_result = json_decode($chresult, true);
        return $json_result;
    }
}
