<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Support\Facades\Route;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        if ($request->expectsJson()) {
            return null;
        }

        $adminDomain = $this->normalizeHost((string) env('FILAMENT_ADMIN_DOMAIN', ''));
        $requestHost = $this->normalizeHost((string) $request->getHost());

        if ($adminDomain !== '' && $requestHost !== '' && strcasecmp($requestHost, $adminDomain) === 0) {
            if (Route::has('filament.admin.auth.login')) {
                return route('filament.admin.auth.login');
            }

            return '/login';
        }

        return route('login');
    }

    private function normalizeHost(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        if (str_contains($value, '://')) {
            $value = (string) (parse_url($value, PHP_URL_HOST) ?? '');
        }

        $value = preg_replace('/:\d+$/', '', $value) ?? '';

        return strtolower(trim($value));
    }
}
