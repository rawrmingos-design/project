<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuthenticateApi
{
    
    protected $whitelist = [
        '103.119.230.251',  
        '44.210.80.167',  
        '54.86.50.139',       
        '2a02:4780:6:1234::29',  
        '93.127.220.78',
        '2a02:4780:6:1884:0:33e7:b18d:e',
        '2a02:4780:6:1884:0:33e7:b18d:1',
        '64.227.21.251',
        '2404:6800:4003:c02::8a',
        '2001:db8:3333:4444:5555:6666:1.2.3.4',
        '2407:3640:2242:3226::1',
        '2001:df7:5300:3::232',
        '2401:c080:1400:1129:5400:5ff:fe54:57c1',
        '2401:c080:1400:1129:5400:05ff:fe54:57c1',
    ];

    public function handle(Request $request, Closure $next)
    {
        if (!$request->expectsJson()) {
            $request->headers->set('Accept', 'application/json');
        }

        $clientIp = $request->ip();
        $host = $request->getHost(); 
        $userAgent = $request->header('User-Agent'); 

        // Log::info('Request to REST API', [
        //     'ip' => $clientIp,
        //     'host' => $host,
        //     'user-agent' => $userAgent,
        //     'url' => $request->fullUrl(),
        //     'method' => $request->method(),
        // ]);

        if (!in_array($clientIp, $this->whitelist)) {
            return response()->json([
                'error' => 'Access denied - is not authorized to access this resource.'
            ], 403);
        }

        return $next($request);
    }
}