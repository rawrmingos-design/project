<?php

namespace App\Http\Controllers;

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
        $data = Cache::remember('recent_purchases_toast', 60, function () {
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

                // Return raw path — client-side JS will prepend origin
                return [
                    'item'     => $p->layanan,
                    'name'     => $buyer ?: 'Seseorang',
                    'image'    => $thumbnail ? '/' . ltrim($thumbnail, '/') : '/assets/logo/favicon.webp',
                    'time_ago' => $p->created_at ? $p->created_at->diffForHumans() : 'baru saja',
                ];
            });
        });

        return response()->json($data);
    }
}
