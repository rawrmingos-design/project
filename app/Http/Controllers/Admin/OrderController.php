<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pembelian;
use App\Models\Layanan;
use App\Models\Pembayaran;
use App\Http\Controllers\digiFlazzController;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use App\Http\Controllers\provider\BangJeffController;
use App\Http\Controllers\provider\MoogoldController;
use App\Http\Controllers\provider\VipResellerController;
use App\Http\Controllers\provider\ApiGamesController;
use App\Http\Controllers\provider\TopupediaController;
use App\Libraries\Provider\ElitediasProvider;
use App\Libraries\Provider\GameShopProvider;
use App\Libraries\Provider\StrleyaShopProvider;
use App\Libraries\Provider\YezzpayProvider;

class OrderController extends Controller
{
    public function create()
    {
        $data = Pembelian::orderBy('pembelians.id', 'desc')
            ->join('pembayarans', 'pembelians.order_id', '=', 'pembayarans.order_id')
            ->leftJoin('data_joki', 'pembelians.order_id', '=', 'data_joki.order_id') 
            ->leftJoin('methods', DB::raw('pembayarans.metode COLLATE utf8mb4_unicode_ci'), '=', DB::raw('methods.code COLLATE utf8mb4_unicode_ci'))
            ->select(
                'pembelians.*',
                'pembayarans.status AS status_pembayaran',
                'pembayarans.metode',
                'pembayarans.no_pembeli AS nomor_hp',
                'pembelians.username AS username',
                'pembelians.profit AS profit',
                'data_joki.nickname_joki',
                'methods.name AS metode_name'
            )
            ->where('pembayarans.metode', '!=', 'MANUAL')
            ->get();

        return view('components.admin.transaction', ['data' => $data]);
    }
    
