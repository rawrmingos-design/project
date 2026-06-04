<?php

namespace Tests\Feature\Reseller;

use App\Models\ResellerIntegration;
use App\Models\User;
use App\Support\PembelianStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 5 — Task 5.4
 * Verifies server-side ?status= filter on OrderLogController.
 */
class OrderLogFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'username' => 'reseller.filter.' . uniqid(),
            'role'     => 'Member',
        ]);

        ResellerIntegration::factory()->create([
            'user_id'          => $this->user->id,
            'integration_code' => 'FILTER-LIVE-' . strtoupper(uniqid()),
            'mode'             => 'live',
            'is_active'        => true,
        ]);
    }

    // ── Filter helpers ─────────────────────────────────────────────────────

    public function test_orders_page_loads_without_filter(): void
    {
        $response = $this->actingAs($this->user)->get('/id/reseller/orders');

        $response->assertStatus(200);
    }

    public function test_orders_page_accepts_success_filter(): void
    {
        $response = $this->actingAs($this->user)->get('/id/reseller/orders?status=success');

        $response->assertStatus(200);
    }

    public function test_orders_page_accepts_failed_filter(): void
    {
        $response = $this->actingAs($this->user)->get('/id/reseller/orders?status=failed');

        $response->assertStatus(200);
    }

    public function test_orders_page_accepts_pending_filter(): void
    {
        $response = $this->actingAs($this->user)->get('/id/reseller/orders?status=pending');

        $response->assertStatus(200);
    }

    public function test_invalid_status_filter_does_not_cause_server_error(): void
    {
        // An invalid value like 'banana' should not cause 500 — just return 200/redirect
        $response = $this->actingAs($this->user)->get('/id/reseller/orders?status=banana');

        $this->assertNotEquals(500, $response->status(),
            'Invalid status filter must not cause a server error');
    }

    // ── PembelianStatus label helpers ──────────────────────────────────────

    public function test_failed_labels_are_correct(): void
    {
        $labels = PembelianStatus::failedLabels();
        $this->assertContains('Gagal', $labels);
        $this->assertContains('Batal', $labels);
    }

    public function test_pending_labels_are_correct(): void
    {
        $labels = PembelianStatus::pendingLabels();
        $this->assertContains('Pending', $labels);
        $this->assertContains('Processing', $labels);
    }

    public function test_success_labels_are_correct(): void
    {
        $labels = PembelianStatus::successLabels();
        $this->assertContains('Sukses', $labels);
    }
}
