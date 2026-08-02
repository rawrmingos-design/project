<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Pembelian;
use App\Models\Pembayaran;
use App\Services\WhatsappNotificationService;
use App\Services\EmailNotificationService;
use App\Services\ProviderRoutingService;
use App\Services\OrderProcessingService;
use App\Services\Payments\DuitkuInvoiceService;
use App\Services\PublicOrderPushNotificationService;
use App\Support\PembelianStatus;
use App\Jobs\PollSufPaymentStatusJob;
use Duitku\Config;
use Duitku\Pop;

class DuitkuPaymentController extends Controller
{
    protected $api;
    protected $duitkuConfig;

    public function __construct()
    {
        $this->api = null;
        $this->duitkuConfig = null;
    }

    protected function initializeDuitkuConfig(): void
    {
        if ($this->duitkuConfig || $this->api) {
            return;
        }

        $this->api = DB::table('setting_webs')->where('id', 1)->first();

        // Initialize Duitku Config
        if ($this->api && $this->api->duitku_merchant_key && $this->api->duitku_merchant_code) {
            $this->duitkuConfig = new Config(
                $this->api->duitku_merchant_key,
                $this->api->duitku_merchant_code
            );
            
            // Set sandbox mode based on database config
            $isSandbox = $this->api->duitku_mode === 'sandbox';
            $this->duitkuConfig->setSandboxMode($isSandbox);
            $this->duitkuConfig->setSanitizedMode(true);
            $this->duitkuConfig->setDuitkuLogs((bool) config('app.debug'));
        }
    }

    /**
     * Create Duitku payment invoice.
     *
     * Compatibility wrapper for legacy callers; invoice creation lives in DuitkuInvoiceService.
     *
     * @param Pembelian $order
     * @param string|null $paymentMethodCode Duitku payment method code from frontend
     * @return array
     */
    public function createInvoice(Pembelian $order, ?string $paymentMethodCode = null)
    {
        return app(DuitkuInvoiceService::class)->createForPembelian($order, $paymentMethodCode);
    }

