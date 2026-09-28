<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Preferensi bahasa bot per percakapan.
 *
 * Kenapa tabel baru, bukan kolom di `telegram_identities`:
 *  - `telegram_identities.user_id` adalah FK NOT NULL → barisnya mustahil ada
 *    tanpa user terdaftar.
 *  - Baris itu baru dibuat SAAT REGISTRASI (BotCommandHandler), bukan saat
 *    `/start`. Jadi user pra-registrasi — sasaran utama auto-deteksi di
 *    sentuhan pertama — tidak punya baris sama sekali.
 *
 * Kunci baris memakai `external_user_id` yang sudah stabil sejak pesan pertama
 * (`telegram:<scope>:<fromId>`) — sama persis dengan kunci state checkout
 * (`BotCommandHandler::checkoutStateKey()`), jadi hanya ada satu konsep kunci.
 *
 * Kenapa DB, bukan cache (padahal state checkout pakai cache 15 menit):
 * preferensi bahasa bersifat mengikat permanen. Kalau cache dibersihkan,
 * semua user kehilangan bahasa pilihannya lalu auto-deteksi menyemainya ulang
 * dari `language_code` — bug "user pilih Indonesia, balik Inggris".
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bot_locale_preferences')) {
            return;
        }

        Schema::create('bot_locale_preferences', function (Blueprint $table): void {
            $table->id();
            $table->string('source', 40);            // 'telegram_gateway' | (wa menyusul)
            $table->string('external_user_id', 191); // 'telegram:default:6252007210'
            $table->string('locale', 8);             // 'id' | 'en' — SELALU terisi
            $table->string('locale_source', 16);     // explicit | detected | panel
            $table->timestamps();

            // Satu preferensi per percakapan. Unique index juga jadi pengaman
            // race saat dua webhook datang bersamaan.
            $table->unique(['source', 'external_user_id'], 'bot_locale_pref_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_locale_preferences');
    }
};
