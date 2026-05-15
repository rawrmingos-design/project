<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Controllers\HitungpointmwController as LegacyMagicWheelController;
use App\Http\Controllers\HitungpointzodiacController as LegacyZodiacController;
use App\Http\Controllers\HitungwrController as LegacyWinRateController;
use App\Services\PublicSiteConfigService;
use App\Support\PublicThemeRegistry;
use Inertia\Inertia;
use Inertia\Response;

class CalculatorPageController extends Controller
{
    public function magicWheel(
        PublicSiteConfigService $siteConfigService,
        LegacyMagicWheelController $legacyMagicWheelController,
    ): Response|\Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\Foundation\Application {
        return $this->renderCalculatorPage(
            'magic-wheel',
            $siteConfigService,
            fn () => $legacyMagicWheelController->create(),
        );
    }

    public function zodiac(
        PublicSiteConfigService $siteConfigService,
        LegacyZodiacController $legacyZodiacController,
    ): Response|\Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\Foundation\Application {
        return $this->renderCalculatorPage(
            'zodiac',
            $siteConfigService,
            fn () => $legacyZodiacController->create(),
        );
    }

    public function winrate(
        PublicSiteConfigService $siteConfigService,
        LegacyWinRateController $legacyWinRateController,
    ): Response|\Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\Foundation\Application {
        return $this->renderCalculatorPage(
            'winrate',
            $siteConfigService,
            fn () => $legacyWinRateController->create(),
        );
    }

    private function renderCalculatorPage(
        string $calculator,
        PublicSiteConfigService $siteConfigService,
        callable $legacyResponder,
    ): Response|\Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\Foundation\Application {
        $settings = $siteConfigService->getSettings();

        if (($settings->public_theme ?? PublicThemeRegistry::DEFAULT) === PublicThemeRegistry::DEFAULT) {
            return $legacyResponder();
        }

        $metaMap = [
            'winrate' => [
                'title' => 'Kalkulator Win Rate',
                'description' => 'Digunakan untuk menghitung total jumlah pertandingan yang harus ditempuh untuk mencapai target win rate yang diinginkan.',
            ],
            'magic-wheel' => [
                'title' => 'Kalkulator Magic Wheel',
                'description' => 'Digunakan untuk mengetahui total maksimal diamond yang dibutuhkan untuk mendapatkan skin Legends.',
            ],
            'zodiac' => [
                'title' => 'Kalkulator Zodiac',
                'description' => 'Digunakan untuk mengetahui total diamond maksimal yang dibutuhkan untuk mendapatkan skin Zodiacs.',
            ],
        ];

        $resolvedCalculator = array_key_exists($calculator, $metaMap) ? $calculator : 'winrate';
        $resolvedMeta = $metaMap[$resolvedCalculator];

        return Inertia::render('Public/Calculator', [
            'calculator' => [
                'type' => $resolvedCalculator,
            ],
            'meta' => [
                'title' => "{$resolvedMeta['title']} - {$settings->judul_web}",
                'description' => $resolvedMeta['description'],
                'keywords' => "kalkulator, {$resolvedCalculator}, top up game, {$settings->judul_web}",
                'canonical' => url('/id/calculator/' . $resolvedCalculator),
                'image' => url($siteConfigService->normalizeAssetPath($settings->logo_favicon)),
            ],
        ]);
    }
}
