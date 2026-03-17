<?php

namespace App\Support;

use App\Models\Kategori;
use App\Services\MediaAssetAssignmentService;

class KategoriFormDataHandler
{
    public function create(array $data): Kategori
    {
        [$kategoriData, $selectedMediaAssets, $customInputState] = $this->prepare($data);
        $kategoriData = $this->seedLegacyMediaColumns($kategoriData, $selectedMediaAssets, true);

        $record = Kategori::query()->create($kategoriData);

        $this->applySelectedMediaAssets($record, $selectedMediaAssets);
        $this->syncCustomInputs($record, $customInputState);

        return $record;
    }

    public function update(Kategori $record, array $data): Kategori
    {
        [$kategoriData, $selectedMediaAssets, $customInputState] = $this->prepare($data);
        $kategoriData = $this->seedLegacyMediaColumns($kategoriData, $selectedMediaAssets, false);

        $record->fill($kategoriData);
        $record->save();

        $this->applySelectedMediaAssets($record, $selectedMediaAssets);
        $this->syncCustomInputs($record, $customInputState);

        return $record;
    }

    public function stripFormOnlyFields(array $data): array
    {
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

    public function extractCustomInputState(array $data): array
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

    public function extractSelectedMediaAssets(array $data): array
    {
        return [
            'thumbnail' => ($data['thumbnail_input_mode'] ?? 'upload') === 'library'
                ? ($data['thumbnail_media_asset_id'] ?? null)
                : null,
            'banner' => ($data['banner_input_mode'] ?? 'upload') === 'library'
                ? ($data['banner_media_asset_id'] ?? null)
                : null,
        ];
    }

    private function prepare(array $data): array
    {
        return [
            $this->stripFormOnlyFields($data),
            $this->extractSelectedMediaAssets($data),
            $this->extractCustomInputState($data),
        ];
    }

    private function applySelectedMediaAssets(Kategori $record, array $selectedMediaAssets): void
    {
        $mediaAssetAssignment = app(MediaAssetAssignmentService::class);

        foreach ($selectedMediaAssets as $collection => $assetId) {
            $mediaAssetAssignment->assignToRecord($record, $assetId ? (int) $assetId : null, $collection);
        }
    }

    private function syncCustomInputs(Kategori $record, array $customInputState): void
    {
        app(CustomInputDefaults::class)->syncFromFormState($record, $customInputState);
    }

    private function seedLegacyMediaColumns(array $kategoriData, array $selectedMediaAssets, bool $isCreate): array
    {
        $mediaAssetAssignment = app(MediaAssetAssignmentService::class);

        foreach (['thumbnail', 'banner'] as $collection) {
            $assetId = $selectedMediaAssets[$collection] ?? null;

            if (! $assetId) {
                continue;
            }

            $relativePath = $mediaAssetAssignment->getRelativePathFromAsset((int) $assetId);

            if ($relativePath) {
                $kategoriData[$collection] = ltrim($relativePath, '/');
            }
        }

        if ($isCreate && blank($kategoriData['thumbnail'] ?? null)) {
            $kategoriData['thumbnail'] = '';
        }

        return $kategoriData;
    }
}
