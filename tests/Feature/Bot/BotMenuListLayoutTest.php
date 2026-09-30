<?php

namespace Tests\Feature\Bot;

use App\Services\Bot\BotGatewayCapabilities;
use App\Services\Bot\BotMessageFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Bentuk layar Menu Utama Telegram: daftar bernomor di TEKS.
 *
 * Perubahan ini memindahkan item dari INLINE BUTTON ke TEKS. Konsekuensinya
 * bukan kosmetik — dulu user bisa memencet nama kategori, sekarang tidak lagi,
 * jadi nomor di teks itu satu-satunya penanda posisi. Karena itu test ini
 * menjaga dua sisi sekaligus:
 *
 * 1. **Bentuk teks** (judul, baris bernomor, footer jam) — yang diminta user.
 * 2. **Button kategori benar-benar hilang** — kalau tidak, layarnya menampilkan
 *    nomor DAN tombol untuk hal yang sama, dan user tidak tahu mana yang aktif.
 *
 * Item untuk WhatsApp tidak diuji di sini: `formatCategories()` dwi-channel, dan
 * perilaku WhatsApp dijaga test-nya sendiri.
 */
class BotMenuListLayoutTest extends TestCase
{
    // `formatCategories()` membaca `SettingWeb` untuk gate banner, jadi tabel
    // database wajib ada walaupun test ini soal bentuk teks.
    use RefreshDatabase;

    private function formatTelegram(array $data, int $page = 1): array
    {
        return app(BotMessageFormatter::class)->formatCategories(
            $data,
            $page,
            BotGatewayCapabilities::forSource(BotGatewayCapabilities::SOURCE_TELEGRAM),
        );
    }

    private function payload(int $count = 4): array
    {
        $names = [
            ['name' => 'Top Up Games', 'slug' => 'top-up-games'],
            ['name' => 'Specialist Mobile Legends', 'slug' => 'specialist-mobile-legends'],
            ['name' => 'App Premium', 'slug' => 'app-premium'],
            ['name' => 'Pulsa & Data', 'slug' => 'pulsa-data'],
            ['name' => 'Kategori Lima', 'slug' => 'kategori-lima'],
            ['name' => 'Kategori Enam', 'slug' => 'kategori-enam'],
            ['name' => 'Kategori Tujuh', 'slug' => 'kategori-tujuh'],
            ['name' => 'Kategori Delapan', 'slug' => 'kategori-delapan'],
            ['name' => 'Kategori Sembilan', 'slug' => 'kategori-sembilan'],
        ];

        return ['ok' => true, 'data' => array_slice($names, 0, $count)];
    }

    public function test_narasi_sapaan_sudah_tidak_ada(): void
    {
        $response = $this->formatTelegram($this->payload());

        $this->assertStringNotContainsString('Selamat datang', $response['text']);
        $this->assertStringNotContainsString('Penuhi kebutuhan game', $response['text']);
        $this->assertStringNotContainsString('Pilih kategori di bawah', $response['text']);
        $this->assertStringNotContainsString('Menu Utama', $response['text']);
    }

    public function test_judul_dari_kunci_lang_ikut_bahasa(): void
    {
        app()->setLocale('id');
        $id = $this->formatTelegram($this->payload())['text'];

        app()->setLocale('en');
        $en = $this->formatTelegram($this->payload())['text'];

        $this->assertStringContainsString(__('bot.menu_list_title', [], 'id'), $id);
        $this->assertStringContainsString(__('bot.menu_list_title', [], 'en'), $en);
    }

    public function test_item_tampil_bernomor_di_teks(): void
    {
        $response = $this->formatTelegram($this->payload());
        $text = $response['text'];

        $this->assertStringContainsString('[1]. Top Up Games', $text);
        $this->assertStringContainsString('[2]. Specialist Mobile Legends', $text);
        $this->assertStringContainsString('[3]. App Premium', $text);
        $this->assertStringContainsString('[4]. Pulsa & Data', $text);
        $this->assertStringNotContainsString('[5].', $text, 'Hanya 4 item yang dikirim.');
    }

    public function test_button_kategori_sudah_tidak_ada(): void
    {
        // Konsekuensi keputusan user: item pindah ke teks, jadi tombol
        // kategori `kategori <slug>` tidak boleh tersisa.
        //
        // WAJIB pakai menu DUA halaman: pada satu halaman `buttons` kosong, dan
        // loop atas array kosong tidak meng-assert apa pun — test yang selalu
        // hijau tanpa memeriksa apa-apa.
        $response = $this->formatTelegram($this->payload(9));
        $seen = 0;

        foreach ($response['buttons'] as $row) {
            foreach ($this->flatten($row) as $button) {
                $seen++;
                $callback = (string) ($button['callback'] ?? '');
                $this->assertStringNotContainsString(
                    'kategori ',
                    $callback,
                    "Tombol kategori masih ada: {$callback} ({$button['text']})",
                );
            }
        }

        $this->assertGreaterThan(0, $seen, 'Harus ada tombol navigasi yang diperiksa.');
    }

