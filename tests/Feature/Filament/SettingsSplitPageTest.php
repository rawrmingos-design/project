<?php

namespace Tests\Feature\Filament;

use App\Filament\Admin\Pages\Settings\GeneralSettings;
use App\Filament\Admin\Pages\Settings\NotificationsSettings;
use App\Filament\Admin\Pages\Settings\ProvidersApiSettings;
use App\Filament\Admin\Pages\Settings\SeoTrackingSettings;
use App\Models\SettingWeb;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\AdminTestCase;

class SettingsSplitPageTest extends AdminTestCase
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
                'deskripsi_web' => '<p>Deskripsi website baru</p>',
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
        $this->assertSame('<p>Deskripsi website baru</p>', $settings->deskripsi_web);
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
                'deskripsi_web' => '<p>Deskripsi website baru</p>',
                'keywords' => 'topup,game,cheap',
                'home_popup_enabled' => false,
                'live_sales_enabled' => false,
                'captcha_enabled' => true,
                'captcha_bypass' => false,
            ])
            ->call('save');

        $this->assertFalse(Cache::has('public:active-theme'));
    }

    public function test_notifications_settings_normalizes_whatsapp_numbers_without_calling_wablas(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'Admin']);
        $this->actingAs($admin);

        $settings = $this->createTrackingSettings([
            'wa_provider' => 'fonnte',
            'nomor_admin' => '628111111111',
            'wa_key' => 'fonnte-token',
            'wa_number' => '628222222222',
        ]);

        Http::fake();

        foreach ([
            ['087780901780', '6287780901780'],
            ['87780901780', '6287780901780'],
            ['6287780901780', '6287780901780'],
            ['+6287780901780', '6287780901780'],
            [null, null],
        ] as [$input, $expected]) {
            $component = Livewire::test(NotificationsSettings::class)
                ->fillForm([
                    'wa_provider' => 'fonnte',
                    'nomor_admin' => $input,
                    'wa_key' => 'fonnte-token',
                    'wa_number' => $input,
                    'mail_mailer' => 'smtp',
                ])
                ->assertSet('data.nomor_admin', $input)
                ->assertSet('data.wa_number', $input);

            $state = $component->instance()->form->getState();
            $this->assertSame($expected, $state['nomor_admin']);
            $this->assertSame($expected, $state['wa_number']);

            $component->call('save');

            $settings->refresh();

            $this->assertSame($expected, $settings->nomor_admin);
            $this->assertSame($expected, $settings->wa_number);
        }

        Http::assertNothingSent();
    }

    public function test_notifications_settings_saves_openwa_provider_fields(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'Admin']);
        $this->actingAs($admin);

        $this->createTrackingSettings([
            'wa_provider' => 'fonnte',
        ]);

        // Bot order fields are gated by BOT_ORDER_ENABLED; enable it so openwa fields are visible & saved.
        putenv('BOT_ORDER_ENABLED=true');
        config(['bot.order_enabled' => true]);

        Http::fake();

        Livewire::test(NotificationsSettings::class)
            ->fillForm([
                'wa_provider' => 'openwa',
                'use_separate_bot_wa' => true,
                'openwa_session_id' => 'f802a400-0cf5-4c28-b7b0-aa30c169aee5',
                'openwa_webhook_secret' => 'test-openwa-secret',
                'mail_mailer' => 'smtp',
            ])
            ->call('save');

        $settings = SettingWeb::query()->findOrFail(1);
        $this->assertSame('openwa', $settings->wa_provider);
        $this->assertSame('f802a400-0cf5-4c28-b7b0-aa30c169aee5', $settings->openwa_session_id);
        $this->assertSame('test-openwa-secret', $settings->openwa_webhook_secret);

        Http::assertNothingSent();
    }

    public function test_notifications_settings_openwa_fields_visible_when_provider_is_openwa(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'Admin']);
        $this->actingAs($admin);

        $this->createTrackingSettings([
            'wa_provider' => 'fonnte',
            'openwa_session_id' => 'f802a400-0cf5-4c28-b7b0-aa30c169aee5',
        ]);

        // BOT_ORDER_ENABLED gates visibility of bot order toggles.
        putenv('BOT_ORDER_ENABLED=true');
        config(['bot.order_enabled' => true]);

        Http::fake();

        // Bot order fields are visible independently of wa_provider (notification provider).
        Livewire::test(NotificationsSettings::class)
            ->assertFormFieldExists('wa_provider')
            ->assertFormFieldExists('bot_order_wa_enabled')
            ->assertFormFieldExists('use_separate_bot_wa')
            ->set('data.use_separate_bot_wa', true)
            ->assertFormFieldExists('wa_bot_key')
            ->assertFormFieldExists('wa_bot_number')
            ->assertFormFieldExists('openwa_session_id')
            ->assertFormFieldExists('openwa_webhook_secret')
            ->assertFormSet([
                'wa_provider' => 'fonnte',
                'openwa_session_id' => 'f802a400-0cf5-4c28-b7b0-aa30c169aee5',
            ]);

        Http::assertNothingSent();
    }

    public function test_seo_tracking_settings_saves_encrypted_tiktok_credentials_and_preserves_blank_token(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'Admin']);
        $this->actingAs($admin);

        $this->createTrackingSettings();

        Livewire::test(SeoTrackingSettings::class)
            ->assertFormFieldExists('tiktok_tracking_enabled')
            ->assertFormFieldExists('tiktok_pixel_id')
            ->assertFormFieldExists('tiktok_access_token')
            ->assertFormFieldExists('tiktok_test_event_code')
            ->fillForm([
                'tiktok_tracking_enabled' => true,
                'tiktok_pixel_id' => 'CDB1234567890123',
                'tiktok_access_token' => 'admin-secret-token',
                'tiktok_test_event_code' => 'ADMIN-TEST-CODE',
            ])
            ->call('save');

        $settings = SettingWeb::query()->findOrFail(1);
        $ciphertext = $settings->getRawOriginal('tiktok_access_token_encrypted');

        $this->assertTrue((bool) $settings->tiktok_tracking_enabled);
        $this->assertSame('CDB1234567890123', $settings->tiktok_pixel_id);
        $this->assertSame('admin-secret-token', $settings->decryptedTiktokAccessToken());
        $this->assertNotSame('admin-secret-token', $ciphertext);
        $this->assertSame('ADMIN-TEST-CODE', $settings->tiktok_test_event_code);

        Livewire::test(SeoTrackingSettings::class)
            ->assertFormSet(['tiktok_access_token' => null])
            ->fillForm([
                'tiktok_tracking_enabled' => true,
                'tiktok_pixel_id' => 'CDB1234567890123',
                'tiktok_access_token' => '',
                'tiktok_test_event_code' => null,
            ])
            ->call('save');

        $settings->refresh();
        $this->assertSame($ciphertext, $settings->getRawOriginal('tiktok_access_token_encrypted'));
        $this->assertSame('admin-secret-token', $settings->decryptedTiktokAccessToken());
        $this->assertNull($settings->tiktok_test_event_code);
    }

    public function test_seo_tracking_settings_clear_action_removes_only_database_token(): void
    {
        config(['services.tiktok.access_token' => 'environment-fallback-token']);

        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'Admin']);
        $this->actingAs($admin);

        $this->createTrackingSettings([
            'tiktok_tracking_enabled' => true,
            'tiktok_pixel_id' => 'CDB1234567890123',
            'tiktok_access_token' => 'database-secret-token',
        ]);

        Livewire::test(SeoTrackingSettings::class)
            ->assertFormComponentActionExists('tiktok_credentials_status', 'clear_tiktok_access_token')
            ->callFormComponentAction('tiktok_credentials_status', 'clear_tiktok_access_token');

        $settings = SettingWeb::query()->findOrFail(1);
        $this->assertNull($settings->getRawOriginal('tiktok_access_token_encrypted'));
        $this->assertSame('environment-fallback-token', app(\App\Services\TikTokSettingsService::class)->accessToken());
    }

    public function test_seo_tracking_settings_rejects_enable_without_effective_token(): void
    {
        config(['services.tiktok.access_token' => null]);

        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'Admin']);
        $this->actingAs($admin);

        $this->createTrackingSettings();

        Livewire::test(SeoTrackingSettings::class)
            ->fillForm([
                'tiktok_tracking_enabled' => true,
                'tiktok_pixel_id' => 'CDB1234567890123',
                'tiktok_access_token' => '',
            ])
            ->call('save');

        $settings = SettingWeb::query()->findOrFail(1);
        $this->assertNull($settings->getRawOriginal('tiktok_tracking_enabled'));
        $this->assertNull($settings->tiktok_pixel_id);
    }

    private function createTrackingSettings(array $overrides = []): SettingWeb
    {
        return SettingWeb::query()->create(array_merge([
            'id' => 1,
            'judul_web' => 'Tracking Test',
            'deskripsi_web' => 'Tracking settings test',
            'keywords' => 'tracking',
            'url_wa' => 'https://wa.me/6281234567890',
            'url_ig' => 'https://instagram.com/tracking',
            'url_tiktok' => 'https://tiktok.com/@tracking',
            'url_youtube' => 'https://youtube.com/@tracking',
            'url_fb' => 'https://facebook.com/tracking',
            'topupindo_api' => '-',
            'warna1' => '#111111',
            'warna2' => '#222222',
            'warna3' => '#333333',
            'warna4' => '#444444',
            'paydisini_apikey' => '-',
            'order_prefik' => 'INV',
        ], $overrides));
    }

    public function test_providers_api_settings_excludes_unused_provider_fields(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'Admin']);
        $this->actingAs($admin);

        SettingWeb::query()->create([
            'id' => 1,
            'judul_web' => 'Old Title',
            'deskripsi_web' => 'Old Description',
            'keywords' => 'topup,old',
            'public_theme' => 'default',
            'url_wa' => 'https://wa.me/6281234567890',
            'url_ig' => 'https://instagram.com/original',
            'url_tiktok' => 'https://tiktok.com/@original',
            'url_youtube' => 'https://youtube.com/@original',
            'url_fb' => 'https://facebook.com/original',
            'topupindo_api' => 'topupindo-key',
            'apikey_bangjeff' => 'old-bangjeff-key',
            'apikey_aoshi' => 'old-aoshi-key',
            'api_mobilegamestore' => 'old-mobile-game-store-key',
            'vip_apiid' => 'old-vip-apiid',
            'vip_apikey' => 'old-vip-apikey',
            'vip_sign' => 'old-vip-sign',
            'username_digi' => 'old-digi-user',
            'api_key_digi' => 'old-digi-key',
            'apigames_merchant' => 'old-apigames-merchant',
            'apigames_secret' => 'old-apigames-secret',
            'warna1' => '#111111',
            'warna2' => '#222222',
            'warna3' => '#333333',
            'warna4' => '#444444',
            'paydisini_apikey' => 'paydisini-key',
            'order_prefik' => 'OLD',
        ]);

        Livewire::test(ProvidersApiSettings::class)
            ->assertFormFieldExists('apikey_bangjeff')
            ->assertFormFieldExists('vip_apiid')
            ->assertFormFieldExists('username_digi')
            ->assertFormFieldExists('apigames_merchant')
            ->assertFormFieldDoesNotExist('topupindo_api')
            ->assertFormFieldDoesNotExist('apikey_aoshi')
            ->assertFormFieldDoesNotExist('api_mobilegamestore')
            ->fillForm([
                'apikey_bangjeff' => 'new-bangjeff-key',
                'topupindo_api' => 'changed-topupindo-key',
                'apikey_aoshi' => 'changed-aoshi-key',
                'api_mobilegamestore' => 'changed-mobile-game-store-key',
            ])
            ->call('save');

        $settings = SettingWeb::query()->findOrFail(1);

        $this->assertSame('new-bangjeff-key', $settings->apikey_bangjeff);
        $this->assertSame('topupindo-key', $settings->topupindo_api);
        $this->assertSame('old-aoshi-key', $settings->apikey_aoshi);
        $this->assertSame('old-mobile-game-store-key', $settings->api_mobilegamestore);
    }
}

