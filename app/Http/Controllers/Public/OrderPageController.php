<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Controllers\OrderController as LegacyOrderController;
use App\Models\Kategori;
use App\Services\PublicOrderPageDataService;
use App\Services\PublicSiteConfigService;
use App\Support\PublicThemeRegistry;
use Inertia\Inertia;
use Inertia\Response;

class OrderPageController extends Controller
{
    public function __invoke(
        Kategori $kategori,
        PublicOrderPageDataService $orderPageDataService,
        PublicSiteConfigService $siteConfigService,
        LegacyOrderController $legacyOrderController,
    ): Response|\Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\Foundation\Application {
        $settings = $siteConfigService->getSettings();

        if (($settings->public_theme ?? PublicThemeRegistry::DEFAULT) === PublicThemeRegistry::DEFAULT) {
            return $legacyOrderController->create($kategori);
        }

        if (! $orderPageDataService->isSupportedForInertia($kategori)) {
            return $legacyOrderController->create($kategori);
        }

        $data = $orderPageDataService->getData($kategori);
        $category = $data['category'];

        $rawMetaTitle = trim((string) ($category['metaTitle'] ?? ''));
        $rawMetaDescription = trim((string) ($category['metaDescription'] ?? ''));
        $title = mb_strlen($rawMetaTitle) >= 15
            ? $rawMetaTitle
            : "Top Up {$category['name']} Murah - {$settings->judul_web}";
        $description = mb_strlen($rawMetaDescription) >= 40
            ? $rawMetaDescription
            : "Top up {$category['name']} termurah dan terpercaya di {$settings->judul_web}. Proses instan, layanan 24 jam.";

        return Inertia::render('Public/Order', array_merge($data, [
            'meta' => [
                'title' => $title,
                'description' => $description,
                'keywords' => "topup {$category['name']}, beli {$category['name']}, top up {$category['name']} murah, agen {$category['name']}, {$settings->judul_web}",
                'canonical' => url("/id/{$category['slug']}"),
                'image' => url($category['thumbnail']),
                'schemaMarkup' => $category['schemaMarkup'],
            ],
        ]));
    }
}
