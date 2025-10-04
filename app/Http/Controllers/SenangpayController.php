<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SenangpayController extends Controller
{
    private $apiUrl;
    private $merchantId;
    private $secretKey;

    public function __construct()
    {
        $this->apiUrl = rtrim(env('SENANGPAY_URL', 'https://sandbox.senangpay.my/payment/'), '/') . '/';
        $this->merchantId = env('SENANGPAY_MERCHANT_ID', 'your_merchant_id');
        $this->secretKey = env('SENANGPAY_SECRET_KEY', 'your_secret_key');
    }

    private function generateSecureHash($detail, $amount, $orderId)
    {
        $hashString = $this->secretKey . $detail . $amount . $orderId;
        return hash_hmac('sha256', $hashString, $this->secretKey);
    }

    public function createPaymentRequest(Request $request)
    {
        $validated = $request->validate([
            'detail' => 'required|max:500',
            'amount' => 'required|numeric|min:0.01',
            'order_id' => 'required|max:100',
            'name' => 'nullable|max:100',
            'email' => 'nullable|email|max:100',
            'phone' => 'nullable|max:15',
            'timeout' => 'nullable|integer|min:60',
        ]);

        $detail = $validated['detail'];
        $amount = number_format($validated['amount'], 2, '.', '');
        $orderId = $validated['order_id'];
        $name = $validated['name'] ?? 'Customer';
        $email = $validated['email'] ?? 'example@example.com';
        $phone = $validated['phone'] ?? '0000000000';
        $timeout = $validated['timeout'] ?? 480;

        $hash = $this->generateSecureHash($detail, $amount, $orderId);

        $paymentData = [
            'detail' => $detail,
            'amount' => $amount,
            'order_id' => $orderId,
            'hash' => $hash,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'timeout' => $timeout,
        ];

        $paymentUrl = $this->apiUrl . $this->merchantId;

        Log::info("Payment request prepared", $paymentData);

        return response()->json([
            'status' => 'success',
            'message' => 'Redirecting to payment page.',
            'payment_url' => $paymentUrl,
            'payment_data' => $paymentData,
        ]);
    }

    public function handlePaymentResponse(Request $request)
    {
        $statusId = $request->input('status_id');
        $orderId = $request->input('order_id');
        $msg = $request->input('msg');
        $transactionId = $request->input('transaction_id');
        $hash = $request->input('hash');

        Log::info("Payment response received", [
            'status_id' => $statusId,
            'order_id' => $orderId,
            'msg' => $msg,
            'transaction_id' => $transactionId,
            'hash' => $hash,
        ]);

        $hashString = $this->secretKey . $statusId . $orderId . $transactionId . $msg;
        $expectedHash = hash_hmac('sha256', $hashString, $this->secretKey);

        if ($expectedHash !== $hash) {
            Log::error('Invalid hash detected for order: ' . $orderId);

            return response()->json([
                'status' => 'error',
                'message' => 'Invalid hash. Data could not be verified.',
            ], 400);
        }

        if ($statusId == 1) {
            Log::info("Payment successful for Order: $orderId");
        } elseif ($statusId == 2) {
            Log::info("Payment pending for Order: $orderId");
        } else {
            Log::error("Payment failed for Order: $orderId");
        }

        return response()->json([
            'status' => 'success',
            'message' => $msg,
            'transaction_id' => $transactionId,
        ]);
    }
}
