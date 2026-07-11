<?php

namespace Tests\Feature\Api;

use App\Models\Pembelian;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderControllerStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_v2_order_status_enforces_tenant_isolation(): void
    {
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

        $orderId = 'TRX-123456';
        Pembelian::create([
            'tenant_id' => $tenant1->id,
            'order_id' => $orderId,
            'username' => 'guest',
            'user_id' => '123456',
            'zone' => '1234',
            'nickname' => 'Guest User',
            'layanan' => 'Diamond',
            'harga' => 10000,
            'profit' => 1000,
            'status' => 'Pending',
            'tipe_transaksi' => 'game',
        ]);

        // Request from main site -> fails
        config(['app.url' => 'https://istanatopup.test']);
        $this->getJson("https://istanatopup.test/api/v2/order/status/{$orderId}")
             ->assertStatus(404);

        // Request from Tenant 2 -> fails
        $this->getJson("https://store-b.istanatopup.test/api/v2/order/status/{$orderId}")
             ->assertStatus(404);

        // Request from Tenant 1 -> succeeds
        $response = $this->getJson("https://store-a.istanatopup.test/api/v2/order/status/{$orderId}");
        $response->assertStatus(200)
                 ->assertJsonPath('status', true);
    }
}
