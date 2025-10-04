<?php

namespace App\Http\Controllers\provider;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ApiGamesController extends Controller
{
    public function order($uid = null, $zone = null, $service = null, $order_id = null)
    {
        $api = \DB::table('setting_webs')->where('id',1)->first();
        
        $target = $uid . $zone;
        $sign = md5($api->apigames_secret . $api->apigames_merchant . $order_id . $service . $target);
        $api_postdata = array(
            'ref_id' => $order_id,
            'merchant_id' => $api->apigames_merchant,
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
        $api = \DB::table('setting_webs')->where('id',1)->first();
        
        return $this->connect("/merchant/" . $api->apigames_merchant . "/cektrx/$poid");
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
