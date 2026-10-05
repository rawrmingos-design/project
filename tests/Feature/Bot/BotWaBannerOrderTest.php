<?php

namespace Tests\Feature\Bot;

use App\Models\CategoryType;
use App\Models\InboundSourcePolicy;
use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Paket;
use App\Models\SettingWeb;
use App\Services\Bot\Adapters\FonnteAdapter;
use App\Services\Bot\Adapters\OpenWaAdapter;
use App\Services\WhatsappNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Mockery;

/**
 * URUTAN gambar vs teks pada balasan bot WhatsApp.
 *
 * Permintaan pemilik produk: gambar Menu Utama harus tampil LEBIH DULU
 * dengan teks sebagai caption, tidak lagi teks dulu baru gambar menyusul
 * (yang membuat gambar terasa "telat" beberapa detik karena gateway harus
 * mengunduh gambar setelah teks sudah terkirim).
 *
 * Yang dijaga test ini:
 *
 * 1. **Gambar + teks = SATU panggilan kirim.** Adapter memanggil
 *    `sendMessage($target, $teks, $foto)` sekali; TIDAK ada lagi panggilan
 *    kedua berisi gambar tanpa teks. `WhatsappNotificationService` yang
 *    memecah payload menjadi `send-image` ber-caption.
 * 2. **Tanpa gambar tetap satu panggilan teks.** Perilaku lama tidak berubah.
 * 3. **Fail-safe.** Kalau kirim ber-media gagal, teks dikirim ulang sebagai
 *    teks supaya balasan tidak pernah kosong (payload ber-media tidak lagi
 *    membawa teks terpisah).
 *
 * `WhatsappNotificationService` di-mock: yang diuji adalah KEPUTUSAN dan
 * URUTAN panggilan di adapter, bukan transport HTTP-nya.
 */
class BotWaBannerOrderTest extends TestCase
{
    use RefreshDatabase;

    private const BANNER = 'assets/bot/test-order-banner.webp';

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([['bot_webhook', 'openwa', '103.31.205.166'], ['bot_webhook', 'fonnte', '103.31.205.166']] as [$domain, $name, $ip]) {
            $policy = InboundSourcePolicy::query()->firstOrCreate(
                ['source_domain' => $domain, 'source_name' => $name],
                ['mode' => 'enforce', 'is_active' => true],
            );
            $policy->entries()->firstOrCreate(
                ['value_type' => 'ipv4', 'value' => $ip],
                ['is_active' => true],
            );
        }

        $absolute = public_path(self::BANNER);
        @mkdir(dirname($absolute), 0775, true);
        file_put_contents($absolute, 'fixture');

        $this->createSettings(['bot_menu_banner' => self::BANNER]);
        $this->seedCatalog();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        @unlink(public_path(self::BANNER));
        @rmdir(public_path('assets/bot'));

