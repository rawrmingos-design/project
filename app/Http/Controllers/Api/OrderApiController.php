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
use App\Models\ProviderPath;
use App\Models\ResellerIntegration;
use App\Support\PembelianStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        
        
        $user = $request->attributes->get('api_user');

        if (! $user instanceof \App\Models\User) {
            $user = \App\Models\User::where('api_key', $bearerToken)->first();
        }
        
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
           $route = app(\App\Services\ProviderRoutingService::class)->findBestProvider($s);
           $variantCode = trim((string) ($route['sku'] ?? $s->provider_id));
           $providerCode = strtolower(trim((string) ($route['provider_code'] ?? $s->provider)));
           
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
                "code" => $variantCode,
                "name" => $s->layanan,
                "provider" => $providerCode !== '' ? $providerCode : $s->provider,
                "is_active" => ($s->status == 'available' && $variantCode !== '' ? 'active' : 'inactive'),
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
       
       
       $target = $this->resolveOrderTargetByExternalCode((string) $data->code);
       $service = $target['service'];
       $providerRoute = $target['route'];
       $modalPrice = $target['modal_price'];
       
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
       $providerCode = strtolower(trim((string) ($providerRoute['provider_code'] ?? $service->provider)));
       $providerSku = trim((string) ($providerRoute['sku'] ?? $service->provider_id));
       $credentials = $providerRoute['credentials'] ?? [];
       $resolvedOrderStatus = 'Pending';
       $provider_order_id = '';
       $providerReference = $order_id;

       if($providerCode == "digiflazz"){
            $digi = new DigiFlazzController($credentials);
            $random_part = mt_rand(100000, 999999);
            $provider_order_id = 'WEJIZY-RAPID'. $random_part;
            $order = $digi->order($datagame[0], $datagame[1], $providerSku, $provider_order_id);

            if ($order['data']['status'] == "Pending" || $order['data']['status'] == "Sukses") {
                $order['status'] = true;
            } else {
                $order['status'] = false;
            }
            $resolvedOrderStatus = PembelianStatus::preferredDatabaseLabel($order['data']['status'] ?? 'Pending');
        } else if ($providerCode == "moogold") {
                $moo = new MoogoldController();
                $random_part = mt_rand(100000, 999999);
                $provider_order_id = 'WJMG-RAPID' . $random_part;
            
                $order = $moo->order($datagame[0], $providerSku, $provider_order_id, $datagame[1]);
            
                 if(isset($order['status'])){
                        $provider_order_id = $order['order_id'];
                        $order['status'] = true;
                    }else{
                        $order['status'] = false;
                    }
                 $resolvedOrderStatus = 'Pending';
        } else if($providerCode == "vip" || $providerCode == "vip_reseller"){
                    $vip = new VipResellerController($credentials);
                    $order = $vip->order($datagame[0], $datagame[1] ?? null, $providerSku);
                    
                    if(($order['result'] ?? false) === true){
                        $statusMeta = VipResellerController::normalizeStatusMeta($order['data']['status'] ?? null);
                        $order['data']['status'] = $order['result'];
                        $order['transactionId'] = $order['data']['trxid'] ?? null;
                        $order['provider_status'] = $statusMeta['internal_status'];
                        $provider_order_id = $order['data']['trxid'] ?? '';
                        $resolvedOrderStatus = PembelianStatus::preferredDatabaseLabel($statusMeta['internal_status']);
                    }else{
                        $order['data']['status'] = false;
                    }
                }else if($providerCode == "apigames"){
                    $apigames = new ApiGamesController($credentials);
                    $provider_order_id = $providerReference;
                    $order = $apigames->order($datagame[0], $datagame[1] ?? null, $providerSku, $providerReference);

                    $statusMeta = ApiGamesController::normalizeStatusMeta($order['data']['status'] ?? null);

                    if(($order['result'] ?? false) === true){
                        $order['transactionId'] = $order['data']['trx_id'] ?? $providerReference;
                        $order['provider_status'] = $statusMeta['internal_status'];
                        $order['provider_order_id'] = $order['data']['trx_id'] ?? null;
                        $provider_order_id = $order['data']['trx_id'] ?? $providerReference;
                        $order['data']['status'] = true;
                        $resolvedOrderStatus = PembelianStatus::preferredDatabaseLabel($statusMeta['internal_status']);
                    }else if (($order['transport_error'] ?? false) === true){
                        $order['provider_status'] = 'Pending';
                        $order['data']['status'] = true;
                        $resolvedOrderStatus = 'Pending';
                    }else{
                        $order['data']['status'] = false;
                    }
                }else if($providerCode == "bangjeff"){
                    $bangjeff = new BangJeffController($credentials);
                    $requestData = [['name' => 'ID', 'value' => $datagame[0]]];
                    if (!empty($datagame[1] ?? null)) {
                        $requestData[] = ['name' => 'Server', 'value' => $datagame[1]];
                    }

                    $order = $bangjeff->order($providerSku, $providerReference, 1, $requestData);
                    $isSuccess = (($order['error'] ?? null) === false) || (($order['rc'] ?? null) === '00');
                    $statusCode = strtoupper((string) ($order['data']['statusCode'] ?? 'PROCESSING'));

                    if ($isSuccess) {
                        $provider_order_id = $order['data']['invoiceNumber'] ?? $providerReference;
                        $order['status'] = true;
                        $resolvedOrderStatus = $statusCode === 'SUCCESS'
                            ? 'Sukses'
                            : ($statusCode === 'REFUNDED' ? 'Gagal' : 'Pending');
                    } else {
                        $order['status'] = false;
                    }
                }else if($providerCode == "topupedia"){
                    $topupedia = new TopupediaController($credentials);
                    $requestData = [['name' => 'ID', 'value' => $datagame[0]]];
                    if (!empty($datagame[1] ?? null)) {
                        $requestData[] = ['name' => 'Server', 'value' => $datagame[1]];
                    }

                    $order = $topupedia->order($providerSku, $providerReference, 1, $requestData);

                    if (($order['error'] ?? true) === false) {
                        $provider_order_id = $order['data']['invoiceNumber'] ?? $providerReference;
                        $order['status'] = true;
                        $resolvedOrderStatus = 'Pending';
                    } else {
                        $order['status'] = false;
                    }
                } else if ($providerCode == "gameshop") {
                    $gameshop =  new GameShopProvider;
                    $random_part = mt_rand(100000, 999999);
                    $provider_order_id = 'WJGS-RAPI' . $random_part;
                    $order = $gameshop->order($datagame[0], $providerSku, $provider_order_id, $datagame[1]);
                    Log::info('callback gameshop ' . json_encode($order));
                    if(isset($order['data']['order_no'])){
                        $provider_order_id = $order['data']['order_no'];
                        $order['status'] = true;
                    }else{
                        $order['status'] = false;
                    }
                    $resolvedOrderStatus = 'Pending';
                } else if ($providerCode == "strleyashop") {
                    $strleyashop =  new StrleyaShopProvider;
                    $random_part = mt_rand(100000, 999999);
                    $provider_order_id = 'WJSS-RAPI' . $random_part;
                    $order = $strleyashop->order($datagame[0], $providerSku, $provider_order_id, $datagame[1]);
                    Log::info('callback strleyashop ' . json_encode($order));
                    if(isset($order['order_details']['bot_order_id'])){
                        $provider_order_id = $order['order_details']['bot_order_id'];
                        $order['status'] = true;
                    }else{
                        $order['status'] = false;
                    }
                    $resolvedOrderStatus = 'Pending';
                } else if ($providerCode == "yezzpay") {
                    $yezzpay =  new YezzpayProvider;
                    $random_part = mt_rand(100000, 999999);
                    $provider_order_id = strtoupper(str_replace('.', '', uniqid('ACID-YEZZPAY', true)));
                    $order = $yezzpay->order($datagame[0], $providerSku, $provider_order_id, $datagame[1]);
                    Log::info('callback yezzpay ' . json_encode($order));
                    if(isset($order['data']['trx_id'])){
                        $provider_order_id = $provider_order_id;
                        $order['status'] = true;
                    }else{
                        $order['status'] = false;
                    }
                    $resolvedOrderStatus = 'Pending';
                } else if ($providerCode == "elitedias") {
                    $elitedias =  new EliteDiasProvider;
                    $random_part = mt_rand(100000, 999999);
                    $provider_order_id = 'WJED-RAPI' . $random_part;
                    $order = $elitedias->order($datagame[0], $providerSku, $provider_order_id, $datagame[1]);
                    Log::info('callback elitedias ' . json_encode($order));
                    if(isset($order['order_id'])){
                        $provider_order_id = $order['order_id'];
                        $order['status'] = true;
                    }else{
                        $order['status'] = false;
                    }
                    $resolvedOrderStatus = 'Pending';
        } else if($providerCode == "joki" || $providerCode == "manual"){
            $order['status'] = true;
            $resolvedOrderStatus = 'Sukses';
        }
        

        
        if($order['status']){
            $integration = $request->attributes->get('live_reseller_integration');

            if (! $integration instanceof ResellerIntegration) {
                $integration = null;
            }

            DB::transaction(function () use (
                $user,
                $harga,
                $order_id,
                $datagame,
                $service,
                $modalPrice,
                $resolvedOrderStatus,
                $providerCode,
                $providerSku,
                $provider_order_id,
                $order,
                $data,
                $integration
            ): void {
                $user->update([
                    'balance' => $user->balance - $harga
                ]);

                $pembelian = new Pembelian();
                $pembelian->username = $user->username;
                $pembelian->reseller_integration_id = $integration?->getKey();
                $pembelian->order_id = $order_id;
                $pembelian->user_id = $datagame[0];
                $pembelian->zone = isset($datagame[1]) ? $datagame[1] : null;
                $pembelian->layanan = $service->layanan;
                $pembelian->harga = $harga;
                $pembelian->profit = is_numeric($modalPrice)
                    ? max(0, (int) round($harga - (float) $modalPrice))
                    : (int) round($harga * ENV("MARGIN_PROFIT"));
                $pembelian->status = $resolvedOrderStatus;
                $pembelian->active_layanan_id = $service->id;
                $pembelian->active_provider_code = $providerCode;
                $pembelian->active_provider_sku = $providerSku;
                $pembelian->provider_order_id = $provider_order_id ? $provider_order_id : "";
                $pembelian->log = json_encode($order);
                $pembelian->traffic_source = $integration ? 'reseller_h2h' : $pembelian->traffic_source;
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
            });
            
            return response()->json([
              "error" => false,
              "code" => 200,
              "message" => "Success",
              "data" => [
                "invoiceNumber" => $order_id,
                "status" => PembelianStatus::apiStatusCode($resolvedOrderStatus),
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

    private function resolveOrderTargetByExternalCode(string $requestedCode): array
    {
        $requestedCode = trim($requestedCode);
        $routingService = app(\App\Services\ProviderRoutingService::class);

        $service = \App\Models\Layanan::where('provider_id', $requestedCode)->first();

        if ($service) {
            $route = $routingService->resolveExplicitProvider(
                strtolower(trim((string) $service->provider)),
                trim((string) $service->provider_id)
            );

            return [
                'service' => $service,
                'route' => $route,
                'modal_price' => is_numeric($service->harga ?? null) ? (float) $service->harga : null,
            ];
        }

        $path = ProviderPath::query()
            ->with('layanan')
            ->where('provider_sku', $requestedCode)
            ->where('status', 'available')
            ->orderBy('priority')
            ->orderBy('modal_price')
            ->first();

        if ($path && $path->layanan) {
            return [
                'service' => $path->layanan,
                'route' => $routingService->resolveExplicitProvider(
                    strtolower(trim((string) $path->provider_code)),
                    trim((string) $path->provider_sku)
                ),
                'modal_price' => is_numeric($path->modal_price) ? (float) $path->modal_price : null,
            ];
        }

        return [
            'service' => null,
            'route' => null,
            'modal_price' => null,
        ];
    }
}
