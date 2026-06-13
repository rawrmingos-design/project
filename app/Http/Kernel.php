<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    protected $middleware = [
        \App\Http\Middleware\TrustHosts::class,
        \App\Http\Middleware\TrustProxies::class,
        \Illuminate\Http\Middleware\HandleCors::class,
        \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        // \App\Http\Middleware\XSSProtectionMiddleware::class, // Moved to route-specific
    ];

    protected $middlewareGroups = [
        'web' => [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            \App\Http\Middleware\AddExpirationDateToCookie::class,
            \App\Http\Middleware\TrackVisitors::class,
            \App\Http\Middleware\LanguageDetectMiddleware::class,
            \App\Http\Middleware\CaptureTrafficSource::class,
            \App\Http\Middleware\TrackReferral::class,
        ],

        'api' => [
            // \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    protected $routeMiddleware = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'auth.api' => \App\Http\Middleware\ResolveLiveResellerIntegration::class,
        'auth.sandbox.api' => \App\Http\Middleware\ResolveSandboxResellerIntegration::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        'check.role' => \App\Http\Middleware\CheckRole::class,
        'xss' => \App\Http\Middleware\XSSProtectionMiddleware::class,
        'sanitize' => \App\Http\Middleware\SanitizeInput::class,
        'inbound.whitelist' => \App\Http\Middleware\InboundSourceWhitelist::class,
        'bangjeff.legacy.redirect' => \App\Http\Middleware\RedirectLegacyBladeWhenBangjeff::class,
        'affiliate.only' => \App\Http\Middleware\EnsureAffiliateUser::class,
        'non-affiliate.only' => \App\Http\Middleware\EnsureNonAffiliateUser::class,
        'non-admin.public-dashboard' => \App\Http\Middleware\EnsureNonAdminPublicDashboard::class,
        'reseller.only'     => \App\Http\Middleware\EnsureIsReseller::class,
        'reseller.redirect' => \App\Http\Middleware\RedirectResellerToDedicatedHub::class,
        'not-reseller'      => \App\Http\Middleware\EnsureNotReseller::class,
        'reseller.ip.enforce' => \App\Http\Middleware\EnforceResellerIpWhitelist::class,
        'add.api.version'   => \App\Http\Middleware\AddApiVersionHeader::class,
        'auth.message'      => \App\Http\Middleware\RequireAuthWithMessage::class,
    ];
}
