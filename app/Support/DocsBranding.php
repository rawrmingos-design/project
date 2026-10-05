<?php

namespace App\Support;

use App\Models\SettingWeb;
use App\Services\PublicSiteConfigService;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Merek (logo + favicon) dokumentasi API.
 *
 * Hasil build Docusaurus bersifat statis: logo navbar dan favicon dibakar saat
 * `npm run build`, jadi tidak bisa ikut Settings Web kalau tidak dibangun ulang.
 *
 * Supaya logo bisa diganti dari Settings Web (kolom `logo_header` & `logo_favicon` di
 * panel admin) tanpa rebuild, token di HTML yang sudah dibangun ditulis ulang SAAT
 * DISAJIKAN — lihat `rewriteHtml()` soal batas penggantiannya.
 */
class DocsBranding
{
    /**
     * Token hasil build Docusaurus → diganti URL runtime.
     *
     * Nilai ini harus tepat sama dengan yang dihasilkan build. Verifikasi atas build nyata
     * menunjukkan token selalu TANPA kutip (`rel=icon href=/img/favicon.ico`,
     * `src=/img/logo.svg`), tapi gaya berkutip juga ditangani supaya tidak rapuh terhadap
     * perubahan Docusaurus atau minifier.
     */
    private const LOGO_TOKEN = '/img/logo.svg';
    private const FAVICON_TOKEN = '/img/favicon.ico';

    /**
     * Seluruh nilai penggantian token.
     *
     * @return array<string, string>
     */
    private function replacements(): array
    {
        return array_filter([
            self::LOGO_TOKEN => $this->brandingAssetUrl('logo_header'),
            self::FAVICON_TOKEN => $this->brandingAssetUrl('logo_favicon'),
        ], static fn (string $url): bool => $url !== '');
    }

    /**
     * Tulis ulang token merek di HTML yang sudah dibangun.
     *
     * Hanya nilai atribut `src`/`href` yang menunjuk PERSIS ke token yang diganti. Alasannya:
     * token yang sama (`/img/logo.svg`, `/img/favicon.ico`) juga muncul di dalam payload React
     * di `<script>` inline. Kalau penggantian dilakukan bebas (`str_replace`), payload itu tetap
     * membawa URL lama dan React akan mengembalikannya saat hidrasi — logo settings berkedip
     * balik ke logo bawaan. Verifikasi build menunjukkan token merek tidak pernah muncul di
     * dalam `<script>`, jadi pembatasan ke atribut ini cukup dan payload tidak perlu disentuh.
     */
    public function rewriteHtml(string $html): string
    {
        $escaped = array_map(
            fn (string $url): string => $this->escapeAttribute($url),
            $this->replacements()
        );

        foreach ($escaped as $token => $url) {
            $pattern = preg_quote($token, '#');

            // Ber-kutip (ganda atau tunggal). Dikerjakan lebih dulu supaya atribut berkutip
            // tidak ikut kena pola tanpa-kutip di bawah.
            $html = (string) preg_replace_callback(
                '#(src|href)=(["\'])' . $pattern . '\2#i',
                static fn (array $m): string => $m[1] . '=' . $m[2] . $url . $m[2],
                $html
            );

            // Tanpa kutip — gaya Docusaurus. `(?<![\w./-])` mencegah cocok di tengah token
            // lain (mis. `/img/logo.svg.map`), dan lookahead menutup akhir atribut.
            $html = (string) preg_replace_callback(
                '#(?<![\w./-])(src|href)=' . $pattern . '(?=[\s>/]|$)#i',
                static fn (array $m): string => $m[1] . '=' . $url,
                $html
            );
        }

        return $html;
    }

    /**
     * URL logo/favicon dari Settings Web, dinormalkan ke bentuk absolut agar bekerja dari
     * host dokumentasi (host berbeda, jadi path relatif akan menunjuk ke host docs).
     *
     * `PublicSiteConfigService::normalizeAssetPath()` dipakai supaya aturan path-nya persis
     * sama dengan yang dipakai website utama (path relatif → `/...`, URL absolut dibiarkan,
     * fallback saat kosong).
     */
    private function brandingAssetUrl(string $column): string
    {
        $fallback = '/assets/logo/favicon.webp';

        try {
            if (! Schema::hasTable('setting_webs') || ! Schema::hasColumn('setting_webs', $column)) {
                return $this->absolute($fallback);
            }

            $value = SettingWeb::query()->value($column);
        } catch (Throwable) {
            return $this->absolute($fallback);
        }

        return $this->absolute(
            app(PublicSiteConfigService::class)->normalizeAssetPath(
                is_string($value) ? $value : null,
                $fallback
            )
        );
    }

    /**
     * Jadikan path relatif sebagai URL absolut. URL yang sudah absolut dibiarkan.
     */
    private function absolute(string $path): string
    {
        if ($path === '') {
            return '';
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $base = rtrim(trim((string) config('app.url', '')), '/');

        return $base === '' ? '/' . ltrim($path, '/') : $base . '/' . ltrim($path, '/');
    }

    private function escapeAttribute(string $url): string
    {
        return htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    }
}
