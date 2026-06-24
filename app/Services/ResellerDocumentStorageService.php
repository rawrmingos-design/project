<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ResellerDocumentStorageService
{
    private const BASE_PUBLIC_DIRECTORY = 'assets/reseller-documents';

    public function store(int $userId, UploadedFile $file, string $documentType): string
    {
        $relativePath = $this->buildRelativePath($userId, $documentType, $file->getClientOriginalExtension());
        $absolutePath = public_path($relativePath);
        $directory = dirname($absolutePath);

        $this->ensureDirectoryExists($directory);

        File::put($absolutePath, File::get($file->getRealPath()));

        return str_replace('\\', '/', $relativePath);
    }

    public function replace(?string $oldPath, int $userId, UploadedFile $file, string $documentType): string
    {
        $this->delete($oldPath);

        return $this->store($userId, $file, $documentType);
    }

    public function delete(?string $path): void
    {
        if (blank($path)) {
            return;
        }

        $absolutePath = public_path((string) $path);

        if (File::exists($absolutePath)) {
            File::delete($absolutePath);
        }
    }

    private function ensureDirectoryExists(string $directory): void
    {
        if (File::isDirectory($directory)) {
            return;
        }

        try {
            File::makeDirectory($directory, 0755, true, true);
        } catch (\Throwable $exception) {
            if (! File::isDirectory($directory)) {
                throw $exception;
            }
        }
    }

    public function buildRelativePath(int $userId, string $documentType, string $extension): string
    {
        $safeType = Str::snake(trim((string) $documentType));
        $safeExtension = ltrim(strtolower(trim((string) $extension)), '.');
        $random = Str::lower(Str::random(12));

        return sprintf(
            '%s/%d/%s_%s.%s',
            self::BASE_PUBLIC_DIRECTORY,
            $userId,
            $safeType,
            $random,
            $safeExtension !== '' ? $safeExtension : 'bin'
        );
    }
}
