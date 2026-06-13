<?php

namespace Tests\Unit\Services;

use App\Models\ResellerApplication;
use App\Models\User;
use App\Services\ResellerApplicationEligibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResellerApplicationEligibilityServiceTest extends TestCase
{
    use RefreshDatabase;

    private ResellerApplicationEligibilityService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ResellerApplicationEligibilityService();
    }

    /**
     * User with reseller access should not be eligible to submit a new application.
     */
    public function test_user_with_reseller_access_is_not_eligible(): void
    {
        $user = User::factory()->create([
            'role' => 'Gold',
            'created_at' => now()->subDays(10),
        ]);

        $result = $this->service->evaluate($user);

        $this->assertFalse($result['can_apply']);
        $this->assertContains('Akun sudah memiliki akses reseller.', $result['reasons']);
    }

    /**
     * User with pending application should not be eligible.
     */
    public function test_user_with_pending_application_is_not_eligible(): void
    {
        $user = User::factory()->create([
            'role' => 'Member',
            'created_at' => now()->subDays(10),
        ]);

        ResellerApplication::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        $result = $this->service->evaluate($user->fresh());

        $this->assertFalse($result['can_apply']);
        $this->assertContains('Aplikasi reseller sedang dalam proses review.', $result['reasons']);
    }

    /**
     * Recently rejected applications should enforce the 30-day cooldown.
     */
    public function test_user_with_recent_rejection_is_not_eligible(): void
    {
        $user = User::factory()->create([
            'role' => 'Member',
            'created_at' => now()->subDays(10),
        ]);

        ResellerApplication::factory()->rejected()->create([
            'user_id' => $user->id,
            'rejected_at' => now()->subDays(15),
        ]);

        $result = $this->service->evaluate($user->fresh());

        $this->assertFalse($result['can_apply']);
        $this->assertContains(
            'Pengajuan ulang reseller dapat dilakukan setelah masa tunggu 30 hari berakhir.',
            $result['reasons']
        );
    }

    /**
     * Old rejected applications should no longer block re-application.
     */
    public function test_user_with_old_rejection_is_eligible_after_cooldown(): void
    {
        $user = User::factory()->create([
            'role' => 'Member',
            'created_at' => now()->subDays(45),
        ]);

        ResellerApplication::factory()->rejected()->create([
            'user_id' => $user->id,
            'rejected_at' => now()->subDays(31),
        ]);

        $result = $this->service->evaluate($user->fresh());

        $this->assertTrue($result['can_apply']);
        $this->assertSame([], $result['reasons']);
    }

    /**
     * Account age below 7 days should block application.
     */
    public function test_user_with_account_less_than_7_days_is_not_eligible(): void
    {
        $user = User::factory()->create([
            'role' => 'Member',
            'created_at' => now()->subDays(5),
        ]);

        $result = $this->service->evaluate($user);

        $this->assertFalse($result['can_apply']);
        $this->assertContains('Umur akun minimal 7 hari untuk mengajukan reseller.', $result['reasons']);
    }

    /**
     * Account age of 7 days or more should pass age requirement.
     */
    public function test_user_with_account_at_least_7_days_old_is_eligible(): void
    {
        $user = User::factory()->create([
            'role' => 'Member',
            'created_at' => now()->subDays(8),
        ]);

        $result = $this->service->evaluate($user);

        $this->assertTrue($result['can_apply']);
        $this->assertSame([], $result['reasons']);
    }

    /**
     * Eligible users should return empty reasons.
     */
    public function test_eligible_user_has_empty_reasons_array(): void
    {
        $user = User::factory()->create([
            'role' => 'Member',
            'created_at' => now()->subDays(10),
        ]);

        $result = $this->service->evaluate($user);

        $this->assertTrue($result['can_apply']);
        $this->assertSame([], $result['reasons']);
    }

    /**
     * canApply should return only the eligibility boolean.
     */
    public function test_can_apply_returns_boolean(): void
    {
        $eligibleUser = User::factory()->create([
            'role' => 'Member',
            'created_at' => now()->subDays(10),
        ]);

        $ineligibleUser = User::factory()->create([
            'role' => 'Member',
            'created_at' => now()->subDays(2),
        ]);

        $this->assertTrue($this->service->canApply($eligibleUser));
        $this->assertFalse($this->service->canApply($ineligibleUser));
    }

    /**
     * reasons should return only the reasons array.
     */
    public function test_reasons_returns_array(): void
    {
        $user = User::factory()->create([
            'role' => 'Member',
            'created_at' => now()->subDays(2),
        ]);

        $reasons = $this->service->reasons($user);

        $this->assertIsArray($reasons);
        $this->assertContains('Umur akun minimal 7 hari untuk mengajukan reseller.', $reasons);
    }

    /**
     * Multiple failed rules should return all relevant reasons.
     */
    public function test_evaluate_returns_multiple_reasons_when_multiple_rules_fail(): void
    {
        $user = User::factory()->create([
            'role' => 'Gold',
            'created_at' => now()->subDays(2),
        ]);

        ResellerApplication::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        $result = $this->service->evaluate($user->fresh());

        $this->assertFalse($result['can_apply']);
        $this->assertContains('Akun sudah memiliki akses reseller.', $result['reasons']);
        $this->assertContains('Aplikasi reseller sedang dalam proses review.', $result['reasons']);
        $this->assertContains('Umur akun minimal 7 hari untuk mengajukan reseller.', $result['reasons']);
    }
}
