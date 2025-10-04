<?php

namespace App\Libraries\Provider;

use Illuminate\Support\Facades\Log;

class ElitediasProvider
{
    
    public function order($uid, $product, $refference, $zone = null)
    {
        $product = explode('-', $product);
        $payload = [
            'api_key' => env('ELITEDIAS_API_KEY'),
            'game' => $product[0],
            'userid' => $uid,
            'serverid' => $zone,
            'denom' => $product[1],
            'reference' => $refference,
        ];

        // if(intval($product[0]) == 15145 || intval($product[0]) == 2362359){ //mobile legends
        //     $payload['data']['User ID'] = $uid;
        //     $payload['data']['Server ID'] = $zone;
        // }else if(intval($product[0]) == 2240079 || intval($product[0]) == 144459){ //aov (id) ff (id)
        //     $payload['data']['User ID'] = $uid;
        // }else if(intval($product[0]) == 3636183){ //valorant
        //     $payload['data']['Riot Username'] = $uid;
        // }else if(intval($product[0]) == 15673){ //codm
        //     $payload['data']['Open ID'] = $uid;
        // }else if(intval($product[0]) == 133693){//pubgm (id)
        //     $payload['data']['Character ID'] = $uid;
        // }
        
        // dump($payload);
        $order = $this->connect('/elitedias_reseller_topup_api', $payload);
        
        return $order;
    }
    
    public function status($order_id)
    {
        $payload = [
            'api_key' => env('ELITEDIAS_API_KEY'),
            'order_id' => $order_id
        ];
        
        $status = $this->connect('/track_order', $payload);
        return $status;
    }
    
    public function categories()
    {
        $payload = [
            'limit' => 1000,
        ];
        
        $categories = $this->connect('/product/list', $payload);

        return $categories;
    }
    
    public function products($category = null)
    {
        $payload = [
            'api_key' => env('ELITEDIAS_API_KEY'),
            'game' => $category
        ];
        
        $products = $this->connect('/elitedias_api_denominations', $payload);
        
        return $products;
    }
    
    public function connect($path, $data = null)
    {
        $apiKey = env('ELITEDIAS_API_KEY');
        
        $headers = [
            'Origin: acidgameshop.com',
            'Content-Type: application/json',
        ];
        
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://dev.api.elitedias.com" . $path);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        if ($data) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } else {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        }
        if($headers != []){
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        
        $chresult = curl_exec($ch);
        curl_close($ch);
        Log::info('Response ' . "https://dev.api.elitedias.com" . $path . ' ' . json_encode($data) . ' response ' . $chresult);
        $json_result = json_decode($chresult, true);
        return $json_result;        
    }      
}
