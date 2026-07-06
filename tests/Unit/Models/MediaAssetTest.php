<?php

namespace Tests\Unit\Models;

use App\Models\MediaAsset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\TestCase;

class MediaAssetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'uploads.disk' => 'assets',
            'uploads.placeholder' => 'assets/logo/favicon.webp',
            'filesystems.disks.r2.url' => 'https://cdn.test',
        ]);
    }

    protected function tearDown(): void
    {
        File::delete(public_path('assets/media/media-asset-local.png'));

        parent::tearDown();
    }

    public function test_media_collection_uses_configured_upload_disk(): void
    {
        config(['uploads.disk' => 'r2']);

        $asset = new MediaAsset();
        $asset->registerMediaCollections();

        $this->assertSame('r2', $asset->getMediaCollection('file')->diskName);
    }

    public function test_file_url_resolves_local_path_through_public_upload_resolver(): void
    {
        File::ensureDirectoryExists(public_path('assets/media'));
        File::put(public_path('assets/media/media-asset-local.png'), 'local');

        $asset = new MediaAsset([
            'name' => 'Local Asset',
            'folder' => 'lainnya',
            'path' => '/assets/media/media-asset-local.png',
        ]);

        $this->assertSame('/assets/media/media-asset-local.png', $asset->resolveRelativePath());
        $this->assertSame(asset('assets/media/media-asset-local.png'), $asset->file_url);
    }

    public function test_file_url_resolves_r2_path_when_object_exists(): void
    {
        config(['uploads.disk' => 'r2']);
        Storage::fake('r2');
        Storage::disk('r2')->put('assets/media/media-asset-remote.png', 'remote');

        $asset = new MediaAsset([
            'name' => 'Remote Asset',
            'folder' => 'lainnya',
            'path' => 'assets/media/media-asset-remote.png',
        ]);

        $this->assertSame('assets/media/media-asset-remote.png', $asset->resolveRelativePath());
        $this->assertStringContainsString('assets/media/media-asset-remote.png', $asset->file_url);
    }

    public function test_remote_media_relative_path_does_not_require_local_file(): void
    {
        $asset = MediaAsset::query()->create([
            'name' => 'Remote Media',
            'folder' => 'lainnya',
        ]);

        Media::query()->create([
            'model_type' => MediaAsset::class,
            'model_id' => $asset->getKey(),
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'collection_name' => 'file',
            'name' => 'remote-media',
            'file_name' => 'remote-media.png',
            'mime_type' => 'image/png',
            'disk' => 'r2',
            'conversions_disk' => 'r2',
            'size' => 123,
            'manipulations' => [],
            'custom_properties' => [],
            'generated_conversions' => [],
            'responsive_images' => [],
            'order_column' => 1,
        ]);

        $asset->load('media');

        $this->assertSame('/assets/media/1/remote-media.png', $asset->resolveRelativePath());
    }
}