    public function reorder($order_id)
    {
        $ref = $order_id;

        // Ambil data invoice dan pembelian berdasarkan order_id
        $invoice = Pembayaran::where('order_id', $ref)->first();
        $pembelian = Pembelian::where('order_id', $order_id)->first();

        // Cek apakah status pembelian sudah "Proses" atau "Sukses"
        if ($pembelian->status == 'Proses' || $pembelian->status == 'Sukses') {
            return back()->with('info', 'Pesanan sudah diproses sebelumnya dengan ID #' . $order_id);
        }

        $dataLayanan = Layanan::where('layanan', $pembelian->layanan)->first();

        $uid = $pembelian->user_id;
        $zone = ($pembelian->zone !== null) ? $pembelian->zone : null;
        $provider_id = $dataLayanan->provider_id;
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
                       }
                    } else if ($dataLayanan->provider == "gameshop") {
                        $gameshop =  new GameShopProvider;
                        $random_part = mt_rand(100000, 999999);
                        $provider_order_id = 'REFF-WEJIZY' . $random_part;
                        $order = $gameshop->order($uid, $provider_id, $provider_order_id, $zone);
                        Log::info('callback gameshop ' . json_encode($order));
                        if(isset($order['data']['order_no'])){
                            $provider_order_id = $order['data']['order_no'];
                            $order['data']['status'] = true;
                        }else{
                            $order['data']['status'] = false;
                        }
                    } else if ($dataLayanan->provider == "strleyashop") {
                        $strleyashop =  new StrleyaShopProvider;
                        $random_part = mt_rand(100000, 999999);
                        $provider_order_id = 'REFF-WEJIZY' . $random_part;
                        $order = $strleyashop->order($uid, $provider_id, $provider_order_id, $zone);
                        Log::info('callback strleyashop ' . json_encode($order));
                        if(isset($order['data']['order_details']['bot_order_id'])){
                            $provider_order_id = $order['order_details']['bot_order_id'];
                            $order['data']['status'] = true;
                        }else{
                            $order['data']['status'] = false;
                        }
                    } else if ($dataLayanan->provider == "yezzpay") {
                        $yezzpay =  new YezzpayProvider;
                        $random_part = mt_rand(100000, 999999);
                        $provider_order_id = strtoupper(str_replace('.', '', uniqid('ACID-YEZZPAY', true)));
                        $order = $yezzpay->order($uid, $provider_id, $provider_order_id, $zone);
                        Log::info('callback yezzpay ' . json_encode($order));
                        if(isset($order['data']['trx_id'])){
                            $provider_order_id = $provider_order_id;
                            $order['data']['status'] = true;
                        }else{
                            $order['data']['status'] = false;
                        }
                    } else if ($dataLayanan->provider == "elitedias") {
                        $elitedias =  new ElitediasProvider;
                        $random_part = mt_rand(100000, 999999);
                        $provider_order_id = strtoupper(str_replace('.', '', uniqid('ACID', true)));
                        $order = $elitedias->order($uid, $provider_id, $provider_order_id, $zone);
                        Log::info('callback elitedias ' . json_encode($order));
                        if(isset($order['order_id'])){
                            $provider_order_id = $provider_order_id;
                            $order['data']['status'] = true;
                        }else{
                            $order['data']['status'] = false;
                        }
                    } elseif ($dataLayanan->provider == "joki") {
            $provider_order_id = '';
            $order['data']['status'] = true;
        }

        if ($order['data']['status']) {
            if ($invoice) {
                $invoice->update(['status' => 'Lunas']);
            }

            $pembelian->update([
                'provider_order_id' => isset($provider_order_id) ? $provider_order_id : 0,
                'status' => 'Proses',
                'log' => json_encode($order)
            ]);

            // Kirim pesan berdasarkan status
            if ($dataLayanan->provider != 'joki') {
                $pesanPembeli = 
                    "*Pembayaran Berhasil*\n\n" .
                    "No Invoice: *$order_id*\n" .
                    "Layanan : *$pembelian->layanan*\n" .
                    "ID : *$pembelian->user_id*\n" .
                    "Server : *$pembelian->zone*\n" .
                    "Nickname : *$pembelian->nickname*\n" .
                    "Harga : *Rp. " . number_format($pembelian->harga, 0, '.', ',') . "*\n" .
                    "Status Pembelian: *Diproses*\n" .
                    "Estimasi Proses: *1-5 Menit Max 24 Jam*\n\n" .
                    "INI ADALAH PESAN OTOMATIS";

                $this->msg($pembelian->no_pembeli, $pesanPembeli);
            } else {
                $pesanJoki =
                    "*Pembayaran Berhasil*\n\n" .
                    "No Invoice: *$order_id*\n" .
                    "Layanan: *$pembelian->layanan*\n" .
                    "ID: *$pembelian->user_id*\n" .
                    "Server: *$pembelian->zone*\n" .
                    "Nickname: *$pembelian->nickname*\n" .
                    "Harga: *Rp. " . number_format($pembelian->harga, 0, '.', ',') . "*\n" .
                    "Status Pembelian: *Diproses*\n" .
                    "Joki akan segera memulai permainan Anda.\n\n" .
                    "INI ADALAH PESAN OTOMATIS";

                $this->msg($pembelian->no_pembeli, $pesanJoki);
            }

        } else { // jika pembelian gagal
            $pembelian->update([
                'status' => 'Batal',
                'log' => json_encode($order)
            ]);
        }

        if ($invoice !== null) {
            $invoice->update(['status' => 'Lunas']);
        }

        return back()->with('success', 'Berhasil melakukan reprocess dengan ID #' . $order_id);
    }

    public function update($order_id, $status)
    {
        Pembelian::where('order_id', $order_id)->update([
            'status' => $status,
            'updated_at' => now()
        ]);
        
        // Kirim pesan saat status diperbarui menjadi 'Sukses'
        if ($status == 'Sukses') {
            $pembelian = Pembelian::where('order_id', $order_id)->first();
            if ($pembelian && $pembelian->tipe_transaksi != 'joki') {
                $pesanSukses =
                    "*Diamond Berhasil Dikirim*\n\n" .
                    "No Invoice: *$order_id*\n" .
                    "Layanan: *$pembelian->layanan*\n" .
                    "ID: *$pembelian->user_id*\n" .
                    "Server: *$pembelian->zone*\n" .
                    "Nickname: *$pembelian->nickname*\n" .
                    "Harga: *Rp. " . number_format($pembelian->harga, 0, '.', ',') . "*\n" .
                    "Status Pembelian: *Success*\n\n" .
                    "Terima kasih telah bertransaksi dengan kami.";

                $this->msg($pembelian->no_pembeli, $pesanSukses);
            }
        }

        return back()->with('success', 'Berhasil memperbarui status ID #' . $order_id);        
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
