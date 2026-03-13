<?php

namespace App\Filament\Admin\Resources\Produks\Pages;

use App\Filament\Admin\Resources\Produks\ProdukResource;
use App\Models\PaketLayanan;
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

    protected function afterSave(): void
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
