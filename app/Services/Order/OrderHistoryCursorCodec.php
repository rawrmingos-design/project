<?php

namespace App\Services\Order;

use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Support\Carbon;

class OrderHistoryCursorCodec
{
    private const VERSION = 1;

    private const SIGNATURE_BYTES = 16;

    private const MAX_TOKEN_LENGTH = 256;

    /**
     * @param array{created_at: string, id: int|string} $boundary
     */
    public function encode(
        array $boundary,
        string $direction,
        User $user,
        string $source,
    ): string {
        $createdAt = Carbon::parse(
            $boundary['created_at'],
            (string) config('app.timezone'),
        )->format('Y-m-d H:i:s.u');
        $id = (string) $boundary['id'];

        if (! in_array($direction, ['older', 'newer', 'window'], true) || ! ctype_digit($id)) {
            throw new \InvalidArgumentException('Invalid order history cursor boundary.');
        }

        $payload = json_encode([
            'v' => self::VERSION,
            'd' => $direction,
            't' => $createdAt,
            'i' => $id,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $signature = hash_hmac(
            'sha256',
            $this->scope($user, $source) . '|' . $payload,
            (string) config('app.key'),
            true,
        );

        return $this->base64UrlEncode($payload)
            . '.'
            . $this->base64UrlEncode(substr($signature, 0, self::SIGNATURE_BYTES));
    }

    /**
     * @return array{direction: string, created_at: Carbon, id: string}|null
     */
    public function decode(string $token, User $user, string $source): ?array
    {
        $token = trim($token);
        if ($token === '' || strlen($token) > self::MAX_TOKEN_LENGTH || substr_count($token, '.') !== 1) {
            return null;
        }

        [$encodedPayload, $encodedSignature] = explode('.', $token, 2);
        $payload = $this->base64UrlDecode($encodedPayload);
        $signature = $this->base64UrlDecode($encodedSignature);

        if ($payload === null || $signature === null || strlen($signature) !== self::SIGNATURE_BYTES) {
            return null;
        }

        $expected = substr(hash_hmac(
            'sha256',
            $this->scope($user, $source) . '|' . $payload,
            (string) config('app.key'),
            true,
        ), 0, self::SIGNATURE_BYTES);

        if (! hash_equals($expected, $signature)) {
            return null;
        }

        try {
            $data = json_decode($payload, true, 8, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return null;
        }

        if (
            ! is_array($data)
            || ($data['v'] ?? null) !== self::VERSION
            || ! in_array($data['d'] ?? null, ['older', 'newer', 'window'], true)
            || ! is_string($data['t'] ?? null)
            || ! is_string($data['i'] ?? null)
            || ! ctype_digit($data['i'])
        ) {
            return null;
        }

        try {
            $createdAt = Carbon::createFromFormat(
                'Y-m-d H:i:s.u',
                $data['t'],
                (string) config('app.timezone'),
            );
        } catch (\Throwable) {
            return null;
        }

        if (! $createdAt) {
            return null;
        }

        return [
            'direction' => $data['d'],
            'created_at' => $createdAt,
            'id' => $data['i'],
        ];
    }

    private function scope(User $user, string $source): string
    {
        if (! in_array($source, ['whatsapp_gateway', 'telegram_gateway'], true)) {
            throw new \InvalidArgumentException('Unsupported order history cursor source.');
        }

        $tenantId = app(TenantContext::class)->id();
        $tenantScope = $tenantId === null ? 'landlord' : 'tenant:' . $tenantId;

        return implode('|', [
            'order-history-cursor',
            $tenantScope,
            (string) $user->getKey(),
            $source,
        ]);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): ?string
    {
        if ($value === '' || preg_match('/^[A-Za-z0-9_-]+$/', $value) !== 1) {
            return null;
        }

        $padding = (4 - strlen($value) % 4) % 4;
        $decoded = base64_decode(
            strtr($value, '-_', '+/') . str_repeat('=', $padding),
            true,
        );

        if ($decoded === false || $this->base64UrlEncode($decoded) !== $value) {
            return null;
        }

        return $decoded;
    }
}
