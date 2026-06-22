<?php

namespace App\Filament\Admin\Resources\ResellerOrders\Pages;

use App\Filament\Admin\Resources\ResellerOrders\ResellerOrderResource;
use App\Filament\Admin\Resources\ResellerOrders\Widgets\ResellerOrderStatsOverview;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListResellerOrders extends ListRecords
{
    protected static string $resource = ResellerOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action - orders come from API only
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ResellerOrderStatsOverview::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 4;
    }
}
