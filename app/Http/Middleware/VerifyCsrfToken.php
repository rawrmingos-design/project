<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyCsrfToken extends \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'callback',
        'callbackpl',
        'payload',
        'callback/*',

        // External provider/payment callbacks. These requests do not carry a
        // browser CSRF token; security is handled by provider signatures,
        // inbound IP whitelist rules, and idempotency checks.
        'wejizy/digi/payload',
        'wejizy/digi/*',
        'wejizy/vip/callback',
        'wejizy/apigames/callback',
        'wejizy/tokopay/callback',
        'wejizy/tripay/callback',
        'wejizy/paydisini/callback',
        'wejizy/duitku/callback',
        'api/webhooks/*',
    ];
    
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        $response->headers->set('Set-Cookie', 'SameSite=None; Secure');

        return $response;
    }
}
