<style>
    .fomo-toast {
        position: fixed;
        bottom: 24px;
        right: 24px;
        z-index: 9999;
        width: 100%;
        max-width: 320px;
        transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .fomo-toast-hidden {
        transform: translateY(40px);
        opacity: 0;
        pointer-events: none;
    }

    .fomo-toast-visible {
        transform: translateY(0);
        opacity: 1;
        pointer-events: auto;
    }

    .fomo-toast-box {
        background-color: rgba(31, 41, 55, 0.95);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 16px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5), 0 10px 10px -5px rgba(0, 0, 0, 0.3);
        padding: 16px;
        display: flex;
        align-items: flex-start;
        gap: 12px;
        position: relative;
        overflow: hidden;
    }

    .fomo-shimmer {
        position: absolute;
        inset: 0;
        transform: translateX(-100%);
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.05), transparent);
        animation: fomo-shimmer-anim 3s infinite linear;
        pointer-events: none;
    }

    @keyframes fomo-shimmer-anim {
        100% {
            transform: translateX(100%);
        }
    }

    .fomo-toast-img-wrapper {
        flex-shrink: 0;
        position: relative;
    }

    .fomo-toast-img {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        object-fit: cover;
        border: 2px solid rgba(139, 92, 246, 0.5);
    }

    .fomo-toast-icon-wrapper {
        position: absolute;
        bottom: -4px;
        right: -4px;
        background-color: #22c55e;
        border-radius: 50%;
        padding: 2px;
        border: 2px solid #1f2937;
    }

    .fomo-toast-icon {
        width: 12px;
        height: 12px;
        color: white;
    }

    .fomo-toast-content {
        flex: 1;
        min-width: 0;
        padding-right: 12px;
        text-align: left;
    }

    .fomo-toast-subtitle {
        font-size: 11px;
        color: #9ca3af;
        margin: 0 0 2px;
    }

    .fomo-toast-title {
        font-size: 14px;
        font-weight: 700;
        color: #fff;
        line-height: 1.2;
        margin: 0 0 4px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .fomo-toast-meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: 4px;
    }

    .fomo-toast-name {
        font-size: 12px;
        font-weight: 600;
        color: var(--warna_1, #8b5cf6);
        margin: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        padding-right: 8px;
    }

    .fomo-toast-time {
        font-size: 10px;
        color: #6b7280;
        margin: 0;
        white-space: nowrap;
    }

    .fomo-toast-close {
        position: absolute;
        top: 12px;
        right: 12px;
        background: none;
        border: none;
        color: #9ca3af;
        cursor: pointer;
        padding: 4px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
    }

    .fomo-toast-close:hover {
        color: #fff;
        background-color: rgba(255, 255, 255, 0.1);
    }

    .fomo-toast-close svg {
        width: 16px;
        height: 16px;
        fill: none;
        stroke: currentColor;
    }

    @media (max-width: 640px) {
        .fomo-toast {
            bottom: 80px;
            right: 16px;
            width: calc(100% - 32px);
        }
    }
</style>

<div
    id="live-sales-toast"
    class="fomo-toast fomo-toast-hidden"
    data-endpoint="{{ route('recent-purchases.index') }}"
    data-fallback-image="{{ asset($config ? $config->logo_favicon : 'assets/logo/favicon.webp') }}"
>
    <div class="fomo-toast-box">
        <div class="fomo-shimmer"></div>
        <div class="fomo-toast-img-wrapper">
            <img id="toast-image" src="" alt="Game" class="fomo-toast-img">
            <div class="fomo-toast-icon-wrapper">
                <svg class="fomo-toast-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
        </div>
        <div class="fomo-toast-content">
            <p class="fomo-toast-subtitle">Baru saja membeli</p>
            <p id="toast-item" class="fomo-toast-title"></p>
            <div class="fomo-toast-meta">
                <p id="toast-name" class="fomo-toast-name"></p>
                <p id="toast-time" class="fomo-toast-time"></p>
            </div>
        </div>
        <button type="button" class="fomo-toast-close" onclick="hideLiveSalesToast()">
            <svg viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>
</div>

<script>
    (function () {
        const toast = document.getElementById('live-sales-toast');

        if (!toast) {
            return;
        }

        const endpoint = toast.dataset.endpoint;
        const fallbackImage = toast.dataset.fallbackImage;
        const image = document.getElementById('toast-image');
        const item = document.getElementById('toast-item');
        const name = document.getElementById('toast-name');
        const time = document.getElementById('toast-time');

        let recentPurchases = [];
        let cycleTimer = null;
        let hideTimer = null;

        function hideToast() {
            toast.classList.remove('fomo-toast-visible');
            toast.classList.add('fomo-toast-hidden');
        }

        function clearTimers() {
            clearTimeout(cycleTimer);
            clearTimeout(hideTimer);
            cycleTimer = null;
            hideTimer = null;
        }

        function scheduleNextToast(delay = 2000) {
            clearTimeout(cycleTimer);
            cycleTimer = window.setTimeout(showRandomToast, delay);
        }

        function showRandomToast() {
            if (!recentPurchases.length) {
                return;
            }

            const purchase = recentPurchases[Math.floor(Math.random() * recentPurchases.length)];

            image.src = purchase.image || fallbackImage;
            item.textContent = purchase.item || 'Item';
            name.textContent = purchase.name || 'Seseorang';
            time.textContent = purchase.time_ago || 'Baru saja';

            toast.classList.remove('fomo-toast-hidden');
            toast.classList.add('fomo-toast-visible');

            clearTimeout(hideTimer);
            hideTimer = window.setTimeout(() => {
                hideToast();
                scheduleNextToast();
            }, 3500);
        }

        function startToastCycle() {
            if (!recentPurchases.length || cycleTimer !== null || hideTimer !== null) {
                return;
            }

            scheduleNextToast();
        }

        function fetchRecentPurchases() {
            window.fetch(endpoint, {
                headers: {
                    Accept: 'application/json',
                },
            })
                .then((response) => response.ok ? response.json() : [])
                .then((data) => {
                    if (!Array.isArray(data)) {
                        return;
                    }

                    recentPurchases = data;

                    if (!recentPurchases.length) {
                        clearTimers();
                        hideToast();
                        return;
                    }

                    startToastCycle();
                })
                .catch((error) => console.error('Toast fetch error:', error));
        }

        window.hideLiveSalesToast = function () {
            clearTimeout(hideTimer);
            hideTimer = null;
            hideToast();
            scheduleNextToast();
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', boot, { once: true });
        } else {
            boot();
        }

        function boot() {
            fetchRecentPurchases();
            window.setInterval(fetchRecentPurchases, 300000);
        }
    })();
</script>
