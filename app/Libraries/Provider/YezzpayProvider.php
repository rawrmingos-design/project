<?php

namespace App\Libraries\Provider;


class YezzpayProvider
{
    
    public function order($uid, $product, $refference, $zone = null)
    {
        
        $payload = [
            'api_key' => env('YEZZPAY_API_KEY'),
            'target' => $uid,
            'additional_target' => $zone,
            'code' => $product,
            'ref_id' => $refference,
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
        $order = $this->connect('/order', $payload);
        
        return $order;
    }
    
    public function status($order_id)
    {
        $payload = [
            'api_key' => env('YEZZPAY_API_KEY'),
            'ref_id' => $order_id
        ];
        
        $status = $this->connect('/status', $payload);
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
            'api_key' => env('YEZZPAY_API_KEY'),
            'category' => $category,
            'currency' => 'MYR'
        ];
        
        $products = $this->connect('/services', $payload);
        
        return $products;
    }
    
    public function connect($path, $data = null)
    {
        $apiKey = env('YEZZPAY_API_KEY');
        
        $headers = [];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://yezzpay.com/api/v1" . $path);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        if ($data) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        } else {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        }
        if($headers != []){
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        
        $chresult = curl_exec($ch);
        curl_close($ch);
        $json_result = json_decode($chresult, true);
        return $json_result;        
    }      
}
