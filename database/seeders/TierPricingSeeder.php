<?php

namespace Database\Seeders;

use App\Models\TierPricing;
use Illuminate\Database\Seeder;

/**
 * Nilai awal paket tenant.
 *
 * Angka ini HANYA titik start — semuanya bisa diubah admin platform dari panel
 * Filament setelah seeder jalan. Seeder idempoten: menjalankan ulang tidak
 * menimpa angka yang sudah diubah admin.
 */
class TierPricingSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'tier' => TierPricing::TIER_STARTER,
                'label' => 'Starter',
                'platform_markup_percent' => 4.00,
                'tenant_markup_default' => 8.00,
                'price_3_month' => 330_000,
                'price_12_month' => 800_000,
                'strike_discount_3_month' => 20.00,
                'strike_discount_12_month' => 40.00,
                'domain_tlds' => ['com', 'my.id', 'biz.id'],
                'domain_paid_tlds' => ['id', 'net'],
                'features' => [
                    'Subdomain toko instan',
                    'Checkout payment gateway pusat',
                    'Markup global per toko',
                    'Dashboard order & komisi',
                ],
                'badge' => null,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'tier' => TierPricing::TIER_BUSINESS,
                'label' => 'Business',
                'platform_markup_percent' => 3.00,
                'tenant_markup_default' => 8.00,
                'price_3_month' => 435_000,
                'price_12_month' => 898_000,
                'strike_discount_3_month' => 20.00,
                'strike_discount_12_month' => 40.00,
                'domain_tlds' => ['com', 'my.id', 'biz.id'],
                'domain_paid_tlds' => ['id', 'net'],
                'features' => [
                    'Semua fitur Starter',
                    'Domain sendiri (custom domain)',
                    'Kupon diskon toko',
                    'Atur metode & kategori pembayaran',
                ],
                'badge' => 'Popular',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'tier' => TierPricing::TIER_ENTERPRISE,
                'label' => 'Enterprise',
                'platform_markup_percent' => 2.00,
                'tenant_markup_default' => 8.00,
                'price_3_month' => 537_000,
                'price_12_month' => 989_000,
                'strike_discount_3_month' => 20.00,
                'strike_discount_12_month' => 40.00,
                'domain_tlds' => ['com', 'net', 'id', 'my.id', 'biz.id'],
                'domain_paid_tlds' => [],
                'features' => [
                    'Semua fitur Business',
                    'Modal termurah (markup platform 2%)',
                    'Semua tema tersedia',
                    'APK / TWA & laporan API',
                ],
                'badge' => null,
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($plans as $plan) {
            // firstOrCreate, bukan updateOrCreate: jangan menimpa angka yang
            // sudah disesuaikan admin platform.
            TierPricing::query()->firstOrCreate(
                ['tier' => $plan['tier']],
                $plan
            );
        }
    }
}
