<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Tenancy\Concerns\ResolvesTenantHost;
use App\Tenancy\TenantRegistrationService;
use App\Models\SubscriptionInvoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class TenantRegistrationController extends Controller
{
    use ResolvesTenantHost;

    public function checkSubdomain(Request $request, TenantRegistrationService $registrationService): JsonResponse
    {
        abort_if((bool) config('tenancy.disabled', true), 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:63'],
        ]);

        $hasil = $registrationService->checkSubdomain((string) $validated['name']);

        return response()->json([
            'available' => $hasil['available'],
            'subdomain' => $hasil['subdomain'],
            'reason' => $hasil['reason'],
        ]);
    }

    public function register(Request $request, TenantRegistrationService $registrationService): JsonResponse
    {
        abort_if((bool) config('tenancy.disabled', true), 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            // Email sengaja TIDAK unique di sini: email yang sudah terdaftar
            // ditangani jalur resume (dengan verifikasi password), bukan
            // ditolak mentah-mentah.
            'email' => ['required', 'email', 'max:255'],
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
            'resumed' => (bool) ($result['resumed'] ?? false),
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
                // B1: token publik ikut dikirim supaya formulir bisa mengarahkan
                // pembeli ke halaman tagihan ber-token `/tagihan/{token}` —
                // bukan lagi memakai `payment_url` mentah ke Duitku (B4).
                // Idempoten: token yang sudah terkirim lewat email/WA tidak
                // pernah berputar (D13.1).
                'public_token' => $result['invoice']->ensurePublicToken(),
                'billing_url' => $this->platformUrl(
                    route('billing.public', ['token' => $result['invoice']->ensurePublicToken()], absolute: false)
                ),
                'payment_url' => data_get($result['invoice']->metadata, 'duitku.payment_url'),
                'duitku_reference' => data_get($result['invoice']->metadata, 'duitku.reference'),
                'va_number' => data_get($result['invoice']->metadata, 'duitku.va_number'),
                'qr_string' => data_get($result['invoice']->metadata, 'duitku.qr_string'),
                'due_date' => $result['invoice']->due_date?->toIso8601String(),
            ],
        ], ($result['resumed'] ?? false) ? 200 : 201);
    }
}
