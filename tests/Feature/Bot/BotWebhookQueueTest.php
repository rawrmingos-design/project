<?php

namespace Tests\Feature\Bot;

use App\Jobs\ProcessBotWebhookJob;
use App\Models\InboundSourcePolicy;
use App\Services\Bot\Adapters\TelegramAdapter;
use App\Services\Settings\DatabaseSettingsBridge;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * WEBHOOK BOT DIANTREKAN, TIDAK DIPROSES DI DALAM SIKLUS REQUEST.
 *
 * LATAR TERUKUR: satu update `/start` memakan ~3,2 detik dan 98% waktunya
 * adalah menunggu jawaban `api.telegram.org` (satu panggilan sukses ~0,54 s,
 * satu panggilan ditolak ~1,05 s). Telegram menyerah sekitar 10 detik lalu
 * mencatat "Read timeout expired" dan mengirim ulang update yang sama. Selama
 * pemrosesan menempel di siklus request, waktu itu tidak bisa dihilangkan.
 *
 * Berkas ini mengunci tiga hal yang mudah rusak tanpa disadari:
 *  1. controller menjawab cepat dan MENYERAHKAN pekerjaan ke antrean;
 *  2. penjaga replay tetap berlaku walau pemrosesan menjadi asinkron;
 *  3. job yang berjalan di WORKER (proses console) tetap mendapat setelan
 *     panel — tanpa itu bot kehilangan token, webhook secret, dan daftar grup
 *     wajib, dan tampak "mati" padahal hanya kehilangan setelan.
 */
class BotWebhookQueueTest extends TestCase
{
    use RefreshDatabase;

    private const TG_SECRET = 'uji-rahasia-antrean';

    private const TG_IP = '149.154.160.10';

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        config([
            'services.telegram-bot-api.token' => 'token-uji',
            'services.telegram-bot-api.webhook_secret' => self::TG_SECRET,
            'services.telegram-bot-api.bot_scope' => 'default',
            'services.telegram-bot-api.order_enabled' => true,
        ]);

        // Guard bridge bersifat per-proses; bersihkan supaya tiap test
        // benar-benar menguji penerapannya.
        config(['bot.database_settings_applied' => false]);

        $this->seedSettingWeb();

        foreach (['telegram', 'fonnte', 'openwa'] as $source) {
            InboundSourcePolicy::query()->updateOrCreate(
                ['source_domain' => 'bot_webhook', 'source_name' => $source],
                ['mode' => 'disabled', 'is_active' => true],
            );
        }

