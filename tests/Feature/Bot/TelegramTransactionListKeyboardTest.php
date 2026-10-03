<?php

namespace Tests\Feature\Bot;

use App\Models\InboundSourcePolicy;
use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Services\Bot\BotMessageFormatter;
use App\Services\Bot\BotNumericMenuStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * LAYAR TRANSAKSI TELEGRAM: TANPA DERETAN ANGKA, KEYBOARD TETAP ADA.
 *
 * Masalah aslinya (permintaan pemilik produk): deretan angka `1 2 3 4 5`
 * muncul DUA kali di satu layar -
 *
 *   1. tombol inline di dalam gelembung pesan (`status <invoice>`), dan
 *   2. keyboard bawah, yang sebenarnya SISA dari daftar sebelumnya.
 *
 * Yang kedua bukan cuma pengulangan visual: angka itu menunjuk ke entri daftar
 * LAMA, jadi menekannya membuka kategori/layanan yang sudah tidak terlihat -
 * bukan order yang sedang dipajang. Dua mekanisme untuk satu niat.
 *
 * Sekarang satu mekanisme saja: keyboard aksi tetap di bawah (permintaan
 * eksplisit: "hapus tombolnya, tapi keyboardnya dipertahankan"), detail dibuka
 * dengan mengetik `status <invoice>`. WhatsApp TIDAK ikut berubah - di sana
 * tombol nomornya memang sarana utama memilih.
 */
class TelegramTransactionListKeyboardTest extends TestCase
{
    use RefreshDatabase;

    private const TG_IP = '149.154.160.10';

    private const FROM = 6252007210;

