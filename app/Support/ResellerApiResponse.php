<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

final class ResellerApiResponse
{
    public const ACCESS_TOKEN_REQUIRED = 'ACCESS_TOKEN_REQUIRED';
    public const INVALID_TOKEN = 'INVALID_TOKEN';
    public const VALIDATION_FAILED = 'VALIDATION_FAILED';
    public const INVALID_JSON_PAYLOAD = 'INVALID_JSON_PAYLOAD';
    public const CODE_NOT_FOUND = 'CODE_NOT_FOUND';
    public const INSUFFICIENT_BALANCE = 'INSUFFICIENT_BALANCE';
    public const ORDER_FAILED = 'ORDER_FAILED';
    public const INVOICE_NOT_FOUND = 'INVOICE_NOT_FOUND';
    public const INTEGRATION_CODE_REQUIRED = 'INTEGRATION_CODE_REQUIRED';
    public const INVALID_INTEGRATION_CODE = 'INVALID_INTEGRATION_CODE';
    public const TOO_MANY_REQUESTS = 'TOO_MANY_REQUESTS';
    public const IP_WHITELIST_EMPTY = 'IP_WHITELIST_EMPTY';
    public const IP_NOT_WHITELISTED = 'IP_NOT_WHITELISTED';

    /**
     * @param array<string, mixed>|null $details
     */
    public static function error(string $message, string $errorCode, int $httpStatus, ?array $details = null): JsonResponse
    {
        $payload = [
            'error' => true,
            'code' => $httpStatus,
            'message' => $message,
            'error_code' => $errorCode,
        ];

        if ($details !== null && $details !== []) {
            $payload['details'] = $details;
        }

        return response()->json($payload, $httpStatus);
    }

    /**
     * Structured response for a failed order.
     *
     * Provides reseller-facing context:
     *   - balance_deducted: apakah saldo sudah terpotong (biasanya false — order gagal sebelum DB commit)
     *   - can_retry: apakah boleh retry dengan referenceNumber yang sama
     *   - reason: penjelasan singkat dari provider (sudah disanitasi, tidak expose internal keys/secrets)
     */
    public static function orderFailed(
        ?string $reason = null,
        bool $balanceDeducted = false,
        bool $canRetry = true,
    ): JsonResponse {
        $payload = [
            'error'      => true,
            'code'       => 400,
            'message'    => 'Order failed',
            'error_code' => self::ORDER_FAILED,
            'data'       => [
                'balance_deducted' => $balanceDeducted,
                'can_retry'        => $canRetry,
            ],
        ];

        $sanitized = self::sanitizeProviderReason($reason);
        if ($sanitized !== null && $sanitized !== '') {
            $payload['data']['reason'] = $sanitized;
        }

        return response()->json($payload, 400);
    }

    /**
     * Sanitize provider error messages before exposing them to resellers.
     *
     * Provider errors may contain internal API keys, secrets, or tokens.
     * We strip those and return a generic message if any sensitive keyword found.
     */
    private static function sanitizeProviderReason(?string $reason): ?string
    {
        if ($reason === null || $reason === '') {
            return null;
        }

        // Keywords that indicate the message contains sensitive internal info
        $sensitivePatterns = [
            '/api[_\-]?key/i',
            '/secret/i',
            '/token/i',
            '/password/i',
            '/credential/i',
            '/authorization/i',
        ];

        foreach ($sensitivePatterns as $pattern) {
            if (preg_match($pattern, $reason)) {
                return 'Provider returned an error. Please contact support if this persists.';
            }
        }

        // Truncate long messages to prevent verbose provider stack traces leaking
        return mb_substr(trim($reason), 0, 200);
    }

    /**
     * @param array<string, string|string[]> $headers
     */
    public static function tooManyRequests(int $retryAfterSeconds, array $headers = []): JsonResponse
    {
        return response()->json([
            'error' => true,
            'code' => 429,
            'message' => 'Too Many Requests',
            'error_code' => self::TOO_MANY_REQUESTS,
            'retryAfterSeconds' => $retryAfterSeconds,
        ], 429, $headers);
    }
}
