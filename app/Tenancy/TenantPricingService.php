<?php

namespace App\Tenancy;

use App\Models\Layanan;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;

/**
 * Harga jual tenant.
 *
 * Rumus (mengikuti pola `profit_member` / `profit_gold` yang sudah ada):
 *
 *   harga pokok    = layanans.harga                              ← anchor modal platform
 *   modal_tenant   = harga × (1 + platform_markup[tier] / 100)   ← harga beli tenant
 *   harga_jual     = harga × (1 + tenant_markup / 100)           ← harga ke customer
 *   profit_tenant  = harga_jual − modal_tenant
 *
 * Harga jual customer sengaja TIDAK memakai platform markup. Kalau dipakai,
 * customer tier enterprise jadi lebih mahal dari starter dan janji "tier tinggi =
 * untung lebih besar" jadi bohong. Yang berbeda antar tier adalah titik modal
 * tenant, bukan harga customer.
 *
 * Syarat: tenant_markup > platform_markup (kalau tidak, profit tenant ≤ 0).
 */
class TenantPricingService
{
    public function __construct(private readonly TierPricingResolver $tiers) {}

    /**
     * @return array{
     *     modal: int, modal_tenant: int, markup: int, profit: int,
     *     sell_price: int, platform_markup_percent: float, tenant_markup_percent: float
     * }
     */
    public function forLayanan(Layanan $layanan, ?Tenant $tenant = null, int $quantity = 1): array
    {
        $tenant ??= app(TenantContext::class)->get();
        $quantity = max(1, $quantity);

        $harga = max(0, (int) round((float) ($layanan->harga ?? 0)));

        if ($harga <= 0) {
            Log::warning('layanan.harga kosong, harga tenant tidak bisa dihitung', [
                'layanan_id' => $layanan->id,
                'tenant_id' => $tenant?->id,
            ]);
        }

        $platformMarkup = $this->tiers->platformMarkupPercent($tenant);

        $modalPlatform = $harga * $quantity;
        $modalTenant = (int) round($modalPlatform * (1 + ($platformMarkup / 100)));

        [$sellPrice, $tenantMarkupPercent] = $this->sellPrice($tenant, $modalPlatform, $modalTenant, $platformMarkup);

        return [
            'modal' => $modalPlatform,
            'modal_tenant' => $modalTenant,
            'markup' => $sellPrice - $modalTenant,
            'profit' => $sellPrice - $modalTenant,
            'sell_price' => max($modalTenant, $sellPrice),
            'platform_markup_percent' => $platformMarkup,
            'tenant_markup_percent' => $tenantMarkupPercent,
        ];
    }

    public function applyToLayanan(Layanan $layanan, ?Tenant $tenant = null, int $quantity = 1): Layanan
    {
        $price = $this->forLayanan($layanan, $tenant, $quantity);

        $layanan->harga = $price['sell_price'];
        $layanan->modal_harga = $price['modal_tenant'];
        $layanan->profit = $price['profit'];
        $layanan->tenant_modal_harga = $price['modal_tenant'];
        $layanan->tenant_profit = $price['profit'];

        return $layanan;
    }

