<?php

namespace App\Tenancy;

use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\Tenant;
use App\Models\User;
use App\Support\WhatsappNumberNormalizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
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

    public function __construct(
        private readonly SubdomainAvailabilityGuard $guard,
    ) {}

    public function register(array $data): array
    {
        $subdomain = $this->normalizeSubdomain((string) ($data['subdomain'] ?? ''));
        $tier = strtolower(trim((string) ($data['tier'] ?? 'starter')));
        $email = trim((string) ($data['email'] ?? ''));

        if (! in_array($tier, self::SELF_SERVICE_TIERS, true)) {
            throw ValidationException::withMessages([
                'tier' => 'Paket tidak tersedia untuk pendaftaran mandiri.',
            ]);
        }

        // Jalur resume: email yang sudah terdaftar TIDAK boleh membuat tenant
        // kedua. Ini juga penjaga keamanan — tanpa cek password, siapa pun
        // yang tahu alamat email bisa mengambil alih pendaftaran orang lain.
        $existing = User::query()->where('email', $email)->first();

        if ($existing !== null) {
            return $this->resume($existing, $data, $subdomain, $tier);
        }

        $this->assertSubdomainAvailable($subdomain);

        return $this->createTenant($data, $subdomain, $tier);
    }

    /**
     * Lanjutkan pendaftaran yang belum dibayar.
     *
     * Q2=B (resume untuk pemilik email sama), Q8=A (pakai invoice lama kalau
     * masih pending), Q9=B (subdomain boleh diganti, username ikut berganti).
     */
    private function resume(User $owner, array $data, string $subdomain, string $tier): array
    {
        if (! Hash::check((string) ($data['password'] ?? ''), (string) $owner->password)) {
            throw ValidationException::withMessages([
                'password' => 'Email ini sudah terdaftar. Masukkan password yang benar untuk melanjutkan.',
            ]);
        }

        $tenant = $owner->tenant_id !== null
            ? Tenant::query()->find($owner->tenant_id)
            : Tenant::query()->where('owner_user_id', $owner->id)->first();

        if ($tenant === null) {
            throw ValidationException::withMessages([
                'email' => 'Email ini sudah terdaftar namun belum punya toko yang bisa dilanjutkan.',
            ]);
        }

        if ($tenant->status !== Tenant::STATUS_PENDING_PAYMENT) {
            throw ValidationException::withMessages([
                'email' => 'Email ini sudah punya toko dengan status ' . $tenant->status . '. Masuk ke dashboard atau hubungi admin.',
            ]);
        }

        return DB::transaction(function () use ($owner, $tenant, $data, $subdomain, $tier): array {
            // Ganti subdomain (Q9=B) hanya kalau berbeda DAN masih tersedia.
            if ($subdomain !== '' && $subdomain !== $tenant->subdomain) {
                $this->assertSubdomainAvailable($subdomain, ignoreTenantId: $tenant->id);

                $tenant->forceFill(['subdomain' => $subdomain])->save();
                $owner->forceFill(['username' => $this->uniqueUsername($subdomain)])->save();
            }

            $subscription = $tenant->subscriptions()->latest('id')->first();

            if ($subscription === null) {
                // Data lama tanpa langganan: buat sekali, jangan biarkan
                // pendaftaran menggantung tanpa invoice.
                $subscription = Subscription::query()->create([
                    'tenant_id' => $tenant->id,
                    'tier' => $tier,
                    'price' => self::TIER_PRICES[$tier],
                    'status' => Subscription::STATUS_PENDING,
                    'gateway_ref' => SubscriptionInvoice::freshGatewayRef(),
                ]);
            }

            $invoice = $this->resumeInvoice($subscription, $tier, $subdomain, $tenant->name);

            return [
                'owner' => $owner->fresh(),
                'tenant' => $tenant->fresh(),
                'subscription' => $subscription->fresh(),
                'invoice' => $invoice->fresh('subscription.tenant.owner'),
                'resumed' => true,
            ];
        });
    }

    /**
     * Q8=A: pakai invoice lama.
     *
     * Tiap invoice punya gateway_ref SENDIRI (kolomnya unik) yang dipakai
     * Duitku sebagai merchantOrderId. Karena itu, resume mengembalikan invoice
     * pending yang sudah ada — bukan membuat invoice baru dengan ref baru,
     * supaya tautan pembayaran yang mungkin sudah dibuka user tetap sah.
     */
    private function resumeInvoice(Subscription $subscription, string $tier, string $subdomain, string $storeName): SubscriptionInvoice
    {
        if (($pending = $this->pendingInvoice($subscription)) !== null) {
            return $pending;
        }

        $invoice = SubscriptionInvoice::query()
            ->where('subscription_id', $subscription->id)
            ->latest('id')
            ->first();

        if ($invoice === null) {
            return $this->createInvoice($subscription, $tier, $subdomain, $storeName);
        }

        // Invoice yang sudah dibayar tidak boleh direset.
        if ($invoice->status === SubscriptionInvoice::STATUS_PAID) {
            return $invoice;
        }

        $invoice->forceFill([
            'status' => SubscriptionInvoice::STATUS_PENDING,
            'paid_at' => null,
            'amount' => self::TIER_PRICES[$tier],
            'due_date' => now()->addDay(),
            'metadata' => array_replace_recursive((array) $invoice->metadata, [
                'store_name' => $storeName,
                'subdomain' => $subdomain,
                'resumed_at' => now()->toIso8601String(),
            ]),
        ])->save();

        // Tautan pembayaran lama sudah kedaluwarsa — segarkan, tapi kegagalan
        // gateway tidak boleh membatalkan resume (invoice tetap pending).
        if ($invoice->gateway === 'duitku') {
            try {
                $invoice = app(DuitkuSubscriptionPaymentService::class)->createAndStoreInvoice($invoice);
            } catch (\Throwable $e) {
                Log::warning('Resume tenant: gagal menyegarkan tautan pembayaran Duitku.', [
                    'invoice_id' => $invoice->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $invoice;
    }

    private function createTenant(array $data, string $subdomain, string $tier): array
    {
        return DB::transaction(function () use ($data, $subdomain, $tier): array {
            $owner = User::query()->create([
                'name' => trim((string) $data['name']),
                'username' => $this->uniqueUsername($subdomain),
                'password' => Hash::make((string) $data['password']),
                'email' => trim((string) $data['email']),
                'no_wa' => WhatsappNumberNormalizer::normalize((string) ($data['no_wa'] ?? '')),
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

            $owner->forceFill(['tenant_id' => $tenant->id])->save();

            $subscription = Subscription::query()->create([
                'tenant_id' => $tenant->id,
                'tier' => $tier,
                'price' => self::TIER_PRICES[$tier],
                'status' => Subscription::STATUS_PENDING,
                'gateway_ref' => SubscriptionInvoice::freshGatewayRef(),
            ]);

            $invoice = $this->createInvoice($subscription, $tier, $subdomain, $tenant->name);

            $this->notifyRegistrationInvoice($invoice);

            return [
                'owner' => $owner,
                'tenant' => $tenant,
                'subscription' => $subscription,
                'invoice' => $invoice->fresh('subscription.tenant.owner'),
                'resumed' => false,
            ];
        });
    }

    private function createInvoice(
        Subscription $subscription,
        string $tier,
        string $subdomain,
        string $storeName,
    ): SubscriptionInvoice {
        $amount = self::TIER_PRICES[$tier];
        $gateway = $amount > 0 ? 'duitku' : 'manual';

        // gateway_ref = merchantOrderId Duitku. WAJIB unik PER INVOICE, bukan
        // per langganan: kolom ini UNIQUE, dan billing berulang butuh banyak
        // invoice untuk satu langganan. Kalau ref diwarisi dari langganan,
        // invoice periode kedua tidak akan pernah bisa dibuat.
        $gatewayRef = SubscriptionInvoice::freshGatewayRef();

        $invoice = SubscriptionInvoice::query()->create([
            'subscription_id' => $subscription->id,
            'amount' => $amount,
            'status' => SubscriptionInvoice::STATUS_PENDING,
            'gateway' => $gateway,
            'gateway_ref' => $gatewayRef,
            'due_date' => now()->addDay(),
            'metadata' => [
                'source' => 'tenant_self_registration',
                'store_name' => $storeName,
                'subdomain' => $subdomain,
                'currency' => 'IDR',
            ],
        ]);

        if ($gateway === 'duitku') {
            $invoice = app(DuitkuSubscriptionPaymentService::class)->createAndStoreInvoice($invoice);
        }

        return $invoice;
    }

    private function pendingInvoice(Subscription $subscription): ?SubscriptionInvoice
    {
        return SubscriptionInvoice::query()
            ->where('subscription_id', $subscription->id)
            ->where('status', SubscriptionInvoice::STATUS_PENDING)
            ->where(function ($query): void {
                $query->whereNull('due_date')->orWhere('due_date', '>', now());
            })
            ->latest('id')
            ->first();
    }

    private function notifyRegistrationInvoice(SubscriptionInvoice $invoice): void
    {
        DB::afterCommit(function () use ($invoice): void {
            try {
                \App\Jobs\SendTenantNotificationJob::dispatch(
                    $invoice->id,
                    \App\Jobs\SendTenantNotificationJob::EVENT_REGISTRATION_INVOICE
                );
            } catch (\Throwable $e) {
                // Notifikasi tidak boleh menggagalkan pendaftaran.
                Log::warning('Gagal menjadwalkan notifikasi invoice pendaftaran tenant.', [
                    'invoice_id' => $invoice->id,
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }

    /** Penolakan subdomain memakai guard yang sama dengan endpoint cek. */
    private function assertSubdomainAvailable(string $subdomain, ?int $ignoreTenantId = null): void
    {
        $hasil = $this->guard->check($subdomain);

        if ($hasil['available']) {
            return;
        }

        throw ValidationException::withMessages([
            'subdomain' => $hasil['reason'] ?: 'Subdomain ini tidak dapat digunakan.',
        ]);
    }

    /**
     * @return array{available: bool, subdomain: string, reason: ?string}
     */
    public function checkSubdomain(string $subdomain): array
    {
        return $this->guard->check($subdomain);
    }

    public function isSubdomainAvailable(string $subdomain): bool
    {
        return (bool) $this->guard->check($subdomain)['available'];
    }

    public function normalizeSubdomain(string $subdomain): string
    {
        return $this->guard->normalize($subdomain);
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
}
