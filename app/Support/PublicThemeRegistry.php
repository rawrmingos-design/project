<?php

namespace App\Support;

class PublicThemeRegistry
{
    public const DEFAULT = 'default';
    public const BANGJEFF = 'bangjeff';
    public const ISTANATOPUP = 'istanatopup';

    public static function options(): array
    {
        return [
            self::DEFAULT => 'Default (Blade Legacy)',
            self::BANGJEFF => 'Bangjeff (Inertia)',
            self::ISTANATOPUP => 'IstanaTopup (Inertia)',
        ];
    }

    public static function normalize(?string $theme): string
    {
        $theme = strtolower(trim((string) $theme));

        if ($theme === 'modern') {
            $theme = self::BANGJEFF;
        }

        return array_key_exists($theme, self::options())
            ? $theme
            : self::DEFAULT;
    }
}
