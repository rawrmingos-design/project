<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Kategori;
use App\Models\Berita;
use App\Models\Tabpills;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class IndexController extends Controller
{
    protected function sanitizeHomepageBanners(Collection $banners, ?Berita $popup = null): Collection
    {
        return $banners
            ->filter(function ($item) use ($popup) {
                if (! $item || ($item->tipe ?? null) !== 'banner') {
                    return false;
                }

                $path = trim((string) ($item->path ?? ''));

                if ($path === '') {
                    return false;
                }

                if ($popup) {
                    if (($popup->id ?? null) === ($item->id ?? null)) {
                        return false;
                    }

                    if (trim((string) ($popup->path ?? '')) === $path) {
                        return false;
                    }
                }

                return true;
            })
            ->values();
    }

    protected function beritaSortColumn(): string
    {
        return Schema::hasColumn('beritas', 'urutan') ? 'urutan' : 'id';
    }

    protected function orderedMlbbCategories(array $customOrder)
    {
        $query = Kategori::whereIn('id', $customOrder);

        if (DB::connection()->getDriverName() !== 'sqlite') {
            return $query
                ->orderByRaw("FIELD(id, " . implode(',', $customOrder) . ")")
                ->get()
                ->map(function ($item) {
                    $item->tipe = "mlbb";
                    return $item;
                });
        }

        return $query
            ->get()
            ->sortBy(function ($item) use ($customOrder) {
                $position = array_search($item->id, $customOrder, true);

                return $position === false ? PHP_INT_MAX : $position;
            })
            ->values()
            ->map(function ($item) {
                $item->tipe = "mlbb";
                return $item;
            });
    }

     public function create()
    {
        // Cache TTL: 5 minutes (300 seconds)
        $ttl = 300;

        // $customOrder = [8507, 8639, 8640, 8641, 8644, 8663, 8646];
        $customOrder = [8507, 8639, 8640, 8641, 8644, 8664];
        
        $kategori = Cache::remember('kategori_populer_home', $ttl, function () {
            return Kategori::query()
                ->select(['id', 'nama', 'sub_nama', 'thumbnail', 'kode', 'tipe'])
                ->where('status', 'active')
                ->where('tipe', 'populer')
                ->orderBy('id')
                ->get();
        });

        $mlbb = Cache::remember('kategori_mlbb', $ttl, function () use ($customOrder) {
            return $this->orderedMlbbCategories($customOrder);
        });

        $banner = Cache::remember('banner_list', $ttl, function () {
            $sortColumn = $this->beritaSortColumn();

            return Berita::where('tipe', 'banner')
                ->orderBy($sortColumn)
                ->orderByDesc('id')
                ->get();
        });

        $logoheader = Cache::remember('logo_header', $ttl, function () {
            return Berita::where('tipe', 'logoheader')->latest()->first();
        });

        $logofooter = Cache::remember('logo_footer', $ttl, function () {
            return Berita::where('tipe', 'logofooter')->latest()->first();
        });

        $popup = Cache::remember('popup_latest', $ttl, function () {
            $sortColumn = $this->beritaSortColumn();

            return Berita::where('tipe', 'popup')
                ->orderBy($sortColumn)
                ->orderByDesc('id')
                ->first();
        });

        $banner = $this->sanitizeHomepageBanners($banner, $popup);

        $pay_method = Cache::remember('payment_methods_home_v1:' . \App\Support\PaymentCatalogAccess::currentTenantId(), $ttl, function () {
            return app(\App\Services\PaymentMethodCatalogService::class)->getVisibleMethods();
        });

        // Flashsale, CategoryTypes, and Articles are now handled by Livewire components
        // No need to fetch them here anymore
        
        return view('template.id.index', compact('kategori', 'mlbb', 'banner', 'logoheader', 'logofooter', 'popup', 'pay_method'));
}


    
    public function cariIndex(Request $request)
    {
        if ($request->ajax()) {
            $requestData = $request->validate([
                'data' => 'required|string|min:2|max:80',
            ]);

            $keyword = Str::lower(trim((string) $requestData['data']));
            $version = (int) Cache::get('public:search:categories:version', 1);
            $cacheKey = 'public:search:legacy:v3:' . $version . ':' . md5($keyword);

            $data = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($keyword) {
                return Kategori::query()
                    ->select(['nama', 'kode', 'thumbnail'])
                    ->where('status', 'active')
                    ->where(function ($query) use ($keyword) {
                        $query
                            ->whereRaw('LOWER(nama) LIKE ?', ["%{$keyword}%"])
                            ->orWhereRaw('LOWER(COALESCE(sub_nama, "")) LIKE ?', ["%{$keyword}%"])
                            ->orWhereRaw('LOWER(kode) LIKE ?', ["%{$keyword}%"]);
                    })
                    ->orderBy('nama')
                    ->limit(6)
                    ->get();
            });

            $res = '';

            foreach ($data as $d) {
                $thumbnail = trim((string) $d->thumbnail);
                if (! str_starts_with($thumbnail, 'http://') && ! str_starts_with($thumbnail, 'https://')) {
                    $thumbnail = '/' . ltrim($thumbnail, '/');
                }

                $res .= '
                    <li class="p-2 dropdown-item">
                        <a href="' . url('/id') . '/' . e($d->kode) . '" class="text-white">
                            <div class="flex cursor-pointer select-none items-center rounded-md px-3 py-2" role="option" tabindex="-1" aria-selected="false">
                                <img alt="' . e($d->nama) . '" class="aspect-square w-24 rounded-2xl object-cover" src="' . e($thumbnail) . '" loading="lazy" decoding="async" />
                                <span class="ml-3 flex-auto truncate">' . e($d->nama) . '</span>
                            </div>
                        </a>
                    </li>';
            }

            return $res;
        }
    }

}
