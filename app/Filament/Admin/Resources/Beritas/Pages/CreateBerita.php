<?php

namespace App\Filament\Admin\Resources\Beritas\Pages;

use App\Filament\Admin\Resources\Beritas\BeritaResource;
use App\Services\MediaAssetAssignmentService;
use Filament\Resources\Pages\CreateRecord;

class CreateBerita extends CreateRecord
{
    protected static string $resource = BeritaResource::class;

    protected ?int $selectedMediaAssetId = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->selectedMediaAssetId = ($data['path_input_mode'] ?? 'upload') === 'library' && isset($data['path_media_asset_id'])
            ? (int) $data['path_media_asset_id']
            : null;

        unset($data['path_media_asset_id'], $data['path_input_mode']);

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
            'path' => $path,
        ])->saveQuietly();
    }
}
