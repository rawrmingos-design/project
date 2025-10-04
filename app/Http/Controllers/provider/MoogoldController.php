<?php

namespace App\Http\Controllers\provider;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MoogoldController extends Controller
{
    
   public function order($uid, $product, $refference, $zone = null)
    {
        $product = explode(',', $product);
        $productId = intval($product[1]);
        $categoryId = intval($product[0]);
    
        $payload = [
            'path' => 'order/create_order',
            'data' => [
                'category' => 1,
                'product-id' => $productId,
                'quantity' => 1
            ],
            'partnerOrderId' => $refference
        ];
    
        if (in_array($categoryId, [15145, 2362359])) { 
            $payload['data']['User ID'] = $uid;
            $payload['data']['Server ID'] = $zone;
        } elseif (in_array($categoryId, [2240079, 144459])) { 
            $payload['data']['User ID'] = $uid;
        } elseif ($categoryId == 3636183) { 
            $payload['data']['Riot Username'] = $uid;
        } elseif ($categoryId == 15673) {
            $payload['data']['Open ID'] = $uid;
        } elseif (in_array($categoryId, [133695,96228,116133,5177311])) {
            $payload['data']['Player ID'] = $uid;
        } elseif (in_array($categoryId, [133693, 6963])) {
            $payload['data']['Character ID'] = $uid;
        }
    
    
        $orderResponse = $this->connect('order/create_order', $payload);
    
        if (isset($orderResponse['err_code'])) {
            Log::error("MooGold Order Failed", ['response' => $orderResponse]);
            return ['error' => "Order failed: " . $orderResponse['err_message']];
        }
    
        return $orderResponse;
    }



    
    public function status($order_id)
    {
        $payload = [
            'path' => 'order/order_detail',
            'order_id' => intval($order_id)
        ];
        
        $status = $this->connect('order/order_detail', $payload);
        return $status;
    }
    
    public function categories()
    {
        $payload = [
            'path' => 'product/list_product',
            'category_id' => 50,
        ];
        
        $categories = $this->connect('product/list_product', $payload);
        return $categories;
    }
    
    public function products($category)
    {
        $payload = [
            'path' => 'product/product_detail',
            'product_id' => $category
        ];
        
        $products = $this->connect('product/product_detail', $payload);
        return $products;
    }
    
    public function cekStatusOrder()
    {
    $orders = \App\Models\Pembelian::where('status', 'Proses')->get();

    foreach ($orders as $order) {
        if ($order->provider != 'moogold') continue; 
        $provider_order_id = $order->provider_order_id;

        $response = $this->status($provider_order_id);

        if (isset($response['order_status'])) {
            $status = strtolower($response['order_status']); 
            $newStatus = null;

            if ($status === 'completed') {
                $newStatus = 'Sukses';
            } elseif ($status === 'Sending') {
                $newStatus = 'Sending';
            } elseif ($status === 'failed') {
                $newStatus = 'Gagal';
            } elseif ($status === 'incorrect-details') {
                $newStatus = 'Gagal';
            } elseif ($status === 'refunded') {
                $newStatus = 'Refunded';
            }

            if ($newStatus) {
                $order->update([
                    'status' => $newStatus,
                    'log' => json_encode([
                        'provider' => 'moogold',
                        'order_id' => $provider_order_id,
                        'status' => $newStatus,
                        'response' => $response
                    ], JSON_PRETTY_PRINT)
                ]);
            }
        } else {
            // Log::warning("Gagal mengambil status Moogold untuk order_id: $provider_order_id");

            $order->update([
                'log' => json_encode([
                    'provider' => 'moogold',
                    'order_id' => $provider_order_id,
                    'error' => 'Gagal mengambil status dari Moogold',
                    'response' => $response
                ], JSON_PRETTY_PRINT)
            ]);
        }
    }
}
     
    public function connect($path, $data = null)
    {
        $time = time();
        $basicAuth = base64_encode(ENV('MOO_PARTNER').":".ENV('MOO_SECRET'));
        $signature = hash_hmac('SHA256', json_encode($data).$time.$path, ENV('MOO_SECRET'));
        
        $headers = array(
            'Content-Type: application/json',
            'Authorization: Basic '.$basicAuth,
            'auth: '.$signature,
            'timestamp: '.$time
        );
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://moogold.com/wp-json/v1/api/" . $path);
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
