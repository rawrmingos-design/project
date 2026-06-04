<?php

namespace Tests\Feature\Reseller;

use App\Http\Controllers\Public\Reseller\DashboardController;
use App\Models\Pembelian;
use App\Models\ResellerIntegration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for DashboardController metrics logic.
 *
 * Strategy: call computeMetrics() directly on the controller, avoiding all
 * Inertia HTTP routing and view-rendering concerns. This is a pure unit test
 * of the business logic, which is what we care about.
 */
class DashboardMetricsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private ResellerIntegration $liveIntegration;
    private DashboardController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->controller = app(DashboardController::class);

        $this->user = User::factory()->create([
            'username' => 'reseller.dashboard.' . uniqid(),
            'role'     => 'Member',
        ]);

        $this->liveIntegration = ResellerIntegration::factory()->create([
            'user_id'          => $this->user->getKey(),
            'integration_code' => 'live-dash-001',
            'mode'             => 'live',
            'is_active'        => true,
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function makeH2hOrder(array $attrs = []): Pembelian
    {
        return Pembelian::factory()->create(array_merge([
            'username'                => $this->user->username,
            'reseller_integration_id' => $this->liveIntegration->getKey(),
            'is_sandbox'              => false,
            'environment'             => 'live',
            'traffic_source'          => 'reseller_h2h',
            'harga'                   => 10_000,
            'status'                  => 'Sukses',
        ], $attrs));
    }

    private function makeWebOrder(array $attrs = []): Pembelian
    {
        return Pembelian::factory()->create(array_merge([
            'username'                => $this->user->username,
            'reseller_integration_id' => null,      // ← web order, NOT H2H
            'is_sandbox'              => false,
            'harga'                   => 10_000,
            'status'                  => 'Sukses',
        ], $attrs));
    }

    private function getMetrics(): array
    {
        return $this->controller->computeMetrics($this->user)['metrics'];
    }

    private function getRecentOrders(): array
    {
        return $this->controller->computeMetrics($this->user)['recent_orders']->toArray();
    }

    // ── Tests: H2H isolation ─────────────────────────────────────────────────

    public function test_web_orders_are_not_counted_in_dashboard_metrics(): void
    {
        $this->makeWebOrder(['status' => 'Sukses']);
        $this->makeWebOrder(['status' => 'Gagal']);

        $metrics = $this->getMetrics();

        $this->assertEquals(0, $metrics['orders_today'],
            'Web orders should not appear in H2H dashboard metrics');
        $this->assertEquals(0, $metrics['revenue_today'],
            'Web order revenue should not appear in H2H dashboard');
    }

    public function test_h2h_orders_appear_in_metrics(): void
    {
        $this->makeH2hOrder(['status' => 'Sukses', 'harga' => 15_000]);
        $this->makeH2hOrder(['status' => 'Sukses', 'harga' => 10_000]);

        $metrics = $this->getMetrics();

        $this->assertEquals(2, $metrics['orders_today']);
        $this->assertEquals(25_000, $metrics['revenue_today']);
        $this->assertEquals(100.0, $metrics['success_rate']);
    }

    public function test_sandbox_orders_not_counted_in_live_metrics(): void
    {
        $sandboxIntegration = ResellerIntegration::factory()->create([
            'user_id'          => $this->user->getKey(),
            'integration_code' => 'sbx-dash-001',
            'mode'             => 'sandbox',
            'is_active'        => true,
        ]);

        Pembelian::factory()->create([
            'username'                => $this->user->username,
            'reseller_integration_id' => $sandboxIntegration->getKey(),
            'is_sandbox'              => true,
            'environment'             => 'sandbox',
            'status'                  => 'Sukses',
            'harga'                   => 10_000,
        ]);

        $metrics = $this->getMetrics();

        $this->assertEquals(0, $metrics['orders_today'],
            'Sandbox orders should not appear in live dashboard metrics');
        $this->assertEquals(0, $metrics['revenue_today']);
    }

    // ── Tests: Status counting ────────────────────────────────────────────────

    public function test_gagal_status_counted_in_failed_pending_today(): void
    {
        $this->makeH2hOrder(['status' => 'Gagal']);

        $metrics = $this->getMetrics();

        $this->assertGreaterThan(0, $metrics['failed_pending_today'],
            "Status 'Gagal' should be counted in failed_pending_today");
    }

    public function test_batal_status_counted_in_failed_pending_today(): void
    {
        $this->makeH2hOrder(['status' => 'Batal']);

        $metrics = $this->getMetrics();

        $this->assertGreaterThan(0, $metrics['failed_pending_today'],
            "Status 'Batal' should be counted in failed_pending_today");
    }

    public function test_error_status_counted_as_failed_not_ignored(): void
    {
        $this->makeH2hOrder(['status' => 'Error']);

        $metrics = $this->getMetrics();

        $this->assertGreaterThan(0, $metrics['failed_pending_today'],
            "Status 'Error' (FAILED alias) should be counted in failed_pending_today");
    }

    public function test_pending_status_counted_in_failed_pending_today(): void
    {
        $this->makeH2hOrder(['status' => 'Pending']);

        $metrics = $this->getMetrics();

        $this->assertGreaterThan(0, $metrics['failed_pending_today'],
            "Status 'Pending' should be counted in failed_pending_today");
    }

    public function test_failed_orders_not_counted_in_revenue(): void
    {
        $this->makeH2hOrder(['status' => 'Sukses', 'harga' => 10_000]);
        $this->makeH2hOrder(['status' => 'Gagal',  'harga' => 10_000]);

        $metrics = $this->getMetrics();

        $this->assertEquals(10_000, $metrics['revenue_today'],
            'Only successful order revenue should be counted');
    }

    // ── Tests: Success rate ───────────────────────────────────────────────────

    public function test_success_rate_calculated_from_h2h_orders_only(): void
    {
        $this->makeH2hOrder(['status' => 'Sukses']);
        $this->makeH2hOrder(['status' => 'Sukses']);
        $this->makeH2hOrder(['status' => 'Gagal']);

        // Web orders — should NOT affect success rate
        $this->makeWebOrder(['status' => 'Gagal']);
        $this->makeWebOrder(['status' => 'Gagal']);

        $metrics = $this->getMetrics();

        $this->assertEquals(3, $metrics['orders_today'],
            'Only 3 H2H orders should be counted, not 5 total');
        $this->assertEquals(round(2 / 3 * 100, 1), $metrics['success_rate'],
            'Success rate: 2/3 H2H orders succeeded');
    }

    public function test_success_rate_is_zero_when_no_orders(): void
    {
        $metrics = $this->getMetrics();

        $this->assertEquals(0, $metrics['success_rate']);
        $this->assertEquals(0, $metrics['orders_today']);
    }

    // ── Tests: Recent orders list ─────────────────────────────────────────────

    public function test_recent_orders_only_shows_h2h_orders(): void
    {
        $h2hOrder = $this->makeH2hOrder();
        $webOrder = $this->makeWebOrder();

        $recentOrders = $this->getRecentOrders();
        $ids = array_column($recentOrders, 'order_id');

        $this->assertContains($h2hOrder->order_id, $ids,
            'H2H order should appear in recent orders');
        $this->assertNotContains($webOrder->order_id, $ids,
            'Web order should NOT appear in recent orders');
    }
}
