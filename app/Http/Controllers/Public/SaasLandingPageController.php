<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\SettingWeb;
use App\Models\TierPricing;
use App\Support\PublicThemeRegistry;
use App\Tenancy\TierPricingResolver;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Landing page SaaS (sewa web top-up).
 *
 * Berbeda dari halaman pendaftaran: ini halaman JUALAN di host platform,
 * bukan di subdomain toko. Karena itu hanya hidup di host platform — daftar
 * host yang dilewati resolusi tenant ada di `ResolvesTenantHost::publicHosts()`,
 * yang mengambil host dari `app.url`. Tidak perlu mengubah middleware.
 *
 * Semua angka di halaman ini berasal dari database:
 *   - nama merek  : setting_webs.judul_web  (sama dengan judul situs publik)
 *   - harga paket : tier_pricing
 * supaya admin bisa mengubah harga/merek tanpa deploy.
 */
class SaasLandingPageController extends Controller
{
    public function __invoke(Request $request, TierPricingResolver $tiers): View
    {
        // Halaman jualan hanya masuk akal kalau SaaS-nya memang dinyalakan.
        abort_if((bool) config('tenancy.disabled', true), 404);

        // Hanya di host platform.
        //
        // Route ini global, sehingga tanpa penjagaan ini halaman jualan
        // platform (lengkap dengan harga paket) ikut bisa dibuka di domain
        // setiap pelanggan — mis. toko-pelanggan.com/sewa-web-topup.
        // `tenant.required` tidak dipakai di route ini, jadi konteks tenant
        // bisa kosong; di situlah halaman ini boleh tampil.
        abort_if(app()->bound(\App\Tenancy\TenantContext::class) && app(\App\Tenancy\TenantContext::class)->get() !== null, 404);

        // Sikap tema, sesuai kontrak PublicThemeSurfaceContractTest.
        //
        // Halaman ini Blade. Tema `istanatopup` adalah aplikasi React yang
        // belum punya padanannya, dan menyajikan Blade di tengah tema React
        // mencampur dua sistem tampilan (dilarang di repo ini). Jadi halaman
        // hanya tersedia di tema yang memang merender Blade.
        $theme = PublicThemeRegistry::activeForEnvironment();

        if (! PublicThemeRegistry::rendersLegacyBlade($theme)) {
            abort(404);
        }

        $tierRows = $tiers->activeTiers()->keyBy('tier');

        $pricing = [];

        foreach (['starter', 'business', 'enterprise'] as $tier) {
            $row = $tierRows->get($tier);

            if ($row === null) {
                continue;
            }

            $pricing[$tier] = [
                'p3' => (int) $row->priceForDuration(3),
                'p12' => (int) $row->priceForDuration(12),
                // Harga coret = harga sebelum diskon, dihitung dari persentase
                // di database lewat model (satu sumber aturan, tidak diduplikasi
                // di controller). Desain memakai angka mati; kalau admin
                // mengubah diskon, angka coret harus ikut berubah.
                'strike3' => (int) $row->strikePriceForDuration(3),
                'strike12' => (int) $row->strikePriceForDuration(12),
                'permonth3' => (int) round($row->pricePerMonthForDuration(3)),
                'permonth12' => (int) round($row->pricePerMonthForDuration(12)),
                'label' => (string) ($row->label ?: ucfirst($tier)),
                'badge' => $row->badge,
                'features' => is_array($row->features) ? $row->features : [],
            ];
        }

        return view('public.saas-landing', [
            'brand' => $this->brandName(),
            'brandSlug' => preg_replace('/[^a-z0-9]+/', '-', strtolower($this->brandName())),
            'contactEmail' => $this->contactEmail(),
            'contactUrl' => $this->contactUrl(),
            'signupUrl' => route('tenant.register.page'),
            'pricing' => $pricing,
            'trialDays' => max(1, min(30, (int) config('tenancy.trial_days', 3))),
            'trialEnabled' => (bool) config('tenancy.trial_enabled', true),
            // Versi aset dari waktu ubah berkas: mengubah CSS/JS membatalkan
            // cache tanpa perlu menaikkan nomor manual.
            'assetVersion' => $this->assetVersion(),
        ]);
    }

    /**
     * Nama merek dari Settings → Informasi Website.
     *
     * `judul_web` berisi nama + tagline (mis. "ISTANATOPUP - TOP UP GAME
     * MURAH & LEGAL 24 JAM"). Untuk logo dan judul halaman, yang dipakai
     * hanya bagian sebelum pemisah pertama.
     */
    private function brandName(): string
    {
        $raw = '';

        try {
            $raw = trim((string) SettingWeb::query()->value('judul_web'));
        } catch (\Throwable) {
            $raw = '';
        }

        if ($raw === '') {
            return (string) config('app.name', 'Top Up Game');
        }

        $name = preg_split('/\s+[-–—|:]\s+/u', $raw)[0] ?? $raw;

        return trim($name) !== '' ? trim($name) : $raw;
    }

    /**
     * Email kontak.
     *
     * `setting_webs` TIDAK punya kolom email. Jadi alamat diambil dari
     * `mail.from.address`, dan kalau itu pun kosong, diturunkan dari host
     * platform — jangan mengarang domain yang tidak dimiliki.
     */
    private function contactEmail(): string
    {
        $email = trim((string) config('mail.from.address', ''));

        if ($email !== '') {
            return $email;
        }

        $host = (string) parse_url((string) config('app.url'), PHP_URL_HOST);

        return 'hello@' . ($host !== '' ? $host : 'example.com');
    }

    /**
     * Tautan kontak memakai WhatsApp dari settings kalau ada, karena itu kanal
     * yang benar-benar dipakai toko ini. Email jadi cadangan.
     */
    private function contactUrl(): string
    {
        try {
            $wa = trim((string) SettingWeb::query()->value('url_wa'));
        } catch (\Throwable) {
            $wa = '';
        }

        return $wa !== '' ? $wa : 'mailto:' . $this->contactEmail();
    }

    private function assetVersion(): string
    {
        $css = public_path('assets/landing/landing.css');

        return is_file($css) ? (string) filemtime($css) : '1';
    }
}
