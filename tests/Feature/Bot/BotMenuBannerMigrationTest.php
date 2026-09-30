<?php

namespace Tests\Feature\Bot;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Guard untuk kolom banner Menu Utama bot (`setting_webs.bot_menu_banner`).
 *
 * Nilai kolom ini dipakai runtime untuk memutuskan apakah bot mengirim
 * `sendPhoto`. Kalau kolomnya hilang, pembacaan kolom melempar
 * QueryException di setiap pembukaan Menu Utama — bukan sekadar banner yang
 * tidak muncul, tapi menu yang rusak.
 *
 * Migrasi ditulis IDEMPOTENT (guard `Schema::hasColumn`), karena schema
 * production punya drift: kolom pernah ditambahkan manual di satu environment
 * saja. Sifat itu diuji langsung di sini, bukan diasumsikan.
 */
class BotMenuBannerMigrationTest extends TestCase
{
    private function migration(): Migration
    {
        return require base_path('database/migrations/2026_09_30_190000_add_bot_menu_banner_to_setting_webs_table.php');
    }

    /**
     * Siapkan tabel tiruan dalam bentuk "sebelum migrasi": kolom anchor
     * (`telegram_admin_url`) ada, `bot_menu_banner` belum.
     */
    private function seedPreMigrationTable(): void
    {
        Schema::dropIfExists('setting_webs');

        Schema::create('setting_webs', function (Blueprint $table) {
            $table->id();
            $table->text('telegram_admin_url')->nullable();
        });

        DB::table('setting_webs')->insert(['id' => 1, 'telegram_admin_url' => 'https://t.me/admin']);
    }

    public function test_kolom_ditambahkan_dan_awalnya_null(): void
    {
        $this->seedPreMigrationTable();

        $this->assertFalse(Schema::hasColumn('setting_webs', 'bot_menu_banner'), 'Prasyarat: kolom harus absen dulu.');

        $this->migration()->up();

        $this->assertTrue(Schema::hasColumn('setting_webs', 'bot_menu_banner'), 'Migrasi harus menambahkan kolomnya.');

        // Default NULL = perilaku LAMA: bot mengirim menu sebagai teks tanpa
        // gambar. Baris yang sudah ada tidak boleh tiba-tiba punya nilai.
        $this->assertNull(
            DB::table('setting_webs')->where('id', 1)->value('bot_menu_banner'),
            'Nilai awal harus NULL supaya bot tidak tiba-tiba mengirim gambar.'
        );
    }

    public function test_data_baris_lama_tidak_rusak(): void
    {
        $this->seedPreMigrationTable();

        $this->migration()->up();

        $this->assertSame(
            'https://t.me/admin',
            DB::table('setting_webs')->where('id', 1)->value('telegram_admin_url'),
            'Menambah kolom tidak boleh menyentuh data yang sudah ada.'
        );
    }

    public function test_idempotent_kalau_kolom_sudah_ada(): void
    {
        $this->seedPreMigrationTable();
        $this->migration()->up();

        // Schema production punya drift: kolom bisa sudah ada (ditambahkan
        // manual) saat migrasi ini pertama kali jalan. `up()` kedua kali
        // TIDAK boleh melempar — kalau melempar, seluruh deploy production
        // gagal di fase migrate.
        $this->migration()->up();

        $this->assertTrue(Schema::hasColumn('setting_webs', 'bot_menu_banner'));
    }

    public function test_down_menghapus_kolom_dan_aman_dipanggil_dua_kali(): void
    {
        $this->seedPreMigrationTable();
        $this->migration()->up();
        $this->assertTrue(Schema::hasColumn('setting_webs', 'bot_menu_banner'));

        $this->migration()->down();
        $this->assertFalse(Schema::hasColumn('setting_webs', 'bot_menu_banner'), 'down() harus menghapus kolom.');

        // Panggilan kedua tidak boleh melempar.
        $this->migration()->down();
        $this->assertFalse(Schema::hasColumn('setting_webs', 'bot_menu_banner'));
    }
}
