<?php

namespace Tests\Feature;

use App\Models\MediaAsset;
use App\Services\MediaAssetDeletionService;
use App\Services\MediaAssetFolderSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MediaAssetDeletionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_deletes_managed_public_file_and_prevents_sync_from_recreating_asset(): void
    {
        $relativePath = '/assets/product_logo/test-delete-managed.png';
        $absolutePath = public_path(ltrim($relativePath, '/'));

        File::ensureDirectoryExists(dirname($absolutePath));
        File::put($absolutePath, 'fake image contents');

        try {
            $asset = MediaAsset::query()->create([
                'name' => 'test-delete-managed',
                'folder' => 'produk',
                'path' => $relativePath,
            ]);

            $result = app(MediaAssetDeletionService::class)->delete($asset);

            $this->assertTrue($result['asset_deleted']);
            $this->assertTrue($result['file_deleted']);
            $this->assertFalse(File::exists($absolutePath));
            $this->assertDatabaseMissing('media_assets', ['id' => $asset->id]);

            app(MediaAssetFolderSyncService::class)->sync();

            $this->assertDatabaseMissing('media_assets', ['path' => $relativePath]);
        } finally {
            File::delete($absolutePath);
        }
    }

    public function test_it_skips_physical_delete_outside_managed_directories(): void
    {
        $relativePath = '/unmanaged-file-manager-test.txt';
        $absolutePath = public_path(ltrim($relativePath, '/'));

        File::put($absolutePath, 'do not delete');

        try {
            $asset = MediaAsset::query()->create([
                'name' => 'unmanaged-file-manager-test',
                'folder' => 'lainnya',
                'path' => $relativePath,
            ]);

            $result = app(MediaAssetDeletionService::class)->delete($asset);

            $this->assertTrue($result['asset_deleted']);
            $this->assertFalse($result['file_deleted']);
            $this->assertTrue($result['file_skipped']);
            $this->assertTrue(File::exists($absolutePath));
            $this->assertDatabaseMissing('media_assets', ['id' => $asset->id]);
        } finally {
            File::delete($absolutePath);
        }
    }

    public function test_it_deletes_optimized_variants_for_managed_images(): void
    {
        $relativePath = '/assets/product_logo/test-delete-variant.png';
        $absolutePath = public_path(ltrim($relativePath, '/'));
        $variantRelativePath = 'assets/optimized/product_logo/test-delete-variant-abc123-160.webp';
        $variantAbsolutePath = public_path($variantRelativePath);

        File::ensureDirectoryExists(dirname($absolutePath));
        File::ensureDirectoryExists(dirname($variantAbsolutePath));
        File::put($absolutePath, 'fake image contents');
        File::put($variantAbsolutePath, 'fake webp contents');

        try {
            $asset = MediaAsset::query()->create([
                'name' => 'test-delete-variant',
                'folder' => 'produk',
                'path' => $relativePath,
            ]);

            $result = app(MediaAssetDeletionService::class)->delete($asset);

            $this->assertTrue($result['file_deleted']);
            $this->assertContains($variantRelativePath, $result['variants_deleted']);
            $this->assertFalse(File::exists($absolutePath));
            $this->assertFalse(File::exists($variantAbsolutePath));
        } finally {
            File::delete($absolutePath);
            File::delete($variantAbsolutePath);
        }
    }
}
