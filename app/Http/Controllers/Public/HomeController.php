<?php

namespace App\Http\Controllers\Public;

use App\Helpers\HtmlSanitizer;
use App\Http\Controllers\Controller;
use App\Http\Controllers\IndexController as LegacyHomeController;
use App\Services\PublicHomePageDataService;
use App\Services\PublicSiteConfigService;
use App\Support\PublicThemeRegistry;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(
        PublicHomePageDataService $homePageDataService,
        PublicSiteConfigService $siteConfigService,
        LegacyHomeController $legacyHomeController,
    ): Response|\Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\Foundation\Application
    {
        $settings = $siteConfigService->getSettings();

        if (($settings->public_theme ?? PublicThemeRegistry::DEFAULT) === PublicThemeRegistry::DEFAULT) {
            return $legacyHomeController->create();
        }

        $data = $homePageDataService->getData();

        return Inertia::render('Public/Home', array_merge($data, [
            'meta' => [
                'title' => $settings->judul_web,
                'description' => HtmlSanitizer::toPlainText($settings->deskripsi_web, 180),
                'keywords' => $settings->keywords,
                'canonical' => url('/id'),
                'image' => url($siteConfigService->normalizeAssetPath($settings->logo_favicon)),
            ],
        ]));
    }
}
