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
        $months = [];
        $totalUsers = [];
        $newUsers = [];
        $premiumUsers = [];
        
        // Generate last 12 months
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthYear = $date->format('M Y');
            $months[] = $monthYear;
            
            // Total users up to this month
            $totalUsersCount = User::where('created_at', '<=', $date->endOfMonth())->count();
            
            // New users in this month
            $newUsersCount = User::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
                
            // Premium users (Gold + Platinum) up to this month
            $premiumUsersCount = User::where('created_at', '<=', $date->endOfMonth())
                ->whereIn('role', ['Gold', 'Platinum'])
                ->count();
            
            $totalUsers[] = $totalUsersCount;
            $newUsers[] = $newUsersCount;
            $premiumUsers[] = $premiumUsersCount;
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
