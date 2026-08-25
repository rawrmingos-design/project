<?php

namespace App\Services;

use App\Helpers\HtmlSanitizer;
use App\Models\SettingWeb;
use App\Support\PublicThemeRegistry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Throwable;

class PublicSiteConfigService
{
    public function getSettings(): object
    {
        $defaults = [
            'judul_web' => 'Game Top-Up',
            'deskripsi_web' => 'Platform Top-Up Game Terpercaya',
            'keywords' => 'top up, game, diamond, voucher',
            'logo_header' => '/assets/logo/01KGSN7TWDAQXP947X0GH07TDE.gif',
            'logo_footer' => '/assets/logo/01KGSN7TXFTHQYY8T2SM6HQ6S2.png',
            'logo_favicon' => '/assets/logo/favicon.ico',
            'warna1' => '#222222',
            'warna2' => '#d06800',
            'warna3' => '#ffa54a',
            'warna4' => '#ff8040',
            'public_theme' => PublicThemeRegistry::DEFAULT,
            'home_popup_enabled' => true,
            'live_sales_enabled' => true,
            'google_analytics_id' => null,
            'google_tag_manager_id' => null,
            'facebook_pixel_id' => null,
            'gtm_custom_head_script' => null,
            'gtm_custom_body_noscript' => null,
            'url_wa' => null,
            'url_ig' => null,
            'url_tiktok' => null,
            'url_youtube' => null,
            'url_fb' => null,
            'seasonal_theme' => 'ramadhan',
        ];

        $settings = null;

        try {
            if (Schema::hasTable('setting_webs')) {
                $settings = SettingWeb::query()->find(1);
            }
        } catch (Throwable) {
            $settings = null;
        }

        $merged = array_merge($defaults, $settings?->toArray() ?? []);
        $merged['public_theme'] = PublicThemeRegistry::resolveForEnvironment($merged['public_theme'] ?? null);

        return (object) $merged;
    }

    public function sharedProps(): array
    {
        $settings = $this->getSettings();
        $theme = PublicThemeRegistry::resolveForEnvironment($settings->public_theme ?? null);
        $authUser = Auth::user();
        $socials = [
            'whatsapp' => $this->normalizeExternalUrl($settings->url_wa),
            'instagram' => $this->normalizeExternalUrl($settings->url_ig),
            'tiktok' => $this->normalizeExternalUrl($settings->url_tiktok),
            'youtube' => $this->normalizeExternalUrl($settings->url_youtube),
            'facebook' => $this->normalizeExternalUrl($settings->url_fb),
        ];
        $logoHeader = $this->resolveAsset($settings->logo_header, '/assets/logo/favicon.webp');
        $logoFooter = $this->resolveAsset($settings->logo_footer, '/assets/logo/favicon.webp');
        $favicon = $this->resolveAsset($settings->logo_favicon, '/assets/logo/favicon.webp');
        $plainDescription = HtmlSanitizer::toPlainText($settings->deskripsi_web, 180);
        $footerDescriptionHtml = HtmlSanitizer::clean($settings->deskripsi_web);

        return [
            'siteConfig' => [
                'name' => $settings->judul_web,
                'description' => $plainDescription,
                'footerDescriptionHtml' => $footerDescriptionHtml,
                'keywords' => $settings->keywords,
                'logoHeader' => $logoHeader['path'],
                'logoFooter' => $logoFooter['path'],
                'favicon' => $favicon['path'],
                'colors' => [
                    'primary' => $settings->warna1,
                    'secondary' => $settings->warna2,
                    'accent' => $settings->warna3,
                    'highlight' => $settings->warna4,
                ],
                'socials' => $socials,
                'footerColumns' => $this->buildFooterColumns($theme, $socials),
                'assetAudit' => [
                    'logoHeader' => $logoHeader,
                    'logoFooter' => $logoFooter,
                    'favicon' => $favicon,
                ],
                'seasonalTheme' => $settings->seasonal_theme ?? 'ramadhan',
            ],
            'theme' => [
                'key' => $theme,
                'options' => PublicThemeRegistry::options(),
            ],
            'authUser' => $authUser ? [
                'id' => $authUser->id,
                'name' => $authUser->name ?? $authUser->username,
                'username' => $authUser->username,
                'role' => $authUser->role,
                'balance' => (int) ($authUser->balance ?? 0),
                'pointBalance' => (int) ($authUser->point_balance ?? 0),
                'avatar' => $this->resolveUserAvatarUrl(
                    (string) ($authUser->name ?? $authUser->username ?? 'Member'),
                    (string) ($authUser->google_avatar ?? ''),
                ),
                'canShowAffiliate' => ! in_array(
                    strtolower(trim((string) ($authUser->affiliate_status ?? ''))),
                    ['', 'inactive'],
                    true
                ),
            ] : null,
            'featureFlags' => [
                'homePopupEnabled' => (bool) ($settings->home_popup_enabled ?? true),
                'liveSalesEnabled' => (bool) ($settings->live_sales_enabled ?? true),
                'saasTenancyEnabled' => ! (bool) config('tenancy.disabled', true),
                'trackingEnabled' => filled($settings->google_tag_manager_id)
                    || filled($settings->gtm_custom_head_script)
                    || filled($settings->google_analytics_id),
            ],
            'seoDefaults' => [
                'title' => $settings->judul_web,
                'description' => $plainDescription,
                'keywords' => $settings->keywords,
                'canonical' => \App\Support\CanonicalUrl::normalize(url()->current()),
                'image' => $favicon['path'],
            ],
        ];
    }

