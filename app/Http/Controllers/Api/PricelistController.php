<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Method;
use Illuminate\Support\Facades\Cache;

class PricelistController extends Controller
{
    public function index()
    {
        $ttl = 600; // 10 minutes

        $services = Cache::remember('pricelist_data_api', $ttl, function () {
            return Layanan::join('kategoris', 'layanans.kategori_id', 'kategoris.id')
                ->where('kategoris.status', 'active')
                ->orderBy('layanans.created_at', 'desc')
                ->select('layanans.*', 'kategoris.nama AS nama_kategori')
                ->get();
        });
                
        $categories = Cache::remember('kategori_all_api', $ttl, function () {
            return Kategori::all();
        });

        $payment_methods = Cache::remember('payment_methods_all_api_v1:' . \App\Support\PaymentCatalogAccess::currentTenantId(), $ttl, function () {
            return app(\App\Services\PaymentMethodCatalogService::class)->getVisibleMethods();
        });

        return response()->json([
            'success' => true,
            'data' => [
                'services' => $services,
                'categories' => $categories,
                'payment_methods' => $payment_methods,
            ]
        ]);
    }
}
