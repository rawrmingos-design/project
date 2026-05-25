<?php

namespace App\Filament\Admin\Resources\ResellerCallbackProfiles\Pages;

use App\Filament\Admin\Resources\ResellerCallbackProfiles\ResellerCallbackProfileResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListResellerCallbackProfiles extends ListRecords
{
    protected static string $resource = ResellerCallbackProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
