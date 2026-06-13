<?php

namespace App\Filament\Admin\Resources\ResellerOrders\Pages;

use App\Filament\Admin\Resources\ResellerOrders\ResellerOrderResource;
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
}
