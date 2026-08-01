{{--
    Shared GTM, GA, Meta Pixel, and data-layer bootstrap for public shells.

    Required: $trackingSettings object/stdClass from SettingWeb.
    Optional: $trackingPlacement = 'head' (default) or 'body'.

    Precedence:
    - trusted custom GTM head snippet > validated GTM container
    - any GTM > direct GA, preventing duplicate GA page views
    - validated Meta Pixel is independent
--}}
@php
    $trackingPlacement = $trackingPlacement ?? 'head';
    $trackingGoogleTagManagerId = trim((string) ($trackingSettings->google_tag_manager_id ?? ''));
    $trackingGoogleAnalyticsId = trim((string) ($trackingSettings->google_analytics_id ?? ''));
    $trackingFacebookPixelId = trim((string) ($trackingSettings->facebook_pixel_id ?? ''));
    $trackingCustomGtmHeadScript = trim((string) ($trackingSettings->gtm_custom_head_script ?? ''));
    $trackingCustomGtmBodyNoscript = trim((string) ($trackingSettings->gtm_custom_body_noscript ?? ''));

    $trackingHasCustomGtmSnippet = $trackingCustomGtmHeadScript !== '';
    $trackingHasValidGtmId = preg_match('/^GTM-[A-Z0-9]+$/i', $trackingGoogleTagManagerId) === 1;
    $trackingHasValidGaId = preg_match('/^(G-|GT-|AW-|UA-)[A-Z0-9\-_]+$/i', $trackingGoogleAnalyticsId) === 1;
    $trackingHasValidPixelId = preg_match('/^[0-9]{5,30}$/', $trackingFacebookPixelId) === 1;
    $trackingGtmEnabled = $trackingHasCustomGtmSnippet || $trackingHasValidGtmId;
    $trackingShouldLoadDirectGa = $trackingHasValidGaId && ! $trackingGtmEnabled;
@endphp

@if($trackingPlacement === 'body')
    @if($trackingHasCustomGtmSnippet && $trackingCustomGtmBodyNoscript !== '')
        {{-- Trusted custom GTM body snippet --}}
        {!! $trackingCustomGtmBodyNoscript !!}
    @elseif($trackingHasValidGtmId)
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $trackingGoogleTagManagerId }}"
        height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    @endif
@else
    @if($trackingHasCustomGtmSnippet)
        {{-- Trusted custom GTM head snippet --}}
        {!! $trackingCustomGtmHeadScript !!}
    @elseif($trackingHasValidGtmId)
        <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
        new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
        'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer','{{ $trackingGoogleTagManagerId }}');</script>
    @endif

    @if($trackingShouldLoadDirectGa)
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $trackingGoogleAnalyticsId }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '{{ $trackingGoogleAnalyticsId }}');
        </script>
    @elseif($trackingGtmEnabled && $trackingHasValidGaId)
        <!-- Direct Google Analytics snippet skipped because GTM is active. Configure GA4 inside GTM to avoid duplicate tracking. -->
    @endif

    @if($trackingHasValidPixelId)
        <script>
            !function(f,b,e,v,n,t,s)
            {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
            n.callMethod.apply(n,arguments):n.queue.push(arguments)};
            if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
            n.queue=[];t=b.createElement(e);t.async=!0;
            t.src=v;s=b.getElementsByTagName(e)[0];
            s.parentNode.insertBefore(t,s)}(window, document,'script',
            'https://connect.facebook.net/en_US/fbevents.js');
            fbq('init', '{{ $trackingFacebookPixelId }}');
            fbq('track', 'PageView');
        </script>
        <noscript><img height="1" width="1" style="display:none"
            src="https://www.facebook.com/tr?id={{ $trackingFacebookPixelId }}&ev=PageView&noscript=1"
        /></noscript>
    @endif

    <script>
        window.dataLayer = window.dataLayer || [];
        window.gtmTrackingEnabled = @json($trackingGtmEnabled);
        window.__trackedTransactions = window.__trackedTransactions || {};

        window.pushDataLayerEvent = function (eventName, payload, options) {
            if (!window.gtmTrackingEnabled || !eventName || !payload || !window.dataLayer) {
                return false;
            }

            var settings = options || {};
            var dedupeKey = settings.dedupeKey || null;

            if (dedupeKey) {
                if (window.__trackedTransactions[dedupeKey]) {
                    return false;
                }

                try {
                    if (window.sessionStorage && window.sessionStorage.getItem('gtm:' + dedupeKey) === '1') {
                        window.__trackedTransactions[dedupeKey] = true;
                        return false;
                    }
                } catch (e) {
                    // sessionStorage unavailable (private mode, storage full).
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
                } catch (e) {
                    // sessionStorage write skipped.
                }
            }

            return true;
        };
    </script>
@endif
