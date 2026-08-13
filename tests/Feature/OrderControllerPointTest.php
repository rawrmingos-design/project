<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Pembelian;
use App\Models\Method;
use App\Services\CheckId\CheckIdResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class OrderControllerPointTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Kategori $kategori;
    private Layanan $layanan;

    protected function setUp(): void
    {
        parent::setUp();

        // OrderController fetches IP meta via ipinfo.io.
        Cache::forget('payment_methods_price_calc_v1:main');

        Http::fake([
            'ipinfo.io/*' => Http::response([
                'ip' => '127.0.0.1',
                'city' => 'Test City',
                'region' => 'Test Region',
                'country' => 'ID',
                'org' => 'Test ISP',
            ], 200),
        ]);

        // Setup setting_webs with point config
        DB::table('setting_webs')->insert([
            'id' => 1,
            'judul_web' => 'Test Web',
            'deskripsi_web' => 'Test Deskripsi',
            'keywords'      => 'test,web',
            'url_wa'        => 'wa',
            'url_ig'        => 'ig',
            'url_tiktok'    => 'tiktok',
            'url_youtube'   => 'yt',
            'url_fb'        => 'fb',
            'topupindo_api' => 'api1',
            'warna1'        => '#000',
            'warna2'        => '#000',
            'warna3'        => '#000',
            'warna4'        => '#000',
            'paydisini_apikey' => 'key',
            'order_prefik'  => 'TRX',
            'point_per_nominal' => 1,
            'point_value' => 100,
            'max_point_usage_percent' => 50,
        ]);

        $this->user = User::factory()->create([
            'point_balance' => 100, // Rp 10.000 equiv
            'balance'       => 100000,
        ]);

        $this->kategori = Kategori::create([
            'nama' => 'Mobile Legends',
            'sub_nama' => 'Mobile Legends',
            'kode' => 'mobile-legends',
            'tipe' => 'game',
            'status' => 'active',
            'thumbnail' => 'thumb.jpg',
            'banner' => 'banner.jpg',
        ]);

        $this->layanan = Layanan::create([
            'kategori_id' => $this->kategori->id,
            'layanan' => '86 Diamond',
            'harga' => 20000,
            'harga_member' => 20000,
            'harga_platinum' => 20000,
            'harga_gold' => 20000,
            'profit_member' => 1000,
            'profit_platinum' => 1000,
            'profit_gold' => 1000,
            'catatan' => 'Test',
            'status' => 'available',
            'provider_id' => 'manual-sku',
            'provider' => 'manual',
        ]);

        $this->mockSuccessfulAccountValidation();
    }

    /** @test */
    public function ordered_endpoint_rejects_invalid_account_before_deducting_balance()
    {
        $this->mock(CheckIdResolver::class, function (MockInterface $mock): void {
            $mock->shouldReceive('resolveForCategory')->once()->andReturn([
                'status' => ['code' => 404, 'message' => 'Account not found'],
            ]);
        });

        $response = $this->actingAs($this->user)->postJson(route('ordered'), [
            'service' => $this->layanan->id,
            'payment_method' => 'SALDO',
            'nomor' => '081234567890',
            'uid' => 'INVALID-UID',
            'zone' => '1234',
            'qty' => 1,
            'use_point' => 50,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('error_code', 'INVALID_GAME_ACCOUNT');
        $this->assertSame(100000, $this->user->fresh()->balance);
        $this->assertSame(100, $this->user->fresh()->point_balance);
        $this->assertDatabaseCount('pembelians', 0);
    }

    /** @test */
    public function price_endpoint_returns_point_info_for_logged_in_user()
    {
        Method::create([
            'code' => 'MANUAL',
            'name' => 'Manual Transfer',
            'images' => 'manual.png',
            'keterangan' => 'Manual transfer',
            'tipe' => 'manual',
            'payment' => 'manual',
            'statuspayment' => true,
        ]);

        $response = $this->actingAs($this->user)->postJson(route('ajax.price'), [
            'nominal' => $this->layanan->id,
            'use_point' => 50,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'harga',
            'point_info' => [
                'balance',
                'point_value',
                'max_points',
                'max_discount',
            ],
            'method_prices',
            'selected_final_price',
            'point_discount',
        ]);

        // Harga 20000, Max percent 50% = 10000 = 100 poin
        // User have 100 poin, so max_points = 100
        $response->assertJson([
            'point_info' => [
                'balance' => 100,
                'point_value' => 100,
                'max_points' => 100,
                'max_discount' => 10000,
            ]
        ]);
        $response->assertJsonPath('point_discount', 5000);
        $response->assertJsonPath('selected_final_price', 15000);

        $methodPrices = $response->json('method_prices');
        $this->assertNotEmpty($methodPrices);
        foreach ($methodPrices as $methodPrice) {
            $this->assertSame(20000, $methodPrice['base_amount']);
            $this->assertSame(5000, $methodPrice['point_discount']);
            $this->assertSame(
                $methodPrice['amount_before_point'] - $methodPrice['point_discount'],
                $methodPrice['final_price'],
            );
        }
    }

    /** @test */
    public function fallback_idempotency_fingerprint_changes_when_use_point_changes()
    {
        $this->actingAs($this->user);
        $controller = app(\App\Http\Controllers\OrderController::class);
        $method = new \ReflectionMethod($controller, 'buildOrderIdempotencyFingerprint');

        $basePayload = [
            'service' => $this->layanan->id,
            'payment_method' => 'SALDO',
            'nomor' => '081234567890',
            'uid' => '12345678',
            'zone' => '1234',
            'qty' => 1,
        ];
        $requestWithoutPoints = \Illuminate\Http\Request::create('/id', 'POST', $basePayload + ['use_point' => 0]);
        $requestWithPoints = \Illuminate\Http\Request::create('/id', 'POST', $basePayload + ['use_point' => 50]);

        $withoutPoints = $method->invoke($controller, $requestWithoutPoints);
        $withPoints = $method->invoke($controller, $requestWithPoints);

        $this->assertNotSame($withoutPoints, $withPoints);
    }

    /** @test */
    public function guest_fallback_idempotency_fingerprint_changes_when_use_point_changes()
    {
        $controller = app(\App\Http\Controllers\OrderController::class);
        $method = new \ReflectionMethod($controller, 'buildOrderIdempotencyFingerprint');
        $basePayload = [
            'service' => $this->layanan->id,
            'payment_method' => 'MANUAL',
            'nomor' => '081234567890',
            'qty' => 1,
        ];
        $requestWithoutPoints = \Illuminate\Http\Request::create('/id', 'POST', $basePayload + ['use_point' => 0]);
        $requestWithPoints = \Illuminate\Http\Request::create('/id', 'POST', $basePayload + ['use_point' => 50]);
        $session = app('session')->driver();
        $requestWithoutPoints->setLaravelSession($session);
        $requestWithPoints->setLaravelSession($session);

        $withoutPoints = $method->invoke($controller, $requestWithoutPoints);
        $withPoints = $method->invoke($controller, $requestWithPoints);

        $this->assertNotSame($withoutPoints, $withPoints);
    }

    /** @test */
    public function price_endpoint_uses_database_fee_configuration_for_each_visible_method()
    {
        $dana = Method::create([
            'code' => 'DANA',
            'name' => 'DANA',
            'images' => 'dana.png',
            'keterangan' => 'DANA',
            'tipe' => 'e-walet',
            'payment' => 'manual',
            'fee_percent' => 3,
            'fix_fee' => 0,
            'statuspayment' => true,
        ]);

        $qris = Method::create([
            'code' => 'QRIS',
            'name' => 'QRIS',
            'images' => 'qris.png',
            'keterangan' => 'QRIS',
            'tipe' => 'qris',
            'payment' => 'manual',
            'fee_percent' => 0.7,
            'fix_fee' => 100,
            'statuspayment' => true,
        ]);

        $response = $this->actingAs($this->user)->postJson(route('ajax.price'), [
            'nominal' => $this->layanan->id,
            'payment_method' => $dana->code,
        ]);

        $response->assertOk()
            ->assertJsonPath('method_prices.DANA.fee_amount', 600)
            ->assertJsonPath('method_prices.DANA.final_price', 20600)
            ->assertJsonPath("method_prices.{$qris->code}.fee_amount", 240)
            ->assertJsonPath("method_prices.{$qris->code}.final_price", 20240)
            ->assertJsonPath('selected_final_price', 20600);
    }

    /** @test */
    public function price_endpoint_does_not_return_point_info_for_guest_even_when_points_are_requested()
    {
        $response = $this->postJson(route('ajax.price'), [
            'nominal' => $this->layanan->id,
            'use_point' => 50,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('point_discount', 0)
            ->assertJsonPath('selected_final_price', 20000);
        $response->assertJsonMissing(['point_info']);
    }

    /** @test */
    public function price_endpoint_changes_discount_and_final_price_when_points_are_requested()
    {
        $withoutPoints = $this->actingAs($this->user)->postJson(route('ajax.price'), [
            'nominal' => $this->layanan->id,
            'use_point' => 0,
        ]);

        $withPoints = $this->actingAs($this->user)->postJson(route('ajax.price'), [
            'nominal' => $this->layanan->id,
            'use_point' => 50,
        ]);

        $withoutPoints->assertOk()
            ->assertJsonPath('point_discount', 0)
            ->assertJsonPath('selected_final_price', 20000);
        $withPoints->assertOk()
            ->assertJsonPath('point_discount', 5000)
            ->assertJsonPath('selected_final_price', 15000);
    }

    /** @test */
    public function price_endpoint_rejects_unavailable_service()
    {
        $this->layanan->update(['status' => 'unavailable']);

        $response = $this->postJson(route('ajax.price'), [
            'nominal' => $this->layanan->id,
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'status' => false,
                'message' => 'Layanan tidak ditemukan atau tidak tersedia.',
                'error_code' => 'SERVICE_NOT_FOUND',
            ]);
    }

    /** @test */
    public function ordered_endpoint_deducts_point_balance_and_reduces_price_for_balance_payment()
    {
        // Mock Provider Routing to simulate successful checkout without hitting external API
        $this->mock(\App\Services\ProviderRoutingService::class, function ($mock) {
            $mock->shouldReceive('findBestProvider')
                 ->andReturn([
                     'provider_code' => 'manual',
                     'sku' => 'manual-123',
                 ]);
        });

        // Poin yang mau dipakai: 50 poin = Rp 5000 diskon
        // Harga layanan: 20000. Bayar pakai saldo member.
        // Saldo awal: 100000. Sesudah bayar: 100000 - (20000 - 5000) = 85000

        $response = $this->actingAs($this->user)->postJson(route('ordered'), [
            'service' => $this->layanan->id,
            'payment_method' => 'SALDO',
            'nomor' => '081234567890', // WA number
            'uid'   => '12345678',     // User ID ML
            'zone'  => '1234',         // Zone ID ML
            'qty'   => 1,
            'use_point' => 50,
        ]);

        // assert successful order
        $response->assertStatus(200);
        $response->assertJson(['status' => true]);

        // Check user point balance
        $this->user->refresh();
        $this->assertEquals(50, $this->user->point_balance); // 100 - 50
        $this->assertEquals(85000, $this->user->balance);

        // Check pembelian used points
        $order = Pembelian::where('username', $this->user->username)->latest()->first();
        $this->assertNotNull($order);
        $this->assertEquals(50, $order->used_points);

        // Check point history record
        $this->assertDatabaseHas('point_histories', [
            'user_id' => $this->user->id,
            'order_id' => $order->order_id,
            'type' => 'redeem',
            'points' => 50,
        ]);
    }

    /** @test */
    public function failed_provider_processing_restores_reserved_points_without_creating_an_order()
    {
        $this->mock(\App\Services\ProviderRoutingService::class, function ($mock): void {
            $mock->shouldReceive('findBestProvider')
                ->andReturn(null);
        });

        $response = $this->actingAs($this->user)->postJson(route('ordered'), [
            'service' => $this->layanan->id,
            'payment_method' => 'SALDO',
            'nomor' => '081234567890',
            'uid' => '12345678',
            'zone' => '1234',
            'qty' => 1,
            'use_point' => 50,
        ]);

        $response->assertOk()
            ->assertJsonPath('status', false)
            ->assertJsonPath('error_code', 'ORDER_PROCESSING_FAILED');

        $this->assertSame(100, $this->user->fresh()->point_balance);
        $this->assertSame(100000, $this->user->fresh()->balance);
        $this->assertDatabaseCount('pembelians', 0);
        $this->assertDatabaseHas('point_histories', [
            'user_id' => $this->user->id,
            'type' => 'redeem',
            'points' => 50,
        ]);
        $this->assertDatabaseHas('point_histories', [
            'user_id' => $this->user->id,
            'type' => 'earn',
            'points' => 50,
            'description' => 'Refund poin dari pembelian 86 Diamond',
        ]);
    }

    /** @test */
    public function repeated_successful_order_with_same_idempotency_key_deducts_points_once()
    {
        $this->mock(\App\Services\ProviderRoutingService::class, function ($mock): void {
            $mock->shouldReceive('findBestProvider')
                ->andReturn([
                    'provider_code' => 'manual',
                    'sku' => 'manual-123',
                ]);
        });

        $payload = [
            'service' => $this->layanan->id,
            'payment_method' => 'SALDO',
            'nomor' => '081234567890',
            'uid' => '12345678',
            'zone' => '1234',
            'qty' => 1,
            'use_point' => 50,
        ];

        $first = $this->actingAs($this->user)
            ->withHeader('X-Idempotency-Key', 'point-order-retry-001')
            ->postJson(route('ordered'), $payload);
        $second = $this->actingAs($this->user)
            ->withHeader('X-Idempotency-Key', 'point-order-retry-001')
            ->postJson(route('ordered'), $payload);

        $first->assertOk()->assertJsonPath('status', true);
        $second->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('message', 'Order sudah diproses sebelumnya.');
        $this->assertSame($first->json('order_id'), $second->json('order_id'));
        $this->assertSame(50, $this->user->fresh()->point_balance);
        $this->assertDatabaseCount('pembelians', 1);
        $this->assertDatabaseCount('point_histories', 1);
        $this->assertDatabaseHas('point_histories', [
            'user_id' => $this->user->id,
            'type' => 'redeem',
            'points' => 50,
        ]);
    }

    /** @test */
    public function ordered_endpoint_caps_use_point_to_max_percent()
    {
        // Mock Provider Routing
        $this->mock(\App\Services\ProviderRoutingService::class, function ($mock) {
            $mock->shouldReceive('findBestProvider')
                 ->andReturn([
                     'provider_code' => 'manual',
                     'sku' => 'manual-123',
                 ]);
        });

        // Harga: 20000. Max diskon: 10000 (100 poin)
        // Coba redeem 150 poin (meski user punya, jika saldo di-update)
        $this->user->update(['point_balance' => 200]);

        $response = $this->actingAs($this->user)->postJson(route('ordered'), [
            'service' => $this->layanan->id,
            'payment_method' => 'SALDO',
            'nomor' => '081234567890', // WA number
            'uid'   => '12345678',     // User ID ML
            'zone'  => '1234',         // Zone ID ML
            'qty'   => 1,
            'use_point' => 150, // Melebihi batas max
        ]);

        $response->assertStatus(200); // Harus tetep sukses, tapi poin di cap.

        // Cap to 100 points
        $this->user->refresh();
        $this->assertEquals(100, $this->user->point_balance); // 200 - 100 = 100
        $this->assertEquals(90000, $this->user->balance); // 100000 - (20000 - 10000) = 90000

        $order = Pembelian::where('username', $this->user->username)->latest()->first();
        $this->assertNotNull($order);
        $this->assertEquals(100, $order->used_points);
    }
}
