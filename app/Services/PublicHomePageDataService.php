<?php

namespace App\Services;

use App\Helpers\HtmlSanitizer;
use App\Models\Artikel;
use App\Models\Berita;
use App\Models\CategoryType;
use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Method;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class PublicHomePageDataService
{
    private const CATEGORY_TAB_TYPE_FALLBACKS = [
        'top-up-games' => ['populer', 'game'],
        'specialist-mobile-legends' => ['vilogml', 'joki'],
        'app-premium' => ['app', 'premium'],
        'pulsa-data' => ['pulsa'],
        'voucher' => ['voucher'],
    ];

    private function beritaSelectableColumns(array $preferred = []): array
    {
        $columns = ['id'];

        foreach ($preferred as $column) {
            if (\Illuminate\Support\Facades\Schema::hasColumn('beritas', $column)) {
                $columns[] = $column;
            }
        }

        return array_values(array_unique($columns));
    }

    private function beritaImage(Berita $berita): ?string
    {
        $path = trim((string) ($berita->path ?? $berita->images ?? ''));

        if ($path === '' || $path === '/' || $path === '#') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return '/' . ltrim($path, '/');
    }

    private function beritaTitle(Berita $berita, string $fallback): string
    {
        return (string) ($berita->judul ?? $berita->kategori ?? $berita->tipe ?? $fallback);
    }

    public function getData(): array
    {
        $ttl = 300;

        return [
            'banners' => Cache::remember('inertia_home:banners', $ttl, fn () => $this->banners()),
            'popup' => Cache::remember('inertia_home:popup', $ttl, fn () => $this->popup()),
            'featuredCategories' => Cache::remember('inertia_home:featured_categories', $ttl, fn () => $this->featuredCategories()),
            'categoryTabs' => Cache::remember('inertia_home:category_tabs', $ttl, fn () => $this->categoryTabs()),
            'flashsale' => Cache::remember('inertia_home:flashsale', 60, fn () => $this->flashsale()),
            'articles' => Cache::remember('inertia_home:articles', $ttl, fn () => $this->articles()),
            'paymentMethods' => Cache::remember('inertia_home:payment_methods', $ttl, fn () => $this->paymentMethods()),
        ];
    }

    private function beritaSortColumn(): string
    {
        return Schema::hasColumn('beritas', 'urutan') ? 'urutan' : 'id';
    }

    private function banners()
    {
        $sortColumn = $this->beritaSortColumn();

        return Berita::query()
            ->where('tipe', 'banner')
            ->orderBy($sortColumn)
            ->orderByDesc('id')
            ->get($this->beritaSelectableColumns(['judul', 'deskripsi', 'kategori', 'images', 'path', 'tipe']))
            ->map(fn (Berita $banner) => [
                'id' => $banner->id,
                'title' => $this->beritaTitle($banner, 'Promo Top Up'),
                'description' => $banner->deskripsi,
                'image' => $this->beritaImage($banner),
                'category' => $banner->kategori ?? $banner->tipe,
            ])
            ->values();
    }

    private function popup(): ?array
    {
        $sortColumn = $this->beritaSortColumn();

        $popup = Berita::query()
            ->where('tipe', 'popup')
            ->orderBy($sortColumn)
            ->orderByDesc('id')
            ->first($this->beritaSelectableColumns(['judul', 'deskripsi', 'images', 'path', 'tipe']));

        if (! $popup) {
            return null;
        }

        return [
            'id' => $popup->id,
            'title' => $this->beritaTitle($popup, 'Info Penting'),
            'description' => HtmlSanitizer::cleanArticle($popup->deskripsi),
            'image' => $this->beritaImage($popup),
        ];
    }

    private function featuredCategories()
    {
        return Kategori::query()
            ->select(['id', 'nama', 'sub_nama', 'thumbnail', 'kode', 'tipe'])
            ->where('status', 'active')
            ->where('tipe', 'populer')
            ->orderBy('id')
            ->get()
            ->map(fn ($kategori) => [
                'id' => $kategori->id,
                'name' => $kategori->nama,
                'subtitle' => $kategori->sub_nama,
                'slug' => $kategori->kode,
                'thumbnail' => '/' . ltrim((string) $kategori->thumbnail, '/'),
                'type' => $kategori->tipe,
            ])
            ->values();
    }

    private function categoryTabs()
    {
        $activeCategories = Kategori::query()
            ->select(['id', 'category_type_id', 'nama', 'sub_nama', 'thumbnail', 'kode', 'tipe'])
            ->where('status', 'active')
            ->orderBy('nama')
            ->get();

        return CategoryType::query()
            ->orderBy('sort', 'asc')
            ->select(['id', 'name', 'slug', 'sort'])
            ->get()
            ->map(function (CategoryType $type) use ($activeCategories) {
                $fallbackTypes = self::CATEGORY_TAB_TYPE_FALLBACKS[$type->slug] ?? [];

                $items = $activeCategories
                    ->filter(function ($kategori) use ($type, $fallbackTypes) {
                        if ((int) $kategori->category_type_id === (int) $type->id) {
                            return true;
                        }

                        return in_array((string) $kategori->tipe, $fallbackTypes, true);
                    })
                    ->unique('id')
                    ->values()
                    ->map(fn ($kategori) => [
                        'id' => $kategori->id,
                        'name' => $kategori->nama,
                        'subtitle' => $kategori->sub_nama,
                        'slug' => $kategori->kode,
                        'thumbnail' => '/' . ltrim((string) $kategori->thumbnail, '/'),
                    ])
                    ->values();

                return [
                    'id' => $type->id,
                    'name' => $type->name,
                    'slug' => $type->slug,
                    'items' => $items,
                ];
            })
            ->filter(fn (array $type) => $type['items']->isNotEmpty())
            ->values();
    }

    private function flashsale()
    {
        return Layanan::query()
            ->join('kategoris', 'kategoris.id', '=', 'layanans.kategori_id')
            ->join('paket_layanans', 'paket_layanans.layanan_id', '=', 'layanans.id')
            ->select(
                'layanans.id',
                'kategoris.thumbnail AS gmr_thumb',
                'kategoris.kode AS kode_game',
                'kategoris.nama AS kategori_nama',
                'layanans.judul_flash_sale',
                'layanans.harga',
                'layanans.harga_flash_sale',
                'layanans.stock_flash_sale',
                'layanans.expired_flash_sale',
                'paket_layanans.product_logo'
            )
            ->where('layanans.is_flash_sale', 1)
            ->where('layanans.expired_flash_sale', '>=', now())
            ->where('layanans.stock_flash_sale', '>', 0)
            ->orderBy('layanans.expired_flash_sale')
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'title' => $item->judul_flash_sale ?: $item->kategori_nama,
                'category' => $item->kategori_nama,
                'slug' => $item->kode_game,
                'thumbnail' => '/' . ltrim((string) $item->gmr_thumb, '/'),
                'productLogo' => '/' . ltrim((string) $item->product_logo, '/'),
                'price' => (int) $item->harga,
                'flashPrice' => (int) $item->harga_flash_sale,
                'stock' => (int) $item->stock_flash_sale,
                'expiresAt' => $item->expired_flash_sale ? Carbon::parse($item->expired_flash_sale)->toIso8601String() : null,
            ])
            ->values();
    }

    private function articles()
    {
        return Artikel::query()
            ->where('status', 'active')
            ->select(['id', 'slug', 'title', 'thumbnail', 'created_at', 'views'])
            ->latest()
            ->take(3)
            ->get()
            ->map(fn ($article) => [
                'id' => $article->id,
                'slug' => $article->slug,
                'title' => $article->title,
                'thumbnail' => '/' . ltrim((string) $article->thumbnail, '/'),
                'publishedAt' => optional($article->created_at)?->toDateString(),
                'views' => (int) ($article->views ?? 0),
            ])
            ->values();
    }

    private function paymentMethods()
    {
        $methods = app(\App\Services\PaymentMethodCatalogService::class)->getVisibleMethods();

        return collect($methods)->map(fn (\App\Models\Method $method) => [
                'id' => $method->id,
                'name' => $method->name,
                'code' => $method->code,
                'type' => $method->displayCategory?->code ?? $method->tipe,
                'typeLabel' => $method->displayCategory?->label ?? $method->tipe,
                'image' => $method->images ? '/' . ltrim((string) $method->images, '/') : null,
            ])
            ->values();
    }
}
