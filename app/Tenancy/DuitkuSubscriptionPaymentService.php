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
            'returnUrl' => route('tenant.register.page'),
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
