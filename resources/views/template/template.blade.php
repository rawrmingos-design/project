<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
   <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- SEO Meta Tags -->
    <meta name="theme-color" content="#575757">
    <meta property="og:title" content="{{ isset($title) ? $title : ($config ? $config->judul_web : '') }}">
    <meta property="og:type" content="website">
    <meta property="og:description" content="{{ isset($meta_description) ? $meta_description : ($config ? $config->deskripsi_web : '') }}">
    @php
        $normalizedCanonicalUrl = \App\Support\CanonicalUrl::normalize($canonical_url ?? url()->current());
    @endphp
    <meta property="og:url" content="{{ $normalizedCanonicalUrl }}">
    <meta property="og:image" content="{{ isset($thumbnail) ? $thumbnail : ($config ? $config->logo_favicon : '') }}">
    <meta name="title" content="{{ isset($title) ? $title : ($config ? $config->judul_web : '') }}">
    <meta name="keywords" content="{{ isset($keywords) ? $keywords : ($config ? $config->keywords : '') }}">
    <meta name="description" content="{{ isset($meta_description) ? $meta_description : ($config ? $config->deskripsi_web : '') }}">
    <meta name="author" content="{{ $config ? $config->judul_web : '' }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="canonical" href="{{ $normalizedCanonicalUrl }}">
    <meta name="google-site-verification" content="YuiRJz7bZ3rDmAJ_fpknZQlWn1p5yGJX_c9Dgfus7Ro" />

    
    <!-- Favicon -->
    <link rel="shortcut icon" href="{{ asset($config ? $config->logo_favicon : 'assets/logo/favicon.webp') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/pwa/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset($config ? $config->logo_favicon : 'assets/logo/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset($config ? $config->logo_favicon : 'assets/logo/favicon-16x16.png') }}">
    <link rel="manifest" href="{{ route('pwa.manifest') }}">
    <meta name="application-name" content="{{ $config ? $config->judul_web : config('app.name') }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="{{ $config ? $config->judul_web : config('app.name') }}">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    
    <!-- Livewire Styles -->
    @livewireStyles
    
    <!-- Title -->
    <title>{{ isset($title) ? $title : ($config ? $config->judul_web : '') }}</title>
    
    <!-- Schema Markup -->
    @if(isset($schema_markup) && $schema_markup)
        {{-- FIX #11: Hanya izinkan konten JSON-LD, strip tag berbahaya --}}
        @php
            // Sanitasi: ekstrak HANYA isi dari <script type="application/ld+json"> ... </script>
            // Jika admin menyimpan script berbahaya, konten di luar JSON-LD block akan diabaikan
            preg_match_all('/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/si', $schema_markup, $matches);
        @endphp
        @if(!empty($matches[1]))
            @foreach($matches[1] as $jsonContent)
                @php
                    // Validasi bahwa isi adalah JSON yang valid sebelum di-render
                    $decoded = json_decode(trim($jsonContent), true);
                @endphp
                @if($decoded !== null)
                    <script type="application/ld+json">{!! json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
                @endif
            @endforeach
        @endif
    @endif

    <!-- Analytics & Tracking -->
    @php
        $googleTagManagerId = '';
        $googleAnalyticsId = '';
        $customGtmHeadScript = '';
        $customGtmBodyNoscript = '';
        $hasCustomGoogleTagManagerSnippet = false;
        $hasValidGoogleTagManagerId = false;
        $hasValidGoogleAnalyticsId = false;
        $shouldLoadDirectGoogleAnalytics = false;
        $gtmTrackingEnabled = false;
    @endphp
    @if(isset($config))
        @php
            $googleTagManagerId = trim((string) ($config->google_tag_manager_id ?? ''));
            $googleAnalyticsId = trim((string) ($config->google_analytics_id ?? ''));
            $facebookPixelId = trim((string) ($config->facebook_pixel_id ?? ''));
            $customGtmHeadScript = trim((string) ($config->gtm_custom_head_script ?? ''));
            $customGtmBodyNoscript = trim((string) ($config->gtm_custom_body_noscript ?? ''));
            $hasCustomGoogleTagManagerSnippet = $customGtmHeadScript !== '';
            $hasValidGoogleTagManagerId = preg_match('/^GTM-[A-Z0-9]+$/i', $googleTagManagerId) === 1;
            $hasValidGoogleAnalyticsId = preg_match('/^(G-|GT-|AW-|UA-)[A-Z0-9\-_]+$/i', $googleAnalyticsId) === 1;
            $hasValidFacebookPixelId = preg_match('/^[0-9]{5,30}$/', $facebookPixelId) === 1;
            $gtmTrackingEnabled = $hasCustomGoogleTagManagerSnippet || $hasValidGoogleTagManagerId;
            $shouldLoadDirectGoogleAnalytics = $hasValidGoogleAnalyticsId && ! $gtmTrackingEnabled;
        @endphp

        @if($hasCustomGoogleTagManagerSnippet)
            <!-- Custom Google Tag Manager -->
            {!! $customGtmHeadScript !!}
        @elseif($hasValidGoogleTagManagerId)
            <!-- Google Tag Manager -->
            <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
            new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
            j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
            'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
            })(window,document,'script','dataLayer','{{ $googleTagManagerId }}');</script>
        @endif

        @if($shouldLoadDirectGoogleAnalytics)
            <!-- Google Analytics 4 -->
            <script async src="https://www.googletagmanager.com/gtag/js?id={{ $googleAnalyticsId }}"></script>
            <script>
                window.dataLayer = window.dataLayer || [];
                function gtag(){dataLayer.push(arguments);}
                gtag('js', new Date());
                gtag('config', '{{ $googleAnalyticsId }}');
            </script>
        @elseif($gtmTrackingEnabled && $hasValidGoogleAnalyticsId)
            <!-- Direct Google Analytics snippet skipped because GTM is active. Configure GA4 inside GTM to avoid duplicate tracking. -->
        @endif

        @if($hasValidFacebookPixelId)
            <!-- Facebook Pixel -->
            <script>
                !function(f,b,e,v,n,t,s)
                {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
                n.callMethod.apply(n,arguments):n.queue.push(arguments)};
                if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
                n.queue=[];t=b.createElement(e);t.async=!0;
                t.src=v;s=b.getElementsByTagName(e)[0];
                s.parentNode.insertBefore(t,s)}(window, document,'script',
                'https://connect.facebook.net/en_US/fbevents.js');
                fbq('init', '{{ $facebookPixelId }}');
                fbq('track', 'PageView');
            </script>
            <noscript><img height="1" width="1" style="display:none"
                src="https://www.facebook.com/tr?id={{ $facebookPixelId }}&ev=PageView&noscript=1"
            /></noscript>
        @endif
    @endif

    <script>
        window.dataLayer = window.dataLayer || [];
        window.gtmTrackingEnabled = @json($gtmTrackingEnabled ?? false);
        window.__trackedTransactions = window.__trackedTransactions || {};

        window.pushDataLayerEvent = function (eventName, payload, options) {
            if (!window.gtmTrackingEnabled || !eventName || !payload || !window.dataLayer) {
                return false;
            }

            const settings = options || {};
            const dedupeKey = settings.dedupeKey || null;

            if (dedupeKey) {
                if (window.__trackedTransactions[dedupeKey]) {
                    return false;
                }

                try {
                    if (window.sessionStorage && window.sessionStorage.getItem('gtm:' + dedupeKey) === '1') {
                        window.__trackedTransactions[dedupeKey] = true;
                        return false;
                    }
                } catch (error) {
                    console.debug('GTM dedupe sessionStorage unavailable:', error);
                }
            }

            if (payload.ecommerce) {
                window.dataLayer.push({ ecommerce: null });
            }

            window.dataLayer.push(Object.assign({ event: eventName }, payload));

            if (dedupeKey) {
                window.__trackedTransactions[dedupeKey] = true;

                try {
                    if (window.sessionStorage) {
                        window.sessionStorage.setItem('gtm:' + dedupeKey, '1');
                    }
                } catch (error) {
                    console.debug('GTM dedupe sessionStorage write skipped:', error);
                }
            }

            return true;
        };
    </script>
    
    @php
        $seasonalEnabled = isset($config) ? (bool) ($config->seasonal_enabled ?? false) : false;
        $seasonalMode = isset($config) ? (string) ($config->seasonal_mode ?? 'manual') : 'manual';
        $seasonalTheme = isset($config) ? (string) ($config->seasonal_theme ?? 'ramadhan') : 'ramadhan';
        $seasonalIntensity = isset($config) ? (string) ($config->seasonal_effect_intensity ?? 'subtle') : 'subtle';
        $seasonalBackgroundImage = isset($config) ? trim((string) ($config->seasonal_background_image ?? '')) : '';
        $seasonalBackgroundImageUrl = null;
        $seasonalBackgroundOpacity = isset($config) ? (int) ($config->seasonal_background_opacity ?? 38) : 38;
        $allowedSeasonalThemes = ['ramadhan', 'halloween'];
        $activeSeasonalTheme = 'default';

        if ($seasonalEnabled && in_array($seasonalTheme, $allowedSeasonalThemes, true)) {
            if ($seasonalMode === 'date_range') {
                $startsAt = ! empty($config?->seasonal_starts_at) ? \Illuminate\Support\Carbon::parse($config->seasonal_starts_at) : null;
                $endsAt = ! empty($config?->seasonal_ends_at) ? \Illuminate\Support\Carbon::parse($config->seasonal_ends_at) : null;
                $now = now();

                if ($startsAt && $endsAt && $now->between($startsAt, $endsAt)) {
                    $activeSeasonalTheme = $seasonalTheme;
                } elseif ($startsAt && ! $endsAt && $now->greaterThanOrEqualTo($startsAt)) {
                    $activeSeasonalTheme = $seasonalTheme;
                } elseif (! $startsAt && $endsAt && $now->lessThanOrEqualTo($endsAt)) {
                    $activeSeasonalTheme = $seasonalTheme;
                } elseif (! $startsAt && ! $endsAt) {
                    $activeSeasonalTheme = $seasonalTheme;
                }
            } else {
                $activeSeasonalTheme = $seasonalTheme;
            }
        }

        if (! in_array($seasonalIntensity, ['subtle', 'normal'], true)) {
            $seasonalIntensity = 'subtle';
        }

        if ($seasonalBackgroundImage !== '') {
            $seasonalBackgroundImageUrl = filter_var($seasonalBackgroundImage, FILTER_VALIDATE_URL)
                ? $seasonalBackgroundImage
                : asset($seasonalBackgroundImage);
        }

        $seasonalBackgroundOpacity = max(5, min(95, $seasonalBackgroundOpacity));
        $seasonalBackgroundOpacityCss = number_format($seasonalBackgroundOpacity / 100, 2, '.', '');
    @endphp

    <!-- Stylesheets and Fonts -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/font-awesome/css/font-awesome.min.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <link rel="stylesheet" href="https://unpkg.com/placeholder-loading/dist/css/placeholder-loading.min.css">
   
     <style> 
           
    
    @import  url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;700&display=swap');
    :root {
        --warna_1: <?= $config->warna1; ?>;
        --warna_2: <?= $config->warna2; ?>;
        --warna_3: <?= $config->warna3; ?>;
        --warna_4: <?= $config->warna4; ?>;
        --gradient-theme: linear-gradient(to top, var(--warna_2) 0%, var(--warna_1) 100%);
    } 

    .bg-weji { 
        --tw-bg-opacity: 1;
        background-color: var(--warna_4);
        --tw-text-opacity: 1;
        color: rgb(255 255 255/var(--tw-text-opacity));
        background-image: url(https://cdn.bangjeff.com/meta/background.png);
        background-repeat: repeat-x, no-repeat; 
        background-position: top; 
        background-size: clamp(20rem, 80em, 100%) auto, cover; 
    } 
        
   .prose :where(ol > li):not(:where([class~=not-prose] *))::marker {
    font-weight: 400;
    color: var(--warna_1) !important;
}
    </style>  
    <link rel="stylesheet" href="{{ asset('/assets/css/pjojikhhoyutyrtd.css') }}">
    <link rel="stylesheet" href="{{ asset('/assets/css/barrsopaosocas.css') }}">
    <link rel="stylesheet" href="{{ asset('/assets/css/owihdagowdhqo.css') }}">
    <link rel="stylesheet" href="{{ asset('/assets/css/seasonal-themes.css') }}">

    @php
        $needsPublicDashboardStyles = request()->routeIs([
            'dashboard',
            'editProfile',
            'riwayat',
            'reload',
            'deposit',
            'affiliate',
            'withdrawal',
        ]);
    @endphp
    @if($needsPublicDashboardStyles)
        @unless(app()->runningUnitTests())
            @vite(['resources/css/public-app.css'])
        @endunless
        <style>
            /* Keep dashboard/settings layout classes, but inherit brand colors from setting_webs */
            .public-dashboard-side__link,
            .public-dashboard-card,
            .public-dashboard-table__shell,
            .public-settings-card {
                background: linear-gradient(145deg, var(--warna_4) 0%, var(--warna_2) 100%);
                border-color: color-mix(in srgb, var(--warna_3) 18%, rgba(255, 255, 255, 0.16));
            }

            .public-dashboard-table thead {
                background: color-mix(in srgb, var(--warna_2) 84%, #000000 16%);
            }

            .public-dashboard-stat--neutral {
                background: color-mix(in srgb, var(--warna_2) 86%, rgba(255, 255, 255, 0.14));
                border-color: color-mix(in srgb, var(--warna_3) 20%, rgba(255, 255, 255, 0.16));
            }

            .public-settings-form input,
            .public-settings-api__value,
            .public-settings-2fa__meta input,
            .public-settings-2fa__disable input {
                background: color-mix(in srgb, var(--warna_4) 88%, #ffffff 12%);
            }

            .public-dashboard-side__link:hover,
            .public-dashboard-side__link:focus-visible {
                border-color: var(--warna_3);
                background: linear-gradient(90deg, var(--warna_3) 0%, transparent 100%);
                background: linear-gradient(90deg, color-mix(in srgb, var(--warna_3) 28%, transparent) 0%, transparent 100%);
            }

            .public-dashboard-side__link.is-active {
                border-color: var(--warna_3);
                background: linear-gradient(90deg, var(--warna_1) 0%, color-mix(in srgb, var(--warna_2) 58%, transparent) 100%);
            }

            .public-dashboard-side__link--logout {
                color: #fca5a5;
            }

            .public-dashboard-alert {
                border-color: color-mix(in srgb, var(--warna_3) 35%, #ffffff 20%);
                background: linear-gradient(90deg, var(--warna_1) 0%, var(--warna_2) 100%);
            }

            .public-dashboard-profile__copy span {
                border-color: var(--warna_3);
                background: transparent;
                border-color: color-mix(in srgb, var(--warna_3) 46%, transparent);
                background: color-mix(in srgb, var(--warna_3) 18%, transparent);
                color: #fff5e6;
            }

            .public-dashboard-credits__amount strong,
            .public-dashboard-table__invoice-link {
                color: var(--warna_3);
            }

            .public-dashboard-button--primary,
            .public-dashboard-stats__switch-btn.is-active,
            .public-settings-form button,
            .public-settings-api__actions button,
            .public-settings-2fa button {
                background: linear-gradient(180deg, var(--warna_1) 0%, var(--warna_2) 100%);
                color: #ffffff;
            }

            .public-dashboard-stats__switch-btn:hover,
            .public-dashboard-stats__switch-btn:focus-visible {
                border-color: var(--warna_3);
            }

            .public-dashboard-table tbody tr:hover td {
                background: transparent;
                background: color-mix(in srgb, var(--warna_3) 12%, transparent);
            }

            .public-settings-form input:focus,
            .public-settings-2fa__meta input:focus,
            .public-settings-2fa__disable input:focus {
                border-color: var(--warna_3);
                box-shadow: 0 0 0 1px var(--warna_3);
            }

            .public-settings-2fa__meta code {
                color: var(--warna_3);
            }
        </style>
    @endif
    


      
    </head>
   
@yield('custom_style')

    <body
        class="bg-gradient-theme text-white antialiased"
        data-season-theme="{{ $activeSeasonalTheme }}"
        data-season-intensity="{{ $seasonalIntensity }}"
        :class="{ 'overflow-hidden': isSearchModalOpen }"
        x-data="{ 'isSearchModalOpen': false }"
        x-on:keydown.escape="isSearchModalOpen=false"
    >
    
    @if(isset($config) && !empty($hasCustomGoogleTagManagerSnippet))
        <!-- Custom Google Tag Manager (noscript) -->
        {!! $customGtmBodyNoscript !!}
    @elseif(isset($config) && !empty($hasValidGoogleTagManagerId))
        <!-- Google Tag Manager (noscript) -->
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $googleTagManagerId }}"
        height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
        <!-- End Google Tag Manager (noscript) -->
    @endif
    <div
        class="seasonal-global-bg"
        aria-hidden="true"
        @if(!empty($seasonalBackgroundImageUrl))
            style="--season-custom-image: url('{{ $seasonalBackgroundImageUrl }}'); --season-custom-opacity: {{ $seasonalBackgroundOpacityCss }};"
        @endif
    ></div>
    <div class="relative z-50" role="dialog" tabindex="-1" x-show="isSearchModalOpen" x-on:click.away="isSearchModalOpen = false" x-cloak x-transition>
        <div class="fixed inset-0 z-50 overflow-hidden p-4 py-20 sm:py-20 sm:px-6 md:p-20">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-25 transition-opacity opacity-100" x-show="isSearchModalOpen" x-cloak x-on:click="isSearchModalOpen=false"></div>
            <div class="mx-auto max-w-2xl transform divide-y divide-gray-500 divide-opacity-10 overflow-hidden rounded-md bg-murky-700 bg-opacity-80 shadow-2xl ring-1 ring-black ring-opacity-5 backdrop-blur backdrop-filter transition-all opacity-100 scale-100"
                id="dialog-panel-:r4g:" data-state="open">
                <div class="relative"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="pointer-events-none absolute left-4 top-3.5 h-5 w-5 text-white text-opacity-40"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd"></path></svg>
                    <form><input class="h-12 w-full border-0 bg-transparent pl-11 pr-4 text-white focus:ring-0 sm:text-sm" placeholder="Cari Game, item, Voucher" id="searchProds" role="combobox" type="text" name="q" aria-expanded="false" aria-autocomplete="list"
                            value="" aria-controls="combobox-options-:r4i:" tabindex="0"></form>
                </div>
                <ul class="resultsearch max-h-80 scroll-py-2 divide-y divide-gray-500 divide-opacity-10 overflow-y-auto">
                    <div class="flex flex-col gap-2 items-center justify-center py-5" id="lottie-container"><span class="text-base text-center opacity-70 py-4">Belum Ada Produk Yang Dicari</span></div>
                </ul>
            </div>
        </div>
    </div>
    <main class="relative" style="z-index:1;">
        <div id="app">
        @yield('content')
    </div>



  </main>

    
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Prefer local lottie-player build when available, fallback to CDN.
        (function () {
            const hasLottieElement = document.querySelector('lottie-player') !== null;
            if (!hasLottieElement) {
                return;
            }

            const hasCustomElements = typeof window.customElements !== 'undefined';
            if (hasCustomElements && window.customElements.get('lottie-player')) {
                return;
            }

            const localSrc = "{{ asset('assets/vendor/lottie/lottie-player.js') }}";
            const cdnSrc = "https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js";

            const loadCdn = function () {
                if (hasCustomElements && window.customElements.get('lottie-player')) {
                    return;
                }

                const cdnScript = document.createElement('script');
                cdnScript.src = cdnSrc;
                cdnScript.defer = true;
                document.head.appendChild(cdnScript);
            };

            const localScript = document.createElement('script');
            localScript.src = localSrc;
            localScript.defer = true;
            localScript.onerror = loadCdn;
            localScript.onload = function () {
                window.setTimeout(function () {
                    if (!(hasCustomElements && window.customElements.get('lottie-player'))) {
                        loadCdn();
                    }
                }, 150);
            };

            document.head.appendChild(localScript);
        })();
    </script>
    {{-- Alpine.js already included by Livewire, no need to load separately --}}
    {{-- <script src="//cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js" defer nonce="YOUR_GENERATED_NONCE"></script> --}}
    {{-- <script src="//cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer nonce="YOUR_GENERATED_NONCE"></script> --}}
    <script>
        $.ajaxSetup({
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content")
            }
        });
    </script>
    <script>
        (function () {
            var endpoint = "{{ url('/id/cari/index') }}";
            var csrfMeta = document.querySelector('meta[name="csrf-token"]');
            var token = csrfMeta ? csrfMeta.getAttribute('content') : '';
            var pairs = [
                {
                    input: document.getElementById('searchProds'),
                    result: document.querySelector('.resultsearch'),
                    emptyState: document.getElementById('lottie-container')
                },
                {
                    input: document.getElementById('searchProdsdekstop'),
                    result: document.querySelector('.resultsearchdekstop'),
                    emptyState: null
                }
            ].filter(function (pair) {
                return pair.input && pair.result;
            });

            if (!pairs.length) {
                return;
            }

            var debounce = function (callback, wait) {
                var timeoutId = 0;
                return function (value) {
                    clearTimeout(timeoutId);
                    timeoutId = setTimeout(function () {
                        callback(value);
                    }, wait);
                };
            };

            pairs.forEach(function (pair) {
                var requestNonce = 0;
                var runSearch = debounce(function (rawValue) {
                    var keyword = String(rawValue || '').trim();
                    var currentNonce = ++requestNonce;

                    if (keyword.length < 2) {
                        pair.result.innerHTML = '';
                        pair.result.classList.remove('show');
                        if (pair.emptyState) {
                            pair.emptyState.style.display = '';
                        }
                        return;
                    }

                    if (pair.emptyState) {
                        pair.emptyState.style.display = 'none';
                    }

                    pair.result.innerHTML = '<li class="p-3 text-sm text-white/70">Mencari produk...</li>';
                    pair.result.classList.add('show');

                    fetch(endpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': token
                        },
                        body: 'data=' + encodeURIComponent(keyword)
                    })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('Search request failed.');
                        }
                        return response.text();
                    })
                    .then(function (html) {
                        if (currentNonce !== requestNonce) {
                            return;
                        }

                        var cleaned = String(html || '').trim();
                        if (!cleaned) {
                            pair.result.innerHTML = '<li class="p-4 text-sm text-white/70">Produk tidak ditemukan.</li>';
                            pair.result.classList.add('show');
                            return;
                        }

                        pair.result.innerHTML = cleaned;
                        pair.result.classList.add('show');
                    })
                    .catch(function () {
                        if (currentNonce !== requestNonce) {
                            return;
                        }
                        pair.result.innerHTML = '';
                        pair.result.classList.remove('show');
                    });
                }, 220);

                pair.input.addEventListener('input', function (event) {
                    runSearch(event.target.value);
                });
            });
        })();
    </script>
    <script src="{{ asset('/assets/js/oo324ddod2323sd2dd.js') }}"></script>

    {{-- Livewire Scripts - Required for Livewire components to work --}}
    @livewireScripts

    @include('template.id.partials.pwa-install-prompt')

    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('/sw.js')
                    .then(function (registration) {
                        registration.update();
                    })
                    .catch(function (error) {
                        console.debug('Service worker registration failed:', error);
                    });
            });
        }
    </script>

     @stack('custom_script')


    </body>
</html>
