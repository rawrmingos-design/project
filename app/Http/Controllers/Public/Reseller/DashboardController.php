<?php

namespace App\Http\Controllers\Public\Reseller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Reseller/Dashboard', [
            'user' => [
                'name' => $request->user()->name,
                'email' => $request->user()->email,
            ],
            // More props to be added in Commit 2
        ]);
    }
}
