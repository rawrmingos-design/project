<?php

namespace App\Http\Controllers;

use App\Http\Controllers\provider\BangJeffController;
use App\Http\Controllers\provider\MoogoldController;
use App\Http\Controllers\provider\TopupediaController;
use App\Http\Contrlllers\provider\VipResellerController;
use App\Http\Controllers\provider\ApiGamesController;
use App\Libraries\Provider\GameShopProvider;
use App\Libraries\Provider\StrleyaShopProvider;
use App\Models\Deposit;
use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CallbackController extends Controller
{
    public function razerpay(Request $request)
    {
        $post = $request->all();
        $sec_key = env('RAZERPAY_SECRET_KEY');
        $nbcb = $post['nbcb'];
        $tranID = $post['tranID'];
        $orderid = $post['orderid'];
        $status = $post['status'];
        $domain = $post['domain'];
        $amount = $post['amount'];
        $currency = $post['currency'];
        $appcode = $post['appcode'];
        $paydate= $post['paydate'];
        $skey = $post['skey'];

        if ($nbcb != 1) abort(404);

        /***********************************************************
         *ToverifythedataintegritysendingbyPG
        ************************************************************/
        $key0 = md5($tranID.$orderid.$status.$domain.$amount.$currency);
        $key1 = md5($paydate.$domain.$key0.$appcode.$sec_key);

        if($skey != $key1) abort(404); //Invalidtransaction

        Log::info("RAZERPG CALLBACK : [".$request->ip()."] " . json_encode($request->all()));

        if (isset($post['orderid'])) {
            $referenceUniq = $orderid;
            $invoice = Pembayaran::where('reference', $referenceUniq)
                ->where('status', 'Belum Lunas')
                ->first();

            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'No invoice found or already paid: ' . $referenceUniq,
                ]);
            }

            $order_id = $invoice->order_id;
            $dataPembeli = Pembelian::where('order_id', $order_id)->first();

            if ($dataPembeli) {
                $dataLayanan = Layanan::where('layanan', $dataPembeli->layanan)->first();

                if ($dataLayanan) {
                    $dataKategori = Kategori::where('id', $dataLayanan->kategori_id)->first();

                    if ($dataKategori) {
                        $pesanPembeli =
                            "*Pembayaran Berhasil*\n\n" .
                            "No Invoice: *$order_id*\n" .
                            "Layanan : *$dataPembeli->layanan*\n" .
                            "ID : *$dataPembeli->user_id*\n" .
                            "Server : *$dataPembeli->zone*\n" .
                            "Nickname : *$dataPembeli->nickname*\n" .
                            "Harga : *Rp. " . number_format($dataPembeli->harga, 0, '.', ',') . "*\n" .
                            "Status Pembelian: *Process*\n" .
                            "Estimasi Proses: *1-5 Menit Max 24 Jam*\n\n" .
                            "INI ADALAH PESAN OTOMATIS";

                        $pesanJoki =
                            "*Pembayaran Berhasil*\n\n" .
                            "No Invoice: *$order_id*\n" .
                            "Layanan: *$dataPembeli->layanan*\n" .
                            "ID: *$dataPembeli->user_id*\n" .
                            "Server: *$dataPembeli->zone*\n" .
                            "Nickname: *$dataPembeli->nickname*\n" .
                            "Harga: *Rp. " . number_format($dataPembeli->harga, 0, '.', ',') . "*\n" .
                            "Status Pembelian: *Process*\n" .
                            "Penjoki kami akan segera memulai permainan.\n\n" .
                            "INI ADALAH PESAN OTOMATIS";

                        $pesanSukses =
                            "*Purchase Successful*\n\n" .
                            "No Invoice: *$order_id*\n" .
                            "Service: *$dataPembeli->layanan*\n" .
                            "ID: *$dataPembeli->user_id*\n" .
                            "Server: *$dataPembeli->zone*\n" .
                            "Nickname: *$dataPembeli->nickname*\n" .
                            "Price: *RM. " . number_format($dataPembeli->harga, 2, '.', ',') . "*\n" .
                            "Purchase Status: *Success*\n\n" .
                            "Thank you for transacting with us.";

                        $zoneSend = $dataPembeli->zone == null ? "" : "($dataPembeli->zone)\n";
                        $nickname = $dataPembeli->nickname == null ? '' : "Nickname : $dataPembeli->nickname\n";

                        $uid = $dataPembeli->user_id;
                        $zone = $dataPembeli->zone;
                        $provider_id = $dataLayanan->provider_id;
                    } else {
                        // Handle jika $dataKategori tidak ditemukan
                    }
                } else {
                    // Handle jika $dataLayanan tidak ditemukan
                }
            } else {
                $dataDeposit = Deposit::where('order_id', $order_id)->first();
            }
            if ($status === "00") {
                // Hanya proses yang status transaksi sudah di bayar, sukses = dibayar
                $ref_id = $orderid;
                if (isset($dataDeposit)) {
                    $userDeposit = User::where('username', $dataDeposit->username)->first();

                    if ($dataDeposit->metode == "QRIS" || $dataDeposit->metode == "QRISREALTIME" || $dataDeposit->metode == "OVOPUSH" || $dataDeposit->metode == "GOPAY" || $dataDeposit->metode == "SHOPEEPAY" || $dataDeposit->metode == "DANA" || $dataDeposit->metode == "ASTRAPAY" || $dataDeposit->metode == "VIRGO" || $dataDeposit->metode == "BRIVA" || $dataDeposit->metode == "BCAVA" || $dataDeposit->metode == "BNIVA" || $dataDeposit->metode == "MANDIRIVA" || $dataDeposit->metode == "PERMATAVA" || $dataDeposit->metode == "CIMBVA" || $dataDeposit->metode == "DANAMONVA" || $dataDeposit->metode == "BSIVA" || $dataDeposit->metode == "ALFAMART" || $dataDeposit->metode == "INDOMARET") {
                        $order['data']['status'] = true;
                    }

                    if ($order['data']['status']) { // Jika pembelian sukses

                        $userDeposit->update([
                            'balance' => $dataDeposit->jumlah + $userDeposit->balance,
                        ]);
                        $dataDeposit->update([
                            'status' => 'Success'
                        ]);

                    } else {
                        $dataDeposit->update([
                            'status' => 'Gagal'
                        ]);
                    }
                } else {
                    if ($dataLayanan->provider == "digiflazz") {
                        $random_part = strtoupper(uniqid());
                        $provider_order_id = 'Rp' . $random_part;
                        $digiFlazz = new digiFlazzController;
                        $order = $digiFlazz->order($uid, $zone, $provider_id, $provider_order_id);
                    
                        if ($order['data']['status'] == "Pending" || $order['data']['status'] == "Sukses") {
                            $order['data']['status'] = true;
                            $order['transactionId'] = $provider_order_id;
                        } else {
                            $order['data']['status'] = false;
                        }
                    } elseif ($dataLayanan->provider == "topupedia") {
                        $topupedia = new TopupediaController;
                        
                        $ttlpembelian = [
                            [
                                "name" => "id",
                                "value" => $dataPembeli->user_id
                            ]
                        ];
                    
                        if ($dataPembeli->zone != null) {
                            $ttlpembelian[] = [
                                "name" => "server",
                                "value" => $dataPembeli->zone
                            ];
                        }
                        
                        $order = $topupedia->order($provider_id, $order_id, 1, $ttlpembelian);
                    
                        if ($order['error'] == false) {
                            $order['transactionId'] = $order['data']['invoiceNumber'];
                            $order['data']['status'] = true;
                        } else {
                            $order['data']['status'] = false;
                        }
                    } elseif ($dataLayanan->provider == "bangjeff") {
                        $bangjef = new BangJeffController;
                        
                        $ttlpembelian = [
                            [
                                "name" => "id",
                                "value" => $dataPembeli->user_id
                            ]
                        ];
                    
                        if ($dataPembeli->zone != null) {
                            $ttlpembelian[] = [
                                "name" => "server",
                                "value" => $dataPembeli->zone
                            ];
                        }
                        
                        $order = $bangjef->order($provider_id, $order_id, 1, $ttlpembelian);
                    
                        if ($order['error'] == false) {
                            $order['transactionId'] = $order['data']['invoiceNumber'];
                            $order['data']['status'] = true;
                        } else {
                            $order['data']['status'] = false;
                        }
                    } else if ($dataLayanan->provider == "moogold") {
                        $moo = new MoogoldController();
                        $random_part = mt_rand(100000, 999999);
                        $provider_order_id = 'ACID-MG' . $random_part;
                        $order = $moo->order($request->uid, $dataLayanan->provider_id, $provider_order_id, $request->zone);
                        Log::info('callback moogold', $order);
                        if(isset($order['status'])){
                            $provider_order_id = $order['order_id'];
                            $order['data']['status'] = true;
                        }else{
                            $order['data']['status'] = false;
                        }
                    } else if ($dataLayanan->provider == "gameshop") {
                        $gameshop =  new GameShopProvider;
                        $random_part = mt_rand(100000, 999999);
                        $provider_order_id = 'ACID-MG' . $random_part;
                        $order = $gameshop->order($request->uid, $dataLayanan->provider_id, $provider_order_id, $request->zone);
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
                        $provider_order_id = 'ACID-MG' . $random_part;
                        $order = $strleyashop->order($request->uid, $dataLayanan->provider_id, $provider_order_id, $request->zone);
                        Log::info('callback strleyashop ' . json_encode($order));
                        if(isset($order['order_details']['bot_order_id'])){
                            $provider_order_id = $order['order_details']['bot_order_id'];
                            $order['data']['status'] = true;
                        }else{
                            $order['data']['status'] = false;
                        }
                    } elseif ($dataLayanan->provider == "joki" || $dataLayanan->provider == "jokigendong" || $dataLayanan->provider == "vilogml") {
                        $provider_order_id = '';
                        $order['data']['status'] = true;
                    }


                    if ($order['data']['status']) {

                        if ($dataPembeli->tipe_transaksi !== 'joki') {
                            // Update status menjadi 'Proses' untuk tipe transaksi bukan 'joki'
                            $dataPembeli->update([
                                'provider_order_id' => isset($provider_order_id) ? $provider_order_id : 0,
                                'status' => 'Proses',
                                'log' => json_encode($order)
                            ]);
                            // Kirim pesan setelah status menjadi 'Diproses'
                            $this->msg($invoice->no_pembeli, $pesanSukses);
                        } else {
                            // Update status menjadi 'Proses' untuk tipe transaksi 'joki'
                            $dataPembeli->update([
                                'provider_order_id' => '', 
                                'status' => 'Proses',
                                'log' => json_encode($order)
                            ]);
                            // Kirim pesan untuk joki setelah status 'Diproses'
                            $this->msg($invoice->no_pembeli, $pesanJoki);
                        }
                    } else {
                        // Logika untuk order yang gagal
                        if ($dataPembeli->tipe_transaksi !== 'joki') {
                            $dataPembeli->update([
                                'status' => 'Batal', // Update status menjadi 'Batal' untuk tipe transaksi bukan 'joki'
                                'log' => json_encode($order)
                            ]);
                        } else {
                            // Jika tipe transaksi adalah 'joki' dan transaksi gagal, Anda dapat menentukan logika khusus di sini
                        }
                    }
                }
                $invoice->update(['status' => 'Lunas']);

                // Cek jika status transaksi berubah menjadi 'Sukses'
                if ($dataPembeli->status === 'Sukses' && $dataPembeli->tipe_transaksi !== 'joki') {
                    $this->msg($invoice->no_pembeli, $pesanSukses);
                }
            } else {
                return Response::json(['error' => "Status payment tidak success"]);
            }
        } else {
            return Response::json(['error' => "Data json tidak sesuai"]);
        }
    }

    public function msg($nomor, $msg)
    {
        $api = \DB::table('setting_webs')->where('id', 1)->first();
        $apiUrl = 'https://api.fonnte.com/send';
        $token = $api->wa_key;
    
        $postData = [
            'target' => $nomor,
            'message' => $msg,
            'countryCode' => '0',
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