<?php

namespace App\Filament\Admin\Resources\Methods\Pages;

use App\Filament\Admin\Resources\Methods\MethodResource;
use App\Support\PaymentCatalogAccess;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMethods extends ListRecords
{
    protected static string $resource = MethodResource::class;

    protected function getHeaderActions(): array
    {
        if (! PaymentCatalogAccess::isMaster()) {
            return [];
        }

        return [
            CreateAction::make(),
        ];
    }
}
