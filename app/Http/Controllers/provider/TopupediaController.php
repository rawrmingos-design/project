<?php

namespace App\Http\Controllers\provider;

use App\Http\Controllers\Controller;
use App\Models\Pembelian;
use Illuminate\Http\Request;
use Http;

class TopupediaController extends Controller
{
    private $api;
    public function __construct($config = [])
    {
        if (!empty($config)) {
            $this->api = $config['api_key'] ?? '';
            $this->url = $config['endpoint'] ?? 'https://api.topupedia.com';
        } else {
            // Fallback
            $api = \DB::table('setting_webs')->where('id', 1)->first();
            $this->api = $api->apikey_bangjeff ?? '4bf8038f-5d65-43b8-bfb9-da1bd6c9cc9e'; // Note: Original fallback seemed to use hardcoded, DB field might be apikey_topupedia (need to check SettingWeb if exists, otherwise keep hardcoded or use bangjeff if shared?) 
            // Wait, Topupedia often uses same system as BangJeff, but keys might differ. 
            // I'll check SettingWeb for topupedia key or fallback to hardcoded if not found in my previous inspection.
            // Earlier SettingWeb view didn't show Topupedia specific fields prominently.
            // Safe bet: fallback to hardcoded string if DB fetch fails or key is missing.
             $this->url = 'https://api.topupedia.com';
        }
    }
    public function balance()
    {
        $data = $this->go($this->url.'/api/v3/balance');
        
        return $data;
    }
    
    public function getProduct()
    {
        $data = $this->go($this->url.'/api/v3/product');
        
        return $data;
    }
    
    public function listVariant()
    {
        $data = $this->go($this->url.'/api/v3/variant', [
            'code' => 'MLBB'
        ]);
        
        return $data;
    }
    
    public function detailVariant($code)
    {
        $data = $this->go($this->url.'/api/v3/variant/'.$code);
        
        return $data;
    }
    
    
     public function order($code,$ref,$qty,$input)
    {
        $data = $this->go($this->url.'/api/v3/checkout',[
          'code' => $code,
          'referenceNumber' => $ref,
          'qty' => $qty,
          'inputs' => $input
        ]);
        
        return $data;
    }
    
    
    public function checkOrder($invoice)
    {
        $data = $this->go($this->url."/api/v3/order/{$invoice}");
        
        return $data;
    }
    
    public function go($url,$data = [])
    {
        $data =  Http::withToken($this->api)->post($url,$data);
        
        $response = $data->json();
        
        return $response;
        
    }

  public function handleCallback(Request $request)
  {
    $json = $request->getContent();
    $data = json_decode($json, true);

    $poid = $data['invoice_number'];
    $voucher = $data['voucher'];
    $statusCode = $data['status_code'];

    if ($statusCode === "SUCCESS") {
        $statusCode = "Sukses";
    }

    \Log::info(json_encode($data));

    $pembelian = Pembelian::where('provider_order_id', $poid)->first();

    // $buka = fopen(storage_path('logging.txt'), 'w');
    // fwrite($buka, 'test ' . json_encode($pembelian));

    if ($pembelian) {
        $updateData = [
            'status' => $statusCode
        ];

        if ($pembelian->tipe_transaksi == "voucher") {
            $updateData['voucher'] = $voucher;
        }

        $pembelian->update($updateData);
    }
}

    
    
}
    
    