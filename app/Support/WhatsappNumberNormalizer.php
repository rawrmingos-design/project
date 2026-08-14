<?php

namespace App\Support;

final class WhatsappNumberNormalizer
{
    public const COUNTRY_CODE = '62';

    private const MIN_LENGTH = 10;

    private const MAX_LENGTH = 15;

    public static function normalize(?string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', trim((string) $value)) ?? '';

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = self::COUNTRY_CODE . substr($digits, 1);
        }

        if (! str_starts_with($digits, self::COUNTRY_CODE)) {
            return null;
        }

        if (! self::isValid($digits)) {
            return null;
        }

        return $digits;
    }

    public static function isValid(?string $value): bool
    {
        $value = (string) $value;

        return preg_match('/^' . self::COUNTRY_CODE . '[0-9]{' . (self::MIN_LENGTH - 2) . ',' . (self::MAX_LENGTH - 2) . '}$/', $value) === 1;
    }
}
