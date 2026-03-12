<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\Order;

class IPay88Controller extends Controller
{
    public $paymentUrl = 'https://payment.ipay88.com.my/epayment/entry.asp';
    protected $requeryUrl = 'https://payment.ipay88.com.my/epayment/enquiry.asp';

    // Merchant Credentials
    protected $merchantCode = 'M34469';
    protected $merchantKey = 'T3c6K4gimk';

    public function getPaymentUrl()
    {
        return $this->paymentUrl;
    }

    /**
     * Function to send payment request to iPay88
     */
    public function initiatePayment(Request $request)
    {
        // Extract the request data
        $orderData = $request->only([
            'amount', 'reference', 'payment_method', 'description',
            'customer_email', 'customer_phone', 'currency',
            'callback_url', 'customer_name'
        ]);

        // Validate required fields
        if (empty($orderData['amount']) || $orderData['amount'] <= 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Amount cannot be empty or less than zero'
            ], 400);
        }

        // Prepare payment data for iPay88
        $paymentData = [
            'MerchantCode' => $this->merchantCode,
            'PaymentId' => '78', // Payment ID based on your gateway
            'RefNo' => $orderData['reference'],
            'Amount' => number_format($orderData['amount'], 2, '.', ''),
            'Currency' => $orderData['currency'],
            'ProdDesc' => $orderData['description'],
            'UserName' => $orderData['customer_name'],
            'UserEmail' => $orderData['customer_email'],
            'UserContact' => $orderData['customer_phone'],
            'Remark' => 'Optional remark',
            'SignatureType' => 'HMACSHA512',
            'ResponseURL' => $orderData['callback_url'],
            'BackendURL' => route('ipay88.backend'), // Backend callback URL
        ];

        // Generate Signature for the request
        $signature = $this->generateSignature($paymentData);
        $paymentData['Signature'] = $signature;

        // Log the data before sending the request
        Log::info('iPay88 Payment Initiation Request', [
            'payment_data' => $paymentData,
            'signature' => $signature,
        ]);

        try {
            // Send request to iPay88
            $response = Http::asForm()->post($this->paymentUrl, $paymentData);

            // Log response for debugging
            Log::info('iPay88 Payment Initiation Response', [
                'response_status' => $response->status(),
                'response_body' => $response->body(),
            ]);

            // Handle response from iPay88
            if ($response->successful()) {
                // Decode the response JSON
                $responseData = $response->json();

                if (isset($responseData['data'])) {
                    $data = $responseData['data'];
                    $no_pembayaran = $data['no_pembayaran'] ?? '';
                    $reference = $data['unique_code'] ?? '';
                    $amount = $data['amount'] ?? $orderData['amount'];

                    // Log successful payment initiation with extracted data
                    Log::info('iPay88 Payment Initiation Successful', [
                        'no_pembayaran' => $no_pembayaran,
                        'reference' => $reference,
                        'amount' => $amount,
                    ]);

                    // Return success response with payment data
                    return response()->json([
                        'status' => 'success',
                        'data' => [
                            'payment_url' => $data['checkout_url'] ?? '',
                            'no_pembayaran' => $no_pembayaran,
                            'amount' => $amount,
                            'reference' => $reference,
                        ]
                    ]);
                } else {
                    // Log error if data is missing in the response
                    Log::error('iPay88 Payment Initiation Failed - Missing Data', [
                        'response_data' => $responseData,
                    ]);

                    return response()->json([
                        'status' => 'error',
                        'message' => 'Response does not contain expected data',
                        'data' => $responseData
                    ], 400);
                }
            } else {
                // Log the error details in case of failure
                Log::error('iPay88 Payment Initiation Failed', [
                    'response_status' => $response->status(),
                    'response_body' => $response->body(),
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Payment initiation failed',
                    'error' => $response->body()
                ], $response->status());
            }
        } catch (\Exception $e) {
            // Log exception and return error response
            Log::error('iPay88 Payment Initiation Exception', [
                'exception_message' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Function to verify the response from iPay88
     */
    public function paymentResponse(Request $request)
    {
        $data = $request->all();

        // Verify the signature
        $signature = $data['Signature'];
        unset($data['Signature']); // Remove the signature from data

        if ($this->verifySignature($data, $signature)) {
            // Signature is valid, process the payment
            if ($data['Status'] == 1) {
                $order = Order::where('ref_no', $data['RefNo'])->first();
                if ($order) {
                    $order->status = 'paid';
                    $order->payment_date = Carbon::now();
                    $order->save();

                    return response()->json(['success' => 'Payment successful']);
                }
            } else {
                return response()->json(['error' => 'Payment failed']);
            }
        } else {
            return response()->json(['error' => 'Invalid signature'], 400);
        }
    }

    /**
     * Function to handle backend response from iPay88
     */
    public function backendResponse(Request $request)
    {
        $data = $request->all();

        // Verify the signature
        $signature = $data['Signature'];
        unset($data['Signature']); // Remove the signature from data

        if ($this->verifySignature($data, $signature)) {
            if ($data['Status'] == 1) {
                $order = Order::where('ref_no', $data['RefNo'])->first();
                if ($order) {
                    $order->status = 'paid';
                    $order->payment_date = Carbon::now();
                    $order->save();

                    return response()->json(['success' => 'Payment successful']);
                }
            } else {
                return response()->json(['error' => 'Payment failed']);
            }
        } else {
            return response()->json(['error' => 'Invalid signature'], 400);
        }
    }

    /**
     * Function to generate a signature for the payment request
     */
    private function generateSignature($data)
    {
        $signatureString = implode('', [
            $data['MerchantCode'],
            $data['PaymentId'],
            $data['RefNo'],
            $data['Amount'],
            $data['Currency'],
            $data['ProdDesc'],
            $data['UserName'],
            $data['UserEmail'],
            $data['UserContact'],
            $data['Remark'],
            $this->merchantKey,
        ]);

        return hash_hmac('sha512', $signatureString, $this->merchantKey);
    }

    /**
     * Function to verify the signature from the response data
     */
    private function verifySignature($data, $receivedSignature)
    {
        $calculatedSignature = $this->generateSignature($data);

        return hash_equals($calculatedSignature, $receivedSignature);
    }
}
