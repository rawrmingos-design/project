<?php

namespace App\Http\Controllers;

use App\Services\PublicSiteConfigService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class PwaManifestController extends Controller
{
    public function __invoke(PublicSiteConfigService $siteConfigService): JsonResponse
    {
        $settings = $siteConfigService->getSettings();
        $appUrl = rtrim((string) config('app.url'), '/');
        $appName = trim((string) ($settings->judul_web ?? config('app.name', 'Game Top-Up')));
        $description = trim((string) ($settings->deskripsi_web ?? 'Platform Top-Up Game Terpercaya'));
        $themeColor = $this->normalizeHexColor((string) ($settings->warna1 ?? '#575757'), '#575757');
        $backgroundColor = $this->normalizeHexColor((string) ($settings->warna4 ?? '#111111'), '#111111');
        $icon192Url = $this->absoluteUrl($appUrl, '/assets/pwa/icon-192.png');
        $icon512Url = $this->absoluteUrl($appUrl, '/assets/pwa/icon-512.png');
        $maskableIconUrl = $this->absoluteUrl($appUrl, '/assets/pwa/icon-maskable-512.png');

        return response()->json([
            'name' => $appName !== '' ? $appName : 'Game Top-Up',
            'short_name' => Str::limit($appName !== '' ? $appName : 'Game Top-Up', 12, ''),
            'description' => $description !== '' ? $description : 'Platform Top-Up Game Terpercaya',
            'start_url' => '/id?source=pwa',
            'scope' => '/',
            'display' => 'standalone',
            'orientation' => 'portrait-primary',
            'theme_color' => $themeColor,
            'background_color' => $backgroundColor,
            'icons' => [
                [
                    'src' => $icon192Url,
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => $icon512Url,
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => $maskableIconUrl,
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'maskable',
                ],
            ],
        ], 200, [
            'Content-Type' => 'application/manifest+json; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function normalizeHexColor(string $color, string $fallback): string
    {
        $color = trim($color);

        return preg_match('/^#[0-9A-Fa-f]{6}$/', $color) === 1 ? $color : $fallback;
    }

    private function absoluteUrl(string $appUrl, string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return rtrim($appUrl, '/') . '/' . ltrim($path, '/');
    }
}
