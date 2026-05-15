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

        return array_merge(parent::share($request), $siteConfigService->sharedProps());
    }
}
