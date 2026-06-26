<?php

namespace App\Http\Controllers\Public\Reseller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(Request $request): Response
    {
        $user = Auth::user();

        return Inertia::render('Reseller/Settings', [
            'settingsPage' => [
                'profile' => [
                    'name' => (string) ($user->name ?? ''),
                    'username' => (string) ($user->username ?? ''),
                    'email' => (string) ($user->email ?? ''),
                    'phone' => (string) ($user->no_wa ?? ''),
                ],
                'twoFactor' => [
                    'enabled' => filled($user->two_factor_secret),
                ],
                'push' => [
                    'enabled' => $user->resellerPushSubscriptions()->exists(),
                    'subscriptionCount' => $user->resellerPushSubscriptions()->count(),
                    'vapidPublicKey' => app(\App\Services\ResellerWebPushService::class)->vapidPublicKey(),
                    'configured' => app(\App\Services\ResellerWebPushService::class)->isConfigured(),
                    'settingsUrl' => route('reseller.settings'),
                ],
                'flash' => [
                    'success' => session('success'),
                    'error' => session('error'),
                ],
            ],
            'meta' => [
                'title' => 'Settings - Reseller Hub',
            ],
        ]);
    }
}
