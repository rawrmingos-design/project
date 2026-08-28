<?php

namespace Tests\Feature;

use App\Jobs\SendInvoiceNotificationJob;
use App\Models\InvoiceNotificationDelivery;
use App\Models\Pembelian;
use App\Models\Pembayaran;
use App\Models\SettingWeb;
use App\Services\InvoiceNotificationDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class InvoiceNotificationDispatcherTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatcher_creates_one_delivery_per_enabled_channel(): void
    {
        Queue::fake();
        $order = $this->createOrder();

        $dispatcher = app(InvoiceNotificationDispatcher::class);
        $first = $dispatcher->dispatchForTransition(
            $order,
            InvoiceNotificationDispatcher::TRANSITION_PAYMENT_PAID,
        );
        $second = $dispatcher->dispatchForTransition(
            $order->fresh(),
            InvoiceNotificationDispatcher::TRANSITION_PAYMENT_PAID,
        );

        $this->assertCount(2, $first);
        $this->assertCount(2, $second);
        $this->assertDatabaseCount('invoice_notification_deliveries', 2);
        Queue::assertPushed(SendInvoiceNotificationJob::class, 2);
    }

    public function test_dispatcher_respects_disabled_channels_and_missing_recipient(): void
    {
        Queue::fake();
        $this->createSettings([
            'invoice_notify_via_whatsapp' => false,
            'invoice_notify_via_email' => true,
        ]);
        $order = Pembelian::factory()->create([
            'order_id' => 'INV-DISPATCH-EMAIL-001',
            'email_pembeli' => null,
        ]);

        $deliveries = app(InvoiceNotificationDispatcher::class)->dispatchForTransition(
            $order,
            InvoiceNotificationDispatcher::TRANSITION_PROVIDER_SUCCESS,
        );

        $this->assertCount(0, $deliveries);
        $this->assertDatabaseCount('invoice_notification_deliveries', 0);
        Queue::assertNothingPushed();
    }

    private function createOrder(): Pembelian
    {
        $this->createSettings();
        $order = Pembelian::factory()->create([
            'order_id' => 'INV-DISPATCH-001',
            'email_pembeli' => 'buyer@example.com',
        ]);
        Pembayaran::query()->create([
            'order_id' => $order->order_id,
            'harga' => '12000',
            'no_pembayaran' => 'QRIS-001',
            'no_pembeli' => '081234567890',
            'status' => 'Lunas',
            'metode' => 'TRIPAY',
        ]);

        return $order->fresh();
    }

    private function createSettings(array $overrides = []): SettingWeb
    {
        return SettingWeb::query()->create(array_merge([
            'id' => 1,
            'judul_web' => 'Test Web',
            'deskripsi_web' => 'Test Description',
            'keywords' => 'test',
            'url_wa' => 'https://wa.me/628123456789',
            'url_ig' => 'https://instagram.com/test',
            'url_tiktok' => 'https://tiktok.com/@test',
            'url_youtube' => 'https://youtube.com/test',
            'url_fb' => 'https://facebook.com/test',
            'topupindo_api' => 'topupindo-test',
            'warna1' => '#111111',
            'warna2' => '#222222',
            'warna3' => '#333333',
            'warna4' => '#444444',
            'paydisini_apikey' => 'paydisini-test-key',
            'order_prefik' => 'INV',
            'wa_provider' => 'fonnte',
            'wa_key' => 'fonnte-token',
            'invoice_notify_via_whatsapp' => true,
            'invoice_notify_via_email' => true,
        ], $overrides));
    }
}

