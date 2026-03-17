<?php

namespace App\Models;

use App\Models\Concerns\SyncsLegacyMediaPaths;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
