<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Tenancy\TenantRegistrationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RegistrationPageController extends Controller
{
    public function __invoke(Request $request): View
    {
        $baseHost = parse_url((string) config('app.url'), PHP_URL_HOST) ?: $request->getHost();

        return view('tenant.register', [
            'baseHost' => preg_replace('/:\d+$/', '', (string) $baseHost) ?: 'topupengine.com',
            'tiers' => [
                'starter' => [
                    'name' => 'Starter',
                    'price' => TenantRegistrationService::TIER_PRICES['starter'],
                    'description' => 'Untuk reseller baru yang ingin buka toko topup sendiri.',
                    'features' => [
                        'Subdomain toko instan',
                        'Checkout payment gateway pusat',
                        'Markup global per toko',
                        'Dashboard order & komisi',
                    ],
                ],
                'business' => [
                    'name' => 'Business',
                    'price' => TenantRegistrationService::TIER_PRICES['business'],
                    'description' => 'Untuk reseller aktif yang butuh branding lebih serius.',
                    'badge' => 'Popular',
                    'features' => [
                        'Semua fitur Starter',
                        'Prioritas custom domain',
                        'Support onboarding prioritas',
                        'Siap scale katalog besar',
                    ],
                ],
                'enterprise' => [
                    'name' => 'Enterprise',
                    'price' => TenantRegistrationService::TIER_PRICES['enterprise'],
                    'description' => 'Untuk brand besar dengan kebutuhan khusus.',
                    'self_service' => false,
                    'features' => [
                        'Harga & SLA custom',
                        'Bantuan setup domain',
                        'Kebutuhan integrasi khusus',
                        'Pendampingan go-live',
                    ],
                ],
            ],
        ]);
    }
}
