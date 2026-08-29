<?php

namespace Tests\Feature;

use App\Models\Deposit;
use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Models\SettingWeb;
use App\Models\User;
use App\Services\EmailNotificationService;
use App\Services\OrderProcessingService;
use App\Services\WhatsappNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class TriPayCallbackControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_paid_callback_does_not_double_credit_deposit_balance(): void
    {
        $settings = $this->createSettings();

        $user = User::factory()->create([
            'username' => 'tripay-user',
            'balance' => 10_000,
        ]);

        Deposit::query()->create([
            'order_id' => 'DEP-TRIPAY-001',
            'username' => $user->username,
            'metode' => 'QRIS',
            'no_pembayaran' => 'TRIPAY-VA-001',
            'jumlah' => 25_000,
            'status' => 'Pending',
        ]);

        Pembayaran::query()->create([
            'order_id' => 'DEP-TRIPAY-001',
            'harga' => '25000',
            'no_pembayaran' => 'TRIPAY-VA-001',
            'no_pembeli' => '081234567890',
            'status' => 'Belum Lunas',
            'metode' => 'TRIPAY',
            'reference' => 'TRIPAY-REF-001',
        ]);

        $payload = [
            'reference' => 'TRIPAY-REF-001',
            'merchant_ref' => 'DEP-TRIPAY-001',
            'status' => 'PAID',
            'total_amount' => 25000,
        ];

        $signature = hash_hmac('sha256', json_encode($payload), $settings->tripay_private_key);

        $this->withHeaders([
            'X-CALLBACK-SIGNATURE' => $signature,
            'X-CALLBACK-EVENT' => 'payment_status',
        ])->postJson('/wejizy/tripay/callback', $payload)
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->withHeaders([
            'X-CALLBACK-SIGNATURE' => $signature,
            'X-CALLBACK-EVENT' => 'payment_status',
        ])->postJson('/wejizy/tripay/callback', $payload)
            ->assertOk()
            ->assertJson(['success' => true, 'message' => 'already_processed']);

        $this->assertDatabaseHas('deposits', [
            'order_id' => 'DEP-TRIPAY-001',
            'status' => 'Success',
        ]);

        $this->assertDatabaseHas('pembayarans', [
            'order_id' => 'DEP-TRIPAY-001',
            'status' => 'Lunas',
        ]);

        $this->assertSame(35_000, (int) $user->fresh()->balance);
    }

    public function test_paid_claim_remains_idempotent_when_order_processing_throws_exception(): void
    {
        $settings = $this->createSettings();

        Pembelian::query()->create([
            'order_id' => 'INV-TRIPAY-ERR-001',
            'username' => 'guest',
            'layanan' => 'Mobile Legends 5 Diamond',
            'harga' => 12000,
            'profit' => 1000,
            'user_id' => '12345',
            'zone' => '2001',
            'status' => 'Pending',
        ]);

        Pembayaran::query()->create([
            'order_id' => 'INV-TRIPAY-ERR-001',
            'harga' => '12000',
            'no_pembayaran' => 'TRIPAY-VA-002',
            'no_pembeli' => '081234567891',
            'status' => 'Belum Lunas',
            'metode' => 'TRIPAY',
            'reference' => 'TRIPAY-REF-002',
        ]);

        $service = Mockery::mock(OrderProcessingService::class);
        $service->shouldReceive('process')
            ->once()
            ->andThrow(new \RuntimeException('Provider timeout'));
        $this->app->instance(OrderProcessingService::class, $service);

        $payload = [
            'reference' => 'TRIPAY-REF-002',
            'merchant_ref' => 'INV-TRIPAY-ERR-001',
            'status' => 'PAID',
            'total_amount' => 12000,
        ];

        $signature = hash_hmac('sha256', json_encode($payload), $settings->tripay_private_key);

        $this->withHeaders([
            'X-CALLBACK-SIGNATURE' => $signature,
            'X-CALLBACK-EVENT' => 'payment_status',
        ])->postJson('/wejizy/tripay/callback', $payload)
            ->assertOk()
            ->assertJson(['success' => true, 'message' => 'claimed_with_processing_error']);

        $this->assertDatabaseHas('pembayarans', [
            'order_id' => 'INV-TRIPAY-ERR-001',
            'status' => 'Lunas',
        ]);

        $this->assertDatabaseHas('pembelians', [
            'order_id' => 'INV-TRIPAY-ERR-001',
            'status' => 'Pending',
        ]);
    }

    public function test_paid_callback_keeps_order_and_payment_when_invoice_notifications_fail(): void
    {
        $settings = $this->createSettings();

        $order = Pembelian::query()->create([
            'order_id' => 'INV-TRIPAY-NOTIF-001',
            'username' => 'guest',
            'layanan' => 'Mobile Legends 5 Diamond',
            'harga' => 12000,
            'profit' => 1000,
            'user_id' => '12345',
            'zone' => '2001',
            'status' => 'Pending',
            'email_pembeli' => 'buyer@example.com',
        ]);

        Pembayaran::query()->create([
            'order_id' => $order->order_id,
            'harga' => '12000',
            'no_pembayaran' => 'TRIPAY-VA-003',
            'no_pembeli' => '081234567892',
            'status' => 'Belum Lunas',
            'metode' => 'TRIPAY',
            'reference' => 'TRIPAY-REF-003',
        ]);

        $processor = Mockery::mock(OrderProcessingService::class);
        $processor->shouldReceive('process')->once()->andReturn([
            'success' => true,
            'transaction_id' => 'TRIPAY-PROVIDER-003',
            'order_status' => 'Pending',
            'sn' => 'Sedang Diproses',
        ]);
        $this->app->instance(OrderProcessingService::class, $processor);

        $wa = Mockery::mock(WhatsappNotificationService::class);
        $wa->shouldReceive('sendNotification')->andReturn(['success' => false, 'message' => 'provider unavailable']);
        $this->app->instance(WhatsappNotificationService::class, $wa);

        $email = Mockery::mock(EmailNotificationService::class);
        $email->shouldReceive('sendTransactionEmail')->andReturn(false);
        $this->app->instance(EmailNotificationService::class, $email);

        $payload = [
            'reference' => 'TRIPAY-REF-003',
            'merchant_ref' => $order->order_id,
            'status' => 'PAID',
            'total_amount' => 12000,
        ];
        $signature = hash_hmac('sha256', json_encode($payload), $settings->tripay_private_key);

        $this->withHeaders([
            'X-CALLBACK-SIGNATURE' => $signature,
            'X-CALLBACK-EVENT' => 'payment_status',
        ])->postJson('/wejizy/tripay/callback', $payload)
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('pembayarans', [
            'order_id' => $order->order_id,
            'status' => 'Lunas',
        ]);
        $this->assertDatabaseHas('pembelians', [
            'order_id' => $order->order_id,
            'status' => 'Pending',
            'provider_order_id' => 'TRIPAY-PROVIDER-003',
        ]);
    }

    private function createSettings(): SettingWeb
    {
        return SettingWeb::query()->create([
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
            'tripay_private_key' => 'tripay-private-test-key',
            'order_prefik' => 'INV',
            'tokopay_merchant_id' => 'M123456TEST',
            'tokopay_secret_key' => 'tokopay-secret-test',
            'vip_apiid' => 'vip-id',
            'vip_apikey' => 'vip-key',
        ]);
    }
}
