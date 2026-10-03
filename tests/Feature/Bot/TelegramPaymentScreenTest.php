<?php

namespace Tests\Feature\Bot;

use App\Services\Bot\BotGatewayCapabilities;
use App\Services\Bot\BotMessageFormatter;
use App\Services\Bot\BotNumericMenuStore;
use App\Support\TelegramMarkdown;
use Tests\TestCase;

/**
 * Layar "Pilih Pembayaran" Telegram: daftar metode di TEKS, dikelompokkan per
 * tipe, TERMURAH DULU di dalam grup.
 *
 * Kenapa layar ini butuh test sendiri: versi sebelumnya menaruh daftar HANYA di
 * tombol inline. Telegram cuma menerima satu `reply_markup` per pesan dan di
 * layar ini keyboard angka menang, jadi tombol itu tidak pernah terkirim —
 * layarnya tampil kosong ("💳 Pilih Pembayaran" tanpa satu metode).
 *
 * Nomor WAJIB per-halaman (dimulai dari 1): batas keyboard angka adalah
 * `BotNumericMenuStore::CONTENT_ENTRY_LIMIT`, jadi penomoran absolut akan
 * membuat seluruh entri isi dibuang dan layarnya mati lagi.
 */
class TelegramPaymentScreenTest extends TestCase
{
    private const TG = BotGatewayCapabilities::SOURCE_TELEGRAM;

    private const WA = BotGatewayCapabilities::SOURCE_WHATSAPP;

    /**
     * @param  array<int, array{name: string, code: string, group?: string, group_sort?: int, fee?: int|null}>  $methods
     */
    private function screen(array $methods, int $page = 1, string $serviceLabel = ''): array
    {
        return app(BotMessageFormatter::class)->formatPaymentMethods(
            ['ok' => true, 'data' => array_map(
                fn (array $m): array => ['name' => $m['name'], 'code' => $m['code']],
                $methods,
            )],
            214,
            $page,
            'layanan free-fire',
            BotGatewayCapabilities::forSource(self::TG),
            $methods,
            $serviceLabel,
        );
    }

    // ------------------------------------------------------------------ isi daftar

    public function test_daftar_metode_ada_di_teks_bukan_di_tombol(): void
    {
        $screen = $this->screen([
            ['name' => 'QRIS', 'code' => 'QRIS', 'group' => 'QRIS', 'group_sort' => 2, 'fee' => 863],
            ['name' => 'BCA Virtual Account', 'code' => 'BC', 'group' => 'Virtual Account', 'group_sort' => 4, 'fee' => 0],
        ]);

        $text = (string) $screen['text'];

        $this->assertStringContainsString('QRIS', $text);
        $this->assertStringContainsString('BCA Virtual Account', $text);
        $this->assertStringContainsString('[1].', $text);

        // Nol tombol: kalau isi daftar kembali ke tombol, layarnya kosong lagi
        // begitu keyboard angka menang di adapter.
        $this->assertSame([], (array) $screen['buttons'], 'Layar ini tidak boleh mengirim tombol item.');
    }

    public function test_judul_menyebut_layanan_yang_sedang_dipilih(): void
    {
        $screen = $this->screen(
            [['name' => 'QRIS', 'code' => 'QRIS', 'group' => 'QRIS', 'group_sort' => 2, 'fee' => 863]],
            serviceLabel: '5 Diamond Free Fire',
        );

        // Tanpa ini user tidak tahu layar ini untuk layanan yang mana.
        $this->assertStringContainsString('5 Diamond Free Fire', (string) $screen['text']);
    }

    // -------------------------------------------------------------------- biaya

    public function test_biaya_tiap_metode_ditampilkan(): void
    {
        $text = (string) $this->screen([
            ['name' => 'BCA Virtual Account', 'code' => 'BC', 'group' => 'Virtual Account', 'group_sort' => 4, 'fee' => 0],
            ['name' => 'DANA', 'code' => 'DANA', 'group' => 'E-Wallet', 'group_sort' => 3, 'fee' => 13],
        ])['text'];

        // Angka biaya harus dijelaskan sebagai FEE ADMIN: angka telanjang setelah
        // nama metode tidak memberi tahu itu biaya apa.
        $this->assertStringContainsString('(fee admin: Rp 0)', $text);
        $this->assertStringContainsString('(fee admin: Rp 13)', $text);
    }

