<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;

/**
 * Serves /.well-known/assetlinks.json for Android TWA (Digital Asset Links).
 *
 * The statement file lives in public/.well-known/ for direct nginx serving,
 * but some environments (PHP built-in server, Laravel test client) route
 * dot-paths through Laravel — so an explicit route is required to keep the
 * statement reachable everywhere.
 */
class DigitalAssetLinksController extends Controller
{
    public function __invoke(): Response
    {
        $path = public_path('.well-known/assetlinks.json');

        if (! File::exists($path)) {
            abort(404);
        }

        return response(File::get($path), 200)
            ->header('Content-Type', 'application/json');
    }
}
