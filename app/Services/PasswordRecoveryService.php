<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Throwable;

class PasswordRecoveryService
{
    public const REQUEST_ACCEPTED_MESSAGE = 'Jika akun dan metode pemulihan tersedia, instruksi reset telah dikirim.';
    public const RESET_FAILURE_MESSAGE = 'Tautan reset tidak valid atau telah kedaluwarsa.';

    public function __construct(
        private readonly EmailNotificationService $emailNotificationService,
        private readonly WhatsappNotificationService $whatsappNotificationService,
    ) {
    }

    public function requestRecovery(string $username): void
    {
        $identifier = $this->identifierFingerprint($username);
        $users = User::query()->where('username', $username)->limit(2)->get();

        if ($users->count() !== 1) {
            $this->logRequest($identifier, null, $users->isEmpty() ? 'unknown_username' : 'ambiguous_username');

            return;
        }

        $user = $users->first();
        $email = trim((string) $user->email);

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->logRequest($identifier, $user->getKey(), 'invalid_email');

            return;
        }

        if (User::query()->where('email', $email)->limit(2)->count() !== 1) {
            $this->logRequest($identifier, $user->getKey(), 'duplicate_email');

            return;
        }

        /** @var PasswordBroker $broker */
        $broker = Password::broker('users');
        $token = $broker->createToken($user);
        $resetUrl = $this->resetUrl($token, $email);
        $emailDelivered = false;
        $whatsappDelivered = false;

        try {
            $emailDelivered = $this->emailNotificationService->sendGenericEmail(
                $email,
                'Instruksi Reset Kata Sandi',
                $this->emailContent($user, $resetUrl),
                [
                    'reference_id' => 'PASSWORD-RESET',
                    'status' => 'info',
                    'recipient_name' => (string) ($user->name ?: $user->username ?: 'Member'),
                    'notification_type' => 'password_recovery',
                ],
            );
        } catch (Throwable) {
            $emailDelivered = false;
        }

        if (! $emailDelivered && filled($user->no_wa)) {
            try {
                $result = $this->whatsappNotificationService->sendMessage(
                    (string) $user->no_wa,
                    $this->whatsappContent($resetUrl),
                );
                $whatsappDelivered = ($result['success'] ?? false) === true;
            } catch (Throwable) {
                $whatsappDelivered = false;
            }
        }

        if (! $emailDelivered && ! $whatsappDelivered) {
            $broker->deleteToken($user);
        }

        $this->logRequest(
            $identifier,
            $user->getKey(),
            $emailDelivered || $whatsappDelivered ? 'delivery_attempted' : 'no_delivery_success',
            $emailDelivered,
            $whatsappDelivered,
        );
    }

    public function resetPassword(string $token, string $email, string $password): bool
    {
        if (User::query()->where('email', $email)->limit(2)->count() !== 1) {
            return false;
        }

        $status = Password::broker('users')->reset(
            [
                'email' => $email,
                'token' => $token,
                'password' => $password,
            ],
            function (User $user, string $newPassword): void {
                DB::transaction(function () use ($user, $newPassword): void {
                    $user->forceFill([
                        'password' => Hash::make($newPassword),
                        'remember_token' => Str::random(100),
                    ])->save();

                    $user->tokens()->delete();
                });
            },
        );

        return $status === PasswordBroker::PASSWORD_RESET;
    }

    private function resetUrl(string $token, string $email): string
    {
        $url = route('password.reset', [
            'token' => $token,
            'email' => $email,
        ]);

        if (app()->environment('production')) {
            return preg_replace('/^http:/i', 'https:', $url) ?? $url;
        }

        return $url;
    }

    private function emailContent(User $user, string $resetUrl): string
    {
        $displayName = e((string) ($user->name ?: $user->username ?: 'Member'));
        $safeUrl = e($resetUrl);
        $expiryMinutes = $this->expiryMinutes();

        return <<<HTML
<p>Halo {$displayName},</p>
<p>Kami menerima permintaan untuk mereset kata sandi akun Anda. Gunakan tautan berikut untuk membuat kata sandi baru:</p>
<p><a href="{$safeUrl}">Reset kata sandi</a></p>
<p>Tautan ini berlaku selama {$expiryMinutes} menit dan hanya dapat digunakan satu kali. Jika Anda tidak meminta reset, abaikan pesan ini.</p>
HTML;
    }

    private function whatsappContent(string $resetUrl): string
    {
        $expiryMinutes = $this->expiryMinutes();

        return "Permintaan reset kata sandi diterima. Gunakan tautan ini untuk membuat kata sandi baru (berlaku {$expiryMinutes} menit, satu kali pakai):\n{$resetUrl}\n\nJika Anda tidak meminta reset, abaikan pesan ini.";
    }

    private function expiryMinutes(): int
    {
        return max(1, (int) config('auth.passwords.users.expire', 60));
    }

    private function identifierFingerprint(string $username): string
    {
        $normalized = mb_strtolower(trim($username));

        return hash_hmac('sha256', $normalized, (string) config('app.key'));
    }

    private function logRequest(string $identifier, ?int $userId, string $reason, bool $emailDelivered = false, bool $whatsappDelivered = false): void
    {
        Log::info('Password recovery request processed.', [
            'correlation_id' => (string) Str::uuid(),
            'identifier_hash' => $identifier,
            'user_id' => $userId,
            'reason' => $reason,
            'email_delivered' => $emailDelivered,
            'whatsapp_delivered' => $whatsappDelivered,
        ]);
    }
}
