<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Tenancy\TenantContext;
use App\Tenancy\TenantPricingService;
use App\Tenancy\TierEntitlements;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SettingsController extends Controller
{
    public function edit(Request $request, TenantContext $tenantContext, TierEntitlements $entitlements, TenantPricingService $pricing)
    {
        $tenant = $tenantContext->get();
        abort_unless($tenant, 404);
        abort_unless((int) ($request->user()?->id) === (int) $tenant->owner_user_id, 403);

        return view('tenant.settings', [
            'tenant' => $tenant,
            'features' => [
                'theme_color' => $entitlements->has($tenant, 'theme_color'),
            ],
            // Batas markup ditampilkan di form supaya tenant melihat angka yang
            // benar-benar dipakai saat menghitung, bukan menebak.
            'minimumMarkup' => $pricing->minimumTenantMarkupPercent($tenant),
            'maximumMarkup' => TenantPricingService::MAX_REASONABLE_MARKUP_PERCENT,
            'markupPreview' => $this->markupPreview($tenant, $pricing),
        ]);
    }

    /**
     * Simulasi harga jual untuk markup yang sedang tersimpan.
     *
     * Memakai harga pokok contoh (Rp 10.000, sama dengan default
     * `effectiveMarkupPercent()`) agar tenant punya gambaran konkret: berapa
     * modalnya, berapa harga jual, berapa profit per transaksi.
     *
     * @return array{modal:int, sell:int, profit:int, effective:float}|null
     */
    private function markupPreview($tenant, TenantPricingService $pricing): ?array
    {
        $sample = 10000;
        $platformMarkup = $pricing->platformMarkupPercent($tenant);
        $modalTenant = (int) round($sample * (1 + ($platformMarkup / 100)));
        [$sell, $effective] = $pricing->sellPrice($tenant, $sample, $modalTenant, $platformMarkup);

        return [
            'modal' => $modalTenant,
            'sell' => $sell,
            'profit' => $sell - $modalTenant,
            'effective' => $effective,
        ];
    }

    public function update(Request $request, TenantContext $tenantContext, TierEntitlements $entitlements, TenantPricingService $pricing)
    {
        $tenant = $tenantContext->get();
        abort_unless($tenant, 404);
        abort_unless((int) ($request->user()?->id) === (int) $tenant->owner_user_id, 403);

        // Gate di server, bukan hanya menyembunyikan input di view: perubahan
        // warna tema adalah fitur paket, jadi permintaan langsung ke endpoint
        // harus ditolak juga.
        $entitlements->authorize($tenant, 'theme_color');

        // Batas markup dibaca dari service, bukan ditulis ulang di sini.
        // `min:0` dulu menyesatkan: tenant mengira 0% sah, lalu `sellPrice()`
        // diam-diam menaikkannya ke markup platform + 0.01% sehingga angka yang
        // tersimpan bukan angka yang berlaku.
        $minimum = $pricing->minimumTenantMarkupPercent($tenant);
        $maximum = TenantPricingService::MAX_REASONABLE_MARKUP_PERCENT;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'primary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'accent_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'contact_whatsapp' => ['nullable', 'string', 'max:30'],
            'markup_type' => ['required', 'in:percent,fixed'],
            'markup_value' => ['required', 'numeric', 'min:0', 'max:1000000'],
        ]);

        $this->assertMarkupIsUsable($validated['markup_type'], (float) $validated['markup_value'], $minimum, $maximum);

        $tenant->update([
            'name' => $validated['name'],
            'theme' => [
                'primary_color' => $validated['primary_color'],
                'accent_color' => $validated['accent_color'],
            ],
            'settings' => [
                'contact_whatsapp' => $validated['contact_whatsapp'] ?? null,
            ],
            'margin_config' => [
                'markup_type' => $validated['markup_type'],
                'markup_value' => (float) $validated['markup_value'],
            ],
        ]);

        return redirect()->route('tenant.settings')->with('success', 'Pengaturan toko berhasil disimpan.');
    }

    /**
     * Tolak markup yang mustahil, dengan pesan yang menjelaskan angkanya.
     *
     * Sengaja MENOLAK, bukan mengoreksi diam-diam seperti `sellPrice()`.
     * Kalau tenant menyimpan 1% lalu sistem memakai 3.01%, tenant tidak pernah
     * tahu dia salah pilih, dan setiap perhitungan profitnya meleset.
     *
     * Markup `fixed` tidak dibatasi batas bawah ini: nominal tetap di atas modal
     * selalu menghasilkan profit positif berapa pun kecilnya.
     */
    private function assertMarkupIsUsable(string $type, float $value, float $minimum, float $maximum): void
    {
        if (strtolower(trim($type)) === 'fixed') {
            return;
        }

        if ($value <= $minimum) {
            throw ValidationException::withMessages([
                'markup_value' => sprintf(
                    'Markup minimal harus di atas %s%% untuk paket toko Anda. Di bawah atau sama '
                    . 'dengan angka itu, harga jual tidak menutup harga beli dari platform, jadi '
                    . 'profit toko nol.',
                    rtrim(rtrim(number_format($minimum, 2, ',', '.'), '0'), ',')
                ),
            ]);
        }

        if ($value > $maximum) {
            throw ValidationException::withMessages([
                'markup_value' => sprintf(
                    'Markup maksimal %s%%. Di atas itu harga jual menjadi berkali-kali lipat '
                    . 'harga pasar dan toko sulit bersaing. Kalau memang disengaja, hubungi admin.',
                    rtrim(rtrim(number_format($maximum, 2, ',', '.'), '0'), ',')
                ),
            ]);
        }
    }
}
