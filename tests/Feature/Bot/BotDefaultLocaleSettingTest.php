<?php

namespace Tests\Feature\Bot;

use App\Filament\Admin\Pages\Settings\NotificationsSettings;
use App\Models\SettingWeb;
use App\Services\Bot\BotLocale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Default bahasa bot dari panel admin.
 *
 * Pitfall yang dikunci di sini SUDAH PERNAH KENA di project ini: field baru di
 * halaman Settings akan dibuang `filterStateByWhitelist()` dan tersimpan NULL
 * kalau lupa didaftarkan di whitelist. Jadi test ini menguji whitelist-nya,
 * bukan cuma keberadaan kolom.
 */
class BotDefaultLocaleSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_kolom_bot_default_locale_ada(): void
    {
        $this->assertTrue(
            \Illuminate\Support\Facades\Schema::hasColumn('setting_webs', 'bot_default_locale'),
            'Kolom setting_webs.bot_default_locale belum ada.',
        );
    }

    public function test_field_terdaftar_di_whitelist_halaman_notifikasi(): void
    {
        $page = new NotificationsSettings();
        $method = new \ReflectionMethod($page, 'getSettingFieldWhitelist');
        $method->setAccessible(true);

        $whitelist = $method->invoke($page);

        $this->assertIsArray($whitelist);
        $this->assertContains(
            'bot_default_locale',
            $whitelist,
            'Field bot_default_locale TIDAK ada di whitelist → nilainya akan tersimpan NULL.',
        );
    }

    public function test_nilai_default_dari_db_dipakai_resolver(): void
    {
        // Beberapa kolom `setting_webs` NOT NULL tanpa default di skema asli.
        // Test menghormati skema itu, bukan mengubahnya demi test.
        SettingWeb::query()->delete();
        SettingWeb::query()->create([
            'id' => 1,
            'judul_web' => 'Egymarket',
            'deskripsi_web' => 'Demo bot',
            'keywords' => 'topup',
            'url_wa' => 'https://wa.me/628123456789',
            'url_ig' => 'https://instagram.com/test',
            'url_tiktok' => 'https://tiktok.com/@test',
            'url_youtube' => 'https://youtube.com/@test',
            'url_fb' => 'https://facebook.com/test',
            'topupindo_api' => 'test',
            'warna1' => '#111111',
            'warna2' => '#222222',
            'warna3' => '#333333',
            'warna4' => '#444444',
            'paydisini_apikey' => 'test',
            'order_prefik' => 'INV',
            'bot_default_locale' => 'en',
        ]);

        // Simulasikan bridge AppServiceProvider (dipanggil saat boot di runtime).
        config(['services.telegram-bot-api.default_locale' => 'en']);

        $loc = app(BotLocale::class);

        $this->assertSame('en', $loc->defaultLocale());
        $this->assertSame(
            'en',
            $loc->resolve(['source' => 'telegram_gateway', 'external_user_id' => 'telegram:default:1']),
        );
    }

    public function test_nilai_db_tidak_valid_tidak_merusak_bahasa(): void
    {
        config(['services.telegram-bot-api.default_locale' => 'klingon']);

        $loc = app(BotLocale::class);

        // Nilai aneh dari DB tidak boleh meloloskan locale tak dikenal.
        $this->assertSame('id', $loc->defaultLocale());
    }
}
