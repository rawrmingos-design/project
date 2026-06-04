<?php

namespace App\Http\Controllers\Public\Reseller;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DepositHistoryController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $validStatuses = ['Success', 'Pending', 'Gagal'];
        $statusFilter  = in_array($request->query('status'), $validStatuses)
            ? $request->query('status')
            : null;

        $query = Deposit::where('username', $user->username)->latest();

        if ($statusFilter !== null) {
            $query->where('status', $statusFilter);
        }

        $deposits = $query->paginate(10)->withQueryString();

        return Inertia::render('Reseller/DepositHistory', [
            'deposits'     => $deposits,
            'activeFilter' => $statusFilter,
        ]);
    }
}
