<?php

namespace Tests\Feature;

use App\Models\SettingWeb;
use App\Models\User;
use App\Services\AffiliateReviewNotificationService;
use App\Services\EmailNotificationService;
use App\Services\WhatsappNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class AffiliateReviewNotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_respects_affiliate_channel_toggles_when_disabled(): void
    {
        $this->seedBangjeffThemeSetting([
            'affiliate_notify_via_whatsapp' => false,
            'affiliate_notify_via_email' => false,
        ]);

        $user = User::factory()->create([
            'username' => 'member.disable',
            'no_wa' => '628123456789',
            'email' => 'member-disable@example.test',
        ]);

        $wa = $this->mock(WhatsappNotificationService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('sendMessage');
        });

        $email = $this->mock(EmailNotificationService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('sendGenericEmail');
        });

        $service = new AffiliateReviewNotificationService($wa, $email);
        $result = $service->notifyReviewDecision($user, 'active', 'approved');

        $this->assertFalse((bool) data_get($result, 'wa.enabled'));
        $this->assertFalse((bool) data_get($result, 'email.enabled'));
        $this->assertFalse((bool) data_get($result, 'wa.attempted'));
        $this->assertFalse((bool) data_get($result, 'email.attempted'));
    }

    public function test_notification_sends_to_enabled_channels(): void
    {
        $this->seedBangjeffThemeSetting([
            'affiliate_notify_via_whatsapp' => true,
            'affiliate_notify_via_email' => true,
        ]);

        $user = User::factory()->create([
            'username' => 'member.enable',
            'no_wa' => '628123456789',
            'email' => 'member-enable@example.test',
        ]);

        $wa = $this->mock(WhatsappNotificationService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('sendMessage')
                ->once()
                ->andReturn(['success' => true, 'message' => 'ok']);
        });

        $email = $this->mock(EmailNotificationService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('sendGenericEmail')
                ->once()
                ->andReturn(true);
        });

        $service = new AffiliateReviewNotificationService($wa, $email);
        $result = $service->notifyReviewDecision($user, 'rejected', 'Perlu perbaikan channel');

        $this->assertTrue((bool) data_get($result, 'wa.enabled'));
        $this->assertTrue((bool) data_get($result, 'email.enabled'));
        $this->assertTrue((bool) data_get($result, 'wa.attempted'));
        $this->assertTrue((bool) data_get($result, 'email.attempted'));
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function seedBangjeffThemeSetting(array $overrides = []): void
    {
        SettingWeb::create(array_merge([
            'id' => 1,
            'judul_web' => 'PlayNusa Topup',
            'deskripsi_web' => 'Demo storefront',
            'keywords' => 'top up game',
            'logo_header' => 'assets/logo/logo.webp',
            'logo_footer' => 'assets/logo/footer.webp',
            'logo_favicon' => 'assets/logo/favicon.webp',
            'url_wa' => 'https://wa.me/6281234567890',
            'url_ig' => 'https://instagram.com/playnusa',
            'url_tiktok' => 'https://tiktok.com/@playnusa',
            'url_youtube' => 'https://youtube.com/@playnusa',
            'url_fb' => 'https://facebook.com/playnusa',
            'topupindo_api' => 'demo-topupindo-key',
            'paydisini_apikey' => 'demo-paydisini-key',
            'order_prefik' => 'DMO',
            'warna1' => '#0f172a',
            'warna2' => '#ea580c',
            'warna3' => '#f59e0b',
            'warna4' => '#fb923c',
        ], $overrides));
    }
}
