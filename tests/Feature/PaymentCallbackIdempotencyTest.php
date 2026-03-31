<?php

namespace Tests\Feature;

use App\Models\Deposit;
use App\Models\Pembayaran;
use App\Models\SettingWeb;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentCallbackIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_tokopay_duplicate_callback_does_not_double_credit_deposit_balance(): void
    {
        $settings = $this->createSettings();

        $user = User::factory()->create([
            'username' => 'tokopay-user',
            'balance' => 10_000,
        ]);

        Deposit::query()->create([
            'order_id' => 'DEP-TOKOPAY-001',
            'username' => $user->username,
            'metode' => 'QRIS',
            'no_pembayaran' => 'TOKOPAY-VA-001',
            'jumlah' => 50_000,
            'status' => 'Pending',
        ]);

        Pembayaran::query()->create([
            'order_id' => 'DEP-TOKOPAY-001',
            'harga' => '50000',
            'no_pembayaran' => 'TOKOPAY-VA-001',
            'no_pembeli' => '081234567890',
            'status' => 'Belum Lunas',
            'metode' => 'TOKOPAY',
            'reference' => 'TP-REF-001',
        ]);

        $payload = [
            'status' => 'Success',
            'reference' => 'TP-REF-001',
            'reff_id' => 'DEP-TOKOPAY-001',
            'signature' => md5($settings->tokopay_merchant_id . ':' . $settings->tokopay_secret_key . ':DEP-TOKOPAY-001'),
        ];

        $this->postJson('/wejizy/tokopay/callback', $payload)
            ->assertOk()
            ->assertJson(['status' => true]);

        $this->postJson('/wejizy/tokopay/callback', $payload)
            ->assertOk()
            ->assertJson(['status' => true]);

        $this->assertDatabaseHas('deposits', [
            'order_id' => 'DEP-TOKOPAY-001',
            'status' => 'Success',
        ]);

        $this->assertDatabaseHas('pembayarans', [
            'order_id' => 'DEP-TOKOPAY-001',
            'status' => 'Lunas',
        ]);

        $this->assertSame(60_000, (int) $user->fresh()->balance);
    }

    public function test_paydisini_duplicate_callback_does_not_double_credit_deposit_balance(): void
    {
        $settings = $this->createSettings();

        $user = User::factory()->create([
            'username' => 'paydisini-user',
            'balance' => 5_000,
        ]);

        Deposit::query()->create([
            'order_id' => 'DEP-PAYDISINI-001',
            'username' => $user->username,
            'metode' => 'QRIS',
            'no_pembayaran' => 'PAYDISINI-VA-001',
            'jumlah' => 25_000,
            'status' => 'Pending',
        ]);

        Pembayaran::query()->create([
            'order_id' => 'DEP-PAYDISINI-001',
            'harga' => '25000',
            'no_pembayaran' => 'PAYDISINI-VA-001',
            'no_pembeli' => '081234567891',
            'status' => 'Belum Lunas',
            'metode' => 'PAYDISINI',
            'reference' => 'PAYDISINI-REF-001',
        ]);

        $payload = [
            'key' => $settings->paydisini_apikey,
            'pay_id' => '9001',
            'unique_code' => 'DEP-PAYDISINI-001',
            'status' => 'Success',
            'signature' => md5($settings->paydisini_apikey . 'DEP-PAYDISINI-001' . 'CallbackStatus'),
        ];

        $this->postJson('/wejizy/paydisini/callback', $payload)
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->postJson('/wejizy/paydisini/callback', $payload)
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('deposits', [
            'order_id' => 'DEP-PAYDISINI-001',
            'status' => 'Success',
        ]);

        $this->assertDatabaseHas('pembayarans', [
            'order_id' => 'DEP-PAYDISINI-001',
            'status' => 'Lunas',
        ]);

        $this->assertSame(30_000, (int) $user->fresh()->balance);
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
            'order_prefik' => 'INV',
            'tokopay_merchant_id' => 'M123456TEST',
            'tokopay_secret_key' => 'tokopay-secret-test',
            'vip_apiid' => 'vip-id',
            'vip_apikey' => 'vip-key',
        ]);
    }
}

