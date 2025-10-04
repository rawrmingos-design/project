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
        return [
            Stat::make('Total Users', User::count())
                ->description('Registered users')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),
                
            Stat::make('Total Games', Kategori::count())
                ->description('Available games')
                ->descriptionIcon('heroicon-m-puzzle-piece')
                ->color('info'),
                
            Stat::make('Total Products', Layanan::count())
                ->description('Available products')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('warning'),
                
            Stat::make('Total Orders', Pembelian::count())
                ->description('All time orders')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('primary'),
        ];
    }
}
