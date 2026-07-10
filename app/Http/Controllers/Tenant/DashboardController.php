<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Pembelian;
use App\Tenancy\TenantContext;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, TenantContext $tenantContext)
    {
        $tenant = $tenantContext->get();
        abort_unless($tenant, 404);
        abort_unless((int) ($request->user()?->id) === (int) $tenant->owner_user_id, 403);

        $orders = Pembelian::query()
            ->latest()
            ->limit(10)
            ->get();

        return view('tenant.dashboard', [
            'tenant' => $tenant,
            'owner' => $request->user(),
            'orders' => $orders,
            'totalOrders' => Pembelian::query()->count(),
            'totalRevenue' => Pembelian::query()->sum('harga'),
            'totalCommission' => Pembelian::query()->sum('profit'),
        ]);
    }
}
