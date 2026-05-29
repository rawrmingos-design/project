<?php

namespace App\Support;

use DomainException;

final class ResellerCallbackUrlValidator
{
    public static function assertAllowed(?string $url, ?string $mode = 'live'): void
    {
        $reason = self::failureReason($url, $mode);

        if ($reason !== null) {
            throw new DomainException($reason);
        }
    }

    public static function failureReason(?string $url, ?string $mode = 'live'): ?string
    {
        $url = trim((string) $url);
        $mode = strtolower(trim((string) $mode)) === 'sandbox' ? 'sandbox' : 'live';

        if ($url === '') {
            return 'Callback URL wajib diisi.';
        }

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return 'Callback URL tidak valid.';
        }

        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));

        if ($mode === 'sandbox') {
            if (! in_array($scheme, ['http', 'https'], true)) {
                return 'Sandbox callback URL harus menggunakan HTTP atau HTTPS.';
            }

            return null;
        }

        if ($scheme !== 'https') {
            return 'Live callback URL harus menggunakan HTTPS.';
        }

        if ($host === '' || $host === 'localhost') {
            return 'Live callback URL harus memakai host publik, bukan localhost.';
        }

        foreach (['.local', '.internal', '.test', '.localhost'] as $suffix) {
            if (str_ends_with($host, $suffix)) {
                return 'Live callback URL tidak boleh memakai host internal/non-publik.';
            }
        }

        if (! str_contains($host, '.') && ! filter_var($host, FILTER_VALIDATE_IP)) {
            return 'Live callback URL harus memakai host publik yang valid.';
        }

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            $isPublic = filter_var(
                $host,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            ) !== false;

            if (! $isPublic) {
                return 'Live callback URL tidak boleh mengarah ke IP private, loopback, atau reserved.';
            }
        }

        return null;
    }
}
