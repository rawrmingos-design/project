<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Controllers\InvoiceController as LegacyInvoiceController;
use App\Models\Layanan;
use App\Models\Method;
use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Services\PublicSiteConfigService;
use App\Support\GtmDataLayerBuilder;
use App\Support\InvoiceRealtimeStatus;
use App\Support\PublicThemeRegistry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class InvoicePageController extends Controller
{
    public function __invoke(
        string $order,
        PublicSiteConfigService $siteConfigService,
        LegacyInvoiceController $legacyInvoiceController,
    ): Response|\Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\Foundation\Application {
        $settings = $siteConfigService->getSettings();

        if (($settings->public_theme ?? PublicThemeRegistry::DEFAULT) === PublicThemeRegistry::DEFAULT) {
            return $legacyInvoiceController->create($order);
        }

        $payment = Pembayaran::query()
            ->where('order_id', $order)
            ->latest('id')
            ->first();

        abort_if(! $payment, 404);
        $payment->syncExpiredStatus();

        $dataQuery = Pembelian::query()
            ->where('pembayarans.order_id', $order)
            ->join('pembayarans', 'pembelians.order_id', '=', 'pembayarans.order_id');

        if (DB::connection()->getDriverName() === 'sqlite') {
            $dataQuery->leftJoin('methods', 'pembayarans.metode', '=', 'methods.code');
        } else {
            $dataQuery->leftJoin(
                'methods',
                DB::raw('pembayarans.metode COLLATE utf8mb4_unicode_ci'),
                '=',
                DB::raw('methods.code COLLATE utf8mb4_unicode_ci')
            );
        }

        $data = $dataQuery
            ->select(
                'pembayarans.status AS status_pembayaran',
                'pembayarans.metode AS metode_pembayaran',
                'pembayarans.no_pembayaran',
                'pembayarans.expired_at',
                'pembayarans.harga AS harga_pembayaran',
                'pembelians.order_id AS id_pembelian',
                'pembelians.user_id',
                'pembelians.zone',
                'pembelians.nickname',
                'pembelians.layanan',
                'pembelians.harga AS harga_layanan',
                'pembelians.status AS status_pembelian',
                'pembelians.created_at',
                'pembelians.email_pembeli',
                'pembayarans.no_pembeli',
                'methods.name AS metode_name',
                'methods.images AS metode_image'
            )
            ->orderByDesc('pembayarans.id')
            ->first();

        abort_if(! $data, 404);

        $layanan = Layanan::query()
            ->with('kategori:id,nama,thumbnail')
            ->where('layanan', $data->layanan)
            ->first();

        $kategori = $layanan?->kategori;
        $productName = $kategori?->nama ?: ($data->layanan ?: 'Produk');
        $thumbnail = $this->normalizeAssetPath($kategori?->thumbnail ?: 'assets/logo/favicon.webp');

        $methodCode = trim((string) ($data->metode_pembayaran ?? ''));
        $methodName = $data->metode_name;
        if (blank($methodName) && $methodCode !== '') {
            $matchedMethod = Method::query()
                ->get(['code', 'name'])
                ->first(function (Method $method) use ($methodCode) {
                    $rawCode = trim((string) ($method->getRawOriginal('code') ?? $method->code));
                    $rawName = trim((string) ($method->getRawOriginal('name') ?? $method->name));

                    return strcasecmp($rawCode, $methodCode) === 0
                        || strcasecmp($rawName, $methodCode) === 0;
                });

            $methodName = $matchedMethod?->name;
        }

        if (blank($methodName)) {
            $methodName = Str::of($methodCode)
                ->replace(['_', '-'], ' ')
                ->squish()
                ->title()
                ->value();
        }

        if (blank($methodName)) {
            $methodName = 'Metode Tidak Dikenal';
        }

        $methodImage = $this->normalizeAssetPath($data->metode_image, '');
        if ($methodImage === '') {
            $methodImage = null;
        }

        $paymentCode = Str::upper((string) ($data->metode_pembayaran ?? ''));
        $paymentValue = (string) ($data->no_pembayaran ?? '');
        $paymentStatusRaw = (string) ($data->status_pembayaran ?? '');
        $orderStatusRaw = (string) ($data->status_pembelian ?? '');
        $paymentStatus = Str::lower(trim($paymentStatusRaw));
        $orderStatus = Str::lower(trim($orderStatusRaw));
        $methodNameLower = Str::lower(trim((string) $methodName));
        $isDuitkuGateway = in_array($paymentCode, ['DUITKU'], true) || Str::contains($methodNameLower, 'duitku');
        $isPaymentUrl = filter_var($paymentValue, FILTER_VALIDATE_URL) !== false;
        $isQrMethod = in_array($paymentCode, [
            'QRIS', '11', '17', '23', 'QRISREALTIME', 'SP', 'QRISC', 'QRISOP', 'QRIS_CUSTOM', 'QRIS2', 'QRIS2_OFFLINE', 'QRIS2_RECURRING',
        ], true) || ($isDuitkuGateway && (str_starts_with($paymentValue, '00020101') || in_array($paymentCode, ['SP', 'QRIS'], true)));
        $isQrImage = str_starts_with($paymentValue, 'data:image/')
            || preg_match('/\.(png|jpe?g|webp|svg)(\?.*)?$/i', $paymentValue) === 1
            || ($isQrMethod && ! $isPaymentUrl && $paymentValue !== '');
        $showQrImage = $paymentStatusRaw === 'Belum Lunas' && $isQrMethod && ! $isPaymentUrl && $paymentValue !== '';
        $dynamicQrSource = 'https://api.qrserver.com/v1/create-qr-code/?size=320x320&data=' . urlencode($paymentValue);
        $resolvedQrImageUrl = $isQrImage
            && ! str_starts_with($paymentValue, '00020101')
            && ! $isPaymentUrl
            && preg_match('/\.(png|jpe?g|webp|svg)(\?.*)?$/i', $paymentValue) === 1
                ? $paymentValue
                : $dynamicQrSource;
        $showPayButton = $paymentStatusRaw === 'Belum Lunas' && $isPaymentUrl && ! $showQrImage;
        $payButtonLabel = $isDuitkuGateway ? 'Buka Link Pembayaran' : 'Bayar Sekarang';
        $showCopyPaymentNumber = ! $isPaymentUrl && ! $showQrImage && (
            Str::startsWith($paymentCode, 'MANUAL') ||
            in_array($paymentCode, [
                'ALFAMRT', 'ALFAMART', 'INDOMARET', 'PERMATAVAA', 'BNCVA', 'BSIVA', 'DANAMONVA', 'CIMBVA', 'PERMATAVA',
                'MANDIRIVA', 'BNIVA', 'BCAVA', 'BC', 'M2', 'VA', 'I1', 'B1', 'BT', 'A1', 'NC', 'BR', 'S1',
                'DM', 'BV', 'IR', 'FT', 'BRIVA', 'DUITKU',
            ], true) || ($isDuitkuGateway && ctype_digit(str_replace(['-', ' '], '', $paymentValue)))
        );

        $heroTitle = 'Harap lengkapi pembayaran.';
        $heroDescription = 'Pesanan kamu ' . $data->id_pembelian . ' menunggu pembayaran sebelum dikirim.';

        if (in_array($paymentStatus, ['paid', 'lunas', 'success'], true)) {
            if (in_array($orderStatus, ['sukses', 'success'], true)) {
                $heroTitle = 'Transaksi berhasil diselesaikan.';
                $heroDescription = 'Pesanan kamu ' . $data->id_pembelian . ' sudah berhasil diproses dan selesai.';
            } elseif (in_array($orderStatus, ['proses', 'processing', 'pending'], true)) {
                $heroTitle = 'Pembayaran sudah diterima.';
                $heroDescription = 'Pesanan kamu ' . $data->id_pembelian . ' sedang diproses oleh sistem dan provider.';
            } else {
                $heroTitle = 'Pembayaran sudah diterima.';
                $heroDescription = 'Pesanan kamu ' . $data->id_pembelian . ' sudah masuk dan sedang menunggu update status transaksi.';
            }
        } elseif ($paymentStatus === 'expired') {
            $heroTitle = 'Invoice sudah kedaluwarsa.';
            $heroDescription = 'Batas pembayaran untuk pesanan ' . $data->id_pembelian . ' telah habis. Silakan buat transaksi baru jika masih diperlukan.';
        } elseif (in_array($orderStatus, ['gagal', 'batal', 'failed', 'cancelled'], true)) {
            $heroTitle = 'Transaksi tidak dapat diselesaikan.';
            $heroDescription = 'Pesanan kamu ' . $data->id_pembelian . ' mengalami kendala. Silakan cek detail status transaksi di bawah.';
        }

        $normalizedPayment = $this->normalizePaymentStatus($paymentStatusRaw);
        $normalizedOrder = $this->normalizeOrderStatus($orderStatusRaw);

        $introState = 'pending';
        $introTitle = 'Pembayaran Belum Diterima';
        $introSubtitle = 'Silakan selesaikan pembayaran agar pesanan bisa diproses.';
        $introIcon = 'clock';

        if (in_array($paymentStatus, ['paid', 'lunas', 'success'], true)) {
            if (in_array($orderStatus, ['sukses', 'success'], true)) {
                $introState = 'paid';
                $introTitle = 'Transaksi Berhasil';
                $introSubtitle = 'Pembayaran berhasil diterima dan transaksi telah selesai diproses.';
                $introIcon = 'check';
            } else {
                $introState = 'paid';
                $introTitle = 'Pembayaran Diterima';
                $introSubtitle = 'Pembayaran berhasil diterima. Sistem sedang menyelesaikan proses transaksi.';
                $introIcon = 'check';
            }
        } elseif ($paymentStatus === 'expired') {
            $introState = 'expired';
            $introTitle = 'Pembayaran Kedaluwarsa';
            $introSubtitle = 'Batas waktu pembayaran telah berakhir. Silakan lakukan pembelian ulang jika masih diperlukan.';
            $introIcon = 'x';
        } elseif (in_array($orderStatus, ['gagal', 'batal', 'failed', 'cancelled'], true)) {
            $introState = 'failed';
            $introTitle = 'Transaksi Gagal';
            $introSubtitle = 'Transaksi tidak dapat diselesaikan. Silakan cek detail invoice untuk informasi lebih lanjut.';
            $introIcon = 'warning';
        }

        $introDuration = match ($introState) {
            'expired' => 4700,
            'paid' => 4300,
            'failed' => 4500,
            default => 4300,
        };

        $introBadgeText = match ($introState) {
            'expired' => 'Pembayaran Kedaluwarsa',
            'paid' => 'Pembayaran Diterima',
            'failed' => 'Transaksi Gagal',
            default => 'Menunggu Pembayaran',
        };

        $introLottieSrc = null;
        $introUsesLottie = false;
        $introLottieSequence = [];

        if ($introState === 'pending') {
            $pendingLottieCandidates = ['First.json', 'Second.json'];

            foreach ($pendingLottieCandidates as $candidateFile) {
                $candidatePath = public_path('assets/invoice-intro/lottie/' . $candidateFile);

                if (is_file($candidatePath)) {
                    $introLottieSequence[] = asset('assets/invoice-intro/lottie/' . rawurlencode($candidateFile));
                }
            }

            if (!empty($introLottieSequence)) {
                $introLottieSrc = $introLottieSequence[0];
                $introUsesLottie = true;
            }
        }

        $expiredAt = $data->expired_at
            ? Carbon::parse($data->expired_at)
            : Carbon::parse($data->created_at)->addHours(3);

        $subtotal = (int) round((float) ($data->harga_layanan ?? 0));
        $total = (int) round((float) ($data->harga_pembayaran ?? 0));
        if ($subtotal <= 0) {
            $subtotal = $total;
        }
        $fee = max(0, $total - $subtotal);

        $gtmBuilder = app(GtmDataLayerBuilder::class);
        $gtmInvoiceItem = $gtmBuilder->buildItem([
            'item_id' => $layanan?->id ?: $data->id_pembelian,
            'item_name' => $data->layanan ?: $productName,
            'item_category' => $productName,
            'item_variant' => $kategori?->tipe ?? null,
            'price' => $total,
            'quantity' => 1,
        ]);
        $gtmIdentityPayload = $gtmBuilder->buildCustomerIdentityPayload(
            $data->email_pembeli ?? null,
            $data->no_pembeli ?? null,
            $data->user_id ?? null,
            $data->zone ?? null,
            $data->nickname ?? null,
        );
        $gtmInvoiceEvents = [
            [
                'name' => 'invoice_viewed',
                'payload' => $gtmBuilder->buildInvoiceViewedPayload(
                    (string) $data->id_pembelian,
                    (string) $methodName,
                    $paymentStatusRaw,
                    $orderStatusRaw,
                    $total,
                    $gtmInvoiceItem,
                    'IDR',
                    $gtmIdentityPayload,
                ),
                'dedupe_key' => 'invoice_viewed:' . $data->id_pembelian,
            ],
        ];

        return Inertia::render('Public/Invoice', [
            'invoice' => [
                'orderId' => (string) $data->id_pembelian,
                'productName' => $productName,
                'itemName' => (string) ($data->layanan ?? $productName),
                'thumbnail' => $thumbnail,
                'account' => [
                    'nickname' => (string) ($data->nickname ?? ''),
                    'userId' => (string) ($data->user_id ?? ''),
                    'zone' => (string) ($data->zone ?? ''),
                ],
                'payment' => [
                    'methodName' => (string) $methodName,
                    'methodCode' => (string) $methodCode,
                    'methodImage' => $methodImage,
                    'paymentNumber' => (string) $paymentValue,
                    'paymentUrl' => $isPaymentUrl ? $paymentValue : null,
                    'showCopyPaymentNumber' => (bool) $showCopyPaymentNumber,
                    'showPayButton' => (bool) $showPayButton,
                    'showQrImage' => (bool) $showQrImage,
                    'qrImageUrl' => $showQrImage ? $resolvedQrImageUrl : null,
                    'payButtonLabel' => $payButtonLabel,
                ],
                'amount' => [
                    'subtotal' => $subtotal,
                    'fee' => $fee,
                    'total' => $total,
                    'quantity' => 1,
                ],
                'status' => [
                    'payment' => $normalizedPayment,
                    'order' => $normalizedOrder,
                ],
                'hero' => [
                    'title' => $heroTitle,
                    'description' => $heroDescription,
                ],
                'intro' => [
                    'state' => $introState,
                    'title' => $introTitle,
                    'subtitle' => $introSubtitle,
                    'icon' => $introIcon,
                    'badgeText' => $introBadgeText,
                    'durationMs' => $introDuration,
                    'usesLottie' => $introUsesLottie,
                    'lottieSrc' => $introLottieSrc,
                    'lottieSequence' => $introLottieSequence,
                ],
                'expiry' => [
                    'expiresAt' => $expiredAt->toIso8601String(),
                    'display' => $expiredAt->translatedFormat('d M Y H:i'),
                ],
                'realtime' => [
                    'channel' => InvoiceRealtimeStatus::channelName((string) $data->id_pembelian),
                    'event' => '.InvoiceStatusUpdated',
                ],
                'gtmEvents' => $gtmInvoiceEvents,
            ],
            'meta' => [
                'title' => "Invoice {$data->id_pembelian} - {$settings->judul_web}",
                'description' => "Detail invoice {$data->id_pembelian} untuk {$productName}.",
                'keywords' => "invoice {$data->id_pembelian}, {$productName}, {$settings->judul_web}",
                'canonical' => url("/id/invoices/{$data->id_pembelian}"),
                'image' => url($thumbnail),
            ],
        ]);
    }

    private function normalizePaymentStatus(string $rawStatus): array
    {
        $normalized = Str::lower(trim($rawStatus));

        if (in_array($normalized, ['paid', 'lunas', 'success'], true)) {
            return ['code' => 'paid', 'label' => 'Paid'];
        }

        if ($normalized === 'expired') {
            return ['code' => 'expired', 'label' => 'Expired'];
        }

        return ['code' => 'unpaid', 'label' => 'Unpaid'];
    }

    private function normalizeOrderStatus(string $rawStatus): array
    {
        $normalized = Str::lower(trim($rawStatus));

        return match (true) {
            in_array($normalized, ['sukses', 'success'], true) => ['code' => 'success', 'label' => 'Success'],
            in_array($normalized, ['proses', 'processing'], true) => ['code' => 'processing', 'label' => 'Processing'],
            in_array($normalized, ['expired', 'kedaluwarsa'], true) => ['code' => 'expired', 'label' => 'Expired'],
            in_array($normalized, ['gagal', 'batal', 'failed', 'cancelled'], true) => ['code' => 'failed', 'label' => 'Failed'],
            default => ['code' => 'pending', 'label' => 'Pending'],
        };
    }

    private function normalizeAssetPath(?string $path, string $fallback = '/assets/logo/favicon.webp'): string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return $fallback;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, 'data:image/')) {
            return $path;
        }

        return '/' . ltrim($path, '/');
    }
}
