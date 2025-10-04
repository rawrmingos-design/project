<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class LanguageDetectMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $language = substr($request->server('HTTP_ACCEPT_LANGUAGE'), 0, 2);

        $availableLanguages = ['en', 'id'];
        if (in_array($language, $availableLanguages)) {
            App::setLocale($language);
        } else {
            App::setLocale('en'); 
        }

        return $next($request);
    }
}
