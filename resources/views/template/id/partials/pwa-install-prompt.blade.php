@php
    $requestPath = request()->path();
    $pwaPromptPage = match (true) {
        request()->is('/'), request()->is('id') => 'homepage',
        request()->is('id/*')
            && ! request()->is([
                'id/invoices*',
                'id/deposit*',
                'id/dashboard*',
                'id/settings*',
                'id/reseller*',
                'id/harga*',
                'id/konfirmasi-data*',
            ])
            && substr_count($requestPath, '/') === 1 => 'order',
        default => null,
    };

    $pwaPromptAllowed = $pwaPromptPage !== null;
    $pwaPromptBlocked = request()->is([
        'admin*',
        'filament*',
        'livewire*',
        'api*',
        'ajax*',
        'callback*',
        'wejizy*',
        'id/invoices*',
        'id/deposit*',
        'id/dashboard*',
        'id/settings*',
        'id/reseller*',
        'id/harga*',
        'id/konfirmasi-data*',
    ]);
@endphp

@if($pwaPromptAllowed && ! $pwaPromptBlocked)
    <style>
        .pwa-install-card {
            position: fixed;
            left: 16px;
            right: 16px;
            bottom: 18px;
            z-index: 60;
            display: none;
            max-width: 520px;
            margin: 0 auto;
            border: 1px solid rgba(255, 255, 255, .14);
            border-radius: 24px;
            background: linear-gradient(145deg, rgba(18, 18, 26, .92), rgba(38, 38, 48, .86));
            box-shadow: 0 24px 70px rgba(0, 0, 0, .42), 0 0 0 1px rgba(255, 255, 255, .04) inset;
            color: #fff;
            overflow: hidden;
            backdrop-filter: blur(18px);
        }

        .pwa-install-card.is-visible {
            display: block;
            animation: pwaSlideUp .32s ease-out;
        }

        .pwa-install-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at 12% 0%, rgba(255, 165, 74, .24), transparent 34%),
                radial-gradient(circle at 95% 100%, rgba(80, 167, 255, .18), transparent 34%);
            pointer-events: none;
        }

        .pwa-install-card__inner {
            position: relative;
            display: grid;
            grid-template-columns: 48px 1fr;
            gap: 14px;
            padding: 16px;
        }

        .pwa-install-card__icon {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            box-shadow: 0 12px 28px rgba(0, 0, 0, .25);
        }

        .pwa-install-card__eyebrow {
            margin: 0 0 3px;
            color: #ffd7a8;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .14em;
            text-transform: uppercase;
        }

        .pwa-install-card__title {
            margin: 0;
            font-size: 15px;
            font-weight: 900;
            line-height: 1.25;
        }

        .pwa-install-card__body {
            margin: 5px 0 0;
            color: rgba(255, 255, 255, .72);
            font-size: 12px;
            line-height: 1.45;
        }

        .pwa-install-card__actions {
            grid-column: 1 / -1;
            display: flex;
            gap: 10px;
            margin-top: 2px;
        }

        .pwa-install-card__button {
            flex: 1;
            border: 0;
            border-radius: 14px;
            padding: 11px 12px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 900;
        }

        .pwa-install-card__button--primary {
            color: #171717;
            background: linear-gradient(135deg, #ffd166, #ff8a3d);
            box-shadow: 0 12px 24px rgba(255, 138, 61, .22);
        }

        .pwa-install-card__button--ghost {
            color: rgba(255, 255, 255, .76);
            background: rgba(255, 255, 255, .08);
        }

        .pwa-install-card__hint {
            grid-column: 1 / -1;
            display: none;
            margin: 2px 0 0;
            padding: 12px 14px;
            border-radius: 16px;
            background: rgba(255, 255, 255, .06);
            color: rgba(255, 255, 255, .78);
            font-size: 12px;
            line-height: 1.5;
        }

        .pwa-install-card__hint.is-visible {
            display: block;
        }

        .pwa-install-card__hint strong {
            color: #fff5dd;
        }

        @keyframes pwaSlideUp {
            from { opacity: 0; transform: translateY(18px) scale(.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
    </style>

    <div id="pwa-install-card" class="pwa-install-card" aria-live="polite">
        <div class="pwa-install-card__inner">
            <img class="pwa-install-card__icon" src="{{ asset('assets/pwa/icon-192.png') }}" alt="{{ $config ? $config->judul_web : config('app.name') }}">
            <div>
                <p class="pwa-install-card__eyebrow">PWA Ready</p>
                <p class="pwa-install-card__title">Install aplikasi {{ $config ? $config->judul_web : config('app.name') }}</p>
                <p class="pwa-install-card__body">Akses top up lebih cepat dari layar utama HP kamu.</p>
            </div>
            <div class="pwa-install-card__actions">
                <button id="pwa-install-dismiss" type="button" class="pwa-install-card__button pwa-install-card__button--ghost">Nanti saja</button>
                <button id="pwa-install-action" type="button" class="pwa-install-card__button pwa-install-card__button--primary">Install Sekarang</button>
            </div>
            <p id="pwa-install-hint" class="pwa-install-card__hint" data-pwa-install-hint>
                <strong>Install manual:</strong> buka menu browser lalu pilih <span data-pwa-install-instruction>Tambahkan ke layar utama</span>.
            </p>
        </div>
    </div>

    <script>
        (function () {
            const card = document.getElementById('pwa-install-card');
            const installButton = document.getElementById('pwa-install-action');
            const dismissButton = document.getElementById('pwa-install-dismiss');
            const installHint = document.getElementById('pwa-install-hint');
            const installInstruction = document.querySelector('[data-pwa-install-instruction]');
            const dismissKey = 'pwa-install-dismissed-at';
            const dismissDays = 7;
            let deferredPrompt = null;
            let hasShownPromptEvent = false;

            function isStandalone() {
                return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
            }

            function isIos() {
                const userAgent = window.navigator.userAgent || '';
                const platform = window.navigator.platform || '';
                const isClassicIos = /iPad|iPhone|iPod/.test(userAgent) && !window.MSStream;
                const isModernIpad = platform === 'MacIntel' && (window.navigator.maxTouchPoints || 0) > 1;

                return isClassicIos || isModernIpad;
            }

            function isTablet() {
                const userAgent = window.navigator.userAgent || '';

                if (/iPad/.test(userAgent)) {
                    return true;
                }

                if (/Android/.test(userAgent) && !/Mobile/.test(userAgent)) {
                    return true;
                }

                if (/Tablet|PlayBook|Silk/i.test(userAgent)) {
                    return true;
                }

                return false;
            }

            function isMobilePhone() {
                if (window.navigator.userAgentData && typeof window.navigator.userAgentData.mobile === 'boolean') {
                    return window.navigator.userAgentData.mobile;
                }

                const userAgent = window.navigator.userAgent || '';
                return /Android.*Mobile|iPhone|iPod|BlackBerry|IEMobile|Opera Mini|Mobile/i.test(userAgent);
            }

            function isMobileOrTabletDevice() {
                if (isIos() || isTablet() || isMobilePhone()) {
                    return true;
                }

                const coarsePointer = window.matchMedia('(pointer: coarse)').matches;
                const narrowViewport = window.innerWidth <= 1024;

                return coarsePointer && narrowViewport;
            }

            function supportsDeferredInstallPrompt() {
                return !!deferredPrompt;
            }

            function track(eventName, payload) {
                if (typeof window.pushDataLayerEvent === 'function') {
                    window.pushDataLayerEvent(eventName, Object.assign({
                        pwa: {
                            source: '{{ $pwaPromptPage === 'order' ? 'order_install_prompt' : 'homepage_install_prompt' }}',
                            display_mode: isStandalone() ? 'standalone' : 'browser'
                        }
                    }, payload || {}));
                }
            }

            function recentlyDismissed() {
                try {
                    const value = Number(window.localStorage.getItem(dismissKey) || 0);
                    return value > 0 && Date.now() - value < dismissDays * 24 * 60 * 60 * 1000;
                } catch (error) {
                    return false;
                }
            }

            function rememberDismissed() {
                try {
                    window.localStorage.setItem(dismissKey, String(Date.now()));
                } catch (error) {
                    // Ignore unavailable storage.
                }
            }

            function showCard() {
                if (!card || isStandalone() || recentlyDismissed() || !isMobileOrTabletDevice()) {
                    return;
                }

                if (isIos()) {
                    showFallbackHint(false);
                }

                card.classList.add('is-visible');

                if (!hasShownPromptEvent) {
                    hasShownPromptEvent = true;
                    track('pwa_install_prompt_shown');
                }
            }

            function hideCard() {
                if (card) {
                    card.classList.remove('is-visible');
                }
            }

            function hideHint() {
                if (installHint) {
                    installHint.classList.remove('is-visible');
                }
            }

            function showFallbackHint(shouldShowCard = true) {
                if (!isMobileOrTabletDevice()) {
                    hideCard();
                    return;
                }

                if (!installHint) {
                    hideCard();
                    return;
                }

                if (installInstruction) {
                    installInstruction.textContent = isIos()
                        ? 'tap Share di Safari lalu pilih Tambahkan ke Layar Utama'
                        : 'Install App atau Tambahkan ke layar utama';
                }

                installHint.classList.add('is-visible');
                if (shouldShowCard) {
                    showCard();
                }
                track('pwa_install_manual_hint_shown', {
                    pwa: {
                        source: isIos() ? 'ios_manual_install_hint' : 'unsupported_browser_install_hint'
                    }
                });
            }

            if (isStandalone()) {
                track('pwa_launched', { pwa: { source: 'standalone_launch', display_mode: 'standalone' } });
                return;
            }

            if (!isMobileOrTabletDevice()) {
                hideCard();
                return;
            }

            if (isIos() && installButton) {
                installButton.textContent = 'Lihat Cara Install';
            }

            window.addEventListener('beforeinstallprompt', function (event) {
                event.preventDefault();
                deferredPrompt = event;
                hideHint();
                showCard();
            });

            window.addEventListener('appinstalled', function () {
                hideCard();
                hideHint();
                deferredPrompt = null;
                track('pwa_install_accepted', { pwa: { source: 'browser_appinstalled_event' } });
            });

            if (dismissButton) {
                dismissButton.addEventListener('click', function () {
                    rememberDismissed();
                    hideCard();
                    hideHint();
                    track('pwa_install_prompt_dismissed');
                });
            }

            if (installButton) {
                installButton.addEventListener('click', function () {
                    if (!supportsDeferredInstallPrompt()) {
                        showFallbackHint();
                        return;
                    }

                    deferredPrompt.prompt();
                    deferredPrompt.userChoice.then(function (choiceResult) {
                        const accepted = choiceResult && choiceResult.outcome === 'accepted';
                        track(accepted ? 'pwa_install_accepted' : 'pwa_install_rejected');
                        if (!accepted) {
                            rememberDismissed();
                        }
                        hideCard();
                        hideHint();
                        deferredPrompt = null;
                    });
                });
            }
        })();
    </script>
@endif
