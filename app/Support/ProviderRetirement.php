<?php

namespace App\Support;

final class ProviderRetirement
{
    public static function retiredCodes(): array
    {
        return array_values(array_unique(array_map(
            static fn (mixed $code): string => self::normalizeCode($code),
            config('providers.retirement.retired_codes', []),
        )));
    }

    public static function retainedExternalCodes(): array
    {
        return array_values(array_unique(array_map(
            static fn (mixed $code): string => self::normalizeCode($code),
            config('providers.retirement.retained_external_codes', []),
        )));
    }

    public static function internalCodes(): array
    {
        return array_values(array_unique(array_map(
            static fn (mixed $code): string => self::normalizeCode($code),
            config('providers.retirement.internal_codes', []),
        )));
    }

    public static function isRetired(mixed $code): bool
    {
        return in_array(self::normalizeCode($code), self::retiredCodes(), true);
    }

    public static function isInternal(mixed $code): bool
    {
        return in_array(self::normalizeCode($code), self::internalCodes(), true);
    }

    public static function canonicalCode(mixed $code): string
    {
        $code = self::normalizeCode($code);

        return $code === 'vip_reseller' ? 'vip' : $code;
    }

    public static function normalizeCode(mixed $code): string
    {
        return strtolower(trim((string) $code));
    }
}
