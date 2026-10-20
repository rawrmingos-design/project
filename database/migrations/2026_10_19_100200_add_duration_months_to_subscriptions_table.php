<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Durasi langganan tenant: hanya 3 atau 12 bulan (tidak ada 1 bulan).
     *
     * Sebelumnya TenantProvisioningService selalu memajukan periode dengan
     * `addMonth()`, jadi semua paket efektif 1 bulan apa pun yang dibeli.
     */
    public function up(): void
    {
        if (! Schema::hasTable('subscriptions')) {
            return;
        }

        Schema::table('subscriptions', function (Blueprint $table): void {
            if (! Schema::hasColumn('subscriptions', 'duration_months')) {
                // Default 1 bulan, BUKAN 3.
                //
                // Semua baris lama lahir pada masa `addMonth()` dan harganya
                // adalah harga 1 bulan. Default 3 akan memberi 2 bulan gratis ke
                // setiap tenant berjalan. Default 1 juga jadi jaring pengaman:
                // kalau ada jalur kode yang lupa mengisi durasi, tenant dapat
                // periode terpendek — bukan yang paling murah.
                $table->unsignedTinyInteger('duration_months')->default(1)->after('tier');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('subscriptions')
            || ! Schema::hasColumn('subscriptions', 'duration_months')) {
            return;
        }

        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->dropColumn('duration_months');
        });
    }
};
