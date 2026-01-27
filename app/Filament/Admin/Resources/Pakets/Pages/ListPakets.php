<?php

namespace App\Filament\Admin\Resources\Pakets\Pages;

use App\Filament\Admin\Resources\Pakets\PaketResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListPakets extends ListRecords
{
    protected static string $resource = PaketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