    public function normalizeAssetPath(?string $path, string $fallback = '/assets/logo/favicon.webp'): string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return $fallback;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return '/' . ltrim($path, '/');
    }

    public function normalizeExternalUrl(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '' || $url === '#') {
            return null;
        }

        if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            return null;
        }

        $host = parse_url($url, PHP_URL_HOST);
        $host = strtolower((string) $host);

        if ($host === '' || $host === 'example.com' || str_ends_with($host, '.example.com')) {
            return null;
        }

        return $url;
    }

    public function docsUrl(): ?string
    {
        $docsDomain = trim((string) env('DOCS_DOMAIN', ''));

        if ($docsDomain === '') {
            return null;
        }

        $url = str_contains($docsDomain, '://') ? $docsDomain : 'https://' . $docsDomain;
        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return null;
        }

        return 'https://' . strtolower($host);
    }

    protected function buildFooterColumns(string $theme, array $socials): array
    {
        $tenancyEnabled = ! (bool) config('tenancy.disabled', true);
        $docsUrl = $this->docsUrl();

        $columns = in_array($theme, ['bangjeff', 'istanatopup'], true)
            ? [
                [
                    'title' => 'Partnership',
                    'items' => [
                        ['label' => 'Join Partnership', 'href' => $socials['whatsapp']],
                        ['label' => 'Reseller Topup', 'href' => '/id/reseller-topup', 'enabled' => $tenancyEnabled],
                        ['label' => 'API Documentation', 'href' => $docsUrl, 'enabled' => $docsUrl !== null],
                    ],
                ],
                [
                    'title' => 'Site Map',
                    'items' => [
                        ['label' => 'Contact Us', 'href' => $socials['whatsapp']],
                        ['label' => 'Reviews', 'href' => '/id/artikel'],
                        ['label' => 'Terms & Conditions', 'href' => '/id/terms-and-condition'],
                        ['label' => 'Privacy Policy', 'href' => '/id/privacy-policy'],
                    ],
                ],
                [
                    'title' => 'Support',
                    'items' => [
                        ['label' => 'WhatsApp', 'href' => $socials['whatsapp']],
                        ['label' => 'Instagram', 'href' => $socials['instagram']],
                        ['label' => 'Facebook', 'href' => $socials['facebook']],
                        ['label' => 'YouTube', 'href' => $socials['youtube']],
                    ],
                ],
            ]
            : [
                [
                    'title' => 'Kemitraan',
                    'items' => [
                        ['label' => 'Gabung Kemitraan', 'href' => $socials['whatsapp']],
                        ['label' => 'Reseller Topup', 'href' => '/id/reseller-topup', 'enabled' => $tenancyEnabled],
                        ['label' => 'Dokumentasi API', 'href' => $docsUrl, 'enabled' => $docsUrl !== null],
                    ],
                ],
                [
                    'title' => 'Peta Situs',
                    'items' => [
                        ['label' => 'Beranda', 'href' => '/id'],
                        ['label' => 'Cek Transaksi', 'href' => '/id/invoices'],
                        ['label' => 'Daftar Harga', 'href' => '/id/price-list'],
                        ['label' => 'Hubungi Kami', 'href' => $socials['whatsapp']],
                    ],
                ],
                [
                    'title' => 'Rekomendasi Topup',
                    'items' => [
                        ['label' => 'Mobile Legends', 'href' => '/id/mobile-legends'],
                        ['label' => 'Honor Of Kings', 'href' => '/id/honor-of-kings'],
                        ['label' => 'Free Fire', 'href' => '/id/free-fire'],
                    ],
                ],
                [
                    'title' => 'Legalitas & Support',
                    'items' => [
                        ['label' => 'WhatsApp', 'href' => $socials['whatsapp']],
                        ['label' => 'Instagram', 'href' => $socials['instagram']],
                        ['label' => 'Kebijakan Pribadi', 'href' => '/id/privacy-policy'],
                        ['label' => 'Syarat & Ketentuan', 'href' => '/id/terms-and-condition'],
                    ],
                ],
            ];

        return collect($columns)
            ->map(function (array $column) {
                $items = collect($column['items'] ?? [])
                    ->filter(fn (array $item) => ($item['enabled'] ?? true) && filled($item['href'] ?? null) && ($item['href'] ?? null) !== '#')
                    ->map(function (array $item) {
                        unset($item['enabled']);

                        return $item;
                    })
                    ->values()
                    ->all();

                return [
                    'title' => $column['title'],
                    'items' => $items,
                ];
            })
            ->filter(fn (array $column) => ! empty($column['items']))
            ->values()
            ->all();
    }

    protected function resolveAsset(?string $path, string $fallback = '/assets/logo/favicon.webp'): array
    {
        $requested = trim((string) $path);
        $usesFallback = $requested === '';
        $resolver = app(PublicUploadUrlService::class);
        $normalized = $this->normalizeAssetPath($requested, $fallback);
        $resolvedUrl = $resolver->url($requested !== '' ? $requested : $fallback, config('uploads.disk', 'assets'), $fallback);
        $exists = $resolver->exists($requested !== '' ? $requested : $fallback, config('uploads.disk', 'assets'));

        return [
            'requested' => $requested !== '' ? $requested : null,
            'path' => $resolvedUrl ?? $normalized,
            'source' => $usesFallback ? 'fallback' : 'db',
            'usesFallback' => $usesFallback,
            'exists' => $exists,
        ];
    }

    protected function resolveUserAvatarUrl(string $displayName, string $googleAvatar): string
    {
        $candidate = trim($googleAvatar);
        if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_URL) !== false) {
            return $candidate;
        }

        return 'https://ui-avatars.com/api/?color=FFFFFF&background=50a7ff&name=' . urlencode($displayName);
    }
}
