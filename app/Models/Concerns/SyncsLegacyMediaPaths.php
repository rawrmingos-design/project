<?php

namespace App\Models\Concerns;

use Spatie\MediaLibrary\MediaCollections\Models\Media;

trait SyncsLegacyMediaPaths
{
    abstract public function getLegacyMediaColumnMap(): array;

    public function getLegacyMediaDirectoryMap(): array
    {
        return [];
    }

    public function syncLegacyMediaColumns(): void
    {
        foreach ($this->getLegacyMediaColumnMap() as $collection => $column) {
            $this->syncLegacyMediaColumn($collection, $column);
        }
    }

    public function syncLegacyMediaColumn(string $collection, ?string $column = null): void
    {
        $column ??= $this->getLegacyMediaColumnMap()[$collection] ?? null;

        if (! $column) {
            return;
        }

        /** @var ?Media $media */
        $media = $this->getFirstMedia($collection);

        $legacyPath = $this->resolvePreferredLegacyPath($collection, $media);

        $this->forceFill([
            $column => $legacyPath,
        ])->saveQuietly();

        if ($legacyPath) {
            $optimizer = app(\App\Services\OptimizedImageService::class);

            $optimizer->ensureVariants(
                $legacyPath,
                $optimizer->profileForCollection($collection),
            );
        }
    }

    protected function resolvePreferredLegacyPath(string $collection, ?Media $media): ?string
    {
        if (! $media) {
            return null;
        }

        $directory = $this->getLegacyMediaDirectoryMap()[$collection] ?? null;

        if ($directory) {
            $candidatePath = '/' . trim($directory, '/') . '/' . ltrim($media->file_name, '/');

            if (is_file(public_path(ltrim($candidatePath, '/')))) {
                return $candidatePath;
            }
        }

        $mediaPath = '/' . ltrim($media->getPathRelativeToRoot(), '/');

        return is_file(public_path(ltrim($mediaPath, '/'))) ? $mediaPath : null;
    }
}
