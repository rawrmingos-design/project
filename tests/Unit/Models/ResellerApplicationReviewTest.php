<?php

namespace Tests\Unit\Models;

use App\Models\ResellerApplicationReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResellerApplicationReviewTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that ResellerApplicationReview has a user relationship.
     */
    public function test_has_user_relationship(): void
    {
        $user = User::factory()->create();
        $review = ResellerApplicationReview::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->assertInstanceOf(User::class, $review->user);
        $this->assertEquals($user->id, $review->user->id);
    }

    /**
     * Test that ResellerApplicationReview has a reviewer relationship.
     */
    public function test_has_reviewer_relationship(): void
    {
        $reviewer = User::factory()->create();
        $review = ResellerApplicationReview::factory()->create([
            'reviewed_by' => $reviewer->id,
        ]);

        $this->assertInstanceOf(User::class, $review->reviewer);
        $this->assertEquals($reviewer->id, $review->reviewer->id);
    }

    /**
     * Test that action can be stored.
     */
    public function test_can_store_action(): void
    {
        $actions = ['submitted', 'approved', 'rejected', 'resubmitted'];

        foreach ($actions as $action) {
            $review = ResellerApplicationReview::factory()->create([
                'action' => $action,
            ]);

            $this->assertEquals($action, $review->action);
        }
    }

    /**
     * Test that notes can be stored.
     */
    public function test_can_store_notes(): void
    {
        $notes = 'Application approved after document verification.';
        
        $review = ResellerApplicationReview::factory()->create([
            'notes' => $notes,
        ]);

        $this->assertEquals($notes, $review->notes);
    }

    /**
     * Test action label accessor for different actions.
     */
    public function test_action_label_accessor_returns_correct_labels(): void
    {
        $submitted = ResellerApplicationReview::factory()->submitted()->create();
        $this->assertEquals('Application Submitted', $submitted->action_label);

        $approved = ResellerApplicationReview::factory()->approved()->create();
        $this->assertEquals('Application Approved', $approved->action_label);

        $rejected = ResellerApplicationReview::factory()->rejected()->create();
        $this->assertEquals('Application Rejected', $rejected->action_label);

        $resubmitted = ResellerApplicationReview::factory()->resubmitted()->create();
        $this->assertEquals('Application Resubmitted', $resubmitted->action_label);
    }

    /**
     * Test isAdminAction returns true for admin actions.
     */
    public function test_is_admin_action_returns_true_for_admin_actions(): void
    {
        $approved = ResellerApplicationReview::factory()->approved()->create();
        $this->assertTrue($approved->isAdminAction());

        $rejected = ResellerApplicationReview::factory()->rejected()->create();
        $this->assertTrue($rejected->isAdminAction());
    }

    /**
     * Test isAdminAction returns false for user actions.
     */
    public function test_is_admin_action_returns_false_for_user_actions(): void
    {
        $submitted = ResellerApplicationReview::factory()->submitted()->create();
        $this->assertFalse($submitted->isAdminAction());

        $resubmitted = ResellerApplicationReview::factory()->resubmitted()->create();
        $this->assertFalse($resubmitted->isAdminAction());
    }

    /**
     * Test isUserAction returns true for user actions.
     */
    public function test_is_user_action_returns_true_for_user_actions(): void
    {
        $submitted = ResellerApplicationReview::factory()->submitted()->create();
        $this->assertTrue($submitted->isUserAction());

        $resubmitted = ResellerApplicationReview::factory()->resubmitted()->create();
        $this->assertTrue($resubmitted->isUserAction());
    }

    /**
     * Test isUserAction returns false for admin actions.
     */
    public function test_is_user_action_returns_false_for_admin_actions(): void
    {
        $approved = ResellerApplicationReview::factory()->approved()->create();
        $this->assertFalse($approved->isUserAction());

        $rejected = ResellerApplicationReview::factory()->rejected()->create();
        $this->assertFalse($rejected->isUserAction());
    }

    /**
     * Test adminActions scope filters admin actions.
     */
    public function test_admin_actions_scope_filters_admin_actions(): void
    {
        ResellerApplicationReview::factory()->submitted()->create();
        ResellerApplicationReview::factory()->approved()->create();
        ResellerApplicationReview::factory()->rejected()->create();
        ResellerApplicationReview::factory()->resubmitted()->create();

        $adminActions = ResellerApplicationReview::adminActions()->get();

        $this->assertCount(2, $adminActions);
        $this->assertTrue($adminActions->every(fn ($review) => in_array($review->action, ['approved', 'rejected'])));
    }

    /**
     * Test userActions scope filters user actions.
     */
    public function test_user_actions_scope_filters_user_actions(): void
    {
        ResellerApplicationReview::factory()->submitted()->create();
        ResellerApplicationReview::factory()->approved()->create();
        ResellerApplicationReview::factory()->rejected()->create();
        ResellerApplicationReview::factory()->resubmitted()->create();

        $userActions = ResellerApplicationReview::userActions()->get();

        $this->assertCount(2, $userActions);
        $this->assertTrue($userActions->every(fn ($review) => in_array($review->action, ['submitted', 'resubmitted'])));
    }

    /**
     * Test that created_at timestamp is automatically set.
     */
    public function test_created_at_timestamp_is_automatically_set(): void
    {
        $review = ResellerApplicationReview::factory()->create();

        $this->assertNotNull($review->created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $review->created_at);
    }

    /**
     * Test that updated_at is disabled.
     */
    public function test_updated_at_is_disabled(): void
    {
        $review = ResellerApplicationReview::factory()->create();

        // Model should not have updated_at column behavior
        $this->assertNull($review->updated_at);
    }
}
