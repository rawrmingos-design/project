<?php

namespace App\Support;

/**
 * Format teks untuk Telegram, memakai **MarkdownV2**.
 *
 * Kenapa MarkdownV2 dan bukan Markdown lama — dibuktikan dengan uji
 * langsung ke API Telegram (entity dibaca dari respons API, offset
 * dikonversi UTF-16 sesuai satuan yang dipakai Telegram):
 *
 *   parse_mode   teks masuk         hasil
 *   ----------   ---------------    ---------------------------
 *   Markdown     *tebal* @bot_x     GAGAL (can't parse entities)
 *   MarkdownV2   *tebal*            bold   ✓
 *   MarkdownV2   **tebal**          TAMPIL MENTAH (tidak berformat)
 *   MarkdownV2   __garis__          underline  ✓
 *   MarkdownV2   ~coret~            strike  ✓
 *
 * Catatan penting: `**tebal**` TIDAK berlaku di Telegram. Yang berlaku
 * hanya `*tebal*`. Karena admin sering terbiasa menulis `**tebal**`,
 * kelas ini menormalkannya menjadi `*tebal*` supaya hasilnya benar-benar
 * tebal, bukan tanda bintang mentah.
 *
 * Masalah MarkdownV2: SEMUA karakter spesial wajib di-escape, kalau tidak
 * Telegram menolak pesan. Sementara admin justru perlu menulis penanda
 * format. Kelas ini menyelesaikan keduanya sekaligus.
 */
class TelegramMarkdown
{
    /**
     * Karakter spesial MarkdownV2 versi Telegram.
     *
     * @see https://core.telegram.org/bots/api#markdownv2-style
     */
    private const SPECIAL = '_*[]()~`>#+-=|{}.!\\';

    /**
     * Format teks yang ditulis ADMIN (template sambutan, isi pengumuman).
     *
     * Penanda format yang sengaja ditulis tetap dibuka; sisanya di-escape
     * sehingga karakter seperti `_` di `@jasakoding_bot` atau `.` di
     * kalimat biasa tidak merusak pesan.
     */
    public static function format(string $text): string
    {
        // Penanda sementara dari Private Use Area supaya mustahil bentrok
        // dengan teks admin biasa.
        $bold = "\u{E000}";
        $underline = "\u{E003}";
        $strike = "\u{E004}";

        // 1. Lindungi penanda yang sengaja ditulis.
        //    `**tebal**` dinormalkan menjadi `*tebal*` karena itu SATU-SATUNYA
        //    sintaks tebal yang berlaku di Telegram (dibuktikan dengan uji
        //    langsung: `**tebal**` justru tampil mentah dengan tanda
        //    bintangnya). Admin yang terbiasa menulis markdown gaya umum
        //    tetap mendapat hasil tebal, bukan tanda bintang mentah.
        $text = str_replace('**', $bold, $text);
        $text = str_replace('__', $underline, $text);

        $text = preg_replace('/\*([^*\n]+)\*/u', $bold . '$1' . $bold, $text);
        $text = preg_replace('/~([^~\n]+)~/u', $strike . '$1' . $strike, $text);

        // 2. Escape sisanya.
        $text = self::escapeAll($text);

        // 3. Kembalikan penanda jadi sintaks asli.
        return str_replace(
            [$underline, $bold, $strike],
            ['__', '*', '~'],
            $text,
        );
    }

    /**
     * Escape penuh — untuk nilai yang berasal dari Telegram atau data lain
     * yang tidak boleh menambah format apa pun (mis. nama member).
     *
     * Contoh: 'Budi_Pratama' -> 'Budi\_Pratama'
     */
    public static function escapeAll(string $value): string
    {
        return preg_replace_callback(
            '/[' . preg_quote(self::SPECIAL, '/') . ']/u',
            static fn (array $m): string => '\\' . $m[0],
            $value,
        ) ?? $value;
    }

    /**
     * Catatan penting — underscore TUNGGAL sengaja TIDAK dibuka sebagai
     * penanda miring.
     *
     * Percobaan pertama membukanya, dan hasilnya terlihat saat uji ke grup
     * asli. Teks yang dibaca member jadi rusak:
     *
     *   asli   : "bot @jasakoding_bot (kode promo_10 tetap berlaku)"
     *   dilihat: "bot @jasakodingbot (kode promo10 tetap berlaku)"
     *
     * Telegram memasangkan garis bawah pertama dengan garis bawah
     * berikutnya, jadi nama bot dan kode promo kehilangan garis bawahnya.
     * Karena teks bebas admin penuh dengan pola seperti itu, miring
     * tunggal dikorbankan. Alternatif yang tersedia:
     *
     *   *tebal*         tebal
     *   **tebal**       tebal (dinormalkan otomatis)
     *   __garis bawah__ garis bawah
     *   ~coret~         coret
     *
     * Tidak dibuka juga: `[teks](url)` karena butuh URL asli, serta
     * `>kutipan`, `||spoiler||`, `` `kode` `` yang artefaknya berbeda di
     * MarkdownV2. Lebih baik tampil apa adanya daripada berubah arti
     * diam-diam.
     */
}
