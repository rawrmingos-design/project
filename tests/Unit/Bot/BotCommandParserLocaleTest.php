<?php

namespace Tests\Unit\Bot;

use App\Services\Bot\BotCommandParser;
use Tests\TestCase;

/**
 * Fase 2 — parser tahan lintas bahasa.
 *
 * Inti pengamanannya: label tombol Telegram dikirim BALIK sebagai teks saat
 * user menekannya, jadi label ITU sendiri adalah perintah. Kalau peta label
 * hanya mengenal satu bahasa, semua tombol di bahasa lain mati — user menekan
 * "Open Menu", bot menganggapnya perintah tak dikenal, dan alur order putus.
 */
class BotCommandParserLocaleTest extends TestCase
{
    private BotCommandParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new BotCommandParser();
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function labelProvider(): array
    {
        $cases = [];

        foreach (BotCommandParser::LABELS as $canonical => $labels) {
            foreach ($labels as $label) {
                $cases["{$canonical}: {$label}"] = [$label, $canonical];
            }
        }

        return $cases;
    }

    /**
     * SEMUA label terdaftar harus memetakan ke perintah kanoniknya.
     *
     * @dataProvider labelProvider
     */
    public function test_setiap_label_terdaftar_dikenali(string $label, string $canonical): void
    {
        $result = $this->parser->parse($label);

        $this->assertSame($canonical, $result['command'], "Label '{$label}' tidak dikenali.");
        $this->assertSame([], $result['args'], "Label '{$label}' tidak boleh menghasilkan argumen.");
    }

    /**
     * Label LAMA tetap dikenali. Ini bukan kehati-hatian teoretis: label sudah
     * terkirim ke riwayat chat dan tombolnya masih tergeletak di sana. Kalau
     * variannya hilang, user lama tidak bisa membuka menu.
     */
    public function test_label_legacy_tetap_dikenali(): void
    {
        // Generasi pertama tombol menu.
        $this->assertSame('menu', $this->parser->parse('🛍️ Tampilkan Menu / Produk')['command']);
        // Dua generasi tombol status.
        $this->assertSame('status', $this->parser->parse('🔎 Cek Status')['command']);
        $this->assertSame('status', $this->parser->parse('📦 Cek Status')['command']);
    }

    /** Setelah ganti bahasa, tombol baru HARUS hidup — inilah anti-salah-flow. */
    public function test_tombol_bahasa_inggris_membuka_menu(): void
    {
        $result = $this->parser->parse('🛍️ Open Menu');

        $this->assertSame('menu', $result['command']);
        $this->assertSame([], $result['args']);
    }

    /** Setiap jawaban state harus dikenali, kalau tidak pendaftaran macet. */
    public function test_jawaban_state_registrasi_dua_bahasa(): void
    {
        foreach (['YA', 'ya', 'YES', 'yes'] as $label) {
            $this->assertSame('ya', $this->parser->parse($label)['command'], $label);
        }

        foreach (['TIDAK', 'tidak', 'NO', 'no'] as $label) {
            $this->assertSame('tidak', $this->parser->parse($label)['command'], $label);
        }

        foreach (['SKIP', 'skip'] as $label) {
            $this->assertSame('skip', $this->parser->parse($label)['command'], $label);
        }
    }

    /**
     * Yang diketik user bukan label — jangan sampai termakan.
     *
     * Ini uji NEGATIF yang paling penting: `yes/no/ya/tidak` memakai kata umum
     * yang bisa muncul sebagai masukan sah (username, ID game, nama produk).
     * Pencocokan harus EXACT, bukan substring.
     */
    public function test_input_bebas_tidak_termakan_peta_label(): void
    {
        // Kata yang MENGANDUNG label tapi bukan label.
        $this->assertNotSame('ya', $this->parser->parse('Yes Man')['command']);
        $this->assertSame('yes', $this->parser->parse('Yes Man')['command']);
        $this->assertSame(['Man'], $this->parser->parse('Yes Man')['args']);

        // Token pertama di-lowercase sejak dulu (perilaku lama, bukan efek
        // Fase 2) — jadi `Tidak Ada` memang ber-command `tidak`. Yang penting:
        // teks ini BUKAN label, dan argumennya tidak ikut termakan.
        $this->assertFalse(BotCommandParser::isKnownLabel('Tidak Ada'));
        $this->assertSame(['Ada'], $this->parser->parse('Tidak Ada')['args']);

        // Label tanpa emoji bukan label (pencocokan exact, bukan substring).
        $this->assertSame('buka', $this->parser->parse('Buka Menu')['command']);
        $this->assertSame(['Menu'], $this->parser->parse('Buka Menu')['args']);

        // Nama produk / username yang kebetulan sama dengan kata label lain.
        $this->assertSame('depositgame', $this->parser->parse('depositgame')['command']);
        $this->assertNotSame('deposit', $this->parser->parse('💰 Deposit 50000')['command']);
    }

