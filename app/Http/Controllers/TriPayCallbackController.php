<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Models\Layanan;
use App\Models\Kategori;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Services\WhatsappNotificationService;
use App\Services\ProviderRoutingService;
use App\Services\OrderProcessingService;

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

            // Check Amount
            if (intval($data->total_amount) !== (int) $invoice->harga) {
                DB::rollBack();
                return 'Invalid amount';
            }

            if ($data->status == "PAID") {
                // Initialize Services
                $waService = new WhatsappNotificationService();
                $routingService = new ProviderRoutingService();
                $orderProcessor = new OrderProcessingService($routingService);

                // 1. Notify Admin 
                // Using existing hardcoded text format for Admin for now, or could use template later
                // Reconstructing strict text to match previous style if needed, or just simple one
                $pesanAdmin = "*Pembayaran Berhasil*\n\n" .
                    "No Invoice: *$order_id*\n" .
                    "Layanan : $dataPembeli->layanan\n" .
                    "ID : $dataPembeli->user_id\n" .
                    "Server : $dataPembeli->zone\n" .
                    "Nickname : $dataPembeli->nickname\n" .
                    "Metode Pembayaran : $invoice->metode\n" .
                    "Harga : Rp. " . number_format($invoice->harga, 0, '.', ',') . "\n\n" .
                    "*Kontak Pembeli*\n" .
                    "No HP : $invoice->no_pembeli\n";

                $waService->sendMessage($this->api->nomor_admin, $pesanAdmin);

                // 2. Process Order
                $result = $orderProcessor->process($dataPembeli);
                $transactionId = $result['transaction_id'] ?? null;
                $orderSuccess = $result['success'];

                // 3. Update Invoice/Transaction
                if ($orderSuccess) {
                    $orderData = ['status' => 'Sukses']; 
                    if ($transactionId) {
                        $orderData['provider_order_id'] = $transactionId;
                    }
                    $dataPembeli->update($orderData);
                    
                    // Notify Buyer (Success/Processing)
                    $waService->sendNotification($invoice->no_pembeli, 'transaction_success', [
                        'nickname' => $dataPembeli->nickname,
                        'order_id' => $order_id,
                        'product' => $dataPembeli->layanan,
                        'amount' => 'Rp ' . number_format($dataPembeli->harga, 0, ',', '.'),
                        'sn' => 'Sedang Diproses', 
                    ]);

                    // Notify Buyer (Email)
                    $emailService = new \App\Services\EmailNotificationService();
                    $recipientEmail = $dataPembeli->email_pembeli ?? ($dataPembeli->user->email ?? null);
                    if ($recipientEmail) {
                        $emailService->sendTransactionEmail($recipientEmail, [
                            'order_id' => $order_id,
                            'product' => $dataPembeli->layanan,
                            'amount' => 'Rp ' . number_format($dataPembeli->harga, 0, ',', '.'),
                            'status' => 'Success',
                            'nickname' => $dataPembeli->nickname,
                            'note' => 'Harap Simpan Invoice ini, akan digunakan untuk verifikasi transaksi.'
                        ]);
                    }

                } else {
                    $dataPembeli->update(['status' => 'Pending']); 
                    Log::warning("Order processing failed for {$order_id}: " . $result['message']);
                    
                    // Notify Buyer (Pending/Failed)
                    $waService->sendNotification($invoice->no_pembeli, 'transaction_pending', [
                        'nickname' => $dataPembeli->nickname,
                        'order_id' => $order_id,
                        'product' => $dataPembeli->layanan,
                        'amount' => 'Rp ' . number_format($dataPembeli->harga, 0, ',', '.'),
                        'status' => 'Menunggu Provider',
                    ]);

                    // Notify Buyer (Email)
                    $emailService = new \App\Services\EmailNotificationService();
                    $recipientEmail = $dataPembeli->email_pembeli ?? ($dataPembeli->user->email ?? null);
                    if ($recipientEmail) {
                        $emailService->sendTransactionEmail($recipientEmail, [
                            'order_id' => $order_id,
                            'product' => $dataPembeli->layanan,
                            'amount' => 'Rp ' . number_format($dataPembeli->harga, 0, ',', '.'),
                            'status' => 'Pending',
                            'nickname' => $dataPembeli->nickname,
                            'note' => 'Pesanan sedang menunggu respon provider. Invoice ini akan digunakan untuk verifikasi transaksi.'
                        ]);
                    }
                }

                $invoice->update(['status' => 'Lunas']);
                DB::commit();
                return response()->json(['success' => true]);

            } else if ($data->status == "EXPIRED" || $data->status == "FAILED") {
                $invoice->update(['status' => 'Batal']);
                
                // Notify Buyer (Failed)
                $waService = new WhatsappNotificationService();
                $waService->sendNotification($invoice->no_pembeli, 'transaction_failed', [
                    'nickname' => $dataPembeli->nickname ?? 'Pelanggan',
                    'order_id' => $order_id,
                    'product' => $dataPembeli->layanan,
                    'reason' => 'Pembayaran Kadaluarsa/Gagal',
                ]);

                // Notify Buyer (Email)
                $emailService = new \App\Services\EmailNotificationService();
                $recipientEmail = $dataPembeli->email_pembeli ?? ($dataPembeli->user->email ?? null);
                if ($recipientEmail) {
                    $emailService->sendTransactionEmail($recipientEmail, [
                        'order_id' => $order_id,
                        'product' => $dataPembeli->layanan,
                        'amount' => 'Rp ' . number_format($dataPembeli->harga, 0, ',', '.'),
                        'status' => 'Failed',
                        'nickname' => $dataPembeli->nickname,
                        'note' => 'Mohon maaf, transaksi Anda gagal atau kadaluarsa. Invoice ini akan digunakan untuk verifikasi transaksi.'
                    ]);
                }

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
}
