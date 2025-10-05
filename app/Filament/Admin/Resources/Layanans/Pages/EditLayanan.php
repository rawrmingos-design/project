<?php

namespace App\Filament\Admin\Resources\Layanans\Pages;

use App\Filament\Admin\Resources\Layanans\LayananResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLayanan extends EditRecord
{
    protected static string $resource = LayananResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
