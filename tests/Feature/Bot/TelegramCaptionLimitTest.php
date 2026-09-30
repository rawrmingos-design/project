<?php

namespace Tests\Feature\Bot;

use App\Services\Bot\Adapters\TelegramAdapter;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Telegram memakai batas BERBEDA untuk caption gambar (`sendPhoto`: **1024**
 * karakter) dan teks biasa (`sendMessage`: 4096), dan pelanggarannya FATAL:
 * pesan dengan caption kepanjangan ditolak SELURUHNYA, bukan dipotong.
 *
 * Di bot ini gambar dipakai untuk hal yang menyentuh uang -- QR pembayaran.
 * Jadi caption yang melebihi batas bukan sekadar "gambar gagal tampil": user
 * tidak menerima QR-nya DAN tidak menerima teksnya. Diukur langsung dari kode
 * formatter: invoice dengan nama produk panjang menghasilkan caption 1103
 * karakter, dan pesan seperti itu hilang total.
 *
 * Batas ini dihitung Telegram dalam satuan **UTF-16**, bukan karakter: emoji
 * (lazim di teks bot) bernilai 2 unit. Menghitung dengan `mb_strlen` membuat
 * pesan penuh emoji dianggap lebih pendek daripada kenyataannya.
 */
class TelegramCaptionLimitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['services.telegram-bot-api.token' => 'test-token']);

        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 1]]),
        ]);
    }

    /** Panggil `sendReply` yang bersifat private lewat reflection. */
    private function send(array $response): void
    {
        $adapter = app(TelegramAdapter::class);
        $method = new \ReflectionMethod($adapter, 'sendReply');
        $method->setAccessible(true);
        $method->invoke($adapter, 12345, $response);
    }

    /** Payload yang benar-benar dikirim ke Telegram (yang pertama berhasil). */
    private function sentPayload(): array
    {
        $recorded = Http::recorded();

        $this->assertNotEmpty($recorded, 'Tidak ada request yang dikirim ke Telegram.');

        return $recorded[0][0]->data();
    }

    private function utf16(string $value): int
    {
        return (int) (strlen(mb_convert_encoding($value, 'UTF-16LE', 'UTF-8')) / 2);
    }

    public function test_caption_wajar_tetap_dikirim_sebagai_gambar(): void
    {
        $this->send([
            'text' => str_repeat('Teks pendek. ', 20), // ~240 unit, jauh di bawah batas
            'photo_url' => 'https://istanatopup.com/assets/bot/banner.webp',
            'buttons' => [],
        ]);

        $payload = $this->sentPayload();

        $this->assertArrayHasKey('photo', $payload, 'Gambar harus tetap dikirim.');
        $this->assertArrayHasKey('caption', $payload);
        $this->assertArrayNotHasKey('text', $payload);
    }

    /**
     * Kasus yang terbukti muncul di produksi: caption 1103 unit. Sebelum guard,
     * Telegram menolak seluruh pesan dan user kehilangan QR pembayarannya.
     */
    public function test_caption_melebihi_batas_dikirim_sebagai_teks_utuh(): void
    {
        $text = str_repeat('x', 1103);

        $this->send([
            'text' => $text,
            'photo_url' => 'https://api.qrserver.com/v1/create-qr-code/?data=QRIS',
            'buttons' => [],
        ]);

        $payload = $this->sentPayload();

        $this->assertArrayNotHasKey(
            'photo',
            $payload,
            'Gambar harus DILEPAS supaya pesan tetap sampai; caption 1103 ditolak Telegram.'
        );
        $this->assertArrayHasKey('text', $payload, 'Teks harus dikirim lewat sendMessage.');
        $this->assertSame(
            $text,
            $payload['text'],
            'Teks harus UTUH: batas sendMessage 4096, jadi tidak ada alasan memotongnya.'
        );
    }

    public function test_emoji_dihitung_dua_unit_seperti_telegram(): void
    {
        // 600 emoji = 600 karakter (mb_strlen) tapi 1200 unit UTF-16 di mata
        // Telegram. Menghitung dengan mb_strlen akan meloloskan pesan ini dan
        // Telegram tetap menolaknya.
        $text = str_repeat('😀', 600);

        $this->assertLessThan(1024, mb_strlen($text), 'Prasyarat test: mb_strlen terlihat aman.');
        $this->assertGreaterThan(1024, $this->utf16($text), 'Prasyarat test: UTF-16 melewati batas.');

        $this->send([
            'text' => $text,
            'photo_url' => 'https://istanatopup.com/assets/bot/banner.webp',
            'buttons' => [],
        ]);

        $this->assertArrayNotHasKey(
            'photo',
            $this->sentPayload(),
            'Batas caption dihitung UTF-16; 600 emoji sudah melewati 1024.'
        );
    }

    public function test_teks_sangat_panjang_dipotong_agar_tetap_sampai(): void
    {
        // Lebih dari batas pesan biasa (4096) pun ditolak Telegram. Dipotong di
        // batas, bukan dibiarkan hilang.
        $this->send([
            'text' => str_repeat('y', 5000),
            'buttons' => [],
        ]);

        $payload = $this->sentPayload();

        $this->assertArrayHasKey('text', $payload);
        $this->assertLessThanOrEqual(
            4096,
            $this->utf16($payload['text']),
            'Teks melebihi 4096 unit akan ditolak Telegram.'
        );
        $this->assertStringEndsWith('…', $payload['text'], 'Harus jelas bahwa teksnya dipotong.');
    }

    public function test_pemotongan_tidak_membelah_emoji(): void
    {
        $this->send([
            'text' => str_repeat('😀', 3000), // 6000 unit
            'buttons' => [],
        ]);

        $text = $this->sentPayload()['text'];

        $this->assertLessThanOrEqual(4096, $this->utf16($text), 'Melebihi batas pesan Telegram.');
        // Surrogate setengah (karakter rusak) tampil sebagai pengganti U+FFFD.
        $this->assertStringNotContainsString(
            "\u{FFFD}",
            $text,
            'Emoji tidak boleh terbelah di tengah pasangan surrogate.'
        );
    }

    public function test_banner_menu_utama_yang_pendek_tidak_terpengaruh(): void
    {
        // Regresi yang paling mungkin dari perubahan ini: guard terlalu agresif
        // sehingga banner menu normal (yang memang tujuannya) ikut dilepas.
        $this->send([
            'text' => "Halo! Selamat datang 👋\n\n🏠 Menu Utama\nPilih kategori di bawah untuk mulai. 👇",
            'photo_url' => 'https://istanatopup.com/assets/bot/menu-banner.webp',
            'buttons' => [],
        ]);

        $payload = $this->sentPayload();

        $this->assertArrayHasKey('photo', $payload, 'Banner menu pendek harus tetap terkirim.');
    }
}
