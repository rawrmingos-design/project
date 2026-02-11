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

// Services
use App\Services\OrderProcessingService;
use App\Services\ProviderRoutingService;
use App\Services\WhatsappNotificationService;

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
                
                // Validasi Signature
                $signature_from_tokopay = $data['signature'];
                $signature_validasi = md5($this->api->tokopay_merchant_id . ":" . $this->api->tokopay_secret_key . ":" . $ref_id);
                
                if ($signature_from_tokopay === $signature_validasi) {

                    if (isset($dataDeposit)) {
                        $userDeposit = User::where('username', $dataDeposit->username)->first();

                        if (in_array($dataDeposit->metode, ["QRIS", "QRISREALTIME", "OVOPUSH", "GOPAY", "SHOPEEPAY", "DANA", "ASTRAPAY", "VIRGO", "BRIVA", "BCAVA", "BNIVA", "MANDIRIVA", "PERMATAVA", "CIMBVA", "DANAMONVA", "BSIVA", "ALFAMART", "INDOMARET"])) {
                            $order['data']['status'] = true;
                        } else {
                            $order['data']['status'] = true; // Assume true for others or add logic
                        }

                        if ($order['data']['status']) { 
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
                        // START Multi-Provider Integration via Services
                        $pembelian = $dataPembeli; // Alias
                        $transaction = $invoice;   // Alias

                        $routingService = new \App\Services\ProviderRoutingService();
                        $orderProcessor = new \App\Services\OrderProcessingService($routingService);
                        $waService = new \App\Services\WhatsappNotificationService();

                        // Process Order
                        $result = $orderProcessor->process($pembelian);
                        
                        if ($result['success']) {
                            $pembelian->update([
                                'status' => 'Sukses',
                                'provider_order_id' => $result['transaction_id'] ?? null,
                                'log' => json_encode(['result' => $result])
                            ]);

                            // Notify Buyer (WhatsApp)
                            $waService->sendNotification($transaction->no_pembeli, 'transaction_success', [
                                'nickname' => $pembelian->nickname,
                                'order_id' => $pembelian->order_id,
                                'product' => $pembelian->layanan,
                                'amount' => 'Rp ' . number_format($pembelian->harga, 0, ',', '.'),
                                'sn' => 'Sedang Diproses',
                            ]);

                            // Notify Buyer (Email)
                            $emailService = new \App\Services\EmailNotificationService();
                            $recipientEmail = $transaction->email_pembeli ?? ($transaction->user->email ?? null);
                            if ($recipientEmail) {
                                $emailService->sendTransactionEmail($recipientEmail, [
                                'order_id' => $pembelian->order_id,
                                'product' => $pembelian->layanan,
                                'amount' => 'Rp ' . number_format($pembelian->harga, 0, ',', '.'),
                                'status' => 'Success',
                                'nickname' => $pembelian->nickname,
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
                        // END Multi-Provider Integration
                    }
                    
                    $invoice->update(['status' => 'Lunas']);
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

}
