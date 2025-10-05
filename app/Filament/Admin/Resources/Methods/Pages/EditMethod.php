<?php

namespace App\Filament\Admin\Resources\Methods\Pages;

use App\Filament\Admin\Resources\Methods\MethodResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMethod extends EditRecord
{
    protected static string $resource = MethodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
