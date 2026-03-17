<?php

namespace App\Console\Commands;

use App\Models\Artikel;
use App\Models\Berita;
use App\Models\Kategori;
use App\Models\Method;
use App\Models\PaketLayanan;
use App\Models\Produk;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class RepairMissingImageRecords extends Command
{
    protected $signature = 'media:repair-missing-image-records {--execute : Terapkan perubahan ke database}';

    protected $description = 'Fallback logo pivot yang hilang, nullify path gambar yang benar-benar missing, dan generate ulang report.';

    public function handle(): int
    {
        $execute = (bool) $this->option('execute');

        $pivotStats = $this->repairMissingPivotProductLogos($execute);
        $nullifyStats = $this->nullifyMissingRecordPaths($execute);
        $report = $this->buildMissingReport();

        $this->info(sprintf(
            'Pivot fallback => scanned: %d, repaired_from_layanan: %d, repaired_from_kategori: %d, nullified: %d',
            $pivotStats['scanned'],
            $pivotStats['from_layanan'],
            $pivotStats['from_kategori'],
            $pivotStats['nullified'],
        ));

        foreach ($nullifyStats as $label => $stats) {
            $this->line(sprintf(
                '%s => scanned: %d, replaced: %d',
                $label,
                $stats['scanned'],
                $stats['replaced'],
            ));
        }

        $this->line('Sisa missing setelah repair:');
        foreach ($report['summary'] as $label => $count) {
            $this->line(" - {$label}: {$count}");
        }

        file_put_contents(
            storage_path('app/media-missing-report.json'),
            json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        );

        $this->info('Report diperbarui: storage/app/media-missing-report.json');

        if (! $execute) {
            $this->warn('Dry run only. Jalankan ulang dengan --execute untuk menyimpan perubahan.');
        }

        return self::SUCCESS;
    }

    /**
     * @return array{scanned:int,from_layanan:int,from_kategori:int,nullified:int}
     */
    protected function repairMissingPivotProductLogos(bool $execute): array
    {
        $stats = [
            'scanned' => 0,
            'from_layanan' => 0,
            'from_kategori' => 0,
            'nullified' => 0,
        ];

        PaketLayanan::query()
            ->whereNotNull('product_logo')
            ->where('product_logo', '!=', '')
            ->chunkById(200, function ($records) use (&$stats, $execute): void {
                foreach ($records as $record) {
                    if (! $this->isMissingPath($record->product_logo)) {
                        continue;
                    }

                    $stats['scanned']++;

                    $layanan = Produk::query()
                        ->select('id', 'kategori_id', 'product_logo')
                        ->find($record->layanan_id);

                    $fallbackPath = null;
                    $source = null;

                    if ($layanan && $this->isValidPath($layanan->product_logo)) {
                        $fallbackPath = $layanan->product_logo;
                        $source = 'from_layanan';
                    } elseif ($layanan && filled($layanan->kategori_id)) {
                        $kategori = Kategori::query()
                            ->select('id', 'thumbnail')
                            ->find($layanan->kategori_id);

                        if ($kategori && $this->isValidPath($kategori->thumbnail)) {
                            $fallbackPath = $kategori->thumbnail;
                            $source = 'from_kategori';
                        }
                    }

                    if ($execute) {
                        $record->forceFill([
                            'product_logo' => $fallbackPath,
                        ])->saveQuietly();
                    }

                    if ($source) {
                        $stats[$source]++;
                    } else {
                        $stats['nullified']++;
                    }
                }
            });

        return $stats;
    }

    /**
     * @return array<string, array{scanned:int,replaced:int}>
     */
    protected function nullifyMissingRecordPaths(bool $execute): array
    {
        $targets = [
            'Kategori.thumbnail' => [Kategori::class, 'thumbnail', fn (Kategori $record): ?string => $this->resolveKategoriImageFallback($record, 'thumbnail')],
            'Kategori.banner' => [Kategori::class, 'banner', fn (Kategori $record): ?string => $this->resolveKategoriImageFallback($record, 'banner')],
            'Produk.product_logo' => [Produk::class, 'product_logo', fn (): ?string => null],
            'Berita.path' => [Berita::class, 'path', fn (): ?string => null],
            'Artikel.thumbnail' => [Artikel::class, 'thumbnail', fn (): ?string => null],
            'Method.images' => [Method::class, 'images', fn (): ?string => null],
        ];

        $stats = [];

        foreach ($targets as $label => [$modelClass, $column, $replacementResolver]) {
            $stats[$label] = [
                'scanned' => 0,
                'replaced' => 0,
            ];

            /** @var class-string<Model> $modelClass */
            $modelClass::query()
                ->whereNotNull($column)
                ->where($column, '!=', '')
                ->chunkById(200, function ($records) use (&$stats, $label, $column, $execute, $replacementResolver): void {
                    foreach ($records as $record) {
                        if (! $this->isMissingPath($record->{$column})) {
                            continue;
                        }

                        $stats[$label]['scanned']++;
                        $replacement = $replacementResolver($record);

                        if ($execute) {
                            $record->forceFill([
                                $column => $replacement,
                            ])->saveQuietly();
                        }

                        $stats[$label]['replaced']++;
                    }
                });
        }

        return $stats;
    }

    /**
     * @return array{generated_at:string,summary:array<string,int>,details:array<string,array<int,array<string,mixed>>>}
     */
    protected function buildMissingReport(): array
    {
        $targets = [
            ['label' => 'Kategori.thumbnail', 'table' => 'kategoris', 'id' => 'id', 'name' => 'nama', 'column' => 'thumbnail'],
            ['label' => 'Kategori.banner', 'table' => 'kategoris', 'id' => 'id', 'name' => 'nama', 'column' => 'banner'],
            ['label' => 'Produk.product_logo', 'table' => 'layanans', 'id' => 'id', 'name' => 'layanan', 'column' => 'product_logo'],
            ['label' => 'PaketLayanan.product_logo', 'table' => 'paket_layanans', 'id' => 'id', 'name' => 'layanan_id', 'column' => 'product_logo'],
            ['label' => 'Berita.path', 'table' => 'beritas', 'id' => 'id', 'name' => 'tipe', 'column' => 'path'],
            ['label' => 'Artikel.thumbnail', 'table' => 'artikels', 'id' => 'id', 'name' => 'title', 'column' => 'thumbnail'],
            ['label' => 'Method.images', 'table' => 'methods', 'id' => 'id', 'name' => 'name', 'column' => 'images'],
            ['label' => 'Setting.logo_favicon', 'table' => 'setting_webs', 'id' => 'id', 'name' => 'judul_web', 'column' => 'logo_favicon'],
            ['label' => 'Setting.logo_header', 'table' => 'setting_webs', 'id' => 'id', 'name' => 'judul_web', 'column' => 'logo_header'],
            ['label' => 'Setting.logo_footer', 'table' => 'setting_webs', 'id' => 'id', 'name' => 'judul_web', 'column' => 'logo_footer'],
        ];

        $result = [
            'generated_at' => now()->toIso8601String(),
            'summary' => [],
            'details' => [],
        ];

        foreach ($targets as $target) {
            $rows = DB::table($target['table'])
                ->select([$target['id'], $target['name'], $target['column']])
                ->whereNotNull($target['column'])
                ->where($target['column'], '!=', '')
                ->get();

            $missing = [];

            foreach ($rows as $row) {
                $path = (string) $row->{$target['column']};

                if (! $this->isMissingPath($path)) {
                    continue;
                }

                $missing[] = [
                    'id' => $row->{$target['id']},
                    'name' => $row->{$target['name']},
                    'column' => $target['column'],
                    'path' => $path,
                    'absolute_path' => public_path($this->normalizePath($path)),
                ];
            }

            $result['summary'][$target['label']] = count($missing);
            $result['details'][$target['label']] = $missing;
        }

        return $result;
    }

    protected function isMissingPath(?string $path): bool
    {
        return filled($path) && ! $this->isValidPath($path);
    }

    protected function isValidPath(?string $path): bool
    {
        if (! filled($path)) {
            return false;
        }

        return is_file(public_path($this->normalizePath($path)));
    }

    protected function normalizePath(string $path): string
    {
        return ltrim(parse_url($path, PHP_URL_PATH) ?? $path, '/');
    }

    protected function resolveKategoriImageFallback(Kategori $record, string $column): ?string
    {
        $alternateColumn = $column === 'thumbnail' ? 'banner' : 'thumbnail';
        $alternatePath = $record->{$alternateColumn} ?? null;

        if ($this->isValidPath($alternatePath)) {
            return $alternatePath;
        }

        $defaultPath = '/assets/logo/favicon.webp';

        return $this->isValidPath($defaultPath) ? $defaultPath : $record->{$column};
    }
}
