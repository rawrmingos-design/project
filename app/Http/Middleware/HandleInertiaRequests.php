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

        $sharedProps = array_merge(
            parent::share($request),
            $siteConfigService->sharedProps(),
            [
                // Flash messages
                'flash' => [
                    'success' => $request->session()->get('success') ?? $request->session()->get('flash_success'),
                    'error'   => $request->session()->get('error') ?? $request->session()->get('flash_error'),
                    'info'    => $request->session()->get('info') ?? $request->session()->get('flash_info'),
                    'new_webhook_secret' => $request->session()->get('new_webhook_secret'),
                    'webhook_mode' => $request->session()->get('webhook_mode'),
                ],
            ]
        );

        if ($user && isset($sharedProps['authUser'])) {
            $sharedProps['authUser']['twoFactorEnabled'] = filled($user->two_factor_secret);
            // Ensure email is also available for reseller pages
            $sharedProps['authUser']['email'] = $user->email;
        }

        return $sharedProps;
    }
}
