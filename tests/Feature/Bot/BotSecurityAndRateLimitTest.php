<?php

namespace Tests\Feature\Bot;

use App\Models\InboundSourcePolicy;
use App\Models\Method;
use App\Models\Paket;
use App\Services\Bot\BotCommandHandler;
use App\Services\Bot\BotNumericMenuStore;
use App\Services\Bot\TelegramChannelMembershipService;
use App\Services\Whatsapp\WhatsappUserResolver;
use App\Support\TelegramMarkdown;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * SKENARIO KEAMANAN & PEMBATAS LAJU JALUR BOT (Telegram + WhatsApp).
 *
 * Beda dari test lain di folder ini: test lain memeriksa BENTUK balasan
 * (copy, penomoran, format). Berkas ini memeriksa PERILAKU PERTAHANAN — apa
 * yang terjadi saat ada yang mendorong batas: spam webhook, token rahasia
 * salah, IP tak dikenal, replay update, pengetukan nomor di luar daftar, dan
 * penyalahgunaan linking/deposit.
 *
 * Aturan yang dipegang di sini:
 *   1. Gagal TERTUTUP. Status yang tidak dikenal TIDAK boleh lolos gate.
 *   2. Pembatas laju dihitung per-IDENTITAS (nomor/akun), bukan global, supaya
 *      satu penyalahguna tidak memblokir semua orang.
 *   3. Replay update Telegram tidak boleh diproses dua kali.
 *   4. Rahasia yang salah TIDAK boleh membocorkan apakah rahasianya pernah
 *      dikonfigurasi.
 */
class BotSecurityAndRateLimitTest extends TestCase
{
    use RefreshDatabase;

    private const TG_SECRET = 'uji-rahasia-telegram';

    /** IP di dalam rentang resmi Telegram (149.154.160.0/20). */
    private const TG_IP = '149.154.160.10';

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        config([
            'services.telegram-bot-api.token' => 'dummy-token',
            'services.telegram-bot-api.webhook_secret' => self::TG_SECRET,
            'services.telegram-bot-api.bot_scope' => 'default',
            'services.telegram-bot-api.order_enabled' => true,
            'services.telegram-bot-api.deposit_enabled' => false,
        ]);

        $this->seedSettingWeb();

        // Kebijakan sumber masuk: mode `disabled` supaya uji pembatas laju tidak
        // tercampur uji daftar-putih IP (itu diuji di BotWebhookSecurityTest).
        foreach (['telegram', 'fonnte', 'openwa'] as $source) {
            InboundSourcePolicy::query()->updateOrCreate(
                ['source_domain' => 'bot_webhook', 'source_name' => $source],
                ['mode' => 'disabled', 'is_active' => true],
            );
        }

