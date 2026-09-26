<?php

namespace Tests\Feature\Bot;

use App\Models\SettingWeb;
use App\Services\Bot\BotCommandHandler;
use App\Services\Bot\BotGatewayCapabilities;
use App\Services\Bot\BotMessageFormatter;
use App\Support\TelegramMarkdown;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Kontak admin Telegram: diisi dari PANEL ADMIN (kolom
 * `setting_webs.telegram_admin_url`), lalu di-bridge ke
 * `services.telegram-bot-api.admin_contact_url`.
 *
 * Kontrak yang dijaga:
 *  1. Di pesan panduan, kontak admin berupa TAUTAN BERTANDA di dalam teks
 *     (`[💬 Klik di sini](https://t.me/...)`) sehingga bisa DIPENCET.
 *  2. Nomor WhatsApp (`nomor_admin`) TIDAK lagi muncul di pesan Telegram.
 *  3. Perintah `/admin` mengirim tombol inline bertipe `url` — jalur itu
 *     tidak memakai reply keyboard, jadi tombolnya benar-benar terkirim.
 *  4. Tautan tetap hidup setelah dikonversi ke MarkdownV2.
 */
class TelegramAdminContactTest extends TestCase
{
    use RefreshDatabase;

    private function seedSettings(array $overrides = []): SettingWeb
    {
        return SettingWeb::query()->create(array_merge([
            'id' => 1,
            'judul_web' => 'Demo Egymarket',
            'deskripsi_web' => 'Test store',
            'keywords' => 'test',
            'url_wa' => 'https://wa.me/6281234567890',
            'url_ig' => 'https://instagram.com/test',
            'url_tiktok' => 'https://tiktok.com/@test',
            'url_youtube' => 'https://youtube.com/@test',
            'url_fb' => 'https://facebook.com/test',
            'topupindo_api' => '-',
            'warna1' => '#111111',
            'warna2' => '#222222',
            'warna3' => '#333333',
            'warna4' => '#444444',
            'paydisini_apikey' => '-',
            'order_prefik' => 'INV',
        ], $overrides));
    }

    /**
     * Simulasikan jembatan DB->config seperti AppServiceProvider.
     *
     * Provider asli hanya berjalan di SAPI web (`!runningInConsole()`), jadi
     * di test kita replikasi barisnya persis — inilah kontrak yang diuji.
     */
    private function bridgeAdminUrlFromDatabase(): void
    {
        $row = SettingWeb::query()->find(1);

        if ($row && ! empty($row->telegram_admin_url)) {
            config(['services.telegram-bot-api.admin_contact_url' => $row->telegram_admin_url]);
        }
    }

    private function telegram(): BotGatewayCapabilities
    {
        return BotGatewayCapabilities::forSource(BotGatewayCapabilities::SOURCE_TELEGRAM);
    }

    public function test_panduan_memuat_tautan_bertanda_ke_admin(): void
    {
        config(['bot.order_enabled' => true]);
        $this->seedSettings(['telegram_admin_url' => 'https://t.me/alexander_vors']);
        $this->bridgeAdminUrlFromDatabase();

        $help = app(BotMessageFormatter::class)->formatHelp($this->telegram());

        $this->assertStringContainsString(
            '[💬 Klik di sini](https://t.me/alexander_vors)',
            $help['text'],
            'Kontak admin harus jadi tautan bertanda yang bisa dipencet.',
        );
    }

    public function test_tanpa_url_di_panel_tidak_ada_tautan_maupun_sebutan(): void
    {
        config(['bot.order_enabled' => true]);
        config(['services.telegram-bot-api.admin_contact_url' => '']);
        $this->seedSettings(['telegram_admin_url' => null]);
        $this->bridgeAdminUrlFromDatabase();

        $help = app(BotMessageFormatter::class)->formatHelp($this->telegram());

        $this->assertStringNotContainsString('Klik di sini', $help['text']);
        $this->assertStringNotContainsString('](', $help['text']);
        // Masih harus ada jalan keluar (perintah /admin).
        $this->assertStringContainsString('/admin', $help['text']);
    }

    public function test_nomor_wa_tidak_lagi_muncul_di_pesan_telegram(): void
    {
        config(['bot.order_enabled' => true]);
        $this->seedSettings([
            'telegram_admin_url' => 'https://t.me/alexander_vors',
            'nomor_admin' => '6285792464508',
        ]);
        $this->bridgeAdminUrlFromDatabase();

        $help = app(BotMessageFormatter::class)->formatHelp($this->telegram());

        // REGRESI: dulu pesan Telegram menampilkan nomor WhatsApp mentah,
        // yang tidak bisa dipencet dan membocorkan nomor pribadi.
        $this->assertStringNotContainsString('6285792464508', $help['text']);
    }

