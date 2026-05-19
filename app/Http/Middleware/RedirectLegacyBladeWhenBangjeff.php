<?php

namespace App\Http\Middleware;

use App\Models\SettingWeb;
use App\Support\PublicThemeRegistry;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RedirectLegacyBladeWhenBangjeff
{
    private const THEME_CACHE_KEY = 'public:active-theme';
    private const THEME_CACHE_TTL_SECONDS = 30;

    public function handle(Request $request, Closure $next): Response
    {
        $adminDomain = $this->normalizeHost((string) env('FILAMENT_ADMIN_DOMAIN', ''));
        $requestHost = $this->normalizeHost((string) $request->getHost());

        if ($adminDomain !== '' && $requestHost !== '' && $requestHost === $adminDomain) {
            return $next($request);
        }

        if ($this->resolveActiveTheme() !== PublicThemeRegistry::BANGJEFF) {
            return $next($request);
        }

        if (! $this->isEligibleForStrictRedirect($request)) {
            return $next($request);
        }

        if ($this->isAllowedException($request)) {
            return $next($request);
        }

        if ($this->isBangjeffInertiaRoute($request)) {
            return $next($request);
        }

        return redirect('/id', 301);
    }

    private function isEligibleForStrictRedirect(Request $request): bool
    {
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return false;
        }

        return $request->is('id') || $request->is('id/*');
    }

    private function isAllowedException(Request $request): bool
    {
        return $request->is('id/sign-in')
            || $request->is('id/sign-up');
    }

    private function isBangjeffInertiaRoute(Request $request): bool
    {
        $actionName = (string) ($request->route()?->getActionName() ?? '');
        $controllerClass = Str::before($actionName, '@');

        if ($controllerClass === '' || $controllerClass === 'Closure') {
            return false;
        }

        return Str::startsWith($controllerClass, 'App\\Http\\Controllers\\Public\\');
    }

    private function resolveActiveTheme(): string
    {
        $theme = Cache::remember(
            self::THEME_CACHE_KEY,
            now()->addSeconds(self::THEME_CACHE_TTL_SECONDS),
            fn (): string => (string) (SettingWeb::query()->select('public_theme')->find(1)?->public_theme ?? PublicThemeRegistry::DEFAULT)
        );

        return PublicThemeRegistry::normalize($theme);
    }

    private function normalizeHost(string $host): string
    {
        $normalized = trim(strtolower($host));

        if ($normalized === '') {
            return '';
        }

        if (str_contains($normalized, '://')) {
            $normalized = (string) (parse_url($normalized, PHP_URL_HOST) ?? '');
        }

        if ($normalized === '') {
            return '';
        }

        return (string) (preg_replace('/:\d+$/', '', $normalized) ?? $normalized);
    }
}
