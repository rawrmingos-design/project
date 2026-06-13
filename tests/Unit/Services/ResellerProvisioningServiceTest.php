<?php

namespace Tests\Unit\Services;

use App\Models\ResellerIntegration;
use App\Models\User;
use App\Services\ResellerProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResellerProvisioningServiceTest extends TestCase
{
    use RefreshDatabase;

    private ResellerProvisioningService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ResellerProvisioningService();
    }

    /**
     * Test provision promotes Member user to Gold role.
     */
    public function test_promotes_member_user_to_gold_role(): void
    {
        $user = User::factory()->create([
            'role' => 'Member',
        ]);

        $this->service->provision($user);

        $user->refresh();
        $this->assertEquals('Gold', $user->role);
    }

    /**
     * Test provision does not change non-Member users.
     */
    public function test_does_not_change_non_member_role(): void
    {
        $goldUser = User::factory()->create(['role' => 'Gold']);
        $platinumUser = User::factory()->create(['role' => 'Platinum']);
        $adminUser = User::factory()->create(['role' => 'Admin']);

        $this->service->provision($goldUser);
        $this->service->provision($platinumUser);
        $this->service->provision($adminUser);

        $this->assertEquals('Gold', $goldUser->fresh()->role);
        $this->assertEquals('Platinum', $platinumUser->fresh()->role);
        $this->assertEquals('Admin', $adminUser->fresh()->role);
    }

    /**
     * Test provision creates live integration.
     */
    public function test_creates_live_integration(): void
    {
        $user = User::factory()->create(['role' => 'Member']);

        $this->service->provision($user);

        $this->assertDatabaseHas('reseller_integrations', [
            'user_id' => $user->id,
            'integration_type' => 'provider',
            'mode' => 'live',
            'is_active' => true,
        ]);

        $integration = ResellerIntegration::where('user_id', $user->id)
            ->where('mode', 'live')
            ->first();

        $this->assertNotNull($integration->api_key_hash);
        $this->assertStringStartsWith('rliv_', $integration->api_key_prefix);
        $this->assertNotEmpty($integration->integration_code);
    }

    /**
     * Test provision creates sandbox integration.
     */
    public function test_creates_sandbox_integration(): void
    {
        $user = User::factory()->create(['role' => 'Member']);

        $this->service->provision($user);

        $this->assertDatabaseHas('reseller_integrations', [
            'user_id' => $user->id,
            'integration_type' => 'provider',
            'mode' => 'sandbox',
            'is_active' => true,
        ]);

        $integration = ResellerIntegration::where('user_id', $user->id)
            ->where('mode', 'sandbox')
            ->first();

        $this->assertNotNull($integration->api_key_hash);
        $this->assertStringStartsWith('rsbx_', $integration->api_key_prefix);
        $this->assertNotEmpty($integration->integration_code);
    }

    /**
     * Test provision activates existing inactive integration.
     */
    public function test_activates_existing_inactive_integration(): void
    {
        $user = User::factory()->create(['role' => 'Member']);

        $existingIntegration = ResellerIntegration::factory()->create([
            'user_id' => $user->id,
            'integration_type' => 'provider',
            'mode' => 'live',
            'is_active' => false,
        ]);

        $this->service->provision($user);

        $existingIntegration->refresh();
        $this->assertTrue($existingIntegration->is_active);
    }

    /**
     * Test provision does not duplicate active integration.
     */
    public function test_does_not_duplicate_active_integration(): void
    {
        $user = User::factory()->create(['role' => 'Member']);

        ResellerIntegration::factory()->create([
            'user_id' => $user->id,
            'integration_type' => 'provider',
            'mode' => 'live',
            'is_active' => true,
        ]);

        $this->service->provision($user);

        $liveIntegrations = ResellerIntegration::where('user_id', $user->id)
            ->where('mode', 'live')
            ->count();

        $this->assertEquals(1, $liveIntegrations);
    }

    /**
     * Test provision is idempotent and can be called multiple times.
     */
    public function test_provision_is_idempotent(): void
    {
        $user = User::factory()->create(['role' => 'Member']);

        $this->service->provision($user);
        $this->service->provision($user);
        $this->service->provision($user);

        $this->assertEquals('Gold', $user->fresh()->role);

        $totalIntegrations = ResellerIntegration::where('user_id', $user->id)
            ->where('integration_type', 'provider')
            ->count();

        $this->assertEquals(2, $totalIntegrations); // 1 live + 1 sandbox
    }

    /**
     * Test provision creates both live and sandbox integrations.
     */
    public function test_creates_both_live_and_sandbox_integrations(): void
    {
        $user = User::factory()->create(['role' => 'Member']);

        $this->service->provision($user);

        $integrations = ResellerIntegration::where('user_id', $user->id)
            ->where('integration_type', 'provider')
            ->get();

        $this->assertCount(2, $integrations);

        $modes = $integrations->pluck('mode')->toArray();
        $this->assertContains('live', $modes);
        $this->assertContains('sandbox', $modes);
    }

    /**
     * Test provision sets metadata indicating auto provisioning.
     */
    public function test_sets_auto_provisioning_metadata(): void
    {
        $user = User::factory()->create(['role' => 'Member']);

        $this->service->provision($user);

        $integration = ResellerIntegration::where('user_id', $user->id)
            ->where('mode', 'live')
            ->first();

        $this->assertIsArray($integration->metadata);
        $this->assertEquals('reseller_application', $integration->metadata['source']);
        $this->assertTrue($integration->metadata['auto_provisioned']);
        $this->assertArrayHasKey('provisioned_at', $integration->metadata);
    }
}
