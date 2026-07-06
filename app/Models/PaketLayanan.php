<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\SyncsLegacyMediaPaths;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class PaketLayanan extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    use SyncsLegacyMediaPaths;

    protected $table = 'paket_layanans';
    protected $guarded = [];

    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection('product_logo')
            ->useDisk(config('uploads.disk', 'assets'))
            ->singleFile();
    }

    public function getLegacyMediaColumnMap(): array
    {
        return [
            'product_logo' => 'product_logo',
        ];
    }

    public function getLegacyMediaDirectoryMap(): array
    {
        return [
            'product_logo' => 'assets/product_logo',
        ];
    }
}
