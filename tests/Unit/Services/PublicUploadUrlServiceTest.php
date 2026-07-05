<?php

namespace Tests\Unit\Services;

use App\Services\PublicUploadUrlService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicUploadUrlServiceTest extends TestCase
{
    private PublicUploadUrlService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.url' => 'https://app.test',
            'uploads.disk' => 'assets',
            'uploads.placeholder' => 'assets/logo/favicon.webp',
            'filesystems.disks.r2.url' => 'https://cdn.test',
        ]);

        $this->service = app(PublicUploadUrlService::class);
        File::ensureDirectoryExists(public_path('assets/logo'));
    }

    protected function tearDown(): void
    {
        File::delete(public_path('assets/logo/r2-resolver-local.png'));
        File::delete(public_path('assets/logo/r2-resolver-fallback.png'));

        parent::tearDown();
    }

    public function test_it_returns_external_urls_as_is(): void
    {
        $this->assertSame(
            'https://cdn.example.com/assets/logo/logo.webp',
            $this->service->url('https://cdn.example.com/assets/logo/logo.webp')
        );

        $this->assertNull($this->service->exists('https://cdn.example.com/assets/logo/logo.webp'));
    }

    public function test_it_resolves_existing_local_legacy_asset(): void
    {
        File::put(public_path('assets/logo/r2-resolver-local.png'), 'local');

        $this->assertSame(
            asset('assets/logo/r2-resolver-local.png'),
            $this->service->url('assets/logo/r2-resolver-local.png')
        );
        $this->assertTrue($this->service->exists('assets/logo/r2-resolver-local.png'));
    }

    public function test_it_falls_back_to_local_asset_when_r2_object_is_missing(): void
    {
        File::put(public_path('assets/logo/r2-resolver-local.png'), 'local');
        Storage::fake('r2');

        $this->assertSame(
            asset('assets/logo/r2-resolver-local.png'),
            $this->service->url('assets/logo/r2-resolver-local.png', 'r2')
        );
        $this->assertTrue($this->service->exists('assets/logo/r2-resolver-local.png', 'r2'));
    }

    public function test_it_uses_placeholder_when_path_is_missing(): void
    {
        File::put(public_path('assets/logo/r2-resolver-fallback.png'), 'fallback');

        $this->assertSame(
            asset('assets/logo/r2-resolver-fallback.png'),
            $this->service->url('assets/logo/missing.png', 'assets', 'assets/logo/r2-resolver-fallback.png')
        );
        $this->assertFalse($this->service->exists('assets/logo/missing.png'));
    }
}
