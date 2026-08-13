<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Controllers\DsController as LegacyDashboardController;
use App\Models\Pembelian;
use App\Services\PublicSiteConfigService;
use App\Support\PembelianStatus;
use App\Support\PublicThemeRegistry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardPageController extends Controller
{
    public function __invoke(
        PublicSiteConfigService $siteConfigService,
        LegacyDashboardController $legacyDashboardController,
    ): Response|\Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\Foundation\Application {
        $settings = $siteConfigService->getSettings();

        if (($settings->public_theme ?? PublicThemeRegistry::DEFAULT) === PublicThemeRegistry::DEFAULT) {
            return $legacyDashboardController->dashboard();
        }

        $user = Auth::user();
        $today = Carbon::today();

        $pendingAliases = PembelianStatus::aliasesFor(PembelianStatus::PENDING);
        $processingAliases = PembelianStatus::aliasesFor(PembelianStatus::PROCESSING);
        $successAliases = PembelianStatus::aliasesFor(PembelianStatus::SUCCESS);
        $failedAliases = array_merge(
            PembelianStatus::aliasesFor(PembelianStatus::FAILED),
            PembelianStatus::aliasesFor(PembelianStatus::CANCELLED),
            PembelianStatus::aliasesFor(PembelianStatus::EXPIRED)
        );

        $buildStats = static function (Collection $transactions) use (
            $pendingAliases,
            $processingAliases,
            $successAliases,
            $failedAliases
        ): array {
            return [
                'totalTransactions' => $transactions->count(),
                'totalSales' => (int) round((float) $transactions->sum('harga')),
                'waiting' => $transactions->whereIn('status', $pendingAliases)->count(),
                'processing' => $transactions->whereIn('status', $processingAliases)->count(),
                'success' => $transactions->whereIn('status', $successAliases)->count(),
                'failed' => $transactions->whereIn('status', $failedAliases)->count(),
            ];
        };

        $periodDefinitions = [
            '1d' => [
                'label' => 'Hari ini',
                'start' => (clone $today)->startOfDay(),
            ],
            '7d' => [
                'label' => '7 hari terakhir',
                'start' => (clone $today)->subDays(6)->startOfDay(),
            ],
            '30d' => [
                'label' => '30 hari terakhir',
                'start' => (clone $today)->subDays(29)->startOfDay(),
            ],
        ];

        $periodStats = [];
        foreach ($periodDefinitions as $periodKey => $periodDefinition) {
            $periodTransactions = Pembelian::query()
                ->where('username', $user->username)
                ->where('created_at', '>=', $periodDefinition['start'])
                ->get();

            $periodStats[$periodKey] = array_merge(
                ['label' => $periodDefinition['label']],
                $buildStats($periodTransactions)
            );
        }

        $defaultPeriod = '30d';
        $currentStats = $periodStats[$defaultPeriod] ?? reset($periodStats);

        $recentTransactions = Pembelian::query()
            ->where('username', $user->username)
            ->latest('created_at')
            ->limit(10)
            ->get()
            ->map(function (Pembelian $transaction): array {
                $zone = blank($transaction->zone) ? '' : '-' . trim((string) $transaction->zone);
                $normalizedStatus = PembelianStatus::normalize((string) $transaction->status);

                $status = match ($normalizedStatus) {
                    PembelianStatus::SUCCESS => ['label' => 'Sukses', 'tone' => 'success'],
                    PembelianStatus::PENDING => ['label' => 'Menunggu', 'tone' => 'pending'],
                    PembelianStatus::PROCESSING => ['label' => 'Diproses', 'tone' => 'processing'],
                    PembelianStatus::FAILED,
                    PembelianStatus::CANCELLED => ['label' => 'Gagal', 'tone' => 'failed'],
                    PembelianStatus::EXPIRED => ['label' => 'Expired', 'tone' => 'failed'],
                    PembelianStatus::REFUNDED => ['label' => 'Refunded', 'tone' => 'failed'],
                    default => ['label' => 'Pending', 'tone' => 'pending'],
                };

                return [
                    'invoiceId' => (string) ($transaction->display_order_id ?: $transaction->order_id),
                    'invoiceUrl' => route('pembelian', ['order' => $transaction->order_id]),
                    'providerOrderId' => (string) ($transaction->provider_order_id ?: '-'),
                    'item' => (string) ($transaction->layanan ?: '-'),
                    'userInput' => trim((string) ($transaction->user_id . $zone)),
                    'price' => (int) round((float) ($transaction->harga ?? 0)),
                    'createdAt' => optional($transaction->created_at)->timezone(config('app.timezone'))->format('d-m-Y H:i:s'),
                    'status' => $status,
                ];
            })
            ->values()
            ->all();

        $phone = $user->no_hp ?? $user->no_wa ?? $user->wa ?? '---';
        $affiliateStatus = (string) ($user->affiliate_status ?? '');
        $isAffiliateActive = strtolower(trim($affiliateStatus)) === 'active';
        $canShowAffiliate = ! in_array(strtolower($affiliateStatus), ['', 'inactive'], true);
        $displayName = (string) ($user->name ?? $user->username ?? 'Member');
        $avatarFallback = 'https://ui-avatars.com/api/?color=FFFFFF&background=50a7ff&name=' . urlencode($displayName);

        return Inertia::render('Public/Dashboard', [
            'dashboard' => [
                'profile' => [
                    'name' => $displayName,
                    'username' => (string) $user->username,
                    'role' => (string) ($user->role ?? 'Member'),
                    'phone' => (string) $phone,
                    'avatar' => $this->resolveProfileAvatar((string) ($user->google_avatar ?? ''), $avatarFallback),
                    'avatarFallback' => $avatarFallback,
                ],
                'credits' => [
                    'coinName' => 'Saldo',
                    'coinSymbol' => 'Rp',
                    'amount' => (int) ($user->balance ?? 0),
                    'showRedeem' => $isAffiliateActive,
                    'showTopUp' => ! $isAffiliateActive,
                ],
                'links' => [
                    'deposit' => route('deposit'),
                    'redeem' => route('withdrawal'),
                    'settings' => route('editProfile'),
                    'dashboard' => route('dashboard'),
                    'transactions' => route('riwayat'),
                    'mutation' => route('reload'),
                    'affiliate' => route('affiliate'),
                    'canShowAffiliate' => $canShowAffiliate,
                ],
                'stats' => [
                    'totalTransactions' => $currentStats['totalTransactions'] ?? 0,
                    'totalSales' => $currentStats['totalSales'] ?? 0,
                    'waiting' => $currentStats['waiting'] ?? 0,
                    'processing' => $currentStats['processing'] ?? 0,
                    'success' => $currentStats['success'] ?? 0,
                    'failed' => $currentStats['failed'] ?? 0,
                    'totalPeriodLabel' => $currentStats['label'] ?? '30 hari terakhir',
                    'statusPeriodLabel' => $currentStats['label'] ?? '30 hari terakhir',
                    'defaultPeriod' => $defaultPeriod,
                    'periods' => $periodStats,
                ],
                'recentTransactions' => $recentTransactions,
            ],
            'meta' => [
                'title' => "Dashboard - {$settings->judul_web}",
                'description' => 'Ringkasan transaksi akun kamu, status order hari ini, dan riwayat transaksi terbaru.',
                'keywords' => "dashboard, transaksi, riwayat pembelian, {$settings->judul_web}",
                'canonical' => url('/id/dashboard'),
                'image' => url($siteConfigService->normalizeAssetPath($settings->logo_favicon)),
            ],
        ]);
    }

    private function resolveProfileAvatar(string $googleAvatar, string $fallback): string
    {
        $candidate = trim($googleAvatar);

        if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_URL) !== false) {
            return $candidate;
        }

        return $fallback;
    }
}
