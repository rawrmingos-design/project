<?php

namespace Tests\Feature\Bot;

use App\Models\CategoryType;
use App\Models\InboundSourcePolicy;
use App\Models\Kategori;
use App\Models\Layanan;
use App\Services\Bot\BotMessageFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Fase 1: copy bot Telegram boleh dipindah ke file lang, TAPI hanya boleh
 * muncul berbahasa Indonesia selama user belum memilih bahasa lain.
 *
 * Kenapa test ini ada:
 * `__()` memakai `app()->getLocale()`, dan locale itu bisa berubah per-request.
 * Kalau suatu saat `LanguageDetectMiddleware` ikut terpasang di grup rute bot
 * (sekarang grup `api`, jadi TIDAK terpasang), maka header `Accept-Language`
 * yang dikirim TELEGRAM — bukan user — akan menentukan bahasa balasan bot.
 * Test ini mengunci: apa pun header yang masuk, teks yang dilihat user tetap
 * IDENTIK dengan baseline sebelum refactor.
 *
 * Kalau test ini merah, artinya Fase 1 mengubah perilaku. Itu regresi, bukan
 * sekadar "kebetulan bahasa Inggris".
 */
class TelegramCopyPhaseOneTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        config(['services.telegram-bot-api.token' => 'dummy-token']);
        config(['services.telegram-bot-api.webhook_secret' => 'dummy-secret']);

        // Seed setting_webs dulu. Tanpa ini, AppServiceProvider membaca
        // bot_order_tg_enabled=false dari DB saat request HTTP dan menimpa
        // config() di atas — webhook lalu menolak memproses order.
        DB::table('setting_webs')->updateOrInsert(
            ['id' => 1],
            [
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
            ],
        );
        config(['app.name' => 'Test Store']);

        // Rute bot dijaga middleware inbound.whitelist; mode 'disabled' =
        // tidak memblokir request lokal (pola sama dengan BotWebhookTest).
        InboundSourcePolicy::query()->create([
            'source_domain' => 'bot_webhook',
            'source_name' => 'telegram',
            'mode' => 'disabled',
            'is_active' => true,
        ]);
    }

    private function postTelegramAsBot(array $data, array $headers = [])
    {
        return $this->postJson('/api/webhooks/bot/telegram', $data, array_merge([
            'X-Telegram-Bot-Api-Secret-Token' => config('services.telegram-bot-api.webhook_secret', ''),
        ], $headers));
    }

    /** Teks yang benar-benar dilihat user (MarkdownV2 di-unescape). */
    private function visibleText(?string $escaped): string
    {
        return preg_replace('/\\\\(.)/u', '$1', (string) $escaped);
    }

    public function test_intro_dan_tagline_tetap_indonesia_walau_header_minta_inggris(): void
    {
        CategoryType::query()->create(['name' => '🎮 Top Up', 'slug' => 'top-up', 'sort' => 1]);
        $kategori = Kategori::factory()->create(['category_type_id' => 1, 'kode' => 'mlbb', 'status' => 'active']);
        Layanan::factory()->create(['kategori_id' => $kategori->id, 'status' => 'available']);

        Http::fake([
            'https://api.telegram.org/*/sendMessage' => Http::response(['ok' => true]),
        ]);

        $this->postTelegramAsBot([
            'message' => [
                'chat' => ['id' => 12345, 'type' => 'private'],
                'from' => ['id' => 9876, 'language_code' => 'en'],
                'text' => '/menu',
                'message_id' => 111,
            ],
        ], ['Accept-Language' => 'en-US,en;q=0.9'])->assertOk();

        Http::assertSent(function ($request): bool {
            if (! str_contains($request->url(), 'sendMessage')) {
                return false;
            }

            $text = $this->visibleText($request['text']);

            return str_contains($text, '👋 *Selamat datang di Test Store*')
                && str_contains($text, 'Penuhi kebutuhan game & aplikasi premium kamu, semua dari satu tempat.')
                && str_contains($text, '🏠 *Menu Utama*')
                && str_contains($text, 'Pilih kategori di bawah untuk mulai. 👇')
                && ! str_contains($text, 'Welcome to')
                && ! str_contains($text, 'Main Menu');
        }, 'Balasan ke user Telegram harus tetap Bahasa Indonesia (baseline sebelum refactor).');
    }

    public function test_formatter_langsung_tetap_indonesia(): void
    {
        $menu = app(BotMessageFormatter::class)->formatCategories([
            'ok' => true,
            'data' => [['name' => 'Top Up Games', 'slug' => 'top-up-games']],
        ]);

        $this->assertStringContainsString('🏠 *Menu Utama*', $menu['text']);
        $this->assertStringContainsString('Pilih kategori di bawah untuk mulai. 👇', $menu['text']);
    }

    public function test_pesan_kategori_kosong_tetap_indonesia(): void
    {
        $menu = app(BotMessageFormatter::class)->formatCategories(['ok' => false, 'data' => []]);

        $this->assertSame('Maaf, daftar tipe kategori sedang tidak tersedia.', $menu['text']);
        $this->assertSame([], $menu['buttons']);
    }

    public function test_nama_kategori_kosong_dapat_fallback_indonesia(): void
    {
        $menu = app(BotMessageFormatter::class)->formatCategories([
            'ok' => true,
            'data' => [['name' => '', 'slug' => 'top-up-games']],
        ]);

        $labels = collect($menu['buttons'])->flatten(1)->pluck('text')->all();

        $this->assertTrue(
            collect($labels)->contains(fn (string $l): bool => str_contains($l, 'Kategori')),
            'Kategori tanpa nama harus dapat fallback "Kategori". Label: ' . implode(' | ', $labels),
        );
    }
}
