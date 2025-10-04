<?php

namespace App\Libraries\Provider;

class StrleyaShopProvider
{
    
    public function order($uid, $product, $refference, $zone = null)
    {
        $product = explode('-', $product);
        
        $payload = [
            'game' => $product[0],
            'userid' => $uid,
            'serverid' => $zone,
            'amount' => $product[1],
        ];
        
        // dump($payload);
        $order = $this->connect('/lucia_reseller_order_api', $payload);
        
        return $order;
    }
    
    public function status($order_id)
    {
        $payload = [
            'order_id' => $order_id,
        ];
        
        $status = $this->connect('/lucia_track_order_api', $payload);
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
            'game' => $category,
        ];
        
        $products = $this->connect('/lucia_reseller_product_detail_api', $payload);
        preg_match_all("/\('([^']+)',\s*([\d.]+)\)/", $products, $matches);

        $services = array_combine($matches[1], $matches[2]);

        $result = [];
        foreach($services as $key => $service){
            $result[] = [
                'id' => $key,
                'price' => floatval($service),
            ];
        }
        return $result;
    }
    
    public function connect($path, $data = null)
    {
        $apiKey = env('STRLEYASHOP_TOKEN');
        
        
        $headers = array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        );
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://strleyashop.pro/api" . $path);
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
        if ($path == '/lucia_reseller_product_detail_api') {
            return $chresult;
        }
        $json_result = json_decode($chresult, true);
        return $json_result;        
    }      
}
