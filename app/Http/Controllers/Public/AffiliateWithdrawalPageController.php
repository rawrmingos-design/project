<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Controllers\DsController as LegacyDashboardController;
use App\Models\Withdrawal;
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

        $minimumWithdrawal = 10_000;
        $currentBalance = (int) round((float) ($user->balance ?? 0));
        $hasRequestedToday = Withdrawal::query()
            ->where('user_id', $user->id)
            ->whereDate('created_at', now()->toDateString())
            ->exists();
        $withdrawalPaginator = Withdrawal::query()
            ->where('user_id', $user->id)
            ->latest('created_at')
            ->paginate(5)
            ->withQueryString();
        $withdrawals = $withdrawalPaginator->getCollection()
            ->map(fn (Withdrawal $withdrawal): array => [
                'createdAt' => $withdrawal->created_at
                    ? $withdrawal->created_at->timezone(config('app.timezone'))->format('d M Y, H:i')
                    : '-',
                'destination' => (string) ($withdrawal->rekening ?: '-'),
                'amount' => (int) round((float) ($withdrawal->total_transfer ?? 0)),
                'adminFee' => (int) round((float) ($withdrawal->biaya_admin ?? 0)),
                'status' => $this->mapWithdrawalStatus((string) ($withdrawal->status ?? 'pending')),
                'proofUrl' => filled($withdrawal->bukti_transfer) ? asset((string) $withdrawal->bukti_transfer) : null,
            ])
            ->values()
            ->all();
        $disabledReason = null;

        if ($hasRequestedToday) {
            $disabledReason = 'Kamu sudah melakukan penarikan hari ini. Coba lagi besok.';
        } elseif ($currentBalance < $minimumWithdrawal) {
            $disabledReason = 'Saldo minimal untuk melakukan penarikan adalah Rp 10.000.';
        }

        return Inertia::render('Public/AffiliateWithdrawal', [
            'withdrawal' => [
                'title' => 'Pembayaran Afiliasi',
                'description' => 'Tarik komisi affiliate kamu ke rekening atau e-wallet yang valid.',
                'currentBalance' => $currentBalance,
                'minimumWithdrawal' => $minimumWithdrawal,
                'hasRequestedToday' => $hasRequestedToday,
                'canSubmit' => $disabledReason === null,
                'disabledReason' => $disabledReason,
                'bankOptions' => ['BCA', 'BNI', 'BRI', 'MANDIRI', 'DANA', 'OVO', 'GOPAY', 'SHOPEEPAY'],
                'withdrawals' => $withdrawals,
                'pagination' => [
                    'currentPage' => $withdrawalPaginator->currentPage(),
                    'lastPage' => $withdrawalPaginator->lastPage(),
                    'perPage' => $withdrawalPaginator->perPage(),
                    'total' => $withdrawalPaginator->total(),
                    'prevPageUrl' => $withdrawalPaginator->previousPageUrl(),
                    'nextPageUrl' => $withdrawalPaginator->nextPageUrl(),
                ],
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

    private function mapWithdrawalStatus(string $status): array
    {
        $normalized = strtolower(trim($status));

        if (in_array($normalized, ['success', 'sukses', 'berhasil', 'paid'], true)) {
            return ['label' => 'Success', 'tone' => 'success'];
        }

        if (in_array($normalized, ['proses', 'processing', 'process', 'diproses'], true)) {
            return ['label' => 'Process', 'tone' => 'processing'];
        }

        if (in_array($normalized, ['gagal', 'failed', 'cancelled', 'canceled', 'ditolak', 'rejected'], true)) {
            return ['label' => 'Failed', 'tone' => 'failed'];
        }

        return ['label' => 'Pending', 'tone' => 'pending'];
    }
}
