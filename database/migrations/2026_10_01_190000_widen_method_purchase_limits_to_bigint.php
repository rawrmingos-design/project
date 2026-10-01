<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('methods', function (Blueprint $table) {
            // Cegah integer overflow: INT (maks 2.147.483.647) -> BIGINT.
            //
            // Batas INT bertanda terlalu kecil untuk batas nominal pembayaran.
            // Nyata: admin mengisi Maximum Pembelian 100000000000 (100 miliar
            // rupiah) dan penyimpanan GAGAL di MySQL STRICT_TRANS_TABLES dengan
            // "1264 Out of range value for column 'max_pembelian'". Dengan
            // 15.000.000 (nilai yang sudah terpasang) masalahnya belum muncul,
            // jadi jebakan ini baru terasa saat admin memasukkan angka besar —
            // dan admin tidak punya cara memperbaikinya dari panel.
            $table->bigInteger('min_pembelian')->nullable()->change();
            $table->bigInteger('max_pembelian')->nullable()->change();
        });
    }

    /**
     * Balikkan migrasi.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('methods', function (Blueprint $table) {
            $table->integer('min_pembelian')->nullable()->change();
            $table->integer('max_pembelian')->nullable()->change();
        });
    }
};
