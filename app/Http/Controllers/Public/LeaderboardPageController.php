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

        if (($settings->public_theme ?? PublicThemeRegistry::DEFAULT) === PublicThemeRegistry::DEFAULT) {
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

        if ($rows->isEmpty()) {
            $rows = $this->buildLeaderboardQuery(null)->get();
        }

        if ($rows->isEmpty()) {
            $rows = $this->buildLeaderboardQuery(null, false)->get();
        }

        return $rows
            ->map(function ($item) {
                $username = trim((string) ($item->username ?? 'User'));

                return [
                    'username' => $this->maskUsername($username),
                    'total' => (int) round((float) ($item->total_harga ?? 0)),
                ];
            })
            ->values()
            ->all();
    }

    private function buildLeaderboardQuery(?string $range, bool $filterActiveStatus = true)
    {
        $query = DB::table('pembelians')
            ->leftJoin('users', 'pembelians.username', '=', 'users.username')
            ->select(
                DB::raw("COALESCE(NULLIF(TRIM(users.name), ''), NULLIF(TRIM(pembelians.username), ''), 'User') as username"),
                DB::raw('SUM(pembelians.harga) as total_harga')
            )
            ->whereNotNull('pembelians.username')
            ->whereRaw("TRIM(pembelians.username) <> ''");

        if ($filterActiveStatus) {
            $query->whereIn(DB::raw('LOWER(pembelians.status)'), [
                'pending',
                'proses',
                'processing',
                'sukses',
                'success',
                'paid',
                'lunas',
            ]);
        }

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