    /**
     * Harga jual customer + persen markup efektif.
     *
     * `percent` → harga pokok × (1 + markup/100). Markup dijamin di atas markup
     *             platform, kalau tidak tenant menjual di bawah modalnya sendiri.
     * `fixed`   → modal tenant + nominal tetap (tidak dikali quantity), sama
     *             seperti perilaku lama.
     *
     * @return array{0: int, 1: float}
     */
    public function sellPrice(?Tenant $tenant, int $modalPlatform, int $modalTenant, ?float $platformMarkup = null): array
    {
        $platformMarkup ??= $this->tiers->platformMarkupPercent($tenant);
        $config = is_array($tenant?->margin_config) ? $tenant->margin_config : [];
        $type = strtolower(trim((string) ($config['markup_type'] ?? 'percent')));

        if ($type === 'fixed') {
            $fixed = max(0, (int) round((float) ($config['markup_value'] ?? 0)));
            $sell = $modalTenant + $fixed;
            $effective = $modalPlatform > 0 ? ((($sell - $modalTenant) / $modalPlatform) * 100) : 0.0;

            return [$sell, round($effective, 2)];
        }

        $percent = array_key_exists('markup_value', $config)
            ? (float) $config['markup_value']
            : $this->tiers->tenantMarkupDefault($tenant);

        $percent = max(0.0, $percent);

        if ($percent <= $platformMarkup) {
            Log::warning('markup tenant ≤ markup platform, dinaikkan ke batas minimum', [
                'tenant_id' => $tenant?->id,
                'tenant_markup' => $percent,
                'platform_markup' => $platformMarkup,
            ]);

            $percent = $platformMarkup + 0.01;
        }

        return [(int) round($modalPlatform * (1 + ($percent / 100))), round($percent, 2)];
    }

    /**
     * Lantai harga: harga jual platform untuk produk ini (harga beli tenant).
     *
     * `null` kalau tidak sedang di toko tenant — di situs platform tidak ada
     * lantai, karena diskon di sana adalah uang platform sendiri.
     */
    public function priceFloor(Layanan $layanan, int $quantity = 1): ?int
    {
        $tenant = app(TenantContext::class)->get();

        if ($tenant === null) {
            return null;
        }

        // `applyToLayanan()` menimpa `layanan.harga` dengan HARGA JUAL tenant.
        // Kalau nilai itu dipakai di sini, markup platform terhitung dua kali
        // (110.000 × 1,03 = 113.300) dan lantainya jadi terlalu tinggi. Kalau
        // `tenant_modal_harga` sudah ada, itu modal tenant yang benar — sama
        // yang dipakai jalur web di `applyTenantMarginAfterDiscount()`.
        if (is_numeric($layanan->tenant_modal_harga ?? null)) {
            return (int) round((float) $layanan->tenant_modal_harga * max(1, $quantity));
        }

        $harga = max(0, (int) round((float) ($layanan->harga ?? 0)));
        $platformMarkup = $this->tiers->platformMarkupPercent($tenant);

        return (int) round(($harga * max(1, $quantity)) * (1 + ($platformMarkup / 100)));
    }

    /**
     * Pesan penolakan kalau harga setelah diskon jatuh di bawah lantai, atau
     * `null` kalau masih aman.
     *
     * Dipusatkan supaya website, bot Telegram/WhatsApp, gateway, dan API
     * reseller memberi jawaban yang sama. Sebelum ini hanya website yang
     * memeriksanya (`applyTenantMarginAfterDiscount()`), jadi kupon yang sama
     * bisa lolos di bot dan membuat toko menjual di bawah harga belinya.
     */
    public function floorViolation(Layanan $layanan, int $amountAfterDiscount, int $quantity = 1): ?string
    {
        $floor = $this->priceFloor($layanan, $quantity);

        if ($floor === null || $amountAfterDiscount >= $floor) {
            return null;
        }

        return sprintf(
            'Harga setelah diskon (Rp %s) di bawah harga jual platform (Rp %s). Kurangi diskon voucher atau naikkan markup toko.',
            number_format($amountAfterDiscount, 0, ',', '.'),
            number_format($floor, 0, ',', '.'),
        );
    }

    /** Markup jual efektif (persen) untuk halaman pengaturan tenant. */
    public function effectiveMarkupPercent(?Tenant $tenant, int $modalPlatform = 10000): float
    {
        $platformMarkup = $this->tiers->platformMarkupPercent($tenant);
        $modalTenant = (int) round($modalPlatform * (1 + ($platformMarkup / 100)));

        return $this->sellPrice($tenant, $modalPlatform, $modalTenant, $platformMarkup)[1];
    }
}
