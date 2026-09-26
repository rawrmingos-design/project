<?php

namespace Tests\Feature\Bot;

use App\Events\InvoiceStatusUpdated;
use App\Listeners\NotifyBotOrderStatusListener;
use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Models\SettingWeb;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramOrderNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function createTelegramOrder(array $overrides = []): Pembelian
    {
        $order = Pembelian::create(array_merge([
            'order_id' => 'TG-NOTIF-001',
            'username' => 'Anonim',
            'user_id' => '12345',
            'zone' => '',
            'nickname' => 'Player',
            'layanan' => '100 Diamond',
            'harga' => 10500,
            'profit' => 500,
            'provider_order_id' => '',
            'status' => 'Pending',
            'log' => json_encode(['source' => 'telegram_gateway_checkout']),
            'traffic_source' => 'telegram_gateway',
            // Bentuk identitas NYATA keluaran TelegramAdapter
            // (telegram:<bot_scope>:<user_id>) — bukan bentuk rekaan,
            // supaya test tidak hijau palsu saat format berubah.
            'gateway_principal' => 'telegram:default:98765',
            'email_pembeli' => '98765@telegram.user',
            'tipe_transaksi' => 'game',
            'active_layanan_id' => 1,
            'active_provider_code' => 'manual',
            'active_provider_sku' => 'manual',
            'environment' => 'live',
            'is_sandbox' => false,
        ], $overrides));

        Pembayaran::create([
            'order_id' => $order->order_id,
            'harga' => 10500,
            'no_pembayaran' => 'TG-VA-001',
            'no_pembeli' => '-',
            'status' => 'Lunas',
            'metode' => 'QRIS',
        ]);

        return $order;
    }

    /**
     * Fixture SettingWeb dengan semua kolom NOT NULL yang wajib
     * (judul_web, deskripsi_web, keywords, warna1-4, order_prefik).
     */
    private function createSetting(array $overrides = []): SettingWeb
    {
        return SettingWeb::query()->create(array_merge([
            'id' => 1,
            'judul_web' => 'Test Store',
            'deskripsi_web' => 'Test description',
            'keywords' => 'topup,test',
            'url_wa' => 'https://wa.me/628123456789',
            'url_ig' => 'https://instagram.com/test',
            'url_tiktok' => 'https://tiktok.com/@test',
            'url_youtube' => 'https://youtube.com/@test',
            'url_fb' => 'https://facebook.com/test',
            'topupindo_api' => 'dummy-api',
            'warna1' => '#123456',
            'warna2' => '#222222',
            'warna3' => '#333333',
            'warna4' => '#654321',
            'paydisini_apikey' => 'dummy-paydisini',
            'order_prefik' => 'INV',
        ], $overrides));
    }

    public function test_telegram_order_sends_proactive_notification_on_success(): void
    {
        config(['services.telegram-bot-api.token' => null]);
        $this->createSetting(['telegram_bot_token' => 'TEST-TG-TOKEN']);

        Cache::flush();
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => []]),
        ]);

        $this->createTelegramOrder(['status' => 'Sukses']);

        (new NotifyBotOrderStatusListener)->handle(new InvoiceStatusUpdated([
            'order_id' => 'TG-NOTIF-001',
        ]));

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/sendMessage')
                && $request['chat_id'] === '98765'
                && str_contains((string) $request['text'], 'Top Up Berhasil')
                && str_contains((string) $request['text'], 'Terima kasih sudah berbelanja');
        });

        // Anti-spam: transisi yang sama terkirim sekali.
        (new NotifyBotOrderStatusListener)->handle(new InvoiceStatusUpdated([
            'order_id' => 'TG-NOTIF-001',
        ]));

        Http::assertSentCount(1);
    }

    public function test_telegram_notification_falls_back_to_plain_text_when_format_rejected(): void
    {
        config(['services.telegram-bot-api.token' => null]);
        $this->createSetting(['telegram_bot_token' => 'TEST-TG-TOKEN']);

        Cache::flush();

        // Percobaan ber-format ditolak; percobaan tanpa format diterima.
        // Ini meniru kejadian nyata: satu karakter tak terduga merusak
        // MarkdownV2, dan notifikasi status order TIDAK boleh hilang.
        $percobaan = [];

        Http::fake(function ($request) use (&$percobaan) {
            if (! str_contains($request->url(), '/sendMessage')) {
                return Http::response(['ok' => true, 'result' => []]);
            }

            $berformat = isset($request['parse_mode']);
            $percobaan[] = $request['parse_mode'] ?? null;

            return $berformat
                ? Http::response(['ok' => false, 'description' => "Bad Request: can't parse entities"], 400)
                : Http::response(['ok' => true, 'result' => []]);
        });

        $this->createTelegramOrder(['status' => 'Sukses']);

        (new NotifyBotOrderStatusListener)->handle(new InvoiceStatusUpdated([
            'order_id' => 'TG-NOTIF-001',
        ]));

        $this->assertSame(
            ['MarkdownV2', null],
            $percobaan,
            'Kirim ber-format dulu, lalu diulang tanpa format.',
        );

        // Notifikasi terkirim (percobaan kedua) → anti-spam ditandai, jadi
        // transisi yang sama tidak dikirim lagi.
        (new NotifyBotOrderStatusListener)->handle(new InvoiceStatusUpdated([
            'order_id' => 'TG-NOTIF-001',
        ]));

        $this->assertCount(2, $percobaan, 'Setelah sukses kirim, transisi sama tidak diulang.');
    }

    public function test_telegram_notification_uses_markdown_v2_formatting(): void
    {
        config(['services.telegram-bot-api.token' => null]);
        $this->createSetting(['telegram_bot_token' => 'TEST-TG-TOKEN']);

        Cache::flush();
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => []]),
        ]);

        $this->createTelegramOrder(['status' => 'Sukses']);

        (new NotifyBotOrderStatusListener)->handle(new InvoiceStatusUpdated([
            'order_id' => 'TG-NOTIF-001',
        ]));

        Http::assertSent(function ($request): bool {
            if (! str_contains($request->url(), '/sendMessage')) {
                return false;
            }

            if (($request['parse_mode'] ?? null) !== 'MarkdownV2') {
                return false;
            }

            // Teks yang dilihat user = backslash dilepas.
            $terlihat = preg_replace('/\\\\(.)/u', '$1', (string) $request['text']);

            return str_contains($terlihat, 'Top Up Berhasil')
                && str_contains($terlihat, 'Terima kasih sudah berbelanja')
                && ! str_contains($terlihat, '\\');
        });
    }

    public function test_whatsapp_orders_do_not_hit_telegram_api(): void
    {
        config(['services.telegram-bot-api.token' => null]);
        $this->createSetting(['telegram_bot_token' => 'TEST-TG-TOKEN']);

        Cache::flush();
        Http::fake();

        $order = Pembelian::create([
            'order_id' => 'WA-NOTIF-001',
            'username' => 'Anonim',
            'user_id' => '12345',
            'nickname' => 'Player',
            'layanan' => '100 Diamond',
            'harga' => 10500,
            'profit' => 500,
            'status' => 'Sukses',
            'log' => json_encode([]),
            'traffic_source' => 'whatsapp_gateway',
            'tipe_transaksi' => 'game',
            'environment' => 'live',
            'is_sandbox' => false,
        ]);

        Pembayaran::create([
            'order_id' => $order->order_id,
            'harga' => 10500,
            'no_pembayaran' => 'WA-VA-001',
            'no_pembeli' => '6281000000001',
            'status' => 'Lunas',
            'metode' => 'QRIS',
        ]);

        $this->mock(\App\Services\WhatsappNotificationService::class, function (\Mockery\MockInterface $mock): void {
            $mock->shouldReceive('sendMessage')->once()->andReturn(['success' => true]);
        });

        (new NotifyBotOrderStatusListener)->handle(new InvoiceStatusUpdated([
            'order_id' => 'WA-NOTIF-001',
        ]));

        Http::assertNothingSent();
    }

    public function test_notification_skipped_when_telegram_token_missing(): void
    {
        config(['services.telegram-bot-api.token' => null]);
        $this->createSetting(['telegram_bot_token' => null]);

        Cache::flush();
        Http::fake();

        $this->createTelegramOrder(['status' => 'Sukses']);

        (new NotifyBotOrderStatusListener)->handle(new InvoiceStatusUpdated([
            'order_id' => 'TG-NOTIF-001',
        ]));

        Http::assertNothingSent();

        // Tanpa cache key → event berikutnya masih mencoba kirim setelah token ada.
        $this->assertFalse(Cache::has('bot:notif:TG-NOTIF-001:success'));
    }

    public function test_non_lunas_payment_is_not_notified(): void
    {
        config(['services.telegram-bot-api.token' => null]);
        $this->createSetting(['telegram_bot_token' => 'TEST-TG-TOKEN']);

        Cache::flush();
        Http::fake();

        $this->createTelegramOrder(['status' => 'Pending'])
            ->pembayaran()->update(['status' => 'Belum Lunas']);

        (new NotifyBotOrderStatusListener)->handle(new InvoiceStatusUpdated([
            'order_id' => 'TG-NOTIF-001',
        ]));

        Http::assertNothingSent();
    }

    /**
     * JARING PENGAMAN Fase 1 (Task 1.5) — di level LISTENER, bukan formatter.
     *
     * `NotifyBotOrderStatusListener` memanggil `formatStatus($payload)` TANPA
     * argumen source, dari QUEUE. Di sana tidak ada konteks channel maupun
     * locale user, jadi bahasa TIDAK boleh ikut berubah — keputusan user
     * 2026-09-26 (opsi 1): notifikasi status tetap Indonesia.
     *
     * Test ini menutup celah yang tidak tertutup oleh test formatter: kalau
     * suatu hari resolusi locale disambungkan ke listener (mis. lewat
     * `BotLocale::apply()`), pesan transaksi akan berganti bahasa dan test ini
     * yang menangkapnya. Ini jalur UANG — user harus bisa membaca status
     * pembayarannya.
     */
    public function test_notifikasi_listener_tetap_indonesia_walau_locale_en(): void
    {
        config(['services.telegram-bot-api.token' => null]);
        $this->createSetting(['telegram_bot_token' => 'TEST-TG-TOKEN']);

        Cache::flush();
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => []]),
        ]);

        $this->createTelegramOrder(['status' => 'Sukses']);

        // Locale request disetel Inggris — meniru kondisi nyata kalau ada
        // middleware/bridge yang membocorkan locale ke proses ini.
        app()->setLocale('en');

        try {
            (new NotifyBotOrderStatusListener)->handle(new InvoiceStatusUpdated([
                'order_id' => 'TG-NOTIF-001',
            ]));
        } finally {
            app()->setLocale('id');
        }

        Http::assertSent(function ($request) {
            $text = (string) $request['text'];

            return str_contains($request->url(), '/sendMessage')
                && str_contains($text, 'Top Up Berhasil')
                && str_contains($text, 'Terima kasih sudah berbelanja')
                // Inti test: TIDAK boleh ikut Inggris walau locale EN.
                && ! str_contains($text, 'Top Up Successful')
                && ! str_contains($text, 'Thank you for shopping');
        });
    }

    /**
     * Transisi `failed` (order Gagal, pembayaran Lunas) juga harus terkirim,
     * dan anti-spam-nya terpisah dari transisi lain.
     *
     * ⚠️ CATATAN TEMUAN: saat test ini ditulis, teksnya IDENTIK dengan transisi
     * `paid` (`✅ *Pembayaran Berhasil*`) karena `formatStatus()` tidak punya
     * cabang khusus "order Gagal" — order Gagal+lunas jatuh ke cabang
     * "pembayaran berhasil, sedang diproses". Variabel `$summary`
     * ('Order Gagal') di listener adalah DEAD CODE (di-assign, tidak dipakai).
     * Assertion di bawah mengunci PERILAKU SAAT INI supaya perubahan tak
     * sengaja terdeteksi; perbaikan copy-nya adalah keputusan terpisah.
     */
    public function test_transisi_failed_terkirim_dengan_cache_key_terpisah(): void
    {
        config(['services.telegram-bot-api.token' => null]);
        $this->createSetting(['telegram_bot_token' => 'TEST-TG-TOKEN']);

        Cache::flush();
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => []]),
        ]);

        $this->createTelegramOrder(['status' => 'Gagal']);

        (new NotifyBotOrderStatusListener)->handle(new InvoiceStatusUpdated([
            'order_id' => 'TG-NOTIF-001',
        ]));

        Http::assertSentCount(1);

        // Cache key memakai `failed`, bukan `paid`/`success`.
        $this->assertTrue(Cache::has('bot:notif:TG-NOTIF-001:failed'));
        $this->assertFalse(Cache::has('bot:notif:TG-NOTIF-001:paid'));
        $this->assertFalse(Cache::has('bot:notif:TG-NOTIF-001:success'));
    }

    /**
     * Transisi `paid` (Lunas, order masih diproses) terkirim dan anti-spam-nya
     * TERPISAH dari `success` — supaya user tetap dapat kabar saat pembayaran
     * diterima, lalu kabar kedua saat top up selesai.
     */
    public function test_transisi_paid_terkirim_dan_tidak_menutup_notifikasi_success(): void
    {
        config(['services.telegram-bot-api.token' => null]);
        $this->createSetting(['telegram_bot_token' => 'TEST-TG-TOKEN']);

        Cache::flush();
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => []]),
        ]);

        $order = $this->createTelegramOrder(['status' => 'Pending']);

        (new NotifyBotOrderStatusListener)->handle(new InvoiceStatusUpdated([
            'order_id' => 'TG-NOTIF-001',
        ]));

        $this->assertTrue(Cache::has('bot:notif:TG-NOTIF-001:paid'));
        $this->assertFalse(Cache::has('bot:notif:TG-NOTIF-001:success'));

        // Order menyelesaikan proses → notifikasi KEDUA harus tetap bisa kirim.
        $order->update(['status' => 'Sukses']);

        (new NotifyBotOrderStatusListener)->handle(new InvoiceStatusUpdated([
            'order_id' => 'TG-NOTIF-001',
        ]));

        $this->assertTrue(Cache::has('bot:notif:TG-NOTIF-001:success'));
        Http::assertSentCount(2);
    }
}
