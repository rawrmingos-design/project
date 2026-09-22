<?php

namespace App\Services;

use App\Models\MediaAsset;
use Illuminate\Support\Facades\File;

class MediaAssetFolderSyncService
{
    public function sync(?callable $logger = null): array
    {
        $created = 0;
        $skipped = 0;

        foreach ($this->directories() as $directory => $folder) {
            $absoluteDirectory = public_path($directory);

            if (! File::isDirectory($absoluteDirectory)) {
                if ($logger) {
                    $logger("Lewatkan {$directory}: folder tidak ditemukan.");
                }

                continue;
            }

            foreach (File::allFiles($absoluteDirectory) as $file) {
                $relativePath = '/' . str_replace('\\', '/', ltrim(str_replace(public_path(), '', $file->getPathname()), '\\/'));

                $asset = MediaAsset::firstOrCreate(
                    ['path' => $relativePath],
                    [
                        'name' => pathinfo($file->getFilename(), PATHINFO_FILENAME),
                        'folder' => $folder,
                        'alt_text' => pathinfo($file->getFilename(), PATHINFO_FILENAME),
                        'description' => 'Indexed from folder ' . $directory,
                    ]
                );

                if ($asset->wasRecentlyCreated) {
                    $created++;
                } else {
                    $skipped++;
                }
            }
        }

        return [
            'created' => $created,
            'skipped' => $skipped,
        ];
    }

    private function directories(): array
    {
        $directories = [
            'assets/product_logo' => 'produk',
            'assets/thumbnail' => 'kategori',
            'assets/banner_game' => 'banner',
            'assets/banner' => 'banner',
            'assets/logo' => 'logo',
            'assets/seasonal' => 'seasonal',
            'articles/thumbnails' => 'artikel',
        ];

        // Unggahan dari form produk/kategori disimpan Spatie Media Library
        // di prefix ini (config media-library.prefix). Kalau tidak
        // di-index, file-nya tidak muncul di File Manager sehingga admin
        // TIDAK BISA menghapusnya dari sana — persis keluhan "sudah
        // dihapus tapi masih tampil" (sebenarnya belum pernah terhapus).
        $spatiePrefix = trim((string) config('media-library.prefix', 'assets/media'), '/');

        if ($spatiePrefix !== '' && ! array_key_exists($spatiePrefix, $directories)) {
            $directories[$spatiePrefix] = 'produk';
        }

        return $directories;
    }
}
