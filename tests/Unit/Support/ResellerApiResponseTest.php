<?php

namespace Tests\Unit\Support;

use App\Support\ResellerApiResponse;
use Tests\TestCase;

/**
 * Unit tests for ResellerApiResponse — especially the new orderFailed() method
 * and sanitizeProviderReason() sensitive-data sanitization.
 */
class ResellerApiResponseTest extends TestCase
{
    // ── Tests: orderFailed() response structure ───────────────────────────────

    public function test_order_failed_has_correct_http_status(): void
    {
        $response = ResellerApiResponse::orderFailed();

        $this->assertEquals(400, $response->getStatusCode());
    }

    public function test_order_failed_has_error_true(): void
    {
        $data = json_decode(ResellerApiResponse::orderFailed()->getContent(), true);

        $this->assertTrue($data['error']);
    }

    public function test_order_failed_has_order_failed_error_code(): void
    {
        $data = json_decode(ResellerApiResponse::orderFailed()->getContent(), true);

        $this->assertEquals(ResellerApiResponse::ORDER_FAILED, $data['error_code']);
    }

    public function test_order_failed_includes_balance_deducted_false_by_default(): void
    {
        $data = json_decode(ResellerApiResponse::orderFailed()->getContent(), true);

        $this->assertArrayHasKey('data', $data);
        $this->assertFalse($data['data']['balance_deducted'],
            'balance_deducted should be false by default — order never committed');
    }

    public function test_order_failed_includes_can_retry_true_by_default(): void
    {
        $data = json_decode(ResellerApiResponse::orderFailed()->getContent(), true);

        $this->assertTrue($data['data']['can_retry'],
            'can_retry should be true by default — reseller may retry immediately');
    }

    public function test_order_failed_with_custom_balance_deducted_true(): void
    {
        $data = json_decode(
            ResellerApiResponse::orderFailed(balanceDeducted: true)->getContent(),
            true
        );

        $this->assertTrue($data['data']['balance_deducted']);
    }

    public function test_order_failed_with_can_retry_false(): void
    {
        $data = json_decode(
            ResellerApiResponse::orderFailed(canRetry: false)->getContent(),
            true
        );

        $this->assertFalse($data['data']['can_retry']);
    }

    // ── Tests: reason field handling ──────────────────────────────────────────

    public function test_order_failed_without_reason_omits_reason_key(): void
    {
        $data = json_decode(ResellerApiResponse::orderFailed()->getContent(), true);

        $this->assertArrayNotHasKey('reason', $data['data'],
            'reason key should be absent when no reason provided');
    }

    public function test_order_failed_with_safe_reason_includes_it(): void
    {
        $data = json_decode(
            ResellerApiResponse::orderFailed(reason: 'Produk tidak tersedia')->getContent(),
            true
        );

        $this->assertArrayHasKey('reason', $data['data']);
        $this->assertEquals('Produk tidak tersedia', $data['data']['reason']);
    }

    public function test_order_failed_with_empty_reason_omits_reason_key(): void
    {
        $data = json_decode(
            ResellerApiResponse::orderFailed(reason: '')->getContent(),
            true
        );

        $this->assertArrayNotHasKey('reason', $data['data']);
    }

    // ── Tests: sensitive reason sanitization ──────────────────────────────────

    /** @dataProvider sensitiveReasonProvider */
    public function test_sensitive_reason_is_sanitized(string $sensitiveReason): void
    {
        $data = json_decode(
            ResellerApiResponse::orderFailed(reason: $sensitiveReason)->getContent(),
            true
        );

        $this->assertArrayHasKey('reason', $data['data'],
            'reason should still be present but sanitized');

        $this->assertStringNotContainsStringIgnoringCase(
            'api_key',
            $data['data']['reason'],
            'Sanitized reason must not contain sensitive keyword'
        );
        $this->assertStringNotContainsStringIgnoringCase(
            'secret',
            $data['data']['reason'],
            'Sanitized reason must not contain sensitive keyword'
        );
    }

    public static function sensitiveReasonProvider(): array
    {
        return [
            'contains api_key'       => ['Invalid api_key provided'],
            'contains secret'        => ['Wrong secret key used'],
            'contains token'         => ['Bearer token invalid'],
            'contains password'      => ['Wrong password for provider'],
            'contains credential'    => ['Bad credential configuration'],
            'contains authorization' => ['Invalid authorization header'],
        ];
    }

    public function test_safe_reason_is_not_sanitized(): void
    {
        $safeReason = 'Nomor tujuan tidak ditemukan di database provider';

        $data = json_decode(
            ResellerApiResponse::orderFailed(reason: $safeReason)->getContent(),
            true
        );

        $this->assertEquals($safeReason, $data['data']['reason'],
            'Safe reason should pass through unmodified');
    }

    public function test_long_reason_is_truncated_to_200_chars(): void
    {
        $longReason = str_repeat('x', 300);

        $data = json_decode(
            ResellerApiResponse::orderFailed(reason: $longReason)->getContent(),
            true
        );

        $this->assertLessThanOrEqual(200, mb_strlen($data['data']['reason']),
            'Reason should be truncated to max 200 characters');
    }

    // ── Tests: backward compat — error() method unchanged ────────────────────

    public function test_regular_error_method_still_works_as_before(): void
    {
        $response = ResellerApiResponse::error(
            'Code Not Found',
            ResellerApiResponse::CODE_NOT_FOUND,
            404
        );

        $data = json_decode($response->getContent(), true);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertTrue($data['error']);
        $this->assertEquals(ResellerApiResponse::CODE_NOT_FOUND, $data['error_code']);
        $this->assertArrayNotHasKey('data', $data,
            'Regular error() should not have data block');
    }
}
