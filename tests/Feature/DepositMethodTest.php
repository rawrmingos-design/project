<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Method;
use App\Models\ResellerIntegration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepositMethodTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_deposit_methods()
    {
        $response = $this->getJson('/id/reseller/deposit-methods');

        // Depending on auth config, might be 401 Unauthorized for JSON requests or 302
        // If it throws AuthenticationException, getJson usually returns 401.
        $response->assertStatus(401);
    }

    public function test_reseller_can_fetch_deposit_methods_json()
    {
        $user = User::factory()->create([
            'role' => 'Member'
        ]);

        ResellerIntegration::create([
            'user_id' => $user->id,
            'integration_code' => 'TEST-002',
            'is_active' => true,
        ]);

        // Seed one method
        Method::create([
            'name' => 'BCA',
            'code' => 'BCA',
            'tipe' => 'bank',
            'images' => 'bca.png',
            'keterangan' => 'Bank BCA',
            'payment' => 'Manual',
            'min_pembelian' => 10000,
            'max_pembelian' => 1000000,
            'statuspayment' => 1,
        ]);

        $response = $this->actingAs($user)->getJson('/id/reseller/deposit-methods');

        $response->assertStatus(200)
                 ->assertJsonPath('success', true)
                 ->assertJsonCount(1, 'data');
    }
}
