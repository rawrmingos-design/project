<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SandboxApiKeyService
{
    public function rotateForUser(User $user): string
    {
        $rawKey = sprintf('sbx_%d_%s', $user->getKey(), Str::random(40));

        $user->forceFill([
            'sandbox_api_key_hash' => Hash::make($rawKey),
            'sandbox_api_key_hint' => $this->buildHint($rawKey),
            'sandbox_api_key_rotated_at' => now(),
            'sandbox_api_key_last_used_at' => null,
        ])->save();

        return $rawKey;
    }

    public function resolveUserFromToken(string $token): ?User
    {
        $token = trim($token);
        $userId = $this->extractUserId($token);

        if ($userId === null) {
            return null;
        }

        $user = User::query()->find($userId);

        if (! $user || blank($user->sandbox_api_key_hash)) {
            return null;
        }

        if (! Hash::check($token, (string) $user->sandbox_api_key_hash)) {
            return null;
        }

        return $user;
    }

    public function buildHint(string $rawKey): string
    {
        $suffix = substr($rawKey, -6);

        return '...' . $suffix;
    }

    private function extractUserId(string $token): ?int
    {
        if (! preg_match('/^sbx_(\d+)_[A-Za-z0-9]+$/', $token, $matches)) {
            return null;
        }

        return isset($matches[1]) ? (int) $matches[1] : null;
    }
}
