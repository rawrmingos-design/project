<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicRateLimitExhaustionTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_check_throttles_repeated_target_and_returns_retry_after(): void
    {
        $ip = '203.0.113.30';
        $payload = [
            'uid' => 'THROTTLE-TARGET',
            'kategori_kode' => 'missing-category',
        ];

        for ($i = 0; $i < 6; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => $ip])
                ->postJson('/ajax/check-account', $payload)
                ->assertNotFound();
        }

        $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson('/ajax/check-account', $payload)
            ->assertStatus(429)
            ->assertHeader('Retry-After')
            ->assertJsonPath('error_code', 'TOO_MANY_REQUESTS')
            ->assertJsonStructure(['retryAfterSeconds']);
    }

    public function test_order_submit_throttles_before_controller_validation(): void
    {
        $ip = '203.0.113.31';

        for ($i = 0; $i < 5; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => $ip])
                ->postJson('/id')
                ->assertStatus(422);
        }

        $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson('/id')
            ->assertStatus(429)
            ->assertHeader('Retry-After')
            ->assertJsonPath('message', 'Too Many Requests');
    }

    public function test_status_throttles_per_order_before_database_lookup(): void
    {
        $ip = '203.0.113.32';

        for ($i = 0; $i < 10; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => $ip])
                ->getJson('/ajax/transaction-status/INV-THROTTLE-TARGET')
                ->assertNotFound();
        }

        $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->getJson('/ajax/transaction-status/INV-THROTTLE-TARGET')
            ->assertStatus(429)
            ->assertHeader('Retry-After')
            ->assertJsonPath('error_code', 'TOO_MANY_REQUESTS');
    }

    public function test_api_login_throttles_identifier_bucket_and_returns_json_429(): void
    {
        $ip = '203.0.113.33';
        $payload = [
            'username' => 'missing-throttle-user',
            'password' => 'invalid-password',
        ];

        for ($i = 0; $i < 8; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => $ip])
                ->postJson('/api/auth/login', $payload)
                ->assertUnauthorized();
        }

        $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson('/api/auth/login', $payload)
            ->assertStatus(429)
            ->assertHeader('Retry-After')
            ->assertJsonPath('error_code', 'TOO_MANY_REQUESTS');
    }

    public function test_api_register_throttles_identifier_bucket_before_validation(): void
    {
        $ip = '203.0.113.34';
        $payload = [
            'email' => 'invalid-email',
            'no_wa' => '081234567890',
        ];

        for ($i = 0; $i < 4; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => $ip])
                ->postJson('/api/auth/register', $payload)
                ->assertStatus(422);
        }

        $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson('/api/auth/register', $payload)
            ->assertStatus(429)
            ->assertHeader('Retry-After')
            ->assertJsonPath('error_code', 'TOO_MANY_REQUESTS');
    }

    public function test_razer_callback_uses_configured_quota(): void
    {
        config(['rate_limits.callbacks.razer_per_minute' => 2]);
        $ip = '203.0.113.35';

        for ($i = 0; $i < 2; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => $ip])
                ->postJson('/callback/razerpay')
                ->assertStatus(400);
        }

        $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson('/callback/razerpay')
            ->assertStatus(429)
            ->assertHeader('Retry-After')
            ->assertJsonPath('error_code', 'TOO_MANY_REQUESTS');
    }
}
