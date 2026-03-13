<?php

namespace App\Filament\Admin\Resources\Produks\Pages;

use App\Filament\Admin\Resources\Produks\ProdukResource;
use App\Models\PaketLayanan;
use Filament\Resources\Pages\CreateRecord;

class CreateProduk extends CreateRecord
{
    protected static string $resource = ProdukResource::class;

    protected function afterCreate(): void
    {
        $this->syncPivotProductLogo();
    }

    private function syncPivotProductLogo(): void
    {
        $record = $this->getRecord();

        if (!$record || empty($record->product_logo)) {
            return;
        }

        PaketLayanan::where('layanan_id', $record->id)->update([
            'product_logo' => $record->product_logo,
            'updated_at' => now(),
        ]);
    }
}
