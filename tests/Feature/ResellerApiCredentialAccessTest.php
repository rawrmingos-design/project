<?php

namespace Tests\Feature;

use App\Models\Pembelian;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResellerApiCredentialAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_reseller_api_status_order_allows_valid_token_without_ip_whitelist(): void
    {
        $user = User::factory()->create([
            'api_key' => 'demo-token',
            'username' => 'api.member',
            'role' => 'Member',
        ]);

        Pembelian::factory()->create([
            'order_id' => 'INV-API-001',
            'username' => $user->username,
            'user_id' => '998877',
            'zone' => '3344',
            'status' => 'Sukses',
        ]);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.99'])
            ->withHeader('Authorization', 'Bearer demo-token')
            ->postJson('/api/v1/status-order/INV-API-001')
            ->assertOk()
            ->assertJsonPath('error', false)
            ->assertJsonPath('data.invoiceNumber', 'INV-API-001')
            ->assertJsonPath('data.statusCode', 'Success');
    }

    public function test_reseller_api_errors_are_token_based_not_legacy_ip_whitelist_based(): void
    {
        $response = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.99'])
            ->postJson('/api/v1/status-order/INV-API-404');

        $response
            ->assertStatus(403)
            ->assertJsonPath('error', true)
            ->assertJsonPath('message', 'Access Token is required')
            ->assertJsonPath('error_code', 'ACCESS_TOKEN_REQUIRED');

        $this->assertStringNotContainsString(
            'Access denied - is not authorized to access this resource.',
            $response->getContent(),
        );
    }
}
