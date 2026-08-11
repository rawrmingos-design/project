<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

trait HandlesLoginRedirect
{
    private const LOGIN_REDIRECT_SESSION_KEY = 'auth.login.redirect';

    protected function rememberLoginRedirect(Request $request): void
    {
        if (! $request->query->has('redirect')) {
            return;
        }

        $redirect = $this->safeLoginRedirect($request->query('redirect'));

        if ($redirect === null) {
            $request->session()->forget(self::LOGIN_REDIRECT_SESSION_KEY);
            return;
        }

        $request->session()->put(self::LOGIN_REDIRECT_SESSION_KEY, $redirect);
    }

    protected function redirectAfterLogin(Request $request): RedirectResponse
    {
        $redirect = $this->safeLoginRedirect(
            $request->session()->pull(self::LOGIN_REDIRECT_SESSION_KEY)
        );

        return redirect()->to($redirect ?? route('dashboard'));
    }

    private function safeLoginRedirect(mixed $redirect): ?string
    {
        if (! is_string($redirect) || $redirect === '') {
            return null;
        }

        if ($redirect !== trim($redirect) || str_contains($redirect, '\\')) {
            return null;
        }

        if (preg_match('/[\x00-\x1F\x7F]/', $redirect) === 1) {
            return null;
        }

        if (str_contains($redirect, '#')) {
            return null;
        }

        if (! str_starts_with($redirect, '/') || str_starts_with($redirect, '//')) {
            return null;
        }

        $parts = parse_url($redirect);
        if ($parts === false || isset($parts['scheme'], $parts['host'], $parts['user'], $parts['pass'], $parts['port'])) {
            return null;
        }

        return $redirect;
    }
}
