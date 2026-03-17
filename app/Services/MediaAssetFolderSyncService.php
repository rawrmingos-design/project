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
        return [
            'assets/product_logo' => 'produk',
            'assets/thumbnail' => 'kategori',
            'assets/banner_game' => 'banner',
            'assets/banner' => 'banner',
            'assets/logo' => 'logo',
            'articles/thumbnails' => 'artikel',
        ];
    }
}