    /**
     * Handle Duitku callback
     *
     * @param Request $request
     * @return Response
     */
    public function handleCallback(Request $request)
    {
        $this->initializeDuitkuConfig();

        try {
            if (!$this->duitkuConfig) {
                Log::error('Duitku: Configuration not found');
                return response('Configuration error', 500);
            }

            $payload = $this->extractCallbackPayload($request);

            Log::debug('Duitku Callback Received', [
                'payload_keys' => array_keys($payload),
                'merchantOrderId' => $payload['merchantOrderId'] ?? null,
                'reference' => $payload['reference'] ?? null,
            ]);

            if (empty($payload)) {
                Log::error('Duitku: Invalid callback payload', [
                    'payload_keys' => array_keys($request->all()),
                    'raw_length' => strlen((string) $request->getContent()),
                ]);
                return response('Invalid payload', 400);
            }

            if (!$this->isValidCallbackSignature($payload)) {
                Log::warning('Duitku: Invalid callback signature', [
                    'merchantOrderId' => $payload['merchantOrderId'] ?? null,
                    'reference' => $payload['reference'] ?? null,
                ]);
                return response('Invalid signature', 400);
            }

            // Start database transaction with locking
            DB::beginTransaction();

            try {
                $reference = $payload['reference'] ?? null;
                $merchantOrderId = $payload['merchantOrderId'] ?? null;

                // Find payment record with lock
                // Strategy: Prioritize matching by reference (unique per attempt) to avoid
                // ambiguity when multiple payment records exist for retries of the same order.
                // The merchantOrderId format 'DUITKU-{order_id}' is shared across all retries,
                // so matching by reference first ensures we update the correct payment attempt.
                $payment = null;

                if ($reference) {
                    $payment = Pembayaran::query()
                        ->where('status', 'Belum Lunas')
                        ->where(function ($query) use ($reference) {
                            $query->where('duitku_reference', $reference)
                                ->orWhere('reference', $reference);
                        })
                        ->lockForUpdate()
                        ->first();
                }

                // Fallback to merchantOrderId if reference not found
                if (!$payment && $merchantOrderId) {
                    $payment = Pembayaran::query()
                        ->where('status', 'Belum Lunas')
                        ->where('duitku_merchant_order_id', $merchantOrderId)
                        ->orderBy('id', 'desc')  // Get latest if multiple retries exist
                        ->lockForUpdate()
                        ->first();
                }

                if (!$payment) {
                    Log::debug('Duitku: Payment not found or already processed', [
                        'reference' => $reference,
                        'merchantOrderId' => $merchantOrderId,
                    ]);
                    DB::rollBack();
                    return response('SUCCESS', 200);
                }

                // Get order (Pembelian OR Deposit)
                $order = Pembelian::where('order_id', $payment->order_id)->first();
                $isDeposit = false;

                if (!$order) {
                    // Try to find in Deposit
                    $order = \App\Models\Deposit::where('order_id', $payment->order_id)->first();
                    $isDeposit = true;
                }

                if (!$order) {
                    Log::error('Duitku: Order/Deposit not found', ['order_id' => $payment->order_id]);
                    DB::rollBack();
                    return response('Order not found', 404);
                }

                // Verify amount
                if ((int) ($payload['amount'] ?? 0) !== (int) $payment->harga) {
                    Log::error('Duitku: Amount mismatch', [
                        'expected' => $payment->harga,
                        'received' => $payload['amount'] ?? null,
                    ]);
                    DB::rollBack();
                    return response('Invalid amount', 400);
                }

                // Handle payment status
                if (($payload['resultCode'] ?? null) == "00") {
                    // SUCCESS
                    $payment->update([
                        'status' => 'Lunas',
                        'paid_at' => now(),
                        'reference' => $reference ?? $payment->reference,
                        'duitku_reference' => $reference ?? $payment->duitku_reference,
                        'duitku_merchant_order_id' => $merchantOrderId ?? $payment->duitku_merchant_order_id,
                    ]);

                    // Initialize Services
                    $waService = new WhatsappNotificationService();
                    $emailService = new EmailNotificationService();
                    $routingService = new ProviderRoutingService();
                    $orderProcessor = new OrderProcessingService($routingService);

                    if ($isDeposit) {
                        // === HANDLE DEPOSIT ===
                        $order->update(['status' => 'Success']);
                        
                        // Add Balance to User
                        $user = \App\Models\User::where('username', $order->username)->first();
                        if ($user) {
                            $user->update(['balance' => $user->balance + $order->jumlah]);
                        }

                        // Notify Admin
                        $pesanAdmin = "*Deposit Berhasil via Duitku*\n\n" .
                            "No Invoice: *{$payment->order_id}*\n" .
                            "Username : {$order->username}\n" .
                            "Metode Pembayaran : Duitku\n" .
                            "Jumlah : Rp. " . number_format($payment->harga, 0, '.', ',') . "\n\n" .
                            "*Kontak Pembeli*\n" .
                            "No HP : {$payment->no_pembeli}\n";
                        
                        $waService->sendMessage($this->api->nomor_admin, $pesanAdmin);

                        // Notify Buyer (WhatsApp)
                        $this->runSafely('duitku_deposit_buyer_notification', function () use ($waService, $payment, $order) {
                            $waService->sendNotification($payment->no_pembeli, 'deposit_success', [
                                'username' => $order->username,
                                'order_id' => $payment->order_id,
                                'amount' => 'Rp ' . number_format($payment->harga, 0, ',', '.'),
                                'status' => 'Berhasil',
                            ]);
                        }, ['order_id' => $payment->order_id]);

                    } else {
                        // === HANDLE PEMBELIAN (GAME TOPUP) ===
                        $this->sendPaymentSuccessPushNotification($order);

                        // Notify Admin
                        $pesanAdmin = "*Pembayaran Berhasil via Duitku*\n\n" .
                            "No Invoice: *{$payment->order_id}*\n" .
                            "Layanan : {$order->layanan}\n" .
                            "ID : {$order->user_id}\n" .
                            "Server : {$order->zone}\n" .
                            "Nickname : {$order->nickname}\n" .
                            "Metode Pembayaran : Duitku\n" .
                            "Harga : Rp. " . number_format($payment->harga, 0, '.', ',') . "\n\n" .
                            "*Kontak Pembeli*\n" .
                            "No HP : {$payment->no_pembeli}\n";

                        $this->runSafely('duitku_payment_admin_notification', function () use ($waService, $pesanAdmin) {
                            $waService->sendMessage($this->api->nomor_admin, $pesanAdmin);
                        }, ['order_id' => $payment->order_id]);

                        $result = [
                            'success' => false,
                            'message' => 'Provider processing was skipped',
                        ];

                        try {
                            $result = $orderProcessor->process($order);
                        } catch (\Throwable $e) {
                            Log::error("Duitku: Provider processing exception for {$payment->order_id}", [
                                'error' => $e->getMessage(),
                            ]);
                        }

                        $transactionId = $result['transaction_id'] ?? null;
                        $orderSuccess = (bool) ($result['success'] ?? false);

                        $normalizedStatus = PembelianStatus::normalize($result['order_status'] ?? PembelianStatus::UNKNOWN);
                        $providerStatus = PembelianStatus::preferredDatabaseLabel($normalizedStatus);
                        $snValue = trim((string) ($result['sn'] ?? '')) ?: ($order->keterangan_sn ?: 'Sedang Diproses');

                        if (in_array($normalizedStatus, [PembelianStatus::FAILED, PembelianStatus::CANCELLED], true)) {
                            $orderData = [
                                'status' => $providerStatus,
                                'keterangan_sn' => $snValue,
                            ];
                            if ($transactionId) {
                                $orderData['provider_order_id'] = $transactionId;
                            }
                            $order->update($orderData);
                            app(\App\Services\PointService::class)->refundRedeemedPoints($order);

                            $this->runSafely('duitku_transaction_failed_provider_whatsapp', function () use ($waService, $payment, $order, $result) {
                                $waService->sendNotification($payment->no_pembeli, 'transaction_failed', [
                                    'nickname' => $order->nickname ?? 'Pelanggan',
                                    'order_id' => $payment->order_id,
                                    'product' => $order->layanan,
                                    'reason' => trim((string) ($result['message'] ?? '')) ?: 'Transaksi gagal dari provider.',
                                ]);
                            }, ['order_id' => $payment->order_id]);

                            $recipientEmail = $order->email_pembeli ?? ($order->user->email ?? null);
                            if ($recipientEmail) {
                                $this->runSafely('duitku_transaction_failed_provider_email', function () use ($emailService, $recipientEmail, $payment, $order, $providerStatus, $snValue, $result) {
                                    $emailService->sendTransactionEmail($recipientEmail, [
                                        'order_id' => $payment->order_id,
                                        'product' => $order->layanan,
                                        'amount' => 'Rp ' . number_format($order->harga, 0, ',', '.'),
                                        'status' => PembelianStatus::apiStatusCode($providerStatus),
                                        'nickname' => $order->nickname,
                                        'sn' => $snValue,
                                        'note' => trim((string) ($result['message'] ?? '')) ?: 'Transaksi gagal dari provider.',
                                    ]);
                                }, ['order_id' => $payment->order_id]);
                            }
                        } elseif ($orderSuccess) {
                            $orderData = ['status' => $providerStatus];
                            if ($transactionId) {
                                $orderData['provider_order_id'] = $transactionId;
                                $orderData['active_attempt_token'] = $transactionId;
                            }
                            $orderData['keterangan_sn'] = $snValue;
                            $order->update($orderData);

                            $freshOrder = $order->fresh(['pembayaran']);
                            if ($freshOrder) {
                                PollSufPaymentStatusJob::dispatchIfNeeded($freshOrder, $transactionId, $providerStatus);
                            }

                            $notificationSlug = PembelianStatus::normalize($providerStatus) === PembelianStatus::SUCCESS
                                ? 'transaction_success'
                                : 'transaction_pending';

                            if (PembelianStatus::normalize($providerStatus) === PembelianStatus::SUCCESS) {
                                $this->sendOrderSuccessPushNotification($order);
                            }

                            $this->runSafely('duitku_transaction_success_whatsapp', function () use ($waService, $payment, $order, $snValue, $providerStatus, $notificationSlug) {
                                $waService->sendNotification($payment->no_pembeli, $notificationSlug, [
                                    'nickname' => $order->nickname,
                                    'order_id' => $payment->order_id,
                                    'product' => $order->layanan,
                                    'amount' => 'Rp ' . number_format($order->harga, 0, ',', '.'),
                                    'sn' => $snValue,
                                    'status' => PembelianStatus::label($providerStatus),
                                ]);
                            }, ['order_id' => $payment->order_id]);

                            $recipientEmail = $order->email_pembeli ?? ($order->user->email ?? null);
                            if ($recipientEmail) {
                                $this->runSafely('duitku_transaction_success_email', function () use ($emailService, $recipientEmail, $payment, $order, $providerStatus, $snValue) {
                                    $emailService->sendTransactionEmail($recipientEmail, [
                                        'order_id' => $payment->order_id,
                                        'product' => $order->layanan,
                                        'amount' => 'Rp ' . number_format($order->harga, 0, ',', '.'),
                                        'status' => PembelianStatus::apiStatusCode($providerStatus),
                                        'nickname' => $order->nickname,
                                        'sn' => $snValue,
                                        'note' => PembelianStatus::normalize($providerStatus) === PembelianStatus::SUCCESS
                                            ? 'Harap Simpan Invoice ini, akan digunakan untuk verifikasi transaksi.'
                                            : 'Pesanan sedang menunggu respon provider. Invoice ini akan digunakan untuk verifikasi transaksi.',
                                    ]);
                                }, ['order_id' => $payment->order_id]);
                            }
                        } else {
                            $order->update(['status' => 'Pending']);
                            Log::warning("Duitku: Order processing failed for {$payment->order_id}: " . ($result['message'] ?? 'Unknown error'));

                            $this->runSafely('duitku_transaction_pending_whatsapp', function () use ($waService, $payment, $order) {
                                $waService->sendNotification($payment->no_pembeli, 'transaction_pending', [
                                    'nickname' => $order->nickname,
                                    'order_id' => $payment->order_id,
                                    'product' => $order->layanan,
                                    'amount' => 'Rp ' . number_format($order->harga, 0, ',', '.'),
                                    'status' => 'Menunggu Provider',
                                ]);
                            }, ['order_id' => $payment->order_id]);
                        }
                    }

                    DB::commit();
                    return response('SUCCESS', 200);

                } else if (($payload['resultCode'] ?? null) == "01") {
                    // FAILED
                    $payment->update(['status' => 'Batal']);
                    $order->update(['status' => 'Gagal']);
                    app(\App\Services\PointService::class)->refundRedeemedPoints($order);

                    // Notify Buyer (Failed)
                    $waService = new WhatsappNotificationService();
                    $this->runSafely('duitku_transaction_failed_whatsapp', function () use ($waService, $payment, $order) {
                        $waService->sendNotification($payment->no_pembeli, 'transaction_failed', [
                            'nickname' => $order->nickname ?? 'Pelanggan',
                            'order_id' => $payment->order_id,
                            'product' => $order->layanan,
                            'reason' => 'Pembayaran Gagal/Kadaluarsa',
                        ]);
                    }, ['order_id' => $payment->order_id]);

                    DB::commit();
                    return response('SUCCESS', 200);
                }

                Log::warning('Duitku: unknown callback resultCode', [
                    'resultCode' => $payload['resultCode'] ?? null,
                    'reference' => $payload['reference'] ?? null,
                    'merchantOrderId' => $payload['merchantOrderId'] ?? null,
                ]);
                DB::rollBack();
                return response('SUCCESS', 200);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Duitku callback error: ' . $e->getMessage(), [
                'merchantOrderId' => $request->input('merchantOrderId'),
                'reference' => $request->input('reference'),
            ]);
            return response('Error', 500);
        }
    }

    private function extractCallbackPayload(Request $request): array
    {
        $payload = $request->all();

        if (!empty($payload)) {
            return $payload;
        }

        $jsonPayload = json_decode($request->getContent(), true);

        return is_array($jsonPayload) ? $jsonPayload : [];
    }

    private function isValidCallbackSignature(array $payload): bool
    {
        $signature = (string) ($payload['signature'] ?? '');
        $merchantCode = (string) ($payload['merchantCode'] ?? '');
        $amount = (string) ($payload['amount'] ?? '');
        $merchantOrderId = (string) ($payload['merchantOrderId'] ?? '');

        if ($signature === '' || $merchantCode === '' || $amount === '' || $merchantOrderId === '') {
            return false;
        }

        $expectedSignature = md5(
            $merchantCode .
            $amount .
            $merchantOrderId .
            $this->duitkuConfig->getApiKey()
        );

        return hash_equals($expectedSignature, $signature);
    }

    private function sendPaymentSuccessPushNotification(Pembelian $pembelian): void
    {
        try {
            app(PublicOrderPushNotificationService::class)
                ->notifyPaymentSuccess($pembelian->loadMissing('user'));
        } catch (\Throwable $exception) {
            Log::warning('Duitku payment success push notification failed', [
                'order_id' => $pembelian->order_id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function sendOrderSuccessPushNotification(Pembelian $pembelian): void
    {
        try {
            app(PublicOrderPushNotificationService::class)
                ->notifyOrderSuccess($pembelian->loadMissing('user'));
        } catch (\Throwable $exception) {
            Log::warning('Duitku order success push notification failed', [
                'order_id' => $pembelian->order_id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function runSafely(string $context, callable $callback, array $extra = []): void
    {
        try {
            $callback();
        } catch (\Throwable $e) {
            Log::warning("Duitku: {$context} failed", array_merge($extra, [
                'error' => $e->getMessage(),
            ]));
        }
    }

    /**
     * Check transaction status
     * 
     * @param string $merchantOrderId
     * @return array
     */
    public function checkStatus($merchantOrderId)
    {
        $this->initializeDuitkuConfig();

        try {
            if (!$this->duitkuConfig) {
                throw new \Exception('Duitku configuration not found');
            }

            $statusResponse = Pop::transactionStatus($merchantOrderId, $this->duitkuConfig);
            $status = json_decode($statusResponse, true);

            return [
                'success' => true,
                'status' => $status
            ];

        } catch (\Exception $e) {
            Log::error('Duitku checkStatus failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get available payment methods
     * 
     * @param int $amount
     * @return array
     */
    public function getPaymentMethods($amount)
    {
        $this->initializeDuitkuConfig();

        try {
            if (!$this->duitkuConfig) {
                throw new \Exception('Duitku configuration not found');
            }

            $methodsResponse = Pop::getPaymentMethod((string)$amount, $this->duitkuConfig);
            $methods = json_decode($methodsResponse, true);

            return [
                'success' => true,
                'methods' => $methods['paymentFee'] ?? []
            ];

        } catch (\Exception $e) {
            Log::error('Duitku getPaymentMethods failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
