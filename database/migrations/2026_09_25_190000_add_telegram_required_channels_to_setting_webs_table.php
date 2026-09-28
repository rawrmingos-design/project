<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gate keanggotaan Telegram mendukung BANYAK channel wajib sekaligus,
 * mis. channel pengumuman + grup komunitas yang harus diikuti bersamaan.
 *
 * Kolom lama (telegram_channel_id / telegram_channel_url) tetap dibiarkan
 * utuh untuk kompatibilitas: bila kolom baru kosong, nilai lama dipakai
 * sebagai satu-satunya channel wajib.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('setting_webs', function (Blueprint $table) {
            $table->json('telegram_required_channels')
                ->nullable()
                ->after('telegram_channel_url');
        });
    }

    public function down(): void
    {
        Schema::table('setting_webs', function (Blueprint $table) {
            $table->dropColumn('telegram_required_channels');
        });
    }
};
