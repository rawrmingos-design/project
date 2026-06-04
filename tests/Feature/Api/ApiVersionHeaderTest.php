<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifies that X-API-Version header is injected on all /api/v1/* responses.
 * Webhook routes (/api/webhooks/*) must NOT have this header (no contamination).
 */
class ApiVersionHeaderTest extends TestCase
{
    use RefreshDatabase;

    public function test_live_endpoint_returns_version_header(): void
    {
        // No credentials — will get 401, but header should still be present
        $response = $this->postJson('/api/v1/balance');

        $response->assertHeader('X-API-Version', '1');
    }

    public function test_sandbox_endpoint_returns_sandbox_version_header(): void
    {
        $response = $this->postJson('/api/v1/sandbox/balance');

        $response->assertHeader('X-API-Version', '1-sandbox');
    }

    public function test_version_header_present_even_on_unauthenticated_response(): void
    {
        // No Authorization header — should still get X-API-Version on 401
        $response = $this->postJson('/api/v1/balance');

        $response->assertHeader('X-API-Version', '1');
    }

    public function test_sandbox_version_header_present_even_on_unauthenticated_response(): void
    {
        $response = $this->postJson('/api/v1/sandbox/balance');

        $response->assertHeader('X-API-Version', '1-sandbox');
    }

    public function test_order_endpoint_also_gets_version_header(): void
    {
        $response = $this->postJson('/api/v1/order', []);

        $response->assertHeader('X-API-Version', '1');
    }
}

