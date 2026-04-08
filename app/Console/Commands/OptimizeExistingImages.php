<?php

namespace App\Console\Commands;

use App\Services\OptimizedImageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OptimizeExistingImages extends Command
{
    protected $signature = 'images:optimize-existing
        {--dry-run : List candidates without writing WebP variants}
        {--limit=0 : Stop after this many candidate paths}
        {--profile= : Force a single optimization profile for every image}';

    protected $description = 'Generate optimized WebP variants for legacy public image paths.';

    public function handle(OptimizedImageService $images): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $limit = max(0, (int) $this->option('limit'));
        $forcedProfile = trim((string) $this->option('profile')) ?: null;

        $stats = [
            'candidate' => 0,
            'generated' => 0,
            'existing' => 0,
            'skipped' => 0,
            'missing' => 0,
            'failed' => 0,
        ];

        foreach ($this->imagePaths() as $item) {
            if ($limit > 0 && $stats['candidate'] >= $limit) {
                break;
            }

            $path = $item['path'];
            $profile = $forcedProfile ?: $item['profile'];
            $stats['candidate']++;

            if ($dryRun) {
                $analysis = $images->analyze($path, $profile);
                $reason = $analysis['reason'] ?? 'ok';

                if (! ($analysis['optimizable'] ?? false)) {
                    $reason === 'missing' ? $stats['missing']++ : $stats['skipped']++;
                    $this->line("[dry-run] skip {$item['source']} {$path} ({$reason})");

                    continue;
                }

                $this->line("[dry-run] optimize {$item['source']} {$path} as {$profile}");

                continue;
            }

            $result = $images->ensureVariants($path, $profile);
            $status = $result['status'] ?? 'skipped';

            if ($status === 'generated') {
                $stats['generated']++;
            } elseif ($status === 'exists') {
                $stats['existing']++;
            } elseif ($status === 'failed') {
                $stats['failed']++;
            } else {
                ($result['reason'] ?? null) === 'missing' ? $stats['missing']++ : $stats['skipped']++;
            }

            $this->line("{$status} {$item['source']} {$path}");
        }

        $this->newLine();
        $this->info(sprintf(
            'Image optimization summary: %d candidates, %d generated, %d existing, %d skipped, %d missing, %d failed.',
            $stats['candidate'],
            $stats['generated'],
            $stats['existing'],
            $stats['skipped'],
            $stats['missing'],
            $stats['failed'],
        ));

        return $stats['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function imagePaths(): \Generator
    {
        yield from $this->tableColumnPaths('kategoris', 'thumbnail', 'thumbnail');
        yield from $this->tableColumnPaths('kategoris', 'banner', 'banner');
        yield from $this->beritaPaths();
        yield from $this->tableColumnPaths('artikels', 'thumbnail', 'article');
        yield from $this->tableColumnPaths('layanans', 'product_logo', 'product_logo');
        yield from $this->tableColumnPaths('paket_layanans', 'product_logo', 'product_logo');
        yield from $this->tableColumnPaths('methods', 'images', 'payment_logo');
        yield from $this->tableColumnPaths('setting_webs', 'logo_header', 'thumbnail');
        yield from $this->tableColumnPaths('setting_webs', 'logo_footer', 'thumbnail');
        yield from $this->tableColumnPaths('setting_webs', 'logo_favicon', 'thumbnail');
        yield from $this->tableColumnPaths('setting_webs', 'seasonal_background_image', 'banner');
    }

    private function tableColumnPaths(string $table, string $column, string $profile): \Generator
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        foreach (DB::table($table)
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->pluck($column) as $path) {
            yield [
                'source' => "{$table}.{$column}",
                'path' => (string) $path,
                'profile' => $profile,
            ];
        }
    }

    private function beritaPaths(): \Generator
    {
        if (! Schema::hasTable('beritas') || ! Schema::hasColumn('beritas', 'path')) {
            return;
        }

        $query = DB::table('beritas')
            ->select('path');

        if (Schema::hasColumn('beritas', 'tipe')) {
            $query->addSelect('tipe');
        }

        foreach ($query
            ->whereNotNull('path')
            ->where('path', '!=', '')
            ->get() as $row) {
            yield [
                'source' => 'beritas.path',
                'path' => (string) $row->path,
                'profile' => strtolower((string) ($row->tipe ?? '')) === 'popup' ? 'popup' : 'banner',
            ];
        }
    }
}
