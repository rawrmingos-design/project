<?php

namespace App\Support;

class DuitkuPaymentChannels
{
    private const DIRECT_CODES = [
        'SP', 'LQ', 'NQ', 'GQ', 'SQ',
        'BT', 'B1', 'A1', 'M2', 'BC', 'BR', 'NC', 'BV', 'VA', 'I1', 'S1', 'DM',
        'FT', 'IR',
    ];

    private const VIRTUAL_ACCOUNT_CODES = [
        'BT', 'B1', 'A1', 'M2', 'BC', 'BR', 'NC', 'BV', 'VA', 'I1', 'S1', 'DM',
    ];

    public static function normalize(?string $code): string
    {
        return strtoupper(trim((string) $code));
    }

    public static function isDirect(?string $code): bool
    {
        return in_array(self::normalize($code), self::DIRECT_CODES, true);
    }

    public static function isVirtualAccount(?string $code): bool
    {
        return in_array(self::normalize($code), self::VIRTUAL_ACCOUNT_CODES, true);
    }

    public static function apiMode(?string $code): string
    {
        return self::isDirect($code) ? 'direct' : 'pop';
    }

    public static function directCodes(): array
    {
        return self::DIRECT_CODES;
    }

    public static function virtualAccountCodes(): array
    {
        return self::VIRTUAL_ACCOUNT_CODES;
    }
}
