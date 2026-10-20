<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        if (Schema::hasTable('tier_pricing')) {
            return;
        }

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

    public function down(): void
    {
        Schema::dropIfExists('tier_pricing');
    }
};
