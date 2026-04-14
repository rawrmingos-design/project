<?php

namespace App\Http\Controllers;

use App\Models\Layanan;
use App\Models\Pembelian;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class RecentPurchasesController extends Controller
{
    public function index(): JsonResponse
    {
        $data = Cache::remember('recent_purchases_toast_v1', 60, function (): Collection {
            $purchases = Pembelian::query()
                ->whereIn('status', ['Success', 'Sukses'])
                ->latest('updated_at')
                ->limit(20)
                ->get([
                    'layanan',
                    'nickname',
                    'username',
                    'user_id',
                    'updated_at',
                    'active_layanan_id',
                ]);

            if ($purchases->isEmpty()) {
                return collect();
            }

            $activeLayananIds = $purchases
                ->pluck('active_layanan_id')
                ->filter()
                ->unique()
                ->values();

            $layananNames = $purchases
                ->pluck('layanan')
                ->filter()
                ->unique()
                ->values();

            $layanans = $this->loadRelatedLayanans($activeLayananIds, $layananNames);
            $layanansById = $layanans->keyBy('id');
            $layanansByName = $layanans->keyBy(fn (Layanan $layanan): string => (string) $layanan->layanan);

            return $purchases->map(function (Pembelian $pembelian) use ($layanansById, $layanansByName): array {
                $layanan = $layanansById->get($pembelian->active_layanan_id)
                    ?? $layanansByName->get((string) $pembelian->layanan);

                $thumbnail = $layanan?->kategori?->thumbnail;

                return [
                    'item' => (string) ($pembelian->layanan ?: 'Item'),
                    'name' => $this->maskBuyerName(
                        (string) ($pembelian->nickname ?: $pembelian->username ?: $pembelian->user_id ?: 'Seseorang')
                    ),
                    'image' => $thumbnail ? '/' . ltrim((string) $thumbnail, '/') : null,
                    'time_ago' => optional($pembelian->updated_at)->diffForHumans() ?: 'Baru saja',
                ];
            })->values();
        });

        return response()->json($data);
    }

    private function loadRelatedLayanans(Collection $activeLayananIds, Collection $layananNames): Collection
    {
        $query = Layanan::query()
            ->with(['kategori:id,nama,thumbnail']);

        if ($activeLayananIds->isNotEmpty()) {
            $query->whereIn('id', $activeLayananIds);

            if ($layananNames->isNotEmpty()) {
                $query->orWhereIn('layanan', $layananNames);
            }

            return $query->get();
        }

        if ($layananNames->isEmpty()) {
            return collect();
        }

        return $query
            ->whereIn('layanan', $layananNames)
            ->get();
    }

    private function maskBuyerName(string $name): string
    {
        $name = trim($name);

        if ($name === '') {
            return 'Seseorang';
        }

        $length = mb_strlen($name);

        if ($length <= 3) {
            return mb_substr($name, 0, 1) . str_repeat('*', max(0, $length - 1));
        }

        $visibleCount = max(1, (int) floor($length / 2));

        return mb_substr($name, 0, $visibleCount) . str_repeat('*', max(1, $length - $visibleCount));
    }
}
