<?php

namespace App\Filament\Admin\Resources\CategoryTypes\Pages;

use App\Filament\Admin\Resources\CategoryTypes\CategoryTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCategoryTypes extends ListRecords
{
    protected static string $resource = CategoryTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
