<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\SyncsLegacyMediaPaths;
use App\Services\PublicUploadUrlService;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Produk extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    use SyncsLegacyMediaPaths;
    
    protected $table = 'layanans';
    
    protected $guarded = [];
    
    protected $casts = [
        'is_flash_sale' => 'boolean',
        'expired_flash_sale' => 'datetime',
        'harga' => 'integer',
        'harga_member' => 'integer',
        'harga_platinum' => 'integer',
        'harga_gold' => 'integer',
        'harga_flash_sale' => 'integer',
        'profit_member' => 'integer',
        'profit_platinum' => 'integer',
        'profit_gold' => 'integer',
        'stock_flash_sale' => 'integer',
    ];
    
    // Relationships
    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class, 'kategori_id');
    }
    
    // Scopes
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['available', 'active']);
    }
    
    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }
    
    public function scopeFlashSale($query)
    {
        return $query->where('is_flash_sale', true)
                    ->where('expired_flash_sale', '>', now());
    }
    
    // Accessors
    public function getFormattedHargaAttribute()
    {
        return 'Rp ' . number_format($this->harga, 0, ',', '.');
    }
    
    public function getIsFlashSaleActiveAttribute()
    {
        return $this->is_flash_sale && 
               $this->expired_flash_sale && 
               $this->expired_flash_sale > now();
    }

    public function provider_paths()
    {
        return $this->hasMany(ProviderPath::class, 'layanan_id');
    }

    public function paket()
    {
        return $this->belongsToMany(Paket::class, 'paket_layanans', 'layanan_id', 'paket_id')
            ->withPivot('product_logo')
            ->withTimestamps();
    }

    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection('product_logo')
            ->useDisk(config('uploads.disk', 'assets'))
            ->singleFile();
    }

    public function getLegacyMediaColumnMap(): array
    {
        return [];
    }

    public function getLegacyMediaDirectoryMap(): array
    {
        return [];
    }

    public function resolveActiveMediaPreviewData(?string $collection = null, ?string $legacyColumn = null): ?array
    {
        if ($collection !== 'product_logo') {
            return null;
        }

        $media = $this->getFirstMedia('product_logo');

        if ($media && (($media->disk ?? 'assets') !== 'assets' || is_file($media->getPath()))) {
            $relativePath = '/' . ltrim($media->getPathRelativeToRoot(), '/');
            $url = app(PublicUploadUrlService::class)->url($relativePath, $media->disk ?: config('uploads.disk', 'assets'));

            return [
                'label' => $media->name ?: pathinfo($media->file_name, PATHINFO_FILENAME),
                'url' => $url,
                'alt' => $media->name ?: pathinfo($media->file_name, PATHINFO_FILENAME),
                'source' => 'Upload Record',
                'path' => $relativePath,
            ];
        }

        $pivotLogo = PaketLayanan::query()
            ->where('layanan_id', $this->id)
            ->whereNotNull('product_logo')
            ->where('product_logo', '!=', '')
            ->value('product_logo');

        if ($pivotLogo) {
            return [
                'label' => pathinfo($pivotLogo, PATHINFO_FILENAME),
                'url' => app(PublicUploadUrlService::class)->url($pivotLogo, config('uploads.disk', 'assets')),
                'alt' => pathinfo($pivotLogo, PATHINFO_FILENAME),
                'source' => 'Path Paket Layanan',
                'path' => $pivotLogo,
            ];
        }

        if (! empty($this->product_logo)) {
            return [
                'label' => pathinfo($this->product_logo, PATHINFO_FILENAME),
                'url' => app(PublicUploadUrlService::class)->url($this->product_logo, config('uploads.disk', 'assets')),
                'alt' => pathinfo($this->product_logo, PATHINFO_FILENAME),
                'source' => 'Path Layanan',
                'path' => $this->product_logo,
            ];
        }

        return null;
    }
}
