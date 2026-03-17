<?php

namespace App\Filament\Admin\Resources\Artikels\Pages;

use App\Filament\Admin\Resources\Artikels\ArtikelResource;
use App\Services\MediaAssetAssignmentService;
use Filament\Resources\Pages\CreateRecord;

class CreateArtikel extends CreateRecord
{
    protected static string $resource = ArtikelResource::class;

    protected ?int $selectedMediaAssetId = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->selectedMediaAssetId = ($data['thumbnail_input_mode'] ?? 'upload') === 'library' && isset($data['thumbnail_media_asset_id'])
            ? (int) $data['thumbnail_media_asset_id']
            : null;

        unset($data['thumbnail_media_asset_id'], $data['thumbnail_input_mode']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->applySelectedMediaAsset();
    }

    private function applySelectedMediaAsset(): void
    {
        if (! $this->selectedMediaAssetId) {
            return;
        }

        $record = $this->getRecord();

        if (! $record) {
            return;
        }

        $path = app(MediaAssetAssignmentService::class)->getRelativePathFromAsset($this->selectedMediaAssetId);

        if (! $path) {
            return;
        }

        $record->forceFill([
            'thumbnail' => $path,
        ])->saveQuietly();
    }
}
