<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use App\Tenancy\TenantContext;

class HomeController extends Controller
{
    public function __invoke(TenantContext $tenantContext)
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
        ]);
    }
}
