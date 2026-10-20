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

        return view('tenant.register', [
            'baseHost' => preg_replace('/:\d+$/', '', (string) $baseHost) ?: 'topupengine.com',
            'plans' => $plans,
            'durations' => \App\Models\TierPricing::DURATIONS,
        ]);
    }
}
