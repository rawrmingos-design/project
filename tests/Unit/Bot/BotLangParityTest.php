<?php

namespace Tests\Unit\Bot;

use Tests\TestCase;

/**
 * Bahasa bot hanya boleh punya SATU himpunan kunci untuk id dan en.
 *
 * Kalau `en` ketinggalan satu kunci, `__('bot.x')` akan mengembalikan
 * literal 'bot.x' dan itu tampil ke user di tengah chat — rusak dan memalukan.
 * Test ini mencegahnya di CI, bukan setelah dilihat user.
 */
class BotLangParityTest extends TestCase
{
    /** @return array<int, string> */
    private function keys(string $locale): array
    {
        $path = lang_path($locale . '/bot.php');

        $this->assertFileExists($path, "File lang {$locale}/bot.php tidak ada.");

        $keys = array_keys(require $path);
        sort($keys);

        return $keys;
    }

    public function test_kunci_lang_id_dan_en_identik(): void
    {
        $id = $this->keys('id');
        $en = $this->keys('en');

        $missingEn = array_values(array_diff($id, $en));
        $missingId = array_values(array_diff($en, $id));

        $this->assertSame(
            [],
            $missingEn,
            'Kunci ini ada di id/bot.php tapi HILANG di en/bot.php: ' . implode(', ', $missingEn),
        );
        $this->assertSame(
            [],
            $missingId,
            'Kunci ini ada di en/bot.php tapi HILANG di id/bot.php: ' . implode(', ', $missingId),
        );
    }

    public function test_tidak_ada_terjemahan_kosong(): void
    {
        foreach (['id', 'en'] as $locale) {
            foreach (require lang_path($locale . '/bot.php') as $key => $value) {
                $this->assertIsString($value, "{$locale}.bot.{$key} harus string.");
                $this->assertNotSame('', trim($value), "{$locale}.bot.{$key} kosong.");
            }
        }
    }

    public function test_placeholder_sama_di_kedua_bahasa(): void
    {
        $id = require lang_path('id/bot.php');
        $en = require lang_path('en/bot.php');

        $placeholders = static function (string $text): array {
            preg_match_all('/:([a-z_]+)/', $text, $m);
            $found = $m[1];
            sort($found);

            return $found;
        };

        foreach ($id as $key => $value) {
            if (! isset($en[$key]) || ! is_string($value) || ! is_string($en[$key])) {
                continue;
            }

            $this->assertSame(
                $placeholders($value),
                $placeholders($en[$key]),
                "Placeholder {$key} tidak sinkron antar bahasa (mis. :store hilang di salah satu).",
            );
        }
    }

    public function test_teks_indonesia_tidak_disalin_mentah_ke_inggris(): void
    {
        $id = require lang_path('id/bot.php');
        $en = require lang_path('en/bot.php');

        // Kunci yang nilainya identik di dua bahasa = kemungkinan belum
        // diterjemahkan (kecuali emoji/simbol). Ini peringatan dini, bukan
        // hukuman: daftar putih untuk teks netral.
        // - `skip`/`ya`/`tidak`  : kata perintah, memang tidak diterjemahkan.
        // - `checkout_total`      : 'Total' adalah kata yang sama di id & en,
        //   dan spasi paddingnya harus identik supaya kolom harga tetap rata.
        $allowedIdentical = ['skip', 'ya', 'tidak', 'checkout_total'];

        $identical = [];
        foreach ($id as $key => $value) {
            if (in_array($key, $allowedIdentical, true)) {
                continue;
            }

            if (isset($en[$key]) && $en[$key] === $value) {
                $identical[] = $key;
            }
        }

        $this->assertSame(
            [],
            $identical,
            'Kunci ini sama persis di id & en (kemungkinan lupa diterjemahkan): ' . implode(', ', $identical),
        );
    }
}
