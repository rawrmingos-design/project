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

    public function test_sandbox_callback_url_allows_localhost_targets(): void
    {
        $this->assertNull(
            ResellerCallbackUrlValidator::failureReason('http://localhost/callback', 'sandbox')
        );
    }
}
