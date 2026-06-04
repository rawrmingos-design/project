<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookSignatureTest extends TestCase
{
    use RefreshDatabase;

    // ── Digiflazz ─────────────────────────────────────────────────────────────

    public function test_digiflazz_webhook_without_signature_header_returns_401(): void
    {
        // Configure a secret so the controller doesn't 500 on misconfiguration
        config(['providers.digiflazz.webhook_secret' => 'test-digi-secret']);

        $response = $this->postJson('/api/webhooks/digiflazz', [
            'ref_id' => 'ORD-001',
            'status' => 'Sukses',
        ]);

        // Without signature → verifyDigiflazzSignature returns false → 401
        $response->assertStatus(401)
            ->assertJson(['error' => 'Invalid signature']);
    }

    public function test_digiflazz_webhook_with_empty_signature_returns_401(): void
    {
        config(['providers.digiflazz.webhook_secret' => 'test-digi-secret']);

        $response = $this->withHeader('X-Digiflazz-Signature', '')
            ->postJson('/api/webhooks/digiflazz', [
                'ref_id' => 'ORD-001',
                'status' => 'Sukses',
            ]);

        $response->assertStatus(401);
    }

    public function test_digiflazz_webhook_with_wrong_signature_returns_401(): void
    {
        config(['providers.digiflazz.webhook_secret' => 'test-digi-secret']);

        $response = $this->withHeader('X-Digiflazz-Signature', 'wrong-signature-here')
            ->postJson('/api/webhooks/digiflazz', [
                'ref_id' => 'ORD-001',
                'status' => 'Sukses',
            ]);

        $response->assertStatus(401);
    }

    public function test_digiflazz_webhook_with_valid_signature_passes_guard(): void
    {
        $secret  = 'test-digi-secret';
        $data    = ['ref_id' => 'ORD-VALID-001', 'status' => 'Sukses'];
        $encoded = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $validSignature = hash_hmac('sha256', $encoded, $secret);

        config(['providers.digiflazz.webhook_secret' => $secret]);

        // Valid signature should NOT return 401 (may return 200 or other if order not found)
        // Bypass inbound.whitelist since test env IP (127.0.0.1) is not whitelisted
        $response = $this->withoutMiddleware(\App\Http\Middleware\InboundSourceWhitelist::class)
            ->withHeaders(['X-Digiflazz-Signature' => $validSignature])
            ->json('POST', '/api/webhooks/digiflazz', $data);

        $this->assertNotEquals(401, $response->getStatusCode(),
            'Valid signature should pass signature verification (not 401)');
    }

    public function test_digiflazz_webhook_without_configured_secret_returns_500(): void
    {
        config(['providers.digiflazz.webhook_secret' => null]);

        // Bypass inbound.whitelist to reach controller
        $response = $this->withoutMiddleware(\App\Http\Middleware\InboundSourceWhitelist::class)
            ->withHeaders(['X-Digiflazz-Signature' => 'any-signature'])
            ->postJson('/api/webhooks/digiflazz', ['ref_id' => 'ORD-001']);

        // Misconfigured server → 500 with explicit error
        $response->assertStatus(500)
            ->assertJson(['error' => 'Server misconfigured']);
    }

    // ── BangJeff ──────────────────────────────────────────────────────────────

    public function test_bangjeff_webhook_without_signature_header_returns_401(): void
    {
        config(['providers.bangjeff.webhook_secret' => 'test-bjeff-secret']);

        $response = $this->postJson('/api/webhooks/bangjeff', [
            'order_id' => 'ORD-BJ-001',
            'status'   => 'Sukses',
        ]);

        $response->assertStatus(401)
            ->assertJson(['error' => 'Invalid signature']);
    }

    public function test_bangjeff_webhook_with_empty_signature_returns_401(): void
    {
        config(['providers.bangjeff.webhook_secret' => 'test-bjeff-secret']);

        $response = $this->withHeader('X-BangJeff-Signature', '')
            ->postJson('/api/webhooks/bangjeff', [
                'order_id' => 'ORD-BJ-001',
                'status'   => 'Sukses',
            ]);

        $response->assertStatus(401);
    }

    public function test_bangjeff_webhook_with_wrong_signature_returns_401(): void
    {
        config(['providers.bangjeff.webhook_secret' => 'test-bjeff-secret']);

        $response = $this->withHeader('X-BangJeff-Signature', 'wrong-bangjeff-sig')
            ->postJson('/api/webhooks/bangjeff', [
                'order_id' => 'ORD-BJ-001',
                'status'   => 'Sukses',
            ]);

        $response->assertStatus(401);
    }
}
