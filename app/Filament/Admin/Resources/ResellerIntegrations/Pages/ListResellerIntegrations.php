<?php

namespace App\Filament\Admin\Resources\ResellerIntegrations\Pages;

use App\Filament\Admin\Resources\ResellerIntegrations\ResellerIntegrationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListResellerIntegrations extends ListRecords
{
    protected static string $resource = ResellerIntegrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
