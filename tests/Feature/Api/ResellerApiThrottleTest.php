<?php

namespace Tests\Feature\Api;

use App\Models\Pembelian;
use App\Models\ResellerIntegration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ResellerApiThrottleTest extends TestCase
{
    use RefreshDatabase;

    public function test_reseller_api_routes_are_bound_to_expected_throttle_limiters(): void
    {
        $this->assertRouteHasThrottle('/api/v1/balance', 'reseller-api-balance');
        $this->assertRouteHasThrottle('/api/v1/category', 'reseller-api-category');
        $this->assertRouteHasThrottle('/api/v1/variant', 'reseller-api-variant');
        $this->assertRouteHasThrottle('/api/v1/order', 'reseller-api-order');
        $this->assertRouteHasThrottle('/api/v1/status-order/INV-THROTTLE-001', 'reseller-api-status');
    }

    public function test_balance_returns_json_429_after_token_limit_is_exhausted(): void
    {
        ResellerIntegration::factory()->create([
            'api_key_hash' => hash('sha256', 'token-throttle-balance'),
            'mode' => 'live',
            'is_active' => true,
            'allowed_ips' => ['203.0.113.10'],
        ]);

        $ip = '203.0.113.10';

        for ($i = 0; $i < 30; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => $ip])
                ->withHeader('Authorization', 'Bearer token-throttle-balance')
                ->postJson('/api/v1/balance')
                ->assertOk();
        }

        $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->withHeader('Authorization', 'Bearer token-throttle-balance')
            ->postJson('/api/v1/balance')
            ->assertStatus(429)
            ->assertJsonPath('error', true)
            ->assertJsonPath('code', 429)
            ->assertJsonPath('message', 'Too Many Requests')
            ->assertJsonPath('error_code', 'TOO_MANY_REQUESTS')
            ->assertJsonStructure(['retryAfterSeconds']);
    }

    public function test_order_throttles_faster_than_status_order(): void
    {
        $integration = ResellerIntegration::factory()->create([
            'api_key_hash' => hash('sha256', 'token-throttle-order'),
            'mode'             => 'live',
            'is_active'        => true,
            'allowed_ips'      => ['203.0.113.11'],
        ]);

        Pembelian::factory()->create([
            'order_id' => 'INV-STATUS-LONG-001',
            'username' => $integration->user->username,
            'user_id'  => '998877',
            'zone'     => '3344',
            'status'   => 'Sukses',
        ]);

        $ip = '203.0.113.11';

        for ($i = 0; $i < 20; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => $ip])
                ->withHeaders([
                    'Authorization'               => 'Bearer token-throttle-order',
                ])
                ->postJson('/api/v1/order', [
                    'code'            => 'MANUAL-MVP-001',
                    'referenceNumber' => 'THROTTLE-ORDER-' . $i,
                    'data'            => '12345678|2001',
                ])
                ->assertStatus(422); // Unprocessable Entity because missing user_id etc, but passes throttle
        }

        $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->withHeaders([
                'Authorization'               => 'Bearer token-throttle-order',
            ])
            ->postJson('/api/v1/order', [
                'code'            => 'MANUAL-MVP-001',
                'referenceNumber' => 'THROTTLE-ORDER-FINAL',
                'data'            => '12345678|2001',
            ])
            ->assertStatus(429)
            ->assertJsonPath('message', 'Too Many Requests')
            ->assertJsonPath('error_code', 'TOO_MANY_REQUESTS');

        // Status-order has a higher limit (50), so 25 requests should still be OK
        for ($i = 0; $i < 25; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => $ip])
                ->withHeaders([
                    'Authorization'               => 'Bearer token-throttle-order',
                ])
                ->postJson('/api/v1/status-order/INV-STATUS-LONG-001')
                ->assertOk();
        }
    }

    public function test_two_tokens_on_same_ip_do_not_share_balance_quota(): void
    {
        ResellerIntegration::factory()->create([
            'api_key_hash' => hash('sha256', 'token-throttle-shared-a'),
            'mode' => 'live',
            'is_active' => true,
            'allowed_ips' => ['203.0.113.12'],
        ]);
        ResellerIntegration::factory()->create([
            'api_key_hash' => hash('sha256', 'token-throttle-shared-b'),
            'mode' => 'live',
            'is_active' => true,
            'allowed_ips' => ['203.0.113.12'],
        ]);

        $ip = '203.0.113.12';

        for ($i = 0; $i < 30; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => $ip])
                ->withHeader('Authorization', 'Bearer token-throttle-shared-a')
                ->postJson('/api/v1/balance')
                ->assertOk();
        }

        $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->withHeader('Authorization', 'Bearer token-throttle-shared-a')
            ->postJson('/api/v1/balance')
            ->assertStatus(429)
            ->assertJsonPath('error_code', 'TOO_MANY_REQUESTS');

        $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->withHeader('Authorization', 'Bearer token-throttle-shared-b')
            ->postJson('/api/v1/balance')
            ->assertOk();
    }

    public function test_missing_token_requests_fall_back_to_ip_throttle_and_return_json_429(): void
    {
        $ip = '203.0.113.13';

        for ($i = 0; $i < 30; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => $ip])
                ->postJson('/api/v1/balance')
                ->assertStatus(403)
                ->assertJsonPath('message', 'Access Token is required')
                ->assertJsonPath('error_code', 'ACCESS_TOKEN_REQUIRED');
        }

        $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson('/api/v1/balance')
            ->assertStatus(429)
            ->assertJsonPath('message', 'Too Many Requests')
            ->assertJsonPath('error_code', 'TOO_MANY_REQUESTS')
            ->assertJsonStructure(['retryAfterSeconds']);
    }

    public function test_invalid_token_requests_are_throttled_consistently(): void
    {
        $ip = '203.0.113.14';

        for ($i = 0; $i < 30; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => $ip])
                ->withHeader('Authorization', 'Bearer invalid-throttle-token')
                ->postJson('/api/v1/balance')
                ->assertStatus(403)
                ->assertJsonPath('message', 'Invalid Token')
                ->assertJsonPath('error_code', 'INVALID_TOKEN');
        }

        $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->withHeader('Authorization', 'Bearer invalid-throttle-token')
            ->postJson('/api/v1/balance')
            ->assertStatus(429)
            ->assertJsonPath('message', 'Too Many Requests')
            ->assertJsonPath('error_code', 'TOO_MANY_REQUESTS');
    }

    private function assertRouteHasThrottle(string $uri, string $limiter): void
    {
        $route = Route::getRoutes()->match(Request::create($uri, 'POST'));

        $this->assertContains('throttle:' . $limiter, $route->gatherMiddleware());
    }
}
