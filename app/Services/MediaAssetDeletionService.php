<?php

namespace App\Services;

use App\Models\MediaAsset;
use Illuminate\Support\Facades\File;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaAssetDeletionService
{
    /**
     * Delete a MediaAsset record and, when safe, its physical public file.
     */
    public function delete(MediaAsset $asset): array
    {
        $absolutePath = $asset->resolveAbsolutePath();
        $relativePath = $asset->resolveRelativePath();

        $result = [
            'asset_deleted' => false,
            'file_deleted' => false,
            'file_skipped' => false,
            'file_path' => $relativePath,
            'media_deleted' => 0,
            'variants_deleted' => [],
            'variants_skipped' => [],
        ];

        if ($absolutePath && $this->isDeletablePublicFile($absolutePath, $asset)) {
            $variantResult = app(OptimizedImageService::class)->deleteVariants($relativePath);
            $result['variants_deleted'] = $variantResult['deleted'] ?? [];
            $result['variants_skipped'] = $variantResult['skipped'] ?? [];

            if (File::delete($absolutePath)) {
                $result['file_deleted'] = true;
            } else {
                $result['file_skipped'] = true;
            }
        } elseif ($absolutePath) {
            $result['file_skipped'] = true;
        }

        foreach ($asset->media as $media) {
            $media->delete();
            $result['media_deleted']++;
        }

        $asset->delete();
        $result['asset_deleted'] = true;

        return $result;
    }

    private function isDeletablePublicFile(string $absolutePath, MediaAsset $asset): bool
    {
        if (! is_file($absolutePath)) {
            return false;
        }

        return $this->isInsideAnyDirectory($absolutePath, $this->managedDirectories())
            || $this->isOwnSpatieMediaFile($absolutePath, $asset);
    }

    private function isOwnSpatieMediaFile(string $absolutePath, MediaAsset $asset): bool
    {
        return $asset->media->contains(function (Media $media) use ($absolutePath): bool {
            return $this->sameFile($absolutePath, $media->getPath());
        });
    }

    private function managedDirectories(): array
    {
        return [
            public_path('assets/product_logo'),
            public_path('assets/thumbnail'),
            public_path('assets/banner_game'),
            public_path('assets/banner'),
            public_path('assets/logo'),
            public_path('assets/seasonal'),
            public_path('articles/thumbnails'),
        ];
    }

    private function isInsideAnyDirectory(string $path, array $directories): bool
    {
        $realPath = realpath($path);

        if (! $realPath) {
            return false;
        }

        foreach ($directories as $directory) {
            $realDirectory = realpath($directory);

            if (! $realDirectory) {
                continue;
            }

            if (str_starts_with($this->normalizePath($realPath), $this->normalizePath($realDirectory) . DIRECTORY_SEPARATOR)) {
                return true;
            }
        }

        return false;
    }

    private function sameFile(string $left, string $right): bool
    {
        $left = realpath($left);
        $right = realpath($right);

        return $left && $right && $this->normalizePath($left) === $this->normalizePath($right);
    }

    private function normalizePath(string $path): string
    {
        return rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
    }
}
