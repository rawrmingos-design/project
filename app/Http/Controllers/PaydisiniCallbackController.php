<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Models\Deposit;
use App\Models\User;
use App\Services\EmailNotificationService;
use App\Services\OrderProcessingService;
use App\Services\WhatsappNotificationService;
use App\Support\PembelianStatus;

class PaydisiniCallbackController extends Controller
{
    private $apiKey;

    public function __construct()
    {
        $this->apiKey = \DB::table('setting_webs')->where('id', 1)->first()->paydisini_apikey;
    }
    
    public function callbackTransaction(Request $request)
    {
        $key = $request->input('key');
        $payId = (string) $request->input('pay_id', '');
        $uniqueCode = (string) $request->input('unique_code', '');
        $status = strtolower((string) $request->input('status', ''));
        $signature = (string) $request->input('signature', '');

        if ($key !== $this->apiKey) {
            return response()->json(['success' => false, 'message' => 'Invalid API Key'], 400);
        }

        if ($uniqueCode === '') {
            return response()->json(['success' => false, 'message' => 'Missing unique_code'], 400);
        }

        // Sesuai docs Paydisini: md5(key . unique_code . 'CallbackStatus')
        $expectedSignature = md5($this->apiKey . $uniqueCode . 'CallbackStatus');
        if ($signature !== $expectedSignature) {
            return response()->json(['success' => false, 'message' => 'Invalid signature'], 400);
        }

        if (!in_array($status, ['success', 'canceled'], true)) {
            // ACK supaya provider tidak retry untuk status yang tidak kita proses.
            return response()->json(['success' => true, 'message' => 'ignored_non_terminal_status']);
        }

        $claim = $this->claimInvoice($uniqueCode, $status);
        $transaction = $claim['invoice'];

        if (!$transaction) {
            Log::warning('Paydisini callback: transaction not found', [
                'unique_code' => $uniqueCode,
                'pay_id' => $payId,
            ]);

            return response()->json(['success' => true, 'message' => 'ignored_transaction_not_found']);
        }

        if (($claim['state'] ?? null) !== 'claimed') {
            return response()->json(['success' => true, 'message' => 'already_processed']);
        }

        try {
            if ($status === 'canceled') {
                $this->handleCanceledCallback($uniqueCode, $transaction);
                return response()->json(['success' => true]);
            }

            $this->handleSuccessCallback($uniqueCode, $transaction);
            return response()->json(['success' => true]);
        } catch (\Throwable $exception) {
            Log::error('Paydisini callback processing error', [
                'unique_code' => $uniqueCode,
                'pay_id' => $payId,
                'error' => $exception->getMessage(),
            ]);

            return response()->json(['success' => true, 'message' => 'claimed_with_processing_error']);
        }
    }

    private function claimInvoice(string $uniqueCode, string $status): array
    {
        return DB::transaction(function () use ($uniqueCode, $status): array {
            $invoice = Pembayaran::query()
                ->where('order_id', $uniqueCode)
                ->lockForUpdate()
                ->first();

            if (!$invoice) {
                return ['state' => 'missing', 'invoice' => null];
            }

            if ($status === 'success') {
                if ($invoice->status !== 'Belum Lunas') {
                    return ['state' => 'already_processed', 'invoice' => $invoice];
                }

                $invoice->update([
                    'status' => 'Lunas',
                    'paid_at' => now(),
                ]);

                return ['state' => 'claimed', 'invoice' => $invoice->fresh()];
            }

            // canceled
            if ($invoice->status === 'Belum Lunas') {
                $invoice->update(['status' => 'Expired']);
                return ['state' => 'claimed', 'invoice' => $invoice->fresh()];
            }

            return ['state' => 'already_processed', 'invoice' => $invoice];
        });
    }

    private function handleSuccessCallback(string $uniqueCode, Pembayaran $transaction): void
    {
        $pembelian = Pembelian::query()->where('order_id', $uniqueCode)->first();
        if ($pembelian) {
            $this->processPembelian($pembelian, $transaction);
            return;
        }

        $deposit = Deposit::query()->where('order_id', $uniqueCode)->first();
        if ($deposit) {
            DB::transaction(function () use ($deposit): void {
                $depositLocked = Deposit::query()
                    ->whereKey($deposit->getKey())
                    ->lockForUpdate()
                    ->first();

                if (!$depositLocked || $depositLocked->status !== 'Pending') {
                    return;
                }

                $user = User::query()
                    ->where('username', $depositLocked->username)
                    ->lockForUpdate()
                    ->first();

                if ($user) {
                    $user->increment('balance', $depositLocked->jumlah);
                }

                $depositLocked->update(['status' => 'Success']);
            });
        }
    }

