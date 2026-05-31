<?php

namespace App\Http\Controllers\Public\Reseller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SandboxController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        $sandboxIntegration = $user->resellerIntegrations()->where('mode', 'sandbox')->first();

        return Inertia::render('Reseller/Sandbox', [
            'is_sandbox_active' => $sandboxIntegration && $sandboxIntegration->is_active,
        ]);
    }
}
