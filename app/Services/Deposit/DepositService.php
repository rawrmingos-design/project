<?php

namespace App\Services\Deposit;

use App\Http\Controllers\TokoPayController;
use App\Http\Controllers\TriPayController;
use App\Models\Deposit;
use App\Models\Method;
use App\Models\Pembayaran;
use App\Models\User;
use Duitku\Config;
use Duitku\Pop;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DepositService
{
    private const MINIMUM_AMOUNT = 10000;

    public function __construct(
        private readonly ?TriPayController $triPayController = null,
        private readonly ?TokoPayController $tokoPayController = null,
    ) {
    }

    /**
     * @param array{jumlah: int|float|string, metode: string, no_telfon?: ?string, source?: string, external_user_id?: ?string, external_message_id?: ?string, metadata?: array<string, mixed>, return_url?: string} $input
     * @return array<string, mixed>
     */
    public function create(User $user, array $input): array
    {
        $netAmount = (int) ceil((float) ($input['jumlah'] ?? 0));
        $paymentMethod = strtoupper(trim((string) ($input['metode'] ?? '')));
        $source = strtolower(trim((string) ($input['source'] ?? 'web'))) ?: 'web';
        $externalUserId = filled($input['external_user_id'] ?? null) ? (string) $input['external_user_id'] : null;
        $externalMessageId = filled($input['external_message_id'] ?? null) ? (string) $input['external_message_id'] : null;
        $normalizedPhone = preg_replace('/\D+/', '', (string) ($input['no_telfon'] ?? '')) ?? '';

        if ($netAmount < self::MINIMUM_AMOUNT) {
            return $this->failure('Minimal deposit Rp 10.000', 'jumlah');
        }

        if ($paymentMethod === '') {
            return $this->failure('Metode pembayaran tidak valid', 'metode');
        }

        if (in_array($source, ['whatsapp_gateway', 'telegram_gateway'], true)
            && ($externalUserId === null || $externalMessageId === null)) {
            return $this->failure('Identitas pesan gateway tidak lengkap.', 'idempotency');
        }

        $api = DB::table('setting_webs')->where('id', 1)->first();
        if (! $api) {
            return $this->failure('Konfigurasi pembayaran belum tersedia. Silakan hubungi admin.');
        }

        $method = Method::query()
            ->enabled()
            ->whereRaw('UPPER(code) = ?', [$paymentMethod])
            ->first();

        if (! $method) {
            return $this->failure('Metode pembayaran tidak valid', 'metode');
        }

        if ($method->isSaldoMethod()) {
            return $this->failure('Metode saldo tidak tersedia untuk top up saldo.');
        }

        if (! app()->environment('local') && $method->isDemoMethod()) {
            return $this->failure('Metode demo tidak tersedia di environment ini.');
        }

        if ($this->methodRequiresPhone($paymentMethod) && mb_strlen($normalizedPhone) < 8) {
            return $this->failure('Nomor WhatsApp aktif wajib diisi untuk metode pembayaran ini.', 'no_telfon');
        }

        $idempotencyKey = $this->idempotencyKey($user, $source, $externalUserId, $externalMessageId);
        if ($idempotencyKey !== null) {
            $existing = Deposit::query()->where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                if ((int) $existing->jumlah !== $netAmount || strtoupper((string) $existing->metode) !== $paymentMethod) {
                    return $this->failure('Idempotency key sudah digunakan untuk transaksi lain.', 'idempotency');
                }

                return $this->replayResult($existing);
            }
        }

        $lockParts = [
            (string) $user->id,
            $paymentMethod,
            (string) $netAmount,
            $normalizedPhone,
        ];

        if ($idempotencyKey !== null) {
            $lockParts[] = $idempotencyKey;
        }

        $submitLockKey = 'deposit-submit:' . sha1(implode('|', $lockParts));

        if (! Cache::add($submitLockKey, true, 30)) {
            return $this->failure('Permintaan sebelumnya masih diproses. Mohon tunggu sebentar.');
        }

        try {
            $duplicatePending = Deposit::query()
                ->where('username', (string) $user->username)
                ->whereRaw('UPPER(metode) = ?', [$paymentMethod])
                ->where('jumlah', $netAmount)
                ->whereIn('status', ['Pending', 'pending'])
                ->where('created_at', '>=', now()->subMinutes(2))
                ->exists();

            if ($duplicatePending && $idempotencyKey === null) {
                return $this->failure('Transaksi deposit serupa sudah dibuat. Silakan cek invoice deposit terbaru kamu.');
            }

            $feePercent = (float) ($method->fee_percent ?? 0);
            $fixedFee = (float) ($method->fix_fee ?? 0);
            $feeAmount = (int) ceil($netAmount * ($feePercent / 100)) + (int) ceil($fixedFee);
            $grossAmount = $netAmount + $feeAmount;
            $gateway = strtolower(trim((string) ($api->deposit_jalur ?? 'duitku')));
            $merchantOrderId = $this->generateUniqueDepositOrderId();
            $isReseller = strtolower(trim((string) $user->role)) === 'reseller';
            $returnUrl = (string) ($input['return_url'] ?? ($isReseller ? route('reseller.dashboard') : route('riwayat')));

            try {
                $result = $this->requestGatewayInvoice(
                    gateway: $gateway,
                    paymentMethod: $paymentMethod,
                    merchantOrderId: $merchantOrderId,
                    grossAmount: $grossAmount,
                    userEmail: (string) ($user->email ?? 'user@example.com'),
                    userName: (string) ($user->name ?? $user->username),
                    username: (string) $user->username,
                    phone: $normalizedPhone ?: '08123456789',
                    settings: $api,
                    returnUrl: $returnUrl,
                );
            } catch (\Throwable) {
                return $this->failure('Gagal membuat invoice via ' . ucfirst($gateway));
            }

            if (! ($result['success'] ?? false)) {
                return $this->failure('Gagal membuat invoice via ' . ucfirst($gateway));
            }

            $expiredAt = $this->resolvePaymentExpiryAt($result, $gateway);
            $metadata = array_merge([
                'source' => $source,
                'external_user_id' => $externalUserId,
                'external_message_id' => $externalMessageId,
                'gateway' => $gateway,
                'payment_code' => $result['payment_code'] ?? null,
                'qr_link' => $result['qr_link'] ?? null,
                'qr_payload' => $result['qr_payload'] ?? null,
                'checkout_url' => $result['checkout_url'] ?? null,
                'pay_url' => $result['pay_url'] ?? null,
            ], is_array($input['metadata'] ?? null) ? $input['metadata'] : []);

            $deposit = DB::transaction(function () use ($user, $paymentMethod, $result, $netAmount, $normalizedPhone, $gateway, $expiredAt, $merchantOrderId, $idempotencyKey, $metadata): Deposit {
                if ($idempotencyKey !== null) {
                    $existing = Deposit::query()->where('idempotency_key', $idempotencyKey)->lockForUpdate()->first();
                    if ($existing) {
                        return $existing;
                    }
                }

                $deposit = new Deposit();
                $deposit->tenant_id = $user->tenant_id;
                $deposit->order_id = $merchantOrderId;
                $deposit->username = (string) $user->username;
                $deposit->metode = $paymentMethod;
                $deposit->no_pembayaran = $result['payment_code'] ?? $result['qr_link'] ?? $result['checkout_url'] ?? $result['pay_url'] ?? '-';
                $deposit->jumlah = $netAmount;
                $deposit->status = 'Pending';
                $deposit->source = $metadata['source'] ?? 'web';
                $deposit->external_user_id = $metadata['external_user_id'] ?? null;
                $deposit->external_message_id = $metadata['external_message_id'] ?? null;
                $deposit->idempotency_key = $idempotencyKey;
                $deposit->payment_metadata = $metadata;
                $deposit->save();

                $pembayaran = new Pembayaran();
                $pembayaran->tenant_id = $user->tenant_id;
                $pembayaran->order_id = $merchantOrderId;
                $pembayaran->harga = $result['amount'];
                $pembayaran->no_pembayaran = $deposit->no_pembayaran;
                $pembayaran->no_pembeli = $normalizedPhone ?: '-';
                $pembayaran->status = 'Belum Lunas';
                $pembayaran->metode = $paymentMethod;
                $pembayaran->reference = $result['gateway_ref'];
                $pembayaran->expired_at = $expiredAt;

                if ($gateway === 'duitku') {
                    $pembayaran->duitku_reference = $result['gateway_ref'];
                    $pembayaran->duitku_merchant_order_id = $merchantOrderId;
                }

                $pembayaran->save();

                return $deposit;
            });

            return [
                'success' => true,
                'order_id' => $deposit->order_id,
                'amount' => $netAmount,
                'fee' => $feeAmount,
                'gross_amount' => $grossAmount,
                'pay_url' => $result['pay_url'] ?? null,
                'checkout_url' => $result['checkout_url'] ?? null,
                'va_number' => $result['payment_code'] ?? null,
                'payment_code' => $result['payment_code'] ?? null,
                'qr_link' => $result['qr_link'] ?? null,
                'qr_payload' => $result['qr_payload'] ?? null,
                'expired_at' => $expiredAt,
                'deposit' => $deposit,
            ];
        } finally {
            Cache::forget($submitLockKey);
        }
    }

    private function failure(string $message, ?string $field = null): array
    {
        return array_filter([
            'success' => false,
            'message' => $message,
            'field' => $field,
        ], static fn (mixed $value): bool => $value !== null);
    }

    private function replayResult(Deposit $deposit): array
    {
        return [
            'success' => true,
            'idempotent_replay' => true,
            'order_id' => $deposit->order_id,
            'amount' => (int) $deposit->jumlah,
            'fee' => null,
            'gross_amount' => null,
            'pay_url' => data_get($deposit->payment_metadata, 'pay_url'),
            'checkout_url' => data_get($deposit->payment_metadata, 'checkout_url'),
            'va_number' => data_get($deposit->payment_metadata, 'payment_code'),
            'payment_code' => data_get($deposit->payment_metadata, 'payment_code'),
            'qr_link' => data_get($deposit->payment_metadata, 'qr_link'),
            'qr_payload' => data_get($deposit->payment_metadata, 'qr_payload'),
            'expired_at' => $deposit->pembayaran?->expired_at,
            'deposit' => $deposit,
        ];
    }

    private function idempotencyKey(User $user, string $source, ?string $externalUserId, ?string $externalMessageId): ?string
    {
        if ($externalUserId === null || $externalMessageId === null) {
            return null;
        }

        return hash('sha256', implode('|', [
            (string) ($user->tenant_id ?? 'global'),
            $source,
            $externalUserId,
            $externalMessageId,
        ]));
    }

    private function requestGatewayInvoice(string $gateway, string $paymentMethod, string $merchantOrderId, int $grossAmount, string $userEmail, string $userName, string $username, string $phone, object $settings, string $returnUrl): array
    {
        return match ($gateway) {
            'duitku' => $this->requestDuitkuInvoice($paymentMethod, $merchantOrderId, $grossAmount, $userEmail, $userName, $username, $phone, $settings, $returnUrl),
            'tripay' => $this->requestTripayInvoice($paymentMethod, $merchantOrderId, $grossAmount, $userEmail, $phone, $returnUrl),
            'tokopay' => $this->requestTokopayInvoice($paymentMethod, $merchantOrderId, $grossAmount, $username, $phone, $returnUrl),
            default => throw new RuntimeException('Gateway Deposit tidak valid'),
        };
    }

    private function requestDuitkuInvoice(string $paymentMethod, string $merchantOrderId, int $grossAmount, string $userEmail, string $userName, string $username, string $phone, object $settings, string $returnUrl): array
    {
        $config = new Config($settings->duitku_merchant_key, $settings->duitku_merchant_code);
        $config->setSandboxMode($settings->duitku_mode === 'sandbox');
        $config->setSanitizedMode(true);
        $config->setDuitkuLogs(true);
        $payload = [
            'paymentAmount' => $grossAmount, 'merchantOrderId' => $merchantOrderId, 'productDetails' => 'Deposit Saldo',
            'email' => $userEmail, 'phoneNumber' => $phone, 'customerVaName' => $userName,
            'paymentMethod' => $this->mapPaymentMethod($paymentMethod), 'callbackUrl' => route('duitku.callback'),
            'returnUrl' => $returnUrl ?: route('riwayat'), 'expiryPeriod' => 180,
            'customerDetail' => ['firstName' => $username, 'lastName' => '', 'email' => $userEmail, 'phoneNumber' => $phone],
            'itemDetails' => [['name' => 'Deposit Saldo', 'price' => $grossAmount, 'quantity' => 1]],
        ];
        $decoded = json_decode(Pop::createInvoice($payload, $config), true);
        if (! isset($decoded['statusCode']) || (string) $decoded['statusCode'] !== '00') {
            throw new RuntimeException('Duitku Error: ' . ($decoded['statusMessage'] ?? 'Unknown'));
        }

        return ['success' => true, 'pay_url' => $decoded['paymentUrl'] ?? null, 'payment_code' => $decoded['vaNumber'] ?? $decoded['qrString'] ?? null, 'qr_payload' => $decoded['qrString'] ?? null, 'amount' => $grossAmount, 'gateway_ref' => $decoded['reference'], 'expired_at' => now()->addMinutes(60)->toIso8601String()];
    }

    private function requestTripayInvoice(string $paymentMethod, string $merchantOrderId, int $grossAmount, string $userEmail, string $phone, string $returnUrl): array
    {
        $payload = ($this->triPayController ?? new TriPayController())->request($merchantOrderId, $grossAmount, $paymentMethod === 'QRIS' ? 'QRIS' : $paymentMethod, $userEmail, $phone, $returnUrl);
        if (! ($payload['success'] ?? false)) {
            throw new RuntimeException('Tripay Error: ' . ($payload['msg'] ?? 'Unknown'));
        }

        $paymentValue = $payload['payment_code'] ?? null;
        return ['success' => true, 'pay_url' => $payload['pay_url'] ?? null, 'checkout_url' => $payload['pay_url'] ?? null, 'payment_code' => $paymentValue, 'qr_link' => $payload['qr_url'] ?? null, 'qr_payload' => $payload['qr_payload'] ?? null, 'amount' => (int) ($payload['amount'] ?? $grossAmount), 'gateway_ref' => $payload['reference'] ?? null, 'expired_at' => $payload['expired_at'] ?? null];
    }

    private function requestTokopayInvoice(string $paymentMethod, string $merchantOrderId, int $grossAmount, string $username, string $phone, string $returnUrl): array
    {
        $payload = ($this->tokoPayController ?? new TokoPayController())->createAdvanceOrder($merchantOrderId, $paymentMethod === 'QRIS' ? 'QRIS' : $paymentMethod, $grossAmount, $username, $phone, 'Deposit Saldo', $returnUrl);
        if (($payload['status'] ?? false) !== true) {
            throw new RuntimeException('Tokopay Error: ' . ($payload['error_msg'] ?? 'Unknown'));
        }

        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
        return ['success' => true, 'pay_url' => $data['pay_url'] ?? null, 'checkout_url' => $data['checkout_url'] ?? null, 'payment_code' => $data['nomor_va'] ?? $data['pay_code'] ?? $data['payment_code'] ?? $data['kode_bayar'] ?? null, 'qr_link' => $data['qr_link'] ?? null, 'qr_payload' => $data['qr_string'] ?? null, 'amount' => (int) ($data['amount'] ?? $grossAmount), 'gateway_ref' => $data['trx_id'] ?? $merchantOrderId, 'expired_at' => $data['expired_at'] ?? $data['expired_ts'] ?? null];
    }

    private function methodRequiresPhone(string $methodCode): bool
    {
        return in_array($methodCode, ['OVO', 'DANA', 'SHOPEEPAY', 'LINKAJA', 'GOPAY', 'OVOPUSH', 'ASTRAPAY', 'VIRGO'], true);
    }

    private function generateUniqueDepositOrderId(): string
    {
        do {
            $candidate = 'DP' . now()->format('His') . substr(str_shuffle('0123456789'), 0, 8);
        } while (Deposit::query()->where('order_id', $candidate)->exists() || Pembayaran::query()->where('order_id', $candidate)->exists());

        return $candidate;
    }

    private function mapPaymentMethod(string $code): string
    {
        return match (strtoupper(trim($code))) {
            'OVO' => 'OV', 'DANA' => 'DA', 'SHOPEEPAY' => 'SA', 'LINKAJA' => 'LF', 'QRIS' => 'SQ', 'BNC' => 'NC', default => strtoupper(trim($code)),
        };
    }

    private function resolvePaymentExpiryAt(array $result, string $gateway): ?Carbon
    {
        foreach ([$result['expired_at'] ?? null, $result['expires_at'] ?? null, $result['expired_time'] ?? null, $result['expired_ts'] ?? null] as $candidate) {
            if (blank($candidate)) {
                continue;
            }
            if (is_numeric($candidate)) {
                $timestamp = (int) $candidate;
                if ($timestamp > 9_999_999_999) {
                    $timestamp = (int) floor($timestamp / 1000);
                }
                return Carbon::createFromTimestamp($timestamp, config('app.timezone'));
            }
            try {
                return Carbon::parse($candidate, config('app.timezone'));
            } catch (\Throwable) {
                continue;
            }
        }

        return match ($gateway) {
            'duitku', 'tokopay' => now()->addHours(3),
            'tripay' => now()->addHours(24),
            default => now()->addHours(3),
        };
    }
}