        parent::tearDown();
    }

    private function createSettings(array $overrides = []): SettingWeb
    {
        return SettingWeb::query()->create(array_merge([
            'id' => 1,
            'judul_web' => 'Test Topup',
            'deskripsi_web' => 'Deskripsi test',
            'keywords' => 'topup,test',
            'url_wa' => 'https://wa.me/628123456789',
            'url_ig' => 'https://instagram.com/test',
            'url_tiktok' => 'https://tiktok.com/@test',
            'url_youtube' => 'https://youtube.com/@test',
            'url_fb' => 'https://facebook.com/test',
            'topupindo_api' => 'dummy-api',
            'warna1' => '#111111',
            'warna2' => '#222222',
            'warna3' => '#333333',
            'warna4' => '#444444',
            'paydisini_apikey' => 'dummy-paydisini',
            'order_prefik' => 'INV',
            'public_theme' => 'default',
            'bot_order_wa_enabled' => 1,
        ], $overrides));
    }

    /**
     * Tanpa katalog nyata, `GatewayCatalogService` mengembalikan daftar kosong
     * dan bot membalas "daftar tipe kategori tidak tersedia" -- bukan menu.
     * Karena itu seed 1 kategori + 1 layanan berpaket, persis seperti
     * prasyarat `BotWebhookTest`.
     */
    private function seedCatalog(): void
    {
        CategoryType::query()->create([
            'name' => '🎮 Top Up',
            'slug' => 'top-up',
            'sort' => 1,
        ]);

        $kategori = Kategori::factory()->create([
            'category_type_id' => 1,
            'kode' => 'mlbb',
            'status' => 'active',
        ]);

        $layanan = Layanan::factory()->create([
            'kategori_id' => $kategori->id,
            'status' => 'available',
        ]);

        $paket = Paket::query()->firstOrCreate(['nama' => '⚡ Proses Instant']);
        $paket->layanan()->syncWithoutDetaching([$layanan->id => ['product_logo' => null]]);
    }

    /**
     * Balasan 'menu' via OpenWA: gambar + teks harus dalam SATU panggilan,
     * dengan URL gambar terisi. Kalau adapter masih memanggil dua kali
     * (teks lalu gambar kosong), test ini MERAH.
     */
    public function test_openwa_menu_mengirim_gambar_dan_teks_dalam_satu_panggilan(): void
    {
        $calls = [];

        $this->mock(WhatsappNotificationService::class, function (Mockery\MockInterface $mock) use (&$calls): void {
            $mock->shouldReceive('sendMessage')
                ->once()
                ->andReturnUsing(function (string $target, string $message, ?string $url = null) use (&$calls) {
                    $calls[] = ['target' => $target, 'message' => $message, 'url' => $url];

                    return ['success' => true];
                });
        });

        $request = Request::create('/api/webhooks/bot/openwa', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($this->openwaPayload('menu')));

        $response = app(OpenWaAdapter::class)->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(1, $calls, 'Gambar dan teks harus SATU panggilan kirim, bukan dua.');
        $this->assertNotNull($calls[0]['url'], 'URL gambar harus ikut di panggilan yang sama.');
        $this->assertStringContainsString('/assets/bot/', (string) $calls[0]['url']);
        $this->assertStringContainsString('Menu Utama', $calls[0]['message']);
        $this->assertStringContainsString('Top Up', $calls[0]['message'], 'Kategori dari katalog ikut terkirim.');
    }

    /**
     * Banner kosong = satu panggilan teks biasa (perilaku lama tak berubah).
     */
    public function test_openwa_tanpa_banner_tetap_satu_panggilan_teks(): void
    {
        SettingWeb::query()->where('id', 1)->update(['bot_menu_banner' => null]);
        Cache::flush();

        $calls = [];

        $this->mock(WhatsappNotificationService::class, function (Mockery\MockInterface $mock) use (&$calls): void {
            $mock->shouldReceive('sendMessage')
                ->once()
                ->andReturnUsing(function (string $target, string $message, ?string $url = null) use (&$calls) {
                    $calls[] = ['message' => $message, 'url' => $url];

                    return ['success' => true];
                });
        });

        $request = Request::create('/api/webhooks/bot/openwa', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($this->openwaPayload('menu')));

        app(OpenWaAdapter::class)->handle($request);

        $this->assertCount(1, $calls);
        $this->assertNull($calls[0]['url'], 'Tanpa banner tidak ada URL media.');
        $this->assertStringContainsString('Menu Utama', $calls[0]['message']);
    }

    /**
     * Fail-safe: kirim ber-media gagal -> teks dikirim ulang sebagai teks,
     * supaya user tetap menerima menu (payload ber-media tidak membawa teks
     * terpisah, jadi teksnya ikut hilang kalau tidak dikirim ulang).
     */
    public function test_openwa_kirim_ber_media_gagal_menyusul_teks_murni(): void
    {
        $calls = [];

        $this->mock(WhatsappNotificationService::class, function (Mockery\MockInterface $mock) use (&$calls): void {
            $mock->shouldReceive('sendMessage')
                ->twice()
                ->andReturnUsing(function (string $target, string $message, ?string $url = null) use (&$calls) {
                    $calls[] = ['message' => $message, 'url' => $url];

                    // Percobaan pertama (ber-media) gagal, percobaan kedua (teks) sukses.
                    return ['success' => count($calls) > 1];
                });
        });

        $request = Request::create('/api/webhooks/bot/openwa', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($this->openwaPayload('menu')));

        app(OpenWaAdapter::class)->handle($request);

        $this->assertCount(2, $calls, 'Gagal kirim ber-media harus menyusul dengan teks murni.');
        $this->assertNotNull($calls[0]['url'], 'Percobaan pertama membawa gambar.');
        $this->assertNull($calls[1]['url'], 'Percobaan kedua teks murni tanpa media.');
        $this->assertStringContainsString('Menu Utama', $calls[1]['message']);
    }

    /**
     * Adapter Fonnte (WA gateway kedua) harus memakai urutan yang sama.
     */
    public function test_fonnte_menu_mengirim_gambar_dan_teks_dalam_satu_panggilan(): void
    {
        $calls = [];

        $this->mock(WhatsappNotificationService::class, function (Mockery\MockInterface $mock) use (&$calls): void {
            $mock->shouldReceive('sendMessage')
                ->once()
                ->andReturnUsing(function (string $target, string $message, ?string $url = null) use (&$calls) {
                    $calls[] = ['message' => $message, 'url' => $url];

                    return ['success' => true];
                });
        });

        $request = Request::create('/api/webhooks/bot/fonnte', 'POST', [
            'sender' => '6281000000009',
            'message' => 'menu',
        ]);

        app(FonnteAdapter::class)->handle($request);

        $this->assertCount(1, $calls, 'Fonnte juga harus satu panggilan untuk gambar + teks.');
        $this->assertNotNull($calls[0]['url']);
        $this->assertStringContainsString('/assets/bot/', (string) $calls[0]['url']);
        $this->assertStringContainsString('Menu Utama', $calls[0]['message']);
    }

    private function openwaPayload(string $body): array
    {
        return [
            'event' => 'message.received',
            'timestamp' => '2026-10-03T12:00:00.000Z',
            'sessionId' => 'f802a400-0cf5-4c28-b7b0-aa30c169aee5',
            'idempotencyKey' => 'order-test-' . $body,
            'deliveryId' => 'dlv-order-' . $body,
            'data' => [
                'id' => 'WA-ORDER-001',
                'from' => '6281000000001@c.us',
                'to' => '6287780901780@c.us',
                'chatId' => '6281000000001@c.us',
                'body' => $body,
                'type' => 'text',
                'timestamp' => 1791000000,
                'fromMe' => false,
                'isGroup' => false,
                'kind' => 'individual',
            ],
        ];
    }
}
