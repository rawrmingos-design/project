<?php

namespace App\Http\Controllers;


use App\Models\Deposit;
use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Services\OrderProcessingService;
use App\Support\PembelianStatus;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;

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

        // if ($nbcb != 1) abort(404);

        /***********************************************************
         *ToverifythedataintegritysendingbyPG
        ************************************************************/
        $key0 = md5($tranID.$orderid.$status.$domain.$amount.$currency);
        $key1 = md5($paydate.$domain.$key0.$appcode.$sec_key);

        // if($skey != $key1) abort(404); //Invalidtransaction

        Log::info("RAZERPG CALLBACK : [".$request->ip()."] " . json_encode($request->all()));

        if (isset($post['orderid'])) {
            $referenceUniq = $orderid;
            $invoice = Pembayaran::where('order_id', $referenceUniq)
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
                $dataLayanan = $dataPembeli->active_layanan_id
                    ? Layanan::query()->find($dataPembeli->active_layanan_id)
                    : Layanan::where('layanan', $dataPembeli->layanan)->first();

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

                    if (in_array($dataDeposit->metode, ["QRIS", "QRISREALTIME", "OVOPUSH", "GOPAY", "SHOPEEPAY", "DANA", "ASTRAPAY", "VIRGO", "BRIVA", "BCAVA", "BNIVA", "MANDIRIVA", "PERMATAVA", "CIMBVA", "DANAMONVA", "BSIVA", "ALFAMART", "INDOMARET"])) {
                        $order['data']['status'] = true;
                    } else {
                        $order['data']['status'] = true; // Default true for others
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
                    $pembelian = $dataPembeli;
                    $transaction = $invoice;

                    $orderProcessor = app(OrderProcessingService::class);
                    $waService = new \App\Services\WhatsappNotificationService();

                    // Process Order
                    $result = $orderProcessor->process($pembelian);
                    
                    $emailService = new \App\Services\EmailNotificationService();
                    $recipientEmail = $transaction->email_pembeli ?? ($transaction->user->email ?? null);
                    $normalizedStatus = PembelianStatus::normalize($result['order_status'] ?? PembelianStatus::UNKNOWN);
                    $providerStatus = PembelianStatus::preferredDatabaseLabel($normalizedStatus);
                    $snValue = trim((string) ($result['sn'] ?? '')) ?: ($pembelian->keterangan_sn ?: 'Sedang Diproses');
                    $providerOrderId = $result['transaction_id'] ?? $pembelian->provider_order_id;

                    if (in_array($normalizedStatus, [PembelianStatus::FAILED, PembelianStatus::CANCELLED], true)) {
                        $pembelian->update([
                            'status' => $providerStatus,
                            'provider_order_id' => $providerOrderId,
                            'keterangan_sn' => $snValue,
                            'log' => json_encode(['result' => $result]),
                        ]);

                        app(\App\Services\PointService::class)->refundRedeemedPoints($pembelian);

                        $waService->sendNotification($transaction->no_pembeli, 'transaction_failed', [
                            'nickname' => $pembelian->nickname,
                            'order_id' => $pembelian->order_id,
                            'product' => $pembelian->layanan,
                            'amount' => 'Rp ' . number_format($pembelian->harga, 0, ',', '.'),
                            'reason' => trim((string) ($result['message'] ?? '')) ?: 'Transaksi gagal dari provider.',
                        ]);

                        if ($recipientEmail) {
                            $emailService->sendTransactionEmail($recipientEmail, [
                                'order_id' => $pembelian->order_id,
                                'product' => $pembelian->layanan,
                                'amount' => 'Rp ' . number_format($pembelian->harga, 0, ',', '.'),
                                'status' => PembelianStatus::apiStatusCode($providerStatus),
                                'nickname' => $pembelian->nickname,
                                'sn' => $snValue,
                                'note' => trim((string) ($result['message'] ?? '')) ?: 'Transaksi gagal dari provider.',
                            ]);
                        }
                    } elseif ($result['success']) {
                        $pembelian->update([
                            'status' => $providerStatus,
                            'provider_order_id' => $providerOrderId,
                            'keterangan_sn' => $snValue,
                            'log' => json_encode(['result' => $result]),
                        ]);

                        $notificationSlug = PembelianStatus::normalize($providerStatus) === PembelianStatus::SUCCESS
                            ? 'transaction_success'
                            : 'transaction_pending';

                        $waService->sendNotification($transaction->no_pembeli, $notificationSlug, [
                            'nickname' => $pembelian->nickname,
                            'order_id' => $pembelian->order_id,
                            'product' => $pembelian->layanan,
                            'amount' => 'Rp ' . number_format($pembelian->harga, 0, ',', '.'),
                            'sn' => $snValue,
                            'status' => PembelianStatus::label($providerStatus),
                        ]);

                        if ($recipientEmail) {
                            $emailService->sendTransactionEmail($recipientEmail, [
                                'order_id' => $pembelian->order_id,
                                'product' => $pembelian->layanan,
                                'amount' => 'Rp ' . number_format($pembelian->harga, 0, ',', '.'),
                                'status' => PembelianStatus::apiStatusCode($providerStatus),
                                'nickname' => $pembelian->nickname,
                                'sn' => $snValue,
                                'note' => PembelianStatus::normalize($providerStatus) === PembelianStatus::SUCCESS
                                    ? 'Terima kasih telah berbelanja.'
                                    : 'Pesanan sedang menunggu respon provider.',
                            ]);
                        }
                    } else {
                        $pembelian->update([
                            'status' => PembelianStatus::preferredDatabaseLabel(PembelianStatus::PENDING),
                            'provider_order_id' => $providerOrderId,
                            'log' => json_encode(['error' => $result['message'] ?? 'Order processing failed']),
                        ]);

                        $waService->sendNotification($transaction->no_pembeli, 'transaction_pending', [
                            'nickname' => $pembelian->nickname,
                            'order_id' => $pembelian->order_id,
                            'product' => $pembelian->layanan,
                            'amount' => 'Rp ' . number_format($pembelian->harga, 0, ',', '.'),
                            'status' => 'Menunggu Provider',
                        ]);

                        if ($recipientEmail) {
                            $emailService->sendTransactionEmail($recipientEmail, [
                                'order_id' => $pembelian->order_id,
                                'product' => $pembelian->layanan,
                                'amount' => 'Rp ' . number_format($pembelian->harga, 0, ',', '.'),
                                'status' => PembelianStatus::apiStatusCode(PembelianStatus::PENDING),
                                'nickname' => $pembelian->nickname,
                                'note' => 'Pesanan sedang menunggu respon provider.',
                            ]);
                        }
                    }
                    // END Multi-Provider Integration
                }
                $invoice->update(['status' => 'Lunas']);

                return true;
            } else {
                return Response::json(['error' => "Status payment tidak success"]);
            }
        } else {
            return Response::json(['error' => "Data json tidak sesuai"]);
        }
    }
}
