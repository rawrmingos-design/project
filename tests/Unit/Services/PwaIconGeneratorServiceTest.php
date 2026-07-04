<?php

namespace Tests\Unit\Services;

use App\Services\PwaIconGeneratorService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PwaIconGeneratorServiceTest extends TestCase
{
    private PwaIconGeneratorService $service;

    /** @var array<string, string|null> */
    private array $backups = [];

    /** @var array<int, string> */
    private array $generatedFiles = [
        'assets/pwa/icon-72.png',
        'assets/pwa/icon-96.png',
        'assets/pwa/icon-128.png',
        'assets/pwa/icon-144.png',
        'assets/pwa/icon-152.png',
        'assets/pwa/icon-192.png',
        'assets/pwa/icon-384.png',
        'assets/pwa/icon-512.png',
        'assets/pwa/icon-maskable-512.png',
        'assets/pwa/apple-touch-icon.png',
        'assets/pwa/badge-72.png',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new PwaIconGeneratorService();
        $this->backupCurrentIcons();
        File::ensureDirectoryExists(public_path('assets/pwa/source-test'));
    }

    protected function tearDown(): void
    {
        $this->restoreCurrentIcons();
        File::deleteDirectory(public_path('assets/pwa/source-test'));
        File::deleteDirectory(storage_path('app/pwa-icon-generator'));

        parent::tearDown();
    }

    public function test_it_generates_expected_pwa_icon_files_and_sizes(): void
    {
        $source = $this->createSourceImage('assets/pwa/source-test/source.png', 640, 720);

        $generated = $this->service->generate($source);

        foreach ($this->generatedFiles as $relativePath) {
            $this->assertArrayHasKey($relativePath, $generated);
            $this->assertFileExists(public_path($relativePath));
        }

        foreach ([72, 96, 128, 144, 152, 192, 384, 512] as $size) {
            $this->assertImageDimensions(public_path("assets/pwa/icon-{$size}.png"), $size, $size);
        }

        $this->assertImageDimensions(public_path('assets/pwa/icon-maskable-512.png'), 512, 512);
        $this->assertImageDimensions(public_path('assets/pwa/apple-touch-icon.png'), 180, 180);
        $this->assertImageDimensions(public_path('assets/pwa/badge-72.png'), 72, 72);
    }

    public function test_invalid_source_does_not_replace_existing_icons(): void
    {
        $existingIcon = public_path('assets/pwa/icon-72.png');
        File::ensureDirectoryExists(dirname($existingIcon));
        File::put($existingIcon, 'existing icon content');

        $this->expectException(\RuntimeException::class);

        try {
            $this->service->generate('assets/pwa/source-test/missing.png');
        } finally {
            $this->assertSame('existing icon content', File::get($existingIcon));
        }
    }

    private function createSourceImage(string $relativePath, int $width, int $height): string
    {
        $file = UploadedFile::fake()->image('source.png', $width, $height);
        $absolutePath = public_path($relativePath);
        File::ensureDirectoryExists(dirname($absolutePath));
        File::copy($file->getRealPath(), $absolutePath);

        return $relativePath;
    }

    private function assertImageDimensions(string $path, int $width, int $height): void
    {
        $size = getimagesize($path);

        $this->assertIsArray($size);
        $this->assertSame($width, $size[0]);
        $this->assertSame($height, $size[1]);
    }

    private function backupCurrentIcons(): void
    {
        foreach ($this->generatedFiles as $relativePath) {
            $absolutePath = public_path($relativePath);
            $this->backups[$relativePath] = File::exists($absolutePath) ? File::get($absolutePath) : null;
        }
    }

    private function restoreCurrentIcons(): void
    {
        foreach ($this->backups as $relativePath => $contents) {
            $absolutePath = public_path($relativePath);

            if ($contents === null) {
                File::delete($absolutePath);
                continue;
            }

            File::ensureDirectoryExists(dirname($absolutePath));
            File::put($absolutePath, $contents);
        }
    }
}
