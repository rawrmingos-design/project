<?php

namespace App\Services;

use App\Helpers\HtmlSanitizer;
use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Method;
use App\Models\Paket;
use App\Models\PaketLayanan;
use App\Support\CustomInputDefaults;
use App\Support\GtmDataLayerBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PublicOrderPageDataService
{
    public function isSupportedForInertia(Kategori $kategori): bool
    {
        return in_array($kategori->tipe, ['game', 'populer', 'voucher', 'joki', 'jokigendong', 'vilogml'], true);
    }

    public function getData(Kategori $kategori): array
    {
        app(CustomInputDefaults::class)->ensureExists($kategori);

        $role = Auth::check() ? Auth::user()->role : 'Guest';
        $cacheKey = "inertia_order_page:v2:{$kategori->kode}:{$role}";

        return Cache::remember($cacheKey, 300, function () use ($kategori, $role) {
            $category = Kategori::query()
                ->leftJoin('custom_inputs', 'kategoris.id', '=', 'custom_inputs.kategori_id')
                ->select(
                    'custom_inputs.field_1 AS field_1',
                    'custom_inputs.field_2 AS field_2',
                    'custom_inputs.field_select_title AS field_select_title',
                    'custom_inputs.field_select AS field_select',
                    'kategoris.id',
                    'kategoris.nama',
                    'kategoris.sub_nama',
                    'kategoris.server_id',
                    'kategoris.require_user_id',
                    'kategoris.thumbnail',
                    'kategoris.kode',
                    'kategoris.deskripsi_game',
                    'kategoris.deskripsi_field',
                    'kategoris.banner',
                    'kategoris.tipe',
                    'kategoris.meta_title',
                    'kategoris.meta_description',
                    'kategoris.schema_markup'
                )
                ->where('kategoris.kode', $kategori->kode)
                ->firstOrFail();

            $products = $this->loadProducts($category->id, $role);
            $packages = $this->loadPackages($category->id, $role);
            $ratings = $this->loadRatings($category->id);
            $methods = $this->loadMethods();
            $gtmBuilder = app(GtmDataLayerBuilder::class);
            $gtmOrderItemCatalog = $this->buildGtmCatalogFromProducts($gtmBuilder, $products, $category);
            $gtmViewItemPayload = null;

            if ($gtmOrderItemCatalog !== []) {
                $gtmViewItemPayload = $gtmBuilder->buildViewItemPayload(array_values($gtmOrderItemCatalog)[0]);
            }

            return [
                'category' => [
                    'id' => $category->id,
                    'name' => $category->nama,
                    'subtitle' => $category->sub_nama,
                    'slug' => $category->kode,
                    'type' => $category->tipe,
                    'orderMode' => $this->resolveOrderMode($category->tipe),
                    'thumbnail' => '/' . ltrim((string) $category->thumbnail, '/'),
                    'banner' => '/' . ltrim((string) $category->banner, '/'),
                    'description' => $this->sanitizeCategoryDescription($category->deskripsi_game),
                    'fieldDescription' => $category->deskripsi_field,
                    'requireUserId' => (bool) ($category->require_user_id ?? true),
                    'serverId' => (bool) ($category->server_id ?? false),
                    'requiresGameValidation' => in_array($category->tipe, ['game', 'populer', 'voucher'], true),
                    'metaTitle' => $category->meta_title,
                    'metaDescription' => $category->meta_description,
                    'schemaMarkup' => $category->schema_markup,
                    'customInputs' => $this->mapCustomInputs($category),
                    'specialFields' => $this->mapSpecialFields($category->tipe),
                    'specialNotes' => $this->mapSpecialNotes($category->tipe),
                ],
                'products' => $products,
                'packages' => $packages,
                'ratings' => $ratings,
                'paymentMethods' => $methods,
                'gtm' => [
                    'viewItemPayload' => $gtmViewItemPayload,
                    'itemCatalog' => $gtmOrderItemCatalog,
                    'paymentMethods' => $this->buildGtmPaymentMethodCatalog($methods),
                ],
            ];
        });
    }

    private function sanitizeCategoryDescription(?string $description): string
    {
        $raw = trim((string) $description);

        if ($raw === '') {
            return '<p>Deskripsi kategori belum tersedia.</p>';
        }

        $normalized = $this->normalizeCategoryDescriptionHtml($raw);

        return HtmlSanitizer::cleanArticle($normalized);
    }

    private function normalizeCategoryDescriptionHtml(string $description): string
    {
        $normalized = trim($description);

        for ($attempt = 0; $attempt < 3; $attempt += 1) {
            $decoded = html_entity_decode($normalized, ENT_QUOTES | ENT_HTML5, 'UTF-8');

            if ($decoded === $normalized) {
                break;
            }

            $normalized = trim($decoded);
        }

        if (preg_match('/^<p>\s*(.*?)\s*<\/p>$/is', $normalized, $matches)) {
            $innerHtml = trim($matches[1]);

            if (preg_match('/<(p|div|ol|ul|li|h[1-6]|table|blockquote|pre|hr)\b/i', $innerHtml)) {
                return $innerHtml;
            }
        }

        return $normalized;
    }

    private function resolveOrderMode(string $type): string
    {
        return match ($type) {
            'joki', 'jokigendong', 'vilogml' => 'complex',
            default => 'standard',
        };
    }

    private function loadProducts(int $categoryId, string $role)
    {
        $query = Layanan::query()
            ->where('kategori_id', $categoryId)
            ->where('status', 'available');

        match ($role) {
            'Member' => $query->select('id', 'layanan', 'harga_member AS harga', 'is_flash_sale', 'expired_flash_sale', 'harga_flash_sale', 'stock_flash_sale', 'product_logo'),
            'Platinum' => $query->select('id', 'layanan', 'harga_platinum AS harga', 'is_flash_sale', 'expired_flash_sale', 'harga_flash_sale', 'stock_flash_sale', 'product_logo'),
            'Gold', 'Admin' => $query->select('id', 'layanan', 'harga_gold AS harga', 'is_flash_sale', 'expired_flash_sale', 'harga_flash_sale', 'stock_flash_sale', 'product_logo'),
            default => $query->select('id', 'layanan', 'harga_member AS harga', 'is_flash_sale', 'expired_flash_sale', 'harga_flash_sale', 'stock_flash_sale', 'product_logo'),
        };

        return $query
            ->orderBy('harga', 'asc')
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->layanan,
                'price' => (int) $item->harga,
                'isFlashSale' => (bool) $item->is_flash_sale,
                'flashPrice' => (int) ($item->harga_flash_sale ?? 0),
                'flashStock' => (int) ($item->stock_flash_sale ?? 0),
                'flashExpiresAt' => $item->expired_flash_sale ? Carbon::parse($item->expired_flash_sale)->toIso8601String() : null,
                'productLogo' => $item->product_logo ? '/' . ltrim((string) $item->product_logo, '/') : null,
            ])
            ->values();
    }

    private function loadPackages(int $categoryId, string $role)
    {
        $packages = [];

        foreach (Paket::all() as $paket) {
            $layananIds = $paket->layanan->pluck('id')->toArray();
            $layananData = Layanan::whereIn('id', $layananIds)
                ->where('kategori_id', $categoryId)
                ->where(function ($query) use ($role) {
                    if ($role === 'Member') {
                        $query->where('harga_member', '>', 0);
                    } elseif ($role === 'Platinum') {
                        $query->where('harga_platinum', '>', 0);
                    } elseif (in_array($role, ['Gold', 'Admin'], true)) {
                        $query->where('harga_gold', '>', 0);
                    } else {
                        $query->where('harga_member', '>', 0);
                    }
                })
                ->get();

            $items = [];
            foreach ($layananData as $layanan) {
                $paketLayanan = PaketLayanan::query()
                    ->where('paket_id', $paket->id)
                    ->where('layanan_id', $layanan->id)
                    ->first();

                if (! $paketLayanan) {
                    continue;
                }

                $price = match ($role) {
                    'Member' => $layanan->harga_member,
                    'Platinum' => $layanan->harga_platinum,
                    'Gold', 'Admin' => $layanan->harga_gold,
                    default => $layanan->harga_member,
                };

                $items[] = [
                    'id' => $layanan->id,
                    'name' => $layanan->layanan,
                    'price' => (int) $price,
                    'productLogo' => $paketLayanan->product_logo ? '/' . ltrim((string) $paketLayanan->product_logo, '/') : null,
                    'isFlashSale' => (bool) $layanan->is_flash_sale,
                    'flashPrice' => (int) ($layanan->harga_flash_sale ?? 0),
                    'flashExpiresAt' => $layanan->expired_flash_sale ? Carbon::parse($layanan->expired_flash_sale)->toIso8601String() : null,
                ];
            }

            if ($items !== []) {
                $packages[] = [
                    'name' => $paket->nama,
                    'items' => collect($items)->sortBy('price')->values()->all(),
                ];
            }
        }

        return $packages;
    }

    private function loadRatings(int $categoryId)
    {
        return DB::table('ratings')
            ->join('pembelians', 'ratings.rating_id', '=', 'pembelians.order_id')
            ->join('pembayarans', 'ratings.rating_id', '=', 'pembayarans.order_id')
            ->where('ratings.kategori_id', $categoryId)
            ->select('ratings.bintang', 'ratings.comment', 'ratings.id', 'ratings.created_at', 'pembelians.username', 'pembelians.layanan', 'pembayarans.no_pembeli')
            ->orderByDesc('ratings.id')
            ->limit(10)
            ->get()
            ->map(function ($rating) {
                $username = $rating->username ?: $rating->no_pembeli ?: 'Guest';
                $length = strlen($username);
                $maskLength = $length <= 5 ? 2 : 4;
                $start = max(0, (int) floor(($length - $maskLength) / 2));
                $masked = substr_replace($username, str_repeat('*', $maskLength), $start, $maskLength);

                return [
                    'id' => $rating->id,
                    'stars' => (int) $rating->bintang,
                    'comment' => $rating->comment,
                    'service' => $rating->layanan,
                    'author' => $masked,
                    'createdAt' => $rating->created_at,
                ];
            })
            ->values();
    }

    private function loadMethods()
    {
        return Method::query()
            ->select(['id', 'name', 'code', 'tipe', 'payment', 'images', 'fee_percent', 'fix_fee', 'min_pembelian', 'max_pembelian'])
            ->get()
            ->map(fn ($method) => [
                'id' => $method->id,
                'name' => $method->name,
                'code' => $method->code,
                'group' => $method->tipe,
                'gateway' => $method->payment,
                'image' => $method->images ? '/' . ltrim((string) $method->images, '/') : null,
                'feePercent' => (float) ($method->fee_percent ?? 0),
                'fixFee' => (float) ($method->fix_fee ?? 0),
                'minAmount' => $method->min_pembelian !== null ? (float) $method->min_pembelian : null,
                'maxAmount' => $method->max_pembelian !== null ? (float) $method->max_pembelian : null,
            ])
            ->values();
    }

    private function buildGtmCatalogFromProducts(GtmDataLayerBuilder $gtmBuilder, iterable $products, object $category): array
    {
        $catalog = [];

        foreach ($products as $product) {
            $item = $gtmBuilder->buildItem([
                'item_id' => $product['id'] ?? null,
                'item_name' => $product['name'] ?? ($category->nama ?? 'Produk'),
                'item_category' => $category->nama ?? 'Produk',
                'item_variant' => $category->tipe ?? null,
                'price' => $product['price'] ?? 0,
                'quantity' => 1,
            ]);

            if (! empty($item['item_id'])) {
                $catalog[(string) $item['item_id']] = $item;
            }
        }

        return $catalog;
    }

    private function buildGtmPaymentMethodCatalog(iterable $methods): array
    {
        $catalog = [];

        foreach ($methods as $method) {
            $code = trim((string) ($method['code'] ?? ''));

            if ($code === '') {
                continue;
            }

            $catalog[$code] = [
                'code' => $code,
                'name' => trim((string) ($method['name'] ?? $code)),
                'provider' => trim((string) ($method['gateway'] ?? '')),
            ];
        }

        return $catalog;
    }

    private function mapCustomInputs(object $category): array
    {
        $field1 = array_map('trim', array_filter(explode(',', (string) ($category->field_1 ?? ''))));
        $field2 = array_map('trim', array_filter(explode(',', (string) ($category->field_2 ?? ''))));
        $fieldSelectTitle = array_map('trim', array_filter(explode(',', (string) ($category->field_select_title ?? ''))));
        $fieldSelect = array_map('trim', array_filter(explode(',', (string) ($category->field_select ?? ''))));

        return [
            'userId' => [
                'label' => $field1[0] ?? 'User ID',
                'placeholder' => $field1[1] ?? 'Masukkan User ID',
                'type' => $field1[2] ?? 'text',
            ],
            'zone' => $field2 !== [] ? [
                'label' => $field2[0] ?? 'Server / Zone',
                'placeholder' => $field2[1] ?? 'Masukkan Server / Zone',
                'type' => $field2[2] ?? 'text',
                'isSelect' => ($field2[2] ?? null) === 'select',
                'options' => collect($fieldSelectTitle)->map(function ($title, $index) use ($fieldSelect) {
                    return [
                        'label' => $title,
                        'value' => $fieldSelect[$index] ?? $title,
                    ];
                })->values()->all(),
            ] : null,
        ];
    }

    private function mapSpecialFields(string $type): array
    {
        return match ($type) {
            'joki' => [
                [
                    'name' => 'email_joki',
                    'label' => 'Email / No. Hp',
                    'placeholder' => 'Ketikan Email/No. Hp',
                    'type' => 'text',
                    'required' => true,
                ],
                [
                    'name' => 'password_joki',
                    'label' => 'Password',
                    'placeholder' => 'Ketikan Password',
                    'type' => 'password',
                    'required' => true,
                ],
                [
                    'name' => 'loginvia_joki',
                    'label' => 'Login Via',
                    'type' => 'select',
                    'required' => true,
                    'placeholder' => 'Pilih Login Via',
                    'options' => [
                        ['label' => 'Moonton (Rekomendasi)', 'value' => 'moonton'],
                        ['label' => 'VK', 'value' => 'vk'],
                        ['label' => 'Tiktok', 'value' => 'tiktok'],
                        ['label' => 'Facebook', 'value' => 'facebook'],
                    ],
                ],
                [
                    'name' => 'nickname_joki',
                    'label' => 'Nickname',
                    'placeholder' => 'Ketikan Nickname',
                    'type' => 'text',
                    'required' => true,
                ],
                [
                    'name' => 'request_joki',
                    'label' => 'Request Hero',
                    'placeholder' => 'Min Request 3 Hero (Diusahakan)',
                    'type' => 'text',
                    'required' => true,
                ],
                [
                    'name' => 'catatan_joki',
                    'label' => 'Catatan untuk Penjoki',
                    'placeholder' => 'Catatan untuk Penjoki',
                    'type' => 'text',
                    'required' => true,
                ],
                [
                    'name' => 'qty',
                    'label' => 'Qty Joki',
                    'placeholder' => '1',
                    'type' => 'number',
                    'required' => true,
                    'min' => 1,
                    'max' => 30,
                    'defaultValue' => 1,
                ],
            ],
            'jokigendong' => [
                [
                    'name' => 'nickname_joki',
                    'label' => 'User ID & Nick Name',
                    'placeholder' => 'User ID & Nick Name',
                    'type' => 'text',
                    'required' => true,
                ],
                [
                    'name' => 'loginvia_joki',
                    'label' => 'Role',
                    'type' => 'select',
                    'required' => true,
                    'placeholder' => 'Pilih Role',
                    'options' => [
                        ['label' => 'Jungler', 'value' => 'jungler'],
                        ['label' => 'Roamer', 'value' => 'roamer'],
                        ['label' => 'Mid Lane', 'value' => 'midlaner'],
                        ['label' => 'Exp Lane', 'value' => 'explaner'],
                        ['label' => 'Gold Lane', 'value' => 'goldlaner'],
                    ],
                ],
                [
                    'name' => 'tglmain_joki',
                    'label' => 'Tanggal Main',
                    'placeholder' => 'Ketikan Tanggal Main',
                    'type' => 'text',
                    'required' => true,
                ],
                [
                    'name' => 'jambooking_joki',
                    'label' => 'Jam Booking',
                    'placeholder' => 'Ketikan Jam Booking',
                    'type' => 'text',
                    'required' => true,
                ],
                [
                    'name' => 'catatan_joki',
                    'label' => 'Catatan Untuk Penjoki',
                    'placeholder' => 'Ketikan Catatan Untuk Penjoki',
                    'type' => 'text',
                    'required' => true,
                ],
            ],
            'vilogml' => [
                [
                    'name' => 'email_joki',
                    'label' => 'Email',
                    'placeholder' => 'Ketikan Email',
                    'type' => 'text',
                    'required' => true,
                ],
                [
                    'name' => 'password_joki',
                    'label' => 'Password',
                    'placeholder' => 'Ketikan Password',
                    'type' => 'password',
                    'required' => true,
                ],
                [
                    'name' => 'loginvia_joki',
                    'label' => 'Login Via',
                    'type' => 'select',
                    'required' => true,
                    'placeholder' => 'Pilih Login Via',
                    'options' => [
                        ['label' => 'Moonton (Rekomendasi)', 'value' => 'moonton'],
                        ['label' => 'VK', 'value' => 'vk'],
                        ['label' => 'Tiktok', 'value' => 'tiktok'],
                        ['label' => 'Facebook', 'value' => 'facebook'],
                    ],
                ],
                [
                    'name' => 'nickname_joki',
                    'label' => 'User ID',
                    'placeholder' => 'Ketikan User ID',
                    'type' => 'text',
                    'required' => true,
                ],
                [
                    'name' => 'request_joki',
                    'label' => 'Server ID',
                    'placeholder' => 'Ketikan Server ID',
                    'type' => 'text',
                    'required' => true,
                ],
                [
                    'name' => 'catatan_joki',
                    'label' => 'Catatan',
                    'placeholder' => 'Catatan',
                    'type' => 'text',
                    'required' => true,
                ],
                [
                    'name' => 'qty',
                    'label' => 'Qty Joki',
                    'placeholder' => '1',
                    'type' => 'number',
                    'required' => true,
                    'min' => 1,
                    'max' => 30,
                    'defaultValue' => 1,
                ],
            ],
            default => [],
        };
    }

    private function mapSpecialNotes(string $type): array
    {
        return match ($type) {
            'joki' => [
                'Lengkapi data akun dengan benar, termasuk kapitalisasi huruf.',
                'Minimal tiga request hero agar penjoki punya alternatif saat draft.',
                'Matikan verifikasi yang menghambat login agar proses lebih cepat.',
                'Jangan login tanpa izin selama order berjalan agar joki tidak batal.',
            ],
            'jokigendong' => [
                'Isi role dan jadwal main dengan jelas agar tim joki bisa menjadwalkan sesi.',
                'Pastikan user ID dan nickname mudah dikenali oleh admin operasional.',
                'Gunakan catatan untuk target rank atau preferensi permainan.',
            ],
            'vilogml' => [
                'Masukkan akun dan server dengan lengkap agar proses tidak tertunda.',
                'Catatan dipakai untuk menjelaskan target atau instruksi tambahan.',
                'Qty dipakai untuk menentukan jumlah sesi yang dipesan.',
            ],
            default => [],
        };
    }
}
