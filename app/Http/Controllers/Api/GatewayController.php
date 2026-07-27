<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Method;
use App\Models\Voucher;
use App\Services\Gateway\GatewayCatalogService;
use App\Services\Gateway\GatewayCheckIdService;
use App\Services\Gateway\GatewayInvoiceService;
use App\Services\Gateway\GatewayPricingService;
use App\Services\PaymentMethodCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GatewayController extends Controller
{
    public function categoryTypes(Request $request, GatewayCatalogService $catalog): JsonResponse
    {
        return response()->json($catalog->categoryTypes([
            'q' => $request->query('q'),
        ]));
    }

    public function categories(Request $request, GatewayCatalogService $catalog): JsonResponse
    {
        return response()->json($catalog->categories(Auth::guard('sanctum')->user(), [
            'q' => $request->query('q'),
            'type' => $request->query('type'),
        ]));
    }

    public function products(Request $request, GatewayCatalogService $catalog): JsonResponse
    {
        return response()->json($catalog->products(Auth::guard('sanctum')->user(), [
            'q' => $request->query('q'),
        ]));
    }

    public function services(Request $request, GatewayCatalogService $catalog): JsonResponse
    {
        return response()->json($catalog->servicesQuery(Auth::guard('sanctum')->user(), [
            'category' => $request->query('category'),
            'service_id' => $request->query('service_id'),
            'q' => $request->query('q'),
        ]));
    }

    public function serviceDetail(int $serviceId, GatewayCatalogService $catalog): JsonResponse
    {
        $result = $catalog->serviceById($serviceId, Auth::guard('sanctum')->user());

        return response()->json($result, ($result['ok'] ?? false) ? 200 : 404);
    }

    public function paymentMethods(Request $request, PaymentMethodCatalogService $catalog): JsonResponse
    {
        $methods = $catalog->getVisibleMethods();

        $user = Auth::guard('sanctum')->user();

        $filtered = $methods
            ->reject(function (Method $method) use ($user): bool {
                if (! $user && $method->isSaldoMethod()) {
                    return true;
                }

                if (app()->environment('production') && $method->isDemoMethod()) {
                    return true;
                }

                return false;
            })
            ->map(function (Method $method): array {
                return [
                    'code' => (string) $method->code,
                    'name' => (string) $method->name,
                    'type' => (string) $method->tipe,
                    'display_category' => $method->displayCategory?->name,
                    'image' => $method->image_url,
                    'description' => (string) ($method->keterangan ?? ''),
                    'fee' => [
                        'percent' => (float) $method->fee_percent,
                        'fixed' => (int) $method->fix_fee,
                    ],
                    'limits' => [
                        'min' => (int) ($method->min_pembelian ?? 0),
                        'max' => (int) ($method->max_pembelian ?? 0),
                    ],
                ];
            })
            ->values()
            ->all();

        return response()->json([
            'ok' => true,
            'message' => 'Metode pembayaran berhasil dimuat.',
            'data' => $filtered,
        ]);
    }

    public function validateVoucher(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:64'],
            'amount' => ['nullable', 'integer', 'min:0'],
        ]);

        $voucher = Voucher::query()->where('kode', $validated['code'])->first();

        if (! $voucher) {
            return response()->json([
                'ok' => false,
                'error_code' => 'VOUCHER_NOT_FOUND',
                'message' => 'Voucher tidak ditemukan.',
                'data' => null,
            ], 404);
        }

        if (! $voucher->isUsable()) {
            return response()->json([
                'ok' => false,
                'error_code' => $voucher->isExpired() ? 'VOUCHER_EXPIRED' : 'VOUCHER_OUT_OF_STOCK',
                'message' => $voucher->isExpired() ? 'Voucher sudah kadaluarsa.' : 'Stock voucher habis.',
                'data' => null,
            ], 422);
        }

        $amount = (int) ($validated['amount'] ?? 0);
        $minTrx = (int) ($voucher->mintrx ?? 0);

        if ($amount > 0 && $minTrx > 0 && $amount < $minTrx) {
            return response()->json([
                'ok' => false,
                'error_code' => 'MIN_TRANSACTION_NOT_MET',
                'message' => 'Minimal transaksi untuk voucher ini adalah Rp ' . number_format($minTrx, 0, ',', '.'),
                'data' => [
                    'code' => (string) $voucher->kode,
                    'min_transaction' => $minTrx,
                    'current_amount' => $amount,
                    'valid' => false,
                ],
            ], 422);
        }

        $discount = 0;
        if ($amount > 0) {
            $discount = min((int) round($amount * ((float) $voucher->promo / 100)), (int) $voucher->max_potongan);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Voucher valid.',
            'data' => [
                'code' => (string) $voucher->kode,
                'discount_percent' => (float) $voucher->promo,
                'max_discount' => (int) $voucher->max_potongan,
                'min_transaction' => $minTrx,
                'stock' => (int) $voucher->stock,
                'expires_at' => $voucher->expired_at?->toIso8601String(),
                'valid' => true,
                'estimated_discount' => $amount > 0 ? $discount : null,
            ],
        ]);
    }

    public function price(Request $request, GatewayPricingService $pricing): JsonResponse
    {
        $validated = $request->validate([
            'service_id' => ['required_without:service', 'integer'],
            'service' => ['required_without:service_id', 'integer'],
            'payment_method' => ['required', 'string', 'max:64'],
            'qty' => ['nullable', 'integer', 'min:1', 'max:999'],
            'voucher' => ['nullable', 'string', 'max:64'],
            'ktg_tipe' => ['nullable', 'string', 'max:50'],
        ]);

        return response()->json($pricing->quote($validated, Auth::guard('sanctum')->user()));
    }

    public function checkId(Request $request, GatewayCheckIdService $checkId): JsonResponse
    {
        $validated = $request->validate([
            'category_code' => ['required', 'string', 'max:100'],
            'service_id' => ['nullable', 'integer'],
            'service' => ['nullable', 'integer'],
            'uid' => ['required', 'string', 'max:50'],
            'zone' => ['nullable', 'string', 'max:50'],
        ]);

        $result = $checkId->check($validated);

        return response()->json($result, ($result['ok'] ?? false) ? 200 : 422);
    }

    public function createInvoice(Request $request, GatewayInvoiceService $invoices): JsonResponse
    {
        $validated = $request->validate([
            'source' => ['required', 'string', 'in:whatsapp_gateway,telegram_gateway'],
            'service' => ['required_without:service_id', 'integer'],
            'service_id' => ['required_without:service', 'integer'],
            'payment_method' => ['required', 'string', 'max:64'],
            'nomor' => ['nullable', 'regex:/^[0-9]{9,16}$/', 'required_without_all:whatsapp,email'],
            'whatsapp' => ['nullable', 'regex:/^[0-9]{9,16}$/'],
            'email' => ['nullable', 'email', 'max:255', 'required_without_all:nomor,whatsapp'],
            'uid' => ['nullable', 'string', 'max:50'],
            'zone' => ['nullable', 'string', 'max:50'],
            'nickname' => ['nullable', 'string', 'max:100'],
            'voucher' => ['nullable', 'string', 'max:64'],
            'ktg_tipe' => ['nullable', 'string', 'max:50'],
            'qty' => ['nullable', 'integer', 'min:1', 'max:999'],
            'idempotency_key' => ['nullable', 'string', 'max:255'],
            'external_user_id' => ['nullable', 'string', 'max:255'],
            'message_id' => ['nullable', 'string', 'max:255'],
        ]);

        $source = (string) $validated['source'];
        unset($validated['source']);

        $result = $invoices->createInvoice($validated, Auth::guard('sanctum')->user(), $source, [
            'ip' => $request->ip(),
            'idempotency_key' => $validated['idempotency_key'] ?? $request->headers->get('X-Idempotency-Key'),
            'external_user_id' => $validated['external_user_id'] ?? null,
            'message_id' => $validated['message_id'] ?? null,
        ]);

        return response()->json($result, ($result['ok'] ?? false) ? 200 : 422);
    }

    public function status(string $orderId, Request $request, GatewayInvoiceService $invoices): JsonResponse
    {
        $validated = $request->validate([
            'source' => ['nullable', 'string', 'in:whatsapp_gateway,telegram_gateway'],
            'external_user_id' => ['nullable', 'string', 'max:255'],
        ]);

        $result = $invoices->status($orderId, Auth::guard('sanctum')->user(), $validated);

        return response()->json($result, ($result['ok'] ?? false) ? 200 : 404);
    }
}
