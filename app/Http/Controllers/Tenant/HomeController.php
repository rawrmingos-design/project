<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use App\Tenancy\TenantContext;
use App\Tenancy\TenantPricingService;

class HomeController extends Controller
{
    public function __invoke(TenantContext $tenantContext, TenantPricingService $pricing)
    {
        $tenant = $tenantContext->get();

        abort_unless($tenant, 404);

        $categories = Kategori::query()
            ->select('id', 'nama', 'sub_nama', 'kode', 'thumbnail', 'banner')
            ->orderBy('nama')
            ->limit(24)
            ->get();

        return view('tenant.home', [
            'tenant' => $tenant,
            'categories' => $categories,
            // Angka NYATA milik tenant ini, bukan teks generik. Sebelumnya
            // halaman ini menulis "modal Gold + markup toko" — sisa model lama
            // yang membuat tenant mengira modalnya = `harga_gold`. Tenant lalu
            // menghitung untungnya sendiri dengan basis yang salah dan
            // komplain "kok profit saya beda" ketika angkanya tidak cocok.
            'pricing' => [
                'platform_markup_percent' => $pricing->platformMarkupPercent($tenant),
                'tenant_markup_percent' => $pricing->effectiveMarkupPercent($tenant),
            ],
        ]);
    }
}
