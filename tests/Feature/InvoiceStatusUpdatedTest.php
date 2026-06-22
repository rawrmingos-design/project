<?php

namespace Tests\Feature;

use App\Events\InvoiceStatusUpdated;
use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Support\InvoiceRealtimeStatus;
use Illuminate\Broadcasting\Channel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoiceStatusUpdatedTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function event_broadcasts_on_signed_invoice_channel_with_safe_payload(): void
    {
        config(['app.key' => 'base64:test-app-key-for-invoice-event']);

        $payload = [
            'order_id' => 'INV-EVENT-001',
            'payment_status' => 'Lunas',
            'order_status' => 'Sukses',
            'payment_status_code' => 'paid',
            'order_status_code' => 'success',
            'is_payment_paid' => true,
            'is_order_success' => true,
            'is_purchase_ready' => true,
        ];

        $event = new InvoiceStatusUpdated($payload);
        $channels = $event->broadcastOn();

        $this->assertInstanceOf(Channel::class, $channels);
        $this->assertSame('InvoiceStatusUpdated', $event->broadcastAs());
        $this->assertSame($payload, $event->broadcastWith());
        $this->assertStringContainsString(
            InvoiceRealtimeStatus::channelName('INV-EVENT-001'),
            (string) $channels,
        );
    }

    #[Test]
    public function dispatch_for_order_dispatches_invoice_status_event_with_purchase_ready_payload(): void
    {
        Event::fake([InvoiceStatusUpdated::class]);

        $purchase = Pembelian::factory()->create([
            'order_id' => 'INV-DISPATCH-001',
            'status' => 'Sukses',
        ]);

        Pembayaran::create([
            'order_id' => $purchase->order_id,
            'harga' => '50000',
            'no_pembayaran' => 'QRIS-003',
            'no_pembeli' => '08123456789',
            'status' => 'Lunas',
            'metode' => 'QRIS',
        ]);

        InvoiceStatusUpdated::dispatchForOrder($purchase->order_id);

        Event::assertDispatched(InvoiceStatusUpdated::class, function (InvoiceStatusUpdated $event) use ($purchase) {
            return $event->payload['order_id'] === $purchase->order_id
                && $event->payload['payment_status_code'] === 'paid'
                && $event->payload['order_status_code'] === 'success'
                && $event->payload['is_purchase_ready'] === true;
        });
    }

    #[Test]
    public function dispatch_for_order_does_not_dispatch_when_payload_cannot_be_built(): void
    {
        Event::fake([InvoiceStatusUpdated::class]);

        Pembelian::factory()->create([
            'order_id' => 'INV-DISPATCH-MISSING-PAYMENT',
            'status' => 'Sukses',
        ]);

        InvoiceStatusUpdated::dispatchForOrder('INV-DISPATCH-MISSING-PAYMENT');
        InvoiceStatusUpdated::dispatchForOrder('INV-DISPATCH-MISSING-ORDER');

        Event::assertNotDispatched(InvoiceStatusUpdated::class);
    }
}