    public function test_biaya_yang_tak_bisa_dihitung_tidak_dikarang(): void
    {
        // `null` = quote gagal (mis. nominal di bawah minimum metode). Angka
        // karangan lebih buruk daripada mengaku biayanya muncul nanti.
        $text = (string) $this->screen([
            ['name' => 'Indomaret', 'code' => 'INDOMARET', 'group' => 'Convenience Store', 'group_sort' => 5, 'fee' => null],
        ])['text'];

        $this->assertStringContainsString('(fee admin: dihitung di langkah berikutnya)', $text);
        $this->assertStringContainsString('Indomaret', $text, 'Metode tetap ditawarkan walau biayanya belum diketahui.');
    }

    /**
     * Label grup memakai penanda MIRING underscore TUNGGAL.
     *
     * Parser Telegram hanya mengenali `_teks_` sebagai miring; `_\:teks_` atau
     * penanda lurus lain ikut ter-escape sehingga tampil MENTAH di layar. Test
     * ini menjaga penandanya tetap satu underscore.
     */
    public function test_label_grup_memakai_penanda_miring_yang_didukung_parser(): void
    {
        $text = (string) $this->screen([
            ['name' => 'QRIS', 'code' => 'QRIS', 'group' => 'QRIS', 'group_sort' => 2, 'fee' => 137],
        ])['text'];

        $this->assertStringContainsString('_QRIS_', $text);

        // Dan penandanya harus benar-benar menjadi miring setelah dikonversi —
        // inilah yang dulu gagal, sehingga user melihat `_QRIS_` apa adanya.
        $terkirim = TelegramMarkdown::fromLegacy($text);

        $this->assertStringContainsString('_QRIS_', $terkirim, 'Penanda miring ikut ter-escape dan tampil mentah.');
        $this->assertStringNotContainsString('\\_QRIS\\_', $terkirim);
        $this->assertStringContainsString('\\(fee admin: Rp 137\\)', $terkirim);
    }

    // ----------------------------------------------------------------- urutan

    public function test_dalam_grup_termurah_dulu_dan_grup_ikut_urutan_panel(): void
    {
        $screen = $this->screen([
            ['name' => 'OVO', 'code' => 'OVO', 'group' => 'E-Wallet', 'group_sort' => 3, 'fee' => 13],
            ['name' => 'DANA', 'code' => 'DANA', 'group' => 'E-Wallet', 'group_sort' => 3, 'fee' => 13],
            ['name' => 'QRIS', 'code' => 'QRIS', 'group' => 'QRIS', 'group_sort' => 2, 'fee' => 863],
            ['name' => 'BCA Virtual Account', 'code' => 'BC', 'group' => 'Virtual Account', 'group_sort' => 4, 'fee' => 0],
        ]);

        $entries = (array) ($screen['numeric_menu']['entries'] ?? []);

        // Grup: QRIS (2) -> E-Wallet (3) -> Virtual Account (4). DANA/OVO sama
        // biaya, jadi urutannya alfabetis supaya stabil.
        $this->assertSame('harga 214 QRIS', (string) $entries['1']['command']);
        $this->assertSame('harga 214 DANA', (string) $entries['2']['command']);
        $this->assertSame('harga 214 OVO', (string) $entries['3']['command']);
        $this->assertSame('harga 214 BC', (string) $entries['4']['command']);
    }

    public function test_metode_tanpa_biaya_diketahui_ditaruh_terakhir_di_grupnya(): void
    {
        $screen = $this->screen([
            ['name' => 'Indomaret', 'code' => 'INDOMARET', 'group' => 'Convenience Store', 'group_sort' => 5, 'fee' => null],
            ['name' => 'Alfamart', 'code' => 'ALFAMART', 'group' => 'Convenience Store', 'group_sort' => 5, 'fee' => 5000],
        ]);

        $entries = (array) ($screen['numeric_menu']['entries'] ?? []);

        $this->assertSame('harga 214 ALFAMART', (string) $entries['1']['command']);
        $this->assertSame('harga 214 INDOMARET', (string) $entries['2']['command']);
    }

