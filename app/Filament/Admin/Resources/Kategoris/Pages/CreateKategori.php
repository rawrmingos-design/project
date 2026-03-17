<?php

namespace App\Filament\Admin\Resources\Kategoris\Pages;

use App\Filament\Admin\Resources\Kategoris\KategoriResource;
use App\Models\Kategori;
use App\Support\KategoriFormDataHandler;
use Filament\Resources\Pages\CreateRecord;

class CreateKategori extends CreateRecord
{
    protected static string $resource = KategoriResource::class;

    protected function handleRecordCreation(array $data): Kategori
    {
        return app(KategoriFormDataHandler::class)->create($data);
    }
}
