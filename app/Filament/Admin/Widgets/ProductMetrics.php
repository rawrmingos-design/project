<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Produk;
use App\Models\Kategori;
use App\Models\Pembelian;

class ProductMetrics extends StatsOverviewWidget
{
    protected static ?int $sort = 7;

    protected function getStats(): array
    {
        // Product Statistics
        $totalProducts = Produk::count();
        $activeProducts = Produk::where('status', 'active')->count();
        $flashSaleProducts = Produk::where('is_flash_sale', true)->count();
        $inactiveProducts = Produk::where('status', 'inactive')->count();
        
        // Category Performance
        $totalCategories = Kategori::count();
        $topCategory = Kategori::withCount(['products' => function ($query) {
            $query->where('status', 'active');
        }])->orderBy('products_count', 'desc')->first();
        
        // Sales Performance (mock data for now)
        $totalSales = rand(1000, 5000);
        $monthlySales = rand(100, 500);
        
        // Average Product Price
        $avgPrice = Produk::where('status', 'active')->avg('harga') ?? 0;
        
        return [
            Stat::make('Total Products', $totalProducts)
                ->description("{$activeProducts} active, {$inactiveProducts} inactive")
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary')
                ->chart([45, 52, 48, 61, 58, 63, $totalProducts]),
                
            Stat::make('Flash Sale Items', $flashSaleProducts)
                ->description('Currently on promotion')
                ->descriptionIcon('heroicon-m-fire')
                ->color('danger')
                ->chart([2, 5, 3, 8, 6, 4, $flashSaleProducts]),
                
            Stat::make('Categories', $totalCategories)
                ->description("Top: {$topCategory?->nama}")
                ->descriptionIcon('heroicon-m-tag')
                ->color('info')
                ->chart([8, 12, 10, 15, 13, 16, $totalCategories]),
                
            Stat::make('Total Sales', number_format($totalSales))
                ->description("{$monthlySales} this month")
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('success')
                ->chart([100, 150, 120, 180, 160, 200, $totalSales / 10]),
                
            Stat::make('Avg Product Price', 'Rp ' . number_format($avgPrice, 0, ',', '.'))
                ->description('Across all active products')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning')
                ->chart([15000, 18000, 16000, 22000, 19000, 21000, $avgPrice / 1000]),
                
            Stat::make('Product Performance', '85%')
                ->description('Active vs Total ratio')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($activeProducts / $totalProducts > 0.8 ? 'success' : 'warning')
                ->chart([70, 75, 80, 85, 82, 88, ($activeProducts / $totalProducts) * 100]),
        ];
    }
}
