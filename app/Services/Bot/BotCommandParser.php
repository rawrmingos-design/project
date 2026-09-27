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
