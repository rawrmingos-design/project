<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class AddExpirationDateToCookie
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        if ($response instanceof Response) {
            $expirationDate = Carbon::now()->addMinutes(60);
            $expirationTimestamp = $expirationDate->getTimestamp();
            $response->withCookie(cookie('name', 'value', $expirationTimestamp));
        }
        return $response;
    }
}
