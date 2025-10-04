<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Models\Layanan;
use App\Models\Kategori;
use App\Models\Voucher;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\digiFlazzController;
use App\Http\Controllers\provider\VipResellerController;
use App\Http\Controllers\provider\ApiGamesController;
use App\Http\Controllers\provider\BangJeffController;
use App\Http\Controllers\provider\TopupediaController;;

use App\Http\Controllers\provider\MoogoldController;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\provider\ApiGamesV2Controller;
use App\Http\Controllers\provider\MengtopupController;
use App\Http\Controllers\provider\AlpharamzController;

class TriPayCallbackController extends Controller
{
    protected $api;

    public function __construct()
    {
        $this->api = DB::table('setting_webs')->where('id', 1)->first();
    }

    public function handle(Request $request)
    {
        $callbackSignature = $request->server('HTTP_X_CALLBACK_SIGNATURE');
        $json = $request->getContent();
        $signature = hash_hmac('sha256', $json, $this->api->tripay_private_key);

        if ($signature !== (string) $callbackSignature) {
            return 'Invalid signature';
        }

        if ('payment_status' !== (string) $request->server('HTTP_X_CALLBACK_EVENT')) {
            return 'Invalid callback event, no action was taken';
        }

        $data = json_decode($json);
        $ref = $data->reference;

        // Mulai transaction dan locking
        DB::beginTransaction();
        try {
            $invoice = Pembayaran::where('reference', $ref)
                ->where('status', 'Belum Lunas')
                ->lockForUpdate()
                ->first();

            if (!$invoice) {
                // Log jika callback diterima lebih dari sekali
                Log::warning('Tripay callback: Invoice not found or already processed', ['reference' => $ref]);
                DB::rollBack();
                return 'Invoice not found or already processed';
            }

            $order_id = $invoice->order_id;
            $dataPembeli = Pembelian::where('order_id', $order_id)->first();
            $dataLayanan = Layanan::where('layanan', $dataPembeli->layanan)->first();
            $dataKategori = Kategori::where('id', $dataLayanan->kategori_id)->first();

            $zoneSend = $dataPembeli->zone == null ? "" : "($dataPembeli->zone)\n";
            $nickname = $dataPembeli->nickname == null ? '' : "Nickname : $dataPembeli->nickname\n";

            $pesanPembeli =
                "*Pembayaran Berhasil*\n\n" .
                "No Invoice: *$order_id*\n" .
                "Layanan : *$dataPembeli->layanan*\n" .
                "ID : *$dataPembeli->user_id*\n" .
                "Server : *$dataPembeli->zone*\n" .
                "Nickname : *$dataPembeli->nickname*\n" .
                "Harga : *Rp. " . number_format($dataPembeli->harga, 0, '.', ',') . "*\n" .
                "Status Pembelian: *Diproses*\n" .
                "Estimasi Proses: *1-5 Menit Max 24 Jam*\n\n" .
                "INI ADALAH PESAN OTOMATIS";

            $pesanAdmin =
                "*Pembayaran Berhasil*\n\n" .
                "No Invoice: *$order_id*\n" .
                "Layanan : $dataPembeli->layanan\n" .
                "ID : $dataPembeli->user_id\n" .
                "Server : $dataPembeli->zone\n" .
                $nickname .
                "Metode Pembayaran : $invoice->metode\n" .
                "Harga : Rp. " . number_format($invoice->harga, 0, '.', ',') . "\n\n" .
                "*Kontak Pembeli*\n" .
                "No HP : $invoice->no_pembeli\n" .
                "Invoice : " . env("APP_URL") . "/id/invoices/$order_id";

            $uid = $dataPembeli->user_id;
            $zone = $dataPembeli->zone;
            $provider_id = $dataLayanan->provider_id;

            if (intval($data->total_amount) !== (int) $invoice->harga) {
                DB::rollBack();
                return 'Invalid amount';
            }

            if ($data->status == "PAID") {
                $requestPesan = $this->msg($this->api->nomor_admin, $pesanAdmin);
                $pesanPembeli = $this->msg($invoice->no_pembeli, $pesanPembeli);

                if ($dataLayanan->provider == "digiflazz") {
                    $provider_order_id = rand(1, 10000);
                    $digiFlazz = new digiFlazzController;
                    $order = $digiFlazz->order($uid, $zone, $provider_id, $provider_order_id);
Log::info('Tripay Callback DigiFlazz Order', ['order' => $order]);
                    if ($order['data']['status'] == "Pending" || $order['data']['status'] == "Sukses") {
                        $order['data']['status'] = true;
                        $order['transactionId'] = $provider_order_id;
                    } else {
                        $order['data']['status'] = false;
                    }
                } else if ($dataLayanan->provider == "vip") {
                    $vip = new VipResellerController;
                    $order = $vip->order($uid, $zone, $provider_id);

                    if ($order['result']) {
                        $order['data']['status'] = $order['result'];
                        $order['transactionId'] = $order['data']['trxid'];
                    } else {
                        $order['data']['status'] = false;
                    }
                } else if ($dataLayanan->provider == "apigames") {
                    $provider_order_id = rand(1, 10000);
                    $apigames = new ApiGamesController;
                    $order = $apigames->order($uid, $zone, $provider_id, $provider_order_id);

                    if ($order['data']['status'] == "Sukses") {
                        $order['transactionId'] = $provider_order_id;
                        $order['data']['status'] = true;
                    } else {
                        $order['data']['status'] = false;
                    }
                } else if ($dataLayanan->provider == "apigamesv2") {
                    $provider_order_id = rand(1, 10000);
                    $apigamesv2 = new ApiGamesV2Controller;
                    $order = $apigamesv2->order($uid, $zone, $provider_id, $provider_order_id);

                    if ($order['data']['status'] == "Sukses") {
                        $order['transactionId'] = $provider_order_id;
                        $order['data']['status'] = true;
                    } else {
                        $order['data']['status'] = false;
                    }
                } else if ($dataLayanan->provider == "meng") {
                    $meng = new App\Http\Controllers\MengtopupController();
                    $order = $meng->order($uid, $zone, $provider_id);

                    if ($order['status']) {
                        $order['transactionId'] = $order['data']['id'];
                        $order['data']['status'] = true;
                    } else {
                        $order['data']['status'] = false;
                    }
                } else if ($dataLayanan->provider == "alpha") {
                    $alpha = new AlpharamzController();
                    $order = $alpha->order($uid, $zone, $provider_id);

                    if ($order['status']) {
                        $order['transactionId'] = $order['data']['id'];
                        $order['data']['status'] = true;
                    } else {
                        $order['data']['status'] = false;
                    }
                } else if ($dataLayanan->provider == "joki") {
                    $order['data']['status'] = true;
                } else if ($dataLayanan->provider == "manual") {
                    $order['data']['status'] = true;
                } else if ($dataLayanan->provider == "gift_skin") {
                    $order['data']['status'] = true;
                } else if ($dataLayanan->provider == "dm_vilog") {
                    $order['data']['status'] = true;
                }


                if ($order['data']['status']) { // Jika pembelian sukses                

                    $pesanSukses =
                        "*Pembayaran Kamu berhasil✨*\n\n" .
                        "Terima kasih berikut detail transaksinya  :\n\n" .
                        "No Invoice: *$order_id*\n" .
                        "Layanan: *$dataPembeli->layanan*\n" .
                        "ID : *$dataPembeli->user_id*\n" .
                        "Server : *$dataPembeli->zone*\n" .
                        "Nickname : *$dataPembeli->nickname*\n" .
                        "No Whatsapp: *$invoice->no_pembeli*\n" .
                        "Harga: *Rp. " . number_format($invoice->harga, 0, '.', ',') . "*\n\n" .
                        "Invoice : " . env("APP_URL") . "/id/invoices/$order_id\n\n" .
                        "*Ditunggu orderan selanjutnya! Terimakasih.*\n";

                    $pesanSuksesAdmin =
                        "*Pembelian Sukses*\n\n" .
                        "No Invoice: *$order_id*\n" .
                        "Layanan: *$dataPembeli->layanan*\n" .
                        "ID : *$dataPembeli->user_id*\n" .
                        "Server : *$dataPembeli->zone*\n" .
                        "Nickname : *$dataPembeli->nickname*\n" .
                        "Harga: *Rp. " . number_format($invoice->harga, 0, '.', ',') . "*\n" .
                        "Status Pembelian: *Sukses*\n\n" .
                        "*Kontak Pembeli*\n" .
                        "No HP : $invoice->no_pembeli\n" .
                        "*Invoice* : " . env("APP_URL") . "/id/invoices/$order_id\n\n" .
                        "INI ADALAH PESAN OTOMATIS";

                    $pesanAdmin = $this->msg($this->api->nomor_admin, $pesanSuksesAdmin);
                    $pesanUser = $this->msg($invoice->no_pembeli, $pesanSukses);

                    $invoice->update(['status' => 'Sukses']);

                    $dataPembeli->update([
                        'provider_order_id' => isset($order['transactionId']) ? $order['transactionId'] : 0,
                        'status' => 'Sukses',
                        'log' => json_encode($order)
                    ]);
                } else { //jika pembelian gagal

                    $dataPembeli->update([
                        'status' => 'Batal',
                        'log' => json_encode($order)
                    ]);
                }

                $invoice->update(['status' => 'Lunas']);
                DB::commit();
                return response()->json(['success' => true]);
            } else if ($data->status == "EXPIRED" || $data->status == "FAILED") {
                $invoice->update(['status' => 'Batal']);
                DB::commit();
                return response()->json(['success' => true]);
            } else {
                DB::rollBack();
                return response()->json(['error' => 'Unrecognized payment status']);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Tripay callback fatal error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Fatal error: ' . $e->getMessage()], 500);
        }
    }

    public function msg($nomor, $msg)
    {
        try {
            $api = DB::table('setting_webs')->where('id', 1)->first();
            if (!$api || !$api->wa_key || !$api->nomor_admin) {
                Log::error('WhatsApp API - Data konfigurasi tidak lengkap.', ['data' => $api]);
                return ['success' => false, 'message' => 'Konfigurasi pengiriman pesan tidak lengkap.'];
            }
            $apiUrl = 'https://wa.egymarket.id/send-message';
            $postData = [
                'api_key' => $api->wa_key,
                'sender' => $api->nomor_admin,
                'number' => $nomor,
                'message' => $msg
            ];
            $headers = ['Content-Type: application/json',];
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $apiUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($postData),
                CURLOPT_HTTPHEADER => $headers,
            ]);
            $response = curl_exec($curl);
            $error = curl_error($curl);
            $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            if ($error) {
                Log::error('WhatsApp API - CURL error', ['error' => $error]);
                return ['success' => false, 'message' => 'CURL Error: ' . $error];
            }
            Log::info(
                'WhatsApp API Response',
                [
                    'status' => $statusCode,
                    'response' => $response
                ]
            );
            return [
                'success' => $statusCode === 200,
                'response' => json_decode($response, true)
            ];
        } catch (\Exception $e) {
            Log::error('WhatsApp API - Exception occurred', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return ['success' => false, 'message' => 'Exception: ' . $e->getMessage()];
        }
    }
}
