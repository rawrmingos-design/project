<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Method;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiV2CheckoutOrderServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['tenancy.disabled' => false]);
    }

    public function test_api_v2_joki_checkout_does_not_require_uid_and_persists_joki_details(): void
    {
        $category = Kategori::factory()->create([
            'tipe' => 'joki',
            'require_user_id' => false,
        ]);

        $service = Layanan::factory()->create([
            'kategori_id' => $category->id,
            'layanan' => 'Joki Rank Epic',
            'provider' => 'manual',
            'provider_id' => 'joki-rank-epic',
            'harga_member' => 100000,
            'harga_platinum' => 100000,
            'harga_gold' => 100000,
            'profit_member' => 10000,
            'profit_platinum' => 10000,
            'profit_gold' => 10000,
        ]);

        Method::query()->create([
            'name' => 'Manual Transfer',
            'code' => 'MANUAL',
            'payment' => 'manual',
            'tipe' => 'manual',
            'images' => 'manual.png',
            'keterangan' => 'Manual transfer',
            'fee_percent' => 0,
            'fix_fee' => 0,
            'statuspayment' => 1,
        ]);

        $response = $this->postJson('/api/v2/order/store', [
            'service' => $service->id,
            'payment_method' => 'MANUAL',
            'nomor' => '081234567890',
            'ktg_tipe' => 'joki',
            'qty' => 2,
            'email_joki' => 'player@example.test',
            'password_joki' => 'secret-pass',
            'loginvia_joki' => 'Moonton',
            'nickname_joki' => 'PlayerOne',
            'request_joki' => 'Push sampai Legend',
            'catatan_joki' => 'Main malam',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('payment.amount', 200000);

        $orderId = $response->json('order_id');
        $order = \App\Models\Pembelian::query()->where('order_id', $orderId)->firstOrFail();

        $this->assertSame('-', $order->user_id);
        $this->assertSame('-', $order->zone);
        $this->assertSame('-', $order->nickname);
        $this->assertSame('joki', $order->tipe_transaksi);

        $this->assertDatabaseHas('data_joki', [
            'order_id' => $orderId,
            'email_joki' => 'player@example.test',
            'password_joki' => 'secret-pass',
            'loginvia_joki' => 'Moonton',
            'nickname_joki' => 'PlayerOne',
            'request_joki' => 'Push sampai Legend',
            'catatan_joki' => 'Main malam',
            'qty' => 2,
            'status_joki' => 'Pending',
        ]);
    }

    public function test_api_v2_checkout_idempotency_enforces_tenant_isolation(): void
    {
        config(['app.url' => 'https://istanatopup.test']);

        $tenantOwner1 = User::factory()->create(['role' => 'Gold']);
        $tenant1 = Tenant::create([
            'name' => 'Store A',
            'subdomain' => 'store-a',
            'status' => Tenant::STATUS_ACTIVE,
            'owner_user_id' => $tenantOwner1->id,
            'tier' => 'starter',
        ]);

        $tenantOwner2 = User::factory()->create(['role' => 'Gold']);
        $tenant2 = Tenant::create([
            'name' => 'Store B',
            'subdomain' => 'store-b',
            'status' => Tenant::STATUS_ACTIVE,
            'owner_user_id' => $tenantOwner2->id,
            'tier' => 'starter',
        ]);

        $category = Kategori::factory()->create(['tipe' => 'game', 'require_user_id' => true]);
        $service = Layanan::factory()->create([
            'kategori_id' => $category->id,
            'layanan' => '100 Diamonds',
            'provider' => 'manual',
            'provider_id' => 'ml-100',
            'harga_member' => 10000,
        ]);

        Method::query()->create([
            'name' => 'Manual Transfer',
            'code' => 'MANUAL',
            'payment' => 'manual',
            'tipe' => 'manual',
            'images' => 'manual.png',
            'keterangan' => 'Manual transfer desc',
            'statuspayment' => 1,
        ]);

        $payload = [
            'service' => $service->id,
            'payment_method' => 'MANUAL',
            'nomor' => '081234567890',
            'uid' => '123456',
            'zone' => '1234',
        ];

        // Store A creates an order
        $responseA = $this->withHeader('X-Idempotency-Key', 'identical-key-123')
            ->postJson("https://store-a.istanatopup.test/api/v2/order/store", $payload);

        $responseA->assertOk()->assertJsonPath('status', true);
        $orderIdA = $responseA->json('order_id');

        // Store B creates an order with the SAME idempotency key and payload
        $responseB = $this->withHeader('X-Idempotency-Key', 'identical-key-123')
            ->postJson("https://store-b.istanatopup.test/api/v2/order/store", $payload);

        $responseB->assertOk()->assertJsonPath('status', true);
        $orderIdB = $responseB->json('order_id');

        // The orders should be distinct because the idempotency key includes tenant context
        $this->assertNotEquals($orderIdA, $orderIdB);

        $orderA = \App\Models\Pembelian::where('order_id', $orderIdA)->first();
        $orderB = \App\Models\Pembelian::where('order_id', $orderIdB)->first();

        $this->assertEquals($tenant1->id, $orderA->tenant_id);
        $this->assertEquals($tenant2->id, $orderB->tenant_id);
    }
}
