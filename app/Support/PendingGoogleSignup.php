<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Google sign-up is a two-step flow.
 *
 * Google only returns name/email/picture — never a phone number — but `users.no_wa`
 * is NOT NULL and is the buyer identity used by the order API (`telp`/`no_pembeli`)
 * and every WhatsApp flow. So a brand new Google account cannot be persisted
 * immediately: the verified Google profile is parked in the session until the
 * visitor supplies a WhatsApp number, mirroring the required `no_wa` field on the
 * normal sign-up form.
 */
final class PendingGoogleSignup
{
    public const SESSION_KEY = 'auth.google.pending';

    /**
     * @param  array{sub: string, email: string, name: string, picture: string, google_id_column: bool, google_avatar_column: bool, redirect: string|null}  $payload
     */
    public static function put(Request $request, array $payload): void
    {
        $request->session()->put(self::SESSION_KEY, $payload);
    }

    /**
     * @return array{sub: string, email: string, name: string, picture: string, google_id_column: bool, google_avatar_column: bool, redirect: string|null}|null
     */
    public static function get(Request $request): ?array
    {
        $pending = $request->session()->get(self::SESSION_KEY);

        if (! is_array($pending) || blank($pending['sub'] ?? null) || blank($pending['email'] ?? null)) {
            return null;
        }

        return $pending;
    }

    public static function forget(Request $request): void
    {
        $request->session()->forget(self::SESSION_KEY);
    }
}
