<?php

namespace App\Http\Controllers;


use App\Jobs\SendPembelianToProviderJob;
use App\Models\Deposit;
use App\Models\Pembayaran;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;

class CallbackController extends Controller
{
    public function razerpay(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nbcb' => ['required', 'integer', 'in:1'],
            'tranID' => ['required', 'string', 'max:100'],
            'orderid' => ['required', 'string', 'max:100'],
            'status' => ['required', 'string', 'max:10'],
            'domain' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'appcode' => ['required', 'string', 'max:100'],
            'paydate' => ['required', 'string', 'max:100'],
            'skey' => ['required', 'string', 'size:32'],
        ]);

        if ($validator->fails()) {
            return Response::json(['success' => false, 'message' => 'Invalid callback payload'], 400);
        }

        $payload = $validator->validated();
        $secret = (string) config('services.razerpay.secret_key');

        if ($secret === '') {
            Log::error('razerpay.callback.secret_missing');

            return Response::json(['success' => false, 'message' => 'Callback unavailable'], 503);
        }

        $key0 = md5(
            $payload['tranID']
            . $payload['orderid']
            . $payload['status']
            . $payload['domain']
            . $payload['amount']
            . $payload['currency'],
        );
        $expectedSignature = md5(
            $payload['paydate']
            . $payload['domain']
            . $key0
            . $payload['appcode']
            . $secret,
        );

        if (! hash_equals($expectedSignature, strtolower($payload['skey']))) {
            Log::warning('razerpay.callback.invalid_signature', [
                'ip_hash' => $this->ipHash($request),
                'payload_hash' => $this->payloadHash($payload),
            ]);

            return Response::json(['success' => false, 'message' => 'Invalid signature'], 400);
        }

        if ($payload['status'] !== '00') {
            return Response::json(['success' => false, 'message' => 'Payment is not successful'], 422);
        }

        $result = DB::transaction(function () use ($payload): array {
            $invoice = Pembayaran::query()
                ->where('order_id', $payload['orderid'])
                ->lockForUpdate()
                ->first();

            if (! $invoice) {
                return ['decision' => 'missing'];
            }

            if (in_array($invoice->normalizedStatus(), ['lunas', 'paid', 'success'], true)) {
                return ['decision' => 'duplicate'];
            }

            if ((int) round((float) $payload['amount']) !== (int) $invoice->harga) {
                return ['decision' => 'amount_mismatch'];
            }

            $deposit = Deposit::query()
                ->where('order_id', $invoice->order_id)
                ->lockForUpdate()
                ->first();
            $purchase = $deposit ? null : $invoice->pembelian()->lockForUpdate()->first();

            if (! $deposit && ! $purchase) {
                return ['decision' => 'missing'];
            }

            $invoice->forceFill([
                'status' => 'Lunas',
                'paid_at' => $invoice->paid_at ?: now(),
                'reference' => $payload['tranID'],
            ])->save();

            if ($deposit) {
                if (strtolower(trim((string) $deposit->status)) !== 'success') {
                    $user = User::query()->where('username', $deposit->username)->lockForUpdate()->first();
                    $deposit->update(['status' => 'Success']);
                    $user?->increment('balance', (int) $deposit->jumlah);
                }

                return ['decision' => 'deposit_paid'];
            }

            return ['decision' => 'purchase_paid', 'purchase_id' => $purchase->id];
        }, 3);

        if (($result['decision'] ?? null) === 'purchase_paid') {
            SendPembelianToProviderJob::dispatch((int) $result['purchase_id'], null, 'auto');
        }

        $status = match ($result['decision'] ?? null) {
            'missing' => 404,
            'amount_mismatch' => 400,
            default => 200,
        };

        Log::info('razerpay.callback.' . ($result['decision'] ?? 'unknown'), [
            'ip_hash' => $this->ipHash($request),
            'payload_hash' => $this->payloadHash($payload),
        ]);

        return Response::json([
            'success' => $status === 200,
            'message' => match ($result['decision'] ?? null) {
                'duplicate' => 'Callback already processed',
                'deposit_paid', 'purchase_paid' => 'Callback processed',
                'amount_mismatch' => 'Invalid amount',
                default => 'Invoice not found',
            },
        ], $status);
    }

    private function payloadHash(array $payload): string
    {
        unset($payload['skey']);
        ksort($payload);

        return hash('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES));
    }

    private function ipHash(Request $request): string
    {
        return hash_hmac('sha256', (string) $request->ip(), (string) config('app.key'));
    }
}
