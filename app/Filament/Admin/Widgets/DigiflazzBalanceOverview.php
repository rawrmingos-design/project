<?php

namespace App\Filament\Admin\Widgets;

use App\Http\Controllers\DigiFlazzController;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class DigiflazzBalanceOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // Cache balance for 5 minutes to avoid spamming the API on every dashboard refresh
        $balance = Cache::remember('digiflazz_balance', 300, function () {
            $controller = new DigiFlazzController();
            $response = $controller->cekSaldo();
            
            // Check if response is successful and has data
            if (isset($response['data']['deposit'])) {
                return $response['data']['deposit'];
            }
            
            return 0;
        });

        return [
            Stat::make('Digiflazz Balance', 'Rp ' . number_format($balance, 0, ',', '.'))
                ->description('Realtime Server Balance')
                ->descriptionIcon('heroicon-m-wallet')
                ->color($balance < 100000 ? 'danger' : 'success'),
        ];
    }
}
