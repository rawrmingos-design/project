<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pembelian;
use App\Models\Kategori;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class RecentPurchasesController extends Controller
{
    /**
     * Return the 20 most recent successful purchases for live sales FOMO toast.
     * Data is cached for 60 seconds to avoid hammering the DB.
     */
    public function index(): JsonResponse
    {
        $data = Cache::remember('recent_purchases_toast_api', 60, function () {
            $purchases = Pembelian::whereIn('status', ['Success', 'Sukses'])
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get(['layanan', 'nickname', 'user_id', 'created_at']);

            // Keyed by nama for thumbnail lookup
            $kategoris = Kategori::pluck('thumbnail', 'nama');

            return $purchases->map(function ($p) use ($kategoris) {
                // Match kategori by checking if layanan contains kategori name
                $thumbnail = null;
                foreach ($kategoris as $nama => $thumb) {
                    if (stripos($p->layanan, $nama) !== false) {
                        $thumbnail = $thumb;
                        break;
                    }
                }

                // Masked buyer name for privacy
                $buyer = $p->nickname ?: $p->user_id;
                if (strlen($buyer) > 4) {
                    $buyer = substr($buyer, 0, 2) . str_repeat('*', strlen($buyer) - 4) . substr($buyer, -2);
                }

                return [
                    'item'     => $this->sanitizeUtf8($p->layanan),
                    'name'     => $this->sanitizeUtf8($buyer ?: 'Seseorang'),
                    'image'    => $this->sanitizeUtf8($thumbnail ? '/' . ltrim($thumbnail, '/') : '/assets/logo/favicon.webp'),
                    'time_ago' => $this->sanitizeUtf8($p->created_at ? $p->created_at->diffForHumans() : 'baru saja'),
                ];
            })->map(fn (array $item) => $this->sanitizeRecursive($item))->values()->all();
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_SLASHES);
    }

    private function sanitizeUtf8(?string $value): string
    {
        $value = (string) ($value ?? '');

        if ($value === '') {
            return '';
        }

        if (mb_check_encoding($value, 'UTF-8')) {
            return $value;
        }

        $clean = @iconv('UTF-8', 'UTF-8//IGNORE', $value);

        return $clean !== false ? $clean : mb_convert_encoding($value, 'UTF-8', 'UTF-8');
    }

    private function sanitizeRecursive(array $payload): array
    {
        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $payload[$key] = $this->sanitizeRecursive($value);
                continue;
            }

            if (is_string($value) || is_null($value)) {
                $payload[$key] = $this->sanitizeUtf8($value);
            }
        }

        return $payload;
    }
}
