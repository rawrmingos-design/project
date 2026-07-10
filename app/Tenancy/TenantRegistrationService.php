<?php

namespace App\Tenancy;

use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TenantRegistrationService
{
    public const TIER_PRICES = [
        'starter' => 500_000,
        'business' => 1_500_000,
        'enterprise' => 0,
    ];

    public const SELF_SERVICE_TIERS = [
        'starter',
        'business',
    ];

    public function register(array $data): array
    {
        $subdomain = $this->normalizeSubdomain((string) ($data['subdomain'] ?? ''));
        $tier = strtolower(trim((string) ($data['tier'] ?? 'starter')));

        $this->validateSubdomain($subdomain);

        if (! in_array($tier, self::SELF_SERVICE_TIERS, true)) {
            throw ValidationException::withMessages([
                'tier' => 'Paket tidak tersedia untuk pendaftaran mandiri.',
            ]);
        }

        return DB::transaction(function () use ($data, $subdomain, $tier): array {
            $amount = self::TIER_PRICES[$tier];
            $gatewayRef = 'SUB-' . now()->format('ymdHis') . '-' . Str::upper(Str::random(6));
            $gateway = $amount > 0 ? 'duitku' : 'manual';

            $owner = User::query()->create([
                'name' => trim((string) $data['name']),
                'username' => $this->uniqueUsername($subdomain),
                'password' => Hash::make((string) $data['password']),
                'email' => trim((string) $data['email']),
                'no_wa' => $this->normalizePhone((string) ($data['no_wa'] ?? '')),
                'role' => 'Member',
                'balance' => 0,
                'referral_code' => $this->uniqueReferralCode(),
            ]);

            $tenant = Tenant::query()->create([
                'owner_user_id' => $owner->id,
                'name' => trim((string) $data['store_name']),
                'subdomain' => $subdomain,
                'tier' => $tier,
                'status' => Tenant::STATUS_PENDING_PAYMENT,
                'margin_config' => $data['margin_config'] ?? $this->defaultMarginConfig(),
                'theme' => $data['theme'] ?? $this->defaultTheme(),
                'settings' => [
                    'contact_whatsapp' => $owner->no_wa,
                ],
            ]);

            $owner->forceFill([
                'tenant_id' => $tenant->id,
            ])->save();

            $subscription = Subscription::query()->create([
                'tenant_id' => $tenant->id,
                'tier' => $tier,
                'price' => $amount,
                'status' => Subscription::STATUS_PENDING,
                'gateway_ref' => $gatewayRef,
            ]);

            $invoice = SubscriptionInvoice::query()->create([
                'subscription_id' => $subscription->id,
                'amount' => $amount,
                'status' => SubscriptionInvoice::STATUS_PENDING,
                'gateway' => $gateway,
                'gateway_ref' => $gatewayRef,
                'due_date' => now()->addDay(),
                'metadata' => [
                    'source' => 'tenant_self_registration',
                    'store_name' => $tenant->name,
                    'subdomain' => $subdomain,
                    'currency' => 'IDR',
                ],
            ]);

            if ($gateway === 'duitku') {
                $invoice = app(DuitkuSubscriptionPaymentService::class)->createAndStoreInvoice($invoice);
            }

            DB::afterCommit(function () use ($invoice) {
                \App\Jobs\SendTenantNotificationJob::dispatch(
                    $invoice->id,
                    \App\Jobs\SendTenantNotificationJob::EVENT_REGISTRATION_INVOICE
                );
            });

            return [
                'owner' => $owner,
                'tenant' => $tenant,
                'subscription' => $subscription,
                'invoice' => $invoice->fresh('subscription.tenant.owner'),
            ];
        });
    }

    public function isSubdomainAvailable(string $subdomain): bool
    {
        $normalized = $this->normalizeSubdomain($subdomain);

        try {
            $this->validateSubdomain($normalized);
        } catch (ValidationException) {
            return false;
        }

        return true;
    }

    public function normalizeSubdomain(string $subdomain): string
    {
        $subdomain = Str::lower(trim($subdomain));
        $subdomain = preg_replace('/[^a-z0-9-]/', '-', $subdomain) ?? '';
        $subdomain = preg_replace('/-+/', '-', $subdomain) ?? '';

        return trim($subdomain, '-');
    }

    public function defaultMarginConfig(): array
    {
        return [
            'markup_type' => 'percent',
            'markup_value' => 10,
        ];
    }

    public function defaultTheme(): array
    {
        return [
            'primary_color' => '#A855F7',
            'accent_color' => '#06B6D4',
        ];
    }

    private function validateSubdomain(string $subdomain): void
    {
        if (! preg_match('/^[a-z0-9](?:[a-z0-9-]{1,61}[a-z0-9])$/', $subdomain)) {
            throw ValidationException::withMessages([
                'subdomain' => 'Subdomain harus 3-63 karakter huruf, angka, atau strip.',
            ]);
        }

        if (in_array($subdomain, Tenant::RESERVED_SUBDOMAINS, true)) {
            throw ValidationException::withMessages([
                'subdomain' => 'Subdomain ini tidak dapat digunakan.',
            ]);
        }

        if (Tenant::query()->where('subdomain', $subdomain)->exists()) {
            throw ValidationException::withMessages([
                'subdomain' => 'Subdomain sudah digunakan.',
            ]);
        }
    }

    private function uniqueUsername(string $subdomain): string
    {
        $base = Str::limit($subdomain, 24, '');
        $candidate = $base;
        $suffix = 1;

        while (User::query()->where('username', $candidate)->exists()) {
            $candidate = $base . '-' . $suffix++;
        }

        return $candidate;
    }

    private function uniqueReferralCode(): string
    {
        do {
            $code = 'REF-' . Str::upper(Str::random(6));
        } while (User::query()->where('referral_code', $code)->exists());

        return $code;
    }

    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($phone, '0')) {
            return '62' . substr($phone, 1);
        }

        return $phone;
    }
}
