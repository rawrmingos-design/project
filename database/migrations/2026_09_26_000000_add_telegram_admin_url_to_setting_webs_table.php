<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * URL kontak admin untuk bot Telegram — diisi dari panel admin.
 *
 * Sebelumnya nilai ini HANYA bisa diisi lewat `.env`
 * (`TELEGRAM_ADMIN_CONTACT_URL`), sehingga admin tidak punya cara mengubahnya
 * dari halaman Settings, dan tombol "📞 Hubungi Admin" bisa diam-diam hilang:
 * teks tetap menyebut tombol yang tidak ada di keyboard.
 *
 * Dipakai untuk:
 *   - tombol `📞 Hubungi Admin` di keyboard tetap bot,
 *   - tombol `💬 Hubungi Admin` saat gate keanggotaan bermasalah,
 *   - balasan perintah `admin`.
 *
 * Nilai di DB MENANG atas `.env`, tapi `.env` tetap dipakai sebagai fallback
 * kalau kolom ini kosong (deployment lama tidak perlu diubah).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('setting_webs', function (Blueprint $table) {
            $table->string('telegram_admin_url', 512)
                ->nullable()
                ->after('telegram_welcome_thread_id');
        });
    }

    public function down(): void
    {
        Schema::table('setting_webs', function (Blueprint $table) {
            $table->dropColumn('telegram_admin_url');
        });
    }
};
