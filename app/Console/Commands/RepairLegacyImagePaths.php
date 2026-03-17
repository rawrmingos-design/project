<?php

namespace App\Console\Commands;

use App\Models\Kategori;
use App\Models\PaketLayanan;
use App\Models\Produk;
use Illuminate\Console\Command;

class RepairLegacyImagePaths extends Command
{
    protected $signature = 'media:repair-legacy-image-paths {--execute : Terapkan perubahan ke database}';

    protected $description = 'Perbaiki path legacy gambar yang masih menunjuk ke /assets/media/... ke folder asli public/assets.';

    public function handle(): int
    {
        $execute = (bool) $this->option('execute');

        $stats = [
            'kategori_thumbnail' => $this->repairModelColumn(Kategori::class, 'thumbnail', 'assets/thumbnail', $execute),
            'kategori_banner' => $this->repairModelColumn(Kategori::class, 'banner', 'assets/banner_game', $execute),
            'produk_logo' => $this->repairModelColumn(Produk::class, 'product_logo', 'assets/product_logo', $execute),
            'paket_layanan_logo' => $this->repairModelColumn(PaketLayanan::class, 'product_logo', 'assets/product_logo', $execute),
        ];

        foreach ($stats as $label => $row) {
            $this->line(sprintf(
                '%s => scanned: %d, repaired: %d, missing_target: %d',
                $label,
                $row['scanned'],
                $row['repaired'],
                $row['missing_target'],
            ));
        }

        if (! $execute) {
            $this->warn('Dry run only. Jalankan ulang dengan --execute untuk menyimpan perubahan.');
        }

        return self::SUCCESS;
    }

    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $modelClass
     * @return array{scanned:int,repaired:int,missing_target:int}
     */
    protected function repairModelColumn(string $modelClass, string $column, string $directory, bool $execute): array
    {
        $stats = [
            'scanned' => 0,
            'repaired' => 0,
            'missing_target' => 0,
        ];

        $modelClass::query()
            ->where($column, 'like', '/assets/media/%')
            ->select('id', $column)
            ->chunkById(200, function ($records) use (&$stats, $column, $directory, $execute): void {
                foreach ($records as $record) {
                    $stats['scanned']++;

                    $currentPath = (string) $record->{$column};
                    $filename = basename($currentPath);
                    $targetPath = '/' . trim($directory, '/') . '/' . $filename;

                    if (! is_file(public_path(ltrim($targetPath, '/')))) {
                        $stats['missing_target']++;
                        continue;
                    }

                    if ($execute) {
                        $record->forceFill([
                            $column => $targetPath,
                        ])->saveQuietly();
                    }

                    $stats['repaired']++;
                }
            });

        return $stats;
    }
}
