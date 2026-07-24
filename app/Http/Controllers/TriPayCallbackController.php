<?php

namespace App\Http\Controllers;

use App\Models\Deposit;
use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Models\User;
use App\Services\EmailNotificationService;
use App\Services\OrderProcessingService;
use App\Services\WhatsappNotificationService;
use App\Services\PublicOrderPushNotificationService;
use App\Support\PembelianStatus;
use App\Events\InvoiceStatusUpdated;
use App\Jobs\PollSufPaymentStatusJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TriPayCallbackController extends Controller
{
    protected $api;

    public function __construct()
    {
        $this->api = null;
    }

    protected function initializeApi(): void
    {
        if ($this->api) {
            return;
        }

        $this->api = DB::table('setting_webs')->where('id', 1)->first();
    }

    public function handle(Request $request)
    {
        $this->initializeApi();

        $callbackSignature = (string) $request->server('HTTP_X_CALLBACK_SIGNATURE');
        $json = $request->getContent();
        $signature = hash_hmac('sha256', $json, (string) optional($this->api)->tripay_private_key);

        if (!hash_equals($signature, $callbackSignature)) {
            return 'Invalid signature';
        }

        if ('payment_status' !== (string) $request->server('HTTP_X_CALLBACK_EVENT')) {
            return 'Invalid callback event, no action was taken';
        }

        $payload = json_decode($json, true);
        if (!is_array($payload)) {
            return response()->json(['success' => false, 'message' => 'invalid_payload'], 400);
        }

        $reference = trim((string) ($payload['reference'] ?? ''));
        $callbackStatus = strtoupper(trim((string) ($payload['status'] ?? '')));
        $callbackAmount = isset($payload['total_amount']) ? (int) $payload['total_amount'] : null;

        if ($reference === '') {
            return response()->json(['success' => false, 'message' => 'missing_reference'], 400);
        }

        if (!in_array($callbackStatus, ['PAID', 'EXPIRED', 'FAILED', 'REFUND'], true)) {
            Log::debug('Tripay callback: unrecognized payment status', [
                'status' => $callbackStatus !== '' ? $callbackStatus : null,
                'reference' => $reference,
            ]);

            return response()->json(['success' => true, 'message' => 'ignored_unrecognized_status']);
        }

        $claim = $this->claimInvoice($reference, $callbackStatus, $callbackAmount);
        $invoice = $claim['invoice'];

        if (!$invoice) {
            Log::debug('Tripay callback: invoice not found', [
                'reference' => $reference,
            ]);

            return response()->json(['success' => true, 'message' => 'ignored_invoice_not_found']);
        }

        if (($claim['state'] ?? null) === 'invalid_amount') {
            Log::warning('Tripay callback: invalid amount', [
                'reference' => $reference,
                'incoming_total_amount' => $callbackAmount,
                'expected_invoice_amount' => (int) $invoice->harga,
            ]);

            return response()->json(['success' => false, 'message' => 'invalid_amount'], 400);
        }

        if (($claim['state'] ?? null) !== 'claimed') {
            return response()->json(['success' => true, 'message' => 'already_processed']);
        }

        $orderId = $invoice->order_id;
        $pembelian = Pembelian::where('order_id', $orderId)->first();
        $deposit = $pembelian ? null : Deposit::where('order_id', $orderId)->first();

        if (!$pembelian && !$deposit) {
            Log::warning('Tripay callback: order/deposit not found after invoice claim', [
                'order_id' => $orderId,
                'reference' => $reference,
            ]);

            return response()->json(['success' => true, 'message' => 'ignored_order_not_found']);
        }

        try {
            if ($callbackStatus === 'PAID') {
                if ($deposit) {
                    $this->processPaidDeposit($deposit);
                } else {
                    $this->sendPaymentSuccessPushNotification($pembelian);
                    $this->processPaidPembelian($pembelian, $invoice);
                }

                return response()->json(['success' => true]);
            }

            if ($deposit) {
                $this->processFailedDeposit($deposit);
            } else {
                $this->processFailedPembelian($pembelian, $invoice, $callbackStatus);
            }

            return response()->json(['success' => true]);
        } catch (\Throwable $exception) {
            Log::error('Tripay callback processing error', [
                'order_id' => $orderId,
                'reference' => $reference,
                'status' => $callbackStatus,
                'error' => $exception->getMessage(),
            ]);

            if ($callbackStatus === 'PAID' && $pembelian) {
                $pembelian->update([
                    'status' => PembelianStatus::preferredDatabaseLabel(PembelianStatus::PENDING),
                    'log' => json_encode(['callback_error' => $exception->getMessage()]),
                ]);
            }

            return response()->json(['success' => true, 'message' => 'claimed_with_processing_error']);
        }
    }

    private function claimInvoice(string $reference, string $callbackStatus, ?int $callbackAmount): array
    {
        return DB::transaction(function () use ($reference, $callbackStatus, $callbackAmount): array {
            $invoice = Pembayaran::query()
                ->where('reference', $reference)
                ->lockForUpdate()
                ->first();

            if (!$invoice) {
                return ['state' => 'missing', 'invoice' => null];
            }

            if ($callbackStatus === 'PAID') {
                if ($invoice->status !== 'Belum Lunas') {
                    return ['state' => 'already_processed', 'invoice' => $invoice];
                }

                if ($callbackAmount !== null && $callbackAmount > 0 && $callbackAmount !== (int) $invoice->harga) {
                    return ['state' => 'invalid_amount', 'invoice' => $invoice];
                }

                $invoice->update([
                    'status' => 'Lunas',
                    'paid_at' => now(),
                ]);

                return ['state' => 'claimed', 'invoice' => $invoice->fresh()];
            }

            if ($invoice->status !== 'Belum Lunas') {
                return ['state' => 'already_processed', 'invoice' => $invoice];
            }

            $invoice->update([
                'status' => $callbackStatus === 'EXPIRED' ? 'Expired' : 'Batal',
            ]);

            return ['state' => 'claimed', 'invoice' => $invoice->fresh()];
        });
    }

    private function sendPaymentSuccessPushNotification(Pembelian $pembelian): void
    {
        try {
            app(PublicOrderPushNotificationService::class)
                ->notifyPaymentSuccess($pembelian->loadMissing('user'));
        } catch (\Throwable $exception) {
            Log::warning('TriPay payment success push notification failed', [
                'order_id' => $pembelian->order_id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function processPaidDeposit(Deposit $deposit): void
    {
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

    private function processFailedDeposit(Deposit $deposit): void
    {
        DB::transaction(function () use ($deposit): void {
            $depositLocked = Deposit::query()
                ->whereKey($deposit->getKey())
                ->lockForUpdate()
                ->first();

            if (!$depositLocked || $depositLocked->status !== 'Pending') {
                return;
            }

            $depositLocked->update(['status' => 'Gagal']);
        });
    }

    private function sendOrderSuccessPushNotification(Pembelian $pembelian): void
    {
        try {
            app(PublicOrderPushNotificationService::class)
                ->notifyOrderSuccess($pembelian->loadMissing('user'));
        } catch (\Throwable $exception) {
            Log::warning('TriPay order success push notification failed', [
                'order_id' => $pembelian->order_id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function processPaidPembelian(Pembelian $pembelian, Pembayaran $invoice): void
    {
        $orderProcessor = app(OrderProcessingService::class);
        $waService = app(WhatsappNotificationService::class);
        $emailService = app(EmailNotificationService::class);

        $result = $orderProcessor->process($pembelian);
        $normalizedStatus = PembelianStatus::normalize($result['order_status'] ?? PembelianStatus::UNKNOWN);
        $providerStatus = PembelianStatus::preferredDatabaseLabel($normalizedStatus);
        $snValue = trim((string) ($result['sn'] ?? '')) ?: ($pembelian->keterangan_sn ?: 'Sedang Diproses');
        $providerOrderId = $result['transaction_id'] ?? $pembelian->provider_order_id;
        $recipientEmail = $pembelian->email_pembeli ?? ($pembelian->user->email ?? null);

        if (in_array($normalizedStatus, [PembelianStatus::FAILED, PembelianStatus::CANCELLED], true)) {
            $pembelian->update([
                'status' => $providerStatus,
                'provider_order_id' => $providerOrderId,
                'keterangan_sn' => $snValue,
                'log' => json_encode(['result' => $result]),
            ]);
            InvoiceStatusUpdated::dispatchForOrder((string) $pembelian->order_id);

            app(\App\Services\PointService::class)->refundRedeemedPoints($pembelian);

            $waService->sendNotification($invoice->no_pembeli, 'transaction_failed', [
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

            return;
        }

        if (($result['success'] ?? false) === true) {
            $pembelian->update([
                'status' => $providerStatus,
                'provider_order_id' => $providerOrderId,
                'active_attempt_token' => $providerOrderId,
                'keterangan_sn' => $snValue,
                'log' => json_encode(['result' => $result]),
            ]);
            InvoiceStatusUpdated::dispatchForOrder((string) $pembelian->order_id);

            $freshPembelian = $pembelian->fresh(['pembayaran']);
            if ($freshPembelian) {
                PollSufPaymentStatusJob::dispatchIfNeeded($freshPembelian, $providerOrderId, $providerStatus);
            }

            $notificationSlug = PembelianStatus::normalize($providerStatus) === PembelianStatus::SUCCESS
                ? 'transaction_success'
                : 'transaction_pending';

            if (PembelianStatus::normalize($providerStatus) === PembelianStatus::SUCCESS) {
                $this->sendOrderSuccessPushNotification($pembelian);
            }

            $waService->sendNotification($invoice->no_pembeli, $notificationSlug, [
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
                        ? 'Harap Simpan Invoice ini, akan digunakan untuk verifikasi transaksi.'
                        : 'Pesanan sedang menunggu respon provider. Invoice ini akan digunakan untuk verifikasi transaksi.',
                ]);
            }

            return;
        }

        $pembelian->update([
            'status' => PembelianStatus::preferredDatabaseLabel(PembelianStatus::PENDING),
            'provider_order_id' => $providerOrderId,
            'log' => json_encode(['error' => $result['message'] ?? 'Order processing failed']),
        ]);
        InvoiceStatusUpdated::dispatchForOrder((string) $pembelian->order_id);

        $waService->sendNotification($invoice->no_pembeli, 'transaction_pending', [
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
                'note' => 'Pesanan sedang menunggu respon provider. Invoice ini akan digunakan untuk verifikasi transaksi.',
            ]);
        }
    }

    private function processFailedPembelian(Pembelian $pembelian, Pembayaran $invoice, string $callbackStatus): void
    {
        if ($callbackStatus === 'EXPIRED') {
            $invoice->syncExpiredPembelianStatus();
            $pembelian->refresh();
        } else {
            $pembelian->update([
                'status' => PembelianStatus::preferredDatabaseLabel(PembelianStatus::FAILED),
            ]);
        }

        InvoiceStatusUpdated::dispatchForOrder((string) $pembelian->order_id);

        app(\App\Services\PointService::class)->refundRedeemedPoints($pembelian);

        $waService = app(WhatsappNotificationService::class);
        $waService->sendNotification($invoice->no_pembeli, 'transaction_failed', [
            'nickname' => $pembelian->nickname,
            'order_id' => $pembelian->order_id,
            'product' => $pembelian->layanan,
            'reason' => $this->failureReasonFromCallbackStatus($callbackStatus),
        ]);

        $emailService = app(EmailNotificationService::class);
        $recipientEmail = $pembelian->email_pembeli ?? ($pembelian->user->email ?? null);
        if ($recipientEmail) {
            $emailService->sendTransactionEmail($recipientEmail, [
                'order_id' => $pembelian->order_id,
                'product' => $pembelian->layanan,
                'amount' => 'Rp ' . number_format($pembelian->harga, 0, ',', '.'),
                'status' => PembelianStatus::apiStatusCode($callbackStatus === 'EXPIRED' ? PembelianStatus::EXPIRED : PembelianStatus::FAILED),
                'nickname' => $pembelian->nickname,
                'note' => 'Mohon maaf, transaksi Anda gagal atau kadaluarsa. Invoice ini akan digunakan untuk verifikasi transaksi.',
            ]);
        }
    }

    private function failureReasonFromCallbackStatus(string $callbackStatus): string
    {
        return match ($callbackStatus) {
            'EXPIRED' => 'Pembayaran Kadaluarsa',
            'REFUND' => 'Pembayaran di-refund',
            default => 'Pembayaran Gagal',
        };
    }
}
