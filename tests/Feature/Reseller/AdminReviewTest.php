<?php

namespace Tests\Feature\Reseller;

use App\Models\ResellerApplication;
use App\Models\User;
use App\Services\ResellerApplicationReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReviewTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Admin can access Filament admin panel.
     */
    public function test_admin_can_access_filament_panel(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertSuccessful();
    }

    /**
     * Non-admin users cannot access Filament admin panel.
     */
    public function test_non_admin_cannot_access_admin_panel(): void
    {
        $member = User::factory()->create(['role' => 'Member']);

        $this->actingAs($member)
            ->get('/admin')
            ->assertForbidden();
    }

    /**
     * Guest cannot access Filament admin panel.
     */
    public function test_guest_cannot_access_admin_panel(): void
    {
        $this->get('/admin')
            ->assertRedirect(route('filament.admin.auth.login'));
    }

    /**
     * Admin can approve pending application (service integration test).
     */
    public function test_admin_can_approve_pending_application(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $application = ResellerApplication::factory()->create(['status' => 'pending']);
        
        $service = app(ResellerApplicationReviewService::class);
        $result = $service->approve($application, $admin, ['note' => 'Approved by admin']);

        $this->assertEquals('approved', $result->status);
        $this->assertNotNull($result->approved_at);
        $this->assertEquals($admin->id, $result->reviewed_by);

        // User should be promoted to Gold
        $this->assertEquals('Gold', $result->user->fresh()->role);
    }

    /**
     * Admin can reject pending application with reason (service integration test).
     */
    public function test_admin_can_reject_pending_application_with_reason(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $application = ResellerApplication::factory()->create(['status' => 'pending']);
        
        $service = app(ResellerApplicationReviewService::class);
        $result = $service->reject($application, $admin, 'Incomplete documentation');

        $this->assertEquals('rejected', $result->status);
        $this->assertNotNull($result->rejected_at);
        $this->assertEquals($admin->id, $result->reviewed_by);
        $this->assertEquals('Incomplete documentation', $result->rejection_reason);

        // User should remain Member
        $this->assertEquals('Member', $result->user->fresh()->role);
    }

    /**
     * Cannot approve already approved application.
     */
    public function test_cannot_approve_already_approved_application(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $application = ResellerApplication::factory()->approved()->create();
        
        $service = app(ResellerApplicationReviewService::class);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        
        $service->approve($application, $admin);
    }

    /**
     * Cannot reject already rejected application.
     */
    public function test_cannot_reject_already_rejected_application(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $application = ResellerApplication::factory()->rejected()->create();
        
        $service = app(ResellerApplicationReviewService::class);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        
        $service->reject($application, $admin, 'Already rejected');
    }

    /**
     * Approval creates review record with admin details.
     */
    public function test_approval_creates_review_record(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $application = ResellerApplication::factory()->create(['status' => 'pending']);
        
        $service = app(ResellerApplicationReviewService::class);
        $service->approve($application, $admin, ['note' => 'Looks good']);

        $this->assertDatabaseHas('reseller_application_reviews', [
            'user_id' => $application->user_id,
            'action' => 'approved',
            'reviewed_by' => $admin->id,
            'notes' => 'Looks good',
        ]);
    }

    /**
     * Rejection creates review record with reason.
     */
    public function test_rejection_creates_review_record_with_reason(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $application = ResellerApplication::factory()->create(['status' => 'pending']);
        
        $service = app(ResellerApplicationReviewService::class);
        $service->reject($application, $admin, 'Missing business proof');

        $this->assertDatabaseHas('reseller_application_reviews', [
            'user_id' => $application->user_id,
            'action' => 'rejected',
            'reviewed_by' => $admin->id,
            'notes' => 'Missing business proof',
        ]);
    }
}
