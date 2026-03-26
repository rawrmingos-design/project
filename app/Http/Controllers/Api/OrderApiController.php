<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\DigiFlazzController;
use App\Http\Controllers\provider\VipResellerController;
use App\Http\Controllers\provider\ApiGamesController;
use App\Http\Controllers\provider\TopupediaController;
use App\Http\Controllers\provider\BangJeffController;
use App\Http\Controllers\provider\MoogoldController;
use App\Libraries\Provider\GameShopProvider;
use App\Libraries\Provider\StrleyaShopProvider;
use App\Libraries\Provider\YezzpayProvider;
use App\Libraries\Provider\ElitediasProvider;
use App\Models\Pembelian;
use App\Models\Pembayaran;
use App\Support\PembelianStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Response;


class OrderApiController extends Controller
{   
    
   public function documentation()
   {
        return view('documentation.index');
   }
    
   public function balance(Request $request)
   {
     
        $bearerToken = $request->bearerToken();

        if(!$bearerToken) {
           return response()->json([
                'error' => true,
                'code' => 403,
                'message' => "Access Token is required"
           ]);
        } 
        
        
        $user = \App\Models\User::where('api_key', $bearerToken)->first();
        
         $buka= fopen(storage_path('logging.txt'), 'w');
                    
         fwrite($buka,'test'.$user);
        
        if(!$user){
           return response()->json([
                'error' => true,
                'code' => 403,
                'message' => "Invalid Token"
           ]);
        }
        
        return response()->json([
              'error' => false,
              'code' => 200,
              'message' => "Success",
              'data' => [
                "name" => $user->name,
                "telp" => $user->no_wa,
                "name" => $user->name,
                'membership' => $user->role,
                'balance' => $user->balance
              ]
        ]);
        
   }
   
