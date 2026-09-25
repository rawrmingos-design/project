<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Perbaiki collation kolom template sambutan Telegram.
 *
 * MASALAH NYATA: tabel `setting_webs` dibuat dengan collation
 * `latin1_swedish_ci`. Menyimpan teks ber-emoji (mis. 👋 di pesan
 * sambutan) ke kolom latin1 GAGAL dengan error MySQL 3988
 * ("Conversion from collation utf8mb4_unicode_ci into latin1_swedish_ci
 * impossible for parameter") — bukan sekadar tampil rusak, tetapi
 * request-nya error dan nilai TIDAK tersimpan.
 *
 * Karena sambutan member memang wajar memakai emoji, kolomnya diubah ke
 * utf8mb4. Sengaja HANYA kolom ini, bukan seluruh tabel: mengubah tabel
 * menyeluruh jauh lebih berisiko dan menjadi keputusan tersendiri.
 *
 * MySQL-only. Di sqlite (dipakai test) collation tidak berlaku, jadi
 * migrasi ini sengaja no-op agar tidak memecah suite.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! $this->isMySql()) {
            return;
        }

        if (! Schema::hasColumn('setting_webs', 'telegram_welcome_template')) {
            return;
        }

        DB::statement(
            'ALTER TABLE `setting_webs`
             MODIFY `telegram_welcome_template` TEXT
             CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL'
        );
    }

    public function down(): void
    {
        if (! $this->isMySql()) {
            return;
        }

        if (! Schema::hasColumn('setting_webs', 'telegram_welcome_template')) {
            return;
        }

        DB::statement(
            'ALTER TABLE `setting_webs`
             MODIFY `telegram_welcome_template` TEXT
             CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL'
        );
    }

    private function isMySql(): bool
    {
        return in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true);
    }
};
