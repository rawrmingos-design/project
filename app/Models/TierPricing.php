<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Konfigurasi paket tenant (CORE bisnis model).
 *
 * Semua nilai diatur admin platform dari panel Filament. Tidak ada angka yang
 * boleh di-hardcode di service lain — supaya platform bisa dijual dan pembelinya
 * mengubah harga/markup tanpa deploy.
 */
class TierPricing extends Model
{
    protected $table = 'tier_pricing';

    public const TIER_STARTER = 'starter';
    public const TIER_BUSINESS = 'business';
    public const TIER_ENTERPRISE = 'enterprise';

    /** Hanya dua durasi paket yang dikenal sistem. */
    public const DURATIONS = [3, 12];

    protected $fillable = [
        'tier',
        'label',
        'platform_markup_percent',
        'tenant_markup_default',
        'price_3_month',
        'price_12_month',
        'strike_discount_3_month',
        'strike_discount_12_month',
        'domain_tlds',
        'domain_paid_tlds',
        'features',
        'badge',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'platform_markup_percent' => 'float',
        'tenant_markup_default' => 'float',
        'price_3_month' => 'integer',
        'price_12_month' => 'integer',
        'strike_discount_3_month' => 'float',
        'strike_discount_12_month' => 'float',
        'domain_tlds' => 'array',
        'domain_paid_tlds' => 'array',
        'features' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public static function tierNames(): array
    {
        return [self::TIER_STARTER, self::TIER_BUSINESS, self::TIER_ENTERPRISE];
    }

    public function priceForDuration(int $months): int
    {
        $column = 'price_'.$months.'_month';

        return (int) ($this->{$column} ?? 0);
    }

    /**
     * Harga coret untuk ditampilkan (angka "sebelum diskon").
     *
     * Dipakai frontend: strike = price / (1 - percent/100), sehingga potongan
     * terhadap harga coret tepat sebesar `percent`. Dihitung di sini (bukan
     * disimpan) supaya selalu konsisten kalau admin mengubah harga paket.
     */
    public function strikePriceForDuration(int $months): int
    {
        $price = $this->priceForDuration($months);
        $percent = (float) ($this->{'strike_discount_'.$months.'_month'} ?? 0);

        if ($price <= 0 || $percent <= 0 || $percent >= 100) {
            return 0;
        }

        return (int) round($price / (1 - ($percent / 100)));
    }

    public function pricePerMonthForDuration(int $months): int
    {
        $price = $this->priceForDuration($months);

        return $price > 0 && $months > 0 ? (int) round($price / $months) : 0;
    }
}
