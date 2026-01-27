<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Models\Layanan;
use App\Models\Kategori;
use App\Models\Voucher;
use App\Models\Deposit;
use App\Models\User;
use App\Http\Controllers\digiFlazzController;
use App\Http\Controllers\provider\TopupediaController;
use App\Http\Controllers\provider\BangjeffController;
use App\Http\Controllers\provider\MoogoldController;
use App\Http\Controllers\provider\VipResellerController;
use App\Http\Controllers\provider\ApigamesController;

class TokoPayCallbackController extends Controller
{
    protected $api;

    public function __construct()
    {
        $this->api = \DB::table('setting_webs')->where('id', 1)->first();
    }

    public function handle(Request $request)
    {
        $json = $request->getContent();
        $data = json_decode($json, true);
        if (isset($data['status'], $data['reff_id'], $data['signature'])) {
            $referenceUniq = $data['reference'];
            $invoice = Pembayaran::where('reference', $referenceUniq)
                ->where('status', 'Belum Lunas')
                ->first();

            if (!$invoice) {
                return Response::json([
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
                            "*Diamond Berhasil Dikirim*\n\n" .
                            "No Invoice: *$order_id*\n" .
                            "Layanan: *$dataPembeli->layanan*\n" .
                            "ID: *$dataPembeli->user_id*\n" .
                            "Server: *$dataPembeli->zone*\n" .
                            "Nickname: *$dataPembeli->nickname*\n" .
                            "Harga: *Rp. " . number_format($dataPembeli->harga, 0, '.', ',') . "*\n" .
                            "Status Pembelian: *Success*\n\n" .
                            "Terima kasih telah bertransaksi dengan kami.";

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

            $status = $data['status'];
            if ($status === "Success") {
                // Hanya proses yang status transaksi sudah di bayar, sukses = dibayar
                $ref_id = $data['reff_id'];
                /*
                 * Validasi Signature
                 */
                $signature_from_tokopay = $data['signature'];
                $signature_validasi = md5($this->api->tokopay_merchant_id . ":" . $this->api->tokopay_secret_key . ":" . $ref_id);
                if ($signature_from_tokopay === $signature_validasi) {

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
                        // START Multi-Provider Integration
                        $routingService = new \App\Services\ProviderRoutingService();
                        $bestRoute = $routingService->findBestProvider($dataLayanan);

                        if (!$bestRoute) {
                            $order['data']['status'] = false;
                            \Log::error("TokoPay Callback: No provider found for Layanan {$dataLayanan->layanan}");
                        } else {
                            $providerCode = $bestRoute['provider_code'];
                            $sku = $bestRoute['sku'];
                            $credentials = $bestRoute['credentials'] ?? [];
                            
                            \Log::info("TokoPay Callback routed to $providerCode with SKU $sku");

                            if ($providerCode == "digiflazz") {
                                $random_part = mt_rand(100000, 999999);
                                $provider_order_id = 'REF-WEJIZY' . $random_part;
                                $digiFlazz = new digiFlazzController($credentials);
                                $order = $digiFlazz->order($uid, $zone, $sku, $provider_order_id);
                            
                                if ($order['data']['status'] == "Pending" || $order['data']['status'] == "Sukses") {
                                    $order['data']['status'] = true;
                                    $order['transactionId'] = $provider_order_id;
                                } else {
                                    $order['data']['status'] = false;
                                }
                            } else if ($providerCode == "vip" || $providerCode == "vip_reseller") {
                                $vip = new VipResellerController($credentials);
                                $order = $vip->order($uid, $zone, $sku);
                                
                                if ($order['result']) {
                                    $order['data']['status'] = true;
                                    $order['transactionId'] = $order['data']['trxid'];
                                } else {
                                    $order['data']['status'] = false;
                                }
                            } elseif ($providerCode == "apigames") {
                                // ApiGames logic from TriPay pattern (missing in original TokoPay callback but good to add if supported)
                                $provider_order_id = rand(1, 10000);
                                $apigames = new ApigamesController($credentials);
                                $order = $apigames->order($uid, $zone, $sku, $provider_order_id);

                                if ($order['data']['status'] == "Sukses") {
                                    $order['transactionId'] = $provider_order_id;
                                    $order['data']['status'] = true;
                                } else {
                                    $order['data']['status'] = false;
                                }
                            } elseif ($providerCode == "topupedia") {
                                $topupedia = new TopupediaController($credentials);
                                $ttlpembelian = [['name' => 'id', 'value' => $uid]];
                                if ($zone != null) $ttlpembelian[] = ['name' => 'server', 'value' => $zone];
                                
                                $order = $topupedia->order($sku, $order_id, 1, $ttlpembelian);
                                if ($order['error'] == false) {
                                    $order['transactionId'] = $order['data']['invoiceNumber'];
                                    $order['data']['status'] = true;
                                } else {
                                    $order['data']['status'] = false;
                                }
                            } elseif ($providerCode == "bangjeff") {
                                $bangjef = new BangjeffController($credentials);
                                $ttlpembelian = [['name' => 'id', 'value' => $uid]];
                                if ($zone != null) $ttlpembelian[] = ['name' => 'server', 'value' => $zone];
                                
                                $order = $bangjef->order($sku, $order_id, 1, $ttlpembelian);
                                if ($order['error'] == false) {
                                    $order['transactionId'] = $order['data']['invoiceNumber'];
                                    $order['data']['status'] = true;
                                } else {
                                    $order['data']['status'] = false;
                                }
                            } elseif ($providerCode == "moogold") {
                                $moo = new MoogoldController();
                                $random_part = mt_rand(100000, 999999);
                                $provider_order_id = 'WEJIZY-REFF' . $random_part;
                                $order = $moo->order($uid, $sku, $provider_order_id, $zone);
                                \Log::info('callback moogold', $order);
                                if(isset($order['status'])){ // Corrected check from OrderController
                                    $order['transactionId'] = $order['order_id'];
                                    $order['data']['status'] = true;
                                } else {
                                    $order['data']['status'] = false;
                                }
                            } elseif (in_array($providerCode, ["joki", "jokigendong", "vilogml", "manual"])) {
                                $provider_order_id = '';
                                $order['data']['status'] = true;
                            }
                        }
                        // END Multi-Provider Integration


                        if ($order['data']['status']) {

                            if ($dataPembeli->tipe_transaksi !== 'joki' || $dataLayanan->provider == "vilogml") {
                                // Update status menjadi 'Proses' untuk tipe transaksi bukan 'joki'
                                $dataPembeli->update([
                                    'provider_order_id' => isset($order['transactionId']) ? $order['transactionId'] : 0,
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

                    return Response::json(['success' => true]);
                } else {
                    return Response::json(['error' => "Invalid Signature"]);
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
