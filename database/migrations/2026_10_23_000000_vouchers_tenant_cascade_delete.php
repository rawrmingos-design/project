<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kupon tenant ikut terhapus saat tenantnya dihapus.
 *
 * Sebelumnya FK `vouchers.tenant_id` memakai `nullOnDelete`. Untuk tabel biasa
 * itu wajar, tapi TIDAK untuk voucher: `tenant_id NULL` secara definisi berarti
 * "kupon milik platform". Jadi menghapus tenant mengubah kuponnya menjadi kupon
 * platform — promo yang dulu hanya untuk satu toko tiba-tiba berlaku di seluruh
 * platform, dan platform yang menanggung diskonnya.
 *
 * Perilaku ini sudah dijaga di level aplikasi (`Tenant::booted()`), tetapi hook
 * Eloquent tidak jalan untuk `DELETE` massal / operasi di luar Eloquent. Karena
 * itu FK-nya diperbaiki juga.
 *
 * SQLite: foreign key tidak bisa diubah tanpa rebuild tabel, dan SQLite tidak
 * dipakai di produksi (hanya untuk tes, yang sudah tertutup hook aplikasi),
 * jadi migrasi ini melewatinya.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vouchers') || ! Schema::hasColumn('vouchers', 'tenant_id')) {
            return;
        }

        // SQLite tidak mendukung drop/foreign; lewati (lihat docblock).
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('vouchers', function (Blueprint $table): void {
            $table->dropForeign(['tenant_id']);
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('vouchers') || ! Schema::hasColumn('vouchers', 'tenant_id')) {
            return;
        }

        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('vouchers', function (Blueprint $table): void {
            $table->dropForeign(['tenant_id']);
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->nullOnDelete();
        });
    }
};
