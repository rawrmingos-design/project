<?php

namespace App\Http\Controllers;

use App\Models\Pembelian;
use App\Services\Payments\DuitkuCallbackException;
use App\Services\Payments\DuitkuCallbackService;
use App\Services\Payments\DuitkuInvoiceService;
use App\Services\Payments\DuitkuReconciliationService;
use Duitku\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DuitkuPaymentController extends Controller
{
    public function createInvoice(Pembelian $order, ?string $paymentMethodCode = null): array
    {
        return app(DuitkuInvoiceService::class)->createForPembelian($order, $paymentMethodCode);
    }

    public function handleCallback(Request $request, DuitkuCallbackService $callbackService)
    {
        try {
            $settings = \App\Services\Payments\DuitkuConfiguration::settings();
            if (! $settings) {
                Log::error('duitku.callback.configuration_missing');

                return response('Configuration error', 500);
            }

            $payload = $this->extractCallbackPayload($request);
            $result = $callbackService->processCallback($payload, $settings);

            return response($result['body'], $result['status']);
        } catch (DuitkuCallbackException $exception) {
            return response($exception->getMessage(), $exception->status);
        } catch (\Throwable $exception) {
            Log::error('duitku.callback.internal_error', [
                'error' => $exception->getMessage(),
            ]);

            return response('Error', 500);
        }
    }

    public function checkStatus(string $merchantOrderId): array
    {
        try {
            $result = app(DuitkuReconciliationService::class)->reconcileByMerchantOrderId($merchantOrderId);

            return [
                'success' => true,
                'status' => $result,
            ];
        } catch (\Throwable $exception) {
            Log::error('duitku.status.failed', [
                'merchant_order_id' => $merchantOrderId,
                'error' => $exception->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $exception->getMessage(),
            ];
        }
    }

    public function getPaymentMethods(int|string $amount): array
    {
        try {
            $config = \App\Services\Payments\DuitkuConfiguration::load();
            if (! $config) {
                throw new \RuntimeException('Duitku configuration not found');
            }

            $methods = app(\App\Services\Payments\DuitkuPopClient::class)
                ->getPaymentMethod((string) $amount, $config);

            return [
                'success' => true,
                'methods' => $methods['paymentFee'] ?? [],
            ];
        } catch (\Throwable $exception) {
            Log::error('duitku.payment_methods.failed', [
                'error' => $exception->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $exception->getMessage(),
            ];
        }
    }

    private function extractCallbackPayload(Request $request): array
    {
        $payload = $request->all();
        if ($payload !== []) {
            return $payload;
        }

        $jsonPayload = json_decode((string) $request->getContent(), true);

        return is_array($jsonPayload) ? $jsonPayload : [];
    }
}


