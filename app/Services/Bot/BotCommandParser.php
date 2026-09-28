<?php

namespace App\Services\Bot;

class BotCommandParser
{
    /**
     * Peta PERINTAKAN KANONIK → semua label yang memicunya.
     *
     * Kenapa label, bukan ID aksi: Telegram reply keyboard dan tombol teks
     * mengirim LABEL-nya kembali sebagai pesan biasa, jadi label ITU sendiri
     * adalah perintah. Konsekuensinya, menerjemahkan label tanpa memperbarui
     * peta ini akan MEMATIKAN tombol — pesan masuk jatuh ke `default` dan
     * dianggap perintah tak dikenal.
     *
     * Aturan saat menambah bahasa (mis. Fase 3):
     *  1. Tambahkan varian label baru DI SINI LEBIH DULU, deploy, baru ubah
     *     label yang dirender formatter. Urutan terbalik = tombol mati.
     *  2. Jangan hapus varian lama. Label pernah terkirim ke riwayat chat user
     *     dan tombolnya MASIH tergeletak di sana; kalau variannya hilang,
     *     menekannya jadi perintah tak dikenal.
     *
     * Pencocokan bersifat EXACT pada pesan yang sudah di-`trim()` — bukan
     * substring. Jadi `Buka Menu` (tanpa emoji) tetap bukan perintah `menu`;
     * ia dipecah seperti perintah biasa. Ada test yang mengunci ini.
     *
     * @var array<string, array<int, string>>
     */
    public const LABELS = [
        'menu' => [
            '🛍️ Buka Menu',
            // Pesan bot yang LEBIH DULU terkirim menulis "🛍️ Tampilkan Menu /
            // Produk". Tombolnya masih tergeletak di riwayat chat user, jadi
            // tulisan lama itu tetap harus dikenali — kalau tidak, menekannya
            // justru dianggap perintah tak dikenal.
            '🛍️ Tampilkan Menu / Produk',
            '🛍️ Open Menu',
        ],
        'status' => [
            '🔎 Cek Status',
            '📦 Cek Status',
            '📦 Check Status',
        ],
        'cekid' => [
            '🔍 Cek ID Game',
            '🔍 Check Game ID',
        ],
        'help' => [
            '❓ Bantuan',
            '❓ Help',
        ],
        'batal' => [
            '❌ Batal Transaksi',
            '❌ Cancel Order',
        ],
        'admin' => [
            '📞 Hubungi Admin',
            '📞 Contact Admin',
        ],
        'leaderboard' => [
            '🏆 Leaderboard',
        ],
        'deposit' => [
            '💰 Deposit',
        ],
        'order_history' => [
            '📜 Riwayat Order',
            '📜 Order History',
        ],
        'account_status' => [
            '📲 Status WhatsApp',
            '📲 WhatsApp Status',
        ],
        'telegram_status' => [
            '📲 Status Telegram',
            '📲 Telegram Status',
        ],
        // Jawaban state checkout DIKETIK user, bukan tombol: prompt registrasi
        // berbunyi "Ketik *YA* untuk daftar sekarang, atau *TIDAK* untuk
        // batalkan." Karena itu varian huruf kecil ikut didaftarkan — user
        // yang mengetik `yes` tidak boleh tertahan di prompt yang sama.
        // Kalau label ini tidak dikenali, pendaftaran macet TANPA pesan error.
        'ya' => ['YA', 'ya', 'YES', 'yes'],
        'tidak' => ['TIDAK', 'tidak', 'NO', 'no'],
        'skip' => ['SKIP', 'skip'],
        // --- Pengaturan bahasa (Task 3.1) ---
        // Label picker bahasa DIKETIK-BALIK seperti label keyboard lain: tap-nya
        // mengirim TEKS, bukan callback. Jadi keempatnya wajib dikenali parser,
        // kalau tidak user menekan pilihan bahasa dan bot menjawab "perintah tak
        // dikenal" — persis kegagalan yang fase ini ada untuk mencegahnya.
        // Label callback-driven murni (mis. `🌐 Bahasa`, `🇬🇧 English` yang
        // dikirim sebagai `data`) tetap TIDAK perlu masuk sini.
        // TIGA varian, karena nama tombol "pindah ke Indonesia" bergantung pada
        // bahasa yang sedang aktif: `🇮🇩 Bahasa` saat locale id, `🇮🇩 Indonesian`
        // saat locale en. Keduanya mengirim TEKS yang sama-sama harus dikenali.
        // (Ketinggalan varian ini langsung ketahuan dari test penjaga Fase 2.)
        'bahasa_id' => ['🇮🇩 Bahasa', '🇮🇩 Bahasa Indonesia', '🇮🇩 Indonesian'],
        'bahasa_en' => ['🇬🇧 English'],
    ];

