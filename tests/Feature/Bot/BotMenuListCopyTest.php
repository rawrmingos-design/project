<?php

namespace Tests\Feature\Bot;

use Tests\TestCase;

/**
 * Copy baru untuk layar Menu Utama bergaya daftar bernomor.
 *
 * Test ini BUKAN duplikat `BotLangParityTest` (yang menjaga himpunan kunci id/en
 * identik). Yang dijaga di sini adalah hal yang tidak bisa dilihat parity test:
 *
 * 1. **Nilai placeholder benar-benar tersubstitusi.** Kunci bisa ada di dua
 *    bahasa tapi tetap rusak kalau nama placeholder-nya salah tulis — user akan
 *    melihat `[:number]. :name` mentah di chat, bukan `[1]. Top Up Games`.
 * 2. **Judul ikut bahasa.** Keputusan user: `LIST PRODUCT` TIDAK boleh jadi
 *    literal Inggris yang bocor ke user Indonesia.
 */
class BotMenuListCopyTest extends TestCase
{
    /** @return array<int, string> */
    private function keys(string $locale): array
    {
        return array_keys(require lang_path($locale . '/bot.php'));
    }

    public function test_kunci_daftar_ada_di_id_dan_en(): void
    {
        foreach (['menu_list_title', 'menu_page_footer', 'menu_item_numbered'] as $key) {
            $this->assertContains($key, $this->keys('id'), "id/bot.php kehilangan {$key}");
            $this->assertContains($key, $this->keys('en'), "en/bot.php kehilangan {$key}");
        }
    }

    public function test_judul_daftar_ikut_bahasa_bukan_literal_inggris(): void
    {
        app()->setLocale('id');
        $id = __('bot.menu_list_title');

        app()->setLocale('en');
        $en = __('bot.menu_list_title');

        // Kalau keduanya sama, terjemahannya belum ada ('LIST PRODUCT' bocor).
        $this->assertNotSame($id, $en, 'Judul daftar harus diterjemahkan, bukan literal Inggris di kedua bahasa.');
    }

    public function test_baris_bernomor_tersubstitusi(): void
    {
        app()->setLocale('id');
        $this->assertSame('[1]. Top Up Games', __('bot.menu_item_numbered', [
            'number' => 1,
            'name' => 'Top Up Games',
        ]));

        app()->setLocale('en');
        $this->assertSame('[7]. Free Fire', __('bot.menu_item_numbered', [
            'number' => 7,
            'name' => 'Free Fire',
        ]));
    }

    public function test_footer_halaman_tersubstitusi(): void
    {
        app()->setLocale('id');
        $this->assertSame('📄 Halaman 1 / 2', __('bot.menu_page_footer', [
            'page' => 1,
            'total' => 2,
        ]));

        app()->setLocale('en');
        $this->assertSame('📄 Page 2 / 3', __('bot.menu_page_footer', [
            'page' => 2,
            'total' => 3,
        ]));
    }
}