        // Cache array bertahan antar-test di dalam satu proses pada beberapa
        // driver; pembatas laju harus mulai dari nol di setiap test.
        Cache::flush();
        RateLimiter::clear('bot-invalid:ip:' . self::TG_IP);
    }

    private function seedSettingWeb(): void
    {
        DB::table('setting_webs')->updateOrInsert(['id' => 1], [
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
        ]);

        config(['app.name' => 'Test Store']);
    }

    /** @param array<string, mixed> $payload */
    private function postTelegram(array $payload, string $secret = self::TG_SECRET, string $ip = self::TG_IP)
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson('/api/webhooks/bot/telegram', $payload, [
                'X-Telegram-Bot-Api-Secret-Token' => $secret,
            ]);
    }

    /** Payload pesan teks Telegram yang sah. */
    private function telegramMessage(int $updateId, string $text, int $fromId = 9876): array
    {
        return [
            'update_id' => $updateId,
            'message' => [
                'message_id' => 1000 + $updateId,
                'chat' => ['id' => $fromId, 'type' => 'private'],
                'from' => ['id' => $fromId, 'first_name' => 'Tester', 'language_code' => 'id'],
                'text' => $text,
            ],
        ];
    }

    // ==================================================================
    // 1. AUTENTIKASI WEBHOOK TELEGRAM
    // ==================================================================

    public function test_rahasia_salah_ditolak_dan_tidak_membocorkan_apakah_rahasia_dikonfigurasi(): void
    {
        $res = $this->postTelegram($this->telegramMessage(1, 'menu'), secret: 'rahasia-palsu');

        $res->assertStatus(401);

        // Balasan tidak boleh memberi tahu penyerang apakah secret sudah diisi.
        $body = strtolower($res->getContent());
        $this->assertStringNotContainsString('secret_configured', $body);
        $this->assertStringNotContainsString('configured', $body);
    }

    public function test_rahasia_kosong_di_config_selalu_ditolak_bahkan_dengan_header_kosong(): void
    {
        // Jangan pernah memperlakukan "secret belum diatur" sebagai "semua boleh".
        config(['services.telegram-bot-api.webhook_secret' => '']);

        $this->postTelegram($this->telegramMessage(2, 'menu'), secret: '')->assertStatus(401);
    }

    public function test_perbandingan_rahasia_tahan_serangan_waktu(): void
    {
        // Bukti tidak langsung: kedua nilai sama panjang tapi beda 1 karakter
        // terakhir pun ditolak. `hash_equals` dipakai, bukan `==`.
        $hampir = substr(self::TG_SECRET, 0, -1) . 'X';

        $this->postTelegram($this->telegramMessage(3, 'menu'), secret: $hampir)->assertStatus(401);
    }

    public function test_header_rahasia_ganda_hanya_dibaca_sebagai_satu_nilai(): void
    {
        // Bot API mengirim secret lewat header; tidak boleh ada jalur alternatif
        // (mis. body) yang bisa dipakai melewati pemeriksaan.
        $this->withServerVariables(['REMOTE_ADDR' => self::TG_IP])
            ->postJson('/api/webhooks/bot/telegram', $this->telegramMessage(4, 'menu') + [
                'secret_token' => self::TG_SECRET,
                'webhook_secret' => self::TG_SECRET,
            ], ['X-Telegram-Bot-Api-Secret-Token' => 'salah'])
            ->assertStatus(401);
    }

    // ==================================================================
    // 2. PEMBATAS LAJU TOKEN RUSAK (bot-invalid)
    // ==================================================================

    public function test_token_rahasia_rusak_dibatasi_per_ip(): void
    {
        config(['rate_limits.callbacks.bot_invalid_per_minute' => 3]);

        for ($i = 0; $i < 3; $i++) {
            $this->postTelegram($this->telegramMessage(100 + $i, 'menu'), secret: 'salah-' . $i)
                ->assertStatus(401);
        }

        // Percobaan ke-4 dari IP yang sama kena batas → 429, bukan 401 lagi.
        $this->postTelegram($this->telegramMessage(200, 'menu'), secret: 'salah-lagi')
            ->assertStatus(429);
    }

    public function test_batas_token_rusak_tidak_memblokir_ip_sah_lain(): void
    {
        config(['rate_limits.callbacks.bot_invalid_per_minute' => 2]);

        for ($i = 0; $i < 3; $i++) {
            $this->postTelegram($this->telegramMessage(300 + $i, 'menu'), secret: 'salah', ip: '149.154.160.11');
        }

        // IP berbeda = keranjang berbeda; pemanggil sah tidak boleh ikut terkunci.
        $this->postTelegram($this->telegramMessage(310, 'menu'), ip: '149.154.160.12')
            ->assertStatus(200);
    }

    public function test_ip_yang_kena_batas_token_rusak_tidak_diblokir_saat_token_benar(): void
    {
        config(['rate_limits.callbacks.bot_invalid_per_minute' => 1]);

        $this->postTelegram($this->telegramMessage(400, 'menu'), secret: 'salah')->assertStatus(401);

        // Bucket `bot-invalid` HANYA diperiksa di cabang token rusak. Jadi user
        // yang mengirim secret BENAR tidak boleh ikut tertahan gara-gara ada
        // penyerang di IP yang sama — dan percobaan sah itu tidak "menutup"
        // kuota bot-invalid, karena kuota itu hanya dihitung untuk header salah.
        $this->postTelegram($this->telegramMessage(401, 'menu'))->assertStatus(200);
    }

    // ==================================================================
    // 3. PEMBATAS LAJU WEBHOOK (bot-webhook)
    // ==================================================================

    public function test_kuota_webhook_bot_dihitung_per_ip_dan_mengembalikan_429(): void
    {
        config(['rate_limits.callbacks.bot_webhook_per_minute' => 3]);

        for ($i = 0; $i < 3; $i++) {
            $this->postTelegram($this->telegramMessage(500 + $i, 'menu'))->assertStatus(200);
        }

        $this->postTelegram($this->telegramMessage(510, 'menu'))->assertStatus(429);
    }

    public function test_permintaan_tak_terautentikasi_juga_memakan_kuota_webhook(): void
    {
        // Kalau jalur gagal-token TIDAK memakan kuota, penyerang bisa mengirim
        // tak terbatas tanpa pernah kena batas.
        config(['rate_limits.callbacks.bot_webhook_per_minute' => 3]);
        config(['rate_limits.callbacks.bot_invalid_per_minute' => 100]);

        for ($i = 0; $i < 3; $i++) {
            $this->postTelegram($this->telegramMessage(600 + $i, 'menu'), secret: 'salah')->assertStatus(401);
        }

        $this->postTelegram($this->telegramMessage(610, 'menu'), secret: 'salah')->assertStatus(429);
    }

    // ==================================================================
    // 4. DAFTAR PUTIH IP SUMBER MASUK
    // ==================================================================

    public function test_mode_enforce_menolak_ip_di_luar_daftar_untuk_ketiga_gateway(): void
    {
        foreach (['telegram', 'fonnte', 'openwa'] as $source) {
            InboundSourcePolicy::query()
                ->where('source_domain', 'bot_webhook')
                ->where('source_name', $source)
                ->update(['mode' => 'enforce']);
        }

        foreach (['telegram' => '/api/webhooks/bot/telegram', 'fonnte' => '/api/webhooks/bot/fonnte', 'openwa' => '/api/webhooks/bot/openwa'] as $path) {
            $this->withServerVariables(['REMOTE_ADDR' => '8.8.8.8'])
                ->postJson($path, [])
                ->assertStatus(403);
        }
    }

    public function test_kebijakan_hilang_memakai_mode_bawaan_enforce_untuk_telegram(): void
    {
        // Rute Telegram memakai fallback `enforce`: tanpa kebijakan sama sekali,
        // permintaan dari IP asing tetap ditolak (gagal tertutup).
        InboundSourcePolicy::query()
            ->where('source_domain', 'bot_webhook')
            ->where('source_name', 'telegram')
            ->delete();

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.99'])
            ->postJson('/api/webhooks/bot/telegram', $this->telegramMessage(700, 'menu'), [
                'X-Telegram-Bot-Api-Secret-Token' => self::TG_SECRET,
            ])
            ->assertStatus(403);
    }

    // ==================================================================
    // 5. REPLAY UPDATE TELEGRAM
    // ==================================================================

    public function test_update_id_yang_sama_tidak_diproses_dua_kali(): void
    {
        $payload = $this->telegramMessage(800, 'menu');

        $pertama = $this->postTelegram($payload);
        $kedua = $this->postTelegram($payload);

        $pertama->assertStatus(200);
        $kedua->assertStatus(200);
        $kedua->assertJsonPath('status', 'duplicate');

        // Hanya SATU baris bukti terima — replay tidak menambah baris.
        $this->assertSame(1, DB::table('telegram_update_receipts')
            ->where('update_id', 800)
            ->count());
    }

    public function test_update_id_berbeda_tetap_diproses(): void
    {
        $this->postTelegram($this->telegramMessage(810, 'menu'))->assertStatus(200);

        $lanjut = $this->postTelegram($this->telegramMessage(811, 'menu'));

        $lanjut->assertStatus(200);
        $this->assertNotSame('duplicate', $lanjut->json('status'));
    }

    public function test_replay_di_bot_scope_berbeda_diproses_terpisah(): void
    {
        // Scope memisahkan dua bot yang berjalan di instalasi yang sama; id
        // update dari bot A tidak boleh "menelan" id yang sama dari bot B.
        config(['services.telegram-bot-api.bot_scope' => 'alpha']);
        $this->postTelegram($this->telegramMessage(820, 'menu'))->assertStatus(200);

        config(['services.telegram-bot-api.bot_scope' => 'beta']);
        $hasil = $this->postTelegram($this->telegramMessage(820, 'menu'));

        $this->assertNotSame('duplicate', $hasil->json('status'));
        $this->assertSame(2, DB::table('telegram_update_receipts')->where('update_id', 820)->count());
    }

    public function test_update_id_negatif_tidak_disimpan_sebagai_bukti_replay(): void
    {
        // `update_id` Telegram selalu >= 0. Nilai negatif/ngawur tidak boleh
        // mengisi tabel bukti (bisa dipakai menumbuhkan tabel tanpa batas).
        $this->postTelegram($this->telegramMessage(-5, 'menu'));

        $this->assertSame(0, DB::table('telegram_update_receipts')->count());
    }

    // ==================================================================
    // 6. GATE KEANGGOTAAN CHANNEL (fail-closed)
    // ==================================================================

    private function enableGate(): void
    {
        config([
            'services.telegram-bot-api.required_channel.enabled' => true,
            'services.telegram-bot-api.required_channel.channels' => [
                ['id' => '@jasakodings', 'url' => 'https://t.me/jasakodings', 'label' => 'Jasakoding'],
            ],
        ]);
    }

    /** @param array<int, array<string, mixed>> $responses */
    private function fakeMembership(string $status): void
    {
        Http::fake(function ($request) use ($status) {
            if (str_contains($request->url(), 'getChatMember')) {
                return Http::response(['ok' => true, 'result' => ['status' => $status]]);
            }

            return Http::response(['ok' => true, 'result' => ['message_id' => 1]]);
        });
    }

    public function test_anggota_belum_bergabung_ditahan_di_gerbang(): void
    {
        $this->enableGate();
        $this->fakeMembership('left');

        $res = $this->postTelegram($this->telegramMessage(900, 'menu'));

        $res->assertStatus(200);
        // Balasan berisi ajakan bergabung, BUKAN katalog.
        $teks = TelegramMarkdown::fromLegacy((string) $res->json('method') ?: '');
        $this->assertStringContainsString('Akses Terbatas', TelegramMarkdown::fromLegacy($this->sentText()));
    }

    public function test_status_keanggotaan_yang_tidak_dikenal_ditahan_fail_closed(): void
    {
        // Status baru/aneh dari Telegram TIDAK boleh otomatis dianggap "boleh".
        $this->enableGate();
        $this->fakeMembership('status-yang-belum-dikenal');

        $this->assertSame(
            TelegramChannelMembershipService::STATUS_UNAVAILABLE,
            'unavailable',
            'Sanity: konstanta status harus stabil.',
        );

        $this->postTelegram($this->telegramMessage(910, 'menu'))->assertStatus(200);

        $this->assertStringNotContainsString(
            'LIST PRODUCT',
            $this->sentText(),
            'Status tak dikenal tidak boleh membuka katalog.',
        );
    }

    public function test_perintah_riwayat_tetap_lolos_saat_gerbang_aktif(): void
    {
        // Riwayat/status/bahasa = data milik pengirim sendiri; gangguan
        // verifikasi sesaat tidak boleh mengunci user dari transaksinya.
        $this->enableGate();
        $this->fakeMembership('left');

        $this->postTelegram($this->telegramMessage(920, 'riwayat'))->assertStatus(200);

        $this->assertStringNotContainsString('Akses Terbatas', $this->sentText());
    }

    public function test_masalah_konfigurasi_gerbang_tidak_mengundang_user_coba_lagi(): void
    {
        // Bot belum jadi anggota channel → setelan salah, TIDAK akan sembuh
        // sendiri. Pesannya harus pesan perbaikan, bukan "coba lagi".
        $this->enableGate();
        Http::fake(function ($request) {
            if (str_contains($request->url(), 'getChatMember')) {
                return Http::response([
                    'ok' => false,
                    'description' => 'Bad Request: chat not found',
                ], 400);
            }

            return Http::response(['ok' => true, 'result' => ['message_id' => 1]]);
        });

        $this->postTelegram($this->telegramMessage(930, 'menu'))->assertStatus(200);

        $teks = $this->sentText();

        // Masalah setelan → pesan PERBAIKAN (menyebut admin), bukan pesan
        // gangguan-sesaat yang menyuruh user sekadar menunggu.
        $this->assertStringContainsString('Layanan Sedang Diperbaiki', $teks);
        $this->assertStringContainsString('admin', strtolower($teks));
        $this->assertStringNotContainsString('Verifikasi Keanggotaan Bermasalah', $teks);
    }

    /** Teks pesan terakhir yang benar-benar dikirim bot ke Telegram. */
    private function sentText(): string
    {
        $teks = '';

        Http::recorded(function ($request, $response) use (&$teks) {
            if (! str_contains($request->url(), 'sendMessage')) {
                return true;
            }

            $teks = TelegramMarkdown::fromLegacy((string) ($request['text'] ?? ''));

            return true;
        });

        return $teks;
    }

    // ==================================================================
    // 7. PENYIMPAN NOMOR MENU: nomor asing & peta kedaluwarsa
    // ==================================================================

    private function store(): BotNumericMenuStore
    {
        return app(BotNumericMenuStore::class);
    }

    private function seedMenu(string $user = 'telegram:default:9876'): void
    {
        $this->store()->put($user, [
            'menu' => 'categories',
            'parent_menu' => null,
            'page' => 1,
            'entries' => [
                '1' => ['type' => 'content', 'label' => 'Top Up Games', 'command' => 'kategori top-up-games'],
            ],
        ], "LIST PRODUCT\n\n[1]. Top Up Games\n\n📆 05:58:50 PM");
    }

    public function test_nomor_di_luar_daftar_ditolak_bukan_ditebak(): void
    {
        $this->seedMenu();

        // Nomor asing tidak boleh "jatuh" ke entri pertama.
        $this->assertSame('invalid', $this->store()->resolve('telegram:default:9876', 2)['status']);
        $this->assertSame('invalid', $this->store()->resolve('telegram:default:9876', 9999)['status']);
    }

    public function test_nomor_98_99_dan_0_ditolak_kalau_layar_tidak_menyediakannya(): void
    {
        $this->seedMenu();

        // 98/99 = halaman, 0 = kembali. Layar ini tidak punya satupun; menerima
        // angka itu berarti melompat ke layar yang tidak pernah ditampilkan.
        foreach ([0, 98, 99, -1] as $nomor) {
            $this->assertSame(
                'invalid',
                $this->store()->resolve('telegram:default:9876', $nomor)['status'],
                "Nomor {$nomor} seharusnya tidak sah di layar ini.",
            );
        }
    }

    public function test_peta_nomor_kedaluwarsa_ditolak_walau_ada_di_cache(): void
    {
        $user = 'telegram:default:9999';
        $this->seedMenu($user);

        // Masa berlaku state = 360 menit (TTL_MINUTES). Yang diperiksa di sini
        // adalah timestamp DI DALAM state, bukan TTL cache: driver cache bisa
        // berbeda antar environment (file vs redis), dan state yang tersimpan
        // lebih lama dari masa berlakunya tetap tidak boleh dipakai.
        $this->assertSame('ok', $this->store()->resolve($user, 1)['status']);

        $this->travel(361)->minutes();

        $this->assertSame('expired', $this->store()->resolve($user, 1)['status']);
    }

    public function test_menu_dibuang_tidak_bisa_lagi_dipilih(): void
    {
        // Kasus nyata: user sedang mengetik ID game (angka murni) sementara peta
        // nomor layar sebelumnya masih menempel → pesanan salah sasaran.
        $user = 'telegram:default:8888';
        $this->seedMenu($user);
        $this->store()->forget($user);

        $this->assertSame('expired', $this->store()->resolve($user, 1)['status']);
    }

    public function test_entri_cacat_dibuang_tanpa_mematikan_seluruh_daftar(): void
    {
        $state = $this->store()->put('telegram:default:7777', [
            'menu' => 'categories',
            'parent_menu' => null,
            'page' => 1,
            'entries' => [
                '1' => ['type' => 'content', 'label' => 'Sah', 'command' => 'kategori sah'],
                '2' => ['type' => 'content', 'label' => 'Kosong', 'command' => '   '],
                '3' => ['type' => 'entri-jenis-asing', 'label' => 'Aneh', 'command' => 'kategori aneh'],
                '4' => 'bukan-array',
            ],
        ], 'teks');

        // Entri cacat dibuang; yang sah tetap hidup.
        $this->assertArrayHasKey('1', $state['entries']);
        $this->assertArrayNotHasKey('2', $state['entries']);
        $this->assertArrayNotHasKey('3', $state['entries']);
        $this->assertArrayNotHasKey('4', $state['entries']);

        $this->assertSame('ok', $this->store()->resolve('telegram:default:7777', 1)['status']);
    }

    // ==================================================================
    // 8. PEMBATAS LAJU PER-PENGIRIM (linking & deposit WhatsApp)
    // ==================================================================

    public function test_percobaan_linking_whatsapp_dibatasi_per_nomor(): void
    {
        config(['rate_limits.callbacks.link_per_sender_per_minute' => 2]);

        $handler = $this->whatsappHandler();

        $hasil = [];
        for ($i = 0; $i < 3; $i++) {
            $hasil[] = (string) ($handler->handle('link', ['123456'], [
                'source' => 'whatsapp_gateway',
                'whatsapp' => '628123450001',
                'external_user_id' => '628123450001',
            ])['text'] ?? '');
        }

        $this->assertStringNotContainsString('Terlalu banyak', $hasil[0]);
        $this->assertStringContainsString('Terlalu banyak', $hasil[2], 'Percobaan ke-3 harus dibatasi.');
    }

    public function test_batas_linking_tidak_memblokir_nomor_lain(): void
    {
        config(['rate_limits.callbacks.link_per_sender_per_minute' => 1]);

        $handler = $this->whatsappHandler();

        $context = fn (string $nomor): array => [
            'source' => 'whatsapp_gateway',
            'whatsapp' => $nomor,
            'external_user_id' => $nomor,
        ];

        $handler->handle('link', ['123456'], $context('628123450002'));
        $handler->handle('link', ['123456'], $context('628123450002'));

        // Nomor berbeda = keranjang berbeda.
        $this->assertStringNotContainsString(
            'Terlalu banyak',
            (string) $handler->handle('link', ['123456'], $context('628123450003'))['text'],
        );
    }

    public function test_percobaan_deposit_whatsapp_dibatasi_per_nomor(): void
    {
        config(['rate_limits.callbacks.deposit_per_sender_per_minute' => 2]);
        config(['services.telegram-bot-api.deposit_enabled' => true]);

        $handler = $this->whatsappHandler();

        for ($i = 0; $i < 2; $i++) {
            $handler->handle('deposit', [], [
                'source' => 'whatsapp_gateway',
                'whatsapp' => '628123450010',
                'external_user_id' => '628123450010',
            ]);
        }

        $this->assertStringContainsString(
            'Terlalu banyak',
            (string) $handler->handle('deposit', [], [
                'source' => 'whatsapp_gateway',
                'whatsapp' => '628123450010',
                'external_user_id' => '628123450010',
            ])['text'],
        );
    }

    public function test_linking_whatsapp_menolak_format_dan_nomor_tak_dikenal(): void
    {
        $handler = $this->whatsappHandler();

        // Kode wajib 6 digit; `ABC123` dan kosong harus ditolak sebelum
        // menyentuh layanan verifikasi.
        foreach (['ABC123', '12345', '', '1234567'] as $kode) {
            $this->assertStringContainsString(
                'Format salah',
                (string) $handler->handle('link', [$kode], [
                    'source' => 'whatsapp_gateway',
                    'whatsapp' => '628123450020',
                    'external_user_id' => '628123450020',
                ])['text'],
            );
        }
    }

    /** Handler dengan resolver WhatsApp yang tidak menyentuh database. */
    private function whatsappHandler(): BotCommandHandler
    {
        $this->mock(WhatsappUserResolver::class, function (MockInterface $mock): void {
            $mock->shouldReceive('resolve')->andReturn([
                'status' => WhatsappUserResolver::STATUS_LINKED,
                'user' => null,
            ]);
        });

        return app(BotCommandHandler::class);
    }

    // ==================================================================
    // 9. DATA YANG BOCOR LEWAT PESAN
    // ==================================================================

    public function test_payload_kosong_tidak_memicu_pemrosesan(): void
    {
        // Webhook kosong (mis. probe) harus diabaikan, bukan diproses jadi menu.
        $res = $this->postTelegram([]);

        $res->assertStatus(200);
        $res->assertJsonPath('status', 'ignored');
    }

    public function test_pesan_tanpa_pengirim_tidak_diproses(): void
    {
        $res = $this->postTelegram([
            'update_id' => 1000,
            'message' => [
                'message_id' => 1,
                'chat' => ['id' => 9876, 'type' => 'private'],
                'text' => 'menu',
            ],
        ]);

        $res->assertStatus(200);
        $res->assertJsonPath('status', 'ignored');
    }

    public function test_angka_telanjang_dari_user_asing_tidak_membuat_pesanan(): void
    {
        // Tanpa peta nomor aktif (belum pernah buka menu), "1" adalah teks biasa
        // — tidak boleh diartikan sebagai pemilihan entri.
        config(['services.telegram-bot-api.order_enabled' => true]);

        $res = $this->postTelegram($this->telegramMessage(1100, '1'));

        $res->assertStatus(200);
        $this->assertSame(0, DB::table('pembelians')->count(), 'Angka tanpa konteks tidak boleh membuat pesanan.');
    }
}
