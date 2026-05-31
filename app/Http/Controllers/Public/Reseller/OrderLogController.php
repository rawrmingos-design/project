<?php

namespace App\Http\Controllers\Public\Reseller;

use App\Http\Controllers\Controller;
use App\Models\Pembelian;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrderLogController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Scope to orders matching the username AND originating from a reseller integration
        $orders = Pembelian::query()
            ->where('username', $user->username)
            ->whereHas('resellerIntegration', function ($query) use ($user) {
                // Double check that the integration actually belongs to this user
                $query->where('user_id', $user->id);
            })
            ->with(['resellerIntegration:id,mode'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return Inertia::render('Reseller/OrderLogs', [
            'orders' => $orders,
        ]);
    }
}
