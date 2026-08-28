<?php

namespace App\Support;

class PublicThemeRegistry
{
    public const DEFAULT = 'default';
    public const BANGJEFF = 'bangjeff';
    public const ISTANATOPUP = 'istanatopup';

    /**
     * Theme yang masih dalam tahap pengembangan: boleh dipakai untuk
     * preview di staging/local, diblokir saat disimpan dari production.
     */
    public const PREVIEW_ONLY_THEMES = [
        self::ISTANATOPUP,
    ];

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

    /**
     * Apakah theme aktif boleh dipakai di environment ini?
     * Theme preview-only otomatis jatuh ke default saat production.
     */
    public static function resolveForEnvironment(?string $theme): string
    {
        $theme = self::normalize($theme);

        if (
            app()->environment('production') &&
            in_array($theme, self::PREVIEW_ONLY_THEMES, true)
        ) {
            return self::DEFAULT;
        }

        return $theme;
    }
}
