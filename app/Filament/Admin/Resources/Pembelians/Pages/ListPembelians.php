<?php

namespace App\Filament\Admin\Resources\Pembelians\Pages;

use App\Filament\Admin\Resources\Pembelians\PembelianResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPembelians extends ListRecords
{
    protected static string $resource = PembelianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
