<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Controllers\RiwayatPembelian as LegacyTransactionHistoryController;
use App\Models\Pembelian;
use App\Services\PublicSiteConfigService;
use App\Support\PembelianStatus;
use App\Support\PublicThemeRegistry;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class TransactionHistoryPageController extends Controller
{
    public function __invoke(
        PublicSiteConfigService $siteConfigService,
        LegacyTransactionHistoryController $legacyTransactionHistoryController,
    ): Response|\Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\Foundation\Application {
        $settings = $siteConfigService->getSettings();

        if (($settings->public_theme ?? PublicThemeRegistry::DEFAULT) === PublicThemeRegistry::DEFAULT) {
            return $legacyTransactionHistoryController->create();
        }

        $user = Auth::user();
        $canShowAffiliate = ! in_array(strtolower((string) ($user->affiliate_status ?? '')), ['', 'inactive'], true);

        $transactions = Pembelian::query()
            ->where('username', $user->username)
            ->latest('created_at')
            ->limit(120)
            ->get()
            ->map(function (Pembelian $transaction): array {
                $zone = blank($transaction->zone) ? '' : '-' . trim((string) $transaction->zone);
                $userInput = trim((string) ($transaction->user_id . $zone));

                return [
                    'invoiceId' => (string) ($transaction->display_order_id ?: $transaction->order_id),
                    'invoiceUrl' => route('pembelian', ['order' => $transaction->order_id]),
                    'providerOrderId' => (string) ($transaction->provider_order_id ?: 'n/a'),
                    'item' => (string) ($transaction->layanan ?: '-'),
                    'userInput' => $userInput !== '' ? $userInput : '-',
                    'price' => (int) round((float) ($transaction->harga ?? 0)),
                    'createdAt' => optional($transaction->created_at)->timezone(config('app.timezone'))->format('d-m-Y H:i:s'),
                    'status' => $this->mapOrderStatus((string) ($transaction->status ?? '')),
                ];
            })
            ->values()
            ->all();

        return Inertia::render('Public/TransactionHistory', [
            'history' => [
                'title' => 'Riwayat Transaksi',
                'description' => 'Menampilkan data riwayat transaksi yang telah kamu lakukan.',
                'transactions' => $transactions,
                'links' => [
                    'dashboard' => route('dashboard'),
                    'transactions' => route('riwayat'),
                    'mutation' => route('reload'),
                    'affiliate' => route('affiliate'),
                    'canShowAffiliate' => $canShowAffiliate,
                ],
            ],
            'meta' => [
                'title' => "Riwayat Transaksi - {$settings->judul_web}",
                'description' => 'Riwayat transaksi akun kamu, lengkap dengan status order dan nomor invoice.',
                'keywords' => "riwayat transaksi, invoice, status order, {$settings->judul_web}",
                'canonical' => url('/id/dashboard/history'),
                'image' => url($siteConfigService->normalizeAssetPath($settings->logo_favicon)),
            ],
        ]);
    }

    private function mapOrderStatus(string $statusRaw): array
    {
        return match (PembelianStatus::normalize($statusRaw)) {
            PembelianStatus::SUCCESS => ['label' => 'Sukses', 'tone' => 'success'],
            PembelianStatus::PENDING => ['label' => 'Menunggu', 'tone' => 'pending'],
            PembelianStatus::PROCESSING => ['label' => 'Diproses', 'tone' => 'processing'],
            PembelianStatus::FAILED,
            PembelianStatus::CANCELLED,
            PembelianStatus::REFUNDED => ['label' => 'Gagal', 'tone' => 'failed'],
            PembelianStatus::EXPIRED => ['label' => 'Expired', 'tone' => 'failed'],
            default => ['label' => 'Menunggu', 'tone' => 'pending'],
        };
    }
}
