<?php

namespace Tests\Feature\Bot;

use App\Models\BotLocalePreference;
use App\Models\InboundSourcePolicy;
use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Services\Bot\BotGatewayCapabilities;
use App\Services\Bot\BotMessageFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * DUA PERBAIKAN LAYAR TRANSAKSI TELEGRAM.
 *
 * 1. Tombol "Riwayat Order" DICABUT dari Telegram. Fungsinya sudah tercakup
 *    "Cek Status": perintah `status` tanpa invoice memang menampilkan transaksi
 *    terakhir sender, jadi tombol itu hanya menduplikasi — dan versi lamanya
 *    menuntut akun tertaut, sehingga user dibalas "riwayat belum tersedia"
 *    padahal layar yang dia mau sudah ada satu tombol di sebelahnya.
 *
 * 2. Petunjuk "Ketik `status <invoice>` untuk detail" BOCOR bahasa Indonesia
 *    ke user berbahasa Inggris. Sejak commit 6551a34d kedua cabangnya ditulis
 *    sebagai literal di dalam formatter, bukan lewat kunci lang — jadi cabang
 *    `en` ikut teks Indonesia. Sekarang kembali ke kunci lang, satu kunci per
 *    channel karena WhatsApp masih menawarkan tombol nomor sedangkan Telegram
 *    tidak.
 */
class TelegramHistoryRemovalAndLocaleTest extends TestCase
{
    use RefreshDatabase;

    private const TG_IP = '149.154.160.10';

    private const FROM = 6252007210;

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

