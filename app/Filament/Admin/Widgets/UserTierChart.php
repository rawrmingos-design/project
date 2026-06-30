<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\User;

class UserTierChart extends ChartWidget
{
    protected ?string $heading = 'Buyer Statistics';
    
    protected static ?int $sort = 4;
    
    protected int | string | array $columnSpan = 1;

    protected function getData(): array
    {
        $roleCounts = User::query()
            ->select('role')
            ->selectRaw('COUNT(*) as aggregate_count')
            ->whereIn('role', ['Admin', 'Platinum', 'Gold', 'Member'])
            ->groupBy('role')
            ->pluck('aggregate_count', 'role')
            ->map(fn ($count) => (int) $count)
            ->all();

        return [
            'datasets' => [
                [
                    'label' => 'User Distribution',
                    'data' => [
                        $roleCounts['Admin'] ?? 0,
                        $roleCounts['Platinum'] ?? 0,
                        $roleCounts['Gold'] ?? 0,
                        $roleCounts['Member'] ?? 0,
                    ],
                    'backgroundColor' => [
                        'rgb(239, 68, 68)',   // Red for Admin
                        'rgb(59, 130, 246)',  // Blue for Platinum
                        'rgb(245, 158, 11)',  // Yellow for Gold
                        'rgb(34, 197, 94)',   // Green for Member
                    ],
                    'borderColor' => [
                        'rgb(220, 38, 38)',
                        'rgb(37, 99, 235)',
                        'rgb(217, 119, 6)',
                        'rgb(22, 163, 74)',
                    ],
                    'borderWidth' => 2,
                ],
            ],
            'labels' => ['Admin', 'Platinum', 'Gold', 'Member'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
    
    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }
}