    // ------------------------------------------------------------------ nomor

    public function test_nomor_diterima_penyimpan_menu(): void
    {
        $screen = $this->screen([
            ['name' => 'QRIS', 'code' => 'QRIS', 'group' => 'QRIS', 'group_sort' => 2, 'fee' => 863],
            ['name' => 'DANA', 'code' => 'DANA', 'group' => 'E-Wallet', 'group_sort' => 3, 'fee' => 13],
        ]);

        $store = app(BotNumericMenuStore::class);
        $store->put('user-1', (array) $screen['numeric_menu'], (string) $screen['text']);

        // Kalau nomornya melewati CONTENT_ENTRY_LIMIT, put() membuang seluruh
        // entri isi dan resolve() mengembalikan 'invalid' — layar mati lagi.
        $this->assertSame('ok', $store->resolve('user-1', 1)['status']);
        $this->assertSame('ok', $store->resolve('user-1', 2)['status']);
    }

    public function test_entri_kembali_menuju_daftar_layanan(): void
    {
        $entries = (array) ($this->screen([
            ['name' => 'QRIS', 'code' => 'QRIS', 'group' => 'QRIS', 'group_sort' => 2, 'fee' => 863],
        ])['numeric_menu']['entries'] ?? []);

        $this->assertArrayHasKey('0', $entries);
        $this->assertSame('layanan free-fire', (string) $entries['0']['command']);
    }

    // ---------------------------------------------------------------- WhatsApp

    /**
     * Footer "Ketik angka untuk memilih pembayaran." DIHAPUS atas permintaan.
     *
     * Nomor pada daftar sudah menjelaskan sendiri bahwa angkanya bisa diketuk,
     * dan baris itu mengulang hal yang sama di setiap layar. Yang tetap wajib
     * ada hanya stempel waktu di baris paling bawah.
     */
    public function test_baris_panduan_ketik_angka_tidak_ada_lagi(): void
    {
        $text = (string) $this->screen([
            ['name' => 'QRIS', 'code' => 'QRIS', 'group' => 'QRIS', 'group_sort' => 2, 'fee' => 137],
        ])['text'];

        $this->assertStringNotContainsString('Ketik angka', $text);
        $this->assertStringNotContainsString('Type a number', $text);
    }

    /**
     * Kunci lang-nya harus benar-benar DIBUANG, bukan hanya tidak dipakai.
     *
     * Kalau kuncinya ditinggal, ia jadi pintu masuk untuk menghidupkan kembali
     * baris itu lewat terjemahan, dan `BotLangParityTest` tidak akan menangkap
     * perbedaan karena kuncinya tetap ada di dua bahasa.
     */
    public function test_kunci_lang_footer_pembayaran_sudah_dibuang(): void
    {
        foreach (['id', 'en'] as $locale) {
            $keys = array_keys(require lang_path($locale . '/bot.php'));

            $this->assertNotContains('payment_list_footer', $keys, "Kunci payment_list_footer masih ada di {$locale}/bot.php.");
        }
    }

    public function test_whatsapp_tetap_memakai_tombol(): void
    {
        $wa = app(BotMessageFormatter::class)->formatPaymentMethods(
            ['ok' => true, 'data' => [['name' => 'QRIS', 'code' => 'QRIS']]],
            214,
            1,
            'layanan free-fire',
            BotGatewayCapabilities::forSource(self::WA),
        );

        $this->assertSame('💳 *Pilih Pembayaran*', $wa['text']);
        $this->assertStringNotContainsString('[1].', (string) $wa['text']);

        $texts = [];
        foreach ((array) ($wa['buttons'] ?? []) as $row) {
            foreach ((array) $row as $button) {
                $texts[] = (string) ($button['text'] ?? '');
            }
        }

        $this->assertContains('💳 QRIS', $texts);
    }
}
