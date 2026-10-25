<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kunci gateway pembayaran per kategori (game).
     * NULL  = tanpa kunci: semua metode/gateway yang aktif tetap muncul (perilaku lama).
     * Terisi = metode yang muncul & boleh dipakai dibatasi ke gateway ini
     *          (nilai harus sama dengan kolom `payment` di tabel `methods`,
     *           mis. 'duitku', 'tokopay', 'tripay', 'paydisini'). Saldo member
     *          tetap diizinkan karena bukan gateway pihak ketiga.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('kategoris', 'payment_gateway')) {
            Schema::table('kategoris', function (Blueprint $table) {
                $table->string('payment_gateway', 50)->nullable()->after('tipe');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('kategoris', 'payment_gateway')) {
            Schema::table('kategoris', function (Blueprint $table) {
                $table->dropColumn('payment_gateway');
            });
        }
    }
};
