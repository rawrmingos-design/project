<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @php
        $props = is_array($page ?? null) ? ($page['props'] ?? []) : [];
        $meta = is_array($props['meta'] ?? null) ? $props['meta'] : [];
        $siteConfig = is_array($props['siteConfig'] ?? null) ? $props['siteConfig'] : [];
        $seoDefaults = is_array($props['seoDefaults'] ?? null) ? $props['seoDefaults'] : [];

        $title = trim((string) ($meta['title'] ?? $seoDefaults['title'] ?? $siteConfig['name'] ?? config('app.name')));
        $description = trim((string) ($meta['description'] ?? $seoDefaults['description'] ?? $siteConfig['description'] ?? ''));
        $keywords = trim((string) ($meta['keywords'] ?? $seoDefaults['keywords'] ?? $siteConfig['keywords'] ?? ''));
        $canonical = trim((string) ($meta['canonical'] ?? $seoDefaults['canonical'] ?? url()->current()));
        $ogTitle = trim((string) ($meta['ogTitle'] ?? $title));
        $ogDescription = trim((string) ($meta['ogDescription'] ?? $description));
        $ogUrl = trim((string) ($meta['ogUrl'] ?? $canonical));
        $robots = trim((string) ($meta['robots'] ?? 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1'));
        $themeColor = trim((string) ($siteConfig['colors']['accent'] ?? '#fb923c'));

        $rawImage = trim((string) ($meta['image'] ?? $seoDefaults['image'] ?? $siteConfig['favicon'] ?? ''));
        if ($rawImage !== '' && !\Illuminate\Support\Str::startsWith($rawImage, ['http://', 'https://', 'data:'])) {
            $rawImage = url('/' . ltrim($rawImage, '/'));
        }
        $ogImage = $rawImage;
        $twitterCard = trim((string) ($meta['twitterCard'] ?? ($ogImage !== '' ? 'summary_large_image' : 'summary')));

        $schemaMarkup = $meta['schemaMarkup'] ?? null;
        $schemaJson = null;

        if (is_array($schemaMarkup)) {
            try {
                $schemaJson = json_encode($schemaMarkup, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            } catch (\Throwable $ignored) {
                $schemaJson = null;
            }
        } elseif (is_string($schemaMarkup)) {
            $candidate = trim($schemaMarkup);
            if ($candidate !== '') {
                json_decode($candidate, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $schemaJson = $candidate;
                }
            }
        }

        if ($schemaJson === null) {
            $siteName = trim((string) ($siteConfig['name'] ?? config('app.name')));
            $siteUrl = url('/id');
            $orgLogo = $ogImage !== '' ? $ogImage : null;
            $sameAs = [];

            foreach (['whatsapp', 'instagram', 'tiktok', 'youtube', 'facebook'] as $socialKey) {
                $socialUrl = trim((string) ($siteConfig['socials'][$socialKey] ?? ''));
                if ($socialUrl !== '' && \Illuminate\Support\Str::startsWith($socialUrl, ['http://', 'https://'])) {
                    $sameAs[] = $socialUrl;
                }
            }

            $fallbackSchema = [
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'WebSite',
                    'name' => $siteName,
                    'url' => $siteUrl,
                    'inLanguage' => app()->getLocale(),
                    'potentialAction' => [
                        '@type' => 'SearchAction',
                        'target' => url('/id/search/products') . '?q={search_term_string}',
                        'query-input' => 'required name=search_term_string',
                    ],
                ],
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'Organization',
                    'name' => $siteName,
                    'url' => $siteUrl,
                    'logo' => $orgLogo,
                    'sameAs' => $sameAs,
                ],
            ];

            try {
                $schemaJson = json_encode($fallbackSchema, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            } catch (\Throwable $ignored) {
                $schemaJson = null;
            }
        }
    @endphp

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="{{ $themeColor }}">
    <meta name="robots" content="{{ $robots }}">

    @if($description !== '')
        <meta name="description" content="{{ $description }}">
    @endif
    @if($keywords !== '')
        <meta name="keywords" content="{{ $keywords }}">
    @endif

    @if($title !== '')
        <title inertia>{{ $title }}</title>
        <meta property="og:title" content="{{ $ogTitle }}">
        <meta name="twitter:title" content="{{ $ogTitle }}">
    @endif
    @if($ogDescription !== '')
        <meta property="og:description" content="{{ $ogDescription }}">
        <meta name="twitter:description" content="{{ $ogDescription }}">
    @endif
    @if($canonical !== '')
        <link rel="canonical" href="{{ $canonical }}">
        <meta property="og:url" content="{{ $ogUrl }}">
    @endif
    @if($ogImage !== '')
        <meta property="og:image" content="{{ $ogImage }}">
        <meta name="twitter:image" content="{{ $ogImage }}">
    @endif
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="{{ $twitterCard }}">

    @if(isset($siteConfig['favicon']) && $siteConfig['favicon'] !== '')
        @php
            $faviconPath = (string) $siteConfig['favicon'];
            if (!\Illuminate\Support\Str::startsWith($faviconPath, ['http://', 'https://', 'data:'])) {
                $faviconPath = url('/' . ltrim($faviconPath, '/'));
            }
        @endphp
        <link rel="icon" href="{{ $faviconPath }}">
        <link rel="shortcut icon" href="{{ $faviconPath }}">
        <meta property="og:site_name" content="{{ $siteConfig['name'] ?? config('app.name') }}">
    @endif

    @if($schemaJson)
        <script type="application/ld+json">{!! $schemaJson !!}</script>
    @endif

    <!-- Inject runtime environment variables for React/Vite -->
    <script>
        window.Laravel = {
            reverb: {
                key: "{{ env('REVERB_APP_KEY', env('PUSHER_APP_KEY', env('VITE_PUSHER_APP_KEY'))) }}",
                host: "{{ env('REVERB_HOST', env('PUSHER_HOST', env('VITE_PUSHER_HOST'))) }}",
                port: {{ env('REVERB_PORT', env('PUSHER_PORT', env('VITE_PUSHER_PORT', 8080))) }},
                scheme: "{{ env('REVERB_SCHEME', env('PUSHER_SCHEME', env('VITE_PUSHER_SCHEME', 'http'))) }}"
            }
        };
    </script>

    @inertiaHead
    @unless(app()->runningUnitTests())
        @viteReactRefresh
        @vite(['resources/css/public-app.css', 'resources/js/public-app.jsx'])
    @endunless
</head>
<body>
    @inertia
</body>
</html>
