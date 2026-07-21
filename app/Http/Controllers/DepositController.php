<?php

namespace App\Http\Controllers;

use App\Models\Berita;
use App\Models\Deposit;
use App\Models\Method;
use App\Models\Pembayaran;
use Duitku\Config;
use Duitku\Pop;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DepositController extends Controller
{
    public function reloadd()
    {
        $showDemoMethods = app()->environment('local');

        return view('template.reload', [
            'data' => Deposit::where('username', Auth::user()->username)->orderBy('created_at', 'desc')->get(),
            'logoheader' => Berita::where('tipe', 'logoheader')->latest()->first(),
            'logofooter' => Berita::where('tipe', 'logofooter')->latest()->first(),
            'pay_method' => Method::availableForDeposit($showDemoMethods),
        ]);
    }

    public function create()
    {
        if (Auth::user()->isAffiliateActive()) {
            return redirect()->route('dashboard')->with('error', 'Akun Affiliate tidak dapat melakukan deposit. Silakan hubungi Admin.');
        }

        $showDemoMethods = app()->environment('local');

        return view('template.deposit', [
            'data' => Deposit::where('username', Auth::user()->username)->orderBy('created_at', 'desc')->get(),
            'logoheader' => Berita::where('tipe', 'logoheader')->latest()->first(),
            'logofooter' => Berita::where('tipe', 'logofooter')->latest()->first(),
            'pay_method' => Method::availableForDeposit($showDemoMethods),
        ]);
    }

    public function store(Request $request)
    {
        if (Auth::user()->isAffiliateActive()) {
            return back()->with('error', 'Akun Affiliate tidak dapat melakukan deposit. Silakan hubungi Admin.');
        }

        $validated = $request->validate([
            'jumlah' => ['required', 'numeric', 'min:10000'],
            'metode' => ['required', 'string', 'max:50'],
            'no_telfon' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s]*$/'],
        ], [
            'jumlah.required' => 'Mohon isi jumlah deposit',
            'jumlah.numeric' => 'Jumlah harus berupa angka',
            'jumlah.min' => 'Minimal deposit Rp 10.000',
            'metode.required' => 'Mohon pilih metode pembayaran',
            'no_telfon.regex' => 'Format nomor WhatsApp tidak valid.',
        ]);

        $user = Auth::user();
        $api = DB::table('setting_webs')->where('id', 1)->first();
        if (! $api) {
            return back()->withInput()->withErrors([
                'msg' => 'Konfigurasi pembayaran belum tersedia. Silakan hubungi admin.',
            ]);
        }

        $paymentMethod = strtoupper(trim((string) $validated['metode']));
        $normalizedPhone = preg_replace('/\D+/', '', (string) ($validated['no_telfon'] ?? ''));
        $netAmount = (int) ceil((float) $validated['jumlah']);

        $submitLockKey = 'deposit-submit:' . sha1(implode('|', [
            (string) $user->id,
            $paymentMethod,
            (string) $netAmount,
            $normalizedPhone,
        ]));

        if (! Cache::add($submitLockKey, true, 30)) {
            return back()->withInput()->withErrors([
                'msg' => 'Permintaan sebelumnya masih diproses. Mohon tunggu sebentar.',
            ]);
        }

        try {
            $method = Method::query()
                ->enabled()
                ->whereRaw('UPPER(code) = ?', [$paymentMethod])
                ->first();

            if (! $method) {
                return back()->withInput()->withErrors(['msg' => 'Metode pembayaran tidak valid']);
            }

            if ($method->isSaldoMethod()) {
                return back()->withInput()->withErrors(['msg' => 'Metode saldo tidak tersedia untuk top up saldo.']);
            }

            if (! app()->environment('local') && $method->isDemoMethod()) {
                return back()->withInput()->withErrors(['msg' => 'Metode demo tidak tersedia di environment ini.']);
            }

            if ($this->methodRequiresPhone($paymentMethod) && mb_strlen($normalizedPhone) < 8) {
                return back()->withInput()->withErrors([
                    'no_telfon' => 'Nomor WhatsApp aktif wajib diisi untuk metode pembayaran ini.',
                ]);
            }

            $duplicatePending = Deposit::query()
                ->where('username', (string) $user->username)
                ->whereRaw('UPPER(metode) = ?', [$paymentMethod])
                ->where('jumlah', $netAmount)
                ->whereIn('status', ['Pending', 'pending'])
                ->where('created_at', '>=', now()->subMinutes(2))
                ->exists();

            if ($duplicatePending) {
                return back()->withInput()->withErrors([
                    'msg' => 'Transaksi deposit serupa sudah dibuat. Silakan cek invoice deposit terbaru kamu.',
                ]);
            }

            $feePercent = (float) ($method->fee_percent ?? 0);
            $fixedFee = (float) ($method->fix_fee ?? 0);
            $feeAmount = (int) ceil($netAmount * ($feePercent / 100)) + (int) ceil($fixedFee);
            $grossAmount = $netAmount + $feeAmount;

            $gateway = $api->deposit_jalur ?? 'duitku';
            $merchantOrderId = $this->generateUniqueDepositOrderId();

            $isReseller = strtolower(trim((string) $user->role)) === 'reseller';
            $returnUrl = $isReseller ? route('reseller.dashboard') : route('riwayat');

            $result = $this->requestGatewayInvoice(
                gateway: (string) $gateway,
                paymentMethod: $paymentMethod,
                merchantOrderId: $merchantOrderId,
                grossAmount: $grossAmount,
                userEmail: (string) ($user->email ?? 'user@example.com'),
                userName: (string) ($user->name ?? $user->username),
                username: (string) $user->username,
                phone: $normalizedPhone ?: '08123456789',
                settings: $api,
                returnUrl: $returnUrl
            );

            if (! ($result['success'] ?? false)) {
                return back()->withInput()->withErrors(['msg' => 'Gagal membuat invoice via ' . ucfirst((string) $gateway)]);
            }

            $expiredAt = $this->resolvePaymentExpiryAt($result, (string) $gateway);

            DB::transaction(function () use (
                $merchantOrderId,
                $user,
                $paymentMethod,
                $result,
                $netAmount,
                $normalizedPhone,
                $gateway,
                $expiredAt
            ): void {
                $deposit = new Deposit();
                $deposit->order_id = $merchantOrderId;
                $deposit->username = (string) $user->username;
                $deposit->metode = $paymentMethod;
                $deposit->no_pembayaran = $result['va_number'] ?? $result['pay_url'] ?? '-';
                $deposit->jumlah = $netAmount;
                $deposit->status = 'Pending';
                $deposit->save();

                $pembayaran = new Pembayaran();
                $pembayaran->order_id = $merchantOrderId;
                $pembayaran->harga = $result['amount'];
                $pembayaran->no_pembayaran = $deposit->no_pembayaran;
                $pembayaran->no_pembeli = $normalizedPhone ?: '-';
                $pembayaran->status = 'Belum Lunas';
                $pembayaran->metode = $paymentMethod;
                $pembayaran->reference = $result['gateway_ref'];
                $pembayaran->expired_at = $expiredAt;

                if ((string) $gateway === 'duitku') {
                    $pembayaran->duitku_reference = $result['gateway_ref'];
                    $pembayaran->duitku_merchant_order_id = $merchantOrderId;
                }

                $pembayaran->save();
            });

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'order_id' => $merchantOrderId,
                    'amount' => $netAmount,
                    'fee' => $feeAmount,
                    'gross_amount' => $grossAmount,
                    'pay_url' => $result['pay_url'] ?? null,
                    'va_number' => $result['va_number'] ?? null,
                    'expired_at' => $expiredAt,
                    'message' => 'Silakan lakukan pembayaran'
                ]);
            }

            return redirect()->route('deposit.invoice', $merchantOrderId)->with('success', 'Silakan lakukan pembayaran');
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withInput()->withErrors([
                'msg' => 'Terjadi kesalahan: ' . $exception->getMessage(),
            ]);
        } finally {
            Cache::forget($submitLockKey);
        }
    }

    private function requestGatewayInvoice(
        string $gateway,
        string $paymentMethod,
        string $merchantOrderId,
        int $grossAmount,
        string $userEmail,
        string $userName,
        string $username,
        string $phone,
        object $settings,
        string $returnUrl = ''
    ): array {
        return match (strtolower($gateway)) {
            'duitku' => $this->requestDuitkuInvoice(
                paymentMethod: $paymentMethod,
                merchantOrderId: $merchantOrderId,
                grossAmount: $grossAmount,
                userEmail: $userEmail,
                userName: $userName,
                username: $username,
                phone: $phone,
                settings: $settings,
                returnUrl: $returnUrl
            ),
            'tripay' => $this->requestTripayInvoice(
                paymentMethod: $paymentMethod,
                merchantOrderId: $merchantOrderId,
                grossAmount: $grossAmount,
                userEmail: $userEmail,
                phone: $phone,
                returnUrl: $returnUrl
            ),
            'tokopay' => $this->requestTokopayInvoice(
                paymentMethod: $paymentMethod,
                merchantOrderId: $merchantOrderId,
                grossAmount: $grossAmount,
                username: $username,
                phone: $phone,
                returnUrl: $returnUrl
            ),
            default => throw new \RuntimeException('Gateway Deposit tidak valid'),
        };
    }

    private function requestDuitkuInvoice(
        string $paymentMethod,
        string $merchantOrderId,
        int $grossAmount,
        string $userEmail,
        string $userName,
        string $username,
        string $phone,
        object $settings,
        string $returnUrl
    ): array {
        $duitkuConfig = new Config($settings->duitku_merchant_key, $settings->duitku_merchant_code);
        $duitkuConfig->setSandboxMode($settings->duitku_mode === 'sandbox');
        $duitkuConfig->setSanitizedMode(true);
        $duitkuConfig->setDuitkuLogs(true);

        $params = [
            'paymentAmount' => $grossAmount,
            'merchantOrderId' => $merchantOrderId,
            'productDetails' => 'Deposit Saldo',
            'email' => $userEmail,
            'phoneNumber' => $phone,
            'customerVaName' => $userName,
            'paymentMethod' => $this->mapPaymentMethod($paymentMethod),
            'callbackUrl' => route('duitku.callback'),
            'returnUrl' => $returnUrl ?: route('riwayat'),
            'expiryPeriod' => 180,
            'customerDetail' => [
                'firstName' => $username,
                'lastName' => '',
                'email' => $userEmail,
                'phoneNumber' => $phone,
            ],
            'itemDetails' => [
                [
                    'name' => 'Deposit Saldo',
                    'price' => $grossAmount,
                    'quantity' => 1,
                ],
            ],
        ];

        $response = Pop::createInvoice($params, $duitkuConfig);
        $payload = json_decode($response, true);

        if (! isset($payload['statusCode']) || (string) $payload['statusCode'] !== '00') {
            throw new \RuntimeException('Duitku Error: ' . ($payload['statusMessage'] ?? 'Unknown'));
        }

        return [
            'success' => true,
            'reference' => $payload['reference'],
            'pay_url' => $payload['paymentUrl'] ?? null,
            'va_number' => $payload['vaNumber'] ?? $payload['qrString'] ?? null,
            'amount' => $grossAmount,
            'gateway_ref' => $payload['reference'],
            'expired_at' => now()->addMinutes(60)->toIso8601String(),
        ];
    }

    private function requestTripayInvoice(
        string $paymentMethod,
        string $merchantOrderId,
        int $grossAmount,
        string $userEmail,
        string $phone,
        string $returnUrl
    ): array {
        $tripay = new TriPayController();
        $tripayMethod = $paymentMethod === 'QRIS' ? 'QRIS' : $paymentMethod;

        $payload = $tripay->request(
            $merchantOrderId,
            $grossAmount,
            $tripayMethod,
            $userEmail,
            $phone,
            $returnUrl
        );

        if (! ($payload['success'] ?? false)) {
            throw new \RuntimeException('Tripay Error: ' . ($payload['msg'] ?? 'Unknown'));
        }

        $payUrl = null;
        $vaOrQr = $payload['no_pembayaran'] ?? null;
        if (filled($vaOrQr) && filter_var((string) $vaOrQr, FILTER_VALIDATE_URL)) {
            $payUrl = $vaOrQr;
        }

        return [
            'success' => true,
            'reference' => $payload['reference'] ?? null,
            'pay_url' => $payUrl,
            'va_number' => $vaOrQr,
            'amount' => (int) ($payload['amount'] ?? $grossAmount),
            'gateway_ref' => $payload['reference'] ?? null,
            'expired_at' => $payload['expired_at'] ?? null,
        ];
    }

    private function requestTokopayInvoice(
        string $paymentMethod,
        string $merchantOrderId,
        int $grossAmount,
        string $username,
        string $phone,
        string $returnUrl
    ): array {
        $tokopay = new TokoPayController();
        $tokopayMethod = $paymentMethod === 'QRIS' ? 'QRIS' : $paymentMethod;

        $payload = $tokopay->createAdvanceOrder(
            $merchantOrderId,
            $tokopayMethod,
            $grossAmount,
            $username,
            $phone,
            'Deposit Saldo',
            $returnUrl
        );

        if (! (($payload['status'] ?? false) === true)) {
            throw new \RuntimeException('Tokopay Error: ' . ($payload['error_msg'] ?? 'Unknown'));
        }

        $data = $payload['data'] ?? [];

        // Extract payment details based on Tokopay API response structure
        // Ref: https://docs.tokopay.id/order/create-order
        $payUrl = $data['pay_url'] ?? null;
        $checkoutUrl = $data['checkout_url'] ?? null;
        $qrLink = $data['qr_link'] ?? null;
        $qrString = $data['qr_string'] ?? null;

        // Determine the appropriate value for va_number/no_pembayaran field
        // Priority: QR code (qr_link) > checkout URL > QR string > payment URL
        $paymentValue = $qrLink ?? $checkoutUrl ?? $qrString ?? $payUrl;

        return [
            'success' => true,
            'reference' => $data['trx_id'] ?? $merchantOrderId,
            'pay_url' => $payUrl,
            'va_number' => $paymentValue,
            'amount' => (int) ($data['amount'] ?? $grossAmount),
            'gateway_ref' => $data['trx_id'] ?? null,
            'expired_at' => $data['expired_at'] ?? $data['expired_ts'] ?? null,
        ];
    }

    private function methodRequiresPhone(string $methodCode): bool
    {
        return in_array($methodCode, [
            'OVO',
            'DANA',
            'SHOPEEPAY',
            'LINKAJA',
            'GOPAY',
            'OVOPUSH',
            'ASTRAPAY',
            'VIRGO',
        ], true);
    }

    private function generateUniqueDepositOrderId(): string
    {
        do {
            $candidate = 'DP' . now()->format('His') . substr(str_shuffle('0123456789'), 0, 8);
        } while (
            Deposit::query()->where('order_id', $candidate)->exists()
            || Pembayaran::query()->where('order_id', $candidate)->exists()
        );

        return $candidate;
    }

    private function mapPaymentMethod(string $code): string
    {
        $normalized = strtoupper(trim($code));

        return match ($normalized) {
            'OVO' => 'OV',
            'DANA' => 'DA',
            'SHOPEEPAY' => 'SA',
            'LINKAJA' => 'LF',
            'QRIS' => 'SQ',
            'BNC' => 'NC',
            default => $normalized,
        };
    }

    private function resolvePaymentExpiryAt(array $result, string $gateway): ?Carbon
    {
        $candidates = [
            $result['expired_at'] ?? null,
            $result['expires_at'] ?? null,
            $result['expired_time'] ?? null,
            $result['expired_ts'] ?? null,
        ];

        foreach ($candidates as $candidate) {
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

        return match (strtolower($gateway)) {
            'duitku' => now()->addHours(3),
            'tripay' => now()->addHours(24),
            'tokopay' => now()->addHours(3),
            default => now()->addHours(3),
        };
    }
}
