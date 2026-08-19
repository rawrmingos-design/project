<?php

namespace App\Services\Checkout;

use App\Models\BotCheckoutIntent;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BotCheckoutIntentService
{
    private const ALLOWED_SOURCES = ['whatsapp_gateway', 'telegram_gateway'];
    private const INTENT_TTL_MINUTES = 15;

    /**
     * @return array{intent: BotCheckoutIntent, token: string, replayed: bool}
     */
    public function create(array $payload, array $quote, array $context, ?User $user = null): array
    {
        [$source, $senderFingerprint, $messageFingerprint, $tenantScope, $tenantId] = $this->scope($context, $user);
        $canonicalPayload = $this->canonicalPayload($payload);
        $canonicalQuote = $this->canonicalQuote($quote);
        $payloadFingerprint = $this->fingerprint($canonicalPayload);
        $quoteFingerprint = $this->fingerprint($canonicalQuote);

        try {
            return DB::transaction(function () use (
                $canonicalPayload,
                $canonicalQuote,
                $messageFingerprint,
                $payloadFingerprint,
                $quoteFingerprint,
                $senderFingerprint,
                $source,
                $tenantId,
                $tenantScope,
                $user,
            ): array {
                $existing = $this->findByOrigin(
                    $tenantScope,
                    $source,
                    $senderFingerprint,
                    $messageFingerprint,
                    true,
                );

                if ($existing) {
                    return $this->replayResult(
                        $existing,
                        $payloadFingerprint,
                        $user,
                    );
                }

                $token = $this->randomToken();
                $intent = BotCheckoutIntent::query()->create([
                    'intent_id' => (string) Str::uuid(),
                    'tenant_scope' => $tenantScope,
                    'tenant_id' => $tenantId,
                    'user_id' => $user?->id,
                    'source' => $source,
                    'sender_fingerprint' => $senderFingerprint,
                    'origin_message_fingerprint' => $messageFingerprint,
                    'action_token_hash' => $this->tokenHash($token),
                    'action_token' => $token,
                    'payload' => $canonicalPayload,
                    'payload_fingerprint' => $payloadFingerprint,
                    'quote_snapshot' => $canonicalQuote,
                    'quote_fingerprint' => $quoteFingerprint,
                    'status' => BotCheckoutIntent::STATUS_AWAITING_CONFIRMATION,
                    'expires_at' => now()->addMinutes(self::INTENT_TTL_MINUTES),
                ]);

                return [
                    'intent' => $intent,
                    'token' => $token,
                    'replayed' => false,
                ];
            });
        } catch (QueryException $exception) {
            if (! $this->isUniqueConstraintViolation($exception)) {
                throw $exception;
            }

            $existing = $this->findByOrigin(
                $tenantScope,
                $source,
                $senderFingerprint,
                $messageFingerprint,
            );

            if (! $existing) {
                throw $exception;
            }

            return $this->replayResult(
                $existing,
                $payloadFingerprint,
                $user,
            );
        }
    }

    public function findScoped(string $token, array $context, ?User $user = null): ?BotCheckoutIntent
    {
        if (! preg_match('/^[A-Za-z0-9_-]{20,32}$/', $token)) {
            return null;
        }

        [$source, $senderFingerprint, , $tenantScope] = $this->scope($context, $user, false);

        return BotCheckoutIntent::query()
            ->where('action_token_hash', $this->tokenHash($token))
            ->where('tenant_scope', $tenantScope)
            ->where('source', $source)
            ->where('sender_fingerprint', $senderFingerprint)
            ->where('user_id', $user?->id)
            ->first();
    }

    /**
     * @return array{status: string, intent?: BotCheckoutIntent}
     */
    public function claim(string $token, array $context, ?User $user = null): array
    {
        [$source, $senderFingerprint, $confirmationFingerprint, $tenantScope] = $this->scope($context, $user);

        return DB::transaction(function () use (
            $confirmationFingerprint,
            $senderFingerprint,
            $source,
            $tenantScope,
            $token,
            $user,
        ): array {
            $intent = BotCheckoutIntent::query()
                ->where('action_token_hash', $this->tokenHash($token))
                ->where('tenant_scope', $tenantScope)
                ->where('source', $source)
                ->where('sender_fingerprint', $senderFingerprint)
                ->where('user_id', $user?->id)
                ->lockForUpdate()
                ->first();

            if (! $intent) {
                return ['status' => 'invalid'];
            }

            if ($intent->status === BotCheckoutIntent::STATUS_COMPLETED) {
                return ['status' => 'completed', 'intent' => $intent];
            }

            if ($intent->status === BotCheckoutIntent::STATUS_PROCESSING) {
                return ['status' => 'processing', 'intent' => $intent];
            }

            if ($intent->expires_at?->isPast()) {
                $intent->forceFill([
                    'status' => BotCheckoutIntent::STATUS_EXPIRED,
                    'failure_code' => 'intent_expired',
                ])->save();

                return ['status' => 'expired', 'intent' => $intent];
            }

            if (! in_array($intent->status, [
                BotCheckoutIntent::STATUS_AWAITING_CONFIRMATION,
                BotCheckoutIntent::STATUS_FAILED_RETRYABLE,
            ], true)) {
                return ['status' => $intent->status, 'intent' => $intent];
            }

            $intent->forceFill([
                'status' => BotCheckoutIntent::STATUS_PROCESSING,
                'confirmation_message_fingerprint' => $confirmationFingerprint,
                'confirmed_at' => $intent->confirmed_at ?? now(),
                'processing_at' => now(),
                'failure_code' => null,
            ])->save();

            return ['status' => 'claimed', 'intent' => $intent->fresh()];
        });
    }

    public function cancel(string $token, array $context, ?User $user = null): bool
    {
        $intent = $this->findScoped($token, $context, $user);
        if (! $intent) {
            return false;
        }

        return DB::transaction(function () use ($intent): bool {
            $locked = BotCheckoutIntent::query()->whereKey($intent->getKey())->lockForUpdate()->first();
            if (! $locked || ! in_array($locked->status, [
                BotCheckoutIntent::STATUS_AWAITING_CONFIRMATION,
                BotCheckoutIntent::STATUS_FAILED_RETRYABLE,
            ], true)) {
                return false;
            }

            $locked->forceFill([
                'status' => BotCheckoutIntent::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'failure_code' => 'cancelled_by_user',
            ])->save();

            return true;
        });
    }

    public function expireForQuoteChange(BotCheckoutIntent $intent): void
    {
        DB::transaction(function () use ($intent): void {
            $locked = BotCheckoutIntent::query()
                ->whereKey($intent->getKey())
                ->lockForUpdate()
                ->first();

            if (
                ! $locked
                || $locked->status
                    !== BotCheckoutIntent::STATUS_PROCESSING
                || $locked->provider_dispatched_at !== null
            ) {
                return;
            }

            $locked->forceFill([
                'status' => BotCheckoutIntent::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'failure_code' => 'quote_changed',
            ])->save();
        });
    }

    public function prepareMutation(string $intentId, array $context, ?User $user = null): BotCheckoutIntent
    {
        [$source, $senderFingerprint, , $tenantScope] = $this->scope($context, $user, false);

        return DB::transaction(function () use (
            $intentId,
            $senderFingerprint,
            $source,
            $tenantScope,
            $user,
        ): BotCheckoutIntent {
            $intent = BotCheckoutIntent::query()
                ->where('intent_id', $intentId)
                ->where('tenant_scope', $tenantScope)
                ->where('source', $source)
                ->where('sender_fingerprint', $senderFingerprint)
                ->where('user_id', $user?->id)
                ->lockForUpdate()
                ->first();

            if (! $intent || ! in_array($intent->status, [
                BotCheckoutIntent::STATUS_PROCESSING,
                BotCheckoutIntent::STATUS_COMPLETED,
            ], true)) {
                throw ValidationException::withMessages([
                    'intent' => 'Checkout belum dikonfirmasi atau tidak valid.',
                ]);
            }

            if ($intent->status === BotCheckoutIntent::STATUS_PROCESSING && blank($intent->merchant_reference)) {
                $intent->merchant_reference = 'BOT' . now()->format('ymdHis') . Str::upper(Str::random(8));
                $intent->save();
            }

            return $intent->fresh();
        });
    }

    public function markProviderDispatch(
        BotCheckoutIntent $intent,
    ): bool {
        return DB::transaction(function () use ($intent): bool {
            $locked = BotCheckoutIntent::query()
                ->whereKey($intent->getKey())
                ->lockForUpdate()
                ->first();

            if (
                ! $locked
                || $locked->status
                    !== BotCheckoutIntent::STATUS_PROCESSING
                || $locked->provider_dispatched_at !== null
            ) {
                return false;
            }

            $locked->forceFill([
                'provider_dispatched_at' => now(),
            ])->save();
            $intent->setRawAttributes(
                $locked->getAttributes(),
                true,
            );

            return true;
        });
    }

    public function markCompleted(
        BotCheckoutIntent $intent,
        string $orderId,
    ): void {
        $intent->forceFill([
            'status' => BotCheckoutIntent::STATUS_COMPLETED,
            'order_id' => $orderId,
            'completed_at' => now(),
            'failure_code' => null,
        ])->save();
    }

    public function markFailure(
        BotCheckoutIntent $intent,
        bool $providerDispatched = false,
    ): void {
        $intent->refresh();
        if (
            $intent->status
            !== BotCheckoutIntent::STATUS_PROCESSING
        ) {
            return;
        }

        $outcomeUnknown = $providerDispatched
            || $intent->provider_dispatched_at !== null;
        $intent->forceFill([
            'status' => $outcomeUnknown
                ? BotCheckoutIntent::STATUS_REQUIRES_RECONCILIATION
                : BotCheckoutIntent::STATUS_FAILED_RETRYABLE,
            'failure_code' => $outcomeUnknown
                ? 'provider_outcome_unknown'
                : 'pre_dispatch_failure',
        ])->save();
    }

    public function quoteMatches(BotCheckoutIntent $intent, array $quote): bool
    {
        return hash_equals((string) $intent->quote_fingerprint, $this->quoteFingerprint($quote));
    }

    public function payloadMatches(BotCheckoutIntent $intent, array $payload): bool
    {
        $matches = hash_equals(
            (string) $intent->payload_fingerprint,
            $this->fingerprint($this->canonicalPayload($payload)),
        );

        if (! $matches) {
            \Illuminate\Support\Facades\Log::warning('BotCheckoutIntentService::payloadMatches mismatch', [
                'intent_id' => $intent->intent_id,
                'stored_fp' => $intent->payload_fingerprint,
                'incoming_fp' => $this->fingerprint($this->canonicalPayload($payload)),
                'stored_payload' => $intent->payload,
                'incoming_payload' => $payload,
            ]);
        }

        return $matches;
    }

    private function findByOrigin(
        string $tenantScope,
        string $source,
        string $senderFingerprint,
        string $messageFingerprint,
        bool $lock = false,
    ): ?BotCheckoutIntent {
        $query = BotCheckoutIntent::query()
            ->where('tenant_scope', $tenantScope)
            ->where('source', $source)
            ->where('sender_fingerprint', $senderFingerprint)
            ->where('origin_message_fingerprint', $messageFingerprint);

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    /**
     * @return array{intent: BotCheckoutIntent, token: string, replayed: true}
     */
    private function replayResult(
        BotCheckoutIntent $intent,
        string $payloadFingerprint,
        ?User $user,
    ): array {
        if ((int) ($intent->user_id ?? 0) !== (int) ($user?->id ?? 0)) {
            throw ValidationException::withMessages([
                'intent' => 'Identitas akun checkout berubah.',
            ]);
        }

        if (! hash_equals(
            (string) $intent->payload_fingerprint,
            $payloadFingerprint,
        )) {
            throw ValidationException::withMessages([
                'intent' => 'Identitas pesan sudah digunakan untuk checkout berbeda.',
            ]);
        }

        return [
            'intent' => $intent,
            'token' => (string) $intent->action_token,
            'replayed' => true,
        ];
    }

    private function isUniqueConstraintViolation(
        QueryException $exception,
    ): bool {
        return in_array(
            (string) ($exception->errorInfo[0] ?? ''),
            ['23000', '23505'],
            true,
        );
    }

    private function scope(array $context, ?User $user = null, bool $requireMessage = true): array
    {
        $source = strtolower(trim((string) ($context['source'] ?? '')));
        $externalUserId = trim((string) ($context['external_user_id'] ?? ''));
        $messageId = trim((string) ($context['message_id'] ?? ''));

        if (! in_array($source, self::ALLOWED_SOURCES, true) || $externalUserId === '') {
            throw ValidationException::withMessages(['intent' => 'Identitas gateway checkout tidak lengkap.']);
        }

        if ($requireMessage && $messageId === '') {
            throw ValidationException::withMessages(['intent' => 'ID pesan gateway checkout tidak lengkap.']);
        }

        $tenantId = $user?->tenant_id ?? app(TenantContext::class)->id();
        $tenantScope = $tenantId === null ? 'main' : (string) $tenantId;

        return [
            $source,
            $this->identifierFingerprint($source, $externalUserId),
            $messageId === '' ? '' : $this->identifierFingerprint($source, $messageId),
            $tenantScope,
            $tenantId,
        ];
    }

    private function canonicalPayload(array $payload): array
    {
        $keys = [
            'service', 'payment_method', 'uid', 'zone',
            'nomor', 'whatsapp', 'email', 'voucher', 'ktg_tipe',
            'qty', 'nickname', 'email_joki', 'password_joki',
            'loginvia_joki', 'nickname_joki', 'request_joki',
            'catatan_joki', 'tglmain_joki', 'jambooking_joki',
        ];
        $canonical = [];

        if (
            array_key_exists('service_id', $payload)
            && ! array_key_exists('service', $payload)
        ) {
            $payload['service'] = $payload['service_id'];
        }

        foreach ($keys as $key) {
            if (
                ! array_key_exists($key, $payload)
                || $payload[$key] === null
            ) {
                continue;
            }

            $canonical[$key] = is_string($payload[$key])
                ? trim($payload[$key])
                : $payload[$key];
        }

        ksort($canonical);

        return $canonical;
    }

    private function quoteFingerprint(array $quote): string
    {
        return $this->fingerprint($this->canonicalQuote($quote));
    }

    private function canonicalQuote(array $quote): array
    {
        $data = is_array($quote['data'] ?? null)
            ? $quote['data']
            : $quote;

        return [
            'service_id' => (int) ($data['service_id'] ?? 0),
            'service_name' => (string) ($data['service_name'] ?? ''),
            'payment_method' => [
                'code' => (string) data_get(
                    $data,
                    'payment_method.code',
                    '',
                ),
                'name' => (string) data_get(
                    $data,
                    'payment_method.name',
                    '',
                ),
                'type' => (string) data_get(
                    $data,
                    'payment_method.type',
                    '',
                ),
            ],
            'base_amount' => (int) ($data['base_amount'] ?? 0),
            'discount' => (int) ($data['discount'] ?? 0),
            'payment_fee' => (int) ($data['payment_fee'] ?? 0),
            'total_amount' => (int) ($data['total_amount'] ?? 0),
        ];
    }

    private function fingerprint(array $value): string
    {
        return hash('sha256', (string) json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    private function identifierFingerprint(string $source, string $value): string
    {
        return hash_hmac('sha256', $source . '|' . $value, (string) config('app.key'));
    }

    private function tokenHash(string $token): string
    {
        return hash_hmac('sha256', $token, (string) config('app.key'));
    }

    private function randomToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '=');
    }
}
