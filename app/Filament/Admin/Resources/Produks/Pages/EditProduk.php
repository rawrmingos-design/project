<?php

namespace App\Filament\Admin\Resources\Produks\Pages;

use App\Filament\Admin\Resources\Produks\ProdukResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProduk extends EditRecord
{
    protected static string $resource = ProdukResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
