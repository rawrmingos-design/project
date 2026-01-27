<?php

namespace App\Filament\Admin\Resources\CategoryTypes\Pages;

use App\Filament\Admin\Resources\CategoryTypes\CategoryTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCategoryType extends EditRecord
{
    protected static string $resource = CategoryTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
