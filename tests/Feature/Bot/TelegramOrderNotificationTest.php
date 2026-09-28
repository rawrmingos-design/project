<?php

namespace Tests\Feature\Bot;

use App\Events\InvoiceStatusUpdated;
use App\Listeners\NotifyBotOrderStatusListener;
use App\Models\BotLocalePreference;
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
     * Notifikasi order Telegram TIDAK boleh ikut `app()->getLocale()`.
     *
     * `NotifyBotOrderStatusListener` jalan di QUEUE; `app()->getLocale()` di
     * worker berisi sisa locale job SEBELUMNYA, bukan bahasa user ini. Kalau
     * locale bocor ke pesan, user A bisa menerima kabar pembayarannya dalam
     * bahasa user B.
     *
     * Test ini TIDAK memakai preferensi tersimpan (`bot_locale_preferences`
     * kosong) — jadi sekaligus menetapkan bahwa tanpa preferensi, notifikasi
     * tetap Indonesia apa pun locale proses. Pasangan test-nya:
     * `test_notifikasi_ikut_preferensi_tersimpan_user`.
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
     * Preferensi tersimpan `en` → notifikasi Telegram berbahasa Inggris.
     *
     * Ini pasangan langsung test di atas: yang membedakan keduanya HANYA
     * keberadaan baris `bot_locale_preferences`, bukan `language_code` maupun
     * locale proses.
     */
    public function test_notifikasi_ikut_preferensi_tersimpan_user(): void
    {
        config(['services.telegram-bot-api.token' => null]);
        $this->createSetting(['telegram_bot_token' => 'TEST-TG-TOKEN']);

        Cache::flush();
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => []]),
        ]);

        // Kunci HARUS bentuk yang ditulis `BotLocale::setForContext()` —
        // `telegram:<bot_scope>:<user_id>`, bukan bentuk kanonik principal.
        BotLocalePreference::create([
            'source' => 'telegram_gateway',
            'external_user_id' => 'telegram:default:98765',
            'locale' => 'en',
            'locale_source' => BotLocalePreference::SOURCE_EXPLICIT,
        ]);

        $this->createTelegramOrder(['status' => 'Sukses']);

        (new NotifyBotOrderStatusListener)->handle(new InvoiceStatusUpdated([
            'order_id' => 'TG-NOTIF-001',
        ]));

        Http::assertSent(function ($request) {
            $text = (string) $request['text'];

            return str_contains($request->url(), '/sendMessage')
                && str_contains($text, 'Top Up Successful')
                && str_contains($text, 'Thank you for shopping');
        });
    }

    /**
     * Preferensi tersimpan `id` harus MENANG atas locale proses `en`.
     *
     * Tanpa test ini, implementasi yang membaca `app()->getLocale()` (bukan
     * baris preferensi) akan tetap hijau di test sebelumnya — dan di produksi
     * mencampur bahasa antar user di worker yang sama.
     */
    public function test_preferensi_indonesia_menang_atas_locale_proses_en(): void
    {
        config(['services.telegram-bot-api.token' => null]);
        $this->createSetting(['telegram_bot_token' => 'TEST-TG-TOKEN']);

        Cache::flush();
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => []]),
        ]);

        BotLocalePreference::create([
            'source' => 'telegram_gateway',
            'external_user_id' => 'telegram:default:98765',
            'locale' => 'id',
            'locale_source' => BotLocalePreference::SOURCE_EXPLICIT,
        ]);

        $this->createTelegramOrder(['status' => 'Sukses']);

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

            return str_contains($text, 'Top Up Berhasil')
                && ! str_contains($text, 'Top Up Successful');
        });
    }

    /**
     * Notifikasi user A (preferensi `en`) TIDAK boleh mengubah bahasa
     * notifikasi user B (preferensi `id`) di worker yang sama.
     *
     * Ini bahaya nyata dari `App::setLocale()` yang bersifat global-per-proses:
     * dua job berurutan di satu worker tanpa restorasi akan membuat user
     * terakhir "menang". Order ids HARUS dibedakan supaya sekaligus bisa
     * membuktikan cache anti-spam tidak menutupi job kedua.
     */
    public function test_bahasa_tidak_bocor_antar_job_di_worker_yang_sama(): void
    {
        config(['services.telegram-bot-api.token' => null]);
        $this->createSetting(['telegram_bot_token' => 'TEST-TG-TOKEN']);

        Cache::flush();
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => []]),
        ]);

        BotLocalePreference::create([
            'source' => 'telegram_gateway',
            'external_user_id' => 'telegram:default:98765',
            'locale' => 'en',
            'locale_source' => BotLocalePreference::SOURCE_EXPLICIT,
        ]);

        BotLocalePreference::create([
            'source' => 'telegram_gateway',
            'external_user_id' => 'telegram:default:11111',
            'locale' => 'id',
            'locale_source' => BotLocalePreference::SOURCE_EXPLICIT,
        ]);

        $this->createTelegramOrder([
            'order_id' => 'TG-NOTIF-A',
            'gateway_principal' => 'telegram:default:98765',
            'email_pembeli' => '98765@telegram.user',
            'status' => 'Sukses',
        ]);
        $this->createTelegramOrder([
            'order_id' => 'TG-NOTIF-B',
            'gateway_principal' => 'telegram:default:11111',
            'email_pembeli' => '11111@telegram.user',
            'status' => 'Sukses',
        ]);

        $listener = new NotifyBotOrderStatusListener;

        // Sentinel: locale proses disetel ke nilai yang TIDAK dipakai preferensi
        // mana pun (`fr`) — jadi kalau listener membaca locale proses alih-alih
        // baris preferensi, hasilnya `fr` dan langsung ketahuan. Memakai `id` di
        // sini akan menyamarkan bug karena kebetulan sama dengan default.
        app()->setLocale('fr');

        // Job `en` didahulukan, job `id` terakhir: kalau locale tidak dipulihkan
        // setelah job `en`, job `id` ikut Inggris. Urutan ini yang membuat
        // kebocoran antar-job terlihat.
        $listener->handle(new InvoiceStatusUpdated(['order_id' => 'TG-NOTIF-A']));
        $listener->handle(new InvoiceStatusUpdated(['order_id' => 'TG-NOTIF-B']));

        // Dipulihkan ke `fr` (locale sebelum job), bukan ke `id`.
        $this->assertSame('fr', app()->getLocale());

        app()->setLocale('id');

        $sent = [];
        Http::assertSent(function ($request) use (&$sent) {
            $sent[] = (string) $request['text'];

            return true;
        });

        $toA = collect($sent)->first(fn ($t) => str_contains($t, 'TG-NOTIF-A'));
        $toB = collect($sent)->first(fn ($t) => str_contains($t, 'TG-NOTIF-B'));

        $this->assertNotNull($toA, 'Notifikasi user A tidak terkirim');
        $this->assertNotNull($toB, 'Notifikasi user B tidak terkirim');

        // A: preferensi en. B: preferensi id — HARUS tetap id walau job
        // sebelumnya menyetel locale ke en.
        $this->assertStringContainsString('Top Up Successful', $toA);
        $this->assertStringContainsString('Top Up Berhasil', $toB);
        $this->assertStringNotContainsString('Top Up Successful', $toB);
    }

    /**
     * Benih AUTO-DETEKSI (`locale_source = detected`) juga dihormati.
     *
     * Ini keputusan sadar: user yang Telegram-nya berbahasa Inggris melihat bot
     * berbahasa Inggris, jadi notifikasi order-nya harus sama — kalau tidak,
     * satu percakapan jadi dua bahasa. Risikonya diakui: `detected` adalah
     * tebakan perangkat, dan tebakan yang salah akan terbawa ke notifikasi
     * jalur uang. Karena itu seed hanya ditulis di chat PRIVAT dan selalu bisa
     * ditimpa user lewat `/bahasa` (jadi `explicit`).
     *
     * Kalau suatu saat keputusannya berubah jadi "hanya pilihan eksplisit yang
     * dihormati di jalur uang", test ini yang harus diubah lebih dulu —
     * itu penanda perubahan kebijakan, bukan sekadar refactor.
     */
    public function test_benih_auto_deteksi_juga_dihormati(): void
    {
        config(['services.telegram-bot-api.token' => null]);
        $this->createSetting(['telegram_bot_token' => 'TEST-TG-TOKEN']);

        Cache::flush();
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => []]),
        ]);

        BotLocalePreference::create([
            'source' => 'telegram_gateway',
            'external_user_id' => 'telegram:default:98765',
            'locale' => 'en',
            'locale_source' => BotLocalePreference::SOURCE_DETECTED,
        ]);

        $this->createTelegramOrder(['status' => 'Sukses']);

        (new NotifyBotOrderStatusListener)->handle(new InvoiceStatusUpdated([
            'order_id' => 'TG-NOTIF-001',
        ]));

        Http::assertSent(fn ($request) => str_contains((string) $request['text'], 'Top Up Successful'));
    }

    /**
     * Order LAMA tanpa baris preferensi (semua order produksi sebelum fitur ini)
     * → tetap Indonesia, tidak error, tidak notifikasi kosong.
     */
    public function test_order_tanpa_preferensi_tetap_indonesia(): void
    {
        config(['services.telegram-bot-api.token' => null]);
        $this->createSetting(['telegram_bot_token' => 'TEST-TG-TOKEN']);

        Cache::flush();
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => []]),
        ]);

        $this->createTelegramOrder(['status' => 'Sukses']);

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

            return str_contains($text, 'Top Up Berhasil')
                && ! str_contains($text, 'Top Up Successful');
        });
    }

    /**
     * WhatsApp TIDAK ikut switch bahasa — tetap jalur WA, bukan Telegram,
     * walau ada preferensi `en` dan locale proses `en`.
     *
     * Scope switch bahasa adalah Telegram saja; ini penjaga invarian itu di
     * level listener.
     */
    public function test_whatsapp_tetap_indonesia_walau_ada_preferensi_en(): void
    {
        config(['services.telegram-bot-api.token' => null]);
        $this->createSetting(['telegram_bot_token' => 'TEST-TG-TOKEN']);

        Cache::flush();
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => []]),
        ]);

        BotLocalePreference::create([
            'source' => 'telegram_gateway',
            'external_user_id' => 'telegram:default:98765',
            'locale' => 'en',
            'locale_source' => BotLocalePreference::SOURCE_EXPLICIT,
        ]);

        $waOrder = $this->createTelegramOrder([
            'order_id' => 'WA-NOTIF-001',
            'traffic_source' => 'whatsapp_gateway',
            'gateway_principal' => null,
            'email_pembeli' => null,
            'username' => '628123456789',
        ]);
        $waOrder->pembayaran->update(['no_pembeli' => '628123456789']);

        app()->setLocale('en');

        try {
            (new NotifyBotOrderStatusListener)->handle(new InvoiceStatusUpdated([
                'order_id' => 'WA-NOTIF-001',
            ]));
        } finally {
            app()->setLocale('id');
        }

        // Pesan WA dikirim lewat WhatsappNotificationService (mock-free di sini:
        // tidak ada sesi WA di test), jadi yang bisa dipastikan adalah jalur
        // Telegram TIDAK dipakai dan bahasa proses dipulihkan.
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'api.telegram.org'));
        $this->assertSame('id', app()->getLocale());
    }

    /**
     * Transisi `failed` (order Gagal, pembayaran Lunas) juga harus terkirim,
     * dan anti-spam-nya terpisah dari transisi lain.
     *
     * ✅ Hutang D6 SUDAH DIBAYAR (Fase 4): `formatStatus()` kini punya cabang
     * khusus order Gagal. Sebelumnya teksnya IDENTIK dengan transisi `paid`
     * (`✅ *Pembayaran Berhasil*` / "sedang diproses"), jadi user yang uangnya
     * sudah masuk diberi tahu pesanannya masih jalan padahal provider sudah
     * menyatakan GAGAL. Variabel `$summary` ('Order Gagal') di listener dulu
     * DEAD CODE; sekarang tidak lagi dibutuhkan karena formatnya dari
     * `status` di payload.
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

        // Teks yang benar-benar dikirim ke Telegram TIDAK boleh menyebut
        // "sedang diproses" — itu inti hutang D6.
        $sent = $this->sentTexts();
        $this->assertNotEmpty($sent);
        $this->assertStringNotContainsString('sedang diproses', implode("\n", $sent));

        // Cache key memakai `failed`, bukan `paid`/`success`.
        $this->assertTrue(Cache::has('bot:notif:TG-NOTIF-001:failed'));
        $this->assertFalse(Cache::has('bot:notif:TG-NOTIF-001:paid'));
        $this->assertFalse(Cache::has('bot:notif:TG-NOTIF-001:success'));
    }

    /** @return array<int, string> Teks mentah dari semua permintaan sendMessage. */
    private function sentTexts(): array
    {
        $texts = [];

        Http::recorded(function ($request) use (&$texts) {
            $body = $request->data();
            if (isset($body['text'])) {
                $texts[] = (string) $body['text'];
            }

            return true;
        });

        return $texts;
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
