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

        if ($url === '' || preg_match('/[\x00-\x1F\x7F]/', $url)) {
            return 'Callback URL tidak valid.';
        }

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return 'Callback URL tidak valid.';
        }

        $parts = parse_url($url);
        if ($parts === false) {
            return 'Callback URL tidak valid.';
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower(rtrim((string) ($parts['host'] ?? ''), '.'));
        $port = $parts['port'] ?? null;

        if (! in_array($scheme, $mode === 'live' ? ['https'] : ['http', 'https'], true)) {
            return $mode === 'live'
                ? 'Live callback URL harus menggunakan HTTPS.'
                : 'Sandbox callback URL harus menggunakan HTTP atau HTTPS.';
        }

        if ($host === '' || isset($parts['user']) || isset($parts['pass']) || isset($parts['fragment'])) {
            return 'Callback URL tidak valid.';
        }

        if ($port !== null && ! in_array((int) $port, [80, 443], true)) {
            return 'Callback URL menggunakan port yang tidak didukung.';
        }

        if ($host === 'localhost' || str_ends_with($host, '.localhost')) {
            return 'Callback URL tidak boleh mengarah ke localhost.';
        }

        foreach (['.local', '.internal', '.intranet', '.test', '.home.arpa'] as $suffix) {
            if ($host === trim($suffix, '.') || str_ends_with($host, $suffix)) {
                return 'Callback URL tidak boleh memakai host internal/non-publik.';
            }
        }

        $ip = filter_var($host, FILTER_VALIDATE_IP);
        if ($ip !== false) {
            if (! self::isPublicIp($ip)) {
                return 'Callback URL tidak boleh mengarah ke IP private, loopback, metadata, multicast, atau reserved.';
            }

            return null;
        }

        if (! str_contains($host, '.') || ! preg_match('/^[a-z0-9.-]+$/', $host)) {
            return 'Callback URL harus memakai host publik yang valid.';
        }

        return null;
    }

    public static function isPublicIp(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }

        $packed = @inet_pton($ip);
        if ($packed === false) {
            return false;
        }

        if (strlen($packed) === 4) {
            $first = ord($packed[0]);
            return $first < 224;
        }

        if (strlen($packed) === 16) {
            $first = ord($packed[0]);
            return $first < 224;
        }

        return false;
    }
}
