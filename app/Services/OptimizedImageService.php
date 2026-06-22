<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class OptimizedImageService
{
    private const QUALITY = 82;

    private const PROFILES = [
        'thumbnail' => [160, 320, 480],
        'product_logo' => [160, 320, 480],
        'banner' => [640, 960, 1280, 1600],
        'article' => [400, 800, 1200],
        'popup' => [480, 720, 960],
        'payment_logo' => [80, 160, 320],
    ];

    private const SUPPORTED_MIMES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    public function profileForCollection(string $collection): string
    {
        return match ($collection) {
            'banner' => 'banner',
            'product_logo' => 'product_logo',
            default => 'thumbnail',
        };
    }

    public function profileForBerita(?string $type): string
    {
        return strtolower((string) $type) === 'popup' ? 'popup' : 'banner';
    }

    public function metadata(?string $source, string $profile = 'thumbnail'): array
    {
        $analysis = $this->analyze($source, $profile);
        $fallback = $this->fallbackUrl($source);

        if (! ($analysis['optimizable'] ?? false)) {
            return [
                'src' => $fallback,
                'srcset' => null,
                'width' => $analysis['width'] ?? null,
                'height' => $analysis['height'] ?? null,
                'optimizable' => false,
            ];
        }

        $srcset = collect($this->variantCandidates($analysis))
            ->filter(fn (array $variant): bool => is_file($variant['absolute']))
            ->map(fn (array $variant): string => asset($variant['relative']) . ' ' . $variant['width'] . 'w')
            ->implode(', ');

        return [
            'src' => $fallback,
            'srcset' => $srcset !== '' ? $srcset : null,
            'width' => $analysis['width'],
            'height' => $analysis['height'],
            'optimizable' => true,
        ];
    }

    public function preferredUrl(?string $source, string $profile = 'thumbnail'): ?string
    {
        $analysis = $this->analyze($source, $profile);
        $fallback = $this->fallbackUrl($source);

        if (! ($analysis['optimizable'] ?? false)) {
            return $fallback;
        }

        $variant = collect($this->variantCandidates($analysis))
            ->filter(fn (array $variant): bool => is_file($variant['absolute']))
            ->sortByDesc('width')
            ->first();

        return $variant ? asset($variant['relative']) : $fallback;
    }

    public function ensureVariants(?string $source, string $profile = 'thumbnail'): array
    {
        $analysis = $this->analyze($source, $profile);

        if (! ($analysis['optimizable'] ?? false)) {
            return [
                'status' => 'skipped',
                'reason' => $analysis['reason'] ?? 'not_optimizable',
                'source' => $source,
                'variants' => [],
            ];
        }

        $variants = $this->variantCandidates($analysis);
        $generated = [];
        $existing = [];
        $failed = [];

        foreach ($variants as $variant) {
            if (is_file($variant['absolute'])) {
                $existing[] = $variant['relative'];

                continue;
            }

            try {
                $this->writeWebpVariant($analysis, $variant);
                $generated[] = $variant['relative'];
            } catch (\Throwable $exception) {
                report($exception);

                $failed[] = [
                    'path' => $variant['relative'],
                    'error' => $exception->getMessage(),
                ];
            }
        }

        return [
            'status' => $failed !== [] ? 'failed' : ($generated !== [] ? 'generated' : 'exists'),
            'reason' => null,
            'source' => $source,
            'variants' => array_values(array_unique([...$existing, ...$generated])),
            'generated' => $generated,
            'existing' => $existing,
            'failed' => $failed,
        ];
    }

    public function deleteVariants(?string $source, ?string $profile = null): array
    {
        $profiles = $profile ? [$this->normalizeProfile($profile)] : array_keys(self::PROFILES);
        $deleted = [];
        $skipped = [];

        foreach ($profiles as $profileName) {
            $analysis = $this->analyze($source, $profileName);

            if ($analysis['optimizable'] ?? false) {
                foreach ($this->variantCandidates($analysis) as $variant) {
                    if (! is_file($variant['absolute'])) {
                        continue;
                    }

                    if (File::delete($variant['absolute'])) {
                        $deleted[] = $variant['relative'];
                    } else {
                        $skipped[] = $variant['relative'];
                    }
                }

                continue;
            }

            foreach ($this->fallbackVariantCandidates($source, $profileName) as $variant) {
                if (File::delete($variant['absolute'])) {
                    $deleted[] = $variant['relative'];
                } else {
                    $skipped[] = $variant['relative'];
                }
            }
        }

        return [
            'deleted' => array_values(array_unique($deleted)),
            'skipped' => array_values(array_unique($skipped)),
        ];
    }

    public function analyze(?string $source, string $profile = 'thumbnail'): array
    {
        $relative = $this->normalizeRelativePath($source);

        if (! $relative) {
            return [
                'optimizable' => false,
                'reason' => 'unsupported_path',
            ];
        }

        $absolute = public_path($relative);

        if (! is_file($absolute)) {
            return [
                'optimizable' => false,
                'reason' => 'missing',
                'relative' => $relative,
                'absolute' => $absolute,
            ];
        }

        if (! function_exists('imagewebp')) {
            return [
                'optimizable' => false,
                'reason' => 'webp_unavailable',
                'relative' => $relative,
                'absolute' => $absolute,
            ];
        }

        $size = @getimagesize($absolute);

        if (! $size || empty($size[0]) || empty($size[1])) {
            return [
                'optimizable' => false,
                'reason' => 'invalid_image',
                'relative' => $relative,
                'absolute' => $absolute,
            ];
        }

        $mime = $size['mime'] ?? null;

        if (! in_array($mime, self::SUPPORTED_MIMES, true)) {
            return [
                'optimizable' => false,
                'reason' => 'unsupported_mime',
                'relative' => $relative,
                'absolute' => $absolute,
                'mime' => $mime,
                'width' => (int) $size[0],
                'height' => (int) $size[1],
            ];
        }

        return [
            'optimizable' => true,
            'reason' => null,
            'relative' => $relative,
            'absolute' => $absolute,
            'profile' => $this->normalizeProfile($profile),
            'mime' => $mime,
            'width' => (int) $size[0],
            'height' => (int) $size[1],
            'hash' => substr(sha1_file($absolute) ?: sha1($relative), 0, 12),
        ];
    }

    public function normalizeRelativePath(?string $source): ?string
    {
        $source = trim((string) $source);

        if ($source === '') {
            return null;
        }

        if (Str::startsWith(Str::lower($source), ['http://', 'https://', '//', 'data:', 'blob:'])) {
            return null;
        }

        $path = parse_url($source, PHP_URL_PATH);
        $path = is_string($path) ? trim($path) : $source;
        $path = ltrim($path, '/');

        if (Str::startsWith($path, 'public/')) {
            $path = substr($path, strlen('public/'));
        }

        if ($path === '') {
            return null;
        }

        $extension = Str::lower(pathinfo($path, PATHINFO_EXTENSION));

        if (! in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return null;
        }

        return str_replace('\\', '/', $path);
    }

    private function fallbackUrl(?string $source): ?string
    {
        $source = trim((string) $source);

        if ($source === '') {
            return null;
        }

        if (Str::startsWith(Str::lower($source), ['http://', 'https://', '//', 'data:', 'blob:'])) {
            return $source;
        }

        $path = parse_url($source, PHP_URL_PATH);
        $path = is_string($path) ? $path : $source;

        return asset(ltrim($path, '/'));
    }

    private function variantCandidates(array $analysis): array
    {
        $profile = $this->normalizeProfile($analysis['profile'] ?? 'thumbnail');
        $sourceWidth = (int) $analysis['width'];
        $widths = collect(self::PROFILES[$profile])
            ->filter(fn (int $width): bool => $width <= $sourceWidth)
            ->unique()
            ->sort()
            ->values()
            ->all();

        if ($widths === []) {
            $widths = [$sourceWidth];
        }

        $baseName = Str::slug(pathinfo((string) $analysis['relative'], PATHINFO_FILENAME)) ?: 'image';
        $hash = $analysis['hash'] ?? substr(sha1((string) $analysis['relative']), 0, 12);

        return collect($widths)
            ->map(function (int $width) use ($profile, $baseName, $hash): array {
                $relative = "assets/optimized/{$profile}/{$baseName}-{$hash}-{$width}.webp";

                return [
                    'width' => $width,
                    'relative' => $relative,
                    'absolute' => public_path($relative),
                ];
            })
            ->all();
    }

    private function fallbackVariantCandidates(?string $source, string $profile): array
    {
        $relative = $this->normalizeRelativePath($source);

        if (! $relative) {
            return [];
        }

        $profile = $this->normalizeProfile($profile);
        $baseName = Str::slug(pathinfo($relative, PATHINFO_FILENAME)) ?: 'image';
        $directory = public_path("assets/optimized/{$profile}");

        if (! File::isDirectory($directory)) {
            return [];
        }

        return collect(File::files($directory))
            ->filter(fn ($file): bool => str_starts_with($file->getFilename(), $baseName . '-') && $file->getExtension() === 'webp')
            ->map(fn ($file): array => [
                'relative' => 'assets/optimized/' . $profile . '/' . $file->getFilename(),
                'absolute' => $file->getPathname(),
            ])
            ->values()
            ->all();
    }

    private function writeWebpVariant(array $analysis, array $variant): void
    {
        File::ensureDirectoryExists(dirname($variant['absolute']));

        $source = $this->createImageResource($analysis['absolute'], $analysis['mime']);
        $sourceWidth = (int) $analysis['width'];
        $sourceHeight = (int) $analysis['height'];
        $targetWidth = (int) $variant['width'];
        $targetHeight = max(1, (int) round($sourceHeight * ($targetWidth / $sourceWidth)));

        $target = imagecreatetruecolor($targetWidth, $targetHeight);

        imagealphablending($target, false);
        imagesavealpha($target, true);

        $transparent = imagecolorallocatealpha($target, 0, 0, 0, 127);
        imagefilledrectangle($target, 0, 0, $targetWidth, $targetHeight, $transparent);

        imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);

        try {
            if (! imagewebp($target, $variant['absolute'], self::QUALITY)) {
                throw new \RuntimeException("Failed to write WebP variant: {$variant['absolute']}");
            }
        } finally {
            imagedestroy($source);
            imagedestroy($target);
        }
    }

    /**
     * @return \GdImage|resource
     */
    private function createImageResource(string $path, string $mime)
    {
        $image = match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($path),
            'image/png' => imagecreatefrompng($path),
            'image/webp' => imagecreatefromwebp($path),
            default => throw new \InvalidArgumentException("Unsupported image mime type: {$mime}"),
        };

        if (! $image) {
            throw new \RuntimeException("Failed to read source image: {$path}");
        }

        return $image;
    }

    private function normalizeProfile(string $profile): string
    {
        return array_key_exists($profile, self::PROFILES) ? $profile : 'thumbnail';
    }
}
