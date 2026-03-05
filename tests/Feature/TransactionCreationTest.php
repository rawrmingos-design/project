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

        // Seed basic settings (only columns that exist in real migration)
        DB::table('setting_webs')->insert([
            'id'                   => 1,
            'order_prefik'         => 'TRX',
            'tripay_api'           => 'test_api_key',
            'tripay_merchant_code' => 'test_merchant',
            'tripay_private_key'   => 'test_private',
            'username_digi'        => 'test_digi',
            'api_key_digi'         => 'test_digi_key',
            'logo_header'          => 'logo.png',
            'judul_web'            => 'Test Web',
            'deskripsi_web'        => 'Test Desc',
            'keywords'             => 'test',
        ]);

        // Create User
        $this->user = User::factory()->create([
            'username' => 'testuser',
            'balance'  => 100000,
            'role'     => 'Member'
        ]);

        // Create Category
        $kategori = Kategori::create([
            'nama'          => 'Mobile Legends',
            'kode'          => 'mobile-legends',
            'brand'         => 'ML',
            'kode_kategori' => 'ml',
            'tipe'          => 'game',
            'server_id'     => 1,
            'status'        => 'active',
            'thumbnail'     => 'thumb.jpg',
            'bannerlayanan' => 'banner.jpg'
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
            'profit'           => 1000,
            'profit_member'    => 500,
            'profit_platinum'  => 200,
            'profit_gold'      => 100,
            'status'           => 'available',
            'catatan'          => 'Test Note',
            'is_flash_sale'    => 0
        ]);

        // Create Payment Methods
        $this->methodSaldo = Method::create([
            'name'    => 'Saldo Akun',
            'code'    => 'SALDO',
            'data'    => 'saldo',
            'payment' => 'saldo',
            'tipe'    => 'saldo',
            'images'  => 'saldo.png',
            'status'  => 'active'
        ]);

        $this->methodTripay = Method::create([
            'name'    => 'QRIS',
            'code'    => 'QRIS',
            'data'    => 'qris',
            'payment' => 'tripay',
            'tipe'    => 'e-wallet',
            'images'  => 'qris.png',
            'status'  => 'active'
        ]);
    }

    /** @test */
    public function user_can_create_order_using_balance()
    {
        $this->actingAs($this->user);

        Http::fake([
            'api.digiflazz.com/*' => Http::response([
                'data' => [
                    'status'      => 'Pending',
                    'sn'          => 'SN123',
                    'customer_no' => '12345',
                    'tele'        => '12345',
                    'rc'          => '00',
                    'message'     => 'Success',
                ]
            ], 200),
        ]);

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
            'harga'           => $this->layanan->harga,
            'status'          => 'Proses',
            'tipe_transaksi'  => 'game'
        ]);

        $this->assertDatabaseHas('users', [
            'id'      => $this->user->id,
            'balance' => 90000
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
            'provider_order_id'=> 'DEV-123'
        ]);
    }
}
