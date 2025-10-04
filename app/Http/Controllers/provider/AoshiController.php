<?php

namespace App\Http\Controllers\provider;

use App\Http\Controllers\Controller;
use App\Models\Pembelian;
use Illuminate\Http\Request;
use Http;

class AoshiController extends Controller
{
    private $api;
    
    public function __construct()
    {
        // $this->api = \DB::table('setings')->first()->api_bj;
        $this->api = \DB::table('setting_webs')->where('id',1)->first()->apikey_aoshi;
        $this->url = 'https://api.aoshimarket.com';
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
    
    public function listVariant($code)
    {
        $data = $this->go($this->url.'/api/v3/variant', [
            'code' => $code
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
    
    public function callback(Request $request)
    {
        $json = $request->getContent();
        
        $data = json_decode($json);
        
        $statuscode = $data->status_code;
        $tgl = $data->status_desc;
        
        $buka= fopen(storage_path('logging.txt'), 'w');
                    
        fwrite($buka,'test'.$statuscode.' '.$tgl);
        
        $pembelian = Pembelian::where('provider_order_id', $data->invoice_number)->first();
        
        if($pembelian) {
            $pembelian->update([
                'status' => 'Sukses',
                ]);
        }
    }
    
}