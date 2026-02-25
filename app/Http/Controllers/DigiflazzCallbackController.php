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

                    $updatePesanan = Pembayaran::where('order_id', $invoice->order_id)->where('metode', 'SALDO')->first();

                    try {
                        $waService = new \App\Services\WhatsappNotificationService();
                        $emailService = new \App\Services\EmailNotificationService();
                        $targetWa = $updatePesanan ? $updatePesanan->no_pembeli : ($invoice->user->no_wa ?? null);
                        $targetEmail = $invoice->email_pembeli ?? ($invoice->user->email ?? '');

                        if (in_array(strtolower($updateStatus), ['gagal', 'batal', 'failed'])) {
                            // Refund Saldo if payment method was SALDO
                            if ($updatePesanan && $invoice->user) {
                                $invoice->user->increment('balance', $invoice->harga);
                                Log::info("Refunded Saldo for Order $refId to User " . $invoice->user->username);
                            }

                            // Notification Failed
                            if ($targetWa) {
                                $waService->sendNotification($targetWa, 'transaction_failed', [
                                    'nickname' => $invoice->nickname,
                                    'order_id' => $invoice->order_id,
                                    'product' => $invoice->layanan,
                                    'amount' => 'Rp ' . number_format($invoice->harga, 0, ',', '.'),
                                    'note' => $msg_n ?? 'Transaksi dibatalkan oleh provider.'
                                ]);
                            }
                            if ($targetEmail) {
                                $emailService->sendTransactionEmail($targetEmail, [
                                    'order_id' => $invoice->order_id,
                                    'product' => $invoice->layanan,
                                    'amount' => 'Rp ' . number_format($invoice->harga, 0, ',', '.'),
                                    'status' => 'Failed',
                                    'nickname' => $invoice->nickname,
                                    'note' => $msg_n ?? 'Transaksi dibatalkan oleh provider.'
                                ]);
                            }

                        } elseif (in_array(strtolower($updateStatus), ['sukses', 'success'])) {
                            // Notification Success
                            if ($targetWa) {
                                $waService->sendNotification($targetWa, 'transaction_success', [
                                    'nickname' => $invoice->nickname,
                                    'order_id' => $invoice->order_id,
                                    'product' => $invoice->layanan,
                                    'amount' => 'Rp ' . number_format($invoice->harga, 0, ',', '.'),
                                    'sn' => $ser_n, // Actual SN from provider
                                ]);
                            }
                            if ($targetEmail) {
                                $emailService->sendTransactionEmail($targetEmail, [
                                    'order_id' => $invoice->order_id,
                                    'product' => $invoice->layanan,
                                    'amount' => 'Rp ' . number_format($invoice->harga, 0, ',', '.'),
                                    'status' => 'Success',
                                    'nickname' => $invoice->nickname,
                                    'note' => 'Terima kasih telah berbelanja.'
                                ]);
                            }
                        }
                    } catch (\Exception $e) {
                        Log::error('Notification/Refund error (DigiflazzCallback)', ['error' => $e->getMessage()]);
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
