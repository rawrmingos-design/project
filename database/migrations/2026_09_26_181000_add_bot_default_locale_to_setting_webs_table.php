<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Default bahasa bot (`/bahasa` + auto-deteksi seed).
 *
 * Ini SATU-SATUNYA field panel untuk fitur bahasa — tidak ada toggle
 * auto-deteksi (keputusan user 2026-09-26: "cukup kita bikin aja").
 * Auto-deteksi selalu aktif sebagai seed; pilihan eksplisit user selalu menang
 * (lihat App\Services\Bot\BotLocale).
 *
 * Default 'id' = pasar sebenarnya, sekaligus jaring terakhir kalau kode bahasa
 * Telegram absen / tidak didukung.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('setting_webs') || Schema::hasColumn('setting_webs', 'bot_default_locale')) {
            return;
        }

        Schema::table('setting_webs', function (Blueprint $table): void {
            $table->string('bot_default_locale', 8)
                ->default('id')
                ->after('bot_order_tg_enabled');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('setting_webs') || ! Schema::hasColumn('setting_webs', 'bot_default_locale')) {
            return;
        }

        Schema::table('setting_webs', function (Blueprint $table): void {
            $table->dropColumn('bot_default_locale');
        });
    }
};
