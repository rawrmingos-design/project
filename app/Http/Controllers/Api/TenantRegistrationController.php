<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Tenancy\TenantRegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TenantRegistrationController extends Controller
{
    public function checkSubdomain(Request $request, TenantRegistrationService $registrationService): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:63'],
        ]);

        $normalized = $registrationService->normalizeSubdomain((string) $request->query('name'));

        return response()->json([
            'available' => $registrationService->isSubdomainAvailable($normalized),
            'subdomain' => $normalized,
        ]);
    }

    public function register(Request $request, TenantRegistrationService $registrationService): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'no_wa' => ['required', 'string', 'max:30'],
            'store_name' => ['required', 'string', 'max:255'],
            'subdomain' => ['required', 'string', 'max:63'],
            'tier' => ['required', 'string', Rule::in(array_keys(TenantRegistrationService::TIER_PRICES))],
            'theme' => ['nullable', 'array'],
            'margin_config' => ['nullable', 'array'],
        ]);

        $result = $registrationService->register($validated);

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
                'gateway_ref' => $result['invoice']->gateway_ref,
                'due_date' => $result['invoice']->due_date?->toIso8601String(),
            ],
        ], 201);
    }
}
