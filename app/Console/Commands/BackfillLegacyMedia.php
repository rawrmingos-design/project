<?php

namespace App\Console\Commands;

use App\Models\Kategori;
use App\Models\MediaAsset;
use App\Models\PaketLayanan;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

class BackfillLegacyMedia extends Command
{
    protected $signature = 'media:backfill-legacy {--model=* : Batasi ke model tertentu: kategori, produk}';

    protected $description = 'Salin file lama dari kolom path legacy ke Spatie Media Library tanpa menghapus file asli.';

    public function handle(): int
    {
        $selectedModels = collect($this->option('model'))
            ->map(fn (string $value) => strtolower(trim($value)))
            ->filter()
            ->values();

        $targets = collect([
            'kategori' => [
                'model' => Kategori::class,
                'columns' => [
                    'thumbnail' => 'thumbnail',
                    'banner' => 'banner',
                ],
            ],
            'produk' => [
                'model' => PaketLayanan::class,
                'columns' => [
                    'product_logo' => 'product_logo',
                ],
            ],
        ]);

        if ($selectedModels->isNotEmpty()) {
            $targets = $targets->only($selectedModels->all());
        }

        if ($targets->isEmpty()) {
            $this->error('Tidak ada model yang cocok. Gunakan: kategori atau produk.');

            return self::FAILURE;
        }

        foreach ($targets as $label => $config) {
            $this->backfillModel($label, $config['model'], $config['columns']);
        }

        $this->newLine();
        $this->info('Backfill legacy media selesai.');

        return self::SUCCESS;
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @param  array<string, string>  $columns
     */
    private function backfillModel(string $label, string $modelClass, array $columns): void
    {
        $this->newLine();
        $this->info("Memproses {$label}...");

        $totalImported = 0;

        $modelClass::query()->orderBy('id')->chunkById(100, function ($records) use (&$totalImported, $columns): void {
            foreach ($records as $record) {
                foreach ($columns as $collection => $column) {
                    $legacyPath = $record->{$column};

                    if (blank($legacyPath)) {
                        continue;
                    }

                    $this->syncMediaAssetReference($legacyPath, $collection, $column);

                    if ($record->getFirstMedia($collection)) {
                        continue;
                    }

                    $fullPath = public_path(ltrim($legacyPath, '/'));

                    if (! is_file($fullPath)) {
                        $recordClass = get_class($record);
                        $this->warn("Lewatkan {$recordClass}#{$record->getKey()} {$column}: file tidak ditemukan di {$legacyPath}");
                        continue;
                    }

                    $record
                        ->addMedia($fullPath)
                        ->preservingOriginal()
                        ->usingName(pathinfo($fullPath, PATHINFO_FILENAME))
                        ->toMediaCollection($collection, 'assets');

                    $totalImported++;
                }
            }
        });

        $this->line("Imported: {$totalImported} file.");
    }

    private function syncMediaAssetReference(string $legacyPath, string $collection, string $column): void
    {
        MediaAsset::firstOrCreate(
            ['path' => $legacyPath],
            [
                'name' => pathinfo($legacyPath, PATHINFO_FILENAME),
                'folder' => $this->inferFolder($collection, $column),
                'alt_text' => pathinfo($legacyPath, PATHINFO_FILENAME),
                'description' => 'Indexed from legacy path ' . $legacyPath,
            ]
        );
    }

    private function inferFolder(string $collection, string $column): string
    {
        $value = strtolower($collection . ' ' . $column);

        if (str_contains($value, 'thumb')) {
            return 'kategori';
        }

        if (str_contains($value, 'banner')) {
            return 'banner';
        }

        if (str_contains($value, 'logo') || str_contains($value, 'product')) {
            return 'produk';
        }

        return 'lainnya';
    }
}
