<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ledger idempotency untuk restore stok voucher: setiap order yang berakhir
 * gagal/expired/dibatalkan setelah stok voucher terpotong boleh mengembalikan
 * stok maksimal SEKALI. Stempel ini mencegah double-restore ketika callback
 * gateway/webhook terkirim ulang atau command expiry jalan berulang.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('pembelians', 'voucher_stock_restored_at')) {
            return;
        }

        Schema::table('pembelians', function (Blueprint $table) {
            $table->timestamp('voucher_stock_restored_at')->nullable()->after('used_point_amount');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('pembelians', 'voucher_stock_restored_at')) {
            return;
        }

        Schema::table('pembelians', function (Blueprint $table) {
            $table->dropColumn('voucher_stock_restored_at');
        });
    }
};
