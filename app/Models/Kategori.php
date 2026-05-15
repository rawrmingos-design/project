<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use App\Models\Concerns\SyncsLegacyMediaPaths;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Kategori extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    use SyncsLegacyMediaPaths;
    protected $guarded = [];

    protected $casts = [
        'server_id' => 'boolean',
        'require_user_id' => 'boolean',
    ];

    protected static function booted(): void
    {
        $invalidateSearchCache = static function (): void {
            static::bumpSearchCacheVersion();
        };

        static::saved($invalidateSearchCache);
        static::deleted($invalidateSearchCache);
    }

    protected static function bumpSearchCacheVersion(): void
    {
        $key = 'public:search:categories:version';

        try {
            $current = (int) Cache::get($key, 1);
            Cache::forever($key, max(1, $current) + 1);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
    
    // Relationships
    public function layanans(): HasMany
    {
        return $this->hasMany(Produk::class, 'kategori_id');
    }
    
    public function products(): HasMany
    {
        return $this->hasMany(Produk::class, 'kategori_id');
    }

    public function customInput(): HasOne
    {
        return $this->hasOne(CustomInput::class, 'kategori_id');
    }

    public function categoryType()
    {
        return $this->belongsTo(CategoryType::class);
    }

    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection('thumbnail')
            ->useDisk('assets')
            ->singleFile();

        $this
            ->addMediaCollection('banner')
            ->useDisk('assets')
            ->singleFile();
    }

    public function getLegacyMediaColumnMap(): array
    {
        return [
            'thumbnail' => 'thumbnail',
            'banner' => 'banner',
        ];
    }

    public function getLegacyMediaDirectoryMap(): array
    {
        return [
            'thumbnail' => 'assets/thumbnail',
            'banner' => 'assets/banner_game',
        ];
    }
}
