<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\CariController as LegacyCariController;
use App\Http\Controllers\Controller;
use App\Models\Pembelian;
use App\Services\PublicSiteConfigService;
use App\Support\PublicThemeRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class TransactionLookupPageController extends Controller
{
    private const SESSION_RECENT_ORDER_IDS_KEY = 'public_recent_order_ids';
    private const RECENT_LIMIT = 10;
    private const LOOKUP_CACHE_KEY_PREFIX = 'public:invoice-lookup:';
    private const LOOKUP_CACHE_TTL_SECONDS = 180;
    private const LOOKUP_CACHE_MISS_TTL_SECONDS = 25;
    private const LOOKUP_CACHE_MISS_SENTINEL = '__miss__';

    public function index(
        Request $request,
        PublicSiteConfigService $siteConfigService,
        LegacyCariController $legacyCariController,
    ): Response|\Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\Foundation\Application {
        $settings = $siteConfigService->getSettings();

        if (($settings->public_theme ?? PublicThemeRegistry::DEFAULT) === PublicThemeRegistry::DEFAULT) {
            return $legacyCariController->create();
        }

        $recentScope = $this->resolveRecentTransactionsScope($request);
        $recentTransactions = $this->buildRecentTransactions($request, $recentScope['key']);

        return Inertia::render('Public/CheckTransactions', [
            'recentTransactions' => $recentTransactions,
            'recentTransactionsScope' => $recentScope,
            'meta' => [
                'title' => "Cek Transaksi - {$settings->judul_web}",
                'description' => 'Cek detail transaksi dengan nomor invoice dan lihat riwayat transaksi yang tersimpan untuk akun atau browser saat ini.',
                'keywords' => "cek transaksi, cek invoice, status transaksi, {$settings->judul_web}",
                'canonical' => url('/id/invoices'),
                'image' => url($siteConfigService->normalizeAssetPath($settings->logo_favicon)),
            ],
        ]);
    }

    public function lookup(Request $request): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'string', 'max:80'],
        ]);

        $invoiceId = trim((string) $validated['id']);
        $cacheKey = self::LOOKUP_CACHE_KEY_PREFIX . sha1(strtolower($invoiceId));
        $cachedOrderId = Cache::get($cacheKey);
        $orderId = null;

        if (is_string($cachedOrderId) && $cachedOrderId !== '' && $cachedOrderId !== self::LOOKUP_CACHE_MISS_SENTINEL) {
            $orderId = $cachedOrderId;
        } elseif ($cachedOrderId === self::LOOKUP_CACHE_MISS_SENTINEL) {
            $orderId = null;
        } else {
            $order = Pembelian::query()
                ->select(['order_id'])
                ->where('order_id', $invoiceId)
                ->orWhere('display_order_id', $invoiceId)
                ->first();

            if ($order?->order_id) {
                $orderId = (string) $order->order_id;
                Cache::put($cacheKey, $orderId, now()->addSeconds(self::LOOKUP_CACHE_TTL_SECONDS));
            } else {
                Cache::put($cacheKey, self::LOOKUP_CACHE_MISS_SENTINEL, now()->addSeconds(self::LOOKUP_CACHE_MISS_TTL_SECONDS));
            }
        }

        if (! $orderId) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Nomor invoice tidak ditemukan. Periksa kembali lalu coba lagi.',
                ], 404);
            }

            return back()->with('error', 'Order not found');
        }

        $redirectUrl = route('pembelian', ['order' => $orderId]);

        if ($request->expectsJson()) {
            return response()->json([
                'status' => true,
                'message' => 'Invoice ditemukan.',
                'redirect_url' => $redirectUrl,
            ]);
        }

        return redirect($redirectUrl);
    }

    public static function rememberRecentOrderId(Request $request, string $orderId): void
    {
        $trimmedOrderId = trim($orderId);

        if ($trimmedOrderId === '') {
            return;
        }

        $recentOrderIds = collect($request->session()->get(self::SESSION_RECENT_ORDER_IDS_KEY, []))
            ->prepend($trimmedOrderId)
            ->filter(fn (mixed $value): bool => is_string($value) && trim($value) !== '')
            ->map(fn (string $value): string => trim($value))
            ->unique()
            ->take(self::RECENT_LIMIT)
            ->values()
            ->all();

        $request->session()->put(self::SESSION_RECENT_ORDER_IDS_KEY, $recentOrderIds);
    }

    private function resolveRecentTransactionsScope(Request $request): array
    {
        if (Auth::check()) {
            return [
                'key' => 'auth-user',
                'title' => 'Transaksi Kamu Terakhir',
                'description' => 'Berikut transaksi terbaru dari akun yang sedang login.',
            ];
        }

        $recentOrderIds = $request->session()->get(self::SESSION_RECENT_ORDER_IDS_KEY, []);

        if (! empty($recentOrderIds)) {
            return [
                'key' => 'guest-session',
                'title' => 'Transaksi Terakhir',
                'description' => 'Berikut transaksi terakhir yang tersimpan di browser ini.',
            ];
        }

        return [
            'key' => 'guest-empty',
            'title' => 'Transaksi Terakhir',
            'description' => 'Belum ada transaksi yang tersimpan di browser ini. Gunakan nomor invoice untuk cek detail pembelian.',
        ];
    }

    private function buildRecentTransactions(Request $request, string $scopeKey): array
    {
        $query = Pembelian::query()->with('pembayaran');

        if ($scopeKey === 'auth-user') {
            $query->where('username', Auth::user()->username);
        } elseif ($scopeKey === 'guest-session') {
            $recentOrderIds = collect($request->session()->get(self::SESSION_RECENT_ORDER_IDS_KEY, []))
                ->filter(fn (mixed $value): bool => is_string($value) && trim($value) !== '')
                ->map(fn (string $value): string => trim($value))
                ->unique()
                ->take(self::RECENT_LIMIT)
                ->values()
                ->all();

            if (empty($recentOrderIds)) {
                return [];
            }

            $query->whereIn('order_id', $recentOrderIds);
        } else {
            return [];
        }

        return $query
            ->latest('created_at')
            ->limit(self::RECENT_LIMIT)
            ->get()
            ->map(function (Pembelian $pembelian): array {
                $payment = $pembelian->pembayaran;

                return [
                    'invoiceId' => (string) ($pembelian->display_order_id ?: $pembelian->order_id),
                    'invoiceUrl' => route('pembelian', ['order' => $pembelian->order_id]),
                    'createdAt' => optional($pembelian->created_at)->timezone(config('app.timezone'))->format('d-m-Y H:i:s'),
                    'phone' => (string) ($payment->no_pembeli ?? '-'),
                    'price' => (int) round((float) ($pembelian->harga ?? 0)),
                    'status' => $this->mapTransactionStatus(
                        (string) ($pembelian->status ?? ''),
                        (string) ($payment->status ?? '')
                    ),
                ];
            })
            ->values()
            ->all();
    }

    private function mapTransactionStatus(string $orderStatusRaw, string $paymentStatusRaw): array
    {
        $orderStatus = strtolower(trim($orderStatusRaw));
        $paymentStatus = strtolower(trim($paymentStatusRaw));

        if (in_array($orderStatus, ['gagal', 'batal', 'failed', 'cancelled', 'canceled'], true)) {
            return [
                'label' => 'Cancelled',
                'badge' => 'invoice-badge--unpaid',
            ];
        }

        if (in_array($orderStatus, ['sukses', 'success'], true)) {
            return [
                'label' => 'Success',
                'badge' => 'invoice-badge--paid',
            ];
        }

        if (in_array($orderStatus, ['proses', 'processing'], true)) {
            return [
                'label' => 'Processing',
                'badge' => 'invoice-badge--processing',
            ];
        }

        if ($paymentStatus === 'expired') {
            return [
                'label' => 'Expired',
                'badge' => 'invoice-badge--expired',
            ];
        }

        if (in_array($paymentStatus, ['paid', 'lunas', 'success'], true)) {
            return [
                'label' => 'Paid',
                'badge' => 'invoice-badge--paid',
            ];
        }

        return [
            'label' => 'Pending',
            'badge' => 'invoice-badge--expired',
        ];
    }
}
