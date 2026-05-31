<?php

namespace App\Http\Controllers\Public\Reseller;

use App\Http\Controllers\Controller;
use App\Models\ResellerCallbackDelivery;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CallbackLogController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $deliveries = ResellerCallbackDelivery::query()
            ->whereHas('integration', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with(['integration:id,mode,integration_code'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return Inertia::render('Reseller/CallbackLogs', [
            'deliveries' => $deliveries,
        ]);
    }
}
