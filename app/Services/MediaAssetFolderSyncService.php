<?php

namespace App\Services;

use App\Models\MediaAsset;
use Illuminate\Support\Facades\File;

class MediaAssetFolderSyncService
{
    /**
     * md5 isi file => path baris yang sudah memilikinya.
     *
     * Dipakai sebagai penjaga agar satu isi file tidak di-index berkali-kali
     * hanya karena ada di lokasi berbeda.
     *
     * @var array<string, string>
     */
    private array $knownContent = [];

    public function sync(?callable $logger = null): array
    {
        $created = 0;
        $skipped = 0;
        $skippedDuplicateContent = 0;

        $this->knownContent = $this->indexedContentSignatures();

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

                $signature = $this->contentSignature($file->getPathname());

                // Isi file yang sama sudah punya baris di lokasi lain: JANGAN
                // bikin baris kedua. Kunci firstOrCreate(['path' => ...])
                // hanya unik per LOKASI, sehingga salinan isi yang identik di
                // folder lain lolos dan File Manager menampilkan gambar yang
                // sama berkali-kali. Penjaga ini menutup celah itu.
                if ($signature !== null && isset($this->knownContent[$signature]) && $this->knownContent[$signature] !== $relativePath) {
                    $skippedDuplicateContent++;

                    if ($logger) {
                        $logger("Lewatkan {$relativePath}: isi file identik dengan {$this->knownContent[$signature]}.");
                    }

                    continue;
                }

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

                if ($signature !== null) {
                    $this->knownContent[$signature] ??= $relativePath;
                }
            }
        }

        return [
            'created' => $created,
            'skipped' => $skipped,
            'skipped_duplicate_content' => $skippedDuplicateContent,
        ];
    }

    /**
     * Signature isi file. Null = tidak bisa dibaca.
     *
     * Sengaja "fail-open": kalau file tidak terbaca, pemanggil tetap
     * meng-index-nya seperti perilaku lama. Menelan file karena gagal hash
     * jauh lebih berbahaya daripada melewatkan satu dedupe.
     */
    private function contentSignature(string $absolutePath): ?string
    {
        if (! is_file($absolutePath) || ! is_readable($absolutePath)) {
            return null;
        }

        $signature = @md5_file($absolutePath);

        return $signature === false ? null : $signature;
    }

    /**
     * md5 semua isi file yang sudah punya baris media_assets.
     *
     * @return array<string, string>
     */
    private function indexedContentSignatures(): array
    {
        $signatures = [];

        MediaAsset::query()
            ->select('id', 'path')
            ->orderBy('id')
            ->chunkById(500, function ($assets) use (&$signatures): void {
                foreach ($assets as $asset) {
                    $relative = ltrim((string) $asset->path, '/');

                    if ($relative === '') {
                        continue;
                    }

                    $signature = $this->contentSignature(public_path($relative));

                    if ($signature === null) {
                        continue;
                    }

                    $signatures[$signature] ??= '/' . $relative;
                }
            });

        return $signatures;
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
            // Buang entri yang bersarang di dalam prefix (mis.
            // 'assets/media/265' sudah tercakup oleh 'assets/media').
            // Kalau dibiarkan, file yang sama diproses dua kali dan label
            // folder-nya bisa tertimpa.
            $prefix = $spatiePrefix . '/';
            $directories = array_filter(
                $directories,
                static fn (string $path): bool => ! str_starts_with($path . '/', $prefix)
            );

            $directories[$spatiePrefix] = 'produk';
        }

        return $directories;
    }
}
