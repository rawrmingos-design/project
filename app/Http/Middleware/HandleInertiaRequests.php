<?php

namespace App\Http\Middleware;

use App\Services\PublicSiteConfigService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app-inertia';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        /** @var PublicSiteConfigService $siteConfigService */
        $siteConfigService = app(PublicSiteConfigService::class);
        $user = $request->user();

        return array_merge(
            parent::share($request),
            $siteConfigService->sharedProps(),
            [
                // Override authUser with twoFactorEnabled so ALL reseller pages
                // can read this without each controller having to pass it manually.
                'authUser' => $user ? [
                    'id'               => $user->id,
                    'name'             => $user->name,
                    'email'            => $user->email,
                    'twoFactorEnabled' => filled($user->two_factor_secret),
                ] : null,

                // Flash messages — shared automatically from session.
                // Controllers use redirect()->with('success', '...') etc.
                // Pages read from usePage().props.flash in React.
                'flash' => [
                    'success' => $request->session()->get('success') ?? $request->session()->get('flash_success'),
                    'error'   => $request->session()->get('error') ?? $request->session()->get('flash_error'),
                    'info'    => $request->session()->get('info') ?? $request->session()->get('flash_info'),
                    'new_webhook_secret' => $request->session()->get('new_webhook_secret'),
                    'webhook_mode' => $request->session()->get('webhook_mode'),
                ],
            ]
        );
    }
}
