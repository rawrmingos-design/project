<?php

namespace App\Console\Commands;

use App\Services\MediaAssetDedupeService;
use Illuminate\Console\Command;

class DedupeMediaAssets extends Command
{
    protected $signature = 'media:dedupe-assets
        {--execute : Benar-benar hapus. Tanpa flag ini hanya DRY-RUN.}
        {--stats : Hanya tampilkan ringkasan, tanpa daftar per baris.}';

    protected $description = 'Hapus baris media_assets yang isinya identik dan tidak direferensikan (default dry-run).';

    public function handle(MediaAssetDedupeService $dedupeService): int
    {
        $execute = (bool) $this->option('execute');
        $statsOnly = (bool) $this->option('stats');

        if (! $execute) {
            $this->warn('DRY-RUN: tidak ada baris atau file yang dihapus. Tambahkan --execute untuk menerapkan.');
        } else {
            $this->warn('EXECUTE: baris dan file yang memenuhi syarat akan dihapus permanen.');
        }

        $result = $dedupeService->dedupe($execute, function (string $message) use ($statsOnly): void {
            if (! $statsOnly) {
                $this->line($message);
            }
        });

        $this->newLine();
        $this->info('Ringkasan dedupe media assets');
        $this->table(
            ['Metrik', 'Nilai'],
            [
                ['Grup isi identik', (string) $result['groups']],
                ['Grup yang bisa dikurangi', (string) $result['dedupeable_groups']],
                ['Baris dilindungi (dipertahankan)', (string) $result['kept_protected_rows']],
                ['Grup tanpa referensi', (string) $result['groups_without_reference']],
                [$execute ? 'Baris dihapus' : 'Baris akan dihapus', (string) $result['deleted_rows']],
                ['File dihapus', (string) $result['deleted_files']],
                ['File dilewati (di luar folder terkelola)', (string) $result['failed_files']],
                ['Disk dibebaskan', round($result['freed_bytes'] / 1048576, 2) . ' MB'],
            ]
        );

        return self::SUCCESS;
    }
}
