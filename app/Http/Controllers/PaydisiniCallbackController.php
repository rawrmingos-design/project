<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Models\Layanan;
use App\Models\Deposit;
use App\Models\User;
use App\Http\Controllers\digiFlazzController;
use App\Http\Controllers\provider\BangJeffController;
use App\Http\Controllers\provider\TopupediaController;
use App\Http\Controllers\provider\VipResellerController;
use App\Http\Controllers\provider\MoogoldController;
use App\Http\Controllers\provider\ApiGamesController;

class PaydisiniCallbackController extends Controller
{
    private $apiKey;
    public function __construct()

    {

        $this->apiKey = \DB::table('setting_webs')->where('id',1)->first()->paydisini_apikey;
    }
    
    public function callbackTransaction(Request $request)
    {
        // Validasi IP
        // if ($request->ip() !== '194.233.92.170') {
        //     return response()->json(['success' => false, 'message' => 'Invalid IP address'], 400);
        // }

        // Ambil data dari request
        $key = $request->input('key');
        $payId = $request->input('pay_id');
        $uniqueCode = $request->input('unique_code');
        $status = $request->input('status');
        $signature = $request->input('signature');

        // Validasi API Key
        if ($key !== $this->apiKey) {
            return response()->json(['success' => false, 'message' => 'Invalid API Key'], 400);
        }

        // Validasi signature
        $expectedSignature = md5($this->apiKey . $uniqueCode . 'CallbackStatus');
        if ($signature !== $expectedSignature) {
            return response()->json(['success' => false, 'message' => 'Invalid signature'], 400);
        }

        // Cek transaksi
        $transaction = Pembayaran::where('order_id', $uniqueCode)
            ->where('status', 'Belum Lunas')
            ->first();

        if (!$transaction) {
            return response()->json(['success' => false, 'message' => 'Transaction not found'], 404);
        }

        // Proses status pembayaran
        if ($status === 'Success') {
            $transaction->update(['status' => 'Lunas']);
            $pembelian = Pembelian::where('order_id', $uniqueCode)->first();

            if ($pembelian) {
                $pembelian->update(['status' => 'Sukses']);
                $this->handleSuccess($pembelian, $transaction);
            } else {
                $deposit = Deposit::where('order_id', $uniqueCode)->first();
                if ($deposit) {
                    $user = User::where('username', $deposit->username)->first();
                    if ($user) {
                        $user->update(['balance' => $user->balance + $deposit->jumlah]);
                        $deposit->update(['status' => 'Success']);
                    }
                }
            }
            return response()->json(['success' => true]);
        } elseif ($status === 'Canceled') {
            $transaction->update(['status' => 'Expired']);
            $pembelian = Pembelian::where('order_id', $uniqueCode)->first();
            if ($pembelian) {
                $pembelian->update(['status' => 'Expired']);
            } else {
                $deposit = Deposit::where('order_id', $uniqueCode)->first();
                if ($deposit) {
                    $deposit->update(['status' => 'Expired']);
                }
            }
            return response()->json(['success' => true]);
        } else {
            return response()->json(['success' => false, 'message' => 'Invalid status'], 400);
        }
    }