    private function handleCanceledCallback(string $uniqueCode, Pembayaran $transaction): void
    {
        $pembelian = Pembelian::query()->where('order_id', $uniqueCode)->first();
        if (!$pembelian) {
            return;
        }

        $pembelian->update(['status' => PembelianStatus::preferredDatabaseLabel(PembelianStatus::FAILED)]);
        app(\App\Services\PointService::class)->refundRedeemedPoints($pembelian);

        $waService = app(WhatsappNotificationService::class);
        $waService->sendNotification($transaction->no_pembeli, 'transaction_failed', [
            'nickname' => $pembelian->nickname,
            'order_id' => $pembelian->order_id,
            'product' => $pembelian->layanan,
            'reason' => 'Pembayaran Dibatalkan',
        ]);

        $emailService = app(EmailNotificationService::class);
        $recipientEmail = $pembelian->email_pembeli ?? ($pembelian->user->email ?? null);
        if ($recipientEmail) {
            $emailService->sendTransactionEmail($recipientEmail, [
                'order_id' => $pembelian->order_id,
                'product' => $pembelian->layanan,
                'amount' => 'Rp ' . number_format($pembelian->harga, 0, ',', '.'),
                'status' => PembelianStatus::apiStatusCode(PembelianStatus::FAILED),
                'nickname' => $pembelian->nickname,
                'note' => 'Pembayaran dibatalkan atau kadaluarsa.',
            ]);
        }
    }

    private function processPembelian(Pembelian $pembelian, Pembayaran $transaction): void
    {
        $orderProcessor = app(OrderProcessingService::class);
        $waService = app(WhatsappNotificationService::class);
        $emailService = app(EmailNotificationService::class);

        $result = $orderProcessor->process($pembelian);
        $normalizedStatus = PembelianStatus::normalize($result['order_status'] ?? PembelianStatus::UNKNOWN);
        $providerStatus = PembelianStatus::preferredDatabaseLabel($normalizedStatus);
        $snValue = trim((string) ($result['sn'] ?? '')) ?: ($pembelian->keterangan_sn ?: 'Sedang Diproses');

        if (in_array($normalizedStatus, [PembelianStatus::FAILED, PembelianStatus::CANCELLED], true)) {
            $pembelian->update([
                'status' => $providerStatus,
                'provider_order_id' => $result['transaction_id'] ?? $pembelian->provider_order_id,
                'keterangan_sn' => $snValue,
                'log' => json_encode(['result' => $result]),
            ]);

            $waService->sendNotification($transaction->no_pembeli, 'transaction_failed', [
                'nickname' => $pembelian->nickname,
                'order_id' => $pembelian->order_id,
                'product' => $pembelian->layanan,
                'amount' => 'Rp ' . number_format($pembelian->harga, 0, ',', '.'),
                'reason' => trim((string) ($result['message'] ?? '')) ?: 'Transaksi gagal dari provider.',
            ]);

            $recipientEmail = $pembelian->email_pembeli ?? ($pembelian->user->email ?? null);
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

            return;
        }

        if (($result['success'] ?? false) === true) {
            $pembelian->update([
                'status' => $providerStatus,
                'provider_order_id' => $result['transaction_id'] ?? $pembelian->provider_order_id,
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

            $recipientEmail = $pembelian->email_pembeli ?? ($pembelian->user->email ?? null);
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

            return;
        }

        $pembelian->update([
            'status' => PembelianStatus::preferredDatabaseLabel(PembelianStatus::PENDING),
            'log' => json_encode(['error' => $result['message'] ?? 'Order processing failed']),
        ]);

        $waService->sendNotification($transaction->no_pembeli, 'transaction_pending', [
            'nickname' => $pembelian->nickname,
            'order_id' => $pembelian->order_id,
            'product' => $pembelian->layanan,
            'amount' => 'Rp ' . number_format($pembelian->harga, 0, ',', '.'),
            'status' => 'Menunggu Provider',
        ]);

        $recipientEmail = $pembelian->email_pembeli ?? ($pembelian->user->email ?? null);
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
}
