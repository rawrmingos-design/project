<?php

namespace Tests\Feature\Filament;

use App\Filament\Admin\Pages\Settings\GeneralSettings;
use App\Models\SettingWeb;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class SettingsSplitPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_settings_hub_and_all_sub_pages(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        $this->actingAs($admin);

        $routes = [
            'filament.admin.pages.settings',
            'filament.admin.pages.settings.general',
            'filament.admin.pages.settings.branding',
            'filament.admin.pages.settings.seo-tracking',
            'filament.admin.pages.settings.payment-gateways',
            'filament.admin.pages.settings.providers-api',
            'filament.admin.pages.settings.notifications',
            'filament.admin.pages.settings.membership-rewards',
        ];

        foreach ($routes as $routeName) {
            $this->get(route($routeName))->assertOk();
        }
    }

    public function test_non_admin_cannot_access_settings_hub_or_sub_pages(): void
    {
        $member = User::factory()->create(['role' => 'Member']);

        $this->actingAs($member);

        $routes = [
            'filament.admin.pages.settings',
            'filament.admin.pages.settings.general',
            'filament.admin.pages.settings.branding',
            'filament.admin.pages.settings.seo-tracking',
            'filament.admin.pages.settings.payment-gateways',
            'filament.admin.pages.settings.providers-api',
            'filament.admin.pages.settings.notifications',
            'filament.admin.pages.settings.membership-rewards',
        ];

        foreach ($routes as $routeName) {
            $this->get(route($routeName))->assertForbidden();
        }
    }

    public function test_general_settings_page_only_updates_whitelisted_fields(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $this->actingAs($admin);

        $settings = SettingWeb::query()->create([
            'id' => 1,
            'judul_web' => 'Old Title',
            'deskripsi_web' => 'Old Description',
            'keywords' => 'topup,old',
            'public_theme' => 'default',
            'home_popup_enabled' => true,
            'live_sales_enabled' => true,
            'captcha_enabled' => true,
            'captcha_bypass' => false,
            'google_client_id' => 'old-google-client-id',
            'order_prefik' => 'OLD',
            'logo_header' => 'assets/logo/original-header.webp',
            'warna1' => '#111111',
            'warna2' => '#222222',
            'warna3' => '#333333',
            'warna4' => '#444444',
            'url_wa' => 'https://wa.me/6281234567890',
            'url_ig' => 'https://instagram.com/original',
            'url_tiktok' => 'https://tiktok.com/@original',
            'url_youtube' => 'https://youtube.com/@original',
            'url_fb' => 'https://facebook.com/original',
            'topupindo_api' => 'topupindo-key',
            'paydisini_apikey' => 'paydisini-key',
            'profit_member' => 7,
        ]);

        Livewire::test(GeneralSettings::class)
            ->fillForm([
                'judul_web' => 'New Website',
                'order_prefik' => 'INV',
                'public_theme' => 'bangjeff',
                'deskripsi_web' => 'Deskripsi website baru',
                'keywords' => 'topup,game,cheap',
                'home_popup_enabled' => false,
                'live_sales_enabled' => false,
                'captcha_enabled' => true,
                'captcha_bypass' => true,
                'captcha_site_key' => 'new-site-key',
                'captcha_secret' => 'new-secret-key',
                'google_client_id' => 'new-google-client-id',
            ])
            ->call('save');

        $settings->refresh();

        $this->assertSame('New Website', $settings->judul_web);
        $this->assertSame('Deskripsi website baru', $settings->deskripsi_web);
        $this->assertSame('bangjeff', $settings->public_theme);
        $this->assertFalse((bool) $settings->home_popup_enabled);
        $this->assertFalse((bool) $settings->live_sales_enabled);
        $this->assertTrue((bool) $settings->captcha_bypass);
        $this->assertSame('new-google-client-id', $settings->google_client_id);

        $this->assertSame('assets/logo/original-header.webp', $settings->logo_header);
        $this->assertSame('#111111', $settings->warna1);
        $this->assertSame('https://instagram.com/original', $settings->url_ig);
        $this->assertSame(7, (int) $settings->profit_member);
    }

    public function test_saving_general_settings_invalidates_public_theme_cache(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $this->actingAs($admin);

        SettingWeb::query()->create([
            'id' => 1,
            'judul_web' => 'Old Title',
            'deskripsi_web' => 'Old Description',
            'keywords' => 'topup,old',
            'public_theme' => 'default',
            'home_popup_enabled' => true,
            'live_sales_enabled' => true,
            'captcha_enabled' => true,
            'captcha_bypass' => false,
            'url_wa' => 'https://wa.me/6281234567890',
            'url_ig' => 'https://instagram.com/original',
            'url_tiktok' => 'https://tiktok.com/@original',
            'url_youtube' => 'https://youtube.com/@original',
            'url_fb' => 'https://facebook.com/original',
            'topupindo_api' => 'topupindo-key',
            'warna1' => '#111111',
            'warna2' => '#222222',
            'warna3' => '#333333',
            'warna4' => '#444444',
            'paydisini_apikey' => 'paydisini-key',
            'order_prefik' => 'OLD',
        ]);

        Cache::put('public:active-theme', 'default', now()->addMinutes(5));

        Livewire::test(GeneralSettings::class)
            ->fillForm([
                'judul_web' => 'New Website',
                'order_prefik' => 'INV',
                'public_theme' => 'bangjeff',
                'deskripsi_web' => 'Deskripsi website baru',
                'keywords' => 'topup,game,cheap',
                'home_popup_enabled' => false,
                'live_sales_enabled' => false,
                'captcha_enabled' => true,
                'captcha_bypass' => false,
            ])
            ->call('save');

        $this->assertFalse(Cache::has('public:active-theme'));
    }
}
