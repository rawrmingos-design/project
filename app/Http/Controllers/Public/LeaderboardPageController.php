<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Controllers\leaderboard\LeaderboardController as LegacyLeaderboardController;
use App\Services\PublicSiteConfigService;
use App\Support\PublicThemeRegistry;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class LeaderboardPageController extends Controller
{
    public function __invoke(
        PublicSiteConfigService $siteConfigService,
        LegacyLeaderboardController $legacyLeaderboardController,
    ): Response|\Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\Foundation\Application {
        $settings = $siteConfigService->getSettings();

        if (PublicThemeRegistry::rendersLegacyBlade($settings->public_theme)) {
            return $legacyLeaderboardController->leaderboard();
        }

        $daily = $this->getTopPurchasesByRange('daily');
        $weekly = $this->getTopPurchasesByRange('weekly');
        $monthly = $this->getTopPurchasesByRange('monthly');
        return Inertia::render('Public/Leaderboard', [
            'companyName' => mb_strtoupper((string) $settings->judul_web),
            'leaderboards' => [
                'daily' => $daily,
                'weekly' => $weekly,
                'monthly' => $monthly,
            ],
            'meta' => [
                'title' => "Leaderboard - {$settings->judul_web}",
                'description' => 'Top 10 pembelian terbanyak dari pelanggan kami untuk periode harian, mingguan, dan bulanan.',
                'keywords' => "leaderboard, top pembelian, top up game, {$settings->judul_web}",
                'canonical' => url('/id/leaderboard'),
                'image' => url($siteConfigService->normalizeAssetPath($settings->logo_favicon)),
            ],
        ]);
    }

    private function getTopPurchasesByRange(string $range): array
    {
        $rows = $this->buildLeaderboardQuery($range)->get();

        // Kalau periode berjalan belum ada transaksi sukses, tampilkan agregat
        // semua waktu (tetap hanya transaksi sukses & tetap tanpa role Admin).
        if ($rows->isEmpty()) {
            $rows = $this->buildLeaderboardQuery(null)->get();
        }

        return $rows
            ->map(function ($item) {
                $username = trim((string) ($item->username ?? 'User'));

                return [
                    'username' => $this->maskUsername($username),
                    'count' => (int) ($item->transaction_count ?? 0),
                    'total' => (int) round((float) ($item->total_harga ?? 0)),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Query dasar leaderboard.
     *
     * Dua aturan produk yang ditegakkan di sini:
     * 1. Hanya transaksi berstatus sukses (`Sukses`/`Success`, case-insensitive).
     *    Pending/Expired/Gagal tidak pernah dihitung sebagai "total transaksi".
     * 2. Akun internal role `Admin` tidak ikut kompetisi. Pembeli tanpa baris
     *    `users` (data lama) tetap boleh tampil — `users.role` NULL berarti bukan
     *    Admin, bukan alasan untuk disembunyikan.
     */
    private function buildLeaderboardQuery(?string $range)
    {
        $query = DB::table('pembelians')
            ->leftJoin('users', 'pembelians.username', '=', 'users.username')
            ->select(
                DB::raw("COALESCE(NULLIF(TRIM(users.name), ''), NULLIF(TRIM(pembelians.username), ''), 'User') as username"),
                DB::raw('COUNT(pembelians.id) as transaction_count'),
                DB::raw('SUM(pembelians.harga) as total_harga')
            )
            ->whereNotNull('pembelians.username')
            ->whereRaw("TRIM(pembelians.username) <> ''")
            ->where(function ($roleQuery) {
                $roleQuery->whereNull('users.role')
                    ->orWhereRaw("LOWER(users.role) <> 'admin'");
            })
            ->whereIn(DB::raw('LOWER(pembelians.status)'), [
                'sukses',
                'success',
            ]);

        if ($range === 'daily') {
            $query->whereDate('pembelians.created_at', Carbon::today());
        } elseif ($range === 'weekly') {
            $query->whereBetween('pembelians.created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
        } elseif ($range === 'monthly') {
            $query->whereYear('pembelians.created_at', Carbon::now()->year)
                ->whereMonth('pembelians.created_at', Carbon::now()->month);
        }

        return $query
            ->groupBy(DB::raw("COALESCE(NULLIF(TRIM(users.name), ''), NULLIF(TRIM(pembelians.username), ''), 'User')"))
            ->orderByDesc('total_harga')
            ->limit(10);
    }

    private function maskUsername(string $username): string
    {
        $length = mb_strlen($username);

        if ($length <= 3) {
            return $username;
        }

        $visibleCount = max(1, (int) floor($length / 2));
        $visible = mb_substr($username, 0, $visibleCount);
        $hidden = str_repeat('*', $length - $visibleCount);

        return "{$visible}{$hidden}";
    }
}
