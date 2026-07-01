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

    public function test_tokopay_callback_rejects_mismatched_total_dibayar(): void
    {
        $settings = $this->createSettings();

        User::factory()->create([
            'username' => 'tokopay-mismatch-user',
            'balance' => 10_000,
        ]);

        Deposit::query()->create([
            'order_id' => 'DEP-TOKOPAY-MISMATCH-001',
            'username' => 'tokopay-mismatch-user',
            'metode' => 'QRIS',
            'no_pembayaran' => 'TOKOPAY-VA-MISMATCH-001',
            'jumlah' => 50_000,
            'status' => 'Pending',
        ]);

        Pembayaran::query()->create([
            'order_id' => 'DEP-TOKOPAY-MISMATCH-001',
            'harga' => '50000',
            'no_pembayaran' => 'TOKOPAY-VA-MISMATCH-001',
            'no_pembeli' => '081234567892',
            'status' => 'Belum Lunas',
            'metode' => 'TOKOPAY',
            'reference' => 'TP-REF-MISMATCH-001',
        ]);

        $payload = [
            'status' => 'Success',
            'reference' => 'TP-REF-MISMATCH-001',
            'reff_id' => 'DEP-TOKOPAY-MISMATCH-001',
            'signature' => md5($settings->tokopay_merchant_id . ':' . $settings->tokopay_secret_key . ':DEP-TOKOPAY-MISMATCH-001'),
            'data' => [
                'total_dibayar' => 10_000,
            ],
        ];

        $this->postJson('/wejizy/tokopay/callback', $payload)
            ->assertStatus(400)
            ->assertJson(['status' => false, 'message' => 'invalid_amount']);

        $this->assertDatabaseHas('deposits', [
            'order_id' => 'DEP-TOKOPAY-MISMATCH-001',
            'status' => 'Pending',
        ]);

        $this->assertDatabaseHas('pembayarans', [
            'order_id' => 'DEP-TOKOPAY-MISMATCH-001',
            'status' => 'Belum Lunas',
        ]);
    }

    public function test_tokopay_callback_rejects_mismatched_reff_id(): void
    {
        $settings = $this->createSettings();

        Deposit::query()->create([
            'order_id' => 'DEP-TOKOPAY-IDENTITY-001',
            'username' => 'tokopay-identity-user',
            'metode' => 'QRIS',
            'no_pembayaran' => 'TOKOPAY-VA-IDENTITY-001',
            'jumlah' => 50_000,
            'status' => 'Pending',
        ]);

        Pembayaran::query()->create([
            'order_id' => 'DEP-TOKOPAY-IDENTITY-001',
            'harga' => '50000',
            'no_pembayaran' => 'TOKOPAY-VA-IDENTITY-001',
            'no_pembeli' => '081234567893',
            'status' => 'Belum Lunas',
            'metode' => 'TOKOPAY',
            'reference' => 'TP-REF-IDENTITY-001',
        ]);

        $payload = [
            'status' => 'Success',
            'reference' => 'TP-REF-IDENTITY-001',
            'reff_id' => 'DEP-TOKOPAY-WRONG-001',
            'signature' => md5($settings->tokopay_merchant_id . ':' . $settings->tokopay_secret_key . ':DEP-TOKOPAY-WRONG-001'),
            'data' => [
                'total_dibayar' => 50_000,
            ],
        ];

        $this->postJson('/wejizy/tokopay/callback', $payload)
            ->assertStatus(400)
            ->assertJson(['status' => false, 'message' => 'invalid_order_reference']);

        $this->assertDatabaseHas('pembayarans', [
            'order_id' => 'DEP-TOKOPAY-IDENTITY-001',
            'status' => 'Belum Lunas',
        ]);
    }

    public function test_tripay_callback_rejects_mismatched_merchant_ref(): void
    {
        $settings = $this->createSettings();

        Deposit::query()->create([
            'order_id' => 'DEP-TRIPAY-IDENTITY-001',
            'username' => 'tripay-identity-user',
            'metode' => 'QRIS',
            'no_pembayaran' => 'TRIPAY-VA-IDENTITY-001',
            'jumlah' => 50_000,
            'status' => 'Pending',
        ]);

        Pembayaran::query()->create([
            'order_id' => 'DEP-TRIPAY-IDENTITY-001',
            'harga' => '50000',
            'no_pembayaran' => 'TRIPAY-VA-IDENTITY-001',
            'no_pembeli' => '081234567894',
            'status' => 'Belum Lunas',
            'metode' => 'TRIPAY',
            'reference' => 'TRIPAY-REF-IDENTITY-001',
        ]);

        $payload = [
            'reference' => 'TRIPAY-REF-IDENTITY-001',
            'merchant_ref' => 'DEP-TRIPAY-WRONG-001',
            'total_amount' => 50_000,
            'status' => 'PAID',
        ];
        $json = json_encode($payload);

        $this->call('POST', '/wejizy/tripay/callback', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_CALLBACK_EVENT' => 'payment_status',
            'HTTP_X_CALLBACK_SIGNATURE' => hash_hmac('sha256', $json, $settings->tripay_private_key),
        ], $json)
            ->assertStatus(400)
            ->assertJson(['success' => false, 'message' => 'invalid_merchant_ref']);

        $this->assertDatabaseHas('pembayarans', [
            'order_id' => 'DEP-TRIPAY-IDENTITY-001',
            'status' => 'Belum Lunas',
        ]);
    }

    public function test_duitku_callback_rejects_mixed_payment_identity(): void
    {
        $settings = $this->createSettings();

        Deposit::query()->create([
            'order_id' => 'DEP-DUITKU-IDENTITY-001',
            'username' => 'duitku-identity-user',
            'metode' => 'QRIS',
            'no_pembayaran' => 'DUITKU-VA-IDENTITY-001',
            'jumlah' => 50_000,
            'status' => 'Pending',
        ]);

        Pembayaran::query()->create([
            'order_id' => 'DEP-DUITKU-IDENTITY-001',
            'harga' => '50000',
            'no_pembayaran' => 'DUITKU-VA-IDENTITY-001',
            'no_pembeli' => '081234567895',
            'status' => 'Belum Lunas',
            'metode' => 'DUITKU',
            'reference' => 'DUITKU-REF-IDENTITY-001',
            'duitku_reference' => 'DUITKU-REF-IDENTITY-001',
            'duitku_merchant_order_id' => 'DUITKU-DEP-DUITKU-IDENTITY-001',
        ]);

        $payload = [
            'merchantCode' => $settings->duitku_merchant_code,
            'amount' => 50_000,
            'merchantOrderId' => 'DUITKU-DEP-DUITKU-WRONG-001',
            'resultCode' => '00',
            'reference' => 'DUITKU-REF-IDENTITY-001',
        ];
        $payload['signature'] = hash_hmac(
            'sha256',
            $payload['merchantCode'] . $payload['amount'] . $payload['merchantOrderId'],
            $settings->duitku_merchant_key
        );

        $this->post('/wejizy/duitku/callback', $payload)
            ->assertStatus(400)
            ->assertSee('Invalid payment identity');

        $this->assertDatabaseHas('pembayarans', [
            'order_id' => 'DEP-DUITKU-IDENTITY-001',
            'status' => 'Belum Lunas',
        ]);
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
            'tripay_private_key' => 'tripay-private-test',
            'duitku_merchant_code' => 'DTEST',
            'duitku_merchant_key' => 'duitku-secret-test',
            'duitku_mode' => 'sandbox',
            'vip_apiid' => 'vip-id',
            'vip_apikey' => 'vip-key',
        ]);
    }
}

