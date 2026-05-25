<?php

namespace App\Support;

use DomainException;

final class ResellerCallbackUrlValidator
{
    public static function assertAllowed(?string $url): void
    {
        $reason = self::failureReason($url);

        if ($reason !== null) {
            throw new DomainException($reason);
        }
    }

    public static function failureReason(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return 'Callback URL wajib diisi.';
        }

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return 'Callback URL tidak valid.';
        }

        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));

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
