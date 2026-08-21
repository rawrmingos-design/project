<?php

namespace App\Services;

use App\Models\MediaAsset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaAssetDeletionService
{
    /**
     * Return legacy database references that point to this asset path.
     *
     * @return array<int, array{table:string,column:string,id:int,label:string}>
     */
    public function references(MediaAsset $asset): array
    {
        $path = ltrim((string) $asset->resolveRelativePath(), '/');
        if ($path === '') {
            return [];
        }

        $targets = [
            ['table' => 'kategoris', 'column' => 'thumbnail', 'label' => 'Kategori thumbnail', 'name' => 'nama'],
            ['table' => 'kategoris', 'column' => 'banner', 'label' => 'Kategori banner', 'name' => 'nama'],
            ['table' => 'layanans', 'column' => 'product_logo', 'label' => 'Produk logo', 'name' => 'layanan'],
            ['table' => 'paket_layanans', 'column' => 'product_logo', 'label' => 'Paket produk logo', 'name' => 'layanan_id'],
            ['table' => 'artikels', 'column' => 'thumbnail', 'label' => 'Artikel thumbnail', 'name' => 'title'],
            ['table' => 'beritas', 'column' => 'path', 'label' => 'Banner/berita', 'name' => 'judul'],
            ['table' => 'methods', 'column' => 'images', 'label' => 'Metode pembayaran', 'name' => 'name'],
            ['table' => 'setting_webs', 'column' => 'logo_favicon', 'label' => 'Logo favicon', 'name' => 'judul_web'],
            ['table' => 'setting_webs', 'column' => 'logo_header', 'label' => 'Logo header', 'name' => 'judul_web'],
            ['table' => 'setting_webs', 'column' => 'logo_footer', 'label' => 'Logo footer', 'name' => 'judul_web'],
        ];

        $references = [];
        foreach ($targets as $target) {
            if (! DB::getSchemaBuilder()->hasTable($target['table'])) {
                continue;
            }

            $rows = DB::table($target['table'])
                ->select('id', $target['name'])
                ->where($target['column'], ltrim($path, '/'))
                ->orWhere($target['column'], '/' . ltrim($path, '/'))
                ->get();

            foreach ($rows as $row) {
                $references[] = [
                    'table' => $target['table'],
                    'column' => $target['column'],
                    'id' => (int) $row->id,
                    'label' => $target['label'] . ': ' . (string) ($row->{$target['name']} ?? $row->id),
                ];
            }
        }

        return $references;
    }

    /**
     * Clear legacy references before deleting the physical asset.
     */
    public function clearReferences(MediaAsset $asset): int
    {
        $path = ltrim((string) $asset->resolveRelativePath(), '/');
        if ($path === '') {
            return 0;
        }

        $cleared = 0;
        foreach ($this->references($asset) as $reference) {
            $cleared += DB::table($reference['table'])
                ->where('id', $reference['id'])
                ->where(function ($query) use ($reference, $path): void {
                    $query->where($reference['column'], $path)
                        ->orWhere($reference['column'], '/' . $path);
                })
                ->update([$reference['column'] => null]);
        }

        return $cleared;
    }

    /**
     * Delete a MediaAsset record and its physical public file.
     */
    public function delete(MediaAsset $asset): array
    {
        $absolutePath = $asset->resolveAbsolutePath();
        $relativePath = $asset->resolveRelativePath();
        $references = $this->references($asset);

        $result = [
            'asset_deleted' => false,
            'references_found' => $references,
            'references_cleared' => 0,
            'file_deleted' => false,
            'file_skipped' => false,
            'file_path' => $relativePath,
            'media_deleted' => 0,
            'variants_deleted' => [],
            'variants_skipped' => [],
        ];

        $result['references_cleared'] = $this->clearReferences($asset);

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
