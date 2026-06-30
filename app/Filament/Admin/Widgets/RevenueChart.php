<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Pembelian;
use Carbon\Carbon;

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
            $bucketDates = [];

            for ($i = 11; $i >= 0; $i--) {
                $date = Carbon::now()->subMonths($i);
                $bucketDates[] = $date->copy()->startOfMonth();
                $labels[] = $date->format('M Y');
            }

            $rangeStart = $bucketDates[0]->copy()->startOfMonth();
            $rangeEnd = end($bucketDates)->copy()->endOfMonth();
            $bucketMap = collect($bucketDates)->mapWithKeys(fn (Carbon $date): array => [
                $date->format('Y-m') => [
                    'revenue' => 0,
                    'profit' => 0,
                ],
            ])->all();

            $groupedResults = Pembelian::query()
                ->whereIn('status', ['Success', 'Sukses'])
                ->whereBetween('created_at', [$rangeStart, $rangeEnd])
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as bucket_key")
                ->selectRaw('COALESCE(SUM(harga), 0) as total_revenue')
                ->selectRaw('COALESCE(SUM(profit), 0) as total_profit')
                ->groupBy('bucket_key')
                ->pluck('total_profit', 'bucket_key')
                ->all();

            $groupedRevenue = Pembelian::query()
                ->whereIn('status', ['Success', 'Sukses'])
                ->whereBetween('created_at', [$rangeStart, $rangeEnd])
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as bucket_key")
                ->selectRaw('COALESCE(SUM(harga), 0) as total_revenue')
                ->groupBy('bucket_key')
                ->pluck('total_revenue', 'bucket_key')
                ->all();

            foreach (array_keys($bucketMap) as $bucketKey) {
                $revenueData[] = (float) ($groupedRevenue[$bucketKey] ?? 0);
                $profitData[] = (float) ($groupedResults[$bucketKey] ?? 0);
            }
        } else {
            $days = $activeFilter === 'week' ? 7 : 30;
            $bucketDates = [];

            for ($i = $days - 1; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);
                $bucketDates[] = $date->copy()->startOfDay();
                $labels[] = $date->format('d M');
            }

            $rangeStart = $bucketDates[0]->copy()->startOfDay();
            $rangeEnd = end($bucketDates)->copy()->endOfDay();
            $bucketMap = collect($bucketDates)->mapWithKeys(fn (Carbon $date): array => [
                $date->format('Y-m-d') => [
                    'revenue' => 0,
                    'profit' => 0,
                ],
            ])->all();

            $groupedResults = Pembelian::query()
                ->whereIn('status', ['Success', 'Sukses'])
                ->whereBetween('created_at', [$rangeStart, $rangeEnd])
                ->selectRaw('DATE(created_at) as bucket_key')
                ->selectRaw('COALESCE(SUM(harga), 0) as total_revenue')
                ->selectRaw('COALESCE(SUM(profit), 0) as total_profit')
                ->groupBy('bucket_key')
                ->get()
                ->keyBy('bucket_key');

            foreach (array_keys($bucketMap) as $bucketKey) {
                $revenueData[] = (float) ($groupedResults[$bucketKey]->total_revenue ?? 0);
                $profitData[] = (float) ($groupedResults[$bucketKey]->total_profit ?? 0);
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