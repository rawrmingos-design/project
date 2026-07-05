<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class PublicUploadUrlService
{
    public function url(?string $path, ?string $disk = null, ?string $fallback = null): ?string
    {
        $normalized = $this->normalizePath($path);

        if ($normalized === null) {
            return $this->fallbackUrl($fallback);
        }

        if (Str::startsWith($normalized, ['http://', 'https://'])) {
            return $normalized;
        }

        $disk ??= (string) config('uploads.disk', 'assets');

        if ($disk !== 'assets') {
            $remoteUrl = $this->remoteUrl($disk, $normalized);

            if ($remoteUrl !== null) {
                return $remoteUrl;
            }
        }

        if ($this->localExists($normalized)) {
            return asset($normalized);
        }

        return $this->fallbackUrl($fallback) ?? asset($normalized);
    }

    public function exists(?string $path, ?string $disk = null): ?bool
    {
        $normalized = $this->normalizePath($path);

        if ($normalized === null) {
            return false;
        }

        if (Str::startsWith($normalized, ['http://', 'https://'])) {
            return null;
        }

        $disk ??= (string) config('uploads.disk', 'assets');

        if ($disk !== 'assets') {
            try {
                return Storage::disk($disk)->exists($normalized) || $this->localExists($normalized);
            } catch (Throwable) {
                return $this->localExists($normalized);
            }
        }

        return $this->localExists($normalized);
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

    private function remoteUrl(string $disk, string $path): ?string
    {
        try {
            if (! Storage::disk($disk)->exists($path)) {
                return null;
            }

            return Storage::disk($disk)->url($path);
        } catch (Throwable) {
            return null;
        }
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
