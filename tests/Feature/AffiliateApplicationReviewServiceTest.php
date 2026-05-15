<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AffiliateApplicationReviewService;
use App\Services\AffiliateReviewNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Mockery\MockInterface;
use Tests\TestCase;

class AffiliateApplicationReviewServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_approve_pending_affiliate_and_referral_code_is_generated(): void
    {
        $this->mock(AffiliateReviewNotificationService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('notifyReviewDecision')->once()->andReturn([
                'decision' => 'active',
                'wa' => ['enabled' => true, 'attempted' => true, 'success' => true],
                'email' => ['enabled' => true, 'attempted' => true, 'success' => true],
            ]);
        });

        $admin = User::factory()->create([
            'role' => 'Admin',
            'username' => 'admin.reviewer',
        ]);

        $applicant = User::factory()->create([
            'affiliate_status' => 'pending',
            'referral_code' => null,
            'affiliate_application_meta' => [
                'promotion_channel_url' => 'https://instagram.com/memberpromo',
            ],
        ]);

        $result = app(AffiliateApplicationReviewService::class)->approve(
            $applicant,
            $admin,
            'Data valid, akun disetujui.'
        );

        $result->refresh();

        $this->assertSame('active', strtolower((string) $result->affiliate_status));
        $this->assertNotEmpty($result->referral_code);
        $this->assertStringStartsWith('REF-', (string) $result->referral_code);
        $this->assertSame('active', data_get($result->affiliate_application_meta, 'review_last.decision'));
        $this->assertSame('Data valid, akun disetujui.', data_get($result->affiliate_application_meta, 'review_last.note'));
        $this->assertSame($admin->id, data_get($result->affiliate_application_meta, 'review_last.reviewed_by_id'));
        $this->assertCount(1, (array) data_get($result->affiliate_application_meta, 'review_history', []));
    }

    public function test_admin_can_reject_pending_affiliate_and_reason_is_saved(): void
    {
        $this->mock(AffiliateReviewNotificationService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('notifyReviewDecision')->once()->andReturn([
                'decision' => 'rejected',
                'wa' => ['enabled' => true, 'attempted' => true, 'success' => true],
                'email' => ['enabled' => true, 'attempted' => true, 'success' => true],
            ]);
        });

        $admin = User::factory()->create([
            'role' => 'Admin',
            'username' => 'admin.rejector',
        ]);

        $applicant = User::factory()->create([
            'affiliate_status' => 'pending',
            'affiliate_application_meta' => [
                'promotion_channel_url' => 'https://tiktok.com/@memberpromo',
            ],
        ]);

        $result = app(AffiliateApplicationReviewService::class)->reject(
            $applicant,
            $admin,
            'Channel promosi belum memenuhi guideline anti-spam.'
        );

        $result->refresh();

        $this->assertSame('rejected', strtolower((string) $result->affiliate_status));
        $this->assertSame('rejected', data_get($result->affiliate_application_meta, 'review_last.decision'));
        $this->assertSame(
            'Channel promosi belum memenuhi guideline anti-spam.',
            data_get($result->affiliate_application_meta, 'review_last.note')
        );
        $this->assertCount(1, (array) data_get($result->affiliate_application_meta, 'review_history', []));
    }

    public function test_reject_requires_review_note(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $applicant = User::factory()->create(['affiliate_status' => 'pending']);

        $this->expectException(ValidationException::class);

        app(AffiliateApplicationReviewService::class)->reject($applicant, $admin, null);
    }

    public function test_review_is_rejected_when_status_is_not_pending(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $applicant = User::factory()->create(['affiliate_status' => 'active']);

        $this->expectException(ValidationException::class);

        app(AffiliateApplicationReviewService::class)->approve($applicant, $admin, 'Coba approve ulang');
    }

    public function test_review_still_succeeds_when_notification_dispatch_fails(): void
    {
        $this->mock(AffiliateReviewNotificationService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('notifyReviewDecision')
                ->once()
                ->andThrow(new \RuntimeException('Simulasi WA gateway timeout'));
        });

        $admin = User::factory()->create(['role' => 'Admin', 'username' => 'admin.safe']);
        $applicant = User::factory()->create(['affiliate_status' => 'pending']);

        $result = app(AffiliateApplicationReviewService::class)->approve($applicant, $admin, 'Approve walau notif gagal.');

        $result->refresh();
        $this->assertSame('active', strtolower((string) $result->affiliate_status));
    }
}
