<?php

namespace App\Filament\Admin\Resources\Methods\Pages;

use App\Filament\Admin\Resources\Methods\MethodResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMethods extends ListRecords
{
    protected static string $resource = MethodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
