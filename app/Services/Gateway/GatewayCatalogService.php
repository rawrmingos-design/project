<?php

namespace App\Services\Gateway;

use App\Models\CategoryType;
use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class GatewayCatalogService
{
    public function categoryTypes(array $filters = []): array
    {
        $search = strtolower(trim((string) ($filters['q'] ?? '')));
        $cacheKey = 'gateway:category-types:v1:' . sha1($search);

        return Cache::remember($cacheKey, 300, function () use ($search): array {
            $activeCategories = Kategori::query()
                ->select(['id', 'category_type_id', 'tipe'])
                ->where('status', 'active')
                ->get();

            $categoryCounts = $activeCategories
                ->filter(fn (Kategori $category): bool => $category->category_type_id !== null)
                ->countBy('category_type_id');

            $serviceCounts = Layanan::query()
                ->join('kategoris', 'kategoris.id', '=', 'layanans.kategori_id')
                ->where('kategoris.status', 'active')
                ->where('layanans.status', 'available')
                ->whereNotNull('kategoris.category_type_id')
                ->selectRaw('kategoris.category_type_id, COUNT(*) as aggregate')
                ->groupBy('kategoris.category_type_id')
                ->pluck('aggregate', 'category_type_id');

            $types = CategoryType::query()
                ->select(['id', 'name', 'slug', 'sort', 'icon'])
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($inner) use ($search): void {
                        $inner->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                            ->orWhereRaw('LOWER(slug) LIKE ?', ["%{$search}%"]);
                    });
                })
                ->orderBy('sort')
                ->orderBy('name')
                ->get()
                ->map(function (CategoryType $type) use ($categoryCounts, $serviceCounts): array {
                    return [
                        'id' => $type->id,
                        'slug' => (string) $type->slug,
                        'name' => (string) $type->name,
                        'sort' => (int) $type->sort,
                        'icon' => $type->icon,
                        'category_count' => (int) ($categoryCounts[$type->id] ?? 0),
                        'service_count' => (int) ($serviceCounts[$type->id] ?? 0),
                    ];
                })
                ->filter(fn (array $type): bool => $type['category_count'] > 0)
                ->values()
                ->all();

            return [
                'ok' => true,
                'message' => 'Tipe kategori berhasil dimuat.',
                'data' => $types,
            ];
        });
    }

    public function categories(?User $user = null, array $filters = []): array
    {
        $search = strtolower(trim((string) ($filters['q'] ?? '')));
        $typeSlug = strtolower(trim((string) ($filters['type'] ?? $filters['category_type'] ?? '')));
        $role = (string) ($user?->role ?? 'Guest');
        $cacheKey = 'gateway:categories:v1:' . sha1(json_encode([$search, $typeSlug, $role], JSON_UNESCAPED_SLASHES));

        return Cache::remember($cacheKey, 300, function () use ($search, $typeSlug): array {
            $categories = Kategori::query()
                ->with('categoryType:id,name,slug,sort,icon')
                ->where('status', 'active')
                ->when($typeSlug !== '', function ($query) use ($typeSlug): void {
                    $query->whereHas('categoryType', function ($inner) use ($typeSlug): void {
                        $inner->where('slug', $typeSlug);
                    });
                })
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($inner) use ($search): void {
                        $inner->whereRaw('LOWER(nama) LIKE ?', ["%{$search}%"])
                            ->orWhereRaw('LOWER(kode) LIKE ?', ["%{$search}%"]);
                    });
                })
                ->orderBy('nama')
                ->get();

            $serviceCounts = Layanan::query()
                ->selectRaw('kategori_id, COUNT(*) as aggregate')
                ->where('status', 'available')
                ->whereIn('kategori_id', $categories->pluck('id'))
                ->groupBy('kategori_id')
                ->pluck('aggregate', 'kategori_id');

            return [
                'ok' => true,
                'message' => 'Kategori berhasil dimuat.',
                'data' => $categories->map(function (Kategori $category) use ($serviceCounts): array {
                    return [
                        'id' => $category->id,
                        'code' => (string) $category->kode,
                        'name' => (string) $category->nama,
                        'sub_name' => (string) ($category->sub_nama ?? ''),
                        'type' => (string) ($category->tipe ?? 'game'),
                        'category_type' => $category->categoryType ? [
                            'slug' => (string) $category->categoryType->slug,
                            'name' => (string) $category->categoryType->name,
                        ] : null,
                        'requires_user_id' => (bool) ($category->require_user_id ?? true),
                        'requires_zone_id' => (bool) ($category->server_id ?? false),
                        'service_count' => (int) ($serviceCounts[$category->id] ?? 0),
                        'thumbnail' => $category->thumbnail,
                    ];
                })->values()->all(),
            ];
        });
    }

    public function products(?User $user = null, array $filters = []): array
    {
        return $this->categories($user, $filters);
    }

    public function categoriesWithServices(?User $user = null, array $filters = []): array
    {
        $search = strtolower(trim((string) ($filters['q'] ?? '')));
        $typeSlug = strtolower(trim((string) ($filters['type'] ?? $filters['category_type'] ?? '')));
        $serviceSearch = strtolower(trim((string) ($filters['service_q'] ?? $filters['service'] ?? '')));
        $role = (string) ($user?->role ?? 'Guest');
        $cacheKey = 'gateway:categories-with-services:v1:' . sha1(json_encode([$search, $typeSlug, $serviceSearch, $role], JSON_UNESCAPED_SLASHES));

        return Cache::remember($cacheKey, 300, function () use ($search, $typeSlug, $serviceSearch, $user): array {
            $categories = Kategori::query()
                ->with('categoryType:id,name,slug,sort,icon')
                ->where('status', 'active')
                ->when($typeSlug !== '', function ($query) use ($typeSlug): void {
                    $query->whereHas('categoryType', function ($inner) use ($typeSlug): void {
                        $inner->where('slug', $typeSlug);
                    });
                })
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($inner) use ($search): void {
                        $inner->whereRaw('LOWER(nama) LIKE ?', ["%{$search}%"])
                            ->orWhereRaw('LOWER(kode) LIKE ?', ["%{$search}%"]);
                    });
                })
                ->orderBy('nama')
                ->get();

            $services = Layanan::query()
                ->whereIn('kategori_id', $categories->pluck('id'))
                ->where('status', 'available')
                ->when($serviceSearch !== '', function ($query) use ($serviceSearch): void {
                    $query->whereRaw('LOWER(layanan) LIKE ?', ["%{$serviceSearch}%"]);
                })
                ->orderBy('harga_member')
                ->orderBy('layanan')
                ->get()
                ->groupBy('kategori_id');

            $items = $categories
                ->map(function (Kategori $category) use ($services, $user): array {
                    $categoryServices = $services->get($category->id, collect())
                        ->map(fn (Layanan $service): array => $this->servicePayload($service, $category, $user))
                        ->values()
                        ->all();

                    return [
                        ...$this->categoryPayload($category, count($categoryServices)),
                        'services' => $categoryServices,
                    ];
                })
                ->filter(fn (array $category): bool => count($category['services']) > 0)
                ->values()
                ->all();

            return [
                'ok' => true,
                'message' => 'Kategori dan layanan berhasil dimuat.',
                'data' => $items,
            ];
        });
    }

    public function servicesQuery(?User $user = null, array $filters = []): array
    {
        $categoryCode = strtolower(trim((string) ($filters['category'] ?? '')));
        $serviceId = (int) ($filters['service_id'] ?? 0);
        $search = strtolower(trim((string) ($filters['q'] ?? '')));

        if ($categoryCode === '') {
            return [
                'ok' => false,
                'error_code' => 'CATEGORY_REQUIRED',
                'message' => 'Query parameter "category" wajib diisi.',
                'data' => [],
            ];
        }

        $category = Kategori::query()
            ->where('kode', $categoryCode)
            ->where('status', 'active')
            ->first();

        if (! $category) {
            return [
                'ok' => false,
                'error_code' => 'CATEGORY_NOT_FOUND',
                'message' => 'Kategori tidak ditemukan atau tidak aktif.',
                'data' => [],
            ];
        }

        $query = Layanan::query()
            ->where('kategori_id', $category->id)
            ->where('status', 'available');

        if ($serviceId > 0) {
            $query->where('id', $serviceId);
        }

        if ($search !== '') {
            $query->whereRaw('LOWER(layanan) LIKE ?', ["%{$search}%"]);
        }

        $services = $query
            ->orderBy('harga_member')
            ->orderBy('layanan')
            ->get();

        if ($serviceId > 0 && $services->isEmpty()) {
            return [
                'ok' => false,
                'error_code' => 'SERVICE_NOT_FOUND',
                'message' => 'Layanan tidak ditemukan.',
                'data' => [],
            ];
        }

        return [
            'ok' => true,
            'message' => $serviceId > 0 ? 'Layanan berhasil dimuat.' : 'Daftar layanan berhasil dimuat.',
            'data' => [
                'category' => [
                    'code' => (string) $category->kode,
                    'name' => (string) $category->nama,
                    'type' => (string) ($category->tipe ?? 'game'),
                    'requires_user_id' => (bool) ($category->require_user_id ?? true),
                    'requires_zone_id' => (bool) ($category->server_id ?? false),
                ],
                'services' => $services->map(fn (Layanan $service): array => $this->servicePayload($service, $category, $user))->values()->all(),
            ],
        ];
    }

    public function services(string $categoryCode, ?User $user = null, array $filters = []): array
    {
        $category = Kategori::query()
            ->with('categoryType:id,name,slug,sort,icon')
            ->where('kode', strtolower(trim($categoryCode)))
            ->where('status', 'active')
            ->first();

        if (! $category) {
            return [
                'ok' => false,
                'error_code' => 'CATEGORY_NOT_FOUND',
                'message' => 'Produk tidak ditemukan atau tidak aktif.',
                'data' => [],
            ];
        }

        $search = strtolower(trim((string) ($filters['q'] ?? '')));

        $services = Layanan::query()
            ->where('kategori_id', $category->id)
            ->where('status', 'available')
            ->when($search !== '', function ($query) use ($search): void {
                $query->whereRaw('LOWER(layanan) LIKE ?', ["%{$search}%"]);
            })
            ->orderBy('harga_member')
            ->orderBy('layanan')
            ->get();

        return [
            'ok' => true,
            'message' => 'Layanan berhasil dimuat.',
            'data' => [
                'category' => $this->categoryPayload($category, $services->count()),
                'services' => $services->map(fn (Layanan $service): array => $this->servicePayload($service, $category, $user))->values()->all(),
            ],
        ];
    }

    public function serviceById(int $serviceId, ?User $user = null): array
    {
        $service = Layanan::query()
            ->whereKey($serviceId)
            ->where('status', 'available')
            ->first();

        if (! $service) {
            return [
                'ok' => false,
                'error_code' => 'SERVICE_NOT_FOUND',
                'message' => 'Layanan tidak ditemukan atau tidak tersedia.',
                'data' => null,
            ];
        }

        $category = Kategori::query()
            ->with('categoryType:id,name,slug,sort,icon')
            ->whereKey($service->kategori_id)
            ->first();

        if (! $category || $category->status !== 'active') {
            return [
                'ok' => false,
                'error_code' => 'CATEGORY_NOT_FOUND',
                'message' => 'Kategori layanan tidak ditemukan atau tidak aktif.',
                'data' => null,
            ];
        }

        $payload = $this->servicePayload($service, $category, $user);
        $payload['category'] = $this->categoryPayload($category, 1);

        return [
            'ok' => true,
            'message' => 'Detail layanan berhasil dimuat.',
            'data' => $payload,
        ];
    }

    private function categoryPayload(Kategori $category, int $serviceCount): array
    {
        return [
            'id' => $category->id,
            'code' => (string) $category->kode,
            'name' => (string) $category->nama,
            'sub_name' => (string) ($category->sub_nama ?? ''),
            'type' => (string) ($category->tipe ?? 'game'),
            'category_type' => $category->categoryType ? [
                'slug' => (string) $category->categoryType->slug,
                'name' => (string) $category->categoryType->name,
            ] : null,
            'requires_user_id' => (bool) ($category->require_user_id ?? true),
            'requires_zone_id' => (bool) ($category->server_id ?? false),
            'service_count' => $serviceCount,
            'thumbnail' => $category->thumbnail,
        ];
    }

    private function servicePayload(Layanan $service, Kategori $category, ?User $user): array
    {
        return [
            'service_id' => $service->id,
            'name' => (string) $service->layanan,
            'price' => $this->rolePrice($service, $user),
            'status' => (string) $service->status,
            'category_code' => (string) $category->kode,
            'category_name' => (string) $category->nama,
            'is_flash_sale' => (bool) $service->is_flash_sale,
            'flash_price' => (int) $service->harga_flash_sale,
            'flash_stock' => (int) $service->stock_flash_sale,
            'flash_expires_at' => $service->expired_flash_sale?->toIso8601String(),
        ];
    }

    private function rolePrice(Layanan $service, ?User $user): int
    {
        $amount = match ($user?->role ?? 'Guest') {
            'Member' => $service->harga_member,
            'Platinum' => $service->harga_platinum,
            'Gold', 'Admin' => $service->harga_gold,
            default => $service->harga_member,
        };

        return max(0, (int) round((float) $amount));
    }
}
