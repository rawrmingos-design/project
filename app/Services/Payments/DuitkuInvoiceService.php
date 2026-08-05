<?php

namespace App\Services\Payments;

use App\Models\Pembelian;
use App\Support\DuitkuPaymentChannels;
use Duitku\Config;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class DuitkuInvoiceService
{
    private const EXPIRY_PERIOD_MINUTES = 180;

    public function __construct(private readonly DuitkuPopClient $client)
    {
    }

    public function createForPembelian(Pembelian $order, ?string $paymentMethodCode = null): array
    {
        try {
            $settings = \App\Services\Payments\DuitkuConfiguration::settings();
            $config = \App\Services\Payments\DuitkuConfiguration::load();
            $requestedPaymentMethodCode = strtoupper(trim((string) $paymentMethodCode));
            $duitkuPaymentMethodCode = AppSupportDuitkuPaymentChannels::normalize($requestedPaymentMethodCode);
            $merchantOrderId = 'DUITKU-' . $order->order_id;
            $amount = (int) $order->harga;
            $customerName = trim((string) ($order->nickname ?: $order->username ?: 'Customer'));
            $customerName = $customerName !== '' ? $customerName : 'Customer';
            [$firstName, $lastName] = $this->splitCustomerName($customerName);
            $email = trim((string) ($order->email_pembeli ?: 'customer@example.com'));
            $phoneNumber = $this->phoneForOrder($order);
            $expiryMinutes = self::EXPIRY_PERIOD_MINUTES;

            $params = [
                'paymentAmount' => $amount,
                'merchantOrderId' => $merchantOrderId,
                'productDetails' => (string) $order->layanan,
                'email' => $email,
                'phoneNumber' => $phoneNumber,
                'customerVaName' => $customerName,
                'paymentMethod' => $duitkuPaymentMethodCode,
                'callbackUrl' => $this->callbackUrl($settings),
                'returnUrl' => $this->returnUrl($settings, $order),
                'expiryPeriod' => $expiryMinutes,
                'customerDetail' => [
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'email' => $email,
                    'phoneNumber' => $phoneNumber,
                ],
                'itemDetails' => [
                    [
                        'name' => (string) $order->layanan,
                        'price' => $amount,
                        'quantity' => 1,
                    ],
                ],
            ];

            $payload = AppSupportDuitkuPaymentChannels::isDirect($duitkuPaymentMethodCode)
                ? $this->client->createDirectInvoice($params, $config)
                : $this->client->createInvoice($params, $config);

            Log::debug('Duitku API Response', [
                'statusCode' => $payload['statusCode'] ?? null,
                'statusMessage' => $payload['statusMessage'] ?? null,
                'reference' => $payload['reference'] ?? null,
                'merchantOrderId' => $merchantOrderId,
                'requestedPaymentMethod' => $requestedPaymentMethodCode,
                'duitkuPaymentMethod' => $duitkuPaymentMethodCode,
                'directMode' => AppSupportDuitkuPaymentChannels::isDirect($duitkuPaymentMethodCode),
            ]);

            return $this->normalizeResponse(
                $payload,
                $order,
                $merchantOrderId,
                $requestedPaymentMethodCode,
                $duitkuPaymentMethodCode,
                $expiryMinutes
            );
        } catch (Throwable $exception) {
            Log::error('Duitku create invoice failed', [
                'order_id' => $order->order_id ?? 'unknown',
                'error' => $exception->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $exception->getMessage(),
            ];
        }
    }



    private function normalizeResponse(
        array $payload,
        Pembelian $order,
        string $merchantOrderId,
        string $requestedPaymentMethodCode,
        string $duitkuPaymentMethodCode,
        int $expiryMinutes
    ): array {
        if ((string) ($payload['statusCode'] ?? '') !== '00') {
            throw new RuntimeException($payload['statusMessage'] ?? 'Failed to create invoice');
        }

        $qrString = $payload['qrString'] ?? $payload['qr_string'] ?? null;
        $vaNumber = $payload['vaNumber']
            ?? $payload['va_number']
            ?? $payload['paymentCode']
            ?? $payload['payment_code']
            ?? null;
        $paymentUrl = $payload['paymentUrl'] ?? $payload['payment_url'] ?? null;
        $reference = $payload['reference'] ?? null;
        $paymentValue = $vaNumber ?? $qrString ?? $paymentUrl ?? $reference;
        $amount = (int) ($payload['amount'] ?? $order->harga);
        $expiredAt = Carbon::now()->addMinutes($expiryMinutes)->toIso8601String();

        Log::debug('Duitku invoice created successfully', [
            'order_id' => $order->order_id,
            'reference' => $reference,
            'requestedPaymentMethod' => $requestedPaymentMethodCode,
            'duitkuPaymentMethod' => $duitkuPaymentMethodCode,
        ]);

        return [
            'success' => true,
            'reference' => $reference,
            'gateway_ref' => $reference,
            'paymentUrl' => $paymentUrl,
            'payment_url' => $paymentUrl,
            'vaNumber' => $vaNumber,
            'va_number' => $vaNumber,
            'qrString' => $qrString,
            'qr_string' => $qrString,
            'payment_value' => $paymentValue,
            'no_pembayaran' => $paymentValue,
            'amount' => $amount,
            'merchantOrderId' => $merchantOrderId,
            'merchant_order_id' => $merchantOrderId,
            'requestedPaymentMethod' => $requestedPaymentMethodCode,
            'requested_payment_method' => $requestedPaymentMethodCode,
            'duitkuPaymentMethod' => $duitkuPaymentMethodCode,
            'duitku_payment_method' => $duitkuPaymentMethodCode,
            'expired_at' => $expiredAt,
            'expires_at' => $expiredAt,
        ];
    }



        return $settings;
    }



    private function callbackUrl(object $settings): string
    {
        $callbackUrl = trim((string) ($settings->duitku_callback_url ?? ''));

        return $callbackUrl !== '' ? $callbackUrl : route('duitku.callback');
    }

    private function returnUrl(object $settings, Pembelian $order): string
    {
        $returnUrl = trim((string) ($settings->duitku_return_url ?? ''));

        if ($returnUrl !== '') {
            return $returnUrl;
        }

        return rtrim((string) config('app.url'), '/') . '/id/invoices/' . $order->order_id;
    }

    private function phoneForOrder(Pembelian $order): string
    {
        $user = null;

        try {
            $user = $order->relationLoaded('user') ? $order->user : $order->user()->first();
        } catch (Throwable) {
            $user = null;
        }

        $phone = (string) ($user?->no_wa ?? '081234567890');
        $phone = trim($phone);

        return $phone !== '' ? $phone : '081234567890';
    }

    private function splitCustomerName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), 2) ?: [];

        return [
            $parts[0] ?? 'Customer',
            $parts[1] ?? '',
        ];
    }
}


