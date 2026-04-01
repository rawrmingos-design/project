<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Produk;

class ProfitAnalysis extends ChartWidget
{
    protected ?string $heading = 'Member Markup Analysis by Provider';
    
    protected static ?int $sort = 8;

    protected function getData(): array
    {
        // Get member-tier markup data by provider
        $providers = ['digiflazz', 'apigames', 'vip', 'bangjeff', 'topupedia', 'manual'];
        $profitData = [];
        $avgProfitData = [];
        $productCountData = [];
        
        foreach ($providers as $provider) {
            $products = Produk::where('provider', $provider)
                ->whereIn('status', ['available', 'active']);
            
            $totalProfit = $products->sum('profit_member') ?? 0;
            $avgProfit = $products->avg('profit_member') ?? 0;
            $productCount = $products->count();
            
            $profitData[] = $totalProfit;
            $avgProfitData[] = round($avgProfit, 2);
            $productCountData[] = $productCount;
        }
        
        return [
            'datasets' => [
                [
                    'label' => 'Member Markup (%)',
                    'data' => $profitData,
                    'backgroundColor' => [
                        'rgba(34, 197, 94, 0.8)',   // Green for digiflazz
                        'rgba(59, 130, 246, 0.8)',  // Blue for apigames
                        'rgba(245, 158, 11, 0.8)',  // Yellow for vip
                        'rgba(239, 68, 68, 0.8)',   // Red for bangjeff
                        'rgba(168, 85, 247, 0.8)',  // Purple for topupedia
                        'rgba(107, 114, 128, 0.8)', // Gray for manual
                    ],
                    'borderColor' => [
                        'rgb(34, 197, 94)',
                        'rgb(59, 130, 246)',
                        'rgb(245, 158, 11)',
                        'rgb(239, 68, 68)',
                        'rgb(168, 85, 247)',
                        'rgb(107, 114, 128)',
                    ],
                    'borderWidth' => 2,
                ],
            ],
            'labels' => array_map('ucfirst', $providers),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
    
    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) {
                            return context.dataset.label + ": " + context.parsed.y + "%";
                        }'
                    ]
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                            'text' => 'Markup Percentage (%)',
                    ],
                ],
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Providers',
                    ],
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }
}