    /** @var array<string, string>|null Peta terbalik label → perintah kanonik. */
    private static ?array $reverseMap = null;

    /**
     * Parse raw text message into a structured command and arguments.
     *
     * @param string $message
     * @return array{command: string|null, args: array<int, string>}
     */
    public function parse(string $message): array
    {
        $message = trim($message);

        // Label tombol → perintah kanonik. Tidak ketemu = biarkan apa adanya;
        // perintah yang diketik user (`invoice ...`, `menu`) bukan label.
        $canonical = self::reverseMap()[$message] ?? null;

        if ($canonical !== null) {
            $message = $canonical;
        }

        if ($message === '') {
            return ['command' => null, 'args' => []];
        }

        // Split by spaces, treating multiple spaces as single space
        $parts = preg_split('/\s+/', $message);

        if (empty($parts)) {
            return ['command' => null, 'args' => []];
        }

        // The first part is the command, lowercased, and without leading slashes (e.g. /start -> start)
        $command = strtolower(ltrim(array_shift($parts), '/'));

        return [
            'command' => $command !== '' ? $command : null,
            'args' => $parts,
        ];
    }

    /**
     * Semua varian label untuk satu perintah kanonik.
     *
     * Dipakai fase berikutnya untuk merender tombol dalam bahasa aktif dan
     * untuk memastikan setiap label yang dirender benar-benar dikenali parser.
     *
     * @return array<int, string>
     */
    public static function labelsFor(string $command): array
    {
        return self::LABELS[$command] ?? [];
    }

    /**
     * Apakah teks ini persis salah satu label yang dikenal?
     *
     * Bedakan dengan `parse()`: teks yang diketik user seperti `invoice 1 QRIS`
     * juga menghasilkan `command`, sedangkan method ini hanya true untuk label
     * tombol yang benar-benar terdaftar.
     */
    public static function isKnownLabel(string $message): bool
    {
        return isset(self::reverseMap()[trim($message)]);
    }

    /**
     * @return array<string, string>
     */
    /**
     * Apakah teks ini label yang dikenal — kapan pun jadi perintah yang mana?
     *
     * Dipakai renderer untuk MEMVERIFIKASI tombol yang akan dikirim. Bedanya
     * dengan `isKnownLabel()`: di sini label boleh sudah dicadangkan untuk
     * bahasa/perintah lain. Selama tombolnya mengirim TEKS dan parser mengenali
     * teks itu, tap-nya tidak akan pernah jadi "perintah tak dikenal" — dan
     * itulah satu-satunya hal yang membuat tombol aman dipasang di reply
     * keyboard.
     *
     * Perbedaan ini nyata: `🇮🇩 Indonesian` dicadangkan untuk `bahasa_id`, tapi
     * kegunaannya yang sebenarnya adalah tombol "pindah ke Indonesia" saat
     * bahasa aktif `en`. Pemetaan statis per-locale tidak bisa menyatakannya.
     */
    public static function anyLabel(string $message): bool
    {
        return isset(self::reverseMap()[trim($message)]);
    }

    private static function reverseMap(): array
    {
        if (self::$reverseMap === null) {
            $map = [];

            foreach (self::LABELS as $canonical => $labels) {
                foreach ($labels as $label) {
                    $map[$label] = $canonical;
                }
            }

            self::$reverseMap = $map;
        }

        return self::$reverseMap;
    }
}
