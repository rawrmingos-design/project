<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Controllers\InvoiceDepositController as LegacyInvoiceDepositController;
use App\Models\Deposit;
use App\Models\Method;
use App\Models\Pembayaran;
use App\Services\PublicSiteConfigService;
use App\Support\PublicThemeRegistry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class DepositInvoicePageController extends Controller
{
    public function __invoke(
        string $order,
        PublicSiteConfigService $siteConfigService,
        LegacyInvoiceDepositController $legacyInvoiceDepositController,
    ): Response|\Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\Foundation\Application {
        $settings = $siteConfigService->getSettings();

        if (($settings->public_theme ?? PublicThemeRegistry::DEFAULT) === PublicThemeRegistry::DEFAULT) {
            return $legacyInvoiceDepositController->create($order);
        }

        $payment = Pembayaran::query()
            ->where('order_id', $order)
            ->latest('id')
            ->first();

        abort_if(! $payment, 404);
        $payment->syncExpiredStatus();

        $deposit = Deposit::query()
            ->where('order_id', $order)
            ->latest('id')
            ->first();

        abort_if(! $deposit, 404);

        $user = Auth::user();
        $isAdmin = strtolower((string) ($user->role ?? '')) === 'admin';
        if (! $isAdmin && strcasecmp((string) $deposit->username, (string) $user->username) !== 0) {
            abort(403);
        }

        $paymentStatus = $this->normalizePaymentStatus((string) $payment->status);
        $depositStatus = $this->normalizeDepositStatus((string) $deposit->status);
        $paymentCode = strtoupper(trim((string) $payment->metode));
        $paymentNumber = trim((string) $payment->no_pembayaran);
        $method = $this->resolveMethod($paymentCode);
        $methodName = $method?->name ?: $this->resolveMethodDisplayName($paymentCode);
        $methodImage = $method?->images
            ? $siteConfigService->normalizeAssetPath((string) $method->images, '')
            : null;

        $isPaymentUrl = filter_var($paymentNumber, FILTER_VALIDATE_URL) !== false;
        $isDuitkuUrl = str_contains(strtolower($paymentNumber), 'duitku');
        $isQrPaymentMethod = in_array($paymentCode, [
            'QRIS', 'QRISC', 'QRIS2', 'QRISOP', 'SP', 'SQ', 'QRISREALTIME',
        ], true);
        $isEwalletDeepLink = in_array($paymentCode, [
            'SHOPEEPAY', 'OVOPUSH', 'DANA', 'LINKAJA', '11', '17', '23',
        ], true);

        $showQrImage = $paymentStatus['code'] === 'unpaid'
            && $paymentNumber !== ''
            && $isQrPaymentMethod
            && ! $isDuitkuUrl;

        $showPayButton = $paymentStatus['code'] === 'unpaid'
            && $isPaymentUrl
            && ($isDuitkuUrl || $isEwalletDeepLink || ! $showQrImage);

        $qrImageUrl = null;
        if ($showQrImage) {
            $qrImageUrl = $isPaymentUrl
                ? $paymentNumber
                : 'https://api.qrserver.com/v1/create-qr-code/?size=320x320&data=' . urlencode($paymentNumber);
        }

        $expiresAt = $payment->expired_at
            ? Carbon::parse($payment->expired_at)
            : Carbon::parse($payment->created_at)->addHours(3);

        $subtotal = (int) round((float) ($deposit->jumlah ?? 0));
        $total = (int) round((float) ($payment->harga ?? 0));
        if ($subtotal <= 0) {
            $subtotal = $total;
        }
        $fee = max(0, $total - $subtotal);

        [$hero, $intro] = $this->resolveHeroAndIntro($deposit, $paymentStatus);

        return Inertia::render('Public/DepositInvoice', [
            'invoice' => [
                'orderId' => (string) $deposit->order_id,
                'hero' => $hero,
                'intro' => $intro,
                'method' => [
                    'name' => $methodName,
                    'code' => $paymentCode,
                    'image' => $methodImage,
                ],
                'payment' => [
                    'number' => $paymentNumber,
                    'isUrl' => $isPaymentUrl,
                    'showPayButton' => $showPayButton,
                    'showQrImage' => $showQrImage,
                    'qrImageUrl' => $qrImageUrl,
                    'paymentUrl' => $showPayButton ? $paymentNumber : null,
                    'showCopyNumber' => ! $isPaymentUrl && $paymentNumber !== '',
                    'buttonLabel' => $isDuitkuUrl ? 'Buka Halaman Pembayaran' : 'Klik di sini untuk melakukan pembayaran',
                    'hint' => $this->resolvePaymentHint($paymentCode),
                ],
                'status' => [
                    'payment' => $paymentStatus,
                    'deposit' => $depositStatus,
                    'message' => $this->resolveStatusMessage($paymentStatus['code'], $depositStatus['code'], $deposit),
                ],
                'amount' => [
                    'subtotal' => $subtotal,
                    'fee' => $fee,
                    'total' => $total,
                ],
                'expiry' => [
                    'expiresAt' => $expiresAt->toIso8601String(),
                    'display' => $expiresAt->timezone(config('app.timezone'))->translatedFormat('d M Y H:i'),
                ],
                'links' => [
                    'topup' => route('deposit'),
                    'history' => route('reload'),
                    'dashboard' => route('dashboard'),
                ],
            ],
            'meta' => [
                'title' => "Invoice Deposit {$deposit->order_id} - {$settings->judul_web}",
                'description' => "Detail invoice deposit {$deposit->order_id}.",
                'keywords' => "invoice deposit, top up saldo, {$settings->judul_web}",
                'canonical' => url('/id/deposit/' . $deposit->order_id),
                'image' => url($siteConfigService->normalizeAssetPath($settings->logo_favicon)),
            ],
        ]);
    }

    private function resolveMethod(?string $paymentCode): ?Method
    {
        if (blank($paymentCode)) {
            return null;
        }

        $paymentCode = strtoupper(trim((string) $paymentCode));

        return Method::query()
            ->get(['id', 'code', 'name', 'images'])
            ->first(function (Method $method) use ($paymentCode): bool {
                $rawCode = strtoupper(trim((string) ($method->getRawOriginal('code') ?? $method->code)));
                $rawName = strtoupper(trim((string) ($method->getRawOriginal('name') ?? $method->name)));

                return $rawCode === $paymentCode || $rawName === $paymentCode;
            });
    }

    private function resolveMethodDisplayName(string $code): string
    {
        if ($code === '') {
            return 'Metode Tidak Dikenal';
        }

        return Str::of($code)
            ->replace(['_', '-'], ' ')
            ->squish()
            ->title()
            ->value();
    }

    private function normalizePaymentStatus(string $rawStatus): array
    {
        $normalized = strtolower(trim($rawStatus));

        if (in_array($normalized, ['paid', 'lunas', 'success'], true)) {
            return ['code' => 'paid', 'label' => 'Paid'];
        }

        if ($normalized === 'expired') {
            return ['code' => 'expired', 'label' => 'Expired'];
        }

        if (in_array($normalized, ['failed', 'gagal', 'cancelled', 'canceled'], true)) {
            return ['code' => 'failed', 'label' => 'Failed'];
        }

        return ['code' => 'unpaid', 'label' => 'Unpaid'];
    }

    private function normalizeDepositStatus(string $rawStatus): array
    {
        $normalized = strtolower(trim($rawStatus));

        if (in_array($normalized, ['success', 'sukses', 'berhasil', 'paid'], true)) {
            return ['code' => 'success', 'label' => 'Success'];
        }

        if (in_array($normalized, ['failed', 'gagal', 'cancelled', 'canceled', 'reject'], true)) {
            return ['code' => 'failed', 'label' => 'Failed'];
        }

        return ['code' => 'pending', 'label' => 'Pending'];
    }

    private function resolveHeroAndIntro(Deposit $deposit, array $paymentStatus): array
    {
        $hero = [
            'eyebrow' => 'Terima Kasih!',
            'title' => 'Harap lengkapi pembayaran.',
            'description' => "Deposit kamu {$deposit->order_id} menunggu pembayaran sebelum diproses.",
        ];

        $intro = [
            'state' => 'pending',
            'title' => 'Menunggu Pembayaran',
            'subtitle' => 'Invoice deposit sedang disiapkan. Mohon selesaikan pembayaran untuk melanjutkan proses deposit.',
            'icon' => 'clock',
            'badgeText' => 'Menunggu Pembayaran',
            'durationMs' => 4300,
            'usesLottie' => false,
            'lottieSrc' => null,
            'lottieSequence' => [],
        ];

        if ($paymentStatus['code'] === 'paid') {
            $hero['title'] = 'Deposit sudah selesai.';
            $hero['description'] = "Deposit kamu {$deposit->order_id} telah dibayar dan saldo sedang diproses oleh sistem.";

            $intro = [
                'state' => 'paid',
                'title' => 'Deposit Diterima',
                'subtitle' => 'Pembayaran deposit berhasil diterima. Sistem sedang menyelesaikan penambahan saldo.',
                'icon' => 'check',
                'badgeText' => 'Deposit Diterima',
                'durationMs' => 4300,
                'usesLottie' => false,
                'lottieSrc' => null,
                'lottieSequence' => [],
            ];
        } elseif ($paymentStatus['code'] === 'expired') {
            $hero['eyebrow'] = 'Perhatian';
            $hero['title'] = 'Invoice deposit sudah kedaluwarsa.';
            $hero['description'] = "Batas pembayaran untuk deposit {$deposit->order_id} telah habis.";

            $intro = [
                'state' => 'expired',
                'title' => 'Pembayaran Kedaluwarsa',
                'subtitle' => 'Batas waktu pembayaran deposit telah berakhir. Silakan buat invoice deposit baru jika masih diperlukan.',
                'icon' => 'x',
                'badgeText' => 'Pembayaran Kedaluwarsa',
                'durationMs' => 4700,
                'usesLottie' => false,
                'lottieSrc' => null,
                'lottieSequence' => [],
            ];
        }

        if ($intro['state'] === 'pending') {
            $pendingLottieSequence = [];

            foreach (['First.json', 'Second.json'] as $candidateFile) {
                $candidatePath = public_path('assets/invoice-intro/lottie/' . $candidateFile);

                if (is_file($candidatePath)) {
                    $pendingLottieSequence[] = asset('assets/invoice-intro/lottie/' . rawurlencode($candidateFile));
                }
            }

            if (!empty($pendingLottieSequence)) {
                $intro['usesLottie'] = true;
                $intro['lottieSrc'] = $pendingLottieSequence[0];
                $intro['lottieSequence'] = $pendingLottieSequence;
            }
        }

        return [$hero, $intro];
    }

    private function resolveStatusMessage(string $paymentCode, string $depositCode, Deposit $deposit): string
    {
        if ($depositCode === 'success') {
            return 'Saldo berhasil ditambahkan. Sistem sedang menyelesaikan proses sinkronisasi.';
        }

        if ($paymentCode === 'paid') {
            return 'Pembayaran deposit sudah diterima. Sistem sedang memproses penambahan saldo.';
        }

        if ($paymentCode === 'expired') {
            return 'Pembayaran deposit telah kedaluwarsa.';
        }

        if ($paymentCode === 'failed' || $depositCode === 'failed') {
            return 'Transaksi deposit gagal diproses. Silakan hubungi admin jika butuh bantuan.';
        }

        return "Menunggu pembayaran deposit {$deposit->order_id}.";
    }

    private function resolvePaymentHint(string $paymentCode): string
    {
        if (in_array($paymentCode, ['QRIS', 'QRISC', 'QRIS2', 'QRISOP', 'SP', 'SQ', 'QRISREALTIME'], true)) {
            return 'Gunakan e-wallet atau mobile banking untuk melakukan scan QR pembayaran.';
        }

        if (in_array($paymentCode, ['BRIVA', 'BCAVA', 'BNIVA', 'MANDIRIVA', 'PERMATAVA', 'CIMBVA', 'DANAMONVA', 'BSIVA'], true)) {
            return 'Gunakan aplikasi mobile banking untuk menyelesaikan pembayaran virtual account.';
        }

        if ($paymentCode === 'INDOMARET') {
            return 'Tunjukkan nomor pembayaran ke kasir Indomaret agar deposit diproses.';
        }

        if ($paymentCode === 'ALFAMART') {
            return 'Tunjukkan nomor pembayaran ke kasir Alfamart agar deposit diproses.';
        }

        return 'Gunakan metode pembayaran yang dipilih untuk menyelesaikan transaksi deposit.';
    }
}
