<?php

namespace Tests\Unit\Services;

use App\Services\OptimizedImageService;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class OptimizedImageServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        File::deleteDirectory(public_path('assets/test-optimized'));

        parent::tearDown();
    }

    public function test_it_generates_webp_variants_without_upscaling(): void
    {
        if (! function_exists('imagecreatetruecolor') || ! function_exists('imagewebp')) {
            $this->markTestSkipped('GD WebP support is not available.');
        }

        $relative = 'assets/test-optimized/source.png';
        $absolute = public_path($relative);

        File::ensureDirectoryExists(dirname($absolute));

        $image = imagecreatetruecolor(400, 200);
        $color = imagecolorallocate($image, 255, 120, 20);
        imagefilledrectangle($image, 0, 0, 400, 200, $color);
        imagepng($image, $absolute);
        imagedestroy($image);

        $service = app(OptimizedImageService::class);
        $result = $service->ensureVariants($relative, 'thumbnail');

        $this->assertContains($result['status'], ['generated', 'exists']);
        $this->assertNotEmpty($result['variants']);

        foreach ($result['variants'] as $variant) {
            $variantPath = public_path($variant);
            $size = getimagesize($variantPath);

            $this->assertFileExists($variantPath);
            $this->assertSame('image/webp', $size['mime'] ?? null);
            $this->assertLessThanOrEqual(400, $size[0]);

            @unlink($variantPath);
        }
    }

    public function test_it_skips_missing_external_and_unsupported_paths(): void
    {
        $service = app(OptimizedImageService::class);

        $this->assertSame('missing', $service->ensureVariants('assets/test-optimized/missing.jpg', 'thumbnail')['reason']);
        $this->assertSame('unsupported_path', $service->ensureVariants('https://example.com/image.jpg', 'thumbnail')['reason']);
        $this->assertSame('unsupported_path', $service->ensureVariants('assets/test-optimized/vector.svg', 'thumbnail')['reason']);
    }
}
