<?php

namespace Tests\Feature\Bot;

use App\Support\TelegramMarkdown;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Aturan format Telegram.
 *
 * Semua perilaku di sini DIBUKTIKAN dengan uji langsung ke API Telegram
 * (entity dibaca dari respons API), bukan dari dokumentasi saja. Offset
 * entity Telegram dihitung dalam UTF-16, jadi pembacaan cuplikan di uji
 * manual memakai konversi UTF-16 — bukan mb_substr.
 */
class TelegramMarkdownTest extends TestCase
{
    public function test_plain_text_special_characters_are_escaped(): void
    {
        // Teks biasa wajib di-escape di MarkdownV2, kalau tidak Telegram
        // menolak pesan. Contoh nyata yang pernah membuat pengumuman GAGAL:
        // "Transaksi lewat bot @jasakoding_bot, bukan di grup ya."
        $hasil = TelegramMarkdown::format('Order lewat @jasakoding_bot ya!');

        $this->assertSame('Order lewat @jasakoding\\_bot ya\\!', $hasil);
    }

    public function test_username_keeps_its_underscore(): void
    {
        // REGRESI BUG NYATA. Percobaan pertama membuka garis bawah tunggal
        // sebagai penanda miring, dan Telegram memasangkan garis bawah
        // pertama dengan berikutnya sehingga yang DIBACA member:
        //
        //   "bot @jasakodingbot (kode promo10 tetap berlaku)"
        //
        // Garis bawah pada nama bot dan kode promo hilang.
        $hasil = TelegramMarkdown::format('@jasakoding_bot dan promo_10');

        $this->assertStringContainsString('jasakoding\\_bot', $hasil);
        $this->assertStringContainsString('promo\\_10', $hasil);
    }

    public function test_single_star_becomes_bold(): void
    {
        $this->assertSame('*tebal* biasa', TelegramMarkdown::format('*tebal* biasa'));
    }

    public function test_double_star_is_normalised_to_single_star(): void
    {
        // Admin sering menulis `**tebal**` (gaya markdown umum). Di Telegram
        // itu TIDAK berformat — tanda bintangnya justru tampil mentah.
        // Jadi dinormalkan menjadi `*tebal*` supaya benar-benar tebal.
        $this->assertSame('*tebal* biasa', TelegramMarkdown::format('**tebal** biasa'));
    }

    public function test_double_underscore_becomes_underline(): void
    {
        $this->assertSame('__garis__ biasa', TelegramMarkdown::format('__garis__ biasa'));
    }

    public function test_strikethrough_is_supported(): void
    {
        $this->assertSame('~coret~ biasa', TelegramMarkdown::format('~coret~ biasa'));
    }

    public function test_markers_work_next_to_emoji_and_punctuation(): void
    {
        // Emoji di depan penanda pernah jadi sumber keraguan saat uji manual,
        // jadi perilakunya dikunci di sini.
        //
        // Catatan: `?` BUKAN karakter spesial MarkdownV2, jadi tidak
        // di-escape. Yang di-escape hanya daftar resmi Telegram:
        // _ * [ ] ( ) ~ ` > # + - = | { } . !
        $hasil = TelegramMarkdown::format('👋 **Halo** __dunia__, apa kabar?');

        $this->assertStringContainsString('*Halo*', $hasil);
        $this->assertStringContainsString('__dunia__', $hasil);
        $this->assertStringContainsString('kabar?', $hasil);
        $this->assertStringContainsString('👋', $hasil);
        $this->assertStringNotContainsString('kabar\\?', $hasil);
    }

    public function test_full_real_world_announcement_example(): void
    {
        // Contoh nyata dari panduan grup yang sempat gagal kirim.
        $asli = "**Penting!** Cek __promo__ di @jasakoding_bot (kode promo_10).\n"
            . "Baca *Announcement* — jangan sampai terlewat!";

        $hasil = TelegramMarkdown::format($asli);

        $this->assertStringContainsString('*Penting\\!*', $hasil);
        $this->assertStringContainsString('__promo__', $hasil);
        $this->assertStringContainsString('@jasakoding\\_bot', $hasil);
        $this->assertStringContainsString('promo\\_10', $hasil);
        $this->assertStringContainsString('*Announcement*', $hasil);

        // Tidak boleh ada sisa penanda sementara (Private Use Area).
        $this->assertSame(0, preg_match('/[\x{E000}-\x{F8FF}]/u', $hasil));
    }

    #[DataProvider('karakterSpesialProvider')]
    public function test_all_special_characters_are_escaped(string $input, string $harus): void
    {
        $this->assertStringContainsString($harus, TelegramMarkdown::format($input));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function karakterSpesialProvider(): array
    {
        return [
            'titik' => ['Halo.', 'Halo\\.'],
            'seru' => ['Halo!', 'Halo\\!'],
            'tanda hubung' => ['a-b', 'a\\-b'],
            'kurung' => ['(tes)', '\\(tes\\)'],
            'plus' => ['1+1', '1\\+1'],
            'pagar' => ['#1', '\\#1'],
            'sama dengan' => ['a=b', 'a\\=b'],
            'koma atas' => ['`kode`', '\\`kode\\`'],
            'kurung siku' => ['[tes]', '\\[tes\\]'],
        ];
    }

    public function test_escape_all_handles_backslash(): void
    {
        // Backslash harus di-escape lebih dulu, kalau tidak hasilnya rusak.
        $this->assertSame('a\\\\b', TelegramMarkdown::escapeAll('a\\b'));
    }

    public function test_escape_all_removes_ability_to_add_format(): void
    {
        // Untuk nilai dari Telegram (nama member), semua penanda harus mati.
        $hasil = TelegramMarkdown::escapeAll('Budi_Pratama *hebat*');

        $this->assertStringContainsString('Budi\\_Pratama', $hasil);
        $this->assertStringContainsString('\\*hebat\\*', $hasil);
    }
}
