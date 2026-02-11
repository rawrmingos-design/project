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
                        try {
                            $waService = new \App\Services\WhatsappNotificationService();
                            $waService->sendNotification($updatePesanan->no_pembeli, 'transaction_success', [
                                'nickname' => $invoice->nickname,
                                'order_id' => $invoice->order_id,
                                'product' => $invoice->layanan,
                                'amount' => 'Rp ' . number_format($invoice->harga, 0, ',', '.'),
                                'sn' => $ser_n, // Actual SN from provider
                            ]);

                            // Notify Buyer (Email)
                            $emailService = new \App\Services\EmailNotificationService();
                            $emailService->sendTransactionEmail($invoice->email_pembeli ?? ($updatePesanan->user->email ?? ''), [ // Use invoice email or fallback to user email
                                'order_id' => $invoice->order_id,
                                'product' => $invoice->layanan,
                                'amount' => 'Rp ' . number_format($invoice->harga, 0, ',', '.'),
                                'status' => 'Success',
                                'nickname' => $invoice->nickname,
                                'note' => 'Terima kasih telah berbelanja.'
                            ]);

                        } catch (\Exception $e) {
                            Log::error('Notification send error (DigiflazzCallback)', ['error' => $e->getMessage()]);
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
}
