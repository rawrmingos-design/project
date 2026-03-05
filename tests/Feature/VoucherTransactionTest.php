<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Voucher;
use App\Models\Layanan;
use App\Models\Kategori;
use App\Models\Method;
use App\Models\SettingWeb;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class VoucherTransactionTest extends TestCase
{
    // use RefreshDatabase; REMOVED because migrations are broken

    protected $user;
    protected $service;
    protected $voucher;
    protected $method;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Force SQLite Memory Connection
        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);
        
        // Manually migrate tables for SQLite Memory
        $this->migrateTables();
        
        // Setup Basic Data
        $this->user = User::create([
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'role' => 'Member',
            'balance' => 500000,
            'no_wa' => '08123456789'
        ]);

        $kategori = Kategori::create([
            'nama' => 'Mobile Legends',
            'kode' => 'mobile-legends',
            'tipe' => 'game',
            'server_id' => 1,
            'status' => 'active'
        ]);

        $this->service = Layanan::create([
            'kategori_id' => $kategori->id,
            'layanan' => '100 Diamonds',
            'provider_id' => '100dm',
            'provider' => 'digiflazz',
            'harga' => 100000,
            'harga_member' => 100000,
            'harga_platinum' => 98000,
            'harga_gold' => 99000,
            'profit' => 5,
            'profit_member' => 5,
            'profit_platinum' => 5,
            'profit_gold' => 5,
            'status' => 'available'
        ]);

        $this->voucher = Voucher::create([
            'kode' => 'DISKON50',
            'promo' => 50, // 50%
            'stock' => 10,
            'mintrx' => 50000,
            'max_potongan' => 20000
        ]);

        $this->method = Method::create([
            'name' => 'Saldo',
            'code' => 'SALDO',
            'tipe' => 'saldo',
            'payment' => 'saldo',
            'images' => 'saldo.png',
            'keterangan' => 'Bayar pakai saldo'
        ]);

        // Mock SettingWeb specifically using DB facade to avoid model issues if they exist
        DB::table('setting_webs')->insert([
            'id' => 1,
            'judul_web' => 'Test Web',
            'deskripsi_web' => 'Test Desc',

            'url_wa' => 'wa.me',
            'url_ig' => 'ig',
            'url_tiktok' => 'tt',
            'url_youtube' => 'yt',
            'url_fb' => 'fb',
            'topupindo_api' => 'test',
            'order_prefik' => 'TRX',
            'created_at' => now(), 
            'updated_at' => now()
        ]);
        
        // Seed whitelist_ips to avoid middleware blocking if active
        // Assuming no strict IP blocking in test env, but good practice
    }

    public function test_voucher_validity_and_calculation()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/order', [
                'uid' => '12345',
                'zone' => '1234',
                'service' => $this->service->id,
                'payment_method' => 'SALDO',
                'nomor' => '08123456789',
                'voucher' => 'DISKON50',
                'ktg_tipe' => 'game'
            ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => true]);

        // Price: 100,000. Discount 50% = 50,000. Max Potongan = 20,000.
        // Final Price: 100,000 - 20,000 = 80,000.
        
        // Assert User Balance Decreased Correctly
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'balance' => 420000 // 500k - 80k
        ]);

        // Assert Voucher Stock Decremented
        $this->assertDatabaseHas('vouchers', [
            'id' => $this->voucher->id,
            'stock' => 9
        ]);
        
        // Assert Voucher Code Saved in Pembelian
        $this->assertDatabaseHas('pembelians', [
            'user_id' => '12345',
            'voucher' => 'DISKON50'
        ]);
    }

    public function test_min_transaction_failure()
    {
        // Set mintrx high
        $this->voucher->update(['mintrx' => 200000]);

        $response = $this->actingAs($this->user)
            ->postJson('/order', [
                'uid' => '12345',
                'zone' => '1234',
                'service' => $this->service->id, // Price 100k < 200k
                'payment_method' => 'SALDO',
                'nomor' => '08123456789',
                'voucher' => 'DISKON50',
                'ktg_tipe' => 'game'
            ]);

        // Should fail or return message
        $response->assertStatus(200); // Controller often returns 200 with status: false
        $response->assertJson(['status' => false]);
        $response->assertSee('Minimal transaksi');

        // Assert Stock NOT Decremented
        $this->assertDatabaseHas('vouchers', [
            'id' => $this->voucher->id,
            'stock' => 10
        ]);
    }

    public function test_insufficient_stock_failure()
    {
        $this->voucher->update(['stock' => 0]);

        $response = $this->actingAs($this->user)
            ->postJson('/order', [
                'uid' => '12345',
                'zone' => '1234',
                'service' => $this->service->id,
                'payment_method' => 'SALDO',
                'nomor' => '08123456789',
                'voucher' => 'DISKON50',
                'ktg_tipe' => 'game'
            ]);
            
        // Depending on logic, it might process without discount OR fail.
        // Current logic in store() checks stock again.
        
        $response->assertJson(['status' => false]);
         $response->assertSee('Voucher habis');
    }

    public function test_insufficient_user_balance_does_not_consume_voucher()
    {
        $this->user->update(['balance' => 1000]); // Less than 80k

        $response = $this->actingAs($this->user)
            ->postJson('/order', [
                'uid' => '12345',
                'zone' => '1234',
                'service' => $this->service->id,
                'payment_method' => 'SALDO',
                'nomor' => '08123456789',
                'voucher' => 'DISKON50',
                'ktg_tipe' => 'game'
            ]);

        $response->assertJson(['status' => false]);
        $response->assertSee('Saldo anda tidak mencukupi');

        // CRITICAL CHECK: Stock must NOT decrease
        $this->assertDatabaseHas('vouchers', [
            'id' => $this->voucher->id,
            'stock' => 10
        ]);
    }

    private function migrateTables()
    {
        $schema = DB::connection()->getSchemaBuilder();
        
        $schema->create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('username');
            $table->string('email');
            $table->string('password');
            $table->string('role')->default('Member');
            $table->bigInteger('balance')->default(0);
            $table->string('no_wa');
            $table->string('api_key')->nullable();
            $table->timestamps();
        });

        $schema->create('vouchers', function ($table) {
            $table->id();
            $table->string('kode');
            $table->integer('promo');
            $table->integer('stock');
            $table->integer('mintrx')->default(0);
            $table->integer('max_potongan');
            $table->timestamps();
        });

        $schema->create('kategoris', function ($table) {
            $table->id();
            $table->string('nama');
            $table->string('kode');
            $table->string('tipe');
            $table->integer('server_id')->default(0);
            $table->string('status')->default('active');
            $table->string('thumbnail')->nullable();
            $table->string('banner')->nullable();
            $table->text('deskripsi_game')->nullable();
            $table->text('deskripsi_field')->nullable();
            $table->timestamps();
        });

        $schema->create('layanans', function ($table) {
            $table->id();
            $table->foreignId('kategori_id');
            $table->string('layanan');
            $table->string('provider_id');
            $table->string('provider');
            $table->bigInteger('harga');
            $table->bigInteger('harga_member');
            $table->bigInteger('harga_platinum');
            $table->bigInteger('harga_gold');
            $table->integer('profit');
            $table->integer('profit_member');
            $table->integer('profit_platinum');
            $table->integer('profit_gold');
            $table->string('status');
            $table->boolean('is_flash_sale')->default(false);
            $table->dateTime('expired_flash_sale')->nullable();
            $table->bigInteger('harga_flash_sale')->default(0);
            $table->integer('stock_flash_sale')->default(0);
            $table->string('product_logo')->nullable();
            $table->timestamps();
        });

        $schema->create('methods', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->string('tipe');
            $table->string('payment');
            $table->string('images')->nullable();
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });
        
        $schema->create('setting_webs', function ($table) {
            $table->id();
            $table->string('judul_web')->nullable();
            $table->string('deskripsi_web')->nullable();
            $table->string('keywords')->nullable();
            $table->string('url_wa')->nullable();
            $table->string('url_ig')->nullable();
            $table->string('url_tiktok')->nullable();
            $table->string('url_youtube')->nullable();
            $table->string('url_fb')->nullable();
            $table->string('topupindo_api')->nullable();
            $table->string('order_prefik')->nullable();
            $table->string('ovo_admin')->nullable();
            $table->string('gopay_admin')->nullable();
            $table->string('nomor_admin')->nullable();
            $table->timestamps();
        });

        $schema->create('pembelians', function ($table) {
            $table->id();
            $table->string('order_id');
            $table->string('user_id');
            $table->string('zone')->nullable();
            $table->string('nickname')->nullable();
            $table->string('layanan');
            $table->bigInteger('harga');
            $table->bigInteger('profit');
            $table->string('status');
            $table->string('tipe_transaksi');
            $table->string('ip_address')->nullable();
            $table->string('voucher')->nullable(); 
            $table->string('provider_order_id')->nullable();
            $table->text('log')->nullable();
            $table->string('username')->nullable();
            $table->timestamps();
        });
        
        $schema->create('pembayarans', function ($table) {
            $table->id();
            $table->string('order_id');
            $table->bigInteger('harga');
            $table->string('no_pembayaran');
            $table->string('no_pembeli');
            $table->string('status');
            $table->string('metode');
            $table->string('reference')->nullable();
            $table->timestamps();
        });
        
        $schema->create('data_joki', function ($table) {
             $table->id();
             $table->string('order_id');
             $table->string('email_joki')->default('-');
             $table->string('password_joki')->default('-');
             $table->string('loginvia_joki')->default('-');
             $table->string('nickname_joki')->default('-');
             $table->string('request_joki')->default('-');
             $table->string('catatan_joki')->default('-');
             $table->string('tglmain_joki')->default('-');
             $table->string('jambooking_joki')->default('-');
             $table->integer('qty')->default(1);
             $table->string('status_joki')->default('Pending');
             $table->timestamps();
        });
    }
}
