<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penghitung kegagalan TRANSPORT provider yang beruntun.
 *
 * Dipakai untuk memutuskan kapan sebuah order boleh di-Gagal-kan ketika provider
 * tidak bisa dihubungi berulang kali (lihat App\Support\ProviderTransportError).
 * Sebelum kolom ini, satu timeout saja sudah cukup untuk memvonis order gagal —
 * termasuk order yang sudah dibayar.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('pembelians', 'transport_failure_count')) {
            return;
        }

        Schema::table('pembelians', function (Blueprint $table) {
            $table->unsignedInteger('transport_failure_count')
                ->default(0)
                ->after('keterangan_sn');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('pembelians', 'transport_failure_count')) {
            return;
        }

        Schema::table('pembelians', function (Blueprint $table) {
            $table->dropColumn('transport_failure_count');
        });
    }
};
