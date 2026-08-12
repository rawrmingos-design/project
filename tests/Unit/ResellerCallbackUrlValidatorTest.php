<?php

namespace Tests\Unit;

use App\Support\ResellerCallbackUrlValidator;
use Tests\TestCase;

class ResellerCallbackUrlValidatorTest extends TestCase
{
    public function test_live_callback_url_rejects_localhost_targets(): void
    {
        $reason = ResellerCallbackUrlValidator::failureReason('http://localhost/callback', 'live');

        $this->assertNotNull($reason);
        $this->assertStringContainsString('HTTPS', $reason);
    }

    public function test_sandbox_callback_url_rejects_localhost_targets(): void
    {
        $reason = ResellerCallbackUrlValidator::failureReason('http://localhost/callback', 'sandbox');

        $this->assertNotNull($reason);
        $this->assertStringContainsString('localhost', $reason);
    }

    /** @dataProvider protectedAddressProvider */
    public function test_callback_url_rejects_protected_literal_addresses(string $url): void
    {
        $this->assertNotNull(ResellerCallbackUrlValidator::failureReason($url, 'sandbox'));
    }

    public static function protectedAddressProvider(): array
    {
        return [
            ['http://127.0.0.1/callback'],
            ['http://10.0.0.1/callback'],
            ['http://172.16.0.1/callback'],
            ['http://192.168.1.1/callback'],
            ['http://169.254.169.254/latest'],
            ['http://[::1]/callback'],
            ['http://[fc00::1]/callback'],
            ['http://224.0.0.1/callback'],
        ];
    }

    public function test_callback_url_rejects_credentials_and_fragments(): void
    {
        $this->assertNotNull(ResellerCallbackUrlValidator::failureReason('https://user:pass@example.com/callback', 'live'));
        $this->assertNotNull(ResellerCallbackUrlValidator::failureReason('https://example.com/callback#secret', 'live'));
    }

    public function test_callback_url_allows_public_https_host(): void
    {
        $this->assertNull(ResellerCallbackUrlValidator::failureReason('https://example.com/callback', 'live'));
    }
}
