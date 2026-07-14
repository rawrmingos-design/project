<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Kategori;
use App\Models\Berita;
use App\Models\Method;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class HomeController extends Controller
{
    protected function beritaSortColumn(): string
    {
        return Schema::hasColumn('beritas', 'urutan') ? 'urutan' : 'id';
    }

    public function index()
    {
        // Cache TTL: 5 minutes (300 seconds)
        $ttl = 300;

        $customOrder = [8507, 8639, 8640, 8641, 8644, 8664];
        
        $categories = Cache::remember('kategori_active', $ttl, function () {
            return Kategori::where('status', 'active')->get();
        });

        $mlbb_categories = Cache::remember('kategori_mlbb', $ttl, function () use ($customOrder) {
            return Kategori::whereIn('id', $customOrder)
                ->orderByRaw("FIELD(id, " . implode(',', $customOrder) . ")")
                ->get()
                ->map(function ($item) {
                    $item->tipe = "mlbb";
                    return $item;
                });
        });

        $banners = Cache::remember('banner_list', $ttl, function () {
            $sortColumn = $this->beritaSortColumn();

            return Berita::where('tipe', 'banner')
                ->orderBy($sortColumn)
                ->orderByDesc('id')
                ->get();
        });

        $logo_header = Cache::remember('logo_header', $ttl, function () {
            return Berita::where('tipe', 'logoheader')->latest()->first();
        });

        $logo_footer = Cache::remember('logo_footer', $ttl, function () {
            return Berita::where('tipe', 'logofooter')->latest()->first();
        });

        $popup = Cache::remember('popup_latest', $ttl, function () {
            $sortColumn = $this->beritaSortColumn();

            return Berita::where('tipe', 'popup')
                ->orderBy($sortColumn)
                ->orderByDesc('id')
                ->first();
        });

        $payment_methods = Cache::remember('payment_methods_home_v1:' . \App\Support\PaymentCatalogAccess::currentTenantId(), $ttl, function () {
            return app(\App\Services\PaymentMethodCatalogService::class)->getVisibleMethods();
        });

        return response()->json([
            'success' => true,
            'data' => [
                'categories' => $categories,
                'mlbb_categories' => $mlbb_categories,
                'banners' => $banners,
                'logo_header' => $logo_header,
                'logo_footer' => $logo_footer,
                'popup' => $popup,
                'payment_methods' => $payment_methods,
            ]
        ]);
    }
}
