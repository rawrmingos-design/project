<?php

namespace App\Filament\Admin\Resources\CategoryTypes\Pages;

use App\Filament\Admin\Resources\CategoryTypes\CategoryTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCategoryType extends CreateRecord
{
    protected static string $resource = CategoryTypeResource::class;
}
