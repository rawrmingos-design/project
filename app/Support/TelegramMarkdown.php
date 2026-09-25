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
     * Konversi teks bergaya Markdown LAMA (yang dipakai BotMessageFormatter)
     * menjadi MarkdownV2 yang valid.
     *
     * Kenapa perlu: `parse_mode=Markdown` lama menerima `.`, `(`, `!`, `|`,
     * `+`, `-`, `=` tanpa escape, sedangkan MarkdownV2 MENOLAK pesan yang
     * memuat karakter itu tanpa backslash. Diuji langsung ke API: 7 dari 12
     * teks bot gagal total kalau dikirim mentah ke MarkdownV2.
     *
     * Karena itu penanda format (`*tebal*`, `` `kode` ``, `__garis__`,
     * `~coret~`) dipertahankan, sedangkan sisanya di-escape. Isi `` `kode` ``
     * di-escape dengan aturan berbeda (hanya `\` dan backtick) supaya titik
     * dan tanda hubung di dalamnya tampil apa adanya.
     */
    public static function fromLegacy(string $text): string
    {
        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $n = count($chars);

        $out = '';
        $plain = '';

        $flush = static function () use (&$plain, &$out): void {
            if ($plain !== '') {
                $out .= self::escapeAll($plain);
                $plain = '';
            }
        };

        for ($i = 0; $i < $n; $i++) {
            $ch = $chars[$i];

            // 1. Escape gaya lama (mis. `\_`) dibuka kembali — nanti di-escape
            //    ulang memakai aturan MarkdownV2, jadi hasilnya tetap benar.
            if ($ch === '\\' && $i + 1 < $n) {
                $plain .= $chars[$i + 1];
                $i++;

                continue;
            }

            // 2. Kode: `` `...` `` (isi hanya boleh escape `\` dan backtick).
            if ($ch === '`') {
                $end = self::findClosing($chars, $i + 1, '`');

                if ($end !== null && $end > $i + 1) {
                    $flush();
                    $inner = implode('', array_slice($chars, $i + 1, $end - $i - 1));
                    $out .= '`' . self::escapeCode($inner) . '`';
                    $i = $end;

                    continue;
                }
            }

            // 3. Tebal `*...*`, coret `~...~`, garis bawah `__...__`.
            $marker = null;

            if ($ch === '*' || $ch === '~') {
                $marker = $ch;
            } elseif ($ch === '_' && ($chars[$i + 1] ?? '') === '_') {
                $marker = '__';
            }

            if ($marker !== null) {
                $open = $i + strlen($marker);
                $end = $marker === '__'
                    ? self::findClosing($chars, $open + 1, '_', 2)
                    : self::findClosing($chars, $open, $marker);

                if ($end !== null && $end > $i + strlen($marker) - 1) {
                    $flush();
                    $inner = implode('', array_slice($chars, $open, $end - $open));
                    $out .= $marker . self::escapeAll($inner) . $marker;
                    $i = $end + strlen($marker) - 1;

                    continue;
                }
            }

            $plain .= $ch;
        }

        $flush();

        return $out;
    }

    /**
     * Cari posisi penutup penanda, dengan menghormati escape.
     *
     * @param  list<string>  $chars
     * @return int|null indeks karakter pembuka penutup
     */
    private static function findClosing(array $chars, int $from, string $char, int $repeat = 1): ?int
    {
        $total = count($chars);

        for ($i = $from; $i < $total; $i++) {
            if ($chars[$i] === '\\') {
                $i++;

                continue;
            }

            if ($chars[$i] !== $char) {
                continue;
            }

            for ($k = 1; $k < $repeat; $k++) {
                if (($chars[$i + $k] ?? null) !== $char) {
                    continue 2;
                }
            }

            return $i;
        }

        return null;
    }

    /**
     * Escape isi blok kode. Di dalam `code`/`pre`, Telegram hanya mewajibkan
     * escape untuk `\` dan backtick — karakter lain harus dibiarkan apa
     * adanya, kalau tidak akan tampil sebagai `\.` di layar user.
     */
    private static function escapeCode(string $value): string
    {
        return str_replace(['\\', '`'], ['\\\\', '\\`'], $value);
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
