<!DOCTYPE html>
<html lang="id">
<head>
    @php
        $props = is_array($page ?? null) ? ($page['props'] ?? []) : [];
        $meta = is_array($props['meta'] ?? null) ? $props['meta'] : [];
        $siteConfig = is_array($props['siteConfig'] ?? null) ? $props['siteConfig'] : [];
        $seoDefaults = is_array($props['seoDefaults'] ?? null) ? $props['seoDefaults'] : [];

        // Bila SSR benar-benar merender request ini, React yang menyumbang
        // seluruh tag SEO (lewat <Head>); merender keduanya membuat tag
        // duplikat di <head>. Hasil dispatch di-cache oleh SsrState, jadi
        // @inertiaHead / @inertia di bawah memakai respons yang sama.
        // Pengecekan memakai path bundle yang dipatok di config/inertia.php
        // supaya public/js/app.js tidak pernah dianggap bundle SSR.
        $ssrBundlePath = config('inertia.ssr.bundle');
        $ssrBundleExists = is_string($ssrBundlePath) && $ssrBundlePath !== '' && file_exists($ssrBundlePath);
        $ssrActive = $ssrBundleExists
            && app(\Inertia\Ssr\SsrState::class)->setPage($page ?? [])->dispatch() !== null;

        $title = trim((string) ($meta['title'] ?? $seoDefaults['title'] ?? $siteConfig['name'] ?? config('app.name')));
        $description = trim((string) ($meta['description'] ?? $seoDefaults['description'] ?? $siteConfig['description'] ?? ''));
        $keywords = trim((string) ($meta['keywords'] ?? $seoDefaults['keywords'] ?? $siteConfig['keywords'] ?? ''));
        $canonical = \App\Support\CanonicalUrl::normalize($meta['canonical'] ?? $seoDefaults['canonical'] ?? url()->current());
        $ogTitle = trim((string) ($meta['ogTitle'] ?? $title));
        $ogDescription = trim((string) ($meta['ogDescription'] ?? $description));
        $ogUrl = \App\Support\CanonicalUrl::normalize($meta['ogUrl'] ?? $canonical);
        $robots = trim((string) ($meta['robots'] ?? $seoDefaults['robots'] ?? \App\Support\SeoRoutePolicy::robots()));
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

        if ($schemaJson === null && !\Illuminate\Support\Str::startsWith($robots, 'noindex')) {
            $siteName = trim((string) ($siteConfig['name'] ?? config('app.name')));
            $siteUrl = \App\Support\CanonicalUrl::normalize(url('/id'));
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
                        'target' => \App\Support\CanonicalUrl::normalize(url('/id/search/products')) . '?q={search_term_string}',
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
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="application-name" content="{{ $siteConfig['appName'] ?? $siteConfig['name'] ?? config('app.name') }}">
    <meta name="apple-mobile-web-app-title" content="{{ $siteConfig['name'] ?? config('app.name') }}">
    <link rel="manifest" href="{{ route('pwa.manifest') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/pwa/apple-touch-icon.png') }}">
    <meta name="format-detection" content="telephone=no">

    {{-- Tag SEO di bawah ini juga dirender React lewat <Head> saat SSR aktif,
         jadi hanya dirender di sini ketika SSR tidak aktif (fallback). --}}
    @unless($ssrActive)
        <meta data-inertia="theme-color" name="theme-color" content="{{ $themeColor }}">
        <meta data-inertia="robots" name="robots" content="{{ $robots }}">

        @if($description !== '')
            <meta data-inertia="description" name="description" content="{{ $description }}">
        @endif
        @if($keywords !== '')
            <meta data-inertia="keywords" name="keywords" content="{{ $keywords }}">
        @endif

        @if($title !== '')
            <title inertia data-inertia="title">{{ $title }}</title>
            <meta data-inertia="og:title" property="og:title" content="{{ $ogTitle }}">
            <meta data-inertia="twitter:title" name="twitter:title" content="{{ $ogTitle }}">
        @endif
        @if($ogDescription !== '')
            <meta data-inertia="og:description" property="og:description" content="{{ $ogDescription }}">
            <meta data-inertia="twitter:description" name="twitter:description" content="{{ $ogDescription }}">
        @endif
        @if($canonical !== '')
            <link data-inertia="canonical" rel="canonical" href="{{ $canonical }}">
            <meta data-inertia="og:url" property="og:url" content="{{ $ogUrl }}">
        @endif
        @if($ogImage !== '')
            <meta data-inertia="og:image" property="og:image" content="{{ $ogImage }}">
            <meta data-inertia="twitter:image" name="twitter:image" content="{{ $ogImage }}">
        @endif
        <meta data-inertia="og:type" property="og:type" content="website">
        <meta data-inertia="twitter:card" name="twitter:card" content="{{ $twitterCard }}">

        @if(isset($siteConfig['favicon']) && $siteConfig['favicon'] !== '')
            <meta data-inertia="og:site_name" property="og:site_name" content="{{ $siteConfig['name'] ?? config('app.name') }}">
        @endif

        @if($schemaJson)
            <script data-inertia="json-ld" type="application/ld+json">{!! $schemaJson !!}</script>
        @endif
    @endunless

    @if(isset($siteConfig['favicon']) && $siteConfig['favicon'] !== '')
        @php
            $faviconPath = (string) $siteConfig['favicon'];
            if (!\Illuminate\Support\Str::startsWith($faviconPath, ['http://', 'https://', 'data:'])) {
                $faviconPath = url('/' . ltrim($faviconPath, '/'));
            }
        @endphp
        <link rel="icon" href="{{ $faviconPath }}">
        <link rel="shortcut icon" href="{{ $faviconPath }}">
    @endif

    <!-- Inject runtime environment variables for React/Vite -->
    @php
        $broadcaster = config('broadcasting.default');
        $broadcastConfig = config("broadcasting.connections.{$broadcaster}");
        $reverbKey = $broadcastConfig['key'] ?? null;
        $reverbHost = $broadcastConfig['options']['host'] ?? null;
        $reverbPort = $broadcastConfig['options']['port'] ?? 8080;
        $reverbScheme = $broadcastConfig['options']['scheme'] ?? 'http';
    @endphp
    <script>
        window.Laravel = {
            reverb: {
                key: "{{ $reverbKey }}",
                host: "{{ $reverbHost }}",
                port: {{ $reverbPort }},
                scheme: "{{ $reverbScheme }}"
            }
        };
    </script>

    {{-- Tracking bootstrap: GTM / GA / Meta Pixel / pushDataLayerEvent --}}
    @php
        $inertiaTrackingSettings = app(\App\Services\PublicSiteConfigService::class)->getSettings();
    @endphp
    @include('partials.tracking-bootstrap', ['trackingSettings' => $inertiaTrackingSettings])

    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800;900&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">

    @inertiaHead
    @unless(app()->runningUnitTests())
        @php
            // CSS harus di-link eksplisit: kalau hanya di-import dari JS, HTML SSR
            // sempat dirender tanpa stylesheet sama sekali (FOUC / halaman terlihat
            // rusak sesaat). Theme istana punya file scoped sendiri; theme lain
            // (mis. bangjeff) sudah termasuk di public-app.css.
            $activeThemeKey = \App\Support\PublicThemeRegistry::resolveForEnvironment(
                $props['theme']['key'] ?? null
            );
            $viteEntries = ['resources/css/public-app.css'];
            if ($activeThemeKey === \App\Support\PublicThemeRegistry::ISTANATOPUP) {
                $viteEntries[] = 'resources/css/public-theme-istanatopup.css';
            }
            $viteEntries[] = 'resources/js/public-app.jsx';
        @endphp
        @viteReactRefresh
        @vite($viteEntries)
    @endunless
</head>
<body>
    @include('partials.tracking-bootstrap', [
        'trackingSettings' => $inertiaTrackingSettings,
        'trackingPlacement' => 'body',
    ])

    {{-- Splash anti-FOUC: HTML SSR bisa tampil sebelum CSS/JS selesai dimuat
         sehingga halaman sempat terlihat rusak. Overlay ini dirender inline
         (tanpa dependency) dan disembunyikan segera setelah React siap. --}}
    @php
        $splashName = trim((string) ($siteConfig['name'] ?? config('app.name', 'Game Top-Up')));
        $splashLogo = trim((string) ($siteConfig['logoHeader'] ?? ''));
        if ($splashLogo !== '' && !\Illuminate\Support\Str::startsWith($splashLogo, ['http://', 'https://', 'data:'])) {
            $splashLogo = url('/' . ltrim($splashLogo, '/'));
        }
        $splashPrimary = trim((string) ($siteConfig['colors']['primary'] ?? '#222222'));
        $splashAccent = trim((string) ($siteConfig['colors']['accent'] ?? '#ffa54a'));
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $splashPrimary)) { $splashPrimary = '#222222'; }
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $splashAccent)) { $splashAccent = '#ffa54a'; }
    @endphp
    <style>
        #ist-boot-splash {
            position: fixed;
            inset: 0;
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            background: {{ $splashPrimary }};
            transition: opacity .28s ease;
        }
        #ist-boot-splash[hidden] { display: none; }
        #ist-boot-splash.is-hiding { opacity: 0; pointer-events: none; }
        #ist-boot-splash .ist-boot-splash__inner {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 18px;
            padding: 0 24px;
            text-align: center;
        }
        #ist-boot-splash img {
            width: 96px;
            height: 96px;
            object-fit: contain;
            animation: istBootPulse 1.6s ease-in-out infinite;
        }
        #ist-boot-splash .ist-boot-splash__bar {
            position: relative;
            width: min(220px, 62vw);
            height: 4px;
            overflow: hidden;
            border-radius: 9999px;
            background: rgba(255, 255, 255, .14);
        }
        #ist-boot-splash .ist-boot-splash__bar::after {
            content: '';
            position: absolute;
            inset: 0;
            transform: translateX(-100%);
            background: linear-gradient(90deg, transparent, {{ $splashAccent }}, transparent);
            animation: istBootSweep 1.15s cubic-bezier(.22, 1, .36, 1) infinite;
        }
        #ist-boot-splash .ist-boot-splash__title {
            margin: 0;
            font: 600 15px/1.4 system-ui, -apple-system, "Segoe UI", sans-serif;
            color: #fff;
            letter-spacing: .01em;
        }
        @keyframes istBootPulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.06); opacity: .88; }
        }
        @keyframes istBootSweep {
            100% { transform: translateX(100%); }
        }
        @media (prefers-reduced-motion: reduce) {
            #ist-boot-splash img,
            #ist-boot-splash .ist-boot-splash__bar::after { animation: none; }
        }
    </style>
    <div id="ist-boot-splash" role="status" aria-live="polite" aria-label="Memuat halaman">
        <div class="ist-boot-splash__inner">
            @if($splashLogo !== '')
                <img src="{{ $splashLogo }}" alt="{{ $splashName }}" width="96" height="96" decoding="async">
            @endif
            <div class="ist-boot-splash__bar" aria-hidden="true"></div>
            <p class="ist-boot-splash__title">{{ $splashName !== '' ? $splashName : 'Memuat…' }}</p>
        </div>
    </div>
    <script>
        (function () {
            // Sembunyikan splash setelah React selesai hidrasi. Batas waktu
            // menjaga agar splash tidak pernah menutupi UI kalau inisialisasi
            // React gagal (mis. error bundle) — halaman tetap bisa dipakai.
            var splash = document.getElementById('ist-boot-splash');
            if (!splash) return;

            var hidden = false;
            function hideBootSplash() {
                if (hidden) return;
                hidden = true;
                splash.classList.add('is-hiding');
                window.setTimeout(function () { splash.hidden = true; }, 320);
            }

            function revealWhenReady() {
                // Skrip ini di-parse sebelum direktif inertia, jadi #app baru
                // ada di DOM setelah dokumen selesai diparse.
                var app = document.getElementById('app');
                if (!app || typeof MutationObserver === 'undefined') {
                    window.setTimeout(hideBootSplash, 1500);
                    return;
                }

                var observer = new MutationObserver(function () {
                    if (app.children.length > 0) {
                        observer.disconnect();
                        requestAnimationFrame(function () { requestAnimationFrame(hideBootSplash); });
                    }
                });

                // Konten SSR sudah ada di #app -> tampilkan halaman segera.
                if (app.children.length > 0) {
                    requestAnimationFrame(function () { requestAnimationFrame(hideBootSplash); });
                    return;
                }

                observer.observe(app, { childList: true });
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', revealWhenReady);
            } else {
                revealWhenReady();
            }

            // Jaring pengaman: splash tidak boleh menutupi UI selamanya.
            window.setTimeout(hideBootSplash, 6000);
        })();
    </script>

    @inertia
    <script>
        // Register the PWA service worker so the footer install button can trigger beforeinstallprompt.
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('/sw.js?v=3').catch(function (error) {
                    console.debug('Service worker registration failed:', error);
                });
            });
        }
    </script>
</body>
</html>
