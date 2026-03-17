<?php

namespace App\Services;

use App\Models\MediaAsset;

class MediaAssetInvalidCleanupService
{
    public function cleanup(bool $execute = false, ?callable $logger = null): array
    {
        $scanned = 0;
        $invalid = 0;
        $deleted = 0;

        MediaAsset::query()
            ->orderBy('id')
            ->chunkById(100, function ($assets) use (&$scanned, &$invalid, &$deleted, $execute, $logger): void {
                foreach ($assets as $asset) {
                    $scanned++;

                    if ($asset->resolveRelativePath()) {
                        continue;
                    }

                    $invalid++;

                    if ($logger) {
                        $logger(sprintf(
                            '[%s] Invalid MediaAsset #%d (%s)',
                            $execute ? 'EXECUTE' : 'DRY-RUN',
                            $asset->id,
                            $asset->name
                        ));
                    }

                    if (! $execute) {
                        continue;
                    }

                    foreach ($asset->media as $media) {
                        $media->delete();
                    }

                    $asset->delete();
                    $deleted++;
                }
            });

        return [
            'scanned' => $scanned,
            'invalid' => $invalid,
            'deleted' => $deleted,
        ];
    }
}
