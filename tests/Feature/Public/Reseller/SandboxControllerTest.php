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

    private function createResellerUser(): User
    {
        return User::factory()->create([
            'role' => 'Member',
        ]);
    }

    private function setupSandboxIntegration(User $user): void
    {
        ResellerIntegration::create([
            'user_id'          => $user->id,
            'integration_code' => 'TEST-SANDBOX-01',
            'is_active'        => true,
            'mode'             => 'sandbox',
        ]);
    }

    public function test_reseller_can_view_sandbox_page(): void
    {
        $user = $this->createResellerUser();
        $this->setupSandboxIntegration($user);

        $response = $this->actingAs($user)->get('/id/reseller/sandbox');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page->component('Reseller/Sandbox'));
    }

    public function test_reseller_can_simulate_status(): void
    {
        $user = $this->createResellerUser();
        $this->setupSandboxIntegration($user);

        $pembelian = Pembelian::create([
            'order_id' => 'TRX-123',
            'username' => $user->username,
            'status'   => 'Pending',
            'is_sandbox' => true,
        ]);

        $response = $this->actingAs($user)->post('/id/reseller/sandbox/simulate', [
            'invoice' => 'TRX-123',
            'status' => 'Success',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('flash_success');
        
        $pembelian->refresh();
        $this->assertEquals('Sukses', $pembelian->status);
    }
}
