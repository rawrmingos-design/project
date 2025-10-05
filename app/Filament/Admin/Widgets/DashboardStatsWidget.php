<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\User;
use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Pembelian;
use Illuminate\Support\Facades\DB;

class DashboardStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        try {
            return [
                Stat::make('Total Users', User::count() ?? 0)
                    ->description('Registered users')
                    ->descriptionIcon('heroicon-m-users')
                    ->color('success'),
                    
                Stat::make('Total Games', Kategori::count() ?? 0)
                    ->description('Available games')
                    ->descriptionIcon('heroicon-m-puzzle-piece')
                    ->color('info'),
                    
                Stat::make('Total Products', Layanan::count() ?? 0)
                    ->description('Available products')
                    ->descriptionIcon('heroicon-m-shopping-bag')
                    ->color('warning'),
                    
                Stat::make('Total Orders', Pembelian::count() ?? 0)
                    ->description('All time orders')
                    ->descriptionIcon('heroicon-m-shopping-cart')
                    ->color('primary'),
            ];
        } catch (\Exception $e) {
            return [
                Stat::make('Error', 'N/A')
                    ->description('Unable to load stats')
                    ->color('danger'),
            ];
        }
    }
}
