<?php

namespace App\Http\Controllers\Public\Reseller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DocsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        $user->load('resellerIntegrations.callbackProfile');
        $liveIntegration = $user->resellerIntegrations->where('mode', 'live')->first();
        $sandboxIntegration = $user->resellerIntegrations->where('mode', 'sandbox')->first();

        return Inertia::render('Reseller/ApiDocs', [
            'canonical_url' => route('docs'),
            'live_base_url' => url('/api/v1'),
            'sandbox_base_url' => url('/api/v1/sandbox'),
            'live' => $liveIntegration ? [
                'integration_code' => $liveIntegration->integration_code,
                'api_key_hint' => $user->api_key_hint,
            ] : null,
            'sandbox' => $sandboxIntegration ? [
                'integration_code' => $sandboxIntegration->integration_code,
                'api_key_hint' => $user->sandbox_api_key_hint,
            ] : null,
        ]);
    }
}
