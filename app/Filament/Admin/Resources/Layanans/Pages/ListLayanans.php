<?php

namespace App\Filament\Admin\Resources\Layanans\Pages;

use App\Filament\Admin\Resources\Layanans\LayananResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLayanans extends ListRecords
{
    protected static string $resource = LayananResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
