<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LeaderboardService
{
    /**
     * @return array{today: array<int, array{username: string, total_harga: int}>, week: array<int, array{username: string, total_harga: int}>, month: array<int, array{username: string, total_harga: int}>}
     */
    public function rankings(): array
    {
        return [
            'today' => $this->topPurchases('today'),
            'week' => $this->topPurchases('week'),
            'month' => $this->topPurchases('month'),
        ];
    }

    /**
     * @return array<int, array{username: string, total_harga: int}>
     */
    private function topPurchases(string $range): array
    {
        $query = DB::table('pembelians')
            ->leftJoin('users', 'pembelians.username', '=', 'users.username')
            ->select(
                DB::raw("COALESCE(NULLIF(TRIM(users.name), ''), NULLIF(TRIM(pembelians.username), ''), 'User') as username"),
                DB::raw('SUM(pembelians.harga) as total_harga')
            )
            ->whereNotNull('pembelians.username')
            ->whereRaw("TRIM(pembelians.username) <> ''")
            ->whereIn(DB::raw('LOWER(pembelians.status)'), [
                'sukses',
                'success',
                'paid',
                'lunas',
            ]);

        if ($range === 'today') {
            $query->whereDate('pembelians.created_at', Carbon::today());
        } elseif ($range === 'week') {
            $query->whereBetween('pembelians.created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
        } else {
            $query->whereYear('pembelians.created_at', Carbon::now()->year)
                ->whereMonth('pembelians.created_at', Carbon::now()->month);
        }

        return $query
            ->groupBy(DB::raw("COALESCE(NULLIF(TRIM(users.name), ''), NULLIF(TRIM(pembelians.username), ''), 'User')"))
            ->orderByDesc('total_harga')
            ->limit(10)
            ->get()
            ->map(fn ($item): array => [
                'username' => $this->maskUsername(trim((string) ($item->username ?? 'User'))),
                'total_harga' => (int) round((float) ($item->total_harga ?? 0)),
            ])
            ->values()
            ->all();
    }

    private function maskUsername(string $username): string
    {
        $length = mb_strlen($username);

        if ($length <= 3) {
            return $username;
        }

        $visibleCount = max(1, (int) floor($length / 2));

        return mb_substr($username, 0, $visibleCount) . str_repeat('*', $length - $visibleCount);
    }
}
