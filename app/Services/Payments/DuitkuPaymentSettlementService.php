<?php

namespace App\Services\Payments;

use App\Jobs\PollSufPaymentStatusJob;
use App\Models\Deposit;
use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Models\User;
use App\Services\InvoiceNotificationDispatcher;
use App\Services\OrderProcessingService;
use App\Services\PointService;
use App\Services\PublicOrderPushNotificationService;
use App\Services\WhatsappNotificationService;
use App\Support\PembelianStatus;
use Illuminate\Support\Facades\Log;

class DuitkuPaymentSettlementService
{
    public function __construct(
        private readonly OrderProcessingService $orderProcessor,
        private readonly WhatsappNotificationService $whatsappService,
    ) {
    }

    public function settle(Pembayaran $payment, ?string $reference = null, ?string $merchantOrderId = null): string
    {
        $order = Pembelian::query()->where('order_id', $payment->order_id)->first();
        $deposit = null;

        if (! $order) {
            $deposit = Deposit::query()->where('order_id', $payment->order_id)->first();
        }

        if (in_array($payment->normalizedStatus(), ['lunas', 'paid', 'success'], true)) {
            return 'duplicate';
        }

        $wasAlreadyPaid = false;

        if (! $order && ! $deposit) {
            throw new DuitkuCallbackException('Order not found', 422, 'order_not_found');
        }

        $payment->forceFill([
            
            'status' => 'Lunas',
            'paid_at' => $payment->paid_at ?: now(),
            'reference' => $reference ?: $payment->reference,
            'duitku_reference' => $reference ?: $payment->duitku_reference,
            'duitku_merchant_order_id' => $merchantOrderId ?: $payment->duitku_merchant_order_id,
        ])->save();

        if ($deposit) {
            $this->settleDeposit($payment, $deposit);

            return $wasAlreadyPaid ? 'recovered' : 'paid';
        }

        $this->settlePurchase($payment, $order);

        return $wasAlreadyPaid ? 'recovered' : 'paid';
    }

    private function settleDeposit(Pembayaran $payment, Deposit $deposit): void
    {
        if (strtolower(trim((string) $deposit->status)) === 'success') {
            return;
        }

        $deposit->update(['status' => 'Success']);

        $user = User::query()->where('username', $deposit->username)->lockForUpdate()->first();
        if ($user) {
            $user->increment('balance', (int) $deposit->jumlah);
        }

        $this->runSafely('deposit_buyer_notification', function () use ($payment, $deposit): void {
            $this->whatsappService->sendNotification($payment->no_pembeli, 'deposit_success', [
                'username' => $deposit->username,
                'order_id' => $payment->order_id,
                'amount' => 'Rp ' . number_format((int) $payment->harga, 0, ',', '.'),
                'status' => 'Berhasil',
            ]);
        }, $payment->order_id);
    }

    private function settlePurchase(Pembayaran $payment, Pembelian $order): void
    {
        $this->runSafely('payment_push_notification', function () use ($order): void {
            app(PublicOrderPushNotificationService::class)->notifyPaymentSuccess($order->loadMissing('user'));
        }, $payment->order_id);

        try {
            $result = $this->orderProcessor->process($order);
        } catch (\Throwable $exception) {
            Log::error('duitku.fulfillment.exception', [
                'order_id' => $payment->order_id,
                'error' => $exception->getMessage(),
            ]);
            $result = [
                'success' => false,
                'message' => 'Provider processing exception.',
                'order_status' => PembelianStatus::PENDING,
            ];
        }

        $transactionId = $result['transaction_id'] ?? null;
        $normalizedStatus = PembelianStatus::normalize($result['order_status'] ?? PembelianStatus::UNKNOWN);
        $providerStatus = PembelianStatus::preferredDatabaseLabel($normalizedStatus);
        $snValue = trim((string) ($result['sn'] ?? '')) ?: ($order->keterangan_sn ?: 'Sedang Diproses');

        if (in_array($normalizedStatus, [PembelianStatus::FAILED, PembelianStatus::CANCELLED], true)) {
            $data = [
                'status' => $providerStatus,
                'keterangan_sn' => $snValue,
            ];
            if ($transactionId) {
                $data['provider_order_id'] = $transactionId;
            }
            $order->update($data);
            app(PointService::class)->refundRedeemedPoints($order);

            return;
        }

        if ((bool) ($result['success'] ?? false)) {
            $data = [
                'status' => $providerStatus,
                'keterangan_sn' => $snValue,
            ];
            if ($transactionId) {
                $data['provider_order_id'] = $transactionId;
                $data['active_attempt_token'] = $transactionId;
            }
            $order->update($data);

            $freshOrder = $order->fresh(['pembayaran']);
            if ($freshOrder) {
                PollSufPaymentStatusJob::dispatchIfNeeded($freshOrder, $transactionId, $providerStatus);
            }

            $this->notifyPurchaseResult($payment, $order, $providerStatus, $snValue);

            return;
        }

        $order->update(['status' => PembelianStatus::preferredDatabaseLabel(PembelianStatus::PENDING)]);
        Log::warning('duitku.fulfillment.pending', [
            'order_id' => $payment->order_id,
            'message' => $result['message'] ?? 'Provider processing failed.',
        ]);
    }

    private function notifyPurchaseResult(Pembayaran $payment, Pembelian $order, string $providerStatus, string $snValue): void
    {
        $normalizedStatus = PembelianStatus::normalize($providerStatus);
        $transition = match ($normalizedStatus) {
            PembelianStatus::SUCCESS => InvoiceNotificationDispatcher::TRANSITION_PROVIDER_SUCCESS,
            PembelianStatus::FAILED, PembelianStatus::CANCELLED => InvoiceNotificationDispatcher::TRANSITION_PROVIDER_FAILED,
            default => InvoiceNotificationDispatcher::TRANSITION_PAYMENT_PAID,
        };

        $this->runSafely('purchase_notification_dispatch', function () use ($order, $transition): void {
            app(InvoiceNotificationDispatcher::class)->dispatchForTransition(
                $order->fresh(),
                $transition,
            );
        }, $payment->order_id);
    }

    private function runSafely(string $context, callable $callback, string $orderId): void
    {
        try {
            $callback();
        } catch (\Throwable $exception) {
            Log::warning('duitku.' . $context . '.failed', [
                'order_id' => $orderId,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
