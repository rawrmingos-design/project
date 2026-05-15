<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Controllers\DepositController as LegacyDepositController;
use App\Models\Deposit;
use App\Services\PublicSiteConfigService;
use App\Support\PublicThemeRegistry;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DepositHistoryPageController extends Controller
{
    public function __invoke(
        PublicSiteConfigService $siteConfigService,
        LegacyDepositController $legacyDepositController,
    ): Response|\Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\Foundation\Application {
        $settings = $siteConfigService->getSettings();

        if (($settings->public_theme ?? PublicThemeRegistry::DEFAULT) === PublicThemeRegistry::DEFAULT) {
            return $legacyDepositController->reloadd();
        }

        $user = Auth::user();
        $canShowAffiliate = ! in_array(strtolower((string) ($user->affiliate_status ?? '')), ['', 'inactive'], true);

        $histories = Deposit::query()
            ->where('username', $user->username)
            ->latest('created_at')
            ->limit(120)
            ->get()
            ->map(function (Deposit $deposit): array {
                return [
                    'orderId' => (string) ($deposit->order_id ?: '-'),
                    'invoiceUrl' => route('deposit.invoice', ['order' => $deposit->order_id]),
                    'transactionId' => 'n/a',
                    'method' => (string) ($deposit->metode ?: '-'),
                    'amount' => (int) round((float) ($deposit->jumlah ?? 0)),
                    'createdAt' => optional($deposit->created_at)->timezone(config('app.timezone'))->format('d-m-Y H:i:s'),
                    'status' => $this->mapDepositStatus((string) ($deposit->status ?? '')),
                ];
            })
            ->values()
            ->all();

        return Inertia::render('Public/DepositHistory', [
            'mutation' => [
                'title' => 'Riwayat Deposit',
                'description' => 'Menampilkan data riwayat transaksi deposit yang telah kamu lakukan.',
                'histories' => $histories,
                'links' => [
                    'dashboard' => route('dashboard'),
                    'transactions' => route('riwayat'),
                    'mutation' => route('reload'),
                    'affiliate' => route('affiliate'),
                    'canShowAffiliate' => $canShowAffiliate,
                ],
            ],
            'meta' => [
                'title' => "Riwayat Deposit - {$settings->judul_web}",
                'description' => 'Riwayat transaksi deposit akun kamu, lengkap dengan metode pembayaran dan status.',
                'keywords' => "riwayat deposit, mutasi saldo, status deposit, {$settings->judul_web}",
                'canonical' => url('/id/deposit/history'),
                'image' => url($siteConfigService->normalizeAssetPath($settings->logo_favicon)),
            ],
        ]);
    }

    private function mapDepositStatus(string $statusRaw): array
    {
        $status = strtolower(trim($statusRaw));

        if (in_array($status, ['success', 'sukses', 'paid', 'lunas', 'berhasil'], true)) {
            return ['label' => 'Sukses', 'tone' => 'success'];
        }

        if (in_array($status, ['process', 'processing', 'proses'], true)) {
            return ['label' => 'Diproses', 'tone' => 'processing'];
        }

        if (in_array($status, ['failed', 'gagal', 'cancelled', 'canceled', 'batal', 'reject', 'rejected', 'refunded'], true)) {
            return ['label' => 'Gagal', 'tone' => 'failed'];
        }

        return ['label' => 'Menunggu', 'tone' => 'pending'];
    }
}
