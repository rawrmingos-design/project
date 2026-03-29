<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use \Illuminate\Database\Eloquent\Factories\HasFactory;
use \Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Artikel extends Model {
    use HasFactory;
    use SoftDeletes;
    
    protected $guarded = [];
    
    protected $casts = [
        'views' => 'integer',
        'status' => 'string',
        'layout' => 'string',
    ];

    protected static function booted(): void
    {
        static::created(function (): void {
            static::bumpFrontendCacheVersion();
        });

        static::updated(function (self $artikel): void {
            if ($artikel->wasChanged([
                'title',
                'slug',
                'thumbnail',
                'content',
                'meta_description',
                'keywords',
                'primary_color',
                'secondary_color',
                'layout',
                'status',
            ])) {
                static::bumpFrontendCacheVersion();
            }
        });

        static::deleted(function (): void {
            static::bumpFrontendCacheVersion();
        });

        static::restored(function (): void {
            static::bumpFrontendCacheVersion();
        });
    }

    public static function frontendCacheVersion(): int
    {
        return (int) Cache::get('articles:cache_version', 1);
    }

    protected static function bumpFrontendCacheVersion(): void
    {
        Cache::forever('articles:cache_version', static::frontendCacheVersion() + 1);
    }
}
