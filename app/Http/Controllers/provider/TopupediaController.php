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
            $api = \DB::table('setting_webs')->where('id', 1)->first();
            $this->api = $api->topupindo_api ?? '';
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
    
    
