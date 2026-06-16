<?php

namespace App\Libraries\Provider;


class GameShopProvider
{
    
    public function order($uid, $product, $refference, $zone = null)
    {
        $product = explode('-', $product);
        
        $payload = [
            'goods_id' => intval($product[0]),
            'goods_sku_id' => intval($product[1]),
            'game_uid' => $uid,
            'game_zone_id' => $zone,
            'email' => '',
            'notify_url' => url('callback/gameshop'),
            'mch_order_no' => $refference,
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
        $order = $this->connect('/order/create', $payload);
        
        return $order;
    }
    
    public function status($order_id)
    {
        $payload = [
            'order_no' => $order_id
        ];
        
        $status = $this->connect('/order/detail', $payload);
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
    
    public function products($category)
    {
        $payload = [
            'goods_id' => intval($category),
        ];
        
        $products = $this->connect('/product/sku_list', $payload);
        return $products;
    }
    
    public function connect($path, $data = null)
    {
        $apiKey = env('GAMESHOP_API_KEY');
        
        $headers = array(
            'Content-Type: application/json',
            'Authorization: ' . $apiKey
        );
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.gameshop.zsdzw.com/api/v1" . $path);
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
        $json_result = json_decode($chresult, true);
        return $json_result;        
    }      
}
