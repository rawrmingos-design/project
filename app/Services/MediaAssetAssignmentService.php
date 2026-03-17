<?php

namespace App\Services;

use App\Models\MediaAsset;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaAssetAssignmentService
{
    public function assignToRecord(Model $record, ?int $assetId, string $collection): bool
    {
        if (! $assetId || ! $record instanceof HasMedia) {
            return false;
        }

        /** @var ?MediaAsset $asset */
        $asset = MediaAsset::find($assetId);

        if (! $asset) {
            return false;
        }

        $sourceMedia = $asset->getFirstMedia('file');
        $sourcePath = $asset->resolveAbsolutePath();

        if (! $sourcePath || ! is_file($sourcePath)) {
            return false;
        }

        $record
            ->addMedia($sourcePath)
            ->preservingOriginal()
            ->usingName($asset->name ?: pathinfo($sourcePath, PATHINFO_FILENAME))
            ->usingFileName($sourceMedia?->file_name ?: basename($sourcePath))
            ->toMediaCollection($collection, 'assets');

        if (method_exists($record, 'getLegacyMediaColumnMap')) {
            $column = $record->getLegacyMediaColumnMap()[$collection] ?? null;
            $relativePath = $asset->resolveRelativePath();

            if ($column && $relativePath) {
                $record->forceFill([
                    $column => $relativePath,
                ])->saveQuietly();
            }
        }

        return true;
    }

    public function getRelativePathFromAsset(?int $assetId): ?string
    {
        if (! $assetId) {
            return null;
        }

        /** @var ?MediaAsset $asset */
        $asset = MediaAsset::find($assetId);

        if (! $asset) {
            return null;
        }

        /** @var ?Media $media */
        $media = $asset->getFirstMedia('file');

        if ($media) {
            return '/' . ltrim($media->getPathRelativeToRoot(), '/');
        }

        return $asset->path ?: null;
    }
}
