<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class TrackVisitors
{
    public function handle(Request $request, Closure $next)
    {
        $date = Carbon::today()->toDateString();
        $visitors = Cache::get('visitors', []);
        
        $visitorIdentifier = $request->ip() . '_' . $request->userAgent();

        if (!isset($visitors[$date])) {
            $visitors[$date] = [];
        } else if (!is_array($visitors[$date])) {
            $visitors[$date] = [];
        }

        if (!$request->hasCookie('visited_' . $date)) {
            if (!in_array($visitorIdentifier, $visitors[$date])) {
                $visitors[$date][] = $visitorIdentifier;
            }

            Cache::put('visitors', $visitors, now()->addDays(1));

            $cookie = cookie('visited_' . $date, true, 1440); 
            return $next($request)->cookie($cookie);
        }

        return $next($request);
    }
}
