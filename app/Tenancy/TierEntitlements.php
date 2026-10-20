<?php

namespace App\Tenancy;

use App\Models\Tenant;
use App\Models\TierPricing;
use Illuminate\Support\Facades\Log;

/**
 * Hak fitur per tier paket.
 *
 * Sumber kebenarannya kolom `tier_pricing.features` (JSON pilihan admin) plus
 * peta default di bawah untuk kunci yang belum pernah diatur. Admin bisa
 * mengubah isi JSON dari panel tanpa deploy.
 *
 * ATURAN UTAMA: fail-closed.
 *
 * Fitur yang tidak dikenal dianggap TIDAK dimiliki. Kebalikannya berbahaya:
 * salah tulis nama kunci di JSON akan otomatis membuka fitur berbayar untuk
 * semua tier, dan itu tidak akan terlihat sampai ada yang memakainya.
 *
 * Tapi fail-closed juga tidak boleh mengunci platform yang sedang berjalan.
 * Karena itu kunci yang belum pernah diatur sama sekali memakai peta DEFAULT di
 * bawah — bukan otomatis "tidak boleh". Yang dianggap tidak dimiliki adalah
 * kunci yang memang tidak ada di peta itu.
 */
class TierEntitlements
{
    /**
     * Fitur bawaan per tier.
     *
     * Ini titik awal, bukan harga mati: daftar yang tersimpan di
     * `tier_pricing.features` (diatur admin) yang menang. Kunci yang tidak
     * disebut di peta ini dianggap tidak dimiliki tier mana pun.
     */
    public const DEFAULTS = [
        'starter' => [
            'subdomain',
            'flash_sale',
            'social_proof',
            'theme_color',
            'payment_methods',
        ],
        'business' => [
            'subdomain',
            'flash_sale',
            'social_proof',
            'theme_color',
            'payment_methods',
            'custom_domain',
            'coupon',
        ],
        'enterprise' => [
            'subdomain',
            'flash_sale',
            'social_proof',
            'theme_color',
            'payment_methods',
            'custom_domain',
            'coupon',
            'all_themes',
            'api_report',
        ],
    ];

    /** @var array<int, array<string>> */
    private array $cache = [];

    public function has(?Tenant $tenant, string $feature): bool
    {
        return in_array($feature, $this->featuresFor($tenant), true);
    }

    /**
     * @return array<string>
     */
    public function featuresFor(?Tenant $tenant): array
    {
        $tier = app(TierPricingResolver::class)->normalizeTier($tenant?->tier);

        if (isset($this->cache[$tier])) {
            return $this->cache[$tier];
        }

        return $this->cache[$tier] = $this->resolveFor($tier);
    }

    /**
     * @return array<string>
     */
    private function resolveFor(string $tier): array
    {
        $row = app(TierPricingResolver::class)->forTier($tier);
        $stored = is_array($row?->features) ? $row->features : [];

        // JSON dari panel boleh berisi label tampilan ("Domain sendiri") maupun
        // kunci teknis ("custom_domain"). Yang dipakai untuk gate hanya yang
        // bentuknya kunci teknis; sisanya tetap tampil di kartu harga.
        $keys = array_values(array_filter($stored, fn ($item) => is_string($item) && $this->isFeatureKey($item)));

        if ($keys === []) {
            // Belum diatur admin → pakai peta default, jangan kosong.
            $keys = self::DEFAULTS[$tier] ?? [];
        }

        return array_values(array_unique($keys));
    }

    /**
     * Kunci teknis: huruf kecil, angka, garis bawah. Label seperti
     * "Semua fitur Starter" atau "Modal termurah (markup 2%)" tidak lolos.
     */
    private function isFeatureKey(string $value): bool
    {
        return (bool) preg_match('/^[a-z][a-z0-9_]*$/', $value);
    }

    public function flush(): void
    {
        $this->cache = [];
    }

    /**
     * Gate untuk dipakai di controller: fail-closed dengan pesan yang jelas.
     *
     * Dipakai sebagai `app(TierEntitlements::class)->authorize($tenant, 'custom_domain')`
     * sehingga penyembunyian menu di UI bukan satu-satunya pertahanan.
     */
    public function authorize(?Tenant $tenant, string $feature): void
    {
        if ($this->has($tenant, $feature)) {
            return;
        }

        Log::info('tenant ditolak memakai fitur yang tidak termasuk paketnya', [
            'tenant_id' => $tenant?->id,
            'tier' => $tenant?->tier,
            'feature' => $feature,
        ]);

        abort(403, 'Fitur ini tidak termasuk paket langganan toko Anda.');
    }
}
