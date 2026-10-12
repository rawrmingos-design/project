<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * D13.1: token publik untuk halaman tagihan.
 *
 * `gateway_ref` TIDAK boleh dipakai sebagai token: isinya timestamp + 6 karakter
 * acak (~30 bit, 8 percobaan sudah menemukan kecocokan) dan nilainya BERUBAH
 * setiap tautan bayar diregenerasi — memakainya akan mematikan tautan yang sudah
 * terkirim lewat email/WA. Kolom ini terpisah dan tetap sepanjang umur invoice.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('subscription_invoices') || Schema::hasColumn('subscription_invoices', 'public_token')) {
            return;
        }

        Schema::table('subscription_invoices', function (Blueprint $table) {
            // 32 byte acak sebagai hex = 64 karakter. Unique: satu token hanya
            // boleh menunjuk satu invoice.
            $table->string('public_token', 64)->nullable()->unique()->after('gateway_ref');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('subscription_invoices', 'public_token')) {
            return;
        }

        Schema::table('subscription_invoices', function (Blueprint $table) {
            $table->dropUnique(['public_token']);
            $table->dropColumn('public_token');
        });
    }
};
