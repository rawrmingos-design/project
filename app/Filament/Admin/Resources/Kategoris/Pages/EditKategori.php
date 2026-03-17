<?php

namespace App\Filament\Admin\Resources\Kategoris\Pages;

use App\Filament\Admin\Resources\Kategoris\KategoriResource;
use App\Support\CustomInputDefaults;
use App\Support\KategoriFormDataHandler;
use App\Support\MediaAssetPicker;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditKategori extends EditRecord
{
    protected static string $resource = KategoriResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return [
            ...$data,
            ...app(CustomInputDefaults::class)->getFormState($this->getRecord()),
            'thumbnail_media_asset_id' => MediaAssetPicker::resolveCurrentMediaAssetId($this->getRecord(), 'thumbnail'),
            'banner_media_asset_id' => MediaAssetPicker::resolveCurrentMediaAssetId($this->getRecord(), 'banner'),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var \App\Models\Kategori $record */
        return app(KategoriFormDataHandler::class)->update($record, $data);
    }
}
