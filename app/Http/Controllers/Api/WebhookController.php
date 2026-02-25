<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Models\Pembelian;

class WebhookController extends Controller
{
    /**
     * Handle Digiflazz webhook
     */
    public function digiflazz(Request $request): JsonResponse
    {
        try {
            // FIX #4: Signature check WAJIB — tolak jika secret tidak dikonfigurasi
            $secret = config('providers.digiflazz.webhook_secret');
            if (!$secret) {
                Log::error('Digiflazz webhook secret not configured! Request blocked for security.');
                return response()->json(['error' => 'Server misconfigured'], 500);
            }

            if (!$this->verifyDigiflazzSignature($request)) {
                Log::warning('Digiflazz webhook signature verification failed', [
                    'ip' => $request->ip(),
                ]);
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            Log::info('Digiflazz webhook received (verified)', $request->all());
            $data = $request->all();
            $this->processDigiflazzWebhook($data);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Digiflazz webhook error: ' . $e->getMessage());
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Handle BangJeff webhook
     */
    public function bangjeff(Request $request): JsonResponse
    {
        try {
            // FIX #4: Signature check WAJIB — tolak jika secret tidak dikonfigurasi
            $secret = config('providers.bangjeff.webhook_secret');
            if (!$secret) {
                Log::error('BangJeff webhook secret not configured! Request blocked for security.');
                return response()->json(['error' => 'Server misconfigured'], 500);
            }

            if (!$this->verifyBangJeffSignature($request)) {
                Log::warning('BangJeff webhook signature verification failed', [
                    'ip' => $request->ip(),
                ]);
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            Log::info('BangJeff webhook received (verified)', $request->all());
            $data = $request->all();
            $this->processBangJeffWebhook($data);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('BangJeff webhook error: ' . $e->getMessage());
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Generic webhook handler for other providers
     */
    public function generic(Request $request, string $provider): JsonResponse
    {
        // FIX #4: Generic webhook diblokir — tidak ada cara verifikasi identitas provider secara aman.
        // Implementasikan handler spesifik per-provider dengan signature verification yang proper.
        Log::warning("Generic webhook blocked for provider: {$provider}", [
            'ip'   => $request->ip(),
            'data' => $request->all(),
        ]);
        return response()->json(['error' => 'This endpoint is disabled for security reasons'], 403);
    }

    /**
     * Process Digiflazz webhook data
     */
    private function processDigiflazzWebhook(array $data): void
    {
        $refId = $data['ref_id'] ?? null;
        $status = $data['status'] ?? null;
        $sn = $data['sn'] ?? null;
        
        if (!$refId) {
            Log::warning('Digiflazz webhook missing ref_id');
            return;
        }
        
        // Find transaction by reference ID
        $transaction = Pembelian::where('order_id', $refId)->first();
        
        if (!$transaction) {
            Log::warning("Transaction not found for ref_id: {$refId}");
            return;
        }
        
        // Update transaction status
        $newStatus = $this->mapDigiflazzStatus($status);
        
        $transaction->update([
            'status' => $newStatus,
            'sn' => $sn,
            'provider_response' => json_encode($data),
            'updated_at' => now(),
        ]);
        
        Log::info("Transaction {$refId} updated to status: {$newStatus}");
    }

    /**
     * Process BangJeff webhook data
     */
    private function processBangJeffWebhook(array $data): void
    {
        $orderId = $data['order_id'] ?? null;
        $status = $data['status'] ?? null;
        $sn = $data['sn'] ?? null;
        
        if (!$orderId) {
            Log::warning('BangJeff webhook missing order_id');
            return;
        }
        
        // Find transaction by order ID
        $transaction = Pembelian::where('order_id', $orderId)->first();
        
        if (!$transaction) {
            Log::warning("Transaction not found for order_id: {$orderId}");
            return;
        }
        
        // Update transaction status
        $newStatus = $this->mapBangJeffStatus($status);
        
        $transaction->update([
            'status' => $newStatus,
            'sn' => $sn,
            'provider_response' => json_encode($data),
            'updated_at' => now(),
        ]);
        
        Log::info("Transaction {$orderId} updated to status: {$newStatus}");
    }

    /**
     * Process generic webhook data
     */
    private function processGenericWebhook(string $provider, array $data): void
    {
        Log::info("Processing generic webhook for provider: {$provider}", $data);
        
        // Basic webhook processing - can be extended for specific providers
        $orderId = $data['order_id'] ?? $data['ref_id'] ?? null;
        $status = $data['status'] ?? null;
        
        if (!$orderId) {
            Log::warning("{$provider} webhook missing order identifier");
            return;
        }
        
        $transaction = Pembelian::where('order_id', $orderId)->first();
        
        if ($transaction) {
            $transaction->update([
                'provider_response' => json_encode($data),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Verify Digiflazz webhook signature
     */
    private function verifyDigiflazzSignature(Request $request): bool
    {
        $signature = $request->header('X-Digiflazz-Signature');
        $payload = $request->getContent();
        $secret = config('providers.digiflazz.webhook_secret');
        
        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Verify BangJeff webhook signature
     */
    private function verifyBangJeffSignature(Request $request): bool
    {
        $signature = $request->header('X-BangJeff-Signature');
        $payload = $request->getContent();
        $secret = config('providers.bangjeff.webhook_secret');
        
        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Map Digiflazz status to internal status
     */
    private function mapDigiflazzStatus(string $status): string
    {
        return match(strtolower($status)) {
            'sukses' => 'Success',
            'pending' => 'Pending',
            'gagal' => 'Failed',
            'proses' => 'Processing',
            default => 'Unknown'
        };
    }

    /**
     * Map BangJeff status to internal status
     */
    private function mapBangJeffStatus(string $status): string
    {
        return match(strtolower($status)) {
            'success', 'sukses' => 'Success',
            'pending', 'process' => 'Pending',
            'error', 'gagal' => 'Failed',
            'processing' => 'Processing',
            default => 'Unknown'
        };
    }
}
