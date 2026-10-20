<?php

namespace App\Tenancy;

use App\Models\Tenant;
use App\Models\TierPricing;
use Illuminate\Support\Facades\Log;

/**
 * Sumber tunggal konfigurasi paket tenant.
 *
 * Semua angka dibaca dari tabel `tier_pricing` (diatur admin dari panel).
 * Konstanta FALLBACK di bawah hanya jaring pengaman kalau tabel kosong/belum
 * di-seed — bukan sumber kebenaran. Kalau sampai terpakai, dicatat ke log
 * supaya kelihatan ada masalah data.
 */
class TierPricingResolver
{
    /** Fallback terakhir. Jangan dijadikan acuan bisnis. */
    public const FALLBACK = [
        'starter' => [
            'platform_markup_percent' => 4.0,
            'tenant_markup_default' => 8.0,
            'price_3_month' => 330000,
            'price_12_month' => 800000,
            'strike_discount_3_month' => 20.0,
            'strike_discount_12_month' => 40.0,
        ],
        'business' => [
            'platform_markup_percent' => 3.0,
            'tenant_markup_default' => 8.0,
            'price_3_month' => 435000,
            'price_12_month' => 898000,
            'strike_discount_3_month' => 20.0,
            'strike_discount_12_month' => 40.0,
        ],
        'enterprise' => [
            'platform_markup_percent' => 2.0,
            'tenant_markup_default' => 8.0,
            'price_3_month' => 537000,
            'price_12_month' => 989000,
            'strike_discount_3_month' => 20.0,
            'strike_discount_12_month' => 40.0,
        ],
    ];

    /** @var array<string, TierPricing|false> */
    private array $cache = [];

    public function forTier(?string $tier): ?TierPricing
    {
        $tier = $this->normalizeTier($tier);

        if (! array_key_exists($tier, $this->cache)) {
            $this->cache[$tier] = TierPricing::query()->where('tier', $tier)->first() ?? false;
        }

        $row = $this->cache[$tier];

        return $row === false ? null : $row;
    }

    public function for(?Tenant $tenant): ?TierPricing
    {
        return $this->forTier($tenant?->tier);
    }

    public function platformMarkupPercent(?Tenant $tenant): float
    {
        $row = $this->for($tenant);

        if ($row !== null) {
            return (float) $row->platform_markup_percent;
        }

        $this->warnMissing($tenant);

        return (float) (self::FALLBACK[$this->normalizeTier($tenant?->tier)]['platform_markup_percent'] ?? 0);
    }

    public function tenantMarkupDefault(?Tenant $tenant): float
    {
        $row = $this->for($tenant);

        if ($row !== null) {
            return (float) $row->tenant_markup_default;
        }

        return (float) (self::FALLBACK[$this->normalizeTier($tenant?->tier)]['tenant_markup_default'] ?? 8);
    }

    public function priceFor(?Tenant $tenant, int $months): int
    {
        $row = $this->for($tenant);

        if ($row !== null) {
            return $row->priceForDuration($months);
        }

        return (int) (self::FALLBACK[$this->normalizeTier($tenant?->tier)]['price_'.$months.'_month'] ?? 0);
    }

    /** Daftar paket aktif untuk halaman pendaftaran/kartu harga. */
    public function activeTiers(): \Illuminate\Support\Collection
    {
        $rows = TierPricing::query()->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get();

        if ($rows->isNotEmpty()) {
            return $rows;
        }

        // Fallback: bangun objek dari konstanta supaya halaman tetap render.
        return collect(self::FALLBACK)->map(function (array $values, string $tier) {
            return new TierPricing(array_merge($values, [
                'tier' => $tier,
                'label' => ucfirst($tier),
                'is_active' => true,
            ]));
        })->values();
    }

    public function flush(): void
    {
        $this->cache = [];
    }

    public function normalizeTier(?string $tier): string
    {
        $tier = strtolower(trim((string) $tier));

        return in_array($tier, TierPricing::tierNames(), true)
            ? $tier
            : TierPricing::TIER_STARTER;
    }

    private function warnMissing(?Tenant $tenant): void
    {
        Log::warning('tier_pricing kosong, memakai nilai fallback', [
            'tenant_id' => $tenant?->id,
            'tier' => $tenant?->tier,
        ]);
    }
}
