<?php

namespace App\Filament\Admin\Resources\Kategoris\Pages;

use App\Filament\Admin\Resources\Kategoris\KategoriResource;
use App\Services\MediaAssetAssignmentService;
use App\Support\CustomInputDefaults;
use Filament\Resources\Pages\CreateRecord;

class CreateKategori extends CreateRecord
{
    protected static string $resource = KategoriResource::class;

    protected array $selectedMediaAssets = [];

    protected array $customInputState = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->selectedMediaAssets = [
            'thumbnail' => ($data['thumbnail_input_mode'] ?? 'upload') === 'library'
                ? ($data['thumbnail_media_asset_id'] ?? null)
                : null,
            'banner' => ($data['banner_input_mode'] ?? 'upload') === 'library'
                ? ($data['banner_media_asset_id'] ?? null)
                : null,
        ];

        $this->customInputState = $this->extractCustomInputState($data);

        unset(
            $data['thumbnail_media_asset_id'],
            $data['banner_media_asset_id'],
            $data['thumbnail_input_mode'],
            $data['banner_input_mode'],
            $data['field_1_title'],
            $data['field_1_placeholder'],
            $data['field_1_type'],
            $data['has_field_2'],
            $data['field_2_title'],
            $data['field_2_placeholder'],
            $data['field_2_type'],
            $data['field_select_title_input'],
            $data['field_select_value_input'],
        );

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->applySelectedMediaAssets();
        $this->syncCustomInputs();
    }

    private function applySelectedMediaAssets(): void
    {
        $record = $this->getRecord();

        if (! $record) {
            return;
        }

        $mediaAssetAssignment = app(MediaAssetAssignmentService::class);

        foreach ($this->selectedMediaAssets as $collection => $assetId) {
            $mediaAssetAssignment->assignToRecord($record, $assetId ? (int) $assetId : null, $collection);
        }
    }

    private function syncCustomInputs(): void
    {
        $record = $this->getRecord();

        if (! $record) {
            return;
        }

        app(CustomInputDefaults::class)->syncFromFormState($record, $this->customInputState);
    }

    private function extractCustomInputState(array $data): array
    {
        return [
            'field_1_title' => $data['field_1_title'] ?? null,
            'field_1_placeholder' => $data['field_1_placeholder'] ?? null,
            'field_1_type' => $data['field_1_type'] ?? null,
            'has_field_2' => $data['has_field_2'] ?? null,
            'field_2_title' => $data['field_2_title'] ?? null,
            'field_2_placeholder' => $data['field_2_placeholder'] ?? null,
            'field_2_type' => $data['field_2_type'] ?? null,
            'field_select_title_input' => $data['field_select_title_input'] ?? null,
            'field_select_value_input' => $data['field_select_value_input'] ?? null,
        ];
    }
}
