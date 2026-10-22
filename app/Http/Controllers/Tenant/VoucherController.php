<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Layanan;
use App\Models\Tenant;
use App\Models\Voucher;
use App\Tenancy\TenantContext;
use App\Tenancy\TenantPricingService;
use App\Tenancy\TierEntitlements;
use Illuminate\Http\Request;

/**
 * Kupon milik tenant, dikelola pemilik toko dari situsnya sendiri.
 *
 * Kenapa bukan panel Filament: `User::canAccessPanel()` hanya mengizinkan
 * `role === 'Admin'`, jadi `tenant_owner` tidak bisa masuk panel. Halaman ini
 * memakai route grup `tenant.` yang sudah ada, dengan penjagaan pemilik.
 *
 * Diskon kupon ditanggung TENANT (lihat OrderController::
 * applyTenantMarginAfterDiscount), jadi batas amannya pun dihitung dari margin
 * tenant sendiri dan ditampilkan di form — supaya dia tidak salah pasang angka
 * lalu ordernya ditolak saat customer mau bayar.
 */
class VoucherController extends Controller
{
    public function index(Request $request, TenantContext $tenantContext, TierEntitlements $entitlements)
    {
        $tenant = $this->authorizeOwner($request, $tenantContext, $entitlements);

        return view('tenant.vouchers', [
            'tenant' => $tenant,
            'vouchers' => Voucher::query()
                ->ownedBy($tenant->id)
                ->orderByDesc('id')
                ->get(),
            'layanans' => Layanan::query()
                ->where('status', 'available')
                ->orderBy('layanan')
                ->limit(300)
                ->get(['id', 'layanan']),
            'batasMargin' => $this->marginCeiling($tenant),
        ]);
    }

    public function store(Request $request, TenantContext $tenantContext, TierEntitlements $entitlements)
    {
        $tenant = $this->authorizeOwner($request, $tenantContext, $entitlements);

        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9_-]+$/'],
            'promo' => ['required', 'numeric', 'min:0.01', 'max:100'],
            'stock' => ['required', 'integer', 'min:1', 'max:1000000'],
            'mintrx' => ['nullable', 'integer', 'min:0'],
            'max_potongan' => ['nullable', 'integer', 'min:0'],
            'max_uses_per_user' => ['nullable', 'integer', 'min:1'],
            'layanan_id' => ['nullable', 'integer', 'exists:layanans,id'],
            'expired_at' => ['nullable', 'date', 'after:now'],
        ], [
            'kode.regex' => 'Kode hanya boleh huruf, angka, garis bawah, dan strip.',
            'promo.max' => 'Promo maksimal 100%.',
        ]);

        // Keunikan per cakupan: tenant boleh memakai kode yang sama dengan
        // platform maupun tenant lain, karena kuponnya tidak pernah bertemu.
        // Yang dilarang hanya duplikat di dalam toko itu sendiri.
        $bentrok = Voucher::query()
            ->ownedBy($tenant->id)
            ->whereRaw('LOWER(kode) = ?', [strtolower($validated['kode'])])
            ->exists();

        if ($bentrok) {
            return back()
                ->withErrors(['kode' => 'Kode ini sudah dipakai. Pilih kode lain.'])
                ->withInput();
        }

        Voucher::create([
            'tenant_id' => $tenant->id,
            'layanan_id' => $validated['layanan_id'] ?? null,
            'kode' => $validated['kode'],
            'promo' => $validated['promo'],
            'stock' => $validated['stock'],
            'mintrx' => $validated['mintrx'] ?? 0,
            'max_potongan' => $validated['max_potongan'] ?? 0,
            'max_uses_per_user' => $validated['max_uses_per_user'] ?? null,
            'expired_at' => $validated['expired_at'] ?? null,
        ]);

        return redirect()
            ->route('tenant.vouchers')
            ->with('success', 'Kupon dibuat dan hanya berlaku di toko Anda.');
    }

    public function destroy(Request $request, TenantContext $tenantContext, TierEntitlements $entitlements, int $id)
    {
        $tenant = $this->authorizeOwner($request, $tenantContext, $entitlements);

        // Scope tenant: kupon milik tenant lain tidak boleh terhapus dari sini.
        $voucher = Voucher::query()->ownedBy($tenant->id)->whereKey($id)->firstOrFail();

        $voucher->delete();

        return redirect()->route('tenant.vouchers')->with('success', 'Kupon dihapus.');
    }

    private function authorizeOwner(Request $request, TenantContext $tenantContext, TierEntitlements $entitlements): Tenant
    {
        $tenant = $tenantContext->get();
        abort_unless($tenant, 404);
        abort_unless((int) ($request->user()?->id) === (int) $tenant->owner_user_id, 403);

        // Gate di server: kupon adalah fitur paket, bukan sekadar menu.
        $entitlements->authorize($tenant, 'coupon');

        return $tenant;
    }

    /**
     * Batas promo (%) yang masih menyisakan margin positif untuk tenant.
     *
     * Dihitung dari produk bermargin TERENDAH, karena satu kupon bisa dipakai
     * untuk semua produk kalau `layanan_id` kosong. Ini saran, bukan pengunci:
     * validasi sebenarnya terjadi saat checkout, per produk.
     *
     * @return array{percent: float, harga_jual: int, modal: int, produk: string}|null
     */
    private function marginCeiling(Tenant $tenant): ?array
    {
        $pricing = app(TenantPricingService::class);

        $terendah = null;

        Layanan::query()
            ->where('status', 'available')
            ->where('harga', '>', 0)
            ->orderBy('harga')
            ->limit(200)
            ->get(['id', 'layanan', 'harga'])
            ->each(function (Layanan $layanan) use ($tenant, $pricing, &$terendah): void {
                $harga = $pricing->forLayanan($layanan, $tenant);

                if ($harga['sell_price'] <= 0) {
                    return;
                }

                $persen = (($harga['sell_price'] - $harga['modal_tenant']) / $harga['sell_price']) * 100;

                if ($terendah === null || $persen < $terendah['percent']) {
                    $terendah = [
                        'percent' => round($persen, 2),
                        'harga_jual' => $harga['sell_price'],
                        'modal' => $harga['modal_tenant'],
                        'produk' => (string) $layanan->layanan,
                    ];
                }
            });

        return $terendah;
    }
}
