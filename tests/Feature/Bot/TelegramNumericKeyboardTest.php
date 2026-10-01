<?php

namespace Tests\Feature\Bot;

use App\Services\Bot\BotGatewayCapabilities;
use App\Services\Bot\BotLocale;
use App\Services\Bot\BotMessageFormatter;
use App\Services\Bot\BotNumericMenuStore;
use Tests\TestCase;

/**
 * Keyboard angka GLOBAL Telegram.
 *
 * Dua keputusan user yang dijaga file ini:
 *
 * 1. **Keyboard angka GLOBAL** — terkirim di setiap layar Telegram, bukan cuma
 *    layar daftar.
 * 2. **"Batal Transaksi" DIHAPUS dari keyboard** — tapi perintah `batal` TETAP
 *    berfungsi, karena jawaban konfirmasi checkout dan perintah ketik bergantung
 *    padanya. Menghapus tombol sampai ikut mematikan perintahnya adalah regresi
 *    yang jauh lebih besar daripada tombol yanghilang.
 *
 * Jumlah angka mengikuti daftar terakhir yang dilihat user. Mengirim 1..10 selalu
 * akan memberi tombol mati saat daftarnya cuma 4 item.
 */
class TelegramNumericKeyboardTest extends TestCase
{
    private function formatter(): BotMessageFormatter
    {
        return app(BotMessageFormatter::class);
    }

    private function telegram(): BotGatewayCapabilities
    {
        return BotGatewayCapabilities::forSource(BotGatewayCapabilities::SOURCE_TELEGRAM);
    }

    /** @return array<int, string> */
    private function labels(array $keyboard): array
    {
        return collect($keyboard['keyboard'])->flatten(1)->pluck('text')->all();
    }

    private function keyboardForNumbers(array $numbers): array
    {
        return $this->formatter()->numericReplyKeyboard($numbers, $this->telegram());
    }

    public function test_keyboard_angka_terkirim_lewat_formatter(): void
    {
        $keyboard = $this->keyboardForNumbers([1, 2, 3, 4]);
        $labels = $this->labels($keyboard);

        foreach (['1', '2', '3', '4'] as $number) {
            $this->assertContains($number, $labels);
        }

        $this->assertSame(true, $keyboard['resize_keyboard']);
        $this->assertSame(true, $keyboard['is_persistent']);
    }

    public function test_jumlah_angka_mengikuti_daftar_bukan_selalu_satu_sampai_sepuluh(): void
    {
        // Daftar 4 item -> maksimal '4'. Kalau '10' ikut terkirim, itu tombol
        // mati yang bikin user mengira daftarnya lebih panjang.
        $labels = $this->labels($this->keyboardForNumbers([1, 2, 3, 4]));

        $this->assertContains('4', $labels);
        $this->assertNotContains('5', $labels);
        $this->assertNotContains('10', $labels);
    }

    public function test_tombol_batal_transaksi_sudah_tidak_ada_di_keyboard(): void
    {
        // Keputusan user. Label ini masih ada di file lang (nol penghapusan),
        // tapi tidak boleh lagi dirender sebagai tombol di keyboard.
        $labels = $this->labels($this->keyboardForNumbers([1, 2]));

        $this->assertNotContains('❌ Batal Transaksi', $labels);
        $this->assertStringNotContainsString('Batal', implode(' ', $labels));

        // Tombol Bantuan berbagi baris dengan Batal. Kalau barisnya dibuang
        // seluruhnya, Bantuan ikut hilang — jadi penyaringan harus per tombol.
        $this->assertContains('❓ Bantuan', $labels);
    }

    public function test_perintah_batal_tetap_dikenali_parser_walau_tombolnya_dihapus(): void
    {
        // Menghapus tombol JANGAN sampai mematikan perintahnya: label lama masih
        // tergeletak di riwayat chat user, dan layar konfirmasi checkout memakai
        // perintah yang sama.
        $parser = app(\App\Services\Bot\BotCommandParser::class);

        $this->assertSame('batal', $parser->parse('❌ Batal Transaksi')['command']);
        $this->assertSame('batal', $parser->parse('batal')['command']);
    }

    public function test_tombol_aksi_lain_tetap_ada_di_keyboard(): void
    {
        // Angka DITAMBAHKAN, tombol lama tidak dibuang: kalau dibuang, user
        // kehilangan akses Menu/Riwayat/Bantuan dari layar.
        $labels = $this->labels($this->formatter()->defaultReplyKeyboard($this->telegram()));

        $this->assertContains('🛍️ Buka Menu', $labels);
        $this->assertContains('📜 Riwayat Order', $labels);
        $this->assertContains('📦 Cek Status', $labels);
        $this->assertContains('❓ Bantuan', $labels);
    }

    public function test_angka_di_atas_tombol_aksi(): void
    {
        $keyboard = $this->keyboardForNumbers([1, 2, 3])['keyboard'];

        $this->assertSame(['1', '2', '3'], collect($keyboard[0])->pluck('text')->all());
        $this->assertContains('🛍️ Buka Menu', collect($keyboard)->flatten(1)->pluck('text')->all());
    }

    public function test_jam_rilis_angka_tetap_muatan_seperti_sebelumnya_bila_bukan_telegram(): void
    {
        // Gate channel: reply keyboard memang cuma dipakai Telegram, tapi
        // penjagaannya harus eksplisit supaya channel lain tidak ikut kena.
        $whatsapp = $this->formatter()->defaultReplyKeyboard(
            BotGatewayCapabilities::forSource(BotGatewayCapabilities::SOURCE_WHATSAPP),
        );

        $labels = $this->labels($whatsapp);

        $this->assertNotContains('1', $labels, 'WhatsApp tidak boleh dapat tombol angka.');
        $this->assertContains('🛍️ Buka Menu', $labels);
    }

    public function test_daftar_tanpa_item_tidak_mengirim_tombol_angka(): void
    {
        $keyboard = $this->keyboardForNumbers([]);
        $labels = $this->labels($keyboard);

        // Tanpa daftar aktif tidak ada tombol angka sama sekali...
        $this->assertNotContains('1', $labels);
        $this->assertNotContains('2', $labels);

        // ...tapi keyboard aksi lama tetap terkirim, karena telegram hanya
        // mengizinkan satu `reply_markup` per pesan: menghapusnya berarti user
        // kehilangan akses Menu/Bantuan dari layar.
        $this->assertContains('🛍️ Buka Menu', $labels);
        $this->assertContains('❓ Bantuan', $labels);
    }
}
