<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dua hal terkait grup topik Telegram:
 *
 *   1. `telegram_discussion_url` — deep-link TOMBOL DISKUSI (opsional).
 *   2. `telegram_announcement_targets` — daftar tujuan pengumuman admin,
 *      masing-masing `{label, chat_id, thread_id}`. `thread_id` = id topik
 *      forum; kosong/null berarti kirim ke chat utama.
 *
 * Kenapa pisah dari `telegram_required_channels`: dua daftar ini punya
 * ARTI berbeda.
 *   - required_channels  = SYARAT masuk (gate). User harus bergabung.
 *   - announcement_targets = TUJUAN kirim (broadcast admin).
 * Satu grup bisa jadi tujuan kirim TANPA jadi syarat masuk, dan
 * sebaliknya. Menggabungkannya akan memaksa admin memakai satu grup untuk
 * dua peran yang berbeda.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('setting_webs', function (Blueprint $table) {
            if (! Schema::hasColumn('setting_webs', 'telegram_discussion_url')) {
                $table->string('telegram_discussion_url')->nullable()->after('telegram_required_channels');
            }

            if (! Schema::hasColumn('setting_webs', 'telegram_announcement_targets')) {
                $table->json('telegram_announcement_targets')->nullable()->after('telegram_discussion_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('setting_webs', function (Blueprint $table) {
            if (Schema::hasColumn('setting_webs', 'telegram_announcement_targets')) {
                $table->dropColumn('telegram_announcement_targets');
            }

            if (Schema::hasColumn('setting_webs', 'telegram_discussion_url')) {
                $table->dropColumn('telegram_discussion_url');
            }
        });
    }
};
