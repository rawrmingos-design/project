<?php

namespace App\Services\Gateway;

use App\Models\CategoryType;
use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Paket;
use App\Models\PaketLayanan;
use App\Models\User;
use App\Services\CheckId\CheckIdResolver;
use App\Support\CustomInputDefaults;
use Illuminate\Support\Facades\Cache;

class GatewayCatalogService
{
    public function categoryTypes(array $filters = [], bool $eligibleOnly = false, bool $hideEmpty = false): array
    {
        $search = strtolower(trim((string) ($filters['q'] ?? '')));
        $variant = $eligibleOnly ? 'bot' : ($hideEmpty ? 'sellable' : 'all');
        $cacheKey = 'gateway:category-types:v3:' . sha1($search . '|' . $variant);

        return Cache::remember($cacheKey, 300, function () use ($search, $eligibleOnly, $hideEmpty): array {
            $activeCategories = Kategori::query()
                ->select(['id', 'kode', 'category_type_id', 'tipe'])
                ->where('status', 'active')
                ->get();

            // Menu Utama bot Telegram hanya boleh memuat tipe yang benar-benar
            // ada isinya untuk BOT. Kategori yang seluruh layanannya tanpa paket
            // tidak bisa dipesan lewat bot, jadi tipe yang isinya cuma kategori
            // begitu akan terbuka KOSONG.
            if ($eligibleOnly) {
                $eligibleCodes = array_flip($this->packageableCategoryCodes());
                $activeCategories = $activeCategories->filter(
                    fn (Kategori $category): bool => isset($eligibleCodes[(string) $category->kode])
                );
            } elseif ($hideEmpty) {
                // Jalur WhatsApp: WA tidak menuntut paket (order per layanan),
                // jadi kriteria "punya paket" SALAH di sini — memakainya akan
                // menyembunyikan produk yang sah. Yang dibuang hanya tipe yang
                // TOTAL layanan tersedianya nol, karena tombolnya membuka layar
                // kosong ("Produk tidak ditemukan atau belum ada layanan.").
                $sellableCodes = array_flip($this->sellableCategoryCodes());
                $activeCategories = $activeCategories->filter(
                    fn (Kategori $category): bool => isset($sellableCodes[(string) $category->kode])
                );
            }

            $categoryCounts = $activeCategories
                ->filter(fn (Kategori $category): bool => $category->category_type_id !== null)
                ->countBy('category_type_id');

            $serviceCounts = Layanan::query()
                ->join('kategoris', 'kategoris.id', '=', 'layanans.kategori_id')
                ->when($eligibleOnly, function ($query): void {
                    $query->join('paket_layanans', 'paket_layanans.layanan_id', '=', 'layanans.id');
                })
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

    /**
     * @param  bool  $eligibleOnly  Hitung hanya layanan yang bisa dipesan lewat
     *   bot (terikat paket). Dipakai bot Telegram; biarkan `false` untuk web,
     *   yang merender tombolnya dari semua layanan.
     */
    public function categories(?User $user = null, array $filters = [], bool $eligibleOnly = false, bool $hideEmpty = false): array
    {
        $search = strtolower(trim((string) ($filters['q'] ?? '')));
        $typeSlug = strtolower(trim((string) ($filters['type'] ?? $filters['category_type'] ?? '')));
        $role = (string) ($user?->role ?? 'Guest');
        $variant = $eligibleOnly ? 'bot' : ($hideEmpty ? 'sellable' : 'all');
        $cacheKey = 'gateway:categories:v4:' . sha1(json_encode([$search, $typeSlug, $role, $variant], JSON_UNESCAPED_SLASHES));

        return Cache::remember($cacheKey, 300, function () use ($search, $typeSlug, $eligibleOnly, $hideEmpty): array {
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

            // Bot Telegram hanya bisa memesan layanan yang terikat paket, jadi
            // menu-nya menyembunyikan kategori yang seluruh layanannya tanpa
            // paket — kategori begitu membuka layar KOSONG.
            //
            // Penyaring ini HARUS memakai kriteria yang sama dengan hitungan di
            // bawah. Kalau daftarnya disaring tapi hitungannya tidak (atau
            // sebaliknya), kategori tampil dengan "12 layanan" lalu terbuka
            // kosong — lebih buruk daripada tidak disaring sama sekali.
            if ($eligibleOnly) {
                $eligibleCodes = array_flip($this->packageableCategoryCodes());
                $categories = $categories->filter(
                    fn (Kategori $category): bool => isset($eligibleCodes[(string) $category->kode])
                )->values();
            } elseif ($hideEmpty) {
                // Jalur WhatsApp: WA tidak menuntut paket (order per layanan),
                // jadi kriteria "punya paket" SALAH di sini — memakainya akan
                // menyembunyikan produk yang sah. Yang dibuang hanya kategori
                // yang NOL layanan tersedia, karena tombolnya membuka layar
                // kosong ("Produk tidak ditemukan atau belum ada layanan.").
                $sellableCodes = array_flip($this->sellableCategoryCodes());
                $categories = $categories->filter(
                    fn (Kategori $category): bool => isset($sellableCodes[(string) $category->kode])
                )->values();
            }

            // `$eligibleOnly` mengubah arti angka ini dari "jumlah layanan" jadi
            // "jumlah layanan YANG BISA DIPESAN lewat bot". Dipakai bot Telegram,
            // yang hanya bisa memesan layanan terikat paket — menghitung semua
            // layanan membuat kategori tampil "ada isinya" lalu terbuka KOSONG.
            //
            // Join ke `paket_layanans` memakai `distinct` supaya layanan yang
            // dipakai beberapa paket tetap terhitung satu.
            $serviceCounts = Layanan::query()
                ->when($eligibleOnly, function ($query): void {
                    $query->join('kategoris', 'kategoris.id', '=', 'layanans.kategori_id')
                        ->join('paket_layanans', 'paket_layanans.layanan_id', '=', 'layanans.id')
                        ->where('kategoris.status', 'active')
                        ->distinct();
                })
                ->selectRaw('layanans.kategori_id as kategori_id, COUNT(*) as aggregate')
                ->where('layanans.status', 'available')
                ->whereIn('layanans.kategori_id', $categories->pluck('id'))
                ->groupBy('layanans.kategori_id')
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
                        'requires_zone_id' => app(CheckIdResolver::class)->requiresZoneId(
                            (string) $category->kode,
                            (bool) ($category->server_id ?? false),
                        ),
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
        $cacheKey = 'gateway:categories-with-services:v2:' . sha1(json_encode([$search, $typeSlug, $serviceSearch, $role], JSON_UNESCAPED_SLASHES));

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
                    'requires_zone_id' => $this->requiresZoneId($category),
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

    private function requiresZoneId(Kategori $category): bool
    {
        return app(CheckIdResolver::class)->requiresZoneId(
            (string) $category->kode,
            (bool) $category->server_id,
        );
    }

    private function categoryPayload(Kategori $category, int $serviceCount): array
    {
        $requiresZoneId = $this->requiresZoneId($category);

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
            'requires_zone_id' => $requiresZoneId,
            'custom_inputs' => app(CustomInputDefaults::class)->inputSpecification($category, $requiresZoneId),
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

    /**
     * Paket layanan + isinya untuk satu kategori, mengikuti pola halaman order
     * storefront (`OrderController`).
     *
     * **Kenapa ada.** Halaman order storefront menampilkan produk dalam bentuk
     * grup paket ("⭐ Spesial Items", "⚡ Proses Instant") yang masing-masing
     * berisi nama layanan + harganya. Bot Telegram dulu cuma menampilkan daftar
     * layanan rata, tanpa pengelompokan itu. Method ini menyediakan bahan yang
     * sama supaya bot bisa menampilkan pola yang user sudah kenal dari web.
     *
     * **Kenapa layanan TANPA paket tidak dikembalikan.** Ini perilaku storefront
     * apa adanya: begitu kategori punya paket, `Order.jsx` merender
     * `packages` dan mengabaikan daftar `products` yang rata. Layanan yang tidak
     * terhubung ke paket mana pun memang tidak bisa dipesan di web, jadi bot
     * ikut tidak menampilkannya — bukan kehilangan data, tapi menyesuaikan diri
     * dengan katalog yang memang berlaku.
     *
     * **Kenapa paket kosong dibuang.** Paket tanpa satu pun layanan tersedia di
     * kategori ini hanya menghasilkan header tanpa isi.
     *
     * @return array<int, array{nama: string, layanan: array<int, array<string, mixed>>}>
     */
    public function servicePackages(string $categoryCode, ?User $user = null): array
    {
        $category = Kategori::query()
            ->where('kode', strtolower(trim($categoryCode)))
            ->where('status', 'active')
            ->first();

        if (! $category) {
            return [];
        }

        // Nama kategori dipakai berulang di samping tiap harga supaya tiap kartu
        // bisa dibaca berdiri sendiri tanpa menengok header grup.
        $categoryName = (string) $category->nama;

        $packages = [];

        foreach (Paket::query()->orderBy('id')->get() as $paket) {
            $layananIds = $paket->layanan->pluck('id')->all();

            if ($layananIds === []) {
                continue;
            }

            $rows = Layanan::query()
                ->whereIn('id', $layananIds)
                ->where('kategori_id', $category->id)
                ->where('status', 'available')
                ->orderBy('harga_member')
                ->orderBy('layanan')
                ->get();

            if ($rows->isEmpty()) {
                continue;
            }

            $items = [];

            foreach ($rows as $service) {
                // `product_logo` melekat pada PASANGAN paket+layanan, bukan pada
                // layanannya: satu layanan bisa dipakai beberapa paket dengan
                // logo berbeda. Karena itu dibaca per pasangan, bukan dari model.
                $logo = PaketLayanan::query()
                    ->where('paket_id', $paket->id)
                    ->where('layanan_id', $service->id)
                    ->value('product_logo');

                $items[] = [
                    'service_id' => (int) $service->id,
                    'name' => (string) $service->layanan,
                    'category_name' => $categoryName,
                    'price' => $this->rolePrice($service, $user),
                    'product_logo' => $logo === null || $logo === '' ? null : (string) $logo,
                ];
            }

            $packages[] = [
                'nama' => (string) $paket->nama,
                'layanan' => $items,
            ];
        }

        return $packages;
    }

    /**
     * Layanan yang bisa dipesan lewat bot, RATA dalam satu daftar.
     *
     * Bentuk rata ini disengaja: bot hanya menampilkan NAMA LAYANAN, tanpa
     * nama paketnya. Nama paket diulang di tiap kartu ("⚡ Proses Instant"
     * tercetak 48 kali untuk Free Fire) tidak menambah informasi apa pun
     * setelah user berada di dalam kategorinya.
     *
     * Urutannya: paket yang namanya memuat "spesial" dipin ke ATAS, sisanya
     * mengikuti urutan harga. Pin ini bukan hiasan — isi paket spesial beda
     * kelas (mis. `WEEKLY DIAMOND PASS` di Mobile Legends, `Blessing of the
     * Welkin Moon` di Genshin) dan harganya jauh di atas paket lain, jadi
     * kalau daftar diurutkan harga murni item itu terkubur di halaman
     * belakang dan praktis tidak bisa ditemukan.
     *
     * @return array<int, array{service_id: int, name: string, price: int}>
     */
    public function packagedServices(string $categoryCode, ?User $user = null): array
    {
        $packages = $this->servicePackages($categoryCode, $user);

        if ($packages === []) {
            return [];
        }

        $special = [];
        $regular = [];

        foreach ($packages as $package) {
            if (str_contains(mb_strtolower((string) $package['nama']), 'spesial')) {
                $special[] = $package;
            } else {
                $regular[] = $package;
            }
        }

        $flat = [];
        $seen = [];

        foreach (array_merge($special, $regular) as $package) {
            foreach ((array) $package['layanan'] as $item) {
                $id = (int) $item['service_id'];

                // Satu layanan bisa dipakai beberapa paket. Dedupe supaya tidak
                // muncul dua kali, dan yang menang adalah paket TERATAS —
                // karena itu pin "spesial" juga menentukan harga yang tampil.
                if (isset($seen[$id])) {
                    continue;
                }

                $seen[$id] = true;
                $flat[] = [
                    'service_id' => $id,
                    'name' => (string) $item['name'],
                    'price' => (int) $item['price'],
                ];
            }
        }

        return $flat;
    }

    /**
     * Kode kategori yang punya MINIMAL satu layanan berpaket.
     *
     * Dipakai bot Telegram untuk menyembunyikan kategori yang akan tampil
     * KOSONG: bot hanya merender layanan yang terikat paket, jadi kategori
     * seperti `undawn` atau `pulsa-telkomsel` (semua layanannya tanpa paket)
     * membuka layar kosong tanpa satu pun pilihan.
     *
     * SENGAJA satu query untuk SEMUA kategori. Memanggil `servicePackages()`
     * per kategori berarti belasan query per kategori, dan puluhan kategori
     * pada satu tipe — ratusan query hanya untuk memutuskan apa yang dipajang.
     */
    public function packageableCategoryCodes(): array
    {
        return Layanan::query()
            ->join('kategoris', 'kategoris.id', '=', 'layanans.kategori_id')
            ->join('paket_layanans', 'paket_layanans.layanan_id', '=', 'layanans.id')
            ->where('layanans.status', 'available')
            ->where('kategoris.status', 'active')
            ->distinct()
            ->pluck('kategoris.kode')
            ->map(static fn (mixed $code): string => (string) $code)
            ->values()
            ->all();
    }

    /**
     * Kategori yang punya MINIMAL satu layanan tersedia — tanpa syarat paket.
     *
     * Dipakai jalur WhatsApp: WA memesan per layanan (bukan lewat paket seperti
     * Telegram), jadi kategori tanpa paket tetap SAH. Tanpa penyaring ini, WA
     * menampilkan tombol produk yang membuka layar kosong.
     *
     * Kriteria sengaja dibuat sama PERSIS dengan penghitung di `categories()` /
     * `categoryTypes()` (`layanans.status = 'available'`, `kategoris.status =
     * 'active'`): kalau daftarnya disaring tapi hitungannya tidak, produk tampil
     * dengan angka layanan padahal isinya kosong — lebih buruk daripada tidak
     * disaring sama sekali.
     */
    private function sellableCategoryCodes(): array
    {
        return Layanan::query()
            ->join('kategoris', 'kategoris.id', '=', 'layanans.kategori_id')
            ->where('layanans.status', 'available')
            ->where('kategoris.status', 'active')
            ->distinct()
            ->pluck('kategoris.kode')
            ->map(static fn (mixed $code): string => (string) $code)
            ->values()
            ->all();
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
