<?php

namespace App\Models;

use App\Models\Concerns\SyncsLegacyMediaPaths;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaAsset extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    use SyncsLegacyMediaPaths;

    protected $guarded = [];

    protected $casts = [
        'source_media_id' => 'integer',
    ];

    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection('file')
            ->useDisk('assets')
            ->singleFile();
    }

    public function getFileUrlAttribute(): ?string
    {
        $relativePath = $this->resolveRelativePath();

        if ($relativePath) {
            return asset($relativePath);
        }

        return $this->getFirstMediaUrl('file') ?: null;
    }

    public function resolveRelativePath(): ?string
    {
        if (! empty($this->path) && is_file(public_path(ltrim($this->path, '/')))) {
            return $this->path;
        }

        if ($legacyPath = $this->resolveSourceLegacyPath()) {
            return $legacyPath;
        }

        $media = $this->getFirstMedia('file');

        if ($media && is_file($media->getPath())) {
            return '/' . ltrim($media->getPathRelativeToRoot(), '/');
        }

        return null;
    }

    public function getFileExtensionAttribute(): ?string
    {
        $relativePath = $this->resolveRelativePath();

        if (! $relativePath) {
            return null;
        }

        $extension = pathinfo($relativePath, PATHINFO_EXTENSION);

        return $extension !== '' ? Str::lower($extension) : null;
    }

    public function getFileMimeTypeAttribute(): ?string
    {
        $media = $this->getFirstMedia('file');

        if ($media && filled($media->mime_type)) {
            return $media->mime_type;
        }

        $absolutePath = $this->resolveAbsolutePath();

        if (! $absolutePath || ! File::exists($absolutePath)) {
            return null;
        }

        return File::mimeType($absolutePath) ?: null;
    }

    public function getFileSizeBytesAttribute(): ?int
    {
        $media = $this->getFirstMedia('file');

        if ($media && filled($media->size)) {
            return (int) $media->size;
        }

        $absolutePath = $this->resolveAbsolutePath();

        if (! $absolutePath || ! File::exists($absolutePath)) {
            return null;
        }

        $size = File::size($absolutePath);

        return is_numeric($size) ? (int) $size : null;
    }

    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size_bytes;

        if (! $bytes || $bytes <= 0) {
            return '-';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);
        $value = $bytes / (1024 ** $power);

        return number_format($value, $power === 0 ? 0 : 2) . ' ' . $units[$power];
    }

    public function getIsImageFileAttribute(): bool
    {
        $mimeType = (string) $this->file_mime_type;

        if ($mimeType !== '' && str_starts_with($mimeType, 'image/')) {
            return true;
        }

        return in_array((string) $this->file_extension, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'], true);
    }

    public function resolveAbsolutePath(): ?string
    {
        $relativePath = $this->resolveRelativePath();

        if (! $relativePath) {
            return null;
        }

        $absolutePath = public_path(ltrim($relativePath, '/'));

        return is_file($absolutePath) ? $absolutePath : null;
    }

    protected function resolveSourceLegacyPath(): ?string
    {
        if (! $this->source_media_id) {
            return null;
        }

        /** @var ?Media $sourceMedia */
        $sourceMedia = Media::find($this->source_media_id);

        if (! $sourceMedia) {
            return null;
        }

        $sourceModel = $sourceMedia->model;

        if ($sourceModel && method_exists($sourceModel, 'getLegacyMediaColumnMap')) {
            $column = $sourceModel->getLegacyMediaColumnMap()[$sourceMedia->collection_name] ?? null;

            if ($column) {
                $legacyPath = $sourceModel->{$column} ?? null;

                if (! empty($legacyPath) && is_file(public_path(ltrim($legacyPath, '/')))) {
                    return $legacyPath;
                }
            }
        }

        if (is_file($sourceMedia->getPath())) {
            return '/' . ltrim($sourceMedia->getPathRelativeToRoot(), '/');
        }

        return null;
    }

    public function getLegacyMediaColumnMap(): array
    {
        return [
            'file' => 'path',
        ];
    }
}
