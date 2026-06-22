<?php

namespace Tests\Unit\Support;

use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Support\InvoiceRealtimeStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoiceRealtimeStatusTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function channel_token_is_deterministic_hmac_for_order_id(): void
    {
        config(['app.key' => 'base64:test-app-key-for-invoice-realtime']);

        $orderId = 'INV-REALTIME-001';

        $this->assertSame(
            hash_hmac('sha256', $orderId, 'base64:test-app-key-for-invoice-realtime'),
            InvoiceRealtimeStatus::channelToken($orderId),
        );
    }

    #[Test]
    public function channel_name_contains_order_id_and_signed_token(): void
    {
        config(['app.key' => 'base64:test-app-key-for-invoice-realtime']);

        $orderId = 'INV-REALTIME-002';
        $token = hash_hmac('sha256', $orderId, 'base64:test-app-key-for-invoice-realtime');

        $this->assertSame(
            "invoice.{$orderId}.{$token}",
            InvoiceRealtimeStatus::channelName($orderId),
        );
    }

    #[Test]
    public function payload_marks_purchase_ready_only_when_payment_paid_and_order_success(): void
    {
        $purchase = Pembelian::factory()->create([
            'order_id' => 'INV-READY-001',
            'status' => 'Sukses',
        ]);

        $payment = Pembayaran::create([
            'order_id' => $purchase->order_id,
            'harga' => '50000',
            'no_pembayaran' => 'QRIS-001',
            'no_pembeli' => '08123456789',
            'status' => 'Lunas',
            'metode' => 'QRIS',
        ]);

        $payload = InvoiceRealtimeStatus::payload($purchase, $payment);

        $this->assertSame('paid', $payload['payment_status_code']);
        $this->assertSame('success', $payload['order_status_code']);
        $this->assertTrue($payload['is_payment_paid']);
        $this->assertTrue($payload['is_order_success']);
        $this->assertTrue($payload['is_purchase_ready']);
    }

    #[Test]
    public function payload_does_not_mark_purchase_ready_when_payment_paid_but_order_still_processing(): void
    {
        $purchase = Pembelian::factory()->create([
            'order_id' => 'INV-NOT-READY-001',
            'status' => 'Proses',
        ]);

        $payment = Pembayaran::create([
            'order_id' => $purchase->order_id,
            'harga' => '50000',
            'no_pembayaran' => 'QRIS-002',
            'no_pembeli' => '08123456789',
            'status' => 'Lunas',
            'metode' => 'QRIS',
        ]);

        $payload = InvoiceRealtimeStatus::payload($purchase, $payment);

        $this->assertSame('paid', $payload['payment_status_code']);
        $this->assertSame('processing', $payload['order_status_code']);
        $this->assertTrue($payload['is_payment_paid']);
        $this->assertFalse($payload['is_order_success']);
        $this->assertFalse($payload['is_purchase_ready']);
    }

    #[Test]
    public function payload_for_order_returns_null_when_purchase_or_payment_is_missing(): void
    {
        $this->assertNull(InvoiceRealtimeStatus::payloadForOrder('MISSING-ORDER'));

        Pembelian::factory()->create([
            'order_id' => 'INV-WITHOUT-PAYMENT',
            'status' => 'Sukses',
        ]);

        $this->assertNull(InvoiceRealtimeStatus::payloadForOrder('INV-WITHOUT-PAYMENT'));
    }
}
