<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\User;

class UserStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 2;

    protected function getColumns(): int
    {
        return 2;
    }

    protected function getStats(): array
    {
        // 1. Transaction Stats (Total Pembelian)
        $totalTransactions = \App\Models\Pembelian::count();
        $transactionsLastMonth = \App\Models\Pembelian::whereMonth('created_at', now()->subMonth()->month)->count();
        $transactionGrowth = $transactionsLastMonth > 0 ? (($totalTransactions - $transactionsLastMonth) / $transactionsLastMonth) * 100 : 0;
        
        // 2. Sales Stats (Total Revenue from Solved Orders)
        $totalSales = \App\Models\Pembelian::where('status', 'Success')->sum('harga'); // Assuming 'Success' or 'Lunas'
        $salesLastMonth = \App\Models\Pembelian::where('status', 'Success')
                            ->whereMonth('created_at', now()->subMonth()->month)
                            ->sum('harga');
        $salesGrowth = $salesLastMonth > 0 ? (($totalSales - $salesLastMonth) / $salesLastMonth) * 100 : 0;

        $newUsersToday = User::whereDate('created_at', today())->count();
        $newUsersYesterday = User::whereDate('created_at', today()->subDay())->count();
        $userGrowth = $newUsersYesterday > 0 ? (($newUsersToday - $newUsersYesterday) / $newUsersYesterday) * 100 : 0;
       
        return [
            Stat::make('Transaction', number_format($totalTransactions))
                ->description(number_format($transactionGrowth, 2) . '% from last month')
                ->descriptionIcon($transactionGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($transactionGrowth >= 0 ? 'success' : 'danger')
                ->chart([7, 2, 10, 3, 15, 4, 17]), 
                
            Stat::make('Sales', 'IDR ' . number_format($totalSales, 0, ',', '.'))
                ->description(number_format($salesGrowth, 2) . '% from last month')
                ->descriptionIcon($salesGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($salesGrowth >= 0 ? 'success' : 'danger')
                ->chart([100000, 500000, 20000, 400000, 100000]),

            Stat::make('New User', $newUsersToday)
                ->description(number_format($userGrowth, 2) . '% from yesterday')
                ->descriptionIcon($userGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($userGrowth >= 0 ? 'success' : 'danger')
                ->chart([1, 0, 5, 2, 0, 1, 0])
                ->columnSpan(2),    
        ];
    }
}
