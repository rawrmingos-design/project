<?php

namespace App\Http\Controllers\Api;

use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Models\ResellerIntegration;
use App\Models\User;
use App\Support\PembelianStatus;
use App\Support\ResellerApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SandboxOrderApiController extends OrderApiController
{
    public function order(Request $request)
    {
        $user = $this->resolveApiUser($request);

        if (! $user instanceof User) {
            return $this->unauthenticatedResponse($request);
        }

        $payload = $this->parseJsonPayload($request);

        if ($payload instanceof JsonResponse) {
            return $payload;
        }

        if ($validation = $this->validatePayload($payload, [
            'code' => ['required', 'string'],
            'referenceNumber' => ['required', 'string'],
            'data' => ['required', 'string'],
        ])) {
            return $validation;
        }

        $referenceNumber = trim((string) $payload['referenceNumber']);
        $existingOrder = $this->findExistingOrderByReference($user, $referenceNumber, 'sandbox');

        if ($existingOrder instanceof Pembelian) {
            return response()->json([
                'error' => false,
                'code' => 200,
                'message' => 'Success',
                'data' => [
                    'invoiceNumber' => $existingOrder->order_id,
                    'status' => PembelianStatus::apiStatusCode($existingOrder->status),
                    'isDuplicate' => true,
                ],
            ], 200);
        }

        $target = $this->resolveOrderTargetByExternalCode(trim((string) $payload['code']));
        $service = $target['service'];

        if (! $service) {
            return ResellerApiResponse::error(
                'Code Not Found',
                ResellerApiResponse::CODE_NOT_FOUND,
                404,
            );
        }

        $integration = $request->attributes->get('sandbox_reseller_integration');

        if (! $integration instanceof ResellerIntegration) {
            return ResellerApiResponse::error(
                'Invalid or inactive reseller integration code',
                ResellerApiResponse::INVALID_INTEGRATION_CODE,
                403,
            );
        }

        $datagame = str_contains((string) $payload['data'], '|')
            ? explode('|', (string) $payload['data'])
            : [(string) $payload['data']];

        $orderId = sprintf('WEJIZY-SBX%s%s', now()->format('His'), Str::upper(Str::random(4)));

        DB::transaction(function () use ($user, $integration, $service, $datagame, $orderId, $referenceNumber): void {
            Pembelian::query()->create([
                'username' => $user->username,
                'reseller_integration_id' => $integration->getKey(),
                'order_id' => $orderId,
                'user_id' => $datagame[0],
                'zone' => $datagame[1] ?? null,
                'layanan' => $service->layanan,
                'harga' => 0,
                'profit' => 0,
                'status' => 'Pending',
                'provider_order_id' => 'SANDBOX-' . $orderId,
                'log' => json_encode([
                    'environment' => 'sandbox',
                    'source' => 'reseller_h2h',
                    'sandbox' => true,
                    'simulated' => true,
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'traffic_source' => 'reseller_h2h',
                'tipe_transaksi' => 'game',
                'environment' => 'sandbox',
                'is_sandbox' => true,
                'active_layanan_id' => $service->getKey(),
                'active_provider_code' => 'sandbox',
                'active_provider_sku' => (string) $service->provider_id,
            ]);

            Pembayaran::query()->create([
                'order_id' => $orderId,
                'harga' => 0,
                'no_pembayaran' => 'SANDBOX',
                'no_pembeli' => $user->no_wa,
                'status' => 'Lunas',
                'metode' => 'SANDBOX',
                'reference' => $referenceNumber,
                'expired_at' => null,
            ]);
        });

        return response()->json([
            'error' => false,
            'code' => 200,
            'message' => 'Success',
            'data' => [
                'invoiceNumber' => $orderId,
                'status' => 'Pending',
            ],
        ], 200);
    }

    public function statusOrder(Request $request, $invoice)
    {
        $user = $this->resolveApiUser($request);

        if (! $user instanceof User) {
            return $this->unauthenticatedResponse($request);
        }

        $cek = Pembelian::query()
            ->where('order_id', trim((string) $invoice))
            ->where('username', $user->username)
            ->where(function ($query): void {
                $query->where('is_sandbox', true)
                    ->orWhere('environment', 'sandbox');
            })
            ->first();

        if (! $cek) {
            return ResellerApiResponse::error(
                'Invoice Not Found',
                ResellerApiResponse::INVOICE_NOT_FOUND,
                404,
            );
        }

        return response()->json([
            'error' => false,
            'code' => 200,
            'message' => 'Success',
            'data' => $this->buildStatusPayload($cek),
        ], 200);
    }

    public function simulateStatus(Request $request, $invoice)
    {
        $user = $this->resolveApiUser($request);

        if (! $user instanceof User) {
            return $this->unauthenticatedResponse($request);
        }

        $payload = $this->parseJsonPayload($request);

        if ($payload instanceof JsonResponse) {
            return $payload;
        }

        if ($validation = $this->validatePayload($payload, [
            'status' => ['required', 'string'],
        ])) {
            return $validation;
        }

        $requestedStatus = trim((string) $payload['status']);
        $normalizedStatus = PembelianStatus::normalize($requestedStatus);

        if (! in_array($normalizedStatus, [
            PembelianStatus::PENDING,
            PembelianStatus::PROCESSING,
            PembelianStatus::SUCCESS,
            PembelianStatus::FAILED,
            PembelianStatus::CANCELLED,
        ], true)) {
            return ResellerApiResponse::error(
                'Validation failed',
                ResellerApiResponse::VALIDATION_FAILED,
                422,
                ['status' => ['The selected status is invalid.']],
            );
        }

        $pembelian = Pembelian::query()
            ->where('order_id', trim((string) $invoice))
            ->where('username', $user->username)
            ->where(function ($query): void {
                $query->where('is_sandbox', true)
                    ->orWhere('environment', 'sandbox');
            })
            ->first();

        if (! $pembelian) {
            return ResellerApiResponse::error(
                'Invoice Not Found',
                ResellerApiResponse::INVOICE_NOT_FOUND,
                404,
            );
        }

        $nextStatus = PembelianStatus::preferredDatabaseLabel($requestedStatus);

        if ($pembelian->status !== $nextStatus) {
            $metadata = $pembelian->sandboxMetadata();
            $metadata['environment'] = 'sandbox';
            $metadata['source'] = 'reseller_h2h';
            $metadata['sandbox'] = true;
            $metadata['simulated'] = true;
            $metadata['last_simulated_status'] = PembelianStatus::apiStatusCode($requestedStatus);

            $pembelian->fill([
                'status' => $nextStatus,
                'log' => json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ])->save();
        }

        return response()->json([
            'error' => false,
            'code' => 200,
            'message' => 'Success',
            'data' => $this->buildStatusPayload($pembelian->fresh()),
        ], 200);
    }
}
