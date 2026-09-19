<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class PublicUploadUrlService
{
    public function url(?string $path, ?string $disk = null, ?string $fallback = null): ?string
    {
        return $this->resolve($path, $disk, $fallback)['url'];
    }

    public function exists(?string $path, ?string $disk = null): ?bool
    {
        return $this->resolve($path, $disk)['exists'];
    }

    /**
     * Return an asset URL only when the requested asset exists.
     *
     * Unlike url(), this method never returns a placeholder or a URL for a
     * missing path. It is intended for data contracts rendered as <img src>.
     */
    public function existingUrl(?string $path, ?string $disk = null): ?string
    {
        $normalized = $this->normalizePath($path);

        if ($normalized === null) {
            return null;
        }

        if (Str::startsWith($normalized, ['http://', 'https://'])) {
            return $normalized;
        }

        $disk ??= (string) config('uploads.disk', 'assets');

        if ($disk !== 'assets') {
            try {
                $storage = Storage::disk($disk);

                if ($storage->exists($normalized)) {
                    return $storage->url($normalized);
                }
            } catch (Throwable) {
                // Fall back to the local public directory below.
            }
        }

        return $this->localExists($normalized) ? asset($normalized) : null;
    }

    /**
     * Resolve an asset URL and its existence state with one storage check.
     *
     * The fallback URL intentionally is not existence-checked to preserve the
     * legacy resolver contract used by shared site configuration.
     *
     * @return array{url: ?string, exists: ?bool}
     */
    public function resolve(?string $path, ?string $disk = null, ?string $fallback = null): array
    {
        $normalized = $this->normalizePath($path);

        if ($normalized === null) {
            return [
                'url' => $this->fallbackUrl($fallback),
                'exists' => false,
            ];
        }

        if (Str::startsWith($normalized, ['http://', 'https://'])) {
            return [
                'url' => $normalized,
                'exists' => null,
            ];
        }

        $disk ??= (string) config('uploads.disk', 'assets');

        if ($disk !== 'assets') {
            try {
                $storage = Storage::disk($disk);

                if ($storage->exists($normalized)) {
                    return [
                        'url' => $storage->url($normalized),
                        'exists' => true,
                    ];
                }
            } catch (Throwable) {
                // Fall back to the local public directory below.
            }
        }

        if ($this->localExists($normalized)) {
            return [
                'url' => asset($normalized),
                'exists' => true,
            ];
        }

        return [
            'url' => $this->fallbackUrl($fallback) ?? asset($normalized),
            'exists' => false,
        ];
    }

    public function normalizePath(?string $path): ?string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return ltrim(str_replace('\\', '/', $path), '/');
    }


    private function localExists(string $path): bool
    {
        return is_file(public_path($path));
    }

    private function fallbackUrl(?string $fallback = null): ?string
    {
        $fallback = $this->normalizePath($fallback ?: config('uploads.placeholder'));

        if ($fallback === null) {
            return null;
        }

        if (Str::startsWith($fallback, ['http://', 'https://'])) {
            return $fallback;
        }

        return asset($fallback);
    }
}
