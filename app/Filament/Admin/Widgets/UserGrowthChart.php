<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\User;
use Carbon\Carbon;

class UserGrowthChart extends ChartWidget
{
    protected ?string $heading = 'User Growth Analytics';
    
    protected static ?int $sort = 6;

    protected function getData(): array
    {
        $startMonth = Carbon::now()->startOfMonth()->subMonths(11);
        $endMonth = Carbon::now()->endOfMonth();

        $months = [];
        $monthKeys = [];

        for ($cursor = $startMonth->copy(); $cursor->lte($endMonth); $cursor->addMonth()) {
            $monthKeys[] = $cursor->format('Y-m');
            $months[] = $cursor->format('M Y');
        }

        $monthlyNewUsers = User::query()
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month_key, COUNT(*) as aggregate_count")
            ->whereBetween('created_at', [$startMonth, $endMonth])
            ->groupBy('month_key')
            ->pluck('aggregate_count', 'month_key')
            ->map(fn ($count) => (int) $count)
            ->all();

        $monthlyPremiumUsers = User::query()
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month_key, COUNT(*) as aggregate_count")
            ->whereBetween('created_at', [$startMonth, $endMonth])
            ->whereIn('role', ['Gold', 'Platinum'])
            ->groupBy('month_key')
            ->pluck('aggregate_count', 'month_key')
            ->map(fn ($count) => (int) $count)
            ->all();

        $totalUsersBaseline = User::query()
            ->where('created_at', '<', $startMonth)
            ->count();

        $premiumUsersBaseline = User::query()
            ->where('created_at', '<', $startMonth)
            ->whereIn('role', ['Gold', 'Platinum'])
            ->count();

        $totalUsers = [];
        $newUsers = [];
        $premiumUsers = [];
        $runningTotalUsers = (int) $totalUsersBaseline;
        $runningPremiumUsers = (int) $premiumUsersBaseline;

        foreach ($monthKeys as $monthKey) {
            $monthNewUsers = $monthlyNewUsers[$monthKey] ?? 0;
            $monthPremiumUsers = $monthlyPremiumUsers[$monthKey] ?? 0;

            $runningTotalUsers += $monthNewUsers;
            $runningPremiumUsers += $monthPremiumUsers;

            $totalUsers[] = $runningTotalUsers;
            $newUsers[] = $monthNewUsers;
            $premiumUsers[] = $runningPremiumUsers;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Users',
                    'data' => $totalUsers,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 2,
                    'fill' => true,
                ],
                [
                    'label' => 'New Users',
                    'data' => $newUsers,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'borderColor' => 'rgb(34, 197, 94)',
                    'borderWidth' => 2,
                    'fill' => true,
                ],
                [
                    'label' => 'Premium Users',
                    'data' => $premiumUsers,
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'borderColor' => 'rgb(245, 158, 11)',
                    'borderWidth' => 2,
                    'fill' => true,
                ],
            ],
            'labels' => $months,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
    
    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
            'interaction' => [
                'mode' => 'index',
                'intersect' => false,
            ],
        ];
    }
}