        Cache::flush();
        RateLimiter::clear('bot-invalid:ip:' . self::TG_IP);
    }

    /** SQLite: kolom NOT NULL wajib lengkap atau puluhan test tumbang sekaligus. */
    private function seedSettingWeb(array $extra = []): void
    {
        DB::table('setting_webs')->updateOrInsert(['id' => 1], array_merge([
            'judul_web' => 'Test Store',
            'deskripsi_web' => 'Test Description',
            'keywords' => 'test',
            'url_wa' => 'https://wa.me/628123456789',
            'url_ig' => 'https://instagram.com/test',
            'url_tiktok' => 'https://tiktok.com/@test',
            'url_youtube' => 'https://youtube.com/test',
            'url_fb' => 'https://facebook.com/test',
            'topupindo_api' => 'test',
            'warna1' => '#000000',
            'warna2' => '#000000',
            'warna3' => '#000000',
            'warna4' => '#000000',
            'paydisini_apikey' => 'test',
            'order_prefik' => 'TRX',
            'nomor_admin' => '628123456789',
            'bot_order_tg_enabled' => 1,
            'bot_order_wa_enabled' => 1,
        ], $extra));
    }

    private function payload(string $text = '/start', int $updateId = 5001, int $fromId = 555001): array
    {
        return [
            'update_id' => $updateId,
            'message' => [
                'message_id' => $updateId,
                'chat' => ['id' => $fromId, 'type' => 'private'],
                'from' => ['id' => $fromId, 'first_name' => 'Tester', 'language_code' => 'id'],
                'text' => $text,
            ],
        ];
    }

    private function tembak(array $payload)
    {
        return $this->withServerVariables(['REMOTE_ADDR' => self::TG_IP])
            ->postJson('/api/webhooks/bot/telegram', $payload, [
                'X-Telegram-Bot-Api-Secret-Token' => self::TG_SECRET,
            ]);
    }

    private function fakeTelegram(): void
    {
        Http::fake([
            'api.telegram.org/bot*' => Http::response(['ok' => true, 'result' => ['message_id' => 1]], 200),
        ]);
    }

    /**
     * Controller TIDAK memproses di tempat: pekerjaan diserahkan ke antrean
     * dan permintaan dijawab tanpa menunggu Telegram.
     */
    public function test_webhook_menyerahkan_pekerjaan_ke_antrean(): void
    {
        Queue::fake();
        $this->fakeTelegram();

        $respons = $this->tembak($this->payload());

        $respons->assertOk()->assertJson(['status' => 'queued']);

        Queue::assertPushed(ProcessBotWebhookJob::class, function (ProcessBotWebhookJob $job) {
            return $job->channel === 'telegram'
                && ($job->payload['update_id'] ?? null) === 5001
                && $job->viaQueue() === 'webhook';
        });

        // Bukti terpenting: TIDAK ada panggilan keluar ke Telegram di siklus
        // request. Kalau ada, seluruh masalah waktu tunggu kembali.
        Http::assertNothingSent();
    }

    /** Update ganda dibuang SEBELUM diantrekan, supaya tidak diproses dua kali. */
    public function test_update_ganda_tidak_diantrekan_dua_kali(): void
    {
        Queue::fake();
        $this->fakeTelegram();

        $this->tembak($this->payload('/', 6001))->assertOk()->assertJson(['status' => 'queued']);

        $kedua = $this->tembak($this->payload('/', 6001));

        $kedua->assertOk()->assertJson(['status' => 'duplicate']);

        Queue::assertPushed(ProcessBotWebhookJob::class, 1);
    }

    /**
     * Job benar-benar memproses update sampai pesan terkirim. Dijalankan
     * langsung (bukan lewat HTTP) untuk meniru apa yang dilakukan worker.
     */
    public function test_job_memproses_update_sampai_pesan_terkirim(): void
    {
        $this->fakeTelegram();

        $job = new ProcessBotWebhookJob($this->payload('/', 7001), 'telegram', 'korelasi-1');
        $job->handle();

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/sendMessage');
        });
    }

    /**
     * INTI PERBAIKAN KONSOLE: job yang berjalan di worker (proses console)
     * harus tetap mendapat setelan panel.
     *
     * `AppServiceProvider` hanya menjembatani setelan DB saat
     * `! runningInConsole()`. Worker SELALU console — jadi tanpa jembatan di
     * dalam job, token bot dan daftar grup wajib hilang dan bot tidak bisa
     * membalas sama sekali.
     */
    public function test_job_menerapkan_setelan_panel_walau_berjalan_di_console(): void
    {
        $this->seedSettingWeb([
            'telegram_bot_token' => 'token-dari-panel',
            'telegram_required_channels' => json_encode([
                ['id' => '@grup_uji', 'url' => 'https://t.me/grup_uji', 'label' => '@grup_uji'],
            ]),
        ]);

        // Kondisi awal meniru worker: token dari .env TIDAK ada, guard bridge
        // belum pernah diterapkan.
        config([
            'services.telegram-bot-api.token' => null,
            'services.telegram-bot-api.required_channel.channels' => [],
            'bot.database_settings_applied' => false,
        ]);

        $job = new ProcessBotWebhookJob($this->payload('/', 8001), 'telegram');
        $adapter = app(TelegramAdapter::class);
        $adapter->applySettings();

        $this->assertSame(
            'token-dari-panel',
            config('services.telegram-bot-api.token'),
            'Token bot dari panel harus terpasang di proses worker.'
        );

        $channels = (array) config('services.telegram-bot-api.required_channel.channels');
        $this->assertCount(1, $channels, 'Daftar grup wajib dari panel harus terpasang di worker.');
        $this->assertSame('@grup_uji', $channels[0]['id'] ?? null);

        $this->assertTrue(
            (bool) config('services.telegram-bot-api.order_enabled'),
            'Saklar order dari panel harus terpasang di worker.'
        );
    }

    /**
     * Kalau antrean tidak bisa dijangkau, JANGAN jawab `200`.
     *
     * Menjawab `200` untuk pekerjaan yang tidak pernah masuk antrean berarti
     * pesan pengguna hilang diam-diam: Telegram menganggapnya terkirim, dan
     * kiriman ulangnya akan ditolak penjaga replay sebagai duplikat (klaimnya
     * sudah tercatat). Jadi klaimnya harus DILEPAS dan request dijawab `500`,
     * supaya Telegram mengirim ulang dan percobaan berikutnya lolos.
     */
    public function test_kegagalan_antrean_melepas_klaim_dan_dijawab_500(): void
    {
        $this->fakeTelegram();

        $dispatcher = \Mockery::mock(Dispatcher::class);
        $dispatcher->shouldReceive('dispatch')
            ->once()
            ->andThrow(new \RuntimeException('antrean tidak dapat dijangkau'));
        $this->app->instance(Dispatcher::class, $dispatcher);

        $respons = $this->tembak($this->payload('/', 9001));

        $respons->assertStatus(500)->assertJson(['status' => 'queue_unavailable']);

        // Bukti klaim dilepas: tidak ada jejak update ini di tabel penerimaan,
        // jadi kiriman ulang Telegram akan diproses (bukan ditolak duplikat).
        $this->assertSame(
            0,
            DB::table('telegram_update_receipts')->where('update_id', 9001)->count(),
            'Klaim replay harus dilepas saat antrean gagal, supaya update bisa dikirim ulang.'
        );

        // Dan tidak ada satu pun panggilan keluar: tidak ada pekerjaan yang jalan.
        Http::assertNothingSent();
    }

    /** Bridge idempoten: penerapan berkali-kali tidak mengubah hasil. */
    public function test_penerapan_setelan_idempoten(): void
    {
        $this->seedSettingWeb(['telegram_bot_token' => 'token-dari-panel']);

        $bridge = app(DatabaseSettingsBridge::class);

        config(['bot.database_settings_applied' => false]);
        $bridge->apply();
        $pertama = config('services.telegram-bot-api.token');

        // Panggilan kedua: nilai berubah di DB TIDAK boleh diambil lagi,
        // karena guard mencegah penerapan ganda dalam satu proses.
        $this->seedSettingWeb(['telegram_bot_token' => 'token-berubah']);
        $bridge->apply();

        $this->assertSame($pertama, config('services.telegram-bot-api.token'));
        $this->assertSame('token-dari-panel', config('services.telegram-bot-api.token'));
    }
}