    /** Jumlah order di fixture. 11 order / 5 per halaman = 3 halaman. */
    private const ORDER_COUNT = 11;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 1]], 200),
        ]);

        config([
            'services.telegram-bot-api.token' => 'dummy-token',
            'services.telegram-bot-api.webhook_secret' => 'dummy-secret',
            'services.telegram-bot-api.bot_scope' => 'default',
            'services.telegram-bot-api.order_enabled' => true,
            // Guard bridge setelan panel bersifat per-proses; bersihkan supaya
            // tiap test benar-benar menerapkan setelan dari DB.
            'bot.database_settings_applied' => false,
        ]);

        DB::table('setting_webs')->updateOrInsert(['id' => 1], [
            'judul_web' => 'T', 'deskripsi_web' => 'T', 'keywords' => 't',
            'url_wa' => 'https://wa.me/628', 'url_ig' => 'https://i.test', 'url_tiktok' => 'https://t.test',
            'url_youtube' => 'https://y.test', 'url_fb' => 'https://f.test', 'topupindo_api' => 't',
            'warna1' => '#000000', 'warna2' => '#000000', 'warna3' => '#000000', 'warna4' => '#000000',
            'paydisini_apikey' => 't', 'order_prefik' => 'TRX', 'nomor_admin' => '628',
            'bot_order_tg_enabled' => 1,
        ]);

        InboundSourcePolicy::query()->updateOrCreate(
            ['source_domain' => 'bot_webhook', 'source_name' => 'telegram'],
            ['mode' => 'disabled', 'is_active' => true],
        );

        Cache::flush();

        for ($i = 1; $i <= self::ORDER_COUNT; $i++) {
            $order = Pembelian::create([
                'order_id' => 'TRANSAKSI-' . str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'layanan' => '12 Diamond Free Fire',
                'harga' => 3331,
                'status' => 'Proses',
                'profit' => 100,
                'gateway_principal' => 'telegram:' . self::FROM,
                'traffic_source' => 'telegram_gateway',
                'email_pembeli' => self::FROM . '@telegram.user',
                'user_id' => (string) self::FROM,
            ]);

            Pembayaran::create([
                'order_id' => $order->order_id, 'harga' => '3331', 'no_pembayaran' => 'P' . $i,
                'no_pembeli' => (string) self::FROM, 'status' => 'Lunas', 'metode' => 'qris',
            ]);
        }
    }

    private function kirim(string $text): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => self::TG_IP])
            ->postJson('/api/webhooks/bot/telegram', [
                'update_id' => random_int(900000, 999999),
                'message' => [
                    'message_id' => 1,
                    'chat' => ['id' => self::FROM, 'type' => 'private'],
                    'from' => ['id' => self::FROM, 'first_name' => 'Tester'],
                    'text' => $text,
                ],
            ], ['X-Telegram-Bot-Api-Secret-Token' => 'dummy-secret'])->assertOk();
    }

    /**
     * Payload pesan UTAMA yang benar-benar dikirim ke Telegram.
     *
     * Bukan `recorded()->last()`: begitu pager hidup, adapter mengirim pesan
     * KEDUA berisi tombol pindah halaman (teksnya "Pindah halaman"), dan pesan
     * itu yang jadi balasan terakhir. Yang mau diperiksa di sini adalah layar
     * daftarnya, yang ditandai keyboard aksi (`keyboard`), bukan inline.
     */
    private function balasanTerakhir(): array
    {
        foreach (Http::recorded()->reverse() as [$request]) {
            $data = (array) $request->data();

            if (isset($data['reply_markup']['keyboard'])) {
                return $data;
            }
        }

        return [];
    }

    /** Label tombol dari `reply_markup`, apa pun jenis keyboardnya. */
    private function labelTombol(array $markup): array
    {
        $rows = $markup['keyboard'] ?? $markup['inline_keyboard'] ?? [];

        return array_map(
            fn (array $row): array => array_map(fn (array $b): string => (string) $b['text'], $row),
            $rows,
        );
    }

    private function daftarkanDaftarLama(): void
    {
        // Peta angka dari daftar yang dilihat user SEBELUM membuka transaksi.
        app(BotNumericMenuStore::class)->put('telegram:default:' . self::FROM, [
            'menu' => 'services', 'parent_menu' => 'categories', 'page' => 1,
            'entries' => [
                '1' => ['type' => 'content', 'label' => 'Svc 1', 'command' => 'layanan svc-1'],
                '2' => ['type' => 'content', 'label' => 'Svc 2', 'command' => 'layanan svc-2'],
                '3' => ['type' => 'content', 'label' => 'Svc 3', 'command' => 'layanan svc-3'],
                '4' => ['type' => 'content', 'label' => 'Svc 4', 'command' => 'layanan svc-4'],
                '5' => ['type' => 'content', 'label' => 'Svc 5', 'command' => 'layanan svc-5'],
            ],
        ], "DAFTAR LAYANAN\n");
    }

    /**
     * INTI: tidak ada satu pun tombol bernomor di pesan layar transaksi.
     */
    public function test_layar_transaksi_tidak_memuat_tombol_angka(): void
    {
        $this->kirim('status');

        $balasan = $this->balasanTerakhir();
        $markup = $balasan['reply_markup'] ?? [];

        // Keyboard aksi tetap dikirim: inilah "keyboard yang dipertahankan".
        $this->assertArrayHasKey('keyboard', $markup, 'Keyboard aksi harus tetap ada.');
        $this->assertEmpty(
            $markup['inline_keyboard'] ?? [],
            'Layar transaksi tidak boleh lagi mengirim tombol inline bernomor.',
        );

        $angka = [];

        foreach ($this->labelTombol($markup) as $row) {
            foreach ($row as $label) {
                if (preg_match('/^\d+$/', trim($label)) === 1) {
                    $angka[] = $label;
                }
            }
        }

        $this->assertSame([], $angka, 'Tidak boleh ada tombol bernomor di layar transaksi.');
    }

    /**
     * ANGKA BASI DARI DAFTAR SEBELUMNYA HARUS IKUT HILANG.
     *
     * Ini bagian yang gampang lolos: pesannya sudah bersih, tapi keyboard bawah
     * masih memajang angka dari daftar lama. Angka itu kalau ditekan membuka
     * entri daftar lama - bukan order yang sedang dilihat.
     */
    public function test_angka_dari_daftar_lama_tidak_menempel_di_layar_transaksi(): void
    {
        $this->daftarkanDaftarLama();

        $petaLama = app(BotNumericMenuStore::class)->get('telegram:default:' . self::FROM);
        $this->assertNotNull($petaLama, 'Prasyarat: peta angka dari daftar lama harus ada.');

        $this->kirim('status');

        $angka = [];

        foreach ($this->labelTombol($this->balasanTerakhir()['reply_markup'] ?? []) as $row) {
            foreach ($row as $label) {
                if (preg_match('/^\d+$/', trim($label)) === 1) {
                    $angka[] = $label;
                }
            }
        }

        $this->assertSame([], $angka, 'Angka dari daftar lama tidak boleh menempel di layar transaksi.');

        // Dan entri isi dari daftar lama benar-benar HILANG dari peta, supaya
        // mengetik angkanya tidak membuka layanan yang sudah tidak terlihat.
        // (Peta itu sendiri tidak kosong: entri 98/99 ditulis supaya tombol
        // pindah halaman tetap terkirim - lihat test pager.)
        $this->assertNull(
            app(BotNumericMenuStore::class)->get('telegram:default:' . self::FROM)['entries'][1] ?? null,
            'Entri isi dari daftar lama (' . "1" . ') harus dibuang oleh layar transaksi.',
        );

        $putusan = app(BotNumericMenuStore::class)->resolve('telegram:default:' . self::FROM, 1);

        $this->assertNotSame('ok', $putusan['status'], 'Menekan nomor lama tidak boleh menghasilkan perintah.');
        $this->assertArrayNotHasKey('command', $putusan, 'Tidak boleh ada perintah dari daftar lama.');
    }

    /** Keyboard yang dipertahankan: tombol aksi Telegram tetap terkirim utuh. */
    public function test_keyboard_aksi_tetap_dipertahankan(): void
    {
        $this->kirim('status');

        $label = array_merge(...$this->labelTombol($this->balasanTerakhir()['reply_markup'] ?? []));

        $this->assertContains('🛍️ Buka Menu', $label);
        $this->assertContains('🏆 Leaderboard', $label);
        $this->assertContains('📦 Cek Status', $label);

        // "Riwayat Order" DICABUT dari Telegram: layar ini sendiri sudah
        // menampilkan transaksi terakhir sender, jadi tombolnya hanya
        // menduplikasi "Cek Status".
        $this->assertNotContains('📜 Riwayat Order', $label);
    }

    /**
     * PAGER TETAP BISA DIJANGKAU.
     *
     * Ini risiko yang muncul begitu tombol inline angka dihapus: pager juga
     * tombol inline, jadi gampang ikut terbuang. Dulu - bahkan SEBELUM
     * perubahan ini - halaman kedua memang tidak pernah bisa dijangkau lewat
     * tombol di layar transaksi: keyboard angka menang, sedangkan layar ini
     * tidak pernah menulis entri 98/99 yang jadi syarat adapter mengirim pesan
     * navigasi. Sekarang entri itu ditulis, jadi pagernya benar-benar ada.
     */
    public function test_tombol_pindah_halaman_tetap_bisa_dijangkau(): void
    {
        $this->kirim('status');

        $pesan = [];

        foreach (Http::recorded() as [$request]) {
            $data = $request->data();
            $teks = (string) ($data['text'] ?? $data['caption'] ?? '');

            if ($teks === '') {
                continue;
            }

            $pesan[] = [
                'text' => $teks,
                'markup' => (array) ($data['reply_markup'] ?? []),
            ];
        }

        // Pesan navigasi = pesan dengan tombol inline yang BUKAN pesan utama.
        $navigasi = array_values(array_filter(
            $pesan,
            static fn (array $m): bool => ! empty($m['markup']['inline_keyboard']),
        ));

        $this->assertNotEmpty($navigasi, 'Halaman 2 harus bisa dijangkau lewat tombol.');

        $tombol = $navigasi[0]['markup']['inline_keyboard'][0] ?? [];
        $this->assertSame('status page:2', (string) ($tombol[0]['callback_data'] ?? ''),
            'Tombol pindah halaman harus memakai perintah layar transaksi, bukan menu.');
    }

    /** Petunjuk tidak lagi menyuruh menekan nomor yang sudah tidak ada. */
    public function test_petunjuk_tidak_menyuruh_menekan_nomor(): void
    {
        $this->kirim('status');

        $teks = (string) ($this->balasanTerakhir()['text'] ?? '');

        $this->assertStringContainsString('status <invoice>', $teks);
        $this->assertStringNotContainsString('tekan nomornya', $teks);
    }

    /**
     * WHATSAPP TIDAK IKUT BERUBAH: di sana tombol nomornya memang sarana
     * memilih, jadi harus tetap ada.
     */
    public function test_whatsapp_tetap_punya_tombol_nomor(): void
    {
        $respons = app(BotMessageFormatter::class)->formatSenderOrderList(
            collect([[
                'order_id' => 'WA-1',
                'product' => 'Mobile Legends',
                'amount' => 12500,
                'payment_status' => 'lunas',
                'order_status' => 'sukses',
            ]]),
            1,
            1,
            1,
            5,
            'whatsapp_gateway',
        );

        $markup = $respons['buttons'][0][0] ?? [];

        $this->assertSame('1', $markup['text'] ?? null);
        $this->assertSame('status WA-1', $markup['callback'] ?? null);

        // Copy WA tidak ikut berubah: di sana tombol nomornya memang ada, jadi
        // petunjuk "tekan nomornya" masih benar.
        $this->assertStringContainsString('tekan nomornya', $respons['text']);
    }
}
