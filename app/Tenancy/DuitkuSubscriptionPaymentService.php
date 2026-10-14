<?php

namespace App\Tenancy;

use App\Models\SubscriptionInvoice;
use App\Services\Payments\DuitkuPopClient;
use Duitku\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DuitkuSubscriptionPaymentService
{
    private const EXPIRY_PERIOD_MINUTES = 1440;

    public function __construct(private readonly DuitkuPopClient $client)
    {
    }

    public function createInvoice(SubscriptionInvoice $invoice): array
    {
        $settings = $this->settings();
        $config = $this->config($settings);
        $invoice->loadMissing('subscription.tenant.owner');

        $subscription = $invoice->subscription;
        $tenant = $subscription->tenant;
        $owner = $tenant->owner;
        $merchantOrderId = (string) $invoice->gateway_ref;
        $amount = (int) $invoice->amount;
        $phone = $this->normalizePhone((string) ($owner?->no_wa ?? '08123456789'));
        $ownerName = trim((string) ($owner?->name ?? $tenant->name));
        $ownerEmail = trim((string) ($owner?->email ?? 'reseller@example.com'));
        $productDetails = 'Langganan White-label ' . ucfirst((string) $subscription->tier);
        $expiresAt = now()->addMinutes(self::EXPIRY_PERIOD_MINUTES);

        $params = [
            'paymentAmount' => $amount,
            'merchantOrderId' => $merchantOrderId,
            'productDetails' => $productDetails,
            'email' => $ownerEmail,
            'phoneNumber' => $phone,
            'customerVaName' => $ownerName !== '' ? $ownerName : $tenant->name,
            'paymentMethod' => '',
            'callbackUrl' => route('duitku.subscription.callback'),
            // B2: pembeli yang baru bayar dikembalikan ke halaman SUKSES, bukan ke
            // formulir pendaftaran yang sudah dia isi.
            //
            // Token diterbitkan DI SINI, sebelum `returnUrl` dibangun: token
            // adalah satu-satunya kunci halaman sukses, dan penerbitannya malas —
            // invoice yang lunas tanpa token (mis. dibayar sebelum kolomnya ada)
            // akan 404 tepat saat pembeli kembali. `ensurePublicToken()` idempoten
            // dan tidak pernah menimpa token yang sudah terkirim lewat email/WA.
            'returnUrl' => $this->successUrl($invoice),
            'expiryPeriod' => self::EXPIRY_PERIOD_MINUTES,
            'customerDetail' => [
                'firstName' => $ownerName !== '' ? explode(' ', $ownerName)[0] : $tenant->name,
                'lastName' => explode(' ', $ownerName)[1] ?? '',
                'email' => $ownerEmail,
                'phoneNumber' => $phone,
            ],
            'itemDetails' => [
                [
                    'name' => $productDetails,
                    'price' => $amount,
                    'quantity' => 1,
                ],
            ],
        ];

        $payload = $this->client->createInvoice($params, $config);

        if ((string) ($payload['statusCode'] ?? '') !== '00') {
            throw new RuntimeException('Duitku Error: ' . ($payload['statusMessage'] ?? 'Unknown'));
        }

        return [
            'merchant_order_id' => $merchantOrderId,
            'reference' => $payload['reference'] ?? null,
            'payment_url' => $payload['paymentUrl'] ?? null,
            'va_number' => $payload['vaNumber'] ?? null,
            'qr_string' => $payload['qrString'] ?? null,
            'amount' => (int) ($payload['amount'] ?? $amount),
            'status_code' => (string) ($payload['statusCode'] ?? '00'),
            'status_message' => $payload['statusMessage'] ?? null,
            'mode' => (string) ($settings->duitku_mode ?? 'sandbox'),
            'expiry_period_minutes' => self::EXPIRY_PERIOD_MINUTES,
            'expires_at' => $expiresAt->toIso8601String(),
            'created_at' => now()->toIso8601String(),
        ];
    }

    public function createAndStoreInvoice(SubscriptionInvoice $invoice, array $metadataMerge = []): SubscriptionInvoice
    {
        $duitku = $this->createInvoice($invoice);
        $metadata = array_replace_recursive($invoice->metadata ?: [], $metadataMerge, [
            'duitku' => $duitku,
        ]);

        $invoice->forceFill([
            'status' => SubscriptionInvoice::STATUS_PENDING,
            'due_date' => isset($duitku['expires_at']) ? \Illuminate\Support\Carbon::parse($duitku['expires_at']) : $invoice->due_date,
            'metadata' => $metadata,
        ])->save();

        return $invoice->fresh('subscription.tenant.owner');
    }

    /**
     * URL halaman sukses (S4) di host PLATFORM.
     *
     * Service ini dipanggil dari 5 jalur — pendaftaran (host platform), admin
     * Filament (host admin), perpanjangan lewat queue/CLI (host `app.url`), dan
     * halaman tagihan. `route()` polos akan menempelkan host request yang sedang
     * berjalan, dan halaman sukses hanya hidup di host platform
     * (`RequirePlatformHost` mem-404-kan host lain) — tautan yang dikirim ke
     * Duitku karena itu bisa mati di jalur queue/admin. Host ditempel eksplisit.
     *
     * Token diterbitkan di sini kalau belum ada — idempoten di level DB, jadi
     * token yang sudah terkirim lewat email/WA tidak pernah berputar (D13.1).
     */
    protected function successUrl(SubscriptionInvoice $invoice): string
    {
        $path = route(
            'billing.public.success',
            ['token' => $invoice->ensurePublicToken()],
            absolute: false
        );

        $appUrl = rtrim((string) config('app.url'), '/');

        if ($appUrl === '') {
            return url($path);
        }

        return $appUrl . '/' . ltrim($path, '/');
    }

    public function extractCallbackPayload(Request $request): array
    {
        $payload = $request->all();

        if (! empty($payload)) {
            return $payload;
        }

        $jsonPayload = json_decode($request->getContent(), true);

        return is_array($jsonPayload) ? $jsonPayload : [];
    }

    public function isValidCallbackSignature(array $payload): bool
    {
        $signature = (string) ($payload['signature'] ?? '');
        $merchantCode = (string) ($payload['merchantCode'] ?? '');
        $amount = (string) ($payload['amount'] ?? '');
        $merchantOrderId = (string) ($payload['merchantOrderId'] ?? '');

        if ($signature === '' || $merchantCode === '' || $amount === '' || $merchantOrderId === '') {
            return false;
        }

        $settings = $this->settings();
        $expectedSignature = md5($merchantCode . $amount . $merchantOrderId . (string) $settings->duitku_merchant_key);

        return hash_equals($expectedSignature, $signature);
    }

    public function merchantCode(): string
    {
        return (string) $this->settings()->duitku_merchant_code;
    }

    public function callbackMetadata(array $payload): array
    {
        return [
            'result_code' => (string) ($payload['resultCode'] ?? ''),
            'reference' => (string) ($payload['reference'] ?? ''),
            'merchant_code' => (string) ($payload['merchantCode'] ?? ''),
            'amount' => (int) ($payload['amount'] ?? 0),
            'received_at' => now()->toIso8601String(),
        ];
    }

    private function settings(): object
    {
        $settings = DB::table('setting_webs')->where('id', 1)->first();

        if (! $settings || blank($settings->duitku_merchant_code ?? null) || blank($settings->duitku_merchant_key ?? null)) {
            throw new RuntimeException('Konfigurasi Duitku belum lengkap.');
        }

        return $settings;
    }

    private function config(object $settings): Config
    {
        $config = new Config($settings->duitku_merchant_key, $settings->duitku_merchant_code);
        $config->setSandboxMode(($settings->duitku_mode ?? 'sandbox') === 'sandbox');
        $config->setSanitizedMode(true);
        $config->setDuitkuLogs((bool) config('app.debug'));

        return $config;
    }

    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($phone, '0')) {
            return '62' . substr($phone, 1);
        }

        return $phone !== '' ? $phone : '628123456789';
    }
}
