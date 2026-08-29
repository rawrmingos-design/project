<?php

namespace Tests\Feature;

use App\Models\InvoiceNotificationDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\QueryException;
use Tests\TestCase;

class InvoiceNotificationDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_delivery_casts_payload_and_defaults_to_pending(): void
    {
        $delivery = InvoiceNotificationDelivery::query()->create([
            'order_id' => 'INV-LEDGER-001',
            'invoice_version' => 0,
            'channel' => InvoiceNotificationDelivery::CHANNEL_EMAIL,
            'transition' => 'payment_pending',
            'template_slug' => 'transaction_pending',
            'recipient' => 'buyer@example.com',
            'payload_json' => ['order_id' => 'INV-LEDGER-001', 'status' => 'Pending'],
            'idempotency_key' => hash('sha256', 'INV-LEDGER-001|0|email|payment_pending'),
        ]);

        $this->assertSame(InvoiceNotificationDelivery::STATUS_PENDING, $delivery->status);
        $this->assertIsArray($delivery->payload_json);
        $this->assertSame('INV-LEDGER-001', $delivery->payload_json['order_id']);
        $this->assertSame(0, $delivery->attempts);
    }

    public function test_idempotency_key_prevents_duplicate_delivery(): void
    {
        $attributes = [
            'order_id' => 'INV-LEDGER-002',
            'invoice_version' => 0,
            'channel' => InvoiceNotificationDelivery::CHANNEL_WHATSAPP,
            'transition' => 'provider_success',
            'template_slug' => 'transaction_success',
            'recipient' => '081234567890',
            'payload_json' => ['order_id' => 'INV-LEDGER-002'],
            'idempotency_key' => hash('sha256', 'INV-LEDGER-002|0|whatsapp|provider_success'),
        ];

        InvoiceNotificationDelivery::query()->create($attributes);

        $this->expectException(QueryException::class);
        InvoiceNotificationDelivery::query()->create($attributes);
    }
}

