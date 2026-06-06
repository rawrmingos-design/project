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

    private function createLiveIntegration(array $allowedIps = []): ResellerIntegration
    {
        return ResellerIntegration::factory()->create([
            'mode'             => 'live',
            'is_active'        => true,
            'allowed_ips'      => $allowedIps,
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
        $integration = $this->createLiveIntegration([]); // empty whitelist

        $this->postJson('/api/v1/order', $this->baseOrderPayload(), [
            'Authorization' => 'Bearer testing_live_key',
        ])->assertStatus(403)
          ->assertJsonPath('error_code', 'IP_WHITELIST_EMPTY');
    }

    public function test_order_denied_when_ip_not_in_whitelist(): void
    {
        // Whitelist only has a different IP — test client is 127.0.0.1
        $integration = $this->createLiveIntegration(['1.2.3.4']);

        $this->postJson('/api/v1/order', $this->baseOrderPayload(), [
            'Authorization' => 'Bearer testing_live_key',
        ])->assertStatus(403)
          ->assertJsonPath('error_code', 'IP_NOT_WHITELISTED');
    }

    public function test_order_allowed_when_ip_is_in_whitelist(): void
    {
        // Whitelist the loopback IP used by the test HTTP client
        $integration = $this->createLiveIntegration(['127.0.0.1']);

        $response = $this->postJson('/api/v1/order', $this->baseOrderPayload(), [
            'Authorization' => 'Bearer testing_live_key',
        ]);

        // IP check passes → not a 403; may be 404 (SKU not found) or 422
        $this->assertNotEquals(403, $response->getStatusCode(),
            'IP 127.0.0.1 should be allowed through when whitelisted');
        $this->assertNotEquals('IP_NOT_WHITELISTED', $response->json('error_code'));
        $this->assertNotEquals('IP_WHITELIST_EMPTY', $response->json('error_code'));
    }

    public function test_order_allowed_when_ip_matches_cidr_range(): void
    {
        // 127.0.0.0/8 covers 127.0.0.1 (test loopback)
        $integration = $this->createLiveIntegration(['127.0.0.0/8']);

        $response = $this->postJson('/api/v1/order', $this->baseOrderPayload(), [
            'Authorization' => 'Bearer testing_live_key',
        ]);

        $this->assertNotEquals(403, $response->getStatusCode(),
            'IP 127.0.0.1 should match CIDR 127.0.0.0/8');
    }

    public function test_order_denied_when_ip_does_not_match_cidr(): void
    {
        // 10.0.0.0/8 does NOT include 127.0.0.1
        $integration = $this->createLiveIntegration(['10.0.0.0/8']);

        $this->postJson('/api/v1/order', $this->baseOrderPayload(), [
            'Authorization' => 'Bearer testing_live_key',
        ])->assertStatus(403)
          ->assertJsonPath('error_code', 'IP_NOT_WHITELISTED');
    }

    // ─── Tests: IP Whitelist on POST /api/v1/status-order/{invoice} ──────────

    public function test_status_order_denied_when_ip_whitelist_is_empty(): void
    {
        $integration = $this->createLiveIntegration([]);

        $this->postJson('/api/v1/status-order/FAKE-INVOICE-001', [], [
            'Authorization' => 'Bearer testing_live_key',
        ])->assertStatus(403)
          ->assertJsonPath('error_code', 'IP_WHITELIST_EMPTY');
    }

    public function test_status_order_allowed_when_ip_is_whitelisted(): void
    {
        $integration = $this->createLiveIntegration(['127.0.0.1']);

        $response = $this->postJson('/api/v1/status-order/FAKE-INVOICE-001', [], [
            'Authorization' => 'Bearer testing_live_key',
        ]);

        // Should get past IP check (404 = invoice not found, not 403 = IP blocked)
        $this->assertNotEquals(403, $response->getStatusCode(),
            'Request should pass IP check when IP is whitelisted');
    }

    // ─── Tests: Sandbox routes NOT affected by IP whitelist ──────────────────

    public function test_sandbox_order_does_not_require_ip_whitelist(): void
    {
        $sandboxIntegration = ResellerIntegration::factory()->sandbox()->create([
            'allowed_ips' => [], // empty — sandbox ignores this
        ]);

        $response = $this->postJson('/api/v1/sandbox/order', [
            'code'            => 'SOME-SKU-123',
            'referenceNumber' => 'SBX-REF-' . uniqid(),
            'data'            => '12345678|1234',
        ], [
            'Authorization' => 'Bearer testing_sbx_key',
        ]);

        // The sandbox IP error codes are not in the response — any non-IP-related status
        $responseData = $response->json();
        $errorCode    = $responseData['error_code'] ?? null;

        $this->assertNotContains($errorCode, ['IP_NOT_WHITELISTED', 'IP_WHITELIST_EMPTY'],
            'Sandbox orders should not be blocked by IP whitelist middleware');
    }
}
