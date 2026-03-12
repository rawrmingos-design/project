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
use App\Http\Controllers\DigiFlazzController;

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

        // Pastikan payload memiliki field wajib
        if (!isset($data['status'], $data['reff_id'], $data['signature'])) {
            return Response::json(['error' => 'Data json tidak sesuai'], 400);
        }

        // FIX #8a: Validasi signature PERTAMA sebelum query DB apapun
        // Ini mencegah DDoS dan memastikan request hanya dari TokoPay yang sah
        $ref_id = $data['reff_id'];
        $signature_from_tokopay = $data['signature'];
        $signature_validasi = md5($this->api->tokopay_merchant_id . ":" . $this->api->tokopay_secret_key . ":" . $ref_id);

        if ($signature_from_tokopay !== $signature_validasi) {
            \Illuminate\Support\Facades\Log::warning('TokoPay callback: Invalid signature', ['ref_id' => $ref_id]);
            return Response::json(['error' => 'Invalid Signature'], 401);
        }

        // Hanya proses jika status payment adalah Success
        if ($data['status'] !== 'Success') {
            return Response::json(['error' => 'Status payment tidak success']);
        }

        // Cari invoice berdasarkan reference
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

        // FIX #8b: Deposit saldo menggunakan DB::transaction + lockForUpdate
        // Mencegah double top-up jika TokoPay mengirim webhook dua kali bersamaan
        if (isset($dataDeposit)) {
            try {
                \Illuminate\Support\Facades\DB::transaction(function () use ($dataDeposit, $invoice) {
                    // lockForUpdate memastikan hanya satu proses yang berjalan sekaligus
                    $depositLocked = \App\Models\Deposit::where('order_id', $dataDeposit->order_id)
                        ->where('status', 'Pending') // Idempotency guard: hanya proses jika masih Pending
                        ->lockForUpdate()
                        ->first();

                    if (!$depositLocked) {
                        // Sudah diproses oleh webhook sebelumnya — abaikan
                        return;
                    }

                    $userDeposit = \App\Models\User::where('username', $depositLocked->username)
                        ->lockForUpdate()
                        ->first();

                    if ($userDeposit) {
                        $userDeposit->increment('balance', $depositLocked->jumlah);
                    }

                    $depositLocked->update(['status' => 'Success']);
                    $invoice->update(['status' => 'Lunas', 'paid_at' => now()]);
                });

                return Response::json(['success' => true]);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('TokoPay deposit callback error', ['error' => $e->getMessage(), 'order_id' => $order_id]);
                return Response::json(['error' => 'Internal server error'], 500);
            }
        } else {
            // Multi-Provider Order Processing (alur order game, bukan deposit)
            $pembelian = $dataPembeli;
            $transaction = $invoice;

            $routingService = new \App\Services\ProviderRoutingService();
            $orderProcessor = new \App\Services\OrderProcessingService($routingService);
            $waService = new \App\Services\WhatsappNotificationService();

            $result = $orderProcessor->process($pembelian);

            if ($result['success']) {
                $pembelian->update([
                    'status' => 'Sukses',
                    'provider_order_id' => $result['transaction_id'] ?? null,
                    'log' => json_encode(['result' => $result])
                ]);

                $waService->sendNotification($transaction->no_pembeli, 'transaction_success', [
                    'nickname' => $pembelian->nickname,
                    'order_id' => $pembelian->order_id,
                    'product' => $pembelian->layanan,
                    'amount' => 'Rp ' . number_format($pembelian->harga, 0, ',', '.'),
                    'sn' => 'Sedang Diproses',
                ]);

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
                    'status' => 'Pending',
                    'log' => json_encode(['error' => $result['message']])
                ]);

                $waService->sendNotification($transaction->no_pembeli, 'transaction_pending', [
                    'nickname' => $pembelian->nickname,
                    'order_id' => $pembelian->order_id,
                    'product' => $pembelian->layanan,
                    'amount' => 'Rp ' . number_format($pembelian->harga, 0, ',', '.'),
                    'status' => 'Menunggu Provider',
                ]);

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

            $invoice->update(['status' => 'Lunas', 'paid_at' => now()]);
            return Response::json(['success' => true]);
        }
    }
}
