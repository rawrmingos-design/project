<?php

namespace Tests\Unit\Models;

use App\Models\ResellerApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResellerApplicationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that ResellerApplication has a user relationship.
     */
    public function test_has_user_relationship(): void
    {
        $user = User::factory()->create();
        $application = ResellerApplication::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->assertInstanceOf(User::class, $application->user);
        $this->assertEquals($user->id, $application->user->id);
    }

    /**
     * Test that ResellerApplication has a reviewer relationship.
     */
    public function test_has_reviewer_relationship(): void
    {
        $reviewer = User::factory()->create();
        $application = ResellerApplication::factory()->create([
            'reviewed_by' => $reviewer->id,
        ]);

        $this->assertInstanceOf(User::class, $application->reviewer);
        $this->assertEquals($reviewer->id, $application->reviewer->id);
    }

    /**
     * Test that isPending returns true for pending status.
     */
    public function test_is_pending_returns_true_for_pending_status(): void
    {
        $application = ResellerApplication::factory()->create([
            'status' => 'pending',
        ]);

        $this->assertTrue($application->isPending());
    }

    /**
     * Test that isPending returns false for non-pending status.
     */
    public function test_is_pending_returns_false_for_non_pending_status(): void
    {
        $application = ResellerApplication::factory()->create([
            'status' => 'approved',
        ]);

        $this->assertFalse($application->isPending());
    }

    /**
     * Test that isApproved returns true for approved status.
     */
    public function test_is_approved_returns_true_for_approved_status(): void
    {
        $application = ResellerApplication::factory()->approved()->create();

        $this->assertTrue($application->isApproved());
    }

    /**
     * Test that isRejected returns true for rejected status.
     */
    public function test_is_rejected_returns_true_for_rejected_status(): void
    {
        $application = ResellerApplication::factory()->rejected()->create();

        $this->assertTrue($application->isRejected());
    }

    /**
     * Test that isInactive returns true for inactive status.
     */
    public function test_is_inactive_returns_true_for_inactive_status(): void
    {
        $application = ResellerApplication::factory()->create([
            'status' => 'inactive',
        ]);

        $this->assertTrue($application->isInactive());
    }

    /**
     * Test that default status is pending.
     */
    public function test_default_status_is_pending(): void
    {
        $application = ResellerApplication::factory()->create();

        $this->assertEquals('pending', $application->status);
        $this->assertTrue($application->isPending());
    }

    /**
     * Test that applied_at is automatically set.
     */
    public function test_applied_at_is_automatically_set(): void
    {
        $application = ResellerApplication::factory()->create();

        $this->assertNotNull($application->applied_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $application->applied_at);
    }

    /**
     * Test business_name accessor.
     */
    public function test_can_access_business_name_from_meta(): void
    {
        $application = ResellerApplication::factory()->create([
            'business_meta' => [
                'business_name' => 'Test Shop',
                'business_url' => 'https://testshop.com',
            ],
        ]);

        $this->assertEquals('Test Shop', $application->business_name);
    }

    /**
     * Test business_url accessor.
     */
    public function test_can_access_business_url_from_meta(): void
    {
        $application = ResellerApplication::factory()->create([
            'business_meta' => [
                'business_name' => 'Test Shop',
                'business_url' => 'https://testshop.com',
            ],
        ]);

        $this->assertEquals('https://testshop.com', $application->business_url);
    }

    /**
     * Test pending scope.
     */
    public function test_pending_scope_filters_pending_applications(): void
    {
        ResellerApplication::factory()->create(['status' => 'pending']);
        ResellerApplication::factory()->create(['status' => 'approved']);
        ResellerApplication::factory()->create(['status' => 'rejected']);

        $pendingApplications = ResellerApplication::pending()->get();

        $this->assertCount(1, $pendingApplications);
        $this->assertEquals('pending', $pendingApplications->first()->status);
    }

    /**
     * Test approved scope.
     */
    public function test_approved_scope_filters_approved_applications(): void
    {
        ResellerApplication::factory()->create(['status' => 'pending']);
        ResellerApplication::factory()->approved()->create();
        ResellerApplication::factory()->create(['status' => 'rejected']);

        $approvedApplications = ResellerApplication::approved()->get();

        $this->assertCount(1, $approvedApplications);
        $this->assertEquals('approved', $approvedApplications->first()->status);
    }

    /**
     * Test rejected scope.
     */
    public function test_rejected_scope_filters_rejected_applications(): void
    {
        ResellerApplication::factory()->create(['status' => 'pending']);
        ResellerApplication::factory()->create(['status' => 'approved']);
        ResellerApplication::factory()->rejected()->create();

        $rejectedApplications = ResellerApplication::rejected()->get();

        $this->assertCount(1, $rejectedApplications);
        $this->assertEquals('rejected', $rejectedApplications->first()->status);
    }
}
