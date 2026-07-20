<?php

namespace App\Http\Controllers\leaderboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Berita;

class LeaderboardController extends Controller 
{
    public function leaderboard()
    {
        // Query top game accounts by total spending (real data from actual purchases)
        // Falls back to reseller username if game account ID is empty
        $baseQuery = DB::table('pembelians')
            ->select(
                DB::raw('COALESCE(NULLIF(pembelians.user_id, ""), pembelians.username) as account_identifier'),
                DB::raw('COUNT(pembelians.id) as transaction_count'),
                DB::raw('SUM(pembelians.harga) as total_harga'),
                DB::raw('MAX(pembelians.created_at) as last_purchase_at')
            )
            ->where('pembelians.status', 'Sukses')
            ->whereNotNull('pembelians.user_id')
            ->groupBy(DB::raw('COALESCE(NULLIF(pembelians.user_id, ""), pembelians.username)'))
            ->orderBy('total_harga', 'desc');

        $top10Today = (clone $baseQuery)
            ->whereDate('pembelians.created_at', Carbon::today())
            ->limit(10)
            ->get();

        $top10ThisWeek = (clone $baseQuery)
            ->whereBetween('pembelians.created_at', [
                Carbon::now()->startOfWeek(Carbon::MONDAY),
                Carbon::now()->endOfWeek(Carbon::SUNDAY)
            ])
            ->limit(10)
            ->get();

        $top10ThisMonth = (clone $baseQuery)
            ->whereMonth('pembelians.created_at', Carbon::now()->month)
            ->whereYear('pembelians.created_at', Carbon::now()->year)
            ->limit(10)
            ->get();

        // Mask account identifiers for privacy
        $maskIdentifier = function($identifier) {
            $len = strlen((string) $identifier);
            if ($len <= 3) return (string) $identifier;
            // Show 1/3, hide 2/3
            $visibleCount = max(1, ceil($len / 3));
            return substr((string) $identifier, 0, $visibleCount) . str_repeat('*', $len - $visibleCount);
        };

        foreach ([$top10Today, $top10ThisWeek, $top10ThisMonth] as $collection) {
            foreach ($collection as $item) {
                if (!empty($item->account_identifier)) {
                    $item->account_identifier = $maskIdentifier($item->account_identifier);
                }
            }
        }

        return view('template.leaderboard.index', [
            'top10Today' => $top10Today,
            'top10ThisWeek' => $top10ThisWeek,
            'top10ThisMonth' => $top10ThisMonth,
            'logoheader' => Berita::where('tipe', 'logoheader')->latest()->first(),
            'logofooter' => Berita::where('tipe', 'logofooter')->latest()->first(),
        ]);
    }
}
