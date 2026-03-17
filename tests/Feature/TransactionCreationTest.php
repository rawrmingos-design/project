<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Layanan;
use App\Models\Kategori;
use App\Models\Method;
use App\Models\Pembelian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class TransactionCreationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $layanan;
    protected $methodSaldo;
    protected $methodTripay;

    protected function setUp(): void
    {
        parent::setUp();

        // Skip in CI: User/Kategori factory columns don't match SQLite schema
        // These are integration tests that require a full MySQL environment.
        if (env('CI')) {
            $this->markTestSkipped('Skipped in CI: requires MySQL with full schema, not SQLite.');
        }

        // Also fake ipinfo (OrderController fetches IP meta).
        Http::fake([
            'ipinfo.io/*' => Http::response(['ip' => '127.0.0.1', 'country' => 'ID'], 200),
            'api.digiflazz.com/*' => Http::response([
                'data' => [
                    'status'      => 'Pending',
                    'sn'          => 'SN123',
                    'customer_no' => '12345',
                    'tele'        => '12345',
                    'rc'          => '00',
                    'message'     => 'Success',
                ],
            ], 200),
        ]);

        // Seed basic settings (only columns that exist in real migration)
        DB::table('setting_webs')->insert([
            'id'                   => 1,
            'judul_web'            => 'Test Web',
            'deskripsi_web'        => 'Test Desc',
            'keywords'             => 'test',
            'url_wa'               => 'wa.me/test',
            'url_ig'               => 'instagram.com/test',
            'url_tiktok'           => 'tiktok.com/test',
            'url_youtube'          => 'youtube.com/test',
            'url_fb'               => 'facebook.com/test',
            'topupindo_api'        => 'test_api',
            'warna1'               => '#000000',
            'warna2'               => '#ffffff',
            'warna3'               => '#cccccc',
            'warna4'               => '#333333',
            'paydisini_apikey'     => 'test_paydisini',
            'order_prefik'         => 'TRX',
            'tripay_api'           => 'test_api_key',
            'tripay_merchant_code' => 'test_merchant',
            'tripay_private_key'   => 'test_private',
            'username_digi'        => 'test_digi',
            'api_key_digi'         => 'test_digi_key',
            'logo_header'          => 'logo.png',
        ]);

        // Create User
        $this->user = User::factory()->create([
            'username' => 'testuser',
            'balance'  => 100000,
            'role'     => 'Member'
        ]);

        // Create Category
        $kategori = Kategori::create([
            'nama'      => 'Mobile Legends',
            'sub_nama'  => 'Mobile Legends',
            'kode'      => 'mobile-legends',
            'tipe'      => 'game',
            'server_id' => 1,
            'status'    => 'active',
            'thumbnail' => 'thumb.jpg',
            'banner'    => 'banner.jpg',
        ]);

        // Create Layanan
        $this->layanan = Layanan::create([
            'kategori_id'      => $kategori->id,
            'layanan'          => '10 Diamonds',
            'provider_id'      => 'ML10',
            'provider'         => 'digiflazz',
            'harga'            => 10000,
            'harga_member'     => 9500,
            'harga_platinum'   => 9000,
            'harga_gold'       => 8500,
            'profit_member'    => 500,
            'profit_platinum'  => 200,
            'profit_gold'      => 100,
            'status'           => 'available',
            'catatan'          => 'Test Note',
            'is_flash_sale'    => 0
        ]);

        // Create Payment Methods (match current `methods` migration columns)
        $this->methodSaldo = Method::create([
            'name'    => 'Saldo Akun',
            'code'    => 'SALDO',
            'payment' => 'saldo',
            'tipe'    => 'saldo',
            'images'  => 'saldo.png',
            'keterangan' => 'Saldo',
            'fee_percent' => 0,
            'fix_fee' => 0,
            'statuspayment' => 1,
        ]);

        $this->methodTripay = Method::create([
            'name'    => 'QRIS',
            'code'    => 'QRIS',
            'payment' => 'tripay',
            'tipe'    => 'e-wallet',
            'images'  => 'qris.png',
            'keterangan' => 'QRIS',
            'fee_percent' => 0.7,
            'fix_fee' => 100,
            'statuspayment' => 1,
        ]);
    }

    /** @test */
    public function user_can_create_order_using_balance()
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/id', [
            'uid'            => '12345',
            'zone'           => '1234',
            'nickname'       => 'TestNick',
            'service'        => $this->layanan->id,
            'payment_method' => 'SALDO',
            'nomor'          => '08123456789',
            'ktg_tipe'       => 'game'
        ]);

        $response->assertStatus(200)
            ->assertJson(['status' => true]);

        $this->assertDatabaseHas('pembelians', [
            'username'        => $this->user->username,
            'layanan'         => $this->layanan->layanan,
            'harga'           => $this->layanan->harga_member,
            'status'          => 'Proses',
            'tipe_transaksi'  => 'game'
        ]);

        $this->assertDatabaseHas('users', [
            'id'      => $this->user->id,
            'balance' => 90500
        ]);
    }

    /** @test */
    public function user_cannot_create_order_with_insufficient_balance()
    {
        $this->user->update(['balance' => 0]);
        $this->actingAs($this->user);

        $response = $this->postJson('/id', [
            'uid'            => '12345',
            'zone'           => '1234',
            'service'        => $this->layanan->id,
            'payment_method' => 'SALDO',
            'nomor'          => '08123456789',
            'ktg_tipe'       => 'game'
        ]);

        $response->assertStatus(200)
            ->assertJson(['status' => false]);
    }

    /** @test */
    public function user_can_create_order_using_tripay()
    {
        $this->actingAs($this->user);

        Http::fake([
            'ipinfo.io/*' => Http::response(['ip' => '127.0.0.1', 'country' => 'ID'], 200),
            'api.digiflazz.com/*' => Http::response([
                'data' => [
                    'status' => 'Pending',
                ],
            ], 200),
            'tripay.co.id/*' => Http::response([
                'success' => true,
                'data'    => [
                    'reference'    => 'DEV-123',
                    'amount'       => 10000,
                    'pay_code'     => 'PAY123',
                    'qr_url'       => 'https://qr.png',
                    'checkout_url' => 'https://checkout',
                ]
            ], 200),
        ]);

        $response = $this->postJson('/id', [
            'uid'            => '12345',
            'zone'           => '1234',
            'service'        => $this->layanan->id,
            'payment_method' => 'QRIS',
            'nomor'          => '08123456789',
            'ktg_tipe'       => 'game'
        ]);

        $response->assertStatus(200)
            ->assertJson(['status' => true]);

        $this->assertDatabaseHas('pembelians', [
            'username'         => $this->user->username,
            'layanan'          => $this->layanan->layanan,
            'status'           => 'Pending',
        ]);

        $this->assertDatabaseHas('pembayarans', [
            'metode' => 'QRIS',
            'reference' => 'DEV-123',
            'status' => 'Belum Lunas',
        ]);
    }
}
