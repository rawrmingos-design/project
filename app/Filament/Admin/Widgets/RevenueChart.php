<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Pembelian;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RevenueChart extends ChartWidget
{
    protected ?string $heading = 'Revenue & Profit Analytics';
    
    protected static ?int $sort = 5;
    
    protected int | string | array $columnSpan = 'full';
    
    public ?string $filter = 'month';

    protected function getFilters(): ?array
    {
        return [
            'week' => 'Last 7 Days',
            'month' => 'Last 30 Days',
            'year' => 'Last 12 Months',
        ];
    }

    protected function getData(): array
    {
        $activeFilter = $this->filter;
        
        $revenueData = [];
        $profitData = [];
        $labels = [];

        if ($activeFilter === 'year') {
            // Last 12 Months
            for ($i = 11; $i >= 0; $i--) {
                $date = Carbon::now()->subMonths($i);
                $monthStart = $date->copy()->startOfMonth();
                $monthEnd = $date->copy()->endOfMonth();
                $label = $date->format('M Y');
                
                $data = Pembelian::whereIn('status', ['Success', 'Sukses'])
                    ->whereBetween('created_at', [$monthStart, $monthEnd])
                    ->select(
                        DB::raw('SUM(harga) as total_revenue'),
                        DB::raw('SUM(profit) as total_profit')
                    )
                    ->first();
                    
                $labels[] = $label;
                $revenueData[] = $data->total_revenue ?? 0;
                $profitData[] = $data->total_profit ?? 0;
            }
        } elseif ($activeFilter === 'week') {
             // Last 7 Days
             for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);
                $dayStart = $date->copy()->startOfDay();
                $dayEnd = $date->copy()->endOfDay();
                $label = $date->format('d M');
                
                $data = Pembelian::whereIn('status', ['Success', 'Sukses'])
                    ->whereBetween('created_at', [$dayStart, $dayEnd])
                    ->select(
                        DB::raw('SUM(harga) as total_revenue'),
                        DB::raw('SUM(profit) as total_profit')
                    )
                    ->first();
                    
                $labels[] = $label;
                $revenueData[] = $data->total_revenue ?? 0;
                $profitData[] = $data->total_profit ?? 0;
             }
        } else {
            // Default: Month (Last 30 Days)
            for ($i = 29; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);
                $dayStart = $date->copy()->startOfDay();
                $dayEnd = $date->copy()->endOfDay();
                $label = $date->format('d M');
                
                $data = Pembelian::whereIn('status', ['Success', 'Sukses'])
                    ->whereBetween('created_at', [$dayStart, $dayEnd])
                    ->select(
                        DB::raw('SUM(harga) as total_revenue'),
                        DB::raw('SUM(profit) as total_profit')
                    )
                    ->first();
                    
                $labels[] = $label;
                $revenueData[] = $data->total_revenue ?? 0;
                $profitData[] = $data->total_profit ?? 0;
            }
        }
        
        return [
            'datasets' => [
                [
                    'label' => 'Total Revenue',
                    'data' => $revenueData,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'borderColor' => 'rgb(34, 197, 94)',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Total Profit',
                    'data' => $profitData,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $labels,
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
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => "function(value) { return 'Rp ' + new Intl.NumberFormat('id-ID').format(value); }",
                    ],
                ],
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                ],
            ],
            'interaction' => [
                'intersect' => false,
                'mode' => 'index',
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }
}