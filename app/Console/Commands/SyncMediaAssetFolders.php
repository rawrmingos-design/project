<?php

namespace App\Console\Commands;

use App\Services\MediaAssetFolderSyncService;
use Illuminate\Console\Command;

class SyncMediaAssetFolders extends Command
{
    protected $signature = 'media:sync-asset-folders';

    protected $description = 'Index file dari folder public/assets ke Media Library tanpa copy file.';

    public function handle(MediaAssetFolderSyncService $syncService): int
    {
        $this->info('Sinkronisasi asset folders ke Media Library...');

        $result = $syncService->sync(function (string $message): void {
            $this->line($message);
        });

        $this->newLine();
        $this->info("Created: {$result['created']}");
        $this->info("Skipped: {$result['skipped']}");

        return self::SUCCESS;
    }
}
