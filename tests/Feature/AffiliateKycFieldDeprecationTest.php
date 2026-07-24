<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\SettingWeb;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class AffiliateKycFieldDeprecationTest extends TestCase
{
    use RefreshDatabase;

    public function test_inertia_affiliate_payload_omits_legacy_document_urls(): void
    {
        $this->createSettings('bangjeff');

        $user = $this->createUserWithLegacyDocuments([
            'affiliate_status' => 'pending',
            'affiliate_requested_at' => now(),
        ]);
        $headers = ['X-Inertia' => 'true'];
        $version = app(HandleInertiaRequests::class)->version(request());

        if ($version !== null) {
            $headers['X-Inertia-Version'] = $version;
        }

        $response = $this
            ->actingAs($user)
            ->withHeaders($headers)
            ->get('/id/affiliate');

        $response->assertOk()
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('component', 'Public/Affiliate')
                ->missing('props.affiliate.application.lastSubmission.ktpDocumentUrl')
                ->missing('props.affiliate.application.lastSubmission.selfieDocumentUrl')
                ->missing('props.affiliate.application.lastSubmission.familyCardDocumentUrl')
                ->etc());
    }

    public function test_inertia_affiliate_submit_does_not_touch_legacy_document_fields(): void
    {
        $this->createSettings('bangjeff');

        $user = $this->createUserWithLegacyDocuments([
            'affiliate_status' => 'inactive',
        ]);

        Storage::fake('public');
        Storage::disk('public')->put('affiliate/legacy/ktp.jpg', 'legacy');

        $response = $this
            ->actingAs($user)
            ->post('/id/affiliate/request', $this->validAffiliatePayload([
                'notes' => 'Saya promosi via komunitas game.',
            ]));

        $response->assertRedirect(route('affiliate'));

        $user->refresh();

        $this->assertSame('pending', $user->affiliate_status);
        $this->assertNotNull($user->affiliate_requested_at);
        $this->assertNotNull($user->affiliate_requirement_acknowledged_at);
        $this->assertSame('Saya promosi via komunitas game.', $user->affiliate_application_note);
        $this->assertSame('inertia_affiliate_form', data_get($user->affiliate_application_meta, 'submitted_via'));
        $this->assertSame('https://example.com/channel', data_get($user->affiliate_application_meta, 'promotion_channel_url'));

        $this->assertLegacyDocumentFieldsUnchanged($user);
        Storage::disk('public')->assertExists('affiliate/legacy/ktp.jpg');
    }

    public function test_blade_affiliate_submit_does_not_touch_legacy_document_fields(): void
    {
        $this->createSettings('default');

        $user = $this->createUserWithLegacyDocuments([
            'affiliate_status' => 'inactive',
        ]);

        $response = $this
            ->actingAs($user)
            ->post('/id/affiliate/request', $this->validAffiliatePayload());

        $response->assertRedirect(route('affiliate'));

        $user->refresh();

        $this->assertSame('pending', $user->affiliate_status);
        $this->assertSame('blade_affiliate_form', data_get($user->affiliate_application_meta, 'submitted_via'));
        $this->assertLegacyDocumentFieldsUnchanged($user);
    }

    public function test_rejected_resubmission_preserves_review_history(): void
    {
        $this->createSettings('bangjeff');

        $reviewHistory = [
            [
                'decision' => 'rejected',
                'note' => 'URL promosi belum jelas.',
                'reviewed_at' => now()->subDay()->toIso8601String(),
                'reviewed_by_username' => 'admin',
            ],
        ];

        $user = $this->createUserWithLegacyDocuments([
            'affiliate_status' => 'rejected',
            'affiliate_application_meta' => [
                'review_history' => $reviewHistory,
                'review_last' => $reviewHistory[0],
            ],
        ]);

        $response = $this
            ->actingAs($user)
            ->post('/id/affiliate/request', $this->validAffiliatePayload([
                'notes' => 'URL sudah diperbaiki.',
            ]));

        $response->assertRedirect(route('affiliate'));

        $user->refresh();

        $this->assertSame('pending', $user->affiliate_status);
        $this->assertEquals($reviewHistory, data_get($user->affiliate_application_meta, 'review_history'));
        $this->assertNull(data_get($user->affiliate_application_meta, 'review_last'));
        $this->assertLegacyDocumentFieldsUnchanged($user);
    }

    private function createUserWithLegacyDocuments(array $overrides = []): User
    {
        $user = User::factory()->create(array_merge([
            'role' => 'Member',
            'affiliate_status' => 'inactive',
        ], $overrides));

        $user->forceFill([
            'affiliate_identity_document_path' => 'affiliate/legacy/identity.jpg',
            'affiliate_support_document_path' => 'affiliate/legacy/support.pdf',
            'affiliate_ktp_document_path' => 'affiliate/legacy/ktp.jpg',
            'affiliate_selfie_document_path' => 'affiliate/legacy/selfie.jpg',
            'affiliate_family_card_document_path' => 'affiliate/legacy/family-card.jpg',
        ])->save();

        return $user;
    }

    private function assertLegacyDocumentFieldsUnchanged(User $user): void
    {
        $this->assertSame('affiliate/legacy/identity.jpg', $user->affiliate_identity_document_path);
        $this->assertSame('affiliate/legacy/support.pdf', $user->affiliate_support_document_path);
        $this->assertSame('affiliate/legacy/ktp.jpg', $user->affiliate_ktp_document_path);
        $this->assertSame('affiliate/legacy/selfie.jpg', $user->affiliate_selfie_document_path);
        $this->assertSame('affiliate/legacy/family-card.jpg', $user->affiliate_family_card_document_path);
    }

    private function validAffiliatePayload(array $overrides = []): array
    {
        return array_merge([
            'whatsapp' => '0812 3456 7890',
            'promotion_channel_url' => 'https://example.com/channel',
            'notes' => null,
            'agree_terms' => '1',
            'agree_affiliate_policy' => '1',
        ], $overrides);
    }

    private function createSettings(string $publicTheme): SettingWeb
    {
        return SettingWeb::query()->create([
            'id' => 1,
            'judul_web' => 'Test Web',
            'deskripsi_web' => 'Test Desc',
            'keywords' => 'test',
            'logo_header' => 'assets/logo-header.png',
            'logo_footer' => 'assets/logo-footer.png',
            'logo_favicon' => 'assets/favicon.ico',
            'url_wa' => 'https://wa.me/test',
            'url_ig' => 'https://instagram.com/test',
            'url_tiktok' => 'https://tiktok.com/test',
            'url_youtube' => 'https://youtube.com/test',
            'url_fb' => 'https://facebook.com/test',
            'topupindo_api' => 'test_api',
            'warna1' => '#222222',
            'warna2' => '#d06800',
            'warna3' => '#ffa54a',
            'warna4' => '#ff8040',
            'paydisini_apikey' => 'test_paydisini',
            'tripay_api' => 'test_api_key',
            'tripay_merchant_code' => 'test_merchant',
            'tripay_private_key' => 'test_private',
            'username_digi' => 'test_digi',
            'api_key_digi' => 'test_digi_key',
            'apigames_secret' => 'secret-123',
            'apigames_merchant' => 'merchant-123',
            'vip_apiid' => 'test_vip_id',
            'vip_apikey' => 'test_vip_key',
            'apikey_bangjeff' => 'test_bangjeff_key',
            'order_prefik' => 'INV',
            'public_theme' => $publicTheme,
        ]);
    }
}
