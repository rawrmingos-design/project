<?php

namespace App\Support;

use App\Models\Pembayaran;
use App\Models\Pembelian;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class InvoiceRealtimeStatus
{
    public static function channelToken(string $orderId): string
    {
        $key = (string) Config::get('app.key');

        return hash_hmac('sha256', $orderId, $key);
    }

    public static function channelName(string $orderId): string
    {
        return 'invoice.' . $orderId . '.' . self::channelToken($orderId);
    }

    public static function payloadForOrder(string $orderId): ?array
    {
        $payment = Pembayaran::query()
            ->where('order_id', $orderId)
            ->latest('id')
            ->first();

        $purchase = Pembelian::query()
            ->where('order_id', $orderId)
            ->first();

        if (! $payment || ! $purchase) {
            return null;
        }

        return self::payload($purchase, $payment);
    }

    public static function payload(Pembelian $purchase, ?Pembayaran $payment = null): array
    {
        $payment ??= Pembayaran::query()
            ->where('order_id', $purchase->order_id)
            ->latest('id')
            ->first();

        $paymentStatus = (string) ($payment?->status ?? '');
        $orderStatus = (string) ($purchase->status ?? '');
        $paymentCode = self::normalizePaymentStatus($paymentStatus);
        $orderCode = self::normalizeOrderStatus($orderStatus);

        return [
            'order_id' => (string) $purchase->order_id,
            'payment_status' => $paymentStatus,
            'order_status' => $orderStatus,
            'payment_status_code' => $paymentCode,
            'order_status_code' => $orderCode,
            'is_payment_paid' => $paymentCode === 'paid',
            'is_order_success' => $orderCode === 'success',
            'is_purchase_ready' => $paymentCode === 'paid' && $orderCode === 'success',
        ];
    }

    private static function normalizePaymentStatus(string $status): string
    {
        $normalized = Str::of($status)->lower()->squish()->value();

        return match ($normalized) {
            'paid', 'lunas', 'success', 'sukses' => 'paid',
            'expired', 'kedaluwarsa' => 'expired',
            'batal', 'cancelled', 'canceled', 'failed', 'gagal' => 'failed',
            default => 'unpaid',
        };
    }

    private static function normalizeOrderStatus(string $status): string
    {
        $normalized = Str::of($status)->lower()->squish()->value();

        return match ($normalized) {
            'sukses', 'success', 'completed', 'complete' => 'success',
            'proses', 'processing', 'process' => 'processing',
            'gagal', 'failed', 'batal', 'cancelled', 'canceled' => 'failed',
            default => 'pending',
        };
    }
}
