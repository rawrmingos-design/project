<?php

namespace Tests\Feature;

use App\Models\Pembelian;
use App\Models\ResellerIntegration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResellerApiCredentialAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Since Phase 1 hardening, IP whitelist is MANDATORY for live API access.
     * Status-order now requires both:
     *   - Bearer token (auth.api)
     *   - X-Reseller-Integration-Code (resolve.live.reseller.integration)
     *   - Caller IP must be in integration's allowed_ips (reseller.ip.enforce)
     *
     * This test verifies that a reseller with proper credentials AND a
     * configured IP whitelist (including their server IP) can access status-order.
     */
    public function test_reseller_api_status_order_allows_valid_token_with_ip_whitelisted(): void
    {
        $clientIp = '203.0.113.99';

        $user = User::factory()->create([
            'api_key'  => 'demo-token',
            'username' => 'api.member',
            'role'     => 'Member',
        ]);

        $integration = ResellerIntegration::query()->create([
            'user_id'          => $user->getKey(),
            'integration_code' => 'demo-live-integration',
            'mode'             => 'live',
            'is_active'        => true,
            'metadata'         => ['allowed_ips' => [$clientIp]],
        ]);

        Pembelian::factory()->create([
            'order_id' => 'INV-API-001',
            'username' => $user->username,
            'user_id'  => '998877',
            'zone'     => '3344',
            'status'   => 'Sukses',
        ]);

        $this->withServerVariables(['REMOTE_ADDR' => $clientIp])
            ->withHeaders([
                'Authorization'               => 'Bearer demo-token',
                'X-Reseller-Integration-Code' => $integration->integration_code,
            ])
            ->postJson('/api/v1/status-order/INV-API-001')
            ->assertOk()
            ->assertJsonPath('error', false)
            ->assertJsonPath('data.invoiceNumber', 'INV-API-001')
            ->assertJsonPath('data.statusCode', 'Success');
    }

    /**
     * Verify that a valid token without IP whitelisted is denied (not just missing auth).
     * This replaces the old "allows valid token without ip whitelist" behavior,
     * which was removed in Phase 1 hardening.
     */
    public function test_reseller_api_status_order_denies_valid_token_when_ip_not_whitelisted(): void
    {
        $user = User::factory()->create([
            'api_key'  => 'demo-token-no-ip',
            'username' => 'api.member.no.ip',
            'role'     => 'Member',
        ]);

        ResellerIntegration::query()->create([
            'user_id'          => $user->getKey(),
            'integration_code' => 'demo-live-no-ip',
            'mode'             => 'live',
            'is_active'        => true,
            'metadata'         => ['allowed_ips' => ['1.2.3.4']], // different IP
        ]);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.99'])
            ->withHeaders([
                'Authorization'               => 'Bearer demo-token-no-ip',
                'X-Reseller-Integration-Code' => 'demo-live-no-ip',
            ])
            ->postJson('/api/v1/status-order/INV-API-404')
            ->assertStatus(403)
            ->assertJsonPath('error', true)
            ->assertJsonPath('error_code', 'IP_NOT_WHITELISTED');
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
