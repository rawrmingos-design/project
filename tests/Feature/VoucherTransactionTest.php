<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Method;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VoucherTransactionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Layanan $service;
    private Voucher $voucher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockSuccessfulAccountValidation();

        Http::fake([
            'ipinfo.io/*' => Http::response(['ip' => '127.0.0.1', 'country' => 'ID'], 200),
        ]);

        // Minimal SettingWeb record required by OrderController.
        DB::table('setting_webs')->insert([
            'id' => 1,
            'judul_web' => 'Test Web',
            'deskripsi_web' => 'Test Desc',
            'keywords' => 'test',
            'logo_header' => 'logo.png',
            'url_wa' => 'wa',
            'url_ig' => 'ig',
            'url_tiktok' => 'tt',
            'url_youtube' => 'yt',
            'url_fb' => 'fb',
            'topupindo_api' => 'api',
            'warna1' => '#000000',
            'warna2' => '#000000',
            'warna3' => '#000000',
            'warna4' => '#000000',
            'paydisini_apikey' => 'key',
            'order_prefik' => 'TRX',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->user = User::factory()->create([
            'role' => 'Member',
            'balance' => 500000,
            'no_wa' => '08123456789',
        ]);

        $kategori = Kategori::factory()->create([
            'tipe' => 'game',
            'server_id' => 1,
        ]);

        // Use provider 'manual' so the order flow does not hit external APIs during tests.
        $this->service = Layanan::factory()->create([
            'kategori_id' => $kategori->id,
            'provider' => 'manual',
            'provider_id' => 'manual-sku',
            'harga' => 100000,
            'harga_member' => 100000,
            'harga_platinum' => 100000,
            'harga_gold' => 100000,
            'profit_member' => 5,
            'profit_platinum' => 5,
            'profit_gold' => 5,
            'catatan' => 'Test',
            'status' => 'available',
        ]);

        $this->voucher = Voucher::create([
            'kode' => 'DISKON50',
            'promo' => 50,
            'stock' => 10,
            'mintrx' => 50000,
            'max_potongan' => 20000,
        ]);

        Method::create([
            'name' => 'Saldo',
            'code' => 'SALDO',
            'tipe' => 'saldo',
            'payment' => 'saldo',
            'images' => 'saldo.png',
            'keterangan' => 'Bayar pakai saldo',
            'fee_percent' => 0,
            'fix_fee' => 0,
            'statuspayment' => 1,
        ]);
    }

    public function test_voucher_validity_and_calculation()
    {
        $response = $this->actingAs($this->user)->postJson('/id', [
            'uid' => '12345',
            'zone' => '1234',
            'service' => $this->service->id,
            'payment_method' => 'SALDO',
            'nomor' => '08123456789',
            'voucher' => 'DISKON50',
            'ktg_tipe' => 'game',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => true]);

        // Harga 100.000. Diskon 50% = 50.000. Max potongan 20.000.
        // Final bayar = 80.000
        $this->user->refresh();
        $this->assertEquals(420000, (int) $this->user->balance);

        $this->voucher->refresh();
        $this->assertEquals(9, (int) $this->voucher->stock);

        $this->assertDatabaseHas('pembelians', [
            'user_id' => '12345',
            'voucher' => 'DISKON50',
        ]);
    }

    public function test_min_transaction_failure()
    {
        $this->voucher->update(['mintrx' => 200000]);

        $response = $this->actingAs($this->user)->postJson('/id', [
            'uid' => '12345',
            'zone' => '1234',
            'service' => $this->service->id,
            'payment_method' => 'SALDO',
            'nomor' => '08123456789',
            'voucher' => 'DISKON50',
            'ktg_tipe' => 'game',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => false]);
    }

    public function test_insufficient_stock_failure()
    {
        $this->voucher->update(['stock' => 0]);

        $response = $this->actingAs($this->user)->postJson('/id', [
            'uid' => '12345',
            'zone' => '1234',
            'service' => $this->service->id,
            'payment_method' => 'SALDO',
            'nomor' => '08123456789',
            'voucher' => 'DISKON50',
            'ktg_tipe' => 'game',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => false]);
    }

    public function test_expired_voucher_cannot_be_used_for_order()
    {
        $this->voucher->update(['expired_at' => now()->subMinute()]);

        $response = $this->actingAs($this->user)->postJson('/id', [
            'uid' => '12345',
            'zone' => '1234',
            'service' => $this->service->id,
            'payment_method' => 'SALDO',
            'nomor' => '08123456789',
            'voucher' => 'DISKON50',
            'ktg_tipe' => 'game',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => false,
            'error_code' => 'VOUCHER_INVALID',
        ]);

        $this->voucher->refresh();
        $this->assertEquals(10, (int) $this->voucher->stock);
        $this->assertDatabaseMissing('pembelians', [
            'voucher' => 'DISKON50',
        ]);
    }

    public function test_future_expiry_voucher_still_works()
    {
        $this->voucher->update(['expired_at' => now()->addDay()]);

        $response = $this->actingAs($this->user)->postJson('/id', [
            'uid' => '12345',
            'zone' => '1234',
            'service' => $this->service->id,
            'payment_method' => 'SALDO',
            'nomor' => '08123456789',
            'voucher' => 'DISKON50',
            'ktg_tipe' => 'game',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => true]);

        $this->voucher->refresh();
        $this->assertEquals(9, (int) $this->voucher->stock);
    }

    public function test_insufficient_user_balance_does_not_consume_voucher()
    {
        $this->user->update(['balance' => 1000]);

        $response = $this->actingAs($this->user)->postJson('/id', [
            'uid' => '12345',
            'zone' => '1234',
            'service' => $this->service->id,
            'payment_method' => 'SALDO',
            'nomor' => '08123456789',
            'voucher' => 'DISKON50',
            'ktg_tipe' => 'game',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => false]);

        $this->voucher->refresh();
        $this->assertEquals(10, (int) $this->voucher->stock);
    }

    public function test_zero_max_potongan_means_unlimited_discount_cap()
    {
        $this->voucher->update([
            'promo' => 10,
            'max_potongan' => 0,
        ]);

        $response = $this->actingAs($this->user)->postJson('/id', [
            'uid' => '12345',
            'zone' => '1234',
            'service' => $this->service->id,
            'payment_method' => 'SALDO',
            'nomor' => '08123456789',
            'voucher' => 'DISKON50',
            'ktg_tipe' => 'game',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => true]);

        // Harga 100.000. Diskon 10% = 10.000. max_potongan 0 berarti tanpa cap.
        // Final bayar = 90.000.
        $this->user->refresh();
        $this->assertEquals(410000, (int) $this->user->balance);

        $this->voucher->refresh();
        $this->assertEquals(9, (int) $this->voucher->stock);

        $this->assertDatabaseHas('pembelians', [
            'user_id' => '12345',
            'harga' => 90000,
            'voucher' => 'DISKON50',
        ]);
    }

    public function test_check_voucher_rejects_expired_voucher()
    {
        $this->voucher->update(['expired_at' => now()->subMinute()]);

        $response = $this->actingAs($this->user)->postJson('/check-voucher', [
            'voucher' => 'DISKON50',
            'service' => $this->service->id,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'status' => false,
            'message' => 'Voucher sudah kadaluarsa',
        ]);
    }

    public function test_available_voucher_endpoint_returns_eligible_vouchers_without_consuming_stock()
    {
        Voucher::create([
            'kode' => 'MIN_TINGGI',
            'promo' => 80,
            'stock' => 5,
            'mintrx' => 200000,
            'max_potongan' => 100000,
        ]);

        Voucher::create([
            'kode' => 'EXPIRED80',
            'promo' => 80,
            'stock' => 5,
            'mintrx' => 0,
            'max_potongan' => 100000,
            'expired_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($this->user)->postJson('/available-voucher', [
            'service' => $this->service->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => true,
            'base_price' => 100000,
        ]);
        $response->assertJsonPath('vouchers.0.kode', 'DISKON50');
        $response->assertJsonPath('vouchers.0.discount_amount', 20000);
        $response->assertJsonMissing(['kode' => 'MIN_TINGGI']);
        $response->assertJsonMissing(['kode' => 'EXPIRED80']);

        $this->voucher->refresh();
        $this->assertEquals(10, (int) $this->voucher->stock);
    }
}
