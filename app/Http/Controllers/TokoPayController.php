<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pembayaran;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\Log;

class TokoPayController extends Controller
{

    private $apiUrl;
    private $merchantId;
    private $secretKey;

    public function __construct()
    {
        $this->apiUrl = 'https://api.tokopay.id';
        $api = \DB::table('setting_webs')->where('id', 1)->first();
        $this->merchantId = $api->tokopay_merchant_id;
        $this->secretKey = $api->tokopay_secret_key;
    }
    
    public function createAdvanceOrder($ref_id, $channel, $jumlah, $nickname, $phone_number, $service){
        $merchantid = $this->merchantId;
        $secretkey = $this->secretKey;
        
        $formula = $merchantid . ":" . $secretkey . ":" . $ref_id;
        $signatureTrx = md5($formula);
        $data = [
            'merchant_id' => $merchantid,
            'kode_channel' => $channel,
            'reff_id' => $ref_id,
            'amount' => $jumlah,
            'customer_name' => "$nickname",
            'customer_email' => "$nickname@gmail.com",
            'customer_phone' => "$phone_number",
            'redirect_url' => "/id/pembelian/invoice/$ref_id",
            'expired_ts' => 0,
            'signature'=>$signatureTrx,
            'items'=> [
                [
                    'product_code'=>'-',
                    'name'=> $service,
                    'price'=>$jumlah,
                    'product_url'=>"/id/pembelian/invoice/$ref_id",
                    'image_url'=>"/id/pembelian/invoice/$ref_id"
                ]
            ]
        ];
        
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->apiUrl.'/v1/order',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>json_encode($data),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        
        return json_decode($response, true);
    }
    
     public function createOrder($nominal, $refId, $kodeChannel)
    {
        $mid = $this->merchantId;
        $secret = $this->secretKey;
        
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->apiUrl . "/v1/order?merchant=$mid&secret=$secret&ref_id=$refId&nominal=$nominal&metode=$kodeChannel",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));
        $response = curl_exec($curl);
        
        $buka= fopen(storage_path('logging.txt'), 'w');
        fwrite($buka,'test '.$response);
        curl_close($curl);
        return json_decode($response, true);
    }
    
       public function akun()
    {
        $merchantId = $this->merchantId;
        $secretKey = $this->secretKey;
    
        $signature = md5($merchantId . ":" . $secretKey);
        $url = $this->apiUrl . "/v1/merchant/balance?merchant={$merchantId}&signature={$signature}";
    
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));
    
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        $responseData = json_decode($response, true);
    
        if ($httpCode != 200 || !$responseData['status']) {
            return view('components.admin.payment.tokopay.akun', ['error' => $responseData['error_msg'] ?? 'Unknown error']);
        }
        return view('components.admin.payment.tokopay.akun', ['data' => $responseData['data']]);
    }
    
    public function tarikSaldo(Request $request)
    {
        $merchantid = $this->merchantId;
        $secretkey = $this->secretKey;
        $nominal = $request->input('nominal');
        $signature = md5($merchantid . ":" . $secretkey . ":" . $nominal);
    
        $data = [
            'merchant_id' => $merchantid,
            'nominal' => $nominal,
            'signature' => $signature
        ];
    
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->apiUrl . "/v1/tarik-saldo",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));
        
        $response = curl_exec($curl);
        curl_close($curl);
    
        Log::info('Tarik Saldo Response: ', ['response' => $response]);
    
        $result = json_decode($response, true);
    
        if ($result['status'] == 1 && $result['rc'] == 200) {
            Withdrawal::create([
                'rekening' => "{{ ENV(REK_NAME) }}",
                'total_transfer' => $nominal,
                'biaya_admin' => 5000, 
                'status' => 'Sukses',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
    
            $withdrawals = Withdrawal::all();
            
            return view('components.admin.payment.tokopay.tariksaldo')->with([
                'success' => $result['message'],
                'withdrawals' => $withdrawals
            ]);
        } else {
            $withdrawals = Withdrawal::all();
            
            return view('components.admin.payment.tokopay.tariksaldo')->with([
                'error' => $result['error_msg'],
                'withdrawals' => $withdrawals
            ]);
        }
    }
    

}
