<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Banner gambar untuk layar **Menu Utama** bot order (Telegram).
 *
 * Satu kolom saja, dan SATU gambar dipakai untuk layar Menu Utama. Admin
 * mengunggahnya dari panel (Settings > Branding), jadi gambar bisa diganti
 * kapan saja tanpa deploy ulang.
 *
 * Default NULL = perilaku LAMA persis (bot mengirim menu sebagai teks tanpa
 * gambar). Deployment tidak boleh tiba-tiba mengubah tampilan bot untuk
 * semua user hanya karena kolom ini lahir.
 *
 * Catatan pemakaian: jalur Telegram memakai `sendPhoto`, dan Telegram
 * menolak SELURUH pesan kalau caption melebihi 1024 karakter. Karena itu
 * `BotMessageFormatter` hanya mengisi `photo_url` kalau berkasnya
 * BENAR-BENAR ada di disk (lewat PublicUploadUrlService::existingUrl()).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('setting_webs', 'bot_menu_banner')) {
            return;
        }

        Schema::table('setting_webs', function (Blueprint $table) {
            $table->text('bot_menu_banner')->nullable()->after('telegram_admin_url');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('setting_webs', 'bot_menu_banner')) {
            return;
        }

        Schema::table('setting_webs', function (Blueprint $table) {
            $table->dropColumn('bot_menu_banner');
        });
    }
};
