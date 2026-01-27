<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Log;
use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Models\Layanan;
use App\Models\Kategori;
use Illuminate\Support\Facades\DB;

class DigiflazzCallbackController extends Controller
{

    public function handle(Request $request)
    {
        $secret = 'WEJIZYSEC18';
        $post_data = file_get_contents('php://input');
        $signature = hash_hmac('sha1', $post_data, $secret);

        if ($request->header('X-Hub-Signature') == 'sha1=' . $signature) {
            $data = json_decode($request->getContent(), true);
            $refId = $data['data']['ref_id'];
            $updateStatus = $data['data']['status'];
            $ser_n = $data['data']['sn'];
            $msg_n = $data['data']['message'];

            if ($request->header('X-Digiflazz-Event') == 'update') {
                $invoice = Pembelian::where('provider_order_id', $refId)->where('status', 'Proses')->first();

                if ($invoice) {
                    $updateData = [
                        'status' => $updateStatus,
                        'log' => json_encode($data)
                    ];

                    if ($invoice->tipe_transaksi == 'voucher') {
                        $updateData['voucher'] = $ser_n;
                    } elseif ($invoice->tipe_transaksi == 'game') {
                        $updateData['message'] = $msg_n;
                    }

                    $invoice->update($updateData);

                    // Cek apakah invoice ada sebelum mengakses properti 'order_id'
                    $updatePesanan = Pembayaran::where('order_id', $invoice->order_id)->where('metode', 'SALDO')->first();

                    if ($updatePesanan) {
                        $pesanSukses = "*Pembelian Sukses*\n\n" .
                            "No Invoice: *$invoice->order_id*\n" .
                            "Layanan: *$invoice->layanan*\n" .
                            "ID : *$invoice->user_id*\n" .
                            "Server : *$invoice->zone*\n" .
                            "Nickname : *$invoice->nickname*\n" .
                            "Harga: *Rp. " . number_format($invoice->harga, 0, '.', ',') . "*\n" .
                            "Status Pembelian: *Sukses*\n" .
                            "Metode Pembayaran: *$updatePesanan->metode*\n\n" .
                            "*Invoice* : " . env("APP_URL") . "/id/invoice/$invoice->order_id\n\n" .
                            "INI ADALAH PESAN OTOMATIS";

                        // Kirim WhatsApp dengan timeout kecil dan tanpa blocking callback
                        try {
                            $this->msg($updatePesanan->no_pembeli, $pesanSukses);
                        } catch (\Exception $e) {
                            Log::error('WhatsApp send error (DigiflazzCallback)', ['error' => $e->getMessage()]);
                        }
                    }
                } else {
                    Log::error("Invoice not found for ref_id: $refId");
                }
            }
        }
        // Selalu return response agar Digiflazz tidak timeout
        return response()->json(['success' => true]);
    }

    public function msg($nomor, $msg)
    {
        try {
            $api = DB::table('setting_webs')->where('id', 1)->first();
            
            if (!$api || !$api->wa_key || !$api->nomor_admin) {
                Log::error('WhatsApp API (Fonnte) - Missing configuration.', ['wa_key_exists' => !empty($api->wa_key), 'nomor_admin_exists' => !empty($api->nomor_admin)]);
                return ['success' => false, 'message' => 'Konfigurasi WA belum lengkap.'];
            }

            $curl = curl_init();
            
            curl_setopt_array($curl, [
                CURLOPT_URL => 'https://api.fonnte.com/send',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 15, // Slightly longer timeout for Digiflazz safety
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => [
                    'target' => $nomor,
                    'message' => $msg,
                ],
                CURLOPT_HTTPHEADER => [
                    'Authorization: ' . $api->wa_key,
                ],
            ]);

            $response = curl_exec($curl);
            $error = curl_error($curl);
            curl_close($curl);

            if ($error) {
                Log::error('WhatsApp API (Fonnte) - Curl Error', ['error' => $error]);
                return ['success' => false, 'message' => 'Connection Error: ' . $error];
            }

            Log::info('WhatsApp API (Fonnte) Response', ['response' => $response]);
            return ['success' => true, 'response' => $response];

        } catch (\Exception $e) {
            Log::error('WhatsApp API (Fonnte) - Exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'System Error: ' . $e->getMessage()];
        }
    }
}
