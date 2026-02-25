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
        $top10Today = DB::table('pembelians')
            ->join('users', 'pembelians.username', '=', 'users.username')
            ->select('users.name as username', DB::raw('SUM(pembelians.harga) as total_harga'))
            ->whereDate('pembelians.created_at', Carbon::today())
            ->where('pembelians.status', 'Sukses')
            ->whereNotNull('pembelians.username')
            ->groupBy('users.name')
            ->orderBy('total_harga', 'desc')
            ->limit(10)
            ->get();

        $top10ThisWeek = DB::table('pembelians')
            ->join('users', 'pembelians.username', '=', 'users.username')
            ->select('users.name as username', DB::raw('SUM(pembelians.harga) as total_harga'))
            ->whereBetween('pembelians.created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
            ->where('pembelians.status', 'Sukses')
            ->whereNotNull('pembelians.username')
            ->groupBy('users.name')
            ->orderBy('total_harga', 'desc')
            ->limit(10)
            ->get();

        $top10ThisMonth = DB::table('pembelians')
            ->join('users', 'pembelians.username', '=', 'users.username')
            ->select('users.name as username', DB::raw('SUM(pembelians.harga) as total_harga'))
            ->whereMonth('pembelians.created_at', Carbon::now()->month)
            ->where('pembelians.status', 'Sukses')
            ->whereNotNull('pembelians.username')
            ->groupBy('users.name')
            ->orderBy('total_harga', 'desc')
            ->limit(10)
            ->get();

        $maskUsername = function($username) {
            $len = strlen($username);
            if ($len <= 3) return $username;
            $visibleCount = max(1, floor($len / 2));
            return substr($username, 0, $visibleCount) . str_repeat('*', $len - $visibleCount);
        };

        foreach ([$top10Today, $top10ThisWeek, $top10ThisMonth] as $collection) {
            foreach ($collection as $item) {
                if (!empty($item->username)) {
                    $item->username = $maskUsername($item->username);
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
