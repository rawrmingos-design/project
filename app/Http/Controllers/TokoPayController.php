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
    }

    private function initializeCredentials(): void
    {
        if ($this->merchantId && $this->secretKey) {
            return;
        }

        $api = \DB::table('setting_webs')->where('id', 1)->first();
        $this->merchantId = $api->tokopay_merchant_id ?? null;
        $this->secretKey = $api->tokopay_secret_key ?? null;
    }
    
    public function createAdvanceOrder($ref_id, $channel, $jumlah, $nickname, $phone_number, $service){
        $this->initializeCredentials();

        $merchantid = $this->merchantId;
        $secretkey = $this->secretKey;
        
        $formula = $merchantid . ":" . $secretkey . ":" . $ref_id;
        $signatureTrx = md5($formula);

        // Pastikan URL redirect adalah URL absolut yang valid
        $appUrl = config('app.url', 'https://example.com');
        $redirectUrl = rtrim($appUrl, '/') . "/id/invoices/$ref_id";

        // Bangun URL produk (untuk items)
        $productUrl = rtrim($appUrl, '/') . "/id/invoices/$ref_id";
        $imageUrl   = rtrim($appUrl, '/') . "/assets/logo/logo.png";

        // Pastikan jumlah adalah integer
        $amount = (int) $jumlah;

        // Bersihkan nickname dari HTML entities (misal: &amp;#039; jadi ')
        // lalu strip karakter non-alphanumeric agar email valid
        $cleanNickname = html_entity_decode($nickname ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $cleanNickname = strip_tags($cleanNickname);
        $cleanNickname = trim($cleanNickname) ?: 'Customer';
        
        // Buat email yang valid dengan hanya menyimpan karakter aman
        $emailPrefix = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $cleanNickname)) ?: 'customer';
        $customerEmail = $emailPrefix . '@customer.com';

        $data = [
            'merchant_id'    => $merchantid,
            'kode_channel'   => $channel,
            'reff_id'        => $ref_id,
            'amount'         => $amount,
            'customer_name'  => $cleanNickname,
            'customer_email' => $customerEmail,
            'customer_phone' => $phone_number ?: '08000000000',
            'redirect_url'   => $redirectUrl,
            'expired_ts'     => now()->addHours(3)->timestamp,
            'signature'      => $signatureTrx,
            'items'          => [
                [
                    'product_code' => '-',
                    'name'         => $service,
                    'price'        => $amount,
                    'product_url'  => $productUrl,
                    'image_url'    => $imageUrl,
                ]
            ]
        ];
        
        Log::info('TokoPay createAdvanceOrder Request:', [
            'order_id'     => $ref_id,
            'channel'      => $channel,
            'amount'       => $amount,
            'redirect_url' => $redirectUrl,
            'request_body' => $data,
        ]);

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
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        
        Log::info('TokoPay createAdvanceOrder Response:', ['response' => $response, 'order_id' => $ref_id]);

        return json_decode($response, true);
    }
    
     public function createOrder($nominal, $refId, $kodeChannel)
    {
        $this->initializeCredentials();

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
        $this->initializeCredentials();

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
        $this->initializeCredentials();

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
