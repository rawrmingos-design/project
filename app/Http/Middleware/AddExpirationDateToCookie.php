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
        
        try {
            if ($response instanceof Response) {
                $expirationDate = Carbon::now()->addMinutes(60);
                $expirationTimestamp = $expirationDate->getTimestamp();
                $response->withCookie(cookie('session_expire', $expirationTimestamp, 60));
            }
        } catch (\Exception $e) {
            // Log error but don't break the request
            \Log::error('AddExpirationDateToCookie error: ' . $e->getMessage());
        }
        
        return $response;
    }
}