    public function test_tombol_navigasi_tetap_ada_saat_lebih_dari_satu_halaman(): void
    {
        $response = $this->formatTelegram($this->payload(9));

        $callbacks = [];
        foreach ($response['buttons'] as $row) {
            foreach ($this->flatten($row) as $button) {
                $callbacks[] = (string) ($button['callback'] ?? '');
            }
        }

        // Tombol pindah halaman adalah SATU-SATUNYA jalan user tahu halaman 2
        // ada; menghapusnya membuat daftar terlihat lengkap padahal terpotong.
        $this->assertContains('menu page:2', $callbacks);
    }

    public function test_peta_nomor_dikirim_untuk_tiap_item(): void
    {
        $response = $this->formatTelegram($this->payload());

        $this->assertArrayHasKey('numeric_menu', $response);
        $entries = $response['numeric_menu']['entries'] ?? [];

        $this->assertSame('kategori top-up-games', $entries['1']['command'] ?? null);
        $this->assertSame('kategori pulsa-data', $entries['4']['command'] ?? null);
        $this->assertArrayNotHasKey('5', $entries);
    }

    public function test_footer_memuat_jam_dan_tidak_memuat_baris_halaman_saat_satu_halaman(): void
    {
        $text = $this->formatTelegram($this->payload())['text'];

        $this->assertMatchesRegularExpression('/\d{2}:\d{2}:\d{2}/', $text);
        $this->assertStringNotContainsString('Halaman', $text);
    }

    public function test_footer_memuat_baris_halaman_saat_lebih_dari_satu_halaman(): void
    {
        // 9 item, halaman Telegram = 8 -> dua halaman.
        $text = $this->formatTelegram($this->payload(9))['text'];

        $this->assertStringContainsString('1 / 2', $text);
        $this->assertStringContainsString('[8]. Kategori Delapan', $text);
        $this->assertStringNotContainsString('[9].', $text);
    }

    public function test_kategori_tanpa_nama_memakai_fallback(): void
    {
        $response = $this->formatTelegram([
            'ok' => true,
            'data' => [
                ['name' => '', 'slug' => 'tanpa-nama'],
                ['slug' => 'tanpa-name-key'],
            ],
        ]);

        $this->assertStringContainsString('[:1]. Kategori', str_replace('[1]', '[:1]', $response['text']));
        $this->assertSame('kategori tanpa-nama', $response['numeric_menu']['entries']['1']['command']);
    }

    public function test_banner_tetap_dikirim_bersama_daftar(): void
    {
        // Banner adalah fitur lain di layar yang sama; perubahan bentuk teks
        // tidak boleh mematikan gate Telegram-nya.
        \App\Models\SettingWeb::query()->create([
            'id' => 1,
            'judul_web' => 'Test Topup',
            'deskripsi_web' => 'Deskripsi test',
            'keywords' => 'topup,test',
            'url_wa' => 'https://wa.me/628123456789',
            'url_ig' => 'https://instagram.com/test',
            'url_tiktok' => 'https://tiktok.com/@test',
            'url_youtube' => 'https://youtube.com/@test',
            'url_fb' => 'https://facebook.com/test',
            'topupindo_api' => 'dummy-api',
            'warna1' => '#111111',
            'warna2' => '#222222',
            'warna3' => '#333333',
            'warna4' => '#444444',
            'paydisini_apikey' => 'dummy-paydisini',
            'order_prefik' => 'INV',
            'public_theme' => 'default',
            'bot_menu_banner' => 'assets/bot/tidak-ada.webp',
        ]);

        $response = $this->formatTelegram($this->payload());

        // Berkas tidak ada -> URL tidak diisi, tapi teks tetap terkirim.
        $this->assertArrayNotHasKey('photo_url', $response);
        $this->assertStringContainsString('[1]. Top Up Games', $response['text']);
    }

    /** @return array<int, array<string, mixed>> */
    private function flatten(mixed $row): array
    {
        if (! is_array($row)) {
            return [];
        }

        if (isset($row['text'])) {
            return [$row];
        }

        $flat = [];
        foreach ($row as $button) {
            if (is_array($button) && isset($button['text'])) {
                $flat[] = $button;
            }
        }

        return $flat;
    }
}
