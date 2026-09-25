<?php

namespace Tests\Feature\Bot;

use App\Services\Bot\BotCommandHandler;
use App\Services\Bot\BotMessageFormatter;
use App\Services\Bot\GatewayInvoiceService;
use App\Services\Bot\GatewayPricingService;
use App\Support\TelegramMarkdown;
use App\Services\Bot\TelegramChannelMembershipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Pesan SESUDAH join + penerjemahan MarkdownV2 untuk balasan bot.
 *
 * Latar belakang: saat gate keanggotaan terlewati, user sebelumnya dilempar
 * langsung ke menu tanpa pesan apa pun — tidak ada tanda bahwa syaratnya
 * sudah terpenuhi. Sekaligus balasan bot dipindah ke MarkdownV2, yang
 * mewajibkan escape karakter seperti `.`, `!`, `(`, `|`.
 */
class TelegramJoinFlowCopyTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.telegram-bot-api.token' => 'dummy-token',
            'services.telegram-bot-api.webhook_secret' => 'dummy-secret',
            'services.telegram-bot-api.order_enabled' => true,
        ]);
    }

    /**
     * Kirim update lewat ENDPOINT WEBHOOK ASLI, bukan memanggil adapter
     * langsung — supaya jalur yang diuji sama dengan jalur produksi.
     */
    private function sendUpdate(int $updateId, string $text): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/api/webhooks/bot/telegram', [
            'update_id' => $updateId,
            'message' => [
                'chat' => ['id' => 12345],
                'from' => ['id' => 9876, 'first_name' => 'Mings'],
                'text' => $text,
                'message_id' => 100 + $updateId,
            ],
        ], [
            'X-Telegram-Bot-Api-Secret-Token' => 'dummy-secret',
        ]);
    }

    /**
     * Konfigurasi gate: satu channel wajib, user dianggap BELUM bergabung
     * pada percobaan pertama lalu SUDAH bergabung pada percobaan berikutnya.
     */
    private function fakeGate(bool &$joined): void
    {
        config([
            'services.telegram-bot-api.required_channel.enabled' => true,
            'services.telegram-bot-api.required_channel.channels' => [
                ['id' => '@jasakodings', 'url' => 'https://t.me/jasakodings', 'label' => 'Jasakoding'],
            ],
        ]);

        Http::fake(function ($request) use (&$joined) {
            if (str_contains($request->url(), 'getChatMember')) {
                return Http::response([
                    'ok' => true,
                    'result' => ['status' => $joined ? 'member' : 'left'],
                ]);
            }

            return Http::response(['ok' => true, 'result' => ['message_id' => 1]]);
        });
    }

    private function seedInboundPolicy(): void
    {
        \Illuminate\Support\Facades\DB::table('setting_webs')->updateOrInsert(
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
            ],
        );

        config(['app.name' => 'Test Store']);

        \App\Models\InboundSourcePolicy::query()->updateOrCreate(
            ['source_domain' => 'bot_webhook', 'source_name' => 'telegram'],
            ['mode' => 'disabled', 'is_active' => true],
        );
    }

    public function test_user_blocked_by_gate_sees_membership_message(): void
    {
        $this->seedInboundPolicy();
        $joined = false;
        $this->fakeGate($joined);

        $this->sendUpdate(1, '/menu');

        Http::assertSent(function ($request): bool {
            if (! str_contains($request->url(), 'sendMessage')) {
                return false;
            }

            $text = preg_replace('/\\\\(.)/u', '$1', (string) $request['text']);

            return str_contains($text, 'Akses Terbatas')
                && str_contains($text, 'bergabung ke channel berikut')
                && str_contains($text, 'Sudah Bergabung');
        });
    }

    public function test_user_who_joined_gets_confirmation_message(): void
    {
        $this->seedInboundPolicy();
        $joined = false;
        $this->fakeGate($joined);

        // Percobaan 1: tertahan di gerbang.
        $this->sendUpdate(1, '/menu');

        // User bergabung, lalu mencoba lagi.
        $joined = true;

        $this->sendUpdate(2, '/menu');

        Http::assertSent(function ($request): bool {
            if (! str_contains($request->url(), 'sendMessage')) {
                return false;
            }

            $text = preg_replace('/\\\\(.)/u', '$1', (string) $request['text']);

            // Pesan konfirmasi harus menyebut nama user DAN menawarkan jalan
            // lanjut ke menu.
            return str_contains($text, 'Verifikasi Berhasil')
                && str_contains($text, 'Halo Mings!')
                && str_contains($text, 'menu');
        });
    }

    /**
     * Ketika BOT sendiri tidak bisa membaca channel, user harus dapat pesan
     * yang jujur (ini masalah kami, sudah dilaporkan) — BUKAN disuruh
     * "coba lagi" selamanya, dan BUKAN disuruh join padahal itu tidak akan
     * menolong.
     */
    public function test_gate_shows_maintenance_message_when_bot_cannot_read_channel(): void
    {
        $this->seedInboundPolicy();

        config([
            'services.telegram-bot-api.required_channel.enabled' => true,
            'services.telegram-bot-api.required_channel.channels' => [
                ['id' => '@jasakodings', 'url' => 'https://t.me/jasakodings', 'label' => 'Jasakoding'],
            ],
            'services.telegram-bot-api.admin_contact_url' => 'https://wa.me/6285792464508',
        ]);

        Http::fake([
            '*' => Http::response([
                'ok' => false,
                'error_code' => 400,
                'description' => 'Bad Request: member list is inaccessible',
            ], 400),
        ]);

        Cache::flush();

        $this->sendUpdate(9901, 'menu')->assertOk();

        $sent = collect(Http::recorded())
            ->first(fn (array $pair): bool => str_contains($pair[0]->url(), 'sendMessage'));

        $this->assertNotNull($sent, 'Bot harus membalas sesuatu, bukan diam.');
        $text = (string) ($sent[0]['text'] ?? '');

        $this->assertStringContainsString('Layanan Sedang Diperbaiki', $text);
        $this->assertStringNotContainsString('Akses Terbatas', $text, 'Jangan salahkan user.');
        $this->assertStringContainsString('admin', $text);
    }

    /**
     * Keamanan: status yang TIDAK dikenal handler tidak boleh dianggap
     * "boleh lewat".
     *
     * Ini nyata terjadi — saat kelas service sudah punya status baru
     * (`misconfigured`) tapi handler-nya belum mengenalinya, user langsung
     * lolos gate tanpa verifikasi. Handler sekarang menahan SEMUA status
     * selain `allowed`.
     */
    public function test_unknown_membership_status_never_lets_the_user_through(): void
    {
        $this->seedInboundPolicy();

        config([
            'services.telegram-bot-api.required_channel.enabled' => true,
            'services.telegram-bot-api.required_channel.channels' => [
                ['id' => '@jasakodings', 'url' => 'https://t.me/jasakodings', 'label' => 'Jasakoding'],
            ],
        ]);

        // Status yang sama sekali tidak dikenal handler — situasi nyata saat
        // kelas service sudah punya status baru tapi handler belum tahu.
        $this->mock(TelegramChannelMembershipService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('check')->andReturn(['status' => 'status_karangan']);
        });

        Cache::flush();

        $response = app(BotCommandHandler::class)->handle('menu', [], [
            'source' => 'telegram_gateway',
            'external_user_id' => 'telegram:9876',
            'telegram_user_id' => 9876,
            'chat_id' => '12345',
            'telegram_metadata' => ['first_name' => 'Mings'],
        ]);

        $text = (string) ($response['text'] ?? '');

        $this->assertStringContainsString('Verifikasi Keanggotaan Bermasalah', $text);
        $this->assertStringNotContainsString('Menu Utama', $text, 'Katalog TIDAK boleh terbuka.');
    }

    public function test_confirmation_message_only_appears_once(): void
    {
        $this->seedInboundPolicy();
        $joined = false;
        $this->fakeGate($joined);

        $this->sendUpdate(1, '/menu');

        $joined = true;

        // Dua kali percobaan setelah bergabung.
        $this->sendUpdate(2, '/menu');
        $this->sendUpdate(3, '/menu');

        // Sapaan verifikasi hanya boleh SEKALI; selebihnya menu biasa.
        $verifikasi = 0;
        Http::assertSent(function ($request) use (&$verifikasi): bool {
            if (str_contains($request->url(), 'sendMessage')
                && str_contains((string) $request['text'], 'Verifikasi Berhasil')) {
                $verifikasi++;
            }

            return true;
        });

        $this->assertSame(1, $verifikasi, 'Sapaan verifikasi muncul sekali saja.');
    }

    public function test_user_already_member_never_sees_confirmation(): void
    {
        $this->seedInboundPolicy();
        $joined = true;
        $this->fakeGate($joined);

        $this->sendUpdate(1, '/menu');

        Http::assertNotSent(fn ($request): bool => str_contains($request->url(), 'sendMessage')
            && str_contains((string) ($request['text'] ?? ''), 'Verifikasi Berhasil'));
    }

    public function test_gate_copy_uses_singular_only_for_single_channel(): void
    {
        $formatter = app(BotMessageFormatter::class);

        $single = $formatter->formatTelegramMembershipRequired([
            ['id' => '@satu', 'url' => 'https://t.me/satu', 'label' => 'Satu'],
        ]);

        $many = $formatter->formatTelegramMembershipRequired([
            ['id' => '@satu', 'url' => 'https://t.me/satu', 'label' => 'Satu'],
            ['id' => '@dua', 'url' => 'https://t.me/dua', 'label' => 'Dua'],
        ]);

        $this->assertStringContainsString('bergabung ke channel berikut', $single['text']);
        $this->assertStringNotContainsString('ke *semua* channel', $single['text']);
        $this->assertStringContainsString('ke *semua* channel', $many['text']);
    }

    // ------------------------------------------------------------------
    // Penerjemahan Markdown lama -> MarkdownV2 untuk balasan bot
    // ------------------------------------------------------------------

    public function test_legacy_reply_is_escaped_for_markdown_v2(): void
    {
        // Karakter ini DITOLAK Telegram di MarkdownV2 kalau tidak di-escape.
        // Diuji langsung ke API: teks bot gagal total tanpa konversi.
        $hasil = TelegramMarkdown::fromLegacy(
            'Selamat datang di Test Store. Harga Rp 1.050 (sudah termasuk fee)!',
        );

        $this->assertStringContainsString('Test Store\\.', $hasil);
        $this->assertStringContainsString('Rp 1\\.050', $hasil);
        $this->assertStringContainsString('\\(sudah termasuk fee\\)\\!', $hasil);
    }

    public function test_legacy_formatting_markers_survive_conversion(): void
    {
        $hasil = TelegramMarkdown::fromLegacy('*🏠 Menu Utama* dan `menu` serta __penting__');

        $this->assertSame('*🏠 Menu Utama* dan `menu` serta __penting__', $hasil);
    }

    public function test_code_span_content_is_not_double_escaped(): void
    {
        // Di dalam `code`, hanya `\` dan backtick yang perlu escape.
        // Kalau titik ikut di-escape, user akan melihat `\.` di layar.
        $hasil = TelegramMarkdown::fromLegacy('Ketik `invoice 456 qris 1234567` ya.');

        $this->assertStringContainsString('`invoice 456 qris 1234567`', $hasil);
        $this->assertStringNotContainsString('456\\.', $hasil);
    }

    public function test_manual_escape_from_legacy_style_is_normalized(): void
    {
        // Teks bot lama sudah menulis `\_` sendiri; jangan sampai jadi `\\_`.
        $hasil = TelegramMarkdown::fromLegacy('User 62812\_3456\_7890 sudah aktif.');

        $this->assertStringContainsString('62812\\_3456\\_7890', $hasil);
        $this->assertStringNotContainsString('\\\\_', $hasil);
    }

    public function test_legacy_url_stays_intact(): void
    {
        $hasil = TelegramMarkdown::fromLegacy('Reset password: https://istanatopup.id/forgot-password');

        $this->assertStringContainsString('https://istanatopup\\.id/forgot\\-password', $hasil);

        // Yang dilihat user harus URL utuh tanpa backslash.
        $terlihat = preg_replace('/\\\\(.)/u', '$1', $hasil);

        $this->assertStringContainsString('https://istanatopup.id/forgot-password', $terlihat);
    }

    public function test_line_breaks_are_preserved(): void
    {
        $hasil = TelegramMarkdown::fromLegacy("Baris satu.\nBaris dua.");

        $this->assertSame("Baris satu\\.\nBaris dua\\.", $hasil);
    }
}
