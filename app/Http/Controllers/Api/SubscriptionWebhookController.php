<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionInvoice;
use App\Tenancy\TenantProvisioningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionWebhookController extends Controller
{
    public function __invoke(Request $request, TenantProvisioningService $provisioningService): JsonResponse
    {
        $token = (string) config('services.tenant_subscription.webhook_token', env('TENANT_SUBSCRIPTION_WEBHOOK_TOKEN', ''));

        if ($token !== '' && ! hash_equals($token, (string) $request->bearerToken())) {
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

        return response()->json([
            'status' => true,
            'invoice_id' => $invoice->id,
            'tenant_status' => $invoice->subscription->tenant->status,
            'subscription_status' => $invoice->subscription->status,
        ]);
    }
}
