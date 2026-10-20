<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Tenancy\TierPricingResolver;
use App\Tenancy\TenantRegistrationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RegistrationPageController extends Controller
{
    public function __invoke(Request $request, TierPricingResolver $tiers): View
    {
        abort_if((bool) config('tenancy.disabled', true), 404);

        $baseHost = parse_url((string) config('app.url'), PHP_URL_HOST) ?: $request->getHost();

        // Semua tier bisa didaftarkan mandiri. Isi kartu (nama, fitur, badge,
        // harga, harga coret) dibaca dari `tier_pricing` supaya bisa diubah
        // admin tanpa deploy.
        $plans = $tiers->activeTiers()->map(function ($row) {
            $durations = [];

            foreach (\App\Models\TierPricing::DURATIONS as $months) {
                $price = $row->priceForDuration($months);

                if ($price <= 0) {
                    continue;
                }

                $durations[$months] = [
                    'months' => $months,
                    'price' => $price,
                    'price_per_month' => $row->pricePerMonthForDuration($months),
                    'strike_price' => $row->strikePriceForDuration($months),
                    'discount_percent' => (float) ($row->{'strike_discount_'.$months.'_month'} ?? 0),
                ];
            }

            return [
                'tier' => $row->tier,
                'name' => $row->label ?: ucfirst($row->tier),
                'badge' => $row->badge,
                'features' => is_array($row->features) ? $row->features : [],
                'durations' => $durations,
            ];
        })->values();

        $baseHost = preg_replace('/:\d+$/', '', (string) ($baseHost ?: $request->getHost()));

        return view('tenant.register', [
            'baseHost' => $baseHost !== '' ? $baseHost : (string) parse_url((string) config('app.url'), PHP_URL_HOST),
            // Nama platform dari Settings Web. Dulu teks "TopupEngine" ditulis
            // keras di blade, jadi pembeli platform tidak bisa mengganti merek
            // tanpa mengedit kode.
            'platformName' => $this->platformName(),
            'plans' => $plans,
            'durations' => \App\Models\TierPricing::DURATIONS,
        ]);
    }

    /**
     * Nama platform untuk ditampilkan.
     *
     * Sumber tunggalnya `setting_webs.judul_web` (Settings → Informasi Website),
     * yang juga sudah dipakai judul situs publik. `config('app.name')` hanya
     * cadangan supaya halaman tidak pernah menampilkan nama kosong.
     */
    private function platformName(): string
    {
        try {
            $name = trim((string) \App\Models\SettingWeb::query()->value('judul_web'));
        } catch (\Throwable) {
            $name = '';
        }

        return $name !== '' ? $name : (string) config('app.name', 'Top Up Game');
    }
}
