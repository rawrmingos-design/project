<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DocsController extends Controller
{
    /**
     * Display the API Documentation portal.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        Inertia::setRootView('docs');
        return Inertia::render('Docs/Index', [
            'appName' => env('APP_NAME', 'EGYMARKET')
        ]);
    }
}
