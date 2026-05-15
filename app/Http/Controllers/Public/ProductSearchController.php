<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ProductSearchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => 'required|string|min:2|max:80',
        ]);

        $query = Str::of((string) $validated['q'])->trim()->lower()->squish()->value();

        if ($query === '') {
            return response()->json([
                'items' => [],
            ]);
        }

        $version = (int) Cache::get('public:search:categories:version', 1);
        $cacheKey = 'public:search:categories:v2:' . $version . ':' . md5($query);

        $items = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($query) {
            $results = Kategori::query()
                ->select(['id', 'nama', 'sub_nama', 'thumbnail', 'kode'])
                ->where('status', 'active')
                ->where(function ($builder) use ($query) {
                    $builder
                        ->whereRaw('LOWER(nama) LIKE ?', ["%{$query}%"])
                        ->orWhereRaw('LOWER(COALESCE(sub_nama, "")) LIKE ?', ["%{$query}%"])
                        ->orWhereRaw('LOWER(kode) LIKE ?', ["%{$query}%"]);
                })
                ->orderBy('nama')
                ->limit(8)
                ->get();

            return $results->map(function (Kategori $category) {
                $thumbnail = trim((string) $category->thumbnail);
                if (! str_starts_with($thumbnail, 'http://') && ! str_starts_with($thumbnail, 'https://')) {
                    $thumbnail = '/' . ltrim($thumbnail, '/');
                }

                return [
                    'slug' => (string) $category->kode,
                    'name' => (string) $category->nama,
                    'subtitle' => (string) ($category->sub_nama ?: 'Produk Topup'),
                    'thumbnail' => $thumbnail,
                ];
            })->values()->all();
        });

        return response()->json([
            'items' => $items,
        ])->header('Cache-Control', 'private, max-age=60');
    }
}
