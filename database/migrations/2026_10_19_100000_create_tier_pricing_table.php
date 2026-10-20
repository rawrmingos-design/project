<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel konfigurasi paket tenant (CORE bisnis model).
     *
     * Semua angka di sini diatur admin platform dari panel Filament — tidak ada
     * yang hardcoded di kode, supaya platform ini bisa dijual dan pembelinya
     * mengubah harga/markup tanpa deploy.
     *
     * Hanya 2 durasi paket: 3 bulan dan 12 bulan.
     */
    public function up(): void
    {
        if (! Schema::hasTable('tier_pricing')) {
            Schema::create('tier_pricing', function (Blueprint $table) {
                $table->id();
                $table->string('tier', 32)->unique();
                $table->string('label', 64)->nullable();

                // Markup platform di atas layanans.harga → menentukan titik modal tenant.
                $table->decimal('platform_markup_percent', 6, 2)->default(0);

                // Default markup jual tenant (bisa diubah tenant sendiri di tenant/settings).
                $table->decimal('tenant_markup_default', 6, 2)->default(8);

                // Harga langganan. TIDAK ada paket 1 bulan.
                $table->unsignedBigInteger('price_3_month')->default(0);
                $table->unsignedBigInteger('price_12_month')->default(0);

                // Persen potongan untuk menghitung harga coret di frontend.
                // strike_price = price / (1 - percent/100)
                $table->decimal('strike_discount_3_month', 5, 2)->default(0);
                $table->decimal('strike_discount_12_month', 5, 2)->default(0);

                // Domain gratis (khusus paket 12 bulan) & TLD yang bayar tambahan.
                $table->json('domain_tlds')->nullable();
                $table->json('domain_paid_tlds')->nullable();

                // Ringkasan fitur untuk kartu harga (bisa diubah admin, tanpa deploy).
                $table->json('features')->nullable();
                $table->string('badge', 32)->nullable();

                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }

        // Isi nilai awal DI DALAM migrasi, bukan hanya di seeder.
        //
        // Workflow deploy menjalankan `php artisan migrate --force` dan TIDAK
        // menjalankan seeder. Kalau tabel dibiarkan kosong, panel admin tampil
        // kosong dan resolver bergantung pada konstanta fallback — padahal fungsi
        // inti fitur ini justru "angka hidup di DB dan bisa diubah admin".
        //
        // Data di-inline (bukan memanggil TierPricingSeeder) supaya migrasi tidak
        // bergantung pada class seeder, yang bisa tidak ter-autoload saat deploy
        // memakai `--optimize-autoloader --no-dev`.
        if (Schema::hasTable('tier_pricing') && DB::table('tier_pricing')->count() === 0) {
            $now = now();

            DB::table('tier_pricing')->insert([
                [
                    'tier' => 'starter',
                    'label' => 'Starter',
                    'platform_markup_percent' => 4.00,
                    'tenant_markup_default' => 8.00,
                    'price_3_month' => 330_000,
                    'price_12_month' => 800_000,
                    'strike_discount_3_month' => 20.00,
                    'strike_discount_12_month' => 40.00,
                    'domain_tlds' => json_encode(['com', 'my.id', 'biz.id']),
                    'domain_paid_tlds' => json_encode(['id', 'net']),
                    'features' => json_encode([
                        'Subdomain toko instan',
                        'Checkout payment gateway pusat',
                        'Markup global per toko',
                        'Dashboard order & komisi',
                    ]),
                    'badge' => null,
                    'is_active' => true,
                    'sort_order' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'tier' => 'business',
                    'label' => 'Business',
                    'platform_markup_percent' => 3.00,
                    'tenant_markup_default' => 8.00,
                    'price_3_month' => 435_000,
                    'price_12_month' => 898_000,
                    'strike_discount_3_month' => 20.00,
                    'strike_discount_12_month' => 40.00,
                    'domain_tlds' => json_encode(['com', 'my.id', 'biz.id']),
                    'domain_paid_tlds' => json_encode(['id', 'net']),
                    'features' => json_encode([
                        'Semua fitur Starter',
                        'Domain sendiri (custom domain)',
                        'Kupon diskon toko',
                        'Atur metode & kategori pembayaran',
                    ]),
                    'badge' => 'Popular',
                    'is_active' => true,
                    'sort_order' => 2,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'tier' => 'enterprise',
                    'label' => 'Enterprise',
                    'platform_markup_percent' => 2.00,
                    'tenant_markup_default' => 8.00,
                    'price_3_month' => 537_000,
                    'price_12_month' => 989_000,
                    'strike_discount_3_month' => 20.00,
                    'strike_discount_12_month' => 40.00,
                    'domain_tlds' => json_encode(['com', 'net', 'id', 'my.id', 'biz.id']),
                    'domain_paid_tlds' => json_encode([]),
                    'features' => json_encode([
                        'Semua fitur Business',
                        'Modal termurah (markup platform 2%)',
                        'Semua tema tersedia',
                        'APK / TWA & laporan API',
                    ]),
                    'badge' => null,
                    'is_active' => true,
                    'sort_order' => 3,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tier_pricing');
    }
};
