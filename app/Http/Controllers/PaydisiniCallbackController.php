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
use App\Services\OrderProcessingService;
use App\Services\ProviderRoutingService;
use App\Services\WhatsappNotificationService;

class PaydisiniCallbackController extends Controller
{
    private $apiKey;

    public function __construct()
    {
        $this->apiKey = \DB::table('setting_webs')->where('id', 1)->first()->paydisini_apikey;
    }
    
    public function callbackTransaction(Request $request)
    {
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
                // Initialize Services
                $routingService = new ProviderRoutingService();
                $orderProcessor = new OrderProcessingService($routingService);
                $waService = new WhatsappNotificationService();

                // Process Order
                $result = $orderProcessor->process($pembelian);
                
                if ($result['success']) {
                    $snValue = trim((string) ($result['sn'] ?? '')) ?: ($pembelian->keterangan_sn ?: 'Sedang Diproses');
                    $pembelian->update([
                        'status' => 'Sukses', // Or Processing based on preference
                        'provider_order_id' => $result['transaction_id'] ?? null,
                        'keterangan_sn' => $snValue,
                        'log' => json_encode(['result' => $result])
                    ]);

                    // Notify Buyer (WhatsApp)
                    $waService->sendNotification($transaction->no_pembeli, 'transaction_success', [
                        'nickname' => $pembelian->nickname,
                        'order_id' => $pembelian->order_id,
                        'product' => $pembelian->layanan,
                        'amount' => 'Rp ' . number_format($pembelian->harga, 0, ',', '.'),
                        'sn' => $snValue,
                    ]);

                     // Notify Buyer (Email)
                     $emailService = new \App\Services\EmailNotificationService();
                     $recipientEmail = $transaction->email_pembeli ?? ($transaction->user->email ?? null);
                     if ($recipientEmail) {
                        $emailService->sendTransactionEmail($recipientEmail, [ 'order_id' => $pembelian->order_id,
                         'product' => $pembelian->layanan,
                         'amount' => 'Rp ' . number_format($pembelian->harga, 0, ',', '.'),
                         'status' => 'Success',
                         'nickname' => $pembelian->nickname,
                         'sn' => $snValue,
                         'note' => 'Terima kasih telah berbelanja.'
                     ]);
                    }

                } else {
                    $pembelian->update([
                        'status' => 'Pending', // Mark pending for retry
                        'log' => json_encode(['error' => $result['message']])
                    ]);

                    // Notify Buyer Pending/Failed (WhatsApp)
                    $waService->sendNotification($transaction->no_pembeli, 'transaction_pending', [
                        'nickname' => $pembelian->nickname,
                        'order_id' => $pembelian->order_id,
                        'product' => $pembelian->layanan,
                        'amount' => 'Rp ' . number_format($pembelian->harga, 0, ',', '.'),
                        'status' => 'Menunggu Provider',
                    ]);

                    // Notify Buyer (Email)
                    $emailService = new \App\Services\EmailNotificationService();
                    $recipientEmail = $transaction->email_pembeli ?? ($transaction->user->email ?? null);
                    if ($recipientEmail) {
                        $emailService->sendTransactionEmail($recipientEmail, [
                        'order_id' => $pembelian->order_id,
                        'product' => $pembelian->layanan,
                        'amount' => 'Rp ' . number_format($pembelian->harga, 0, ',', '.'),
                        'status' => 'Pending',
                        'nickname' => $pembelian->nickname,
                        'note' => 'Pesanan sedang menunggu respon provider.'
                    ]);
                }
                }

            } else {
                $deposit = Deposit::where('order_id', $uniqueCode)->first();
                if ($deposit) {
                    $user = User::where('username', $deposit->username)->first();
                    if ($user) {
                        $user->update(['balance' => $user->balance + $deposit->jumlah]);
                        $deposit->update(['status' => 'Success']);
                        
                        // Notify Deposit Success (Optional)
                        // $waService->sendMessage(...)
                    }
                }
            }
            return response()->json(['success' => true]);

        } elseif ($status === 'Canceled') {
            $transaction->update(['status' => 'Expired']);
            $pembelian = Pembelian::where('order_id', $uniqueCode)->first();
            
            if ($pembelian) {
                $pembelian->update(['status' => 'Expired']); // Or Batal?
                app(\App\Services\PointService::class)->refundRedeemedPoints($pembelian);
                
                // Notify Failed (WhatsApp)
                $waService = new WhatsappNotificationService();
                $waService->sendNotification($transaction->no_pembeli, 'transaction_failed', [
                    'nickname' => $pembelian->nickname,
                    'order_id' => $pembelian->order_id,
                    'product' => $pembelian->layanan,
                    'reason' => 'Pembayaran Dibatalkan',
                ]);

                // Notify Failed (Email)
                $emailService = new \App\Services\EmailNotificationService();
                $recipientEmail = $transaction->email_pembeli ?? ($transaction->user->email ?? null);
                if ($recipientEmail) {
                    $emailService->sendTransactionEmail($recipientEmail, [
                    'order_id' => $pembelian->order_id,
                    'product' => $pembelian->layanan,
                    'amount' => 'Rp ' . number_format($pembelian->harga, 0, ',', '.'),
                    'status' => 'Failed',
                    'nickname' => $pembelian->nickname,
                    'note' => 'Pembayaran dibatalkan atau kadaluarsa.'
                ]);
                }

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
}
