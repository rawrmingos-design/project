<?php

namespace App\Support;

final class IpAddressMatcher
{
    public static function normalize(?string $ip): ?string
    {
        $ip = trim((string) $ip);

        if ($ip === '') {
            return null;
        }

        $packed = @inet_pton($ip);

        if ($packed === false) {
            return null;
        }

        return inet_ntop($packed) ?: null;
    }

    public static function matches(?string $candidateIp, string $allowedValue): bool
    {
        $candidateIp = static::normalize($candidateIp);

        if ($candidateIp === null) {
            return false;
        }

        $allowedValue = trim($allowedValue);

        if ($allowedValue === '') {
            return false;
        }

        if (str_contains($allowedValue, '/')) {
            return static::matchesCidr($candidateIp, $allowedValue);
        }

        return hash_equals($candidateIp, static::normalize($allowedValue) ?? '');
    }

    private static function matchesCidr(string $candidateIp, string $cidr): bool
    {
        [$subnet, $prefixLength] = array_pad(explode('/', $cidr, 2), 2, null);

        $subnet = static::normalize($subnet);

        if ($subnet === null || $prefixLength === null || $prefixLength === '') {
            return false;
        }

        $candidateBytes = @inet_pton($candidateIp);
        $subnetBytes = @inet_pton($subnet);

        if ($candidateBytes === false || $subnetBytes === false || strlen($candidateBytes) !== strlen($subnetBytes)) {
            return false;
        }

        $prefix = (int) $prefixLength;
        $maxBits = strlen($candidateBytes) * 8;

        if ($prefix < 0 || $prefix > $maxBits) {
            return false;
        }

        $fullBytes = intdiv($prefix, 8);
        $remainingBits = $prefix % 8;

        if ($fullBytes > 0 && substr($candidateBytes, 0, $fullBytes) !== substr($subnetBytes, 0, $fullBytes)) {
            return false;
        }

        if ($remainingBits === 0) {
            return true;
        }

        $mask = (0xFF << (8 - $remainingBits)) & 0xFF;

        return (ord($candidateBytes[$fullBytes]) & $mask) === (ord($subnetBytes[$fullBytes]) & $mask);
    }
}
