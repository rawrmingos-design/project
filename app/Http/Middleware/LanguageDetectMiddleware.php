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
        $acceptLanguage = $request->server('HTTP_ACCEPT_LANGUAGE');
        $language = $acceptLanguage ? substr($acceptLanguage, 0, 2) : 'en';

        $availableLanguages = ['en', 'id'];
        if (in_array($language, $availableLanguages)) {
            App::setLocale($language);
        } else {
            App::setLocale('en'); 
        }

        return $next($request);
    }
}
