<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\User;
use App\Models\Layanan;
use App\Models\Kategori;

class UserStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        // User Statistics
        $totalUsers = User::count();
        $adminUsers = User::where('role', 'Admin')->count();
        $platinumUsers = User::where('role', 'Platinum')->count();
        $goldUsers = User::where('role', 'Gold')->count();
        $memberUsers = User::where('role', 'Member')->count();
        $newUsersThisMonth = User::whereMonth('created_at', now()->month)->count();
        
        // Product Statistics
        $totalProducts = Layanan::count();
        $activeProducts = Layanan::where('status', 'available')->count();
        $flashSaleProducts = Layanan::where('is_flash_sale', true)->count();
        
        // Category Statistics
        $totalCategories = Kategori::count();
        
        // Balance Statistics
        $totalBalance = User::sum('balance');
        $avgBalance = User::avg('balance');
        
        return [
            Stat::make('Total Users', $totalUsers)
                ->description('Registered users in system')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary')
                ->chart([7, 12, 8, 15, 22, 18, $totalUsers]),
                
            Stat::make('Premium Users', $platinumUsers + $goldUsers)
                ->description("{$platinumUsers} Platinum, {$goldUsers} Gold")
                ->descriptionIcon('heroicon-m-star')
                ->color('warning')
                ->chart([2, 4, 3, 6, 8, 7, $platinumUsers + $goldUsers]),
                
            Stat::make('New Users This Month', $newUsersThisMonth)
                ->description('Monthly growth')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart([1, 3, 2, 5, 4, 6, $newUsersThisMonth]),
                
            Stat::make('Active Products', $activeProducts)
                ->description("Out of {$totalProducts} total products")
                ->descriptionIcon('heroicon-m-cube')
                ->color('info')
                ->chart([45, 52, 48, 61, 58, 63, $activeProducts]),
                
            Stat::make('Flash Sale Items', $flashSaleProducts)
                ->description('Currently on flash sale')
                ->descriptionIcon('heroicon-m-fire')
                ->color('danger')
                ->chart([2, 5, 3, 8, 6, 4, $flashSaleProducts]),
                
            Stat::make('Total Balance', 'Rp ' . number_format($totalBalance, 0, ',', '.'))
                ->description('Average: Rp ' . number_format($avgBalance, 0, ',', '.'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success')
                ->chart([100000, 150000, 120000, 180000, 200000, 175000, $totalBalance / 1000]),
        ];
    }
    
    protected static ?int $sort = 1;
}
