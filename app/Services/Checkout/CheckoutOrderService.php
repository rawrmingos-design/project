<?php

namespace App\Services\Checkout;

use App\Http\Controllers\TokoPayController;
use App\Http\Controllers\TriPayController;
use App\Services\Payments\DuitkuInvoiceService;
use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Method;
use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Models\SettingWeb;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutOrderService
{
    private const IDEMPOTENCY_TTL_SECONDS = 600;

    public function createFromApi(Request $request, ?User $user = null): array
    {
        $this->validateApiPayload($request);

        $idempotencyKey = $this->idempotencyKey($request, $user);
        if ($cachedOrderId = Cache::get($idempotencyKey)) {
            $order = Pembelian::query()->where('order_id', $cachedOrderId)->with('pembayaran')->first();
            if ($order) {
                return $this->buildResult($order, true);
            }
        }

        $order = DB::transaction(function () use ($request, $user): Pembelian {
            $service = Layanan::query()
                ->whereKey($request->integer('service'))
                ->where('status', 'available')
                ->lockForUpdate()
                ->first();

            if (! $service) {
                throw ValidationException::withMessages([
                    'service' => 'Layanan tidak ditemukan atau tidak tersedia.',
                ]);
            }

            $category = Kategori::query()->findOrFail($service->kategori_id);
            $method = Method::query()
                ->enabled()
                ->where('code', (string) $request->input('payment_method'))
                ->first();

            if (! $method) {
                throw ValidationException::withMessages([
                    'payment_method' => 'Metode pembayaran tidak valid atau tidak aktif.',
                ]);
            }

            if ($this->isSaldoMethod($method) && ! $user) {
                throw ValidationException::withMessages([
                    'payment_method' => 'Saldo hanya bisa digunakan setelah login.',
                ]);
            }

            $baseAmount = $this->resolveServiceAmount($service, $user, $request);
            $voucherCode = trim((string) $request->input('voucher', ''));
            if ($voucherCode !== '') {
                $voucher = Voucher::query()->where('kode', $voucherCode)->lockForUpdate()->first();
                if (! $voucher || $voucher->stock <= 0) {
                    throw ValidationException::withMessages([
                        'voucher' => 'Voucher tidak valid atau sudah habis.',
                    ]);
                }

                if ($voucher->mintrx && $baseAmount < $voucher->mintrx) {
                    throw ValidationException::withMessages([
                        'voucher' => 'Minimal transaksi untuk voucher ini adalah Rp ' . number_format((int) $voucher->mintrx, 0, ',', '.'),
                    ]);
                }

                $discount = min((int) round($baseAmount * ((float) $voucher->promo / 100)), (int) $voucher->max_potongan);
                $baseAmount = max(0, $baseAmount - $discount);
                $voucher->decrement('stock');
            }

            $feeAmount = $this->methodFee($baseAmount, $method);
            $totalAmount = max(1000, $baseAmount + $feeAmount);
            $limitMessage = $this->validateMethodLimit($totalAmount, $method);
            if ($limitMessage !== null) {
                throw ValidationException::withMessages([
                    'payment_method' => $limitMessage,
                ]);
            }

            $orderId = $this->generateOrderId();
            $paymentReference = 'API-' . $orderId;
            $expiresAt = now()->addHours($this->paymentExpiryHours($method));
            $paymentCode = $this->paymentCode($method, $orderId);
            $duitkuMerchantOrderId = null;
            $orderType = $this->orderType((string) $request->input('ktg_tipe', $category->tipe));
            $isJoki = in_array($orderType, ['joki', 'jokigendong', 'vilogml'], true);

            $gateway = $this->requestGatewayInvoice(
                $method,
                $orderId,
                $totalAmount,
                $service,
                $request,
                $user,
                $isJoki
            );

            if ($gateway !== null) {
                $totalAmount = (int) ($gateway['amount'] ?? $totalAmount);
                $paymentReference = (string) ($gateway['reference'] ?? $paymentReference);
                $paymentCode = (string) ($gateway['no_pembayaran'] ?? $paymentCode);
                $expiresAt = $this->parseGatewayExpiry($gateway['expired_at'] ?? null, $method);
                $duitkuMerchantOrderId = $gateway['merchant_order_id'] ?? null;
            }

            $order = new Pembelian();
            $order->username = $user?->username ?? 'Anonim';
            $order->order_id = $orderId;
            $order->user_id = $isJoki ? '-' : (string) $request->input('uid');
            $order->zone = $isJoki ? '-' : (string) $request->input('zone', '');
            $order->nickname = $isJoki
                ? ($orderType !== 'joki' ? (string) $request->input('nickname_joki') : '-')
                : (string) ($request->input('nickname') ?: 'Anonim');
            $order->layanan = $service->layanan;
            $order->harga = $totalAmount;
            $order->profit = $this->resolveProfitAmount($service, $user, $feeAmount);
            $order->provider_order_id = '';
            $order->status = 'Pending';
            $order->log = json_encode([
                'source' => 'api_v2_checkout',
                'payment' => (string) $method->payment,
                'base_amount' => $baseAmount,
                'fee_amount' => $feeAmount,
            ], JSON_UNESCAPED_SLASHES);
            $order->traffic_source = 'api_v2';
            $order->voucher = $voucherCode !== '' ? $voucherCode : null;
            $order->tipe_transaksi = $orderType;
            $order->email_pembeli = $user?->email ?: ($request->input('email') ?: null);
            $order->ip_address = $request->ip();
            $order->active_layanan_id = $service->id;
            $order->active_provider_code = strtolower((string) $service->provider);
            $order->active_provider_sku = (string) $service->provider_id;
            $order->environment = 'live';
            $order->is_sandbox = false;
            $order->save();

            $payment = new Pembayaran();
            $payment->order_id = $orderId;
            $payment->harga = $totalAmount;
            $payment->no_pembayaran = $paymentCode;
            $payment->no_pembeli = (string) $request->input('nomor');
            $payment->status = 'Belum Lunas';
            $payment->metode = (string) $method->code;
            $payment->reference = $paymentReference;
            $payment->expired_at = $expiresAt;

            if ((string) $method->payment === 'duitku') {
                $payment->duitku_reference = $paymentReference;
                $payment->duitku_merchant_order_id = $duitkuMerchantOrderId ?: ('DUITKU-' . $orderId);
            }

            $payment->save();

            if ($isJoki) {
                DB::table('data_joki')->insert([
                    'order_id' => $orderId,
                    'email_joki' => $orderType !== 'jokigendong' ? (string) $request->input('email_joki') : '-',
                    'password_joki' => $orderType !== 'jokigendong' ? (string) $request->input('password_joki') : '-',
                    'loginvia_joki' => (string) $request->input('loginvia_joki'),
                    'nickname_joki' => $orderType !== 'jokigendong' ? (string) $request->input('nickname_joki') : '-',
                    'request_joki' => $orderType !== 'jokigendong' ? (string) $request->input('request_joki') : '-',
                    'catatan_joki' => (string) $request->input('catatan_joki'),
                    'tglmain_joki' => $orderType === 'jokigendong' ? (string) $request->input('tglmain_joki') : '-',
                    'jambooking_joki' => $orderType === 'jokigendong' ? (string) $request->input('jambooking_joki') : '-',
                    'qty' => (int) $request->input('qty', 1),
                    'status_joki' => 'Pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return $order->fresh('pembayaran');
        });

        Cache::put($idempotencyKey, $order->order_id, now()->addSeconds(self::IDEMPOTENCY_TTL_SECONDS));

        return $this->buildResult($order);
    }

    public function statusPayload(Pembelian $order): array
    {
        $payment = $order->pembayaran;

        return [
            'order_id' => $order->order_id,
            'invoice_id' => $order->display_invoice_id,
            'product' => $order->layanan,
            'nickname' => $order->nickname,
            'amount' => (int) $order->harga,
            'status' => $order->status,
            'payment' => [
                'method' => $payment?->metode,
                'status' => $payment?->status,
                'amount' => $payment ? (int) $payment->harga : null,
                'reference' => $payment?->reference,
                'payment_code' => $payment?->no_pembayaran,
                'expires_at' => $payment?->expired_at?->toIso8601String(),
            ],
            'sn' => $order->keterangan_sn,
            'created_at' => $order->created_at?->toIso8601String(),
        ];
    }

    private function validateApiPayload(Request $request): void
    {
        $orderType = $this->orderType((string) $request->input('ktg_tipe', 'game'));
        $isJokiGendong = $orderType === 'jokigendong';
        $isJokiMode = in_array($orderType, ['joki', 'vilogml'], true);

        $rules = [
            'service' => ['required', 'integer'],
            'payment_method' => ['required', 'string', 'max:64'],
            'nomor' => ['required', 'regex:/^[0-9]{9,16}$/'],
            'zone' => ['nullable', 'string', 'max:50'],
            'nickname' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'voucher' => ['nullable', 'string', 'max:64'],
            'ktg_tipe' => ['nullable', 'string', 'max:50'],
            'qty' => ['nullable', 'integer', 'min:1', 'max:999'],
        ];

        if ($isJokiGendong) {
            $rules += [
                'nickname_joki' => ['required', 'string', 'max:255'],
                'tglmain_joki' => ['required', 'string', 'max:255'],
                'jambooking_joki' => ['required', 'string', 'max:255'],
                'loginvia_joki' => ['required', 'string', 'max:255'],
                'catatan_joki' => ['required', 'string', 'max:255'],
            ];
        } elseif ($isJokiMode) {
            $rules += [
                'email_joki' => ['required', 'string', 'max:255'],
                'password_joki' => ['required', 'string', 'max:255'],
                'loginvia_joki' => ['required', 'string', 'max:255'],
                'nickname_joki' => ['required', 'string', 'max:255'],
                'request_joki' => ['required', 'string', 'max:255'],
                'catatan_joki' => ['required', 'string', 'max:255'],
            ];
        } else {
            $rules['uid'] = ['required', 'string', 'max:50'];
        }

        $request->validate($rules);
    }

    private function buildResult(Pembelian $order, bool $idempotent = false): array
    {
        $order->loadMissing('pembayaran');
        $payment = $order->pembayaran;

        return [
            'status' => true,
            'message' => $idempotent ? 'Order sudah diproses sebelumnya.' : 'Order berhasil dibuat.',
            'order_id' => $order->order_id,
            'invoice_url' => route('pembelian', $order->order_id),
            'payment_url' => route('pembelian', $order->order_id),
            'payment' => [
                'method' => $payment?->metode,
                'status' => $payment?->status,
                'amount' => $payment ? (int) $payment->harga : null,
                'reference' => $payment?->reference,
                'payment_code' => $payment?->no_pembayaran,
                'payment_url' => route('pembelian', $order->order_id),
                'expires_at' => $payment?->expired_at?->toIso8601String(),
            ],
        ];
    }

    private function resolveServiceAmount(Layanan $service, ?User $user, Request $request): int
    {
        $amount = match ($user?->role ?? 'Guest') {
            'Member' => $service->harga_member,
            'Platinum' => $service->harga_platinum,
            'Gold', 'Admin' => $service->harga_gold,
            default => $service->harga_member,
        };

        if ($service->is_flash_sale == 1 && $service->expired_flash_sale >= now() && $service->stock_flash_sale > 0) {
            $service->decrement('stock_flash_sale');
            $amount = $service->harga_flash_sale;
        }

        if (in_array((string) $request->input('ktg_tipe'), ['joki', 'jokigendong', 'vilogml'], true)) {
            $amount *= max(1, (int) $request->input('qty', 1));
        }

        return max(0, (int) round((float) $amount));
    }

    private function resolveProfitAmount(Layanan $service, ?User $user, int $feeAmount): int
    {
        $profit = match ($user?->role ?? 'Guest') {
            'Member' => $service->profit_member,
            'Platinum' => $service->profit_platinum,
            'Gold', 'Admin' => $service->profit_gold,
            default => $service->profit_member,
        };

        return max(0, (int) round((float) $profit) - $feeAmount);
    }

    private function methodFee(int $amount, Method $method): int
    {
        return max(0, (int) round(((float) $method->fix_fee) + ($amount * ((float) $method->fee_percent / 100))));
    }

    private function validateMethodLimit(int $amount, Method $method): ?string
    {
        $min = (int) ($method->min_pembelian ?? 0);
        $max = (int) ($method->max_pembelian ?? 0);

        if ($min > 0 && $amount < $min) {
            return 'Minimal pembayaran untuk metode ini adalah Rp ' . number_format($min, 0, ',', '.');
        }

        if ($max > 0 && $amount > $max) {
            return 'Maksimal pembayaran untuk metode ini adalah Rp ' . number_format($max, 0, ',', '.');
        }

        return null;
    }

    private function generateOrderId(): string
    {
        $prefix = SettingWeb::query()->value('order_prefik') ?: 'TRX';

        do {
            $orderId = $prefix . now()->format('ymdHis') . Str::upper(Str::random(6));
        } while (Pembelian::query()->where('order_id', $orderId)->exists());

        return $orderId;
    }

    private function requestGatewayInvoice(
        Method $method,
        string $orderId,
        int $amount,
        Layanan $service,
        Request $request,
        ?User $user,
        bool $isJoki
    ): ?array {
        return match ((string) $method->payment) {
            'duitku' => $this->requestDuitkuInvoice($method, $orderId, $amount, $service, $request, $user, $isJoki),
            'tripay' => $this->requestTripayInvoice($method, $orderId, $amount, $request, $user),
            'tokopay' => $this->requestTokopayInvoice($method, $orderId, $amount, $service, $request),
            default => null,
        };
    }

    private function requestDuitkuInvoice(Method $method, string $orderId, int $amount, Layanan $service, Request $request, ?User $user, bool $isJoki): array
    {
        $tempOrder = new Pembelian();
        $tempOrder->order_id = $orderId;
        $tempOrder->layanan = $service->layanan;
        $tempOrder->user_id = $isJoki ? '-' : (string) $request->input('uid');
        $tempOrder->zone = $isJoki ? '-' : (string) $request->input('zone', '');
        $tempOrder->nickname = (string) ($request->input('nickname') ?: $request->input('nickname_joki') ?: 'Customer');
        $tempOrder->email_pembeli = $user?->email ?: ($request->input('email') ?: 'customer@example.com');
        $tempOrder->username = $user?->username ?? 'guest';
        $tempOrder->harga = $amount;
        $tempOrder->profit = 0;
        $tempOrder->status = 'Pending';

        $result = app(DuitkuInvoiceService::class)->createForPembelian($tempOrder, (string) $method->code);

        if (! ($result['success'] ?? false)) {
            throw ValidationException::withMessages([
                'payment_method' => $result['message'] ?? 'Gagal membuat invoice Duitku.',
            ]);
        }

        return [
            'reference' => $result['reference'] ?? null,
            'no_pembayaran' => $result['payment_value'] ?? $result['no_pembayaran'] ?? $result['vaNumber'] ?? $result['qrString'] ?? $result['paymentUrl'] ?? $result['reference'] ?? null,
            'amount' => $result['amount'] ?? $amount,
            'merchant_order_id' => $result['merchant_order_id'] ?? $result['merchantOrderId'] ?? ('DUITKU-' . $orderId),
            'expired_at' => $result['expired_at'] ?? null,
        ];
    }

    private function requestTripayInvoice(Method $method, string $orderId, int $amount, Request $request, ?User $user): array
    {
        $customerEmail = $user?->email ?: trim((string) $request->input('email'));

        if (! filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'email' => 'Email pembeli wajib diisi dengan format valid untuk metode pembayaran ini.',
            ]);
        }

        $result = app(TriPayController::class)->request(
            $orderId,
            $amount,
            (string) $method->code,
            $customerEmail,
            (string) $request->input('nomor')
        );

        if (! ($result['success'] ?? false)) {
            throw ValidationException::withMessages([
                'payment_method' => $result['msg'] ?? 'Gagal membuat invoice Tripay.',
            ]);
        }

        return [
            'reference' => $result['reference'] ?? null,
            'no_pembayaran' => $result['no_pembayaran'] ?? null,
            'amount' => $result['amount'] ?? $amount,
            'expired_at' => $result['expired_at'] ?? null,
        ];
    }

    private function requestTokopayInvoice(Method $method, string $orderId, int $amount, Layanan $service, Request $request): array
    {
        $result = app(TokoPayController::class)->createAdvanceOrder(
            $orderId,
            (string) $method->code,
            $amount,
            (string) ($request->input('nickname') ?: 'Customer'),
            (string) $request->input('nomor'),
            (string) $service->layanan
        );

        if (($result['status'] ?? null) !== 'Success') {
            throw ValidationException::withMessages([
                'payment_method' => $result['error_msg'] ?? 'Gagal membuat invoice TokoPay.',
            ]);
        }

        $data = $result['data'] ?? [];

        return [
            'reference' => $data['trx_id'] ?? null,
            'no_pembayaran' => $data['nomor_va']
                ?? $data['pay_code']
                ?? $data['payment_code']
                ?? $data['kode_bayar']
                ?? $data['qr_link']
                ?? $data['checkout_url']
                ?? $data['pay_url']
                ?? null,
            'amount' => $data['total_bayar'] ?? $amount,
            'expired_at' => $data['expired_at'] ?? $data['expired_ts'] ?? null,
        ];
    }

    private function parseGatewayExpiry(mixed $value, Method $method): \Illuminate\Support\Carbon
    {
        if (blank($value)) {
            return now()->addHours($this->paymentExpiryHours($method));
        }

        try {
            if (is_numeric($value)) {
                $timestamp = (int) $value;
                if ($timestamp > 9_999_999_999) {
                    $timestamp = (int) floor($timestamp / 1000);
                }

                return \Illuminate\Support\Carbon::createFromTimestamp($timestamp, config('app.timezone'));
            }

            return \Illuminate\Support\Carbon::parse($value, config('app.timezone'));
        } catch (\Throwable) {
            return now()->addHours($this->paymentExpiryHours($method));
        }
    }

    private function paymentCode(Method $method, string $orderId): string
    {
        if ($this->isSaldoMethod($method)) {
            return 'Saldo Akun';
        }

        if ((string) $method->payment === 'manual') {
            return 'MANUAL-' . $orderId;
        }

        return sprintf('%s-%s', Str::upper((string) $method->code), $orderId);
    }

    private function paymentExpiryHours(Method $method): int
    {
        return match ((string) $method->payment) {
            'tripay' => 24,
            'duitku' => 1,
            default => 3,
        };
    }

    private function isSaldoMethod(Method $method): bool
    {
        return Str::upper((string) $method->code) === 'SALDO';
    }

    private function orderType(string $type): string
    {
        return match ($type) {
            'joki' => 'joki',
            'voucher' => 'voucher',
            'vilogml' => 'vilogml',
            'jokigendong' => 'jokigendong',
            default => 'game',
        };
    }

    private function idempotencyKey(Request $request, ?User $user): string
    {
        $tenantId = app(\App\Tenancy\TenantContext::class)->id() ?? 'main';
        $key = trim((string) $request->headers->get('X-Idempotency-Key', ''));

        if ($key === '') {
            $key = hash('sha256', json_encode([
                'tenant_id' => $tenantId,
                'user_id' => $user?->id,
                'ip' => $request->ip(),
                'payload' => $request->only(['service', 'payment_method', 'nomor', 'uid', 'zone', 'voucher']),
            ], JSON_UNESCAPED_SLASHES));
        }

        return "api_v2_checkout:{$tenantId}:" . sha1($key);
    }
}
