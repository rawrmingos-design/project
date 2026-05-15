<?php

namespace Tests\Feature;

use App\Models\SettingWeb;
use App\Models\User;
use App\Support\PublicThemeRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicAffiliateRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_affiliate_request_requires_all_mandatory_fields_and_agreements(): void
    {
        $this->seedBangjeffThemeSetting();
        $user = User::factory()->create([
            'affiliate_status' => 'inactive',
        ]);

        $response = $this->actingAs($user)
            ->from('/id/affiliate')
            ->post(route('affiliate.request'), [
                'whatsapp' => '6281234567890',
                'agree_terms' => '1',
            ]);

        $response->assertRedirect('/id/affiliate');
        $response->assertSessionHasErrors([
            'promotion_channel_url',
            'agree_affiliate_policy',
        ]);

        $user->refresh();
        $this->assertSame('inactive', $user->affiliate_status);
        $this->assertNull($user->affiliate_requested_at);
    }

    public function test_affiliate_request_submits_successfully_with_complete_payload(): void
    {
        $this->seedBangjeffThemeSetting();
        $user = User::factory()->create([
            'affiliate_status' => 'inactive',
            'no_wa' => null,
        ]);

        $response = $this->actingAs($user)
            ->from('/id/affiliate')
            ->post(route('affiliate.request'), [
                'whatsapp' => '62812 3456 7890',
                'promotion_channel_url' => 'https://instagram.com/demoaffiliate',
                'notes' => 'Saya aktif promosi konten game setiap hari.',
                'agree_terms' => '1',
                'agree_affiliate_policy' => '1',
            ]);

        $response->assertRedirect(route('affiliate'));
        $response->assertSessionHas('success');

        $user->refresh();

        $this->assertSame('pending', $user->affiliate_status);
        $this->assertNotNull($user->affiliate_requested_at);
        $this->assertNotNull($user->affiliate_requirement_acknowledged_at);
        $this->assertSame('6281234567890', $user->no_wa);
        $this->assertNull($user->affiliate_ktp_document_path);
        $this->assertNull($user->affiliate_selfie_document_path);
        $this->assertNull($user->affiliate_family_card_document_path);
        $this->assertNull($user->affiliate_identity_document_path);
        $this->assertNull($user->affiliate_support_document_path);
        $this->assertSame('https://instagram.com/demoaffiliate', data_get($user->affiliate_application_meta, 'promotion_channel_url'));
        $this->assertSame('inertia_affiliate_form', data_get($user->affiliate_application_meta, 'submitted_via'));
    }

    public function test_rejected_affiliate_can_reapply_without_document_upload(): void
    {
        $this->seedBangjeffThemeSetting();
        $user = User::factory()->create([
            'affiliate_status' => 'rejected',
            'no_wa' => '628111111111',
            'affiliate_ktp_document_path' => 'affiliate-requests/user-old/old-ktp.jpg',
            'affiliate_selfie_document_path' => 'affiliate-requests/user-old/old-selfie.jpg',
            'affiliate_family_card_document_path' => 'affiliate-requests/user-old/old-kk.jpg',
            'affiliate_identity_document_path' => 'affiliate-requests/user-old/old-ktp.jpg',
            'affiliate_support_document_path' => 'affiliate-requests/user-old/old-selfie.jpg',
            'affiliate_application_meta' => [
                'review_history' => [[
                    'decision' => 'rejected',
                    'note' => 'Dokumen sebelumnya tidak valid.',
                    'reviewed_at' => now()->subDay()->toIso8601String(),
                    'reviewed_by_id' => 1,
                    'reviewed_by_username' => 'admin',
                ]],
                'review_last' => [
                    'decision' => 'rejected',
                    'note' => 'Dokumen sebelumnya tidak valid.',
                ],
            ],
        ]);

        $response = $this->actingAs($user)
            ->from('/id/affiliate')
            ->post(route('affiliate.request'), [
                'whatsapp' => '62812 8888 9999',
                'promotion_channel_url' => 'https://tiktok.com/@demoaffiliate',
                'notes' => 'Siap lanjut proses review affiliate.',
                'agree_terms' => '1',
                'agree_affiliate_policy' => '1',
            ]);

        $response->assertRedirect(route('affiliate'));
        $response->assertSessionHas('success');

        $user->refresh();

        $this->assertSame('pending', strtolower((string) $user->affiliate_status));
        $this->assertNotNull($user->affiliate_requested_at);
        $this->assertSame('6281288889999', $user->no_wa);
        $this->assertNull($user->affiliate_ktp_document_path);
        $this->assertNull($user->affiliate_selfie_document_path);
        $this->assertNull($user->affiliate_family_card_document_path);
        $this->assertNull($user->affiliate_identity_document_path);
        $this->assertNull($user->affiliate_support_document_path);
        $this->assertSame('https://tiktok.com/@demoaffiliate', data_get($user->affiliate_application_meta, 'promotion_channel_url'));
        $this->assertCount(1, (array) data_get($user->affiliate_application_meta, 'review_history', []));
        $this->assertNull(data_get($user->affiliate_application_meta, 'review_last'));
    }

    private function seedBangjeffThemeSetting(): void
    {
        SettingWeb::create([
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
            'public_theme' => PublicThemeRegistry::BANGJEFF,
        ]);
    }
}
