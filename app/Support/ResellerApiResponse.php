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
