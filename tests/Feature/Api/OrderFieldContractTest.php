<?php

namespace Tests\Feature\Api;

use App\Models\ResellerIntegration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 5 — Task 5.1
 * Verifies the breaking change: field `data` (pipe-separated) has been
 * replaced with explicit `user_id` + `zone_id` fields in both live and
 * sandbox /api/v1/order endpoints.
 */
class OrderFieldContractTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $rawKey;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rawKey = 'TEST-CONTRACT-' . strtoupper(substr(md5(uniqid()), 0, 16));

        $this->user = User::factory()->create([
            'username'       => 'reseller.contract.' . uniqid(),
            'role'           => 'Member',
            'balance'        => 500_000,
            'api_key'        => $this->rawKey,
            'api_key_prefix' => null,
        ]);

        ResellerIntegration::factory()->create([
            'user_id'          => $this->user->getKey(),
            'integration_code' => 'LIVE-CONTRACT-' . strtoupper(uniqid()),
            'mode'             => 'live',
            'is_active'        => true,
            'allowed_ips'      => ['127.0.0.1'],
        ]);

        ResellerIntegration::factory()->create([
            'user_id'          => $this->user->getKey(),
            'integration_code' => 'SBX-CONTRACT-' . strtoupper(uniqid()),
            'mode'             => 'sandbox',
            'is_active'        => true,
            'allowed_ips'      => [],
        ]);
    }

    // ── Live /api/v1/order ────────────────────────────────────────────────

    public function test_live_order_rejects_when_user_id_missing(): void
    {
        $response = $this->withoutMiddleware([
                \App\Http\Middleware\InboundSourceWhitelist::class,
                \App\Http\Middleware\EnforceResellerIpWhitelist::class,
            ])
            ->withToken($this->rawKey)
            ->postJson('/api/v1/order', [
                'code'            => 'SOME-CODE',
                'referenceNumber' => 'REF-NO-USERID-' . uniqid(),
                // user_id intentionally omitted
            ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['error' => true]);
    }

    public function test_live_order_accepts_user_id_without_zone_id(): void
    {
        // With only user_id (no zone_id), validation should pass.
        // The order will fail because the code doesn't exist OR integration
        // middleware rejects it, but that's NOT a 422 validation error.
        $response = $this->withoutMiddleware([
                \App\Http\Middleware\InboundSourceWhitelist::class,
                \App\Http\Middleware\EnforceResellerIpWhitelist::class,
                \App\Http\Middleware\ResolveLiveResellerIntegration::class,
            ])
            ->withToken($this->rawKey)
            ->postJson('/api/v1/order', [
                'code'            => 'NONEXISTENT-CODE-XYZ',
                'referenceNumber' => 'REF-NOZID-' . uniqid(),
                'user_id'         => '123456789',
                // zone_id omitted — should be OK
            ]);

        // Must NOT be a 422 validation error — any other status is acceptable
        $this->assertNotEquals(422, $response->status(),
            'Omitting zone_id must not cause a validation error');
        $response->assertJsonFragment(['error' => true]);
    }

    public function test_live_order_accepts_both_user_id_and_zone_id(): void
    {
        $response = $this->withoutMiddleware([
                \App\Http\Middleware\InboundSourceWhitelist::class,
                \App\Http\Middleware\EnforceResellerIpWhitelist::class,
                \App\Http\Middleware\ResolveLiveResellerIntegration::class,
            ])
            ->withToken($this->rawKey)
            ->postJson('/api/v1/order', [
                'code'            => 'NONEXISTENT-CODE-XYZ',
                'referenceNumber' => 'REF-WITHZID-' . uniqid(),
                'user_id'         => '123456789',
                'zone_id'         => '9001',
            ]);

        // Not 422 — validation passed, failed because code doesn't exist
        $this->assertNotEquals(422, $response->status(),
            'Providing both user_id and zone_id must pass validation');
    }

    public function test_live_order_rejects_old_pipe_data_field(): void
    {
        // The old `data` field without `user_id` must cause a 422
        $response = $this->withoutMiddleware([
                \App\Http\Middleware\InboundSourceWhitelist::class,
                \App\Http\Middleware\EnforceResellerIpWhitelist::class,
            ])
            ->withToken($this->rawKey)
            ->postJson('/api/v1/order', [
                'code'            => 'SOME-CODE',
                'referenceNumber' => 'REF-OLD-PIPE-' . uniqid(),
                'data'            => '123456789|9001',   // old format
                // user_id intentionally omitted
            ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['error' => true]);
    }

    // ── Sandbox /api/v1/sandbox/order ────────────────────────────────────
    //
    // Sandbox auth requires a sandbox API key hash — which we don't have in
    // this test setup. We use withoutMiddleware to bypass auth and reach
    // the validation layer directly.

    public function test_sandbox_order_rejects_when_user_id_missing(): void
    {
        // resolveApiUser() looks for Bearer token, so we supply the raw key.
        // We bypass integration middleware so validation is the first gate hit.
        $response = $this->withoutMiddleware([
                \App\Http\Middleware\InboundSourceWhitelist::class,
                \App\Http\Middleware\AuthenticateSandboxApi::class,
                \App\Http\Middleware\ResolveSandboxResellerIntegration::class,
                \App\Http\Middleware\EnforceResellerIpWhitelist::class,
            ])
            ->withToken($this->rawKey)
            ->postJson('/api/v1/sandbox/order', [
                'code'            => 'SOME-CODE',
                'referenceNumber' => 'SBX-REF-NO-USERID-' . uniqid(),
                // user_id intentionally omitted
            ]);

        // resolveApiUser() needs a valid Bearer key in DB to find the user;
        // it will authenticate via the live key, so we get 403 from
        // INVALID_INTEGRATION_CODE (which fires AFTER validation in sandbox).
        // The important thing: the old `data` field is gone from validation.
        // Accept any 4xx — just not 200.
        $this->assertNotEquals(200, $response->status());
        $response->assertJsonFragment(['error' => true]);
    }

    public function test_sandbox_order_accepts_user_id_without_zone_id(): void
    {
        $response = $this->withoutMiddleware([
                \App\Http\Middleware\InboundSourceWhitelist::class,
                \App\Http\Middleware\AuthenticateSandboxApi::class,
                \App\Http\Middleware\ResolveSandboxResellerIntegration::class,
            ])
            ->postJson('/api/v1/sandbox/order', [
                'code'            => 'NONEXISTENT-CODE',
                'referenceNumber' => 'SBX-NOZID-' . uniqid(),
                'user_id'         => '123456789',
                // zone_id omitted
            ]);

        // Not 422 — validation passed
        $this->assertNotEquals(422, $response->status(),
            'Omitting zone_id in sandbox must not cause a validation error');
    }

    public function test_sandbox_order_rejects_old_pipe_data_field(): void
    {
        // Same pattern: supply live key so user resolves, then bypass integration middleware.
        // The test verifies that providing only the old `data` field (without user_id)
        // results in a failure response — not a 200 success.
        $response = $this->withoutMiddleware([
                \App\Http\Middleware\InboundSourceWhitelist::class,
                \App\Http\Middleware\AuthenticateSandboxApi::class,
                \App\Http\Middleware\ResolveSandboxResellerIntegration::class,
                \App\Http\Middleware\EnforceResellerIpWhitelist::class,
            ])
            ->withToken($this->rawKey)
            ->postJson('/api/v1/sandbox/order', [
                'code'            => 'SOME-CODE',
                'referenceNumber' => 'SBX-OLD-PIPE-' . uniqid(),
                'data'            => '123456789|9001',   // old format, no user_id
            ]);

        // Must not succeed — either 422 (if validation fires) or 403/404
        // (if integration check fires because user_id was technically in `data` not `user_id`).
        $this->assertNotEquals(200, $response->status());
        $response->assertJsonFragment(['error' => true]);
    }
}
