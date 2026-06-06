<?php

namespace App\Http\Controllers\Public\Reseller;

use App\Http\Controllers\Controller;
use App\Models\ResellerIntegration;
use App\Support\PembelianStatus;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Pembelian;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        ['metrics' => $metrics, 'recent_orders' => $recentOrders] = $this->computeMetrics($user);

        // --- Integration status: source of truth is reseller_integrations.is_active ---
        $user->load('resellerIntegrations');

        $liveIntegration    = $user->resellerIntegrations->where('mode', 'live')->first();
        $sandboxIntegration = $user->resellerIntegrations->where('mode', 'sandbox')->first();

        $allowedIps = $liveIntegration?->allowed_ips ?? [];

        $live = $liveIntegration ? [
            'is_active'   => (bool) $liveIntegration->is_active,
            'allowed_ips' => is_array($allowedIps) ? $allowedIps : [],
        ] : null;

        $sandbox = $sandboxIntegration ? [
            'is_active'    => (bool) $sandboxIntegration->is_active,
            'api_key_hint' => $sandboxIntegration->api_key_hint,
        ] : null;

        return Inertia::render('Reseller/Dashboard', [
            'live'          => $live,
            'sandbox'       => $sandbox,
            'metrics'       => $metrics,
            'recent_orders' => $recentOrders,
        ]);
    }

    /**
     * Compute dashboard metrics for a user.
     *
     * Extracted to a public method so it can be tested independently
     * without HTTP routing or Inertia view rendering.
     *
     * @return array{metrics: array, recent_orders: \Illuminate\Support\Collection}
     */
    public function computeMetrics(\App\Models\User $user): array
    {
        $today = now()->startOfDay();

        // Base query: ONLY H2H orders (traffic via reseller API, live mode)
        // Keputusan Q2: hanya order yang datang dari H2H API (reseller_integration_id IS NOT NULL)
        $ordersQuery = Pembelian::where('username', $user->username)
            ->whereNotNull('reseller_integration_id')
            ->where(function ($q) {
                // Live only: exclude sandbox orders
                $q->where('is_sandbox', false)
                  ->orWhereNull('is_sandbox');
            })
            ->where(function ($q) {
                $q->where('environment', 'live')
                  ->orWhereNull('environment');
            });

        // Status aliases — use PembelianStatus to handle all DB representations
        $successAliases    = PembelianStatus::aliasesFor(PembelianStatus::SUCCESS);
        $failedAliases     = PembelianStatus::aliasesFor(PembelianStatus::FAILED);
        $cancelledAliases  = PembelianStatus::aliasesFor(PembelianStatus::CANCELLED);
        $pendingAliases    = PembelianStatus::aliasesFor(PembelianStatus::PENDING);
        $failedOrCancelled = array_merge($failedAliases, $cancelledAliases);

        // Metrics (today only)
        $ordersTodayCount   = (clone $ordersQuery)->where('created_at', '>=', $today)->count();
        $ordersTodaySuccess = (clone $ordersQuery)->where('created_at', '>=', $today)->whereIn('status', $successAliases)->count();
        $ordersTodayFailed  = (clone $ordersQuery)->where('created_at', '>=', $today)->whereIn('status', $failedOrCancelled)->count();
        $ordersTodayPending = (clone $ordersQuery)->where('created_at', '>=', $today)->whereIn('status', $pendingAliases)->count();
        $revenueToday       = (clone $ordersQuery)->where('created_at', '>=', $today)->whereIn('status', $successAliases)->sum('harga');
        $successRate        = $ordersTodayCount > 0 ? round(($ordersTodaySuccess / $ordersTodayCount) * 100, 1) : 0;

        // Recent H2H live orders
        $recentOrders = (clone $ordersQuery)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn ($order) => [
                'id'         => $order->id,
                'order_id'   => $order->order_id,
                'layanan'    => $order->layanan,
                'harga'      => $order->harga,
                'status'     => $order->status,
                'is_sandbox' => $order->is_sandbox,
                'created_at' => $order->created_at->format('Y-m-d H:i:s'),
            ]);

        return [
            'metrics' => [
                'orders_today'         => $ordersTodayCount,
                'success_rate'         => $successRate,
                'failed_pending_today' => $ordersTodayFailed + $ordersTodayPending,
                'revenue_today'        => $revenueToday,
            ],
            'recent_orders' => $recentOrders,
        ];
    }
}
