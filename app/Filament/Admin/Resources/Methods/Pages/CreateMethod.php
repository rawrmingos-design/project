<?php

namespace App\Filament\Admin\Resources\Methods\Pages;

use App\Filament\Admin\Resources\Methods\MethodResource;
use App\Services\MediaAssetAssignmentService;
use App\Services\OptimizedImageService;
use Filament\Resources\Pages\CreateRecord;

class CreateMethod extends CreateRecord
{
    protected static string $resource = MethodResource::class;

    protected ?int $selectedMediaAssetId = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->selectedMediaAssetId = ($data['images_input_mode'] ?? 'upload') === 'library' && isset($data['images_media_asset_id'])
            ? (int) $data['images_media_asset_id']
            : null;

        unset($data['images_media_asset_id'], $data['images_input_mode']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->applySelectedMediaAsset();
        $this->optimizeRecordImage();
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
            'images' => $path,
        ])->saveQuietly();
    }

    private function optimizeRecordImage(): void
    {
        $record = $this->getRecord();

        if (! $record || ! $record->images) {
            return;
        }

        app(OptimizedImageService::class)->ensureVariants($record->images, 'payment_logo');
    }
}
