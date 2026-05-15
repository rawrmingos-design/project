<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Controllers\DepositController as LegacyDepositController;
use App\Models\Deposit;
use App\Models\Method;
use App\Services\PublicSiteConfigService;
use App\Support\PublicThemeRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DepositPageController extends Controller
{
    public function __invoke(
        PublicSiteConfigService $siteConfigService,
        LegacyDepositController $legacyDepositController,
    ): Response|\Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\Foundation\Application|RedirectResponse {
        $settings = $siteConfigService->getSettings();

        if (($settings->public_theme ?? PublicThemeRegistry::DEFAULT) === PublicThemeRegistry::DEFAULT) {
            return $legacyDepositController->create();
        }

        $user = Auth::user();

        if (method_exists($user, 'isAffiliateActive') && $user->isAffiliateActive()) {
            return redirect()
                ->route('dashboard')
                ->with('error', 'Akun Affiliate tidak dapat melakukan deposit. Silakan hubungi Admin.');
        }

        $phoneDefault = (string) (
            $user->no_wa
            ?? $user->no_hp
            ?? $user->wa
            ?? $user->whatsapp
            ?? ''
        );

        $supportedPhoneMethods = [
            'OVO',
            'DANA',
            'SHOPEEPAY',
            'LINKAJA',
            'GOPAY',
            'OVOPUSH',
            'ASTRAPAY',
            'VIRGO',
        ];

        $showDemoMethods = app()->environment('local');

        $methods = Method::availableForDeposit($showDemoMethods)
            ->map(function (Method $method) use ($siteConfigService, $supportedPhoneMethods): array {
                $methodCode = strtoupper(trim((string) ($method->getRawOriginal('code') ?? $method->code)));
                $methodType = Method::normalizeTipe($method->getRawOriginal('tipe') ?: $method->tipe);

                return [
                    'code' => $methodCode,
                    'name' => (string) ($method->getRawOriginal('name') ?? $method->name),
                    'type' => (string) $methodType,
                    'typeLabel' => $this->mapMethodTypeLabel((string) $methodType),
                    'note' => (string) ($method->keterangan ?? ''),
                    'image' => $siteConfigService->normalizeAssetPath((string) ($method->getRawOriginal('images') ?? $method->images), ''),
                    'feePercent' => (float) ($method->fee_percent ?? 0),
                    'fixedFee' => (float) ($method->fix_fee ?? 0),
                    'minAmount' => $method->min_pembelian ? (int) $method->min_pembelian : null,
                    'maxAmount' => $method->max_pembelian ? (int) $method->max_pembelian : null,
                    'requiresPhone' => in_array($methodCode, $supportedPhoneMethods, true),
                ];
            })
            ->filter(fn (array $method): bool => $method['code'] !== '')
            ->values()
            ->all();

        usort($methods, static function (array $a, array $b): int {
            $priorityA = $a['type'] === 'qris' ? 0 : 1;
            $priorityB = $b['type'] === 'qris' ? 0 : 1;

            if ($priorityA !== $priorityB) {
                return $priorityA <=> $priorityB;
            }

            return strcmp($a['name'], $b['name']);
        });

        $recentDeposits = Deposit::query()
            ->where('username', (string) $user->username)
            ->latest('created_at')
            ->limit(6)
            ->get()
            ->map(function (Deposit $deposit): array {
                return [
                    'orderId' => (string) ($deposit->order_id ?: '-'),
                    'invoiceUrl' => route('deposit.invoice', ['order' => $deposit->order_id]),
                    'amount' => (int) round((float) ($deposit->jumlah ?? 0)),
                    'method' => (string) ($deposit->metode ?: '-'),
                    'createdAt' => optional($deposit->created_at)->timezone(config('app.timezone'))->format('d-m-Y H:i'),
                    'status' => $this->mapDepositStatus((string) ($deposit->status ?? 'Pending')),
                ];
            })
            ->values()
            ->all();

        return Inertia::render('Public/Deposit', [
            'deposit' => [
                'title' => 'Top Up Saldo',
                'description' => 'Isi saldo akun kamu dengan cepat menggunakan metode pembayaran yang tersedia.',
                'minimumAmount' => 10000,
                'balance' => (int) round((float) ($user->balance ?? 0)),
                'methods' => $methods,
                'recentDeposits' => $recentDeposits,
                'formDefaults' => [
                    'phone' => $phoneDefault,
                ],
                'links' => [
                    'dashboard' => route('dashboard'),
                    'transactions' => route('riwayat'),
                    'mutation' => route('reload'),
                    'affiliate' => route('affiliate'),
                    'history' => route('reload'),
                    'invoiceLookup' => route('cari'),
                ],
                'flash' => [
                    'success' => session('success'),
                    'error' => session('error'),
                ],
            ],
            'meta' => [
                'title' => "Top Up Saldo - {$settings->judul_web}",
                'description' => 'Top up saldo akun untuk kebutuhan transaksi cepat dan aman.',
                'keywords' => "top up saldo, deposit saldo, {$settings->judul_web}",
                'canonical' => url('/id/deposit'),
                'image' => url($siteConfigService->normalizeAssetPath($settings->logo_favicon)),
            ],
        ]);
    }

    private function mapDepositStatus(string $status): array
    {
        $normalized = strtolower(trim($status));

        if (in_array($normalized, ['success', 'sukses', 'berhasil', 'paid', 'lunas'], true)) {
            return ['label' => 'Sukses', 'tone' => 'success'];
        }

        if (in_array($normalized, ['gagal', 'failed', 'cancelled', 'canceled', 'expired'], true)) {
            return ['label' => 'Gagal', 'tone' => 'failed'];
        }

        return ['label' => 'Menunggu', 'tone' => 'pending'];
    }

    private function mapMethodTypeLabel(string $type): string
    {
        return match ($type) {
            'qris' => 'QRIS',
            'e-walet' => 'E-Wallet',
            'virtual-account' => 'Virtual Account',
            'convenience-store' => 'Convenience Store',
            default => 'Metode Pembayaran',
        };
    }
}
