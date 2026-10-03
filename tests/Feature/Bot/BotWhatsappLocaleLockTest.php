<?php

namespace Tests\Feature\Bot;

use App\Models\SettingWeb;
use App\Services\Bot\BotGatewayCapabilities;
use App\Services\Bot\BotMessageFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Kunci "WhatsApp selalu Indonesia".
 *
 * Keputusan produk: bot WhatsApp TIDAK punya pemilih bahasa dan tidak ikut
 * locale proses. Telegram boleh ikut `app()->getLocale()`; WhatsApp memakai
 * copy Indonesia yang dibekukan.
 *
 * Kenapa test ini ada:
 * `__()` membaca locale AKTIF, dan locale itu bisa berubah di luar kendali bot
 * — sisa job antrean, header `Accept-Language`, atau `setLocale()` dari jalur
 * lain di request yang sama. Kalau ada SATU saja situs copy WA yang lupa
 * digerbang dengan `$isTelegram`, pesannya jadi campur bahasa: sapaan Inggris
 * di atas daftar kategori Indonesia, atau sebaliknya.
 *
 * Cara menguji yang benar: render perintah yang SAMA di locale `id` dan `en`,
 * lalu bandingkan. Beda apa pun = kebocoran, tanpa perlu tahu kunci lang mana
 * yang bocor atau kalimat apa yang berubah di masa depan. Test yang mengunci
 * kalimat tertentu akan rapuh dan hanya menangkap situs yang sudah diketahui;
 * diff ini menangkap situs yang BELUM diketahui.
 *
 * Kalau test ini merah, artinya jalur WhatsApp ikut bahasa perangkat. Itu
 * regresi produk, bukan sekadar "kebetulan bahasa Inggris".
 */
class BotWhatsappLocaleLockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        SettingWeb::query()->create([
            'id' => 1,
            'judul_web' => 'Test Store',
            'deskripsi_web' => 'Deskripsi test',
            'keywords' => 'test',
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
            'paydisini_apikey' => 'dummy',
            'order_prefik' => 'INV',
            'public_theme' => 'default',
        ]);

        config(['app.name' => 'Test Store']);
    }

    /** Payload katalog minimal: dua kategori + satu item menu bernomor. */
    private function catalogPayload(): array
    {
        return [
            'ok' => true,
            'data' => [
                ['id' => 21, 'code' => 'alight-motion-vip', 'name' => 'Alight Motion', 'type' => 'app'],
                ['id' => 22, 'code' => 'free-fire', 'name' => 'Free Fire', 'type' => 'game'],
            ],
        ];
    }

    private function wa(): BotGatewayCapabilities
    {
        return BotGatewayCapabilities::forSource(BotGatewayCapabilities::SOURCE_WHATSAPP);
    }

    private function tg(): BotGatewayCapabilities
    {
        return BotGatewayCapabilities::forSource(BotGatewayCapabilities::SOURCE_TELEGRAM);
    }

    /** Render di locale tertentu, kembalikan teks+tombol sebagai satu string. */
    private function renderAt(string $locale, callable $render): string
    {
        app()->setLocale($locale);

        return $render();
    }

    private function flatten(array $result): string
    {
        $out = (string) ($result['text'] ?? '');

        foreach (($result['buttons'] ?? []) as $row) {
            if (isset($row['text'])) {
                $out .= "\nBTN:" . $row['text'];

                continue;
            }

            if (is_array($row)) {
                foreach ($row as $button) {
                    $out .= "\nBTN:" . ($button['text'] ?? '');
                }
            }
        }

        return $out;
    }

    public function test_menu_whatsapp_identik_di_locale_id_dan_en(): void
    {
        $fmt = app(BotMessageFormatter::class);

        $id = $this->renderAt('id', fn () => $this->flatten($fmt->formatCategories($this->catalogPayload(), 1, $this->wa())));
        $en = $this->renderAt('en', fn () => $this->flatten($fmt->formatCategories($this->catalogPayload(), 1, $this->wa())));

        $this->assertSame($id, $en, 'Menu WhatsApp ikut locale proses — harus dibekukan Indonesia.');

        // Dan memang Indonesia, bukan kebetulan sama-sama kosong.
        $this->assertStringContainsString('🏠 *Menu Utama*', $id);
        $this->assertStringContainsString('Selamat datang di Test Store', $id);
        $this->assertStringNotContainsString('Main Menu', $id);
        $this->assertStringNotContainsString('Welcome to', $id);
    }

    public function test_panduan_whatsapp_identik_di_locale_id_dan_en(): void
    {
        $fmt = app(BotMessageFormatter::class);

        $id = $this->renderAt('id', fn () => $this->flatten($fmt->formatHelp($this->wa())));
        $en = $this->renderAt('en', fn () => $this->flatten($fmt->formatHelp($this->wa())));

        $this->assertSame($id, $en, 'Panduan WhatsApp ikut locale proses — harus dibekukan Indonesia.');
        $this->assertStringNotContainsString('How to Order', $id);
        $this->assertStringNotContainsString('Order History', $id);
    }

    public function test_telegram_tetap_mengikuti_locale(): void
    {
        // Kontra-test: gerbang WA tidak boleh ikut membekukan Telegram.
        $fmt = app(BotMessageFormatter::class);

        $id = $this->renderAt('id', fn () => $this->flatten($fmt->formatCategories($this->catalogPayload(), 1, $this->tg())));
        $en = $this->renderAt('en', fn () => $this->flatten($fmt->formatCategories($this->catalogPayload(), 1, $this->tg())));

        $this->assertNotSame($id, $en, 'Telegram seharusnya tetap ikut locale user.');
        // Jalur Telegram memakai layar daftar bernomor (`menu_list_title`), bukan
        // sapaan `storeIntro`, jadi yang berbeda di sini adalah judul daftarnya.
        $this->assertStringContainsString('LIST PRODUCT', $id);
        $this->assertStringContainsString('PRODUCT LIST', $en);
    }

    public function test_render_whatsapp_tidak_merusak_locale_proses(): void
    {
        // Gerbang WA memakai setLocale('id') sementara. Kalau tidak dikembalikan,
        // permintaan setelahnya ikut Indonesia — bocor ke jalur Telegram di
        // request yang sama.
        $fmt = app(BotMessageFormatter::class);

        app()->setLocale('en');
        $fmt->formatHelp($this->wa());

        $this->assertSame('en', app()->getLocale(), 'Locale proses tidak dikembalikan setelah render WhatsApp.');
    }

    public function test_label_tombol_panduan_whatsapp_indonesia_walau_locale_en(): void
    {
        // `buttonNamePlaceholders()` membaca `__()` dari locale aktif dan dipakai
        // baik oleh keyboard tetap maupun panduan. Tanpa pin, panduan WA
        // menyisipkan nama tombol Inggris ('📜 Order History') ke tengah teks
        // Indonesia.
        $fmt = app(BotMessageFormatter::class);

        $en = $this->renderAt('en', fn () => $this->flatten($fmt->formatHelp($this->wa())));

        $this->assertStringNotContainsString('Order History', $en);
        $this->assertStringContainsString('Riwayat Order', $en);
    }
}
