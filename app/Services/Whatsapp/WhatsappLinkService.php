<?php

namespace App\Services\Whatsapp;

use App\Models\User;
use App\Models\WhatsappLinkChallenge;
use App\Support\WhatsappNumberNormalizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class WhatsappLinkService
{
    public const DEFAULT_EXPIRY_MINUTES = 10;

    public const DEFAULT_MAX_ATTEMPTS = 5;

    public function createChallenge(User $user, string $number): array
    {
        $normalizedNumber = $this->normalizeNumber($number);
        $code = (string) random_int(100000, 999999);
        $expiresAt = now()->addMinutes($this->expiryMinutes());
        $maxAttempts = $this->maxAttempts();

        $challenge = DB::transaction(function () use ($user, $normalizedNumber, $code, $expiresAt, $maxAttempts): WhatsappLinkChallenge {
            $this->assertNumberAvailable($normalizedNumber, $user);

            WhatsappLinkChallenge::query()
                ->where('user_id', $user->getKey())
                ->whereNull('consumed_at')
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);

            return WhatsappLinkChallenge::query()->create([
                'user_id' => $user->getKey(),
                'whatsapp_number' => $normalizedNumber,
                'code_hash' => Hash::make($code),
                'expires_at' => $expiresAt,
                'max_attempts' => $maxAttempts,
            ]);
        });

        return [
            'status' => 'created',
            'challenge' => $challenge,
            'code' => $code,
            'expires_at' => $expiresAt,
            'expires_in_minutes' => $this->expiryMinutes(),
        ];
    }

    public function verifyChallenge(string $number, string $code): array
    {
        $normalizedNumber = $this->normalizeNumber($number);
        $code = trim($code);

        if (! preg_match('/^\d{6}$/', $code)) {
            return $this->failure('invalid_code');
        }

        return DB::transaction(function () use ($normalizedNumber, $code): array {
            $challenge = WhatsappLinkChallenge::query()
                ->where('whatsapp_number', $normalizedNumber)
                ->whereNull('consumed_at')
                ->whereNull('revoked_at')
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (! $challenge) {
                return $this->failure('not_found');
            }

            if ($challenge->expires_at?->isPast()) {
                $challenge->forceFill(['revoked_at' => now()])->save();

                return $this->failure('expired');
            }

            if ($challenge->attempts >= $challenge->max_attempts) {
                $challenge->forceFill(['revoked_at' => now()])->save();

                return $this->failure('max_attempts');
            }

            if (! Hash::check($code, (string) $challenge->code_hash)) {
                $challenge->increment('attempts');
                $challenge->refresh();

                if ($challenge->attempts >= $challenge->max_attempts) {
                    $challenge->forceFill(['revoked_at' => now()])->save();

                    return $this->failure('max_attempts');
                }

                return $this->failure('invalid_code');
            }

            $user = User::query()
                ->whereKey($challenge->user_id)
                ->lockForUpdate()
                ->first();

            if (! $user) {
                $challenge->forceFill(['revoked_at' => now()])->save();

                return $this->failure('user_not_found');
            }

            $this->assertNumberAvailable($normalizedNumber, $user);

            $user->forceFill([
                'no_wa' => $normalizedNumber,
                'whatsapp_verified_at' => now(),
            ])->save();

            $challenge->forceFill(['consumed_at' => now()])->save();

            return [
                'status' => 'verified',
                'user' => $user->fresh(),
                'challenge' => $challenge->fresh(),
            ];
        });
    }

    public function revokeForUser(User $user): int
    {
        return WhatsappLinkChallenge::query()
            ->where('user_id', $user->getKey())
            ->whereNull('consumed_at')
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }

    public function unlink(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $this->revokeForUser($user);
            $user->forceFill([
                'whatsapp_verified_at' => null,
            ])->save();
        });
    }

    private function assertNumberAvailable(string $number, User $user): void
    {
        $takenByAnotherUser = User::query()
            ->where('no_wa', $number)
            ->where('id', '<>', $user->getKey())
            ->whereNotNull('whatsapp_verified_at')
            ->exists();

        if ($takenByAnotherUser) {
            throw ValidationException::withMessages([
                'whatsapp' => 'Nomor WhatsApp tidak dapat digunakan.',
            ]);
        }
    }

    private function normalizeNumber(string $number): string
    {
        $normalized = WhatsappNumberNormalizer::normalize($number);

        if ($normalized === null) {
            throw ValidationException::withMessages([
                'whatsapp' => 'Nomor WhatsApp tidak valid.',
            ]);
        }

        return $normalized;
    }

    private function failure(string $reason): array
    {
        return [
            'status' => 'failed',
            'reason' => $reason,
        ];
    }

    private function expiryMinutes(): int
    {
        return max(1, (int) config('services.fonnte.link_challenge_expiry_minutes', self::DEFAULT_EXPIRY_MINUTES));
    }

    private function maxAttempts(): int
    {
        return max(1, min(255, (int) config('services.fonnte.link_challenge_max_attempts', self::DEFAULT_MAX_ATTEMPTS)));
    }
}
