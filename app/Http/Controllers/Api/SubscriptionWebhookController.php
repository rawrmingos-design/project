<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionInvoiceEvent;
use App\Tenancy\DuitkuSubscriptionPaymentService;
use App\Tenancy\TenantProvisioningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SubscriptionWebhookController extends Controller
{
    public function __invoke(Request $request, TenantProvisioningService $provisioningService): JsonResponse
    {
        $token = (string) config('services.tenant_subscription.webhook_token', env('TENANT_SUBSCRIPTION_WEBHOOK_TOKEN', ''));

        if ($token === '') {
            return response()->json([
                'status' => false,
                'message' => 'Webhook token not configured.',
            ], 401);
        }

        if (! hash_equals($token, (string) $request->bearerToken())) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized webhook.',
            ], 401);
        }

        $validated = $request->validate([
            'invoice_id' => ['nullable', 'required_without:gateway_ref', 'integer', 'exists:subscription_invoices,id'],
            'gateway_ref' => ['nullable', 'required_without:invoice_id', 'string', 'max:255'],
            'status' => ['required', 'string'],
        ]);

        if (strtolower((string) $validated['status']) !== SubscriptionInvoice::STATUS_PAID) {
            return response()->json([
                'status' => true,
                'message' => 'Webhook ignored for non-paid status.',
            ]);
        }

        $invoice = SubscriptionInvoice::query()
            ->when($validated['invoice_id'] ?? null, fn ($query, $id) => $query->whereKey($id))
            ->when($validated['gateway_ref'] ?? null, fn ($query, $ref) => $query->where('gateway_ref', $ref))
            ->firstOrFail();

        $invoice = $provisioningService->markInvoicePaid($invoice, $validated['gateway_ref'] ?? null);

        SubscriptionInvoiceEvent::record(
            $invoice,
            SubscriptionInvoiceEvent::TYPE_CALLBACK,
            $invoice->status,
            $validated['gateway_ref'] ?? $invoice->gateway_ref,
            $request->all(),
            ['source' => 'manual_subscription_webhook'],
        );

        return response()->json([
            'status' => true,
            'invoice_id' => $invoice->id,
            'tenant_status' => $invoice->subscription->tenant->status,
            'subscription_status' => $invoice->subscription->status,
        ]);
    }

    public function duitku(
        Request $request,
        DuitkuSubscriptionPaymentService $duitkuService,
        TenantProvisioningService $provisioningService
    ): Response {
        $payload = $duitkuService->extractCallbackPayload($request);

        if (empty($payload)) {
            return response('Invalid payload', 400);
        }

        if (! $duitkuService->isValidCallbackSignature($payload)) {
            return response('Invalid signature', 400);
        }

        if ((string) ($payload['merchantCode'] ?? '') !== $duitkuService->merchantCode()) {
            return response('Invalid merchant', 400);
        }

        $merchantOrderId = (string) ($payload['merchantOrderId'] ?? '');
        $invoice = SubscriptionInvoice::query()
            ->where('gateway', 'duitku')
            ->where('gateway_ref', $merchantOrderId)
            ->firstOrFail();

        if ((int) ($payload['amount'] ?? 0) !== (int) $invoice->amount) {
            return response('Invalid amount', 400);
        }

        $metadata = $invoice->metadata ?: [];
        $storedReference = (string) data_get($metadata, 'duitku.reference', '');
        $callbackReference = (string) ($payload['reference'] ?? '');

        if ($storedReference !== '' && $callbackReference !== '' && $storedReference !== $callbackReference) {
            return response('Invalid reference', 400);
        }

        $lastCallback = $duitkuService->callbackMetadata($payload);
        $metadataMerge = [
            'duitku' => [
                'reference' => $callbackReference ?: $storedReference,
                'last_callback' => $lastCallback,
            ],
        ];

        $resultCode = (string) ($payload['resultCode'] ?? '');

        SubscriptionInvoiceEvent::record(
            $invoice,
            SubscriptionInvoiceEvent::TYPE_CALLBACK,
            $resultCode,
            $callbackReference ?: $storedReference,
            $payload,
            ['source' => 'duitku_subscription_callback'],
        );

        if ($resultCode === '00') {
            $provisioningService->markInvoicePaid($invoice, $merchantOrderId, $metadataMerge);

            return response('SUCCESS', 200);
        }

        if ($resultCode === '01') {
            $provisioningService->markInvoiceExpired($invoice, $metadataMerge);

            return response('SUCCESS', 200);
        }

        $invoice->forceFill([
            'metadata' => array_replace_recursive($metadata, $metadataMerge),
        ])->save();

        return response('SUCCESS', 200);
    }
}