   public function product(Request $request)
   {
     
        $bearerToken = $request->bearerToken();

        if(!$bearerToken) {
           
           return response()->json([
                'error' => true,
                'code' => 403,
                'message' => "Access Token is required"
           ]);
        } 
        
        
        $user = \App\Models\User::where('api_key', $bearerToken)->first();
        
        if(!$user){
            
           return response()->json([
                'error' => true,
                'code' => 403,
                'message' => "Invalid Token"
           ]);
        }
        
       $product = \App\Models\Kategori::all();
       
       
       foreach($product as $p){
           $list[] = [
              "code" => $p->kode,
              "name" => $p->nama,
              "is_active" =>  ($p->status == 'active' ? true : false),  
           ];
       }
       return response()->json([
           'error' => false,
           "code" => 200,
           "message" => "Success",
           'data' => $list
      ]);
       
        
   }
   
   
   public function listVariant(Request $request)
   {
     
        $bearerToken = $request->bearerToken();

        if(!$bearerToken) {
           
           return response()->json([
                'error' => true,
                'code' => 403,
                'message' => "Access Token is required"
           ]);
        } 
        
        $user = \App\Models\User::where('api_key', $bearerToken)->first();
        
        
        if(!$user){
           return response()->json([
                'error' => true,
                'code' => 403,
                'message' => "Invalid Token"
           ]);
            
        }
        
       $json = $request->getContent();
       
       $data = json_decode($json);
       
       if(empty($data)){
           return response()->json([
              "error" => true,
              "code" => 404,
              "message" => "Code Not Found"
          ]);
           
       }
       
       $product = \App\Models\Kategori::where('kode', $data->code)->first();
       
       if(!$product){
           return response()->json([
              "error" => true,
              "code" => 404,
              "message" => "Code Not Found"
          ]);
           
       }
       
       $service = \App\Models\Layanan::where('kategori_id', $product->id)->get();
       
        $list = [];
       foreach($service as $s){
           
            if ($user->role == 'Platinum') {
                $harga = $s->harga_platinum;
            } elseif ($user->role == 'Gold') {
                $harga = $s->harga_gold;
            } elseif ($user->role == 'Member') {
                $harga = $s->harga_member;
            } else {
                $harga = $s->harga;
            }
            
           
           $list[] = [
                "id" => $s->id,
                "code" => $s->provider_id,
                "name" => $s->layanan,
                "is_active" => ($s->status == 'available' ? 'active' : 'inactive'),
                "price" => $harga,
            ];
       }
       return response()->json([
           'error' => false,
           "code" => 200,
           "message" => "Success",
           'data' => $list
      ]);
   }
   
   
   public function order(Request $request)
   {
       
       $bearerToken = $request->bearerToken();

        if(!$bearerToken) {
           return response()->json([
                'error' => true,
                'code' => 403,
                'message' => "Access Token is required"
           ]);
           
        } 
        
        
        $user = \App\Models\User::where('api_key', $bearerToken)->first();
        
        if(!$user){
            
           return response()->json([
                'error' => true,
                'code' => 403,
                'message' => "Invalid Token"
           ]);
            
        }
        
       $json = $request->getContent();
       $data = json_decode($json);
       
       if(empty($data)){
           return response()->json([
              "error" => true,
              "code" => 400,
              "message" => "Please Fill in All The Required Data"
          ]);
           
       }
       
       
       if(empty($data->code)){
          return response()->json([
              "error" => true,
              "code" => 400,
              "message" => "Please Fill in All The Required Data"
          ]);
           
       }
       
       if(empty($data->referenceNumber)){
          return response()->json([
              "error" => true,
              "code" => 400,
              "message" => "Please Fill in All The Required Data"
          ]);
           
       }
       
       if(empty($data->data)){
          return response()->json([
              "error" => true,
              "code" => 400,
              "message" => "Please Fill in All The Required Data"
          ]);
           
       }
       
       
       $service = \App\Models\Layanan::where('provider_id', $data->code)->first();
       
       if(!$service){
           return response()->json([
              "error" => true,
              "code" => 404,
              "message" => "Code Not Found"
          ]);
           
       }
       
       
        if ($user->role == 'Platinum') {
           $harga = $service->harga_platinum;
        } elseif ($user->role == 'Gold') {
            $harga = $service->harga_gold;
        } elseif ($user->role == 'Member') {
            $harga = $service->harga_member;
        } else {
            $harga = $service->harga;
        }
       
       
       if($user->balance < $harga){
           return response()->json([
              "error" => true,
              "code" => 400,
              "message" => "Your Balance is Insufficient"
          ]);
           
       }
      
       if (strpos($data->data, '|') !== false) {
            // Jika data yang diterima mengandung karakter '|', lakukan explode
            $datagame = explode('|', $data->data);
        } else {
            // Jika data yang diterima hanya satu, gunakan data tersebut tanpa explode
            $datagame = array($data->data);
        }
       $unik = date('Hs');
       $kode_unik = substr(str_shuffle(1234567890),0,3);
       $order_id = 'WEJIZY-RAPI'.$unik.$kode_unik;
       
        if($service->provider == "digiflazz"){
            $digi = new DigiFlazzController;
            $random_part = mt_rand(100000, 999999);
            $provider_order_id = 'WEJIZY-RAPID'. $random_part;
            $order = $digi->order($datagame[0], $datagame[1], $service->provider_id, $provider_order_id);

            if ($order['data']['status'] == "Pending" || $order['data']['status'] == "Sukses") {
                $order['status'] = true;
            } else {
                $order['status'] = false;
            }   
        } else if ($service->provider == "moogold") {
                $moo = new MoogoldController();
                $random_part = mt_rand(100000, 999999);
                $provider_order_id = 'WJMG-RAPID' . $random_part;
            
                $order = $moo->order($datagame[0], $service->provider_id, $provider_order_id, $datagame[1]);
            
                 if(isset($order['status'])){
                        $provider_order_id = $order['order_id'];
                        $order['status'] = true;
                    }else{
                        $order['status'] = false;
                    }
        } else if($service->provider == "vip"){
                    $vip = new VipResellerController;
                    $order = $vip->order($datagame[0], $datagame[1] ?? null, $service->provider_id);
                    
                    if(($order['result'] ?? false) === true){
                        $statusMeta = VipResellerController::normalizeStatusMeta($order['data']['status'] ?? null);
                        $order['data']['status'] = $order['result'];
                        $order['transactionId'] = $order['data']['trxid'] ?? null;
                        $order['provider_status'] = $statusMeta['internal_status'];
                    }else{
                        $order['data']['status'] = false;
                    }
                }else if($service->provider == "apigames"){
                    $provider_order_id = rand(1, 10000);
                    $apigames = new ApiGamesController;
                    $order = $apigames->order($datagame[0], $datagame[1] ?? null, $service->provider_id, $provider_order_id);
    
                    if($order['data']['status'] == "Sukses"){
                        $order['transactionId'] = $provider_order_id;
                        $order['data']['status'] = true;
                    }else{
                        $order['data']['status'] = false;
                    }
                } else if ($service->provider == "gameshop") {
                    $gameshop =  new GameShopProvider;
                    $random_part = mt_rand(100000, 999999);
                    $provider_order_id = 'WJGS-RAPI' . $random_part;
                    $order = $gameshop->order($datagame[0], $service->provider_id, $provider_order_id, $datagame[1]);
                    Log::info('callback gameshop ' . json_encode($order));
                    if(isset($order['data']['order_no'])){
                        $provider_order_id = $order['data']['order_no'];
                        $order['status'] = true;
                    }else{
                        $order['status'] = false;
                    }
                } else if ($service->provider == "strleyashop") {
                    $strleyashop =  new StrleyaShopProvider;
                    $random_part = mt_rand(100000, 999999);
                    $provider_order_id = 'WJSS-RAPI' . $random_part;
                    $order = $strleyashop->order($datagame[0], $service->provider_id, $provider_order_id, $datagame[1]);
                    Log::info('callback strleyashop ' . json_encode($order));
                    if(isset($order['order_details']['bot_order_id'])){
                        $provider_order_id = $order['order_details']['bot_order_id'];
                        $order['status'] = true;
                    }else{
                        $order['status'] = false;
                    }
                } else if ($service->provider == "yezzpay") {
                    $yezzpay =  new YezzpayProvider;
                    $random_part = mt_rand(100000, 999999);
                    $provider_order_id = strtoupper(str_replace('.', '', uniqid('ACID-YEZZPAY', true)));
                    $order = $yezzpay->order($datagame[0], $service->provider_id, $provider_order_id, $datagame[1]);
                    Log::info('callback yezzpay ' . json_encode($order));
                    if(isset($order['data']['trx_id'])){
                        $provider_order_id = $provider_order_id;
                        $order['status'] = true;
                    }else{
                        $order['status'] = false;
                    }
                } else if ($service->provider == "elitedias") {
                    $elitedias =  new EliteDiasProvider;
                    $random_part = mt_rand(100000, 999999);
                    $provider_order_id = 'WJED-RAPI' . $random_part;
                    $order = $elitedias->order($datagame[0], $service->provider_id, $provider_order_id, $datagame[1]);
                    Log::info('callback elitedias ' . json_encode($order));
                    if(isset($order['order_id'])){
                        $provider_order_id = $order['order_id'];
                        $order['status'] = true;
                    }else{
                        $order['status'] = false;
                    }
            
        } else if($service->provider == "joki"){
            $order['status'] = true;
        }
        

        
        if($order['status']){
            
            $user->update([
                'balance' => $user->balance - $harga
            ]);
            
            $pembelian = new Pembelian();
            $pembelian->username = $user->username;
            $pembelian->order_id = $order_id;
            $pembelian->user_id = $datagame[0];
            $pembelian->zone = isset($datagame[1]) ? $datagame[1] : null;
            $pembelian->layanan = $service->layanan;
            $pembelian->harga = $harga;
            $pembelian->profit = $harga * ENV("MARGIN_PROFIT");
            $pembelian->status = 'Sukses';
            $pembelian->provider_order_id = $provider_order_id ? $provider_order_id : "";
            $pembelian->log = json_encode($order);
            $pembelian->tipe_transaksi = 'game';
            $pembelian->save();

            $pembayaran = new Pembayaran();
            $pembayaran->order_id = $order_id;
            $pembayaran->harga = $harga;
            $pembayaran->no_pembayaran = "SALDO";
            $pembayaran->no_pembeli = $user->no_wa;
            $pembayaran->status = 'Lunas';
            $pembayaran->metode = 'SALDO';
            $pembayaran->reference = $data->referenceNumber;
            $pembayaran->expired_at = null;
            $pembayaran->save();  
            
            return response()->json([
              "error" => false,
              "code" => 200,
              "message" => "Success",
              "data" => [
                "invoiceNumber" => $order_id,
                "status" => 'Success',
              ]
            ]);
        }else{
            return response()->json([
              "error" => true,
              "code" => 400,
              "message" => "Failed" 
            ]);
        }
   }
   
   
   public function statusOrder(Request $request, $invoice)
    {
        $bearerToken = $request->bearerToken();
    
        if (!$bearerToken) {
            return response()->json([
                'error'   => true,
                'code'    => 403,
                'message' => "Access Token is required"
            ]);
        }
    
        $user = \App\Models\User::where('api_key', $bearerToken)->first();
    
        if (!$user) {
            return response()->json([
                'error'   => true,
                'code'    => 403,
                'message' => "Invalid Token"
            ]);
        }
    
        $cek = Pembelian::where('order_id', $invoice)->first();
    
        if (!$cek) {
            return response()->json([
                'error'   => true,
                'code'    => 404,
                'message' => "Invoice Not Found"
            ]);
        }
    
        $statusCode = PembelianStatus::apiStatusCode($cek->status);
    
        return response()->json([
            "error"   => false,
            "code"    => 200,
            "message" => "Success",
            "data"    => [
                "invoiceNumber" => $cek->order_id,
                "productName"   => $cek->layanan,
                "userData"      => $cek->user_id . '|' . $cek->zone,
                "statusCode"    => $statusCode,
                "sn"            => $cek->keterangan_sn,
                "keteranganSn"  => $cek->keterangan_sn,
            ]
        ]);
    }
}
