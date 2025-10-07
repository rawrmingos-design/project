<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\User;

class UserTierChart extends ChartWidget
{
    protected ?string $heading = 'User Tier Distribution';
    
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $adminCount = User::where('role', 'Admin')->count();
        $platinumCount = User::where('role', 'Platinum')->count();
        $goldCount = User::where('role', 'Gold')->count();
        $memberCount = User::where('role', 'Member')->count();
        
        return [
            'datasets' => [
                [
                    'label' => 'User Distribution',
                    'data' => [$adminCount, $platinumCount, $goldCount, $memberCount],
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