    public function test_whatsapp_tetap_menampilkan_url_apa_adanya(): void
    {
        config(['bot.order_enabled' => true]);
        config(['services.telegram-bot-api.admin_contact_url' => 'https://wa.me/6285792464508']);

        $help = app(BotMessageFormatter::class)->formatHelp(
            BotGatewayCapabilities::forSource(BotGatewayCapabilities::SOURCE_WHATSAPP),
        );

        // WhatsApp tidak mengenal tautan bertanda, jadi URL tampil utuh dan
        // bisa diketuk langsung.
        $this->assertStringContainsString('https://wa.me/6285792464508', $help['text']);
        $this->assertStringNotContainsString('](', $help['text'], 'Sintaks tautan Telegram tidak boleh bocor ke WhatsApp.');
    }

    public function test_perintah_admin_mengirim_tombol_url_yang_bisa_dipencet(): void
    {
        config(['bot.order_enabled' => true]);
        $this->seedSettings(['telegram_admin_url' => 'https://t.me/alexander_vors']);
        $this->bridgeAdminUrlFromDatabase();

        // Jalur ini TIDAK memakai reply keyboard, jadi tombol inline-nya
        // benar-benar terkirim (bukan dibuang adapter).
        $response = app(BotCommandHandler::class)->handle('admin', [], [
            'source' => 'telegram_gateway',
            'external_user_id' => 'telegram:9876',
            'telegram_user_id' => 9876,
        ]);

        $this->assertArrayNotHasKey('use_reply_keyboard', $response, 'Tombol inline butuh pesan tanpa reply keyboard.');

        $button = collect($response['buttons'])->flatten(1)->first();

        $this->assertSame('💬 Chat Admin', $button['text']);
        $this->assertSame('https://t.me/alexander_vors', $button['url']);
    }

    public function test_perintah_admin_tanpa_url_tidak_mengirim_tombol_rusak(): void
    {
        config(['bot.order_enabled' => true]);
        config(['services.telegram-bot-api.admin_contact_url' => '']);
        $this->seedSettings(['telegram_admin_url' => null]);
        $this->bridgeAdminUrlFromDatabase();

        $response = app(BotCommandHandler::class)->handle('admin', [], [
            'source' => 'telegram_gateway',
            'external_user_id' => 'telegram:9876',
            'telegram_user_id' => 9876,
        ]);

        $this->assertSame([], $response['buttons'], 'Tombol kosong lebih baik daripada tombol tanpa tujuan.');
    }

    public function test_tautan_inline_bertahan_setelah_konversi_markdownv2(): void
    {
        // Bug nyata: `fromLegacy()` meng-escape `[`, `]`, `(`, `)` sehingga
        // tautan tampil mentah dan TIDAK bisa dipencet.
        $converted = TelegramMarkdown::fromLegacy(
            'Jika ada kendala: [💬 Klik di sini](https://t.me/alexander_vors)',
        );

        $this->assertStringContainsString('[💬 Klik di sini](https://t.me/alexander_vors)', $converted);
        $this->assertStringNotContainsString('\[', $converted);
    }

    public function test_underscore_di_dalam_url_tidak_dirusak_escape(): void
    {
        // `_` dan `.` di dalam URL TIDAK boleh di-escape, kalau tidak
        // alamatnya rusak dan tautannya gagal dibuka.
        $converted = TelegramMarkdown::fromLegacy('[Chat](https://t.me/namaku_ganteng.bot)');

        $this->assertStringContainsString('(https://t.me/namaku_ganteng.bot)', $converted);
        $this->assertStringNotContainsString('namaku\\_ganteng', $converted);
    }

    public function test_keyboard_tetap_tidak_punya_label_admin_menyesatkan(): void
    {
        config(['bot.order_enabled' => true]);
        $this->seedSettings(['telegram_admin_url' => 'https://t.me/alexander_vors']);
        $this->bridgeAdminUrlFromDatabase();

        $keyboard = app(BotMessageFormatter::class)->defaultReplyKeyboard($this->telegram());

        $labels = array_map(
            static fn (array $row): string => (string) ($row[0]['text'] ?? ''),
            $keyboard['keyboard'],
        );

        // Reply keyboard hanya bisa membawa teks, jadi label admin di sana
        // adalah tombol mati. Kontak admin tidak dipasang di sini.
        $this->assertNotContains('📞 Hubungi Admin', $labels);
    }
}
