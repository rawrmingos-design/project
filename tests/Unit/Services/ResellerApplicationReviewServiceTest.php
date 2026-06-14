<?php

namespace Tests\Unit\Services;

use App\Models\ResellerApplication;
use App\Models\ResellerApplicationReview;
use App\Models\User;
use App\Services\ResellerApplicationReviewService;
use App\Services\ResellerProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ResellerApplicationReviewServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test approve sets application status and review metadata.
     */
    public function test_approve_sets_status_to_approved(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $application = ResellerApplication::factory()->create(['status' => 'pending']);
        $service = $this->makeServiceExpectingProvisionOnce();

        $result = $service->approve($application, $admin, ['note' => 'Approved after verification']);

        $this->assertInstanceOf(ResellerApplication::class, $result);
        $this->assertEquals('approved', $result->status);
        $this->assertNotNull($result->approved_at);
        $this->assertNull($result->rejected_at);
        $this->assertNull($result->rejection_reason);
        $this->assertEquals($admin->id, $result->reviewed_by);
    }

    /**
     * Test approve creates review record with optional admin note.
     */
    public function test_approve_creates_review_record_with_note(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $application = ResellerApplication::factory()->create(['status' => 'pending']);
        $service = $this->makeServiceExpectingProvisionOnce();

        $service->approve($application, $admin, ['note' => 'Documents look valid.']);

        $this->assertDatabaseHas('reseller_application_reviews', [
            'user_id' => $application->user_id,
            'action' => 'approved',
            'reviewed_by' => $admin->id,
            'notes' => 'Documents look valid.',
        ]);
    }

    /**
     * Test approve calls provisioning service for application owner.
     */
    public function test_approve_calls_provisioning_service(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $application = ResellerApplication::factory()->create(['status' => 'pending']);
        $owner = $application->user;

        $provisioningService = $this->createMock(ResellerProvisioningService::class);
        $provisioningService
            ->expects($this->once())
            ->method('provision')
            ->with($this->callback(fn (User $user) => $user->id === $owner->id))
            ->willReturn([
                'live_key' => 'test-key',
                'sandbox_key' => 'test-key',
            ]);

        $service = new ResellerApplicationReviewService($provisioningService);

        $service->approve($application, $admin);
    }

    /**
     * Test reject sets application status and rejection metadata.
     */
    public function test_reject_sets_status_to_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $application = ResellerApplication::factory()->create(['status' => 'pending']);
        $service = $this->makeServiceExpectingNoProvision();

        $result = $service->reject($application, $admin, 'Invalid business proof');

        $this->assertInstanceOf(ResellerApplication::class, $result);
        $this->assertEquals('rejected', $result->status);
        $this->assertNull($result->approved_at);
        $this->assertNotNull($result->rejected_at);
        $this->assertEquals($admin->id, $result->reviewed_by);
        $this->assertEquals('Invalid business proof', $result->rejection_reason);
    }

    /**
     * Test reject trims reason and creates review record.
     */
    public function test_reject_trims_reason_and_creates_review_record(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $application = ResellerApplication::factory()->create(['status' => 'pending']);
        $service = $this->makeServiceExpectingNoProvision();

        $service->reject($application, $admin, '  Missing identity document  ');

        $this->assertDatabaseHas('reseller_application_reviews', [
            'user_id' => $application->user_id,
            'action' => 'rejected',
            'reviewed_by' => $admin->id,
            'notes' => 'Missing identity document',
        ]);

        $this->assertDatabaseHas('reseller_applications', [
            'id' => $application->id,
            'rejection_reason' => 'Missing identity document',
        ]);
    }

    /**
     * Test blank rejection reason throws validation exception.
     */
    public function test_reject_with_blank_reason_throws_validation_exception(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $application = ResellerApplication::factory()->create(['status' => 'pending']);
        $service = $this->makeServiceExpectingNoProvision();

        $this->expectException(ValidationException::class);

        $service->reject($application, $admin, '   ');
    }

    /**
     * Test approve cannot process non-pending applications.
     */
    public function test_approve_non_pending_application_throws_validation_exception(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $application = ResellerApplication::factory()->approved()->create();
        $service = $this->makeServiceExpectingNoProvision();

        $this->expectException(ValidationException::class);

        $service->approve($application, $admin);
    }

    /**
     * Test reject cannot process non-pending applications.
     */
    public function test_reject_non_pending_application_throws_validation_exception(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $application = ResellerApplication::factory()->rejected()->create();
        $service = $this->makeServiceExpectingNoProvision();

        $this->expectException(ValidationException::class);

        $service->reject($application, $admin, 'Already rejected');
    }

    /**
     * Test approve returns fresh application with user and reviewer relations.
     */
    public function test_approve_returns_fresh_application_with_relations(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $application = ResellerApplication::factory()->create(['status' => 'pending']);
        $service = $this->makeServiceExpectingProvisionOnce();

        $result = $service->approve($application, $admin);

        $this->assertTrue($result->relationLoaded('user'));
        $this->assertTrue($result->relationLoaded('reviewer'));
        $this->assertEquals($application->user_id, $result->user->id);
        $this->assertEquals($admin->id, $result->reviewer->id);
    }

    /**
     * Test reject returns fresh application with user and reviewer relations.
     */
    public function test_reject_returns_fresh_application_with_relations(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $application = ResellerApplication::factory()->create(['status' => 'pending']);
        $service = $this->makeServiceExpectingNoProvision();

        $result = $service->reject($application, $admin, 'Invalid document');

        $this->assertTrue($result->relationLoaded('user'));
        $this->assertTrue($result->relationLoaded('reviewer'));
        $this->assertEquals($application->user_id, $result->user->id);
        $this->assertEquals($admin->id, $result->reviewer->id);
    }

    private function makeServiceExpectingProvisionOnce(): ResellerApplicationReviewService
    {
        $provisioningService = $this->createMock(ResellerProvisioningService::class);
        $provisioningService
            ->expects($this->once())
            ->method('provision')
            ->willReturn([
                'live_key' => 'test-live-key-' . uniqid(),
                'sandbox_key' => 'test-sandbox-key-' . uniqid(),
            ]);

        return new ResellerApplicationReviewService($provisioningService);
    }

    private function makeServiceExpectingNoProvision(): ResellerApplicationReviewService
    {
        $provisioningService = $this->createMock(ResellerProvisioningService::class);
        $provisioningService
            ->expects($this->never())
            ->method('provision');

        return new ResellerApplicationReviewService($provisioningService);
    }
}
