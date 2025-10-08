<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Pembelian; // Asumsikan model ini ada
use Carbon\Carbon;

class RevenueChart extends ChartWidget
{
    // Judul Chart
    protected ?string $heading = 'Revenue Analytics (Last 12 Months)';
    
    // Urutan Widget
    protected static ?int $sort = 5;
    
    // Span Kolom (Menggunakan seluruh lebar)
    protected int | string | array $columnSpan = 'full';
    
    /**
     * Mengambil dan memformat data untuk chart.
     */
    protected function getData(): array
    {
        $months = [];
        $depositData = [];
        $purchaseData = [];
        $totalRevenueData = [];
        
        // Generate last 12 months
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthYear = $date->format('M Y');
            $months[] = $monthYear;
            
            // Get deposits for this month (mock data for now)
            // LAKUKAN KONEKSI KE DATABASE DI SINI, BUKAN MOCK DATA.
            $monthlyDeposits = rand(5000000, 15000000); 
            
            // Get purchases for this month (mock data for now)
            // LAKUKAN KONEKSI KE DATABASE DI SINI, BUKAN MOCK DATA.
            $monthlyPurchases = rand(3000000, 10000000);
            
            $depositData[] = $monthlyDeposits;
            $purchaseData[] = $monthlyPurchases;
            $totalRevenueData[] = $monthlyDeposits + $monthlyPurchases;
        }
        
        return [
            'datasets' => [
                [
                    'label' => 'Deposits',
                    'data' => $depositData,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'borderColor' => 'rgb(34, 197, 94)',
                    'borderWidth' => 2,
                    'fill' => true,
                ],
                [
                    'label' => 'Purchases',
                    'data' => $purchaseData,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 2,
                    'fill' => true,
                ],
                [
                    'label' => 'Total Revenue',
                    'data' => $totalRevenueData,
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'borderColor' => 'rgb(245, 158, 11)',
                    'borderWidth' => 3,
                    'fill' => false,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $months,
        ];
    }

    /**
     * Menentukan tipe chart yang akan digunakan (Line Chart).
     */
    protected function getType(): string
    {
        return 'line';
    }
    
    /**
     * Mengatur opsi tampilan ChartJS.
     */
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
                    // Tambahkan callback untuk memformat label Y-axis sebagai mata uang (opsional)
                    // 'ticks' => [
                    //     'callback' => fn ($value) => 'Rp ' . number_format($value, 0, ',', '.'),
                    // ],
                ],
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }
}