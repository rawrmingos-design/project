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
            // Skema beritas beda antar-deployment (istanatopup punya judul,
            // egymarket tidak). Fallback ke tipe kalau judul tidak ada.
            ['table' => 'beritas', 'column' => 'path', 'label' => 'Banner/berita', 'name' => 'judul', 'fallback_name' => 'tipe'],
            ['table' => 'methods', 'column' => 'images', 'label' => 'Metode pembayaran', 'name' => 'name'],
            ['table' => 'setting_webs', 'column' => 'logo_favicon', 'label' => 'Logo favicon', 'name' => 'judul_web'],
            ['table' => 'setting_webs', 'column' => 'logo_header', 'label' => 'Logo header', 'name' => 'judul_web'],
            ['table' => 'setting_webs', 'column' => 'logo_footer', 'label' => 'Logo footer', 'name' => 'judul_web'],
        ];

        $references = [];
        foreach ($targets as $target) {
            $schema = DB::getSchemaBuilder();
            if (! $schema->hasTable($target['table']) || ! $schema->hasColumn($target['table'], $target['column'])) {
                continue;
            }

            $nameColumn = $schema->hasColumn($target['table'], $target['name'])
                ? $target['name']
                : ($target['fallback_name'] ?? null);

            if ($nameColumn === null || ! $schema->hasColumn($target['table'], $nameColumn)) {
                $rows = DB::table($target['table'])->select('id');
            } else {
                $rows = DB::table($target['table'])->select('id', $nameColumn);
            }

            $rows = $rows
                ->where($target['column'], ltrim($path, '/'))
                ->orWhere($target['column'], '/' . ltrim($path, '/'))
                ->get();

            foreach ($rows as $row) {
                $references[] = [
                    'table' => $target['table'],
                    'column' => $target['column'],
                    'id' => (int) $row->id,
                    'label' => $target['label'] . ': ' . (string) ($row->{$nameColumn} ?? $row->id),
                ];
            }
        }

        return $references;
    }

    /**
     * Clear legacy references before deleting the physical asset.
     *
     * $references opsional: snapshot hasil references() yang sama
     * dipakai ulang supaya tidak ada jeda baca kedua (TOCTOU) antara
     * daftar referensi dan proses pengosongannya. Update tetap
     * di-guard where(column = path) sehingga snapshot basi tidak
     * pernah mengosongkan kolom yang sudah tidak menunjuk asset ini.
     */
    public function clearReferences(MediaAsset $asset, ?array $references = null): int
    {
        $path = ltrim((string) $asset->resolveRelativePath(), '/');
        if ($path === '') {
            return 0;
        }

        $cleared = 0;
        $schema = DB::getSchemaBuilder();
        foreach ($references ?? $this->references($asset) as $reference) {
            // Legacy deployments differ: some image columns are NOT NULL
            // (e.g. egymarket.kategoris.thumbnail and methods.images).
            // Empty string is the safe cleared value for those schemas;
            // nullable columns keep using NULL.
            $column = collect($schema->getColumns($reference['table']))
                ->firstWhere('name', $reference['column']);
            $clearValue = ($column['nullable'] ?? true) ? null : '';

            $cleared += DB::table($reference['table'])
                ->where('id', $reference['id'])
                ->where(function ($query) use ($reference, $path): void {
                    $query->where($reference['column'], $path)
                        ->orWhere($reference['column'], '/' . $path);
                })
                ->update([$reference['column'] => $clearValue]);
        }

        return $cleared;
    }

    /**
     * Delete a MediaAsset record and its physical public file.
     *
     * Referensi legacy dihitung SEKALI lalu dipakai ulang, dan
     * pengosongan referensi + penghapusan record dibungkus transaksi
     * supaya konsisten (file fisik tetap dihapus setelah commit).
     */
    public function delete(MediaAsset $asset): array
    {
        $absolutePath = $asset->resolveAbsolutePath();
        $relativePath = $asset->resolveRelativePath();

        // Satu snapshot referensi untuk semua langkah — tidak ada
        // pembacaan kedua yang bisa melihat data berubah (TOCTOU).
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

        DB::transaction(function () use ($asset, $references, &$result): void {
            $result['references_cleared'] = $this->clearReferences($asset, $references);

            foreach ($asset->media as $media) {
                $media->delete();
                $result['media_deleted']++;
            }

            $asset->delete();
        });
        $result['asset_deleted'] = true;

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
