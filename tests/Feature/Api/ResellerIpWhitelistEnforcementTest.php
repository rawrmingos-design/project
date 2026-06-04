<?php

namespace Tests\Feature\Api;

use App\Models\ResellerIntegration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ResellerIpWhitelistEnforcementTest extends TestCase
{
    use RefreshDatabase;

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function createResellerUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role'           => 'Member',
            'balance'        => 100000,
            'api_key'        => 'test_plain_live_key_for_ip_test',
            'api_key_hint'   => '..._test',
            'api_key_prefix' => null, // legacy plain-text key (no prefix → backward compat path)
        ], $overrides));
    }

    private function createLiveIntegration(User $user, array $allowedIps = []): ResellerIntegration
    {
        return ResellerIntegration::create([
            'user_id'          => $user->id,
            'integration_code' => 'LIVE-IP-TEST-' . strtoupper(uniqid()),
            'mode'             => 'live',
            'is_active'        => true,
            'metadata'         => ['allowed_ips' => $allowedIps],
        ]);
    }

    private function baseOrderPayload(): array
    {
        return [
            'code'            => 'SOME-SKU-123',
            'referenceNumber' => 'REF-' . uniqid(),
            'data'            => '12345678|1234',
        ];
    }

    // ─── Tests: IP Whitelist on POST /api/v1/order ───────────────────────────

    public function test_order_denied_when_ip_whitelist_is_empty(): void
    {
        $user        = $this->createResellerUser();
        $integration = $this->createLiveIntegration($user, []); // empty whitelist

        $this->postJson('/api/v1/order', $this->baseOrderPayload(), [
            'Authorization'               => 'Bearer test_plain_live_key_for_ip_test',
            'X-Reseller-Integration-Code' => $integration->integration_code,
        ])->assertStatus(403)
          ->assertJsonPath('error_code', 'IP_WHITELIST_EMPTY');
    }

    public function test_order_denied_when_ip_not_in_whitelist(): void
    {
        $user        = $this->createResellerUser();
        // Whitelist only has a different IP — test client is 127.0.0.1
        $integration = $this->createLiveIntegration($user, ['1.2.3.4']);

        $this->postJson('/api/v1/order', $this->baseOrderPayload(), [
            'Authorization'               => 'Bearer test_plain_live_key_for_ip_test',
            'X-Reseller-Integration-Code' => $integration->integration_code,
        ])->assertStatus(403)
          ->assertJsonPath('error_code', 'IP_NOT_WHITELISTED');
    }

    public function test_order_allowed_when_ip_is_in_whitelist(): void
    {
        $user        = $this->createResellerUser();
        // Whitelist the loopback IP used by the test HTTP client
        $integration = $this->createLiveIntegration($user, ['127.0.0.1']);

        $response = $this->postJson('/api/v1/order', $this->baseOrderPayload(), [
            'Authorization'               => 'Bearer test_plain_live_key_for_ip_test',
            'X-Reseller-Integration-Code' => $integration->integration_code,
        ]);

        // IP check passes → not a 403; may be 404 (SKU not found) or 422
        $this->assertNotEquals(403, $response->getStatusCode(),
            'IP 127.0.0.1 should be allowed through when whitelisted');
        $this->assertNotEquals('IP_NOT_WHITELISTED', $response->json('error_code'));
        $this->assertNotEquals('IP_WHITELIST_EMPTY', $response->json('error_code'));
    }

    public function test_order_allowed_when_ip_matches_cidr_range(): void
    {
        $user        = $this->createResellerUser();
        // 127.0.0.0/8 covers 127.0.0.1 (test loopback)
        $integration = $this->createLiveIntegration($user, ['127.0.0.0/8']);

        $response = $this->postJson('/api/v1/order', $this->baseOrderPayload(), [
            'Authorization'               => 'Bearer test_plain_live_key_for_ip_test',
            'X-Reseller-Integration-Code' => $integration->integration_code,
        ]);

        $this->assertNotEquals(403, $response->getStatusCode(),
            'IP 127.0.0.1 should match CIDR 127.0.0.0/8');
    }

    public function test_order_denied_when_ip_does_not_match_cidr(): void
    {
        $user        = $this->createResellerUser();
        // 10.0.0.0/8 does NOT include 127.0.0.1
        $integration = $this->createLiveIntegration($user, ['10.0.0.0/8']);

        $this->postJson('/api/v1/order', $this->baseOrderPayload(), [
            'Authorization'               => 'Bearer test_plain_live_key_for_ip_test',
            'X-Reseller-Integration-Code' => $integration->integration_code,
        ])->assertStatus(403)
          ->assertJsonPath('error_code', 'IP_NOT_WHITELISTED');
    }

    // ─── Tests: IP Whitelist on POST /api/v1/status-order/{invoice} ──────────

    public function test_status_order_denied_when_ip_whitelist_is_empty(): void
    {
        $user        = $this->createResellerUser();
        $integration = $this->createLiveIntegration($user, []);

        $this->postJson('/api/v1/status-order/FAKE-INVOICE-001', [], [
            'Authorization'               => 'Bearer test_plain_live_key_for_ip_test',
            'X-Reseller-Integration-Code' => $integration->integration_code,
        ])->assertStatus(403)
          ->assertJsonPath('error_code', 'IP_WHITELIST_EMPTY');
    }

    public function test_status_order_allowed_when_ip_is_whitelisted(): void
    {
        $user        = $this->createResellerUser();
        $integration = $this->createLiveIntegration($user, ['127.0.0.1']);

        $response = $this->postJson('/api/v1/status-order/FAKE-INVOICE-001', [], [
            'Authorization'               => 'Bearer test_plain_live_key_for_ip_test',
            'X-Reseller-Integration-Code' => $integration->integration_code,
        ]);

        // Should get past IP check (404 = invoice not found, not 403 = IP blocked)
        $this->assertNotEquals(403, $response->getStatusCode(),
            'Request should pass IP check when IP is whitelisted');
    }

    // ─── Tests: Sandbox routes NOT affected by IP whitelist ──────────────────

    public function test_sandbox_order_does_not_require_ip_whitelist(): void
    {
        $sandboxKey = 'sbx_test_key_' . str_pad('ip', 30, 'x', STR_PAD_LEFT);

        $user = User::factory()->create([
            'role'                 => 'Member',
            'sandbox_api_key_hash' => Hash::make($sandboxKey),
            'sandbox_api_key_hint' => '...' . substr($sandboxKey, -6),
        ]);

        $sandboxIntegration = ResellerIntegration::create([
            'user_id'          => $user->id,
            'integration_code' => 'SBX-NO-IP-' . strtoupper(uniqid()),
            'mode'             => 'sandbox',
            'is_active'        => true,
            'metadata'         => ['allowed_ips' => []], // empty — sandbox ignores this
        ]);

        $response = $this->postJson('/api/v1/sandbox/order', [
            'code'            => 'SOME-SKU-123',
            'referenceNumber' => 'SBX-REF-' . uniqid(),
            'data'            => '12345678|1234',
        ], [
            'Authorization'               => 'Bearer ' . $sandboxKey,
            'X-Reseller-Integration-Code' => $sandboxIntegration->integration_code,
        ]);

        // The sandbox IP error codes are not in the response — any non-IP-related status
        $responseData = $response->json();
        $errorCode    = $responseData['error_code'] ?? null;

        $this->assertNotContains($errorCode, ['IP_NOT_WHITELISTED', 'IP_WHITELIST_EMPTY'],
            'Sandbox orders should not be blocked by IP whitelist middleware');
    }
}
