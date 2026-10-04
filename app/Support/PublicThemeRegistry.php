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
    public const PREVIEW_ONLY_THEMES = [];

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

    /**
     * SATU-SATUNYA gerbang keputusan renderer.
     *
     * `true`  = render halaman dengan legacy Blade (`resources/views/template/...`)
     * `false` = render halaman dengan Inertia (React)
     *
     * Semua controller publik WAJIB memakai ini, bukan membandingkan nama theme
     * sendiri-sendiri. Sebelumnya `LoginController`/`RegisterController` hanya
     * mengenali `istanatopup`, sehingga theme `bangjeff` membuat halaman
     * login & daftar keluar dari tema (Blade) sementara halaman lain Inertia.
     */
    public static function rendersLegacyBlade(?string $theme): bool
    {
        return self::resolveForEnvironment($theme) === self::DEFAULT;
    }

    /**
     * Theme efektif dari `setting_webs`, sudah dinormalkan untuk environment ini.
     * Dipakai agar tidak ada lagi pembacaan `SettingWeb::value('public_theme')`
     * mentah yang bisa berbeda antar halaman.
     */
    public static function activeForEnvironment(): string
    {
        try {
            $theme = \App\Models\SettingWeb::query()->value('public_theme');
        } catch (\Throwable) {
            // Skema belum siap / tabel belum ada → anggap default.
            $theme = null;
        }

        return self::resolveForEnvironment($theme);
    }
}
