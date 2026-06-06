<?php

namespace Tests\Feature\Public\Reseller;

use App\Models\User;
use App\Models\ResellerIntegration;
use App\Models\Pembelian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SandboxControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create user + sandbox integration (to pass reseller.only middleware).
     */
    private function createResellerWithSandbox(): array
    {
        $user = User::factory()->create(['role' => 'Member']);

        $integration = ResellerIntegration::create([
            'user_id'          => $user->id,
            'integration_code' => 'TEST-SANDBOX-01',
            'is_active'        => true,
            'mode'             => 'sandbox',
        ]);

        return [$user, $integration];
    }

    public function test_reseller_can_view_sandbox_page(): void
    {
        [$user] = $this->createResellerWithSandbox();

        $response = $this->actingAs($user)->get('/id/reseller/sandbox');

        $response->assertStatus(200);
        // Only assert the status — don't assert Inertia component since it requires
        // built React assets to exist in the test environment.
    }

    public function test_reseller_can_simulate_order_status(): void
    {
        [$user, $integration] = $this->createResellerWithSandbox();

        // Use factory which sets all required fields including user_id
        $pembelian = Pembelian::factory()->create([
            'username'    => $user->username,
            'status'      => 'Pending',
            'is_sandbox'  => true,
        ]);

        $response = $this->actingAs($user)->post('/id/reseller/sandbox/simulate', [
            'invoice' => $pembelian->order_id,
            'status'  => 'Success',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('flash_success');

        $pembelian->refresh();
        $this->assertEquals('Sukses', $pembelian->status);
    }

    public function test_reseller_sandbox_simulate_returns_error_for_unknown_invoice(): void
    {
        [$user] = $this->createResellerWithSandbox();

        $response = $this->actingAs($user)->post('/id/reseller/sandbox/simulate', [
            'invoice' => 'NONEXISTENT-ORDER',
            'status'  => 'Success',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('flash_error', 'Invoice tidak ditemukan atau bukan milik Anda.');
    }
}
