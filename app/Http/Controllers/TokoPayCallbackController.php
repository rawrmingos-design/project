<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Models\Deposit;
use App\Models\User;
use App\Services\EmailNotificationService;
use App\Services\OrderProcessingService;
use App\Services\WhatsappNotificationService;
use App\Services\PublicOrderPushNotificationService;
use App\Support\PembelianStatus;
use App\Events\InvoiceStatusUpdated;

class TokoPayCallbackController extends Controller
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

        $this->api = \DB::table('setting_webs')->where('id', 1)->first();
    }

    public function handle(Request $request)
    {
        $this->initializeApi();

        $json = $request->getContent();
        $data = json_decode($json, true);

        if (!isset($data['status'], $data['reff_id'], $data['signature'])) {
            return Response::json(['status' => false, 'message' => 'Data json tidak sesuai'], 400);
        }

        $refId = (string) $data['reff_id'];
        $reference = (string) ($data['reference'] ?? '');
        $signature_from_tokopay = $data['signature'];
        $signature_validasi = md5($this->api->tokopay_merchant_id . ":" . $this->api->tokopay_secret_key . ":" . $refId);

        if ($signature_from_tokopay !== $signature_validasi) {
            Log::warning('TokoPay callback: Invalid signature', ['ref_id' => $refId]);
            return Response::json(['status' => false, 'message' => 'Invalid Signature'], 401);
        }

        // Docs TokoPay menampilkan Success/Completed sebagai status berhasil.
        $incomingStatus = strtolower((string) $data['status']);
        if (!in_array($incomingStatus, ['success', 'completed'], true)) {
            return Response::json(['status' => true, 'message' => 'ignored_non_paid_status']);
        }

        if ($reference === '') {
            return Response::json(['status' => false, 'message' => 'reference tidak ditemukan'], 400);
        }

        $claim = $this->claimPaidInvoice($reference);
        $invoice = $claim['invoice'];

        if (!$invoice) {
            Log::debug('TokoPay callback: invoice not found', [
                'reference' => $reference,
                'reff_id' => $refId,
            ]);

            // Balas true agar gateway berhenti retry callback untuk referensi yang tidak dikenal.
            return Response::json(['status' => true, 'message' => 'ignored_invoice_not_found']);
        }

        if (($claim['state'] ?? null) !== 'claimed') {
            // Sudah diproses callback sebelumnya => idempotent ACK.
            return Response::json(['status' => true, 'message' => 'already_processed']);
        }

        $order_id = $invoice->order_id;
        $dataPembeli = Pembelian::where('order_id', $order_id)->first();
        $dataDeposit = $dataPembeli ? null : Deposit::where('order_id', $order_id)->first();

        if (!$dataPembeli && !$dataDeposit) {
            Log::warning('TokoPay callback: order/deposit not found after invoice claim', [
                'order_id' => $order_id,
                'reference' => $reference,
            ]);

            return Response::json(['status' => true, 'message' => 'ignored_order_not_found']);
        }

        try {
            if ($dataDeposit) {
                $this->processDeposit($dataDeposit, $invoice);
            } else {
                $this->sendPaymentSuccessPushNotification($dataPembeli);
                $this->processPembelian($dataPembeli, $invoice);
            }
        } catch (\Throwable $exception) {
            Log::error('TokoPay callback processing error', [
                'order_id' => $order_id,
                'reference' => $reference,
                'error' => $exception->getMessage(),
            ]);

            // Invoice sudah di-claim lunas agar callback retry tidak trigger duplicate order.
            if ($dataPembeli) {
                $dataPembeli->update([
                    'status' => PembelianStatus::preferredDatabaseLabel(PembelianStatus::PENDING),
                    'log' => json_encode(['callback_error' => $exception->getMessage()]),
                ]);
            }
        }

        return Response::json(['status' => true]);
    }

    private function claimPaidInvoice(string $reference): array
    {
        return DB::transaction(function () use ($reference): array {
            $invoice = Pembayaran::query()
                ->where('reference', $reference)
                ->lockForUpdate()
                ->first();

            if (!$invoice) {
                return ['state' => 'missing', 'invoice' => null];
            }

            if ($invoice->status !== 'Belum Lunas') {
                return ['state' => 'already_processed', 'invoice' => $invoice];
            }

            $invoice->update([
                'status' => 'Lunas',
                'paid_at' => now(),
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
            Log::warning('TokoPay payment success push notification failed', [
                'order_id' => $pembelian->order_id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function processDeposit(Deposit $deposit, Pembayaran $invoice): void
    {
        DB::transaction(function () use ($deposit): void {
            $depositLocked = Deposit::query()
                ->whereKey($deposit->getKey())
                ->lockForUpdate()
                ->first();

            if (!$depositLocked || $depositLocked->status !== 'Pending') {
                return;
            }

            $userDeposit = User::query()
                ->where('username', $depositLocked->username)
                ->lockForUpdate()
                ->first();

            if ($userDeposit) {
                $userDeposit->increment('balance', $depositLocked->jumlah);
            }

            $depositLocked->update(['status' => 'Success']);
        });
    }

    private function processPembelian(Pembelian $pembelian, Pembayaran $invoice): void
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
            InvoiceStatusUpdated::dispatchForOrder((string) $pembelian->order_id);

            $waService->sendNotification($invoice->no_pembeli, 'transaction_failed', [
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
            InvoiceStatusUpdated::dispatchForOrder((string) $pembelian->order_id);

            $notificationSlug = PembelianStatus::normalize($providerStatus) === PembelianStatus::SUCCESS
                ? 'transaction_success'
                : 'transaction_pending';

            $waService->sendNotification($invoice->no_pembeli, $notificationSlug, [
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
        InvoiceStatusUpdated::dispatchForOrder((string) $pembelian->order_id);

        $waService->sendNotification($invoice->no_pembeli, 'transaction_pending', [
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
