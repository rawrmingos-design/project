<style>
    .pwa-push-card[hidden] {
        display: none !important;
    }

    .pwa-push-card {
        position: fixed;
        left: 16px;
        right: 16px;
        bottom: 18px;
        z-index: 70;
        max-width: 560px;
        margin: 0 auto;
        color: #fff;
        border: 1px solid rgba(255, 255, 255, .14);
        border-radius: 24px;
        background: linear-gradient(145deg, rgba(15, 23, 42, .94), rgba(30, 41, 59, .9));
        box-shadow: 0 24px 70px rgba(0, 0, 0, .42), 0 0 0 1px rgba(255, 255, 255, .04) inset;
        overflow: hidden;
        backdrop-filter: blur(18px);
        animation: pwaPushSlideUp .32s ease-out;
    }

    .pwa-push-card::before {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(circle at 12% 0%, rgba(34, 197, 94, .24), transparent 34%),
            radial-gradient(circle at 95% 100%, rgba(59, 130, 246, .2), transparent 34%);
        pointer-events: none;
    }

    .pwa-push-card__content {
        position: relative;
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 14px;
        align-items: center;
        padding: 16px;
    }

    .pwa-push-card__eyebrow {
        margin: 0 0 5px;
        font-size: 11px;
        font-weight: 900;
        letter-spacing: .12em;
        text-transform: uppercase;
        color: rgba(187, 247, 208, .88);
    }

    .pwa-push-card__title {
        margin: 0;
        font-size: 16px;
        font-weight: 900;
        line-height: 1.2;
    }

    .pwa-push-card__body,
    .pwa-push-card__status {
        margin: 6px 0 0;
        color: rgba(255, 255, 255, .74);
        font-size: 13px;
        line-height: 1.45;
    }

    .pwa-push-card__status[data-tone="success"] {
        color: #bbf7d0;
    }

    .pwa-push-card__status[data-tone="warning"] {
        color: #fde68a;
    }

    .pwa-push-card__status[data-tone="danger"] {
        color: #fecaca;
    }

    .pwa-push-card__actions {
        display: flex;
        flex-direction: column;
        gap: 8px;
        min-width: 156px;
    }

    .pwa-push-card__button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        padding: 11px 15px;
        border: 0;
        border-radius: 15px;
        cursor: pointer;
        color: #052e16;
        background: linear-gradient(135deg, #bbf7d0, #22c55e);
        box-shadow: 0 14px 28px rgba(34, 197, 94, .24);
        font: inherit;
        font-size: 12px;
        font-weight: 900;
        transition: transform .18s ease, filter .18s ease, opacity .18s ease;
    }

    .pwa-push-card__button--ghost {
        color: rgba(255, 255, 255, .82);
        background: rgba(255, 255, 255, .08);
        border: 1px solid rgba(255, 255, 255, .1);
        box-shadow: none;
    }

    .pwa-push-card__button:hover:not(:disabled) {
        transform: translateY(-1px);
        filter: brightness(1.03);
    }

    .pwa-push-card__button:disabled {
        cursor: not-allowed;
        opacity: .72;
    }

    @keyframes pwaPushSlideUp {
        from { opacity: 0; transform: translateY(12px) scale(.985); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }

    @media (max-width: 640px) {
        .pwa-push-card {
            left: 12px;
            right: 12px;
            bottom: 12px;
        }

        .pwa-push-card__content {
            grid-template-columns: 1fr;
        }

        .pwa-push-card__actions {
            min-width: 0;
            width: 100%;
        }

        .pwa-push-card__button {
            width: 100%;
        }
    }
</style>

<div
    id="pwa-push-card"
    class="pwa-push-card"
    data-pwa-push-card
    hidden
>
    <div class="pwa-push-card__content">
        <div>
            <p class="pwa-push-card__eyebrow" data-pwa-push-eyebrow>Notifikasi IstanaTopup</p>
            <h3 class="pwa-push-card__title" data-pwa-push-title>Aktifkan notifikasi promo & update</h3>
            <p class="pwa-push-card__body" data-pwa-push-body>Izinkan notifikasi agar status pesanan, pembayaran, promo, dan info penting langsung masuk ke aplikasi IstanaTopup.</p>
        </div>
        <div class="pwa-push-card__actions">
            <button type="button" class="pwa-push-card__button" data-pwa-push-enable>
                Aktifkan Notifikasi
            </button>
            <button type="button" class="pwa-push-card__button pwa-push-card__button--ghost" data-pwa-push-dismiss>
                Nanti saja
            </button>
        </div>
        <p class="pwa-push-card__status" data-pwa-push-status aria-live="polite"></p>
    </div>
</div>