    /** Perintah yang diketik user TETAP berfungsi (bukan prosa, jangan diterjemahkan). */
    public function test_perintah_ketikan_tetap_berfungsi(): void
    {
        $this->assertSame('menu', $this->parser->parse('/menu')['command']);
        $this->assertSame('menu', $this->parser->parse('MENU')['command']);
        $this->assertSame('start', $this->parser->parse('/start')['command']);

        $invoice = $this->parser->parse('invoice 1 QRIS uid123');
        $this->assertSame('invoice', $invoice['command']);
        $this->assertSame(['1', 'QRIS', 'uid123'], $invoice['args']);

        // Alias bahasa Indonesia tetap jalan (bukan label, tapi perintah teks).
        $this->assertSame('bantuan', $this->parser->parse('bantuan')['command']);
        $this->assertSame('pesanan', $this->parser->parse('pesanan')['command']);
    }

    /** Setiap label yang dipetakan harus unik — tabrakan = perintah salah. */
    public function test_tidak_ada_label_ganda(): void
    {
        $seen = [];

        foreach (BotCommandParser::LABELS as $canonical => $labels) {
            $this->assertNotEmpty($labels, "Perintah '{$canonical}' tidak punya label.");

            foreach ($labels as $label) {
                $duplikat = $seen[$label] ?? null;

                $this->assertNull(
                    $duplikat,
                    "Label '{$label}' dipetakan dua kali: '{$duplikat}' dan '{$canonical}'.",
                );

                $seen[$label] = $canonical;
            }
        }
    }

    /**
     * Setiap label toolbar yang DIRENDER formatter harus dikenali parser.
     *
     * Ini jaring yang mencegah tombol mati: kalau seseorang menambah tombol ke
     * `defaultReplyKeyboard()` tanpa mendaftarkan labelnya di parser, test ini
     * gagal — bukan user yang menemukannya lewat tombol yang tidak merespons.
     */
    public function test_seluruh_label_reply_keyboard_dikenali_parser(): void
    {
        $keyboard = app(\App\Services\Bot\BotMessageFormatter::class)->defaultReplyKeyboard();

        $jumlah = 0;

        foreach ($keyboard['keyboard'] as $row) {
            foreach ($row as $button) {
                $label = (string) ($button['text'] ?? '');
                $this->assertNotSame('', $label);

                $this->assertTrue(
                    BotCommandParser::isKnownLabel($label),
                    "Label toolbar '{$label}' dirender ke user tapi TIDAK dikenali parser — tombolnya akan mati.",
                );

                $jumlah++;
            }
        }

        // Sanity: kalau struktur keyboard berubah dan loop tidak jalan, test ini
        // tidak boleh lulus diam-diam. 7 = tombol wajib (Buka Menu, Cek Status,
        // Cek ID Game, Bantuan, Batal Transaksi) saat fitur opsional
        // (leaderboard/riwayat/deposit) dimatikan di lingkungan test.
        $this->assertGreaterThanOrEqual(5, $jumlah, 'Keyboard tidak memuat tombol yang diharapkan.');
    }

    /** Helper publik harus konsisten dengan perilaku `parse()`. */
    public function test_helper_labels_for_dan_is_known_label(): void
    {
        $this->assertContains('🛍️ Buka Menu', BotCommandParser::labelsFor('menu'));
        $this->assertContains('🛍️ Open Menu', BotCommandParser::labelsFor('menu'));
        $this->assertSame([], BotCommandParser::labelsFor('perintah_yang_tidak_ada'));

        $this->assertTrue(BotCommandParser::isKnownLabel('🛍️ Open Menu'));
        $this->assertTrue(BotCommandParser::isKnownLabel('  YA  '));
        $this->assertFalse(BotCommandParser::isKnownLabel('Buka Menu'));
        $this->assertFalse(BotCommandParser::isKnownLabel('invoice 1 QRIS uid123'));
    }

    /**
     * Label yang TIDAK terdaftar tidak boleh "diam-diam" jadi perintah.
     *
     * Dulu test lama memakai ini sebagai bukti parser-driven; ternyata label
     * tombol inline mengirim CALLBACK, bukan labelnya, jadi ketidakhadiran
     * mereka di peta ini memang benar. Dikunci supaya tidak ada yang menambah
     * label callback ke `LABELS` tanpa alasan.
     */
    public function test_label_callback_driven_bukan_perintah_teks(): void
    {
        foreach (['✅ Sudah Bergabung', 'Coba Lagi', '❌ Batal', '✅ Konfirmasi', '🔙 Kembali'] as $label) {
            $this->assertFalse(
                BotCommandParser::isKnownLabel($label),
                "'{$label}' dikirim sebagai callback, bukan label — tidak boleh masuk LABELS.",
            );
        }
    }
}
