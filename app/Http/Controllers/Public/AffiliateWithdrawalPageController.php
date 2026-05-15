<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Controllers\DsController as LegacyDashboardController;
use App\Services\PublicSiteConfigService;
use App\Support\PublicThemeRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AffiliateWithdrawalPageController extends Controller
{
    public function __invoke(
        PublicSiteConfigService $siteConfigService,
        LegacyDashboardController $legacyDashboardController,
    ): Response|\Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\Foundation\Application|RedirectResponse {
        $settings = $siteConfigService->getSettings();

        if (($settings->public_theme ?? PublicThemeRegistry::DEFAULT) === PublicThemeRegistry::DEFAULT) {
            return $legacyDashboardController->withdrawal();
        }

        $user = Auth::user();
        $isAffiliateActive = method_exists($user, 'isAffiliateActive')
            ? (bool) $user->isAffiliateActive()
            : strtolower(trim((string) ($user->affiliate_status ?? ''))) === 'active';

        if (! $isAffiliateActive) {
            return redirect()
                ->route('dashboard')
                ->with('error', 'Fitur redeem saldo hanya tersedia untuk akun affiliate yang sudah aktif.');
        }

        return Inertia::render('Public/AffiliateWithdrawal', [
            'withdrawal' => [
                'title' => 'Pembayaran Afiliasi',
                'description' => 'Pantau saldo komisi afiliasi yang saat ini tersedia di akun kamu.',
                'currentBalance' => (int) round((float) ($user->balance ?? 0)),
                'flash' => [
                    'success' => session('success'),
                    'error' => session('error'),
                ],
                'links' => [
                    'dashboard' => route('dashboard'),
                    'transactions' => route('riwayat'),
                    'mutation' => route('reload'),
                    'affiliate' => route('affiliate'),
                    'withdrawal' => route('withdrawal'),
                    'submit' => route('process.withdrawal'),
                    'canShowAffiliate' => true,
                ],
            ],
            'meta' => [
                'title' => "Pembayaran Afiliasi - {$settings->judul_web}",
                'description' => 'Lihat saldo komisi afiliasi yang tersedia pada akun kamu.',
                'keywords' => "affiliate, pembayaran affiliate, withdrawal, {$settings->judul_web}",
                'canonical' => url('/id/withdrawal'),
                'image' => url($siteConfigService->normalizeAssetPath($settings->logo_favicon)),
            ],
        ]);
    }
}
