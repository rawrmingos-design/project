<?php

namespace App\Filament\Admin\Resources\Kategoris\Pages;

use App\Filament\Admin\Resources\Kategoris\KategoriResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditKategori extends EditRecord
{
    protected static string $resource = KategoriResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
