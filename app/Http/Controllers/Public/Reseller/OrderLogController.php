<?php

namespace App\Http\Controllers\Public\Reseller;

use App\Http\Controllers\Controller;
use App\Models\Pembelian;
use App\Support\PembelianStatus;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrderLogController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Phase 5 — Task 5.4: support ?status= server-side filter (bookmarkable)
        // Valid values: 'failed', 'pending', 'success' — anything else is ignored
        $statusFilter = $request->query('status');

        // Scope to orders matching the username AND originating from a reseller integration
        $query = Pembelian::query()
            ->where('username', $user->username)
            ->whereHas('resellerIntegration', function ($q) use ($user) {
                // Double check that the integration actually belongs to this user
                $q->where('user_id', $user->id);
            })
            ->with(['resellerIntegration:id,mode'])
            ->orderByDesc('created_at');

        match ($statusFilter) {
            'failed'  => $query->whereIn('status', PembelianStatus::failedLabels()),
            'pending' => $query->whereIn('status', PembelianStatus::pendingLabels()),
            'success' => $query->whereIn('status', PembelianStatus::successLabels()),
            default   => null,
        };

        $orders = $query->paginate(15)->withQueryString();

        return Inertia::render('Reseller/OrderLogs', [
            'orders'        => $orders,
            'currentFilter' => $statusFilter,
        ]);
    }
}
