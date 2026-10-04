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

    /**
     * Controller publik yang SELALU render Inertia (React) dan TIDAK punya
     * cabang tema, karena belum ada view legacy Blade-nya.
     *
     * KEPUTUSAN PRODUK (Tahap 3): halaman docs & reseller SENGAJA dibiarkan
     * React. Alasannya:
     *   - panel reseller adalah produk terpisah (butuh auth + middleware
     *     `reseller.only`), bukan halaman jualan publik yang ikut tema;
     *   - docs hidup di domain sendiri dan di balik login (`auth.message`),
     *     jadi tidak masuk permukaan storefront;
     *   - `views/docs.blade.php` cuma shell Inertia murni — bukan halaman Blade.
     *
     * Konsekuensinya: saat tema `default` (Blade) aktif, halaman-halaman ini
     * tetap React. Tidak ada tombol "ganti theme" di dalamnya, jadi tidak ada
     * kejutan UX; sementara memaksa mereka ikut tema akan 500 karena view
     * Blade-nya memang tidak ada.
     *
     * Ditegakkan `Tests\Feature\ReactOnlySurfaceRendersInertiaTest` (runtime:
     * wajib Inertia di SETIAP tema + tidak boleh kena redirect 301 middleware)
     * dan `Tests\Feature\PublicThemeSurfaceContractTest` (statik: daftar tidak
     * boleh basi, controller React-only tak terdaftar = CI MERAH).
     *
     * Kalau kelak view Blade-nya dibuat: tulis view-nya, pakai gerbang tema di
     * controller, lalu HAPUS entri dari daftar ini — kedua test itu akan MERAH
     * sampai langkah terakhir dilakukan, jadi keputusan ini tidak bisa basi
     * diam-diam.
     *
     * @var list<class-string>
     */
    public const INERTIA_ONLY_CONTROLLERS = [
        \App\Http\Controllers\Public\DocsController::class,
        \App\Http\Controllers\Public\Reseller\CallbackLogController::class,
        \App\Http\Controllers\Public\Reseller\CredentialController::class,
        \App\Http\Controllers\Public\Reseller\DashboardController::class,
        \App\Http\Controllers\Public\Reseller\DepositHistoryController::class,
        \App\Http\Controllers\Public\Reseller\DocsController::class,
        \App\Http\Controllers\Public\Reseller\OrderLogController::class,
        \App\Http\Controllers\Public\Reseller\RegistryController::class,
        \App\Http\Controllers\Public\Reseller\SalesPageController::class,
        \App\Http\Controllers\Public\Reseller\SandboxController::class,
        \App\Http\Controllers\Public\Reseller\SettingsController::class,
    ];

    /**
     * Apakah controller ini termasuk permukaan React-only yang disengaja?
     */
    public static function isInertiaOnlyController(string $controllerClass): bool
    {
        return in_array(ltrim($controllerClass, '\\'), self::INERTIA_ONLY_CONTROLLERS, true);
    }
}
