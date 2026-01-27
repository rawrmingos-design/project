<?php

namespace App\Filament\Admin\Resources\Artikels\Pages;

use App\Filament\Admin\Resources\Artikels\ArtikelResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListArtikels extends ListRecords
{
    protected static string $resource = ArtikelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