        for ($i = 1; $i <= 3; $i++) {
            $order = Pembelian::create([
                'order_id' => 'TRX-' . $i, 'layanan' => '12 Diamond Free Fire', 'harga' => 3331,
                'status' => 'Proses', 'profit' => 100, 'gateway_principal' => 'telegram:' . self::FROM,
                'traffic_source' => 'telegram_gateway',
                'email_pembeli' => self::FROM . '@telegram.user', 'user_id' => (string) self::FROM,
            ]);

            Pembayaran::create([
                'order_id' => $order->order_id, 'harga' => '3331', 'no_pembayaran' => 'P' . $i,
                'no_pembeli' => (string) self::FROM, 'status' => 'Lunas', 'metode' => 'qris',
            ]);
        }
    }

    /** Kunci preferensi bahasa — MENGAKU lebih dulu supaya locale pasti. */
    private function paksaBahasa(string $locale): void
    {
        BotLocalePreference::query()->updateOrCreate(
            ['source' => 'telegram_gateway', 'external_user_id' => 'telegram:default:' . self::FROM],
            ['locale' => $locale, 'locale_source' => 'explicit'],
        );

        Cache::flush();
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
     * Pesan yang benar-benar dikirim ke Telegram.
     *
     * @return array<int, array{text: string, tombol: array<int, string>}>
     */
    private function pesanTerkirim(): array
    {
        $hasil = [];

        foreach (Http::recorded() as [$request]) {
            if (! str_contains($request->url(), 'sendMessage') && ! str_contains($request->url(), 'sendPhoto')) {
                continue;
            }

            $tombol = [];

            foreach (($request['reply_markup']['keyboard'] ?? []) as $row) {
                foreach ($row as $button) {
                    $tombol[] = (string) ($button['text'] ?? '');
                }
            }

            $hasil[] = [
                'text' => (string) ($request['text'] ?? $request['caption'] ?? ''),
                'tombol' => $tombol,
            ];
        }

        return $hasil;
    }

    /** Tombol keyboard dari pesan terakhir yang memuat keyboard. */
    private function tombolKeyboardTerakhir(): array
    {
        $tombol = [];

        foreach ($this->pesanTerkirim() as $pesan) {
            if ($pesan['tombol'] !== []) {
                $tombol = $pesan['tombol'];
            }
        }

        return $tombol;
    }

    /** INTI #1: tombol "Riwayat Order" tidak lagi dikirim ke keyboard Telegram. */
    public function test_tombol_riwayat_order_dicabut_dari_telegram(): void
    {
        $this->paksaBahasa('id');
        $this->kirim('status');

        $this->assertNotContains(
            '📜 Riwayat Order',
            $this->tombolKeyboardTerakhir(),
            'Tombol "Riwayat Order" harus sudah dicabut dari keyboard Telegram.',
        );
    }

    /** Keputusan produk: yang tetap ada justru "Cek Status". */
    public function test_tombol_cek_status_tetap_ada(): void
    {
        $this->paksaBahasa('id');
        $this->kirim('status');

        $this->assertContains(
            '📦 Cek Status',
            $this->tombolKeyboardTerakhir(),
            '"Cek Status" harus tetap ada — inilah pengganti fungsinya.',
        );
    }

    /** Panduan tidak boleh menyuruh menekan tombol yang sudah tidak ada. */
    public function test_panduan_tidak_lagi_menyebut_riwayat_order(): void
    {
        $this->paksaBahasa('id');
        $this->kirim('help');

        $teks = implode("\n", array_column($this->pesanTerkirim(), 'text'));

        $this->assertStringNotContainsString('Riwayat Order', $teks);
        $this->assertStringNotContainsString('Order History', $teks);
    }

    /** INTI #2: user berbahasa Inggris TIDAK boleh melihat teks Indonesia. */
    public function test_petunjuk_layar_transaksi_ikut_bahasa_inggris(): void
    {
        $this->paksaBahasa('en');
        $this->kirim('status');

        $teks = implode("\n", array_column($this->pesanTerkirim(), 'text'));

        $this->assertStringContainsString('Type `status', $teks, 'Petunjuk harus berbahasa Inggris.');
        $this->assertStringNotContainsString(
            'Ketik `status <invoice>` untuk detail',
            $teks,
            'Teks Indonesia tidak boleh bocor ke user berbahasa Inggris.',
        );
    }

    /** Dan sebaliknya: user Indonesia tetap dapat teks Indonesia. */
    public function test_petunjuk_layar_transaksi_tetap_indonesia_untuk_locale_id(): void
    {
        $this->paksaBahasa('id');
        $this->kirim('status');

        $teks = implode("\n", array_column($this->pesanTerkirim(), 'text'));

        $this->assertStringContainsString('Ketik `status', $teks);
        $this->assertStringNotContainsString('Type `status', $teks);
    }

    /**
     * WhatsApp TIDAK ikut berubah: tombol nomornya masih ada, dan petunjuknya
     * masih menyebut "tekan nomornya".
     */
    public function test_whatsapp_tetap_punya_tombol_riwayat_order(): void
    {
        $keyboard = app(BotMessageFormatter::class)->defaultReplyKeyboard(
            BotGatewayCapabilities::forSource(BotGatewayCapabilities::SOURCE_WHATSAPP),
        );

        $label = [];

        foreach ($keyboard['keyboard'] as $row) {
            foreach ($row as $button) {
                $label[] = (string) ($button['text'] ?? '');
            }
        }

        $this->assertContains('📜 Riwayat Order', $label, 'WhatsApp tidak boleh kehilangan tombol riwayat.');
    }

    /**
     * Tombol INLINE di panduan ikut dicabut — panduan dan keyboard tetap harus
     * memakai daftar tombol yang sama, jadi mencabut satu sisi saja membuat
     * dua layar menyebut tombol yang berbeda.
     */
    public function test_tombol_panduan_riwayat_order_ikut_dicabut_di_telegram(): void
    {
        $this->paksaBahasa('id');
        $this->kirim('help');

        $callback = [];

        foreach (\Illuminate\Support\Facades\Http::recorded() as [$request]) {
            foreach (($request['reply_markup']['inline_keyboard'] ?? []) as $row) {
                foreach ($row as $button) {
                    $callback[] = (string) ($button['callback_data'] ?? '');
                }
            }
        }

        $this->assertNotContains('order_history', $callback, 'Tombol panduan riwayat harus ikut dicabut di Telegram.');

        // WhatsApp tetap mendapatkannya.
        $wa = app(BotMessageFormatter::class)->formatHelp(
            BotGatewayCapabilities::forSource(BotGatewayCapabilities::SOURCE_WHATSAPP),
        );

        $waCallback = [];

        foreach ($wa['buttons'] as $row) {
            foreach ($row as $button) {
                $waCallback[] = (string) ($button['callback'] ?? '');
            }
        }

        $this->assertContains('order_history', $waCallback);
    }

    /** Kunci lang yang dipulihkan harus ada di KEDUA bahasa. */
    public function test_kunci_lang_petunjuk_ada_di_dua_bahasa(): void
    {
        foreach (['sender_list_hint_telegram', 'sender_list_hint_whatsapp'] as $kunci) {
            $id = __('bot.' . $kunci, [], 'id');
            $en = __('bot.' . $kunci, [], 'en');

            $this->assertNotSame('bot.' . $kunci, $id, "Kunci {$kunci} hilang di bahasa Indonesia.");
            $this->assertNotSame('bot.' . $kunci, $en, "Kunci {$kunci} hilang di bahasa Inggris.");
            $this->assertNotSame($id, $en, "Kunci {$kunci} tidak diterjemahkan — bahasa Inggris masih menyalin Indonesia.");
        }
    }
}
