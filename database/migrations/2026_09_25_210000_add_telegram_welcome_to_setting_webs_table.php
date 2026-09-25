<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sambutan otomatis untuk member BARU di grup Telegram.
 *
 *   - `telegram_welcome_enabled`   : saklar on/off (default OFF).
 *   - `telegram_welcome_template`  : isi pesan, mendukung placeholder
 *                                    {nama}, {grup}, {sebutan}.
 *   - `telegram_welcome_thread_id` : opsional; id topik tujuan sambutan.
 *                                    Kosong = kirim ke chat utama
 *                                    (topik "General" bawaan forum).
 *
 * Default OFF supaya deployment TIDAK tiba-tiba menyapa member grup yang
 * sudah ada. Admin harus menyalakan sendiri setelah pesannya diperiksa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('setting_webs', function (Blueprint $table) {
            $table->boolean('telegram_welcome_enabled')->default(false)->nullable()->after('telegram_announcement_targets');
            $table->text('telegram_welcome_template')->nullable()->after('telegram_welcome_enabled');
            $table->unsignedBigInteger('telegram_welcome_thread_id')->nullable()->after('telegram_welcome_template');
        });
    }

    public function down(): void
    {
        Schema::table('setting_webs', function (Blueprint $table) {
            $table->dropColumn([
                'telegram_welcome_enabled',
                'telegram_welcome_template',
                'telegram_welcome_thread_id',
            ]);
        });
    }
};