    private function handleSuccess($pembelian, $transaction)
    {
        $layanan = Layanan::where('layanan', $pembelian->layanan)->first();
        if ($layanan) {
            $provider = $layanan->provider;
            $user_id = $pembelian->user_id;
            $zone = $pembelian->zone;
            $provider_id = $layanan->provider_id;
            $order = [];

            // Proses order berdasarkan provider
            
               if ($provider === "digiflazz") {
                $random_part = Str::random(18, '123456789');
                $provider_order_id = 'REFF-WEJIZY' . $random_part;
                $digiFlazz = new digiFlazzController;
                $order = $digiFlazz->order($user_id, $zone, $provider_id, $provider_order_id);

                if ($order['data']['status'] === "Pending" || $order['data']['status'] === "Sukses") {
                    $order['data']['status'] = true;
                    $order['transactionId'] = $provider_order_id;
                } else {
                    $order['data']['status'] = false;
                }
            } elseif ($provider === "bangjeff") {
                $bangjeff = new BangJeffController;
                
                $ttlpembelian = [
                    [
                        "name" => "id",
                        "value" => $user_id
                    ]
                ];

                if ($zone !== null) {
                    $ttlpembelian[] = [
                        "name" => "server",
                        "value" => $zone
                    ];
                }
                
                $order = $bangjeff->order($provider_id, $pembelian->order_id, 1, $ttlpembelian);

                if (!$order['error']) {
                    $order['transactionId'] = $order['data']['invoiceNumber'];
                    $order['data']['status'] = true;
                } else {
                    $order['data']['status'] = false;
                }
            } elseif ($provider === "topupedia") {
                $topupedia = new TopupediaController;
                
                $ttlpembelian = [
                    [
                        "name" => "id",
                        "value" => $user_id
                    ]
                ];

                if ($zone !== null) {
                    $ttlpembelian[] = [
                        "name" => "server",
                        "value" => $zone
                    ];
                }
                
                $order = $topupedia->order($provider_id, $pembelian->order_id, 1, $ttlpembelian);

                if (!$order['error']) {
                    $order['transactionId'] = $order['data']['invoiceNumber'];
                    $order['data']['status'] = true;
                } else {
                    $order['data']['status'] = false;
                }
            } else if ($dataLayanan->provider == "moogold") {
                        $moo = new MoogoldController();
                        $random_part = mt_rand(100000, 999999);
                        $provider_order_id = 'REFF-WJMG' . $random_part;
                        $order = $moo->order($uid, $provider_id, $provider_order_id, $zone);
                        Log::info('callback moogold', $order);
                        if(isset($order['data']['status'])){
                            $provider_order_id = $order['order_id'];
                            $order['data']['status'] = true;
                        }else{
                            $order['data']['status'] = false;
                        }
                    }else if($dataLayanan->provider == "vip"){
                    $vip = new VipResellerController;
                    $order = $vip->order($uid, $zone, $provider_id);
                    
                    if($order['result']){
                        $order['data']['status'] = $order['result'];
                        $order['transactionId'] = $order['data']['trxid'];
                    }else{
                        $order['data']['status'] = false;
                    }
                }else if($dataLayanan->provider == "apigames"){
                    $provider_order_id = rand(1, 10000);
                    $apigames = new ApiGamesController;
                    $order = $apigames->order($uid, $zone, $provider_id, $provider_order_id);
    
                    if($order['data']['status'] == "Sukses"){
                        $order['transactionId'] = $provider_order_id;
                        $order['data']['status'] = true;
                    }else{
                        $order['data']['status'] = false;
                    } elseif ($provider === "joki" || $provider === "jokigendong") {
                $provider_order_id = '';
                $order['data']['status'] = true;
            }

        // Cek status order dan perbarui pembelian
        if ($order['data']['status']) {
            if ($pembelian->tipe_transaksi !== 'joki') {
                // Penanganan jika tipe transaksi bukan joki
                $pembelian->update([
                    'provider_order_id' => isset($order['transactionId']) ? $order['transactionId'] : 0,
                    'status' => 'Sukses',
                    'log' => json_encode($order)
                ]);

                // Kirim pesan ke nomor WhatsApp
                $this->sendWhatsAppMessage($pembelian, $transaction);
            } else {
                // Penanganan jika tipe transaksi adalah joki
                $pembelian->update([
                    'status' => 'Proses',
                    'log' => json_encode($order)
                ]);

                // Kirim pesan khusus untuk transaksi joki, jika perlu
                $this->sendWhatsAppToJoki($pembelian, $transaction);
            }
        } else {
            $pembelian->update([
                'status' => 'Batal',
                'log' => json_encode($order)
            ]);
        }
    } else {
        Log::error('Service Tidak Ditemukan', ['layanan' => $pembelian->layanan]);
    }
    }

    private function sendWhatsAppMessage($pembelian, $transaction)
    {
        $pesanPembeli = 
            "*Pembayaran Kamu berhasil✨*\n\n" .
            "Terima kasih berikut detail transaksinya  :\n\n" .
            "No Invoice: *{$pembelian->order_id}*\n" .
            "Layanan: *{$pembelian->layanan}*\n" .
            "ID: *{$pembelian->user_id}*\n" .
            "Server: *{$pembelian->zone}*\n" .
            "Nickname: *{$pembelian->nickname}*\n\n" .
            "No Whatsapp: *{$transaction->no_pembeli}*\n" .
            "Harga: *Rp. " . number_format($transaction->harga, 0, '.', ',') . "*\n\n" .
            "Invoice : " . env("APP_URL") . "/id/invoices/{$pembelian->order_id}\n\n" .
            "*Ditunggu orderan selanjutnya! Terimakasih.*\n";

        $nomor = $transaction->no_pembeli; 
        $this->msg($nomor, $pesanPembeli);
    }
    private function sendWhatsAppToJoki($pembelian, $transaction)
    {
        $pesanPembeli = 
            "*⚔️ Halo, Pembayaran Kamu Sudah Berhasil! 🎉*\n\n" .
            "Terima kasih sudah memilih layanan kami, Siap-siap naik peringkat dan mendominasi Land of Dawn! Berikut detail transaksimu:\n\n" .
            "🧾 *No Invoice*: {$pembelian->order_id}\n" .
            "🛡️ *Layanan Joki*: {$pembelian->layanan}\n" .
            "📱 *No WhatsApp*: {$transaction->no_pembeli}\n" .
            "💸 *Total Bayar*: Rp. " . number_format($transaction->harga, 0, '.', ',') . "\n\n" .
            "📜 *Cek Invoice*: " . env("APP_URL") . "/id/invoices/{$pembelian->order_id}\n\n" .
            "🔥 *Catatan Penting*: Jangan lupa untuk save kontak admin kami,dan ikuti kami di media sosial biar nggak ketinggalan promo seru dan tips jagoan!\n\n" .
          
            "*Semoga Savage selalu menunggumu di pertandingan berikutnya! 💥🔥*\n";
    
        $nomor = $transaction->no_pembeli; 
        $this->msg($nomor, $pesanPembeli);
    }


    public function msg($nomor, $msg)
    {
        $api = \DB::table('setting_webs')->where('id', 1)->first();
        $apiUrl = 'https://api.fonnte.com/send';
        $token = $api->wa_key;
    
        $postData = [
            'target' => $nomor,
            'message' => $msg,
        ];
    
        $headers = [
            'Authorization: ' . $token,
        ];
    
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_HTTPHEADER => $headers,
        ]);
    
        $response = curl_exec($curl);
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    
        curl_close($curl);
    
        if ($statusCode === 200) {
            return [
                'success' => true,
                'message' => 'Message sent successfully',
                'response' => $response,
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to send message',
                'response' => $response,
            ];
        }
    }
}
