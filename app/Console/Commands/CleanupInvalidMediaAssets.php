<?php

namespace App\Console\Commands;

use App\Services\MediaAssetInvalidCleanupService;
use Illuminate\Console\Command;

class CleanupInvalidMediaAssets extends Command
{
    protected $signature = 'media:cleanup-invalid-assets {--execute : Jalankan penghapusan row invalid. Default hanya preview.}';

    protected $description = 'Bersihkan row MediaAsset yang tidak lagi punya file valid.';

    public function handle(MediaAssetInvalidCleanupService $cleanupService): int
    {
        $execute = (bool) $this->option('execute');

        $this->info($execute
            ? 'Menjalankan cleanup invalid media assets...'
            : 'Dry-run cleanup invalid media assets...'
        );

        $result = $cleanupService->cleanup($execute, function (string $message): void {
            $this->line($message);
        });

        $this->newLine();
        $this->info("Scanned: {$result['scanned']}");
        $this->info("Invalid: {$result['invalid']}");
        $this->info("Deleted: {$result['deleted']}");

        if (! $execute) {
            $this->warn('Belum ada perubahan diterapkan. Jalankan lagi dengan --execute untuk menghapus row invalid.');
        }

        return self::SUCCESS;
    }
}
