<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Pembayaran;
use App\Models\Pembelian;
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
                // Save payment record
                Pembayaran::create([
                    'order_id' => $order->order_id,
                    'metode' => 'Duitku',
                    'no_pembayaran' => $result['vaNumber'] ?? $result['qrString'] ?? $result['paymentUrl'] ?? $result['reference'] ?? '-',
                    'reference' => $result['reference'] ?? null,
                    'duitku_merchant_order_id' => $merchantOrderId,
                    'duitku_reference' => $result['reference'] ?? null,
                    'harga' => $order->harga,
                    'no_pembeli' => $phoneNumber,
                    'status' => 'Belum Lunas',
                ]);

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

            // Get callback data with signature validation
            $callbackData = Pop::callback($this->duitkuConfig);
            $notification = json_decode($callbackData);

            Log::info('Duitku Callback Received', ['data' => $notification]);

            // Start database transaction with locking
            DB::beginTransaction();

            try {
                // Find payment record with lock
                $payment = Pembayaran::where('duitku_reference', $notification->reference)
                    ->where('status', 'Belum Lunas')
                    ->lockForUpdate()
                    ->first();

                if (!$payment) {
                    Log::warning('Duitku: Payment not found or already processed', [
                        'reference' => $notification->reference
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
                if ((int)$notification->amount !== (int)$payment->harga) {
                    Log::error('Duitku: Amount mismatch', [
                        'expected' => $payment->harga,
                        'received' => $notification->amount
                    ]);
                    DB::rollBack();
                    return response('Invalid amount', 400);
                }

                // Handle payment status
                if ($notification->resultCode == "00") {
                    // SUCCESS
                    $payment->update([
                        'status' => 'Lunas',
                        'paid_at' => now()
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
                        $waService->sendNotification($payment->no_pembeli, 'deposit_success', [
                            'username' => $order->username,
                            'order_id' => $payment->order_id,
                            'amount' => 'Rp ' . number_format($payment->harga, 0, ',', '.'),
                            'status' => 'Berhasil',
                        ]);

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

                    $waService->sendMessage($this->api->nomor_admin, $pesanAdmin);

                    // Process Order
                    $result = $orderProcessor->process($order);
                    $transactionId = $result['transaction_id'] ?? null;
                    $orderSuccess = $result['success'];

                    // Update Order
                    if ($orderSuccess) {
                        $snValue = trim((string) ($result['sn'] ?? '')) ?: ($order->keterangan_sn ?: 'Sedang Diproses');
                        $orderData = ['status' => 'Sukses'];
                        if ($transactionId) {
                            $orderData['provider_order_id'] = $transactionId;
                        }
                        $orderData['keterangan_sn'] = $snValue;
                        $order->update($orderData);

                        // Notify Buyer (WhatsApp)
                        $waService->sendNotification($payment->no_pembeli, 'transaction_success', [
                            'nickname' => $order->nickname,
                            'order_id' => $payment->order_id,
                            'product' => $order->layanan,
                            'amount' => 'Rp ' . number_format($order->harga, 0, ',', '.'),
                            'sn' => $snValue,
                        ]);

                        // Notify Buyer (Email)
                        $recipientEmail = $order->email_pembeli ?? ($order->user->email ?? null);
                        if ($recipientEmail) {
                            $emailService->sendTransactionEmail($recipientEmail, [
                                'order_id' => $payment->order_id,
                                'product' => $order->layanan,
                                'amount' => 'Rp ' . number_format($order->harga, 0, ',', '.'),
                                'status' => 'Success',
                                'nickname' => $order->nickname,
                                'sn' => $snValue,
                                'note' => 'Harap Simpan Invoice ini, akan digunakan untuk verifikasi transaksi.'
                            ]);
                        }

                    } else {
                        $order->update(['status' => 'Pending']);
                        Log::warning("Duitku: Order processing failed for {$payment->order_id}: " . $result['message']);

                        // Notify Buyer (Pending)
                        $waService->sendNotification($payment->no_pembeli, 'transaction_pending', [
                            'nickname' => $order->nickname,
                            'order_id' => $payment->order_id,
                            'product' => $order->layanan,
                            'amount' => 'Rp ' . number_format($order->harga, 0, ',', '.'),
                            'status' => 'Menunggu Provider',
                        ]);
                    }
                    }

                    DB::commit();
                    return response('SUCCESS', 200);

                } else if ($notification->resultCode == "01") {
                    // FAILED
                    $payment->update(['status' => 'Batal']);
                    $order->update(['status' => 'Gagal']);
                    app(\App\Services\PointService::class)->refundRedeemedPoints($order);

                    // Notify Buyer (Failed)
                    $waService = new WhatsappNotificationService();
                    $waService->sendNotification($payment->no_pembeli, 'transaction_failed', [
                        'nickname' => $order->nickname ?? 'Pelanggan',
                        'order_id' => $payment->order_id,
                        'product' => $order->layanan,
                        'reason' => 'Pembayaran Gagal/Kadaluarsa',
                    ]);

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
