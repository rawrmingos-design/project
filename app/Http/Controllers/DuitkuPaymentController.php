<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Pembelian;
use App\Models\Pembayaran;
use App\Services\WhatsappNotificationService;
use App\Services\EmailNotificationService;
use App\Services\ProviderRoutingService;
use App\Services\OrderProcessingService;
use Duitku\Config;
use Duitku\Pop;

class DuitkuPaymentController extends Controller
{
    protected $api;
    protected $duitkuConfig;

    public function __construct()
    {
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
            $this->duitkuConfig->setDuitkuLogs(true);
        }
    }

    /**
     * Create Duitku payment invoice using Direct Mode
     * 
     * @param Pembelian $order
     * @param string $paymentMethodCode Duitku payment method code from frontend
     * @return array
     */
    public function createInvoice(Pembelian $order, string $paymentMethodCode = null)
    {
        try {
            if (!$this->duitkuConfig) {
                throw new \Exception('Duitku configuration not found');
            }

            // If no payment method code provided, use default (will show selection page)
            if (!$paymentMethodCode) {
                $paymentMethodCode = ''; // Empty = user chooses at Duitku page
            }

            // Generate unique merchant order ID
            $merchantOrderId = 'DUITKU-' . $order->order_id;

            // Get customer phone number
            $phoneNumber = '081234567890'; // Default
            if ($order->user && $order->user->phone) {
                $phoneNumber = $order->user->phone;
            }

            // Prepare payment parameters for Direct Mode
            $params = [
                'paymentAmount' => (int) $order->harga,
                'merchantOrderId' => $merchantOrderId,
                'productDetails' => $order->layanan,
                'email' => $order->email_pembeli ?? 'customer@example.com',
                'phoneNumber' => $phoneNumber,
                'customerVaName' => $order->nickname ?? 'Customer',
                'paymentMethod' => $paymentMethodCode, // CRITICAL: Direct mode requires this
                
                // URLs
                'callbackUrl' => $this->api->duitku_callback_url ?? route('duitku.callback'),
                'returnUrl' => $this->api->duitku_return_url ?? env('APP_URL') . '/id/invoices/' . $order->order_id,
                
                'expiryPeriod' => 60, // 60 minutes
                
                // Customer details (required by Duitku)
                'customerDetail' => [
                    'firstName' => explode(' ', $order->nickname ?? 'Customer')[0],
                    'lastName' => explode(' ', $order->nickname ?? 'Customer')[1] ?? '',
                    'email' => $order->email_pembeli ?? 'customer@example.com',
                    'phoneNumber' => $phoneNumber,
                ],
                
                // Item details (required by Duitku)
                'itemDetails' => [
                    [
                        'name' => $order->layanan,
                        'price' => (int) $order->harga,
                        'quantity' => 1
                    ]
                ],
            ];

            // Create invoice via Duitku API (Direct mode if paymentMethod specified)
            $response = Pop::createInvoice($params, $this->duitkuConfig);
            $result = json_decode($response, true);

            Log::info('Duitku API Response', ['result' => $result]);

            if (isset($result['statusCode']) && $result['statusCode'] == '00') {
                Log::info('Duitku invoice created successfully', [
                    'order_id' => $order->order_id,
                    'reference' => $result['reference'],
                    'paymentMethod' => $paymentMethodCode
                ]);

                // Return payment details
                return [
                    'success' => true,
                    'reference' => $result['reference'],
                    'paymentUrl' => $result['paymentUrl'] ?? null,
                    'vaNumber' => $result['vaNumber'] ?? null,
                    'qrString' => $result['qrString'] ?? null,
                    'amount' => $result['amount'] ?? $order->harga,
                    'merchantOrderId' => $merchantOrderId,
                    'expired_at' => Carbon::now()->addMinutes((int) ($params['expiryPeriod'] ?? 60))->toIso8601String(),
                ];
            }

            // Log error response
            Log::error('Duitku API returned error', [
                'statusCode' => $result['statusCode'] ?? 'unknown',
                'statusMessage' => $result['statusMessage'] ?? 'No message',
                'result' => $result
            ]);

            throw new \Exception($result['statusMessage'] ?? 'Failed to create invoice');

        } catch (\Exception $e) {
            Log::error('Duitku createInvoice failed', [
                'order_id' => $order->order_id ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Handle Duitku callback
     * 
     * @param Request $request
     * @return Response
     */
    public function handleCallback(Request $request)
    {
        try {
            if (!$this->duitkuConfig) {
                Log::error('Duitku: Configuration not found');
                return response('Configuration error', 500);
            }

            $payload = $this->extractCallbackPayload($request);

            Log::info('Duitku Callback Received', ['data' => $payload]);

            if (empty($payload)) {
                Log::error('Duitku: Invalid callback payload', [
                    'payload' => $request->all(),
                    'raw' => $request->getContent(),
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
                $payment = Pembayaran::query()
                    ->where(function ($query) use ($reference, $merchantOrderId) {
                        if ($reference) {
                            $query->orWhere('duitku_reference', $reference)
                                ->orWhere('reference', $reference);
                        }

                        if ($merchantOrderId) {
                            $query->orWhere('duitku_merchant_order_id', $merchantOrderId);
                        }
                    })
                    ->where('status', 'Belum Lunas')
                    ->lockForUpdate()
                    ->first();

                if (!$payment) {
                    Log::warning('Duitku: Payment not found or already processed', [
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

                        if ($orderSuccess) {
                            $providerStatus = $result['order_status'] ?? 'Sukses';
                            $snValue = trim((string) ($result['sn'] ?? '')) ?: ($order->keterangan_sn ?: 'Sedang Diproses');
                            $orderData = ['status' => $providerStatus];
                            if ($transactionId) {
                                $orderData['provider_order_id'] = $transactionId;
                            }
                            $orderData['keterangan_sn'] = $snValue;
                            $order->update($orderData);

                            $this->runSafely('duitku_transaction_success_whatsapp', function () use ($waService, $payment, $order, $snValue) {
                                $waService->sendNotification($payment->no_pembeli, 'transaction_success', [
                                    'nickname' => $order->nickname,
                                    'order_id' => $payment->order_id,
                                    'product' => $order->layanan,
                                    'amount' => 'Rp ' . number_format($order->harga, 0, ',', '.'),
                                    'sn' => $snValue,
                                ]);
                            }, ['order_id' => $payment->order_id]);

                            $recipientEmail = $order->email_pembeli ?? ($order->user->email ?? null);
                            if ($recipientEmail) {
                                $this->runSafely('duitku_transaction_success_email', function () use ($emailService, $recipientEmail, $payment, $order, $snValue) {
                                    $emailService->sendTransactionEmail($recipientEmail, [
                                        'order_id' => $payment->order_id,
                                        'product' => $order->layanan,
                                        'amount' => 'Rp ' . number_format($order->harga, 0, ',', '.'),
                                        'status' => 'Success',
                                        'nickname' => $order->nickname,
                                        'sn' => $snValue,
                                        'note' => 'Harap Simpan Invoice ini, akan digunakan untuk verifikasi transaksi.'
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

                DB::rollBack();
                return response('Unknown status', 400);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Duitku callback error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
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
