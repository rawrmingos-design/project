<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Tenancy\TenantRegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class TenantRegistrationController extends Controller
{
    public function checkSubdomain(Request $request, TenantRegistrationService $registrationService): JsonResponse
    {
        abort_if((bool) config('tenancy.disabled', true), 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:63'],
        ]);

        $normalized = $registrationService->normalizeSubdomain((string) $validated['name']);

        return response()->json([
            'available' => $registrationService->isSubdomainAvailable($normalized),
            'subdomain' => $normalized,
        ]);
    }

    public function register(Request $request, TenantRegistrationService $registrationService): JsonResponse
    {
        abort_if((bool) config('tenancy.disabled', true), 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'no_wa' => ['required', 'string', 'max:30'],
            'store_name' => ['required', 'string', 'max:255'],
            'subdomain' => ['required', 'string', 'max:63'],
            'tier' => ['required', 'string', Rule::in(TenantRegistrationService::SELF_SERVICE_TIERS)],
            'terms_accepted' => ['required', 'accepted'],
            'theme' => ['nullable', 'array'],
            'margin_config' => ['nullable', 'array'],
        ]);

        try {
            $result = $registrationService->register($validated);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'status' => false,
                'message' => 'Invoice pembayaran belum bisa dibuat. Coba lagi beberapa saat.',
            ], 502);
        }

        return response()->json([
            'status' => true,
            'tenant' => [
                'id' => $result['tenant']->id,
                'name' => $result['tenant']->name,
                'subdomain' => $result['tenant']->subdomain,
                'status' => $result['tenant']->status,
            ],
            'invoice' => [
                'id' => $result['invoice']->id,
                'amount' => $result['invoice']->amount,
                'status' => $result['invoice']->status,
                'gateway' => $result['invoice']->gateway,
                'gateway_ref' => $result['invoice']->gateway_ref,
                'payment_url' => data_get($result['invoice']->metadata, 'duitku.payment_url'),
                'duitku_reference' => data_get($result['invoice']->metadata, 'duitku.reference'),
                'va_number' => data_get($result['invoice']->metadata, 'duitku.va_number'),
                'qr_string' => data_get($result['invoice']->metadata, 'duitku.qr_string'),
                'due_date' => $result['invoice']->due_date?->toIso8601String(),
            ],
        ], 201);
    }
}
