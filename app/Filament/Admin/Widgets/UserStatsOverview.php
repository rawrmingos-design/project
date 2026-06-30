<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class UserStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 2;

    protected function getColumns(): int
    {
        return 2;
    }

    protected function getStats(): array
    {
        $todayStart = Carbon::today()->startOfDay();
        $todayEnd = Carbon::today()->endOfDay();
        $yesterdayStart = Carbon::yesterday()->startOfDay();
        $yesterdayEnd = Carbon::yesterday()->endOfDay();
        $lastMonthStart = now()->subMonthNoOverflow()->startOfMonth();
        $lastMonthEnd = now()->subMonthNoOverflow()->endOfMonth();

        $transactionAggregates = Pembelian::query()
            ->selectRaw('COUNT(*) as total_transactions')
            ->selectRaw('SUM(CASE WHEN created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as last_month_transactions', [
                $lastMonthStart,
                $lastMonthEnd,
            ])
            ->first();

        $salesAggregates = Pembayaran::query()
            ->where('status', 'Lunas')
            ->selectRaw('COALESCE(SUM(harga), 0) as total_sales')
            ->selectRaw('COALESCE(SUM(CASE WHEN created_at BETWEEN ? AND ? THEN harga ELSE 0 END), 0) as last_month_sales', [
                $lastMonthStart,
                $lastMonthEnd,
            ])
            ->first();

        $userAggregates = User::query()
            ->selectRaw('SUM(CASE WHEN created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as new_users_today', [
                $todayStart,
                $todayEnd,
            ])
            ->selectRaw('SUM(CASE WHEN created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as new_users_yesterday', [
                $yesterdayStart,
                $yesterdayEnd,
            ])
            ->first();

        $totalTransactions = (int) ($transactionAggregates->total_transactions ?? 0);
        $transactionsLastMonth = (int) ($transactionAggregates->last_month_transactions ?? 0);
        $transactionGrowth = $transactionsLastMonth > 0 ? (($totalTransactions - $transactionsLastMonth) / $transactionsLastMonth) * 100 : 0;

        $totalSales = (int) ($salesAggregates->total_sales ?? 0);
        $salesLastMonth = (int) ($salesAggregates->last_month_sales ?? 0);
        $salesGrowth = $salesLastMonth > 0 ? (($totalSales - $salesLastMonth) / $salesLastMonth) * 100 : 0;

        $newUsersToday = (int) ($userAggregates->new_users_today ?? 0);
        $newUsersYesterday = (int) ($userAggregates->new_users_yesterday ?? 0);
        $userGrowth = $newUsersYesterday > 0 ? (($newUsersToday - $newUsersYesterday) / $newUsersYesterday) * 100 : 0;

        return [
            Stat::make('Transaction', number_format($totalTransactions))
                ->description(number_format($transactionGrowth, 2) . '% from last month')
                ->descriptionIcon($transactionGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($transactionGrowth >= 0 ? 'success' : 'danger')
                ->chart([7, 2, 10, 3, 15, 4, 17]),

            Stat::make('Sales', 'IDR ' . number_format($totalSales, 0, ',', '.'))
                ->description(number_format($salesGrowth, 2) . '% from last month')
                ->descriptionIcon($salesGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($salesGrowth >= 0 ? 'success' : 'danger')
                ->chart([100000, 500000, 20000, 400000, 100000]),

            Stat::make('New User', $newUsersToday)
                ->description(number_format($userGrowth, 2) . '% from yesterday')
                ->descriptionIcon($userGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($userGrowth >= 0 ? 'success' : 'danger')
                ->chart([1, 0, 5, 2, 0, 1, 0])
                ->columnSpan(2),
        ];
    }
}
