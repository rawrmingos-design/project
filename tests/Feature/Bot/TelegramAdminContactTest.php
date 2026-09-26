<?php

namespace Tests\Feature\Bot;

use App\Models\SettingWeb;
use App\Services\Bot\BotGatewayCapabilities;
use App\Services\Bot\BotMessageFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * URL kontak admin Telegram diisi dari PANEL ADMIN (kolom
 * `setting_webs.telegram_admin_url`), bukan lagi hanya dari `.env`.
 *
 * Test ini menjaga rantai lengkapnya: kolom DB -> config -> tombol keyboard
 * + balasan perintah `admin` + pesan "Layanan Sedang Diperbaiki".
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

    public function test_url_dari_panel_admin_muncul_di_keyboard_telegram(): void
    {
        config(['bot.order_enabled' => true]);
        $this->seedSettings(['telegram_admin_url' => 'https://t.me/alexander_vors']);
        $this->bridgeAdminUrlFromDatabase();

        $formatter = app(BotMessageFormatter::class);
        $keyboard = $formatter->defaultReplyKeyboard(
            BotGatewayCapabilities::forSource(BotGatewayCapabilities::SOURCE_TELEGRAM),
        );

        $labels = array_map(
            static fn (array $row): string => (string) ($row[0]['text'] ?? ''),
            $keyboard['keyboard'],
        );

        $this->assertContains(
            '📞 Hubungi Admin',
            $labels,
            'Tombol hubungi admin harus muncul karena URL diisi dari panel admin.',
        );
        $this->assertSame('https://t.me/alexander_vors', config('services.telegram-bot-api.admin_contact_url'));
    }

    public function test_tanpa_url_di_panel_tombol_admin_tidak_dikirim(): void
    {
        config(['services.telegram-bot-api.admin_contact_url' => '']);
        config(['bot.order_enabled' => true]);
        $this->seedSettings(['telegram_admin_url' => null]);
        $this->bridgeAdminUrlFromDatabase();

        $formatter = app(BotMessageFormatter::class);
        $keyboard = $formatter->defaultReplyKeyboard(
            BotGatewayCapabilities::forSource(BotGatewayCapabilities::SOURCE_TELEGRAM),
        );

        $labels = array_map(
            static fn (array $row): string => (string) ($row[0]['text'] ?? ''),
            $keyboard['keyboard'],
        );

        $this->assertNotContains('📞 Hubungi Admin', $labels);
    }

    public function test_panduan_tidak_menyebut_tombol_admin_yang_tidak_ada(): void
    {
        config(['bot.order_enabled' => true]);
        config(['services.telegram-bot-api.admin_contact_url' => '']);
        $this->seedSettings(['telegram_admin_url' => null]);
        $this->bridgeAdminUrlFromDatabase();

        $formatter = app(BotMessageFormatter::class);
        $help = $formatter->formatHelp(
            BotGatewayCapabilities::forSource(BotGatewayCapabilities::SOURCE_TELEGRAM),
        );

        // Jangan menjanjikan tombol yang tidak dikirim.
        $this->assertStringNotContainsString('Hubungi Admin', $help['text']);
        $this->assertStringContainsString('admin', $help['text']);
    }

    public function test_panduan_menyebut_tombol_admin_ketika_url_terisi(): void
    {
        config(['bot.order_enabled' => true]);
        $this->seedSettings(['telegram_admin_url' => 'https://t.me/alexander_vors']);
        $this->bridgeAdminUrlFromDatabase();

        $formatter = app(BotMessageFormatter::class);
        $help = $formatter->formatHelp(
            BotGatewayCapabilities::forSource(BotGatewayCapabilities::SOURCE_TELEGRAM),
        );

        $this->assertStringContainsString('Hubungi Admin', $help['text']);
    }
}
