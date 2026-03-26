@extends('template.template')

@section('custom_style')
    <style>
        .fa-star,
        .fa-star-o {
            color: #FFD700;
            cursor: pointer;
            font-size: 24px;
            margin-right: 5px;
        }

        .fa-star-o:hover {
            color: #FFA500;
        }

        .bg-green-500 {
            --tw-bg-opacity: 1;
            background-color: rgb(34 197 94 / var(--tw-bg-opacity));
        }

        .w-0\.5 {
            width: .125rem;
        }

        .h-full {
            height: 100%;
        }

        .mt-0\.5 {
            margin-top: .125rem;
        }

        .-ml-px {
            margin-left: -1px;
        }

        .top-4 {
            top: 1rem;
        }

        .left-4 {
            left: 1rem;
        }

        .absolute {
            position: absolute;
        }

        textarea {
            --tw-shadow: 0 0 #0000;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-color: #636161;
            border-color: #6b7280;
            border-radius: 0;
            border-width: 1px;
            font-size: 1rem;
            line-height: 1.5rem;
            padding: 0.5rem 0.75rem;
        }

        .invoice-animate {
            opacity: 0;
            transform: translateY(22px) scale(0.985);
        }

        .invoice-animate.is-visible {
            animation: invoiceFadeUp .7s cubic-bezier(.22, 1, .36, 1) forwards;
        }

        .invoice-animate-delay-1 {
            animation-delay: .08s;
        }

        .invoice-animate-delay-2 {
            animation-delay: .16s;
        }

        .invoice-animate-delay-3 {
            animation-delay: .24s;
        }

        .invoice-animate-delay-4 {
            animation-delay: .32s;
        }

        .invoice-hero-glow {
            position: relative;
            overflow: hidden;
            isolation: isolate;
            padding: .25rem 0;
        }

        .invoice-hero-glow::before {
            content: '';
            position: absolute;
            inset: -35% auto auto -12%;
            width: 16rem;
            height: 16rem;
            background: radial-gradient(circle, rgba(59, 130, 246, .2) 0%, rgba(59, 130, 246, 0) 70%);
            filter: blur(10px);
            pointer-events: none;
            z-index: -1;
            animation: invoiceGlowFloat 5.5s ease-in-out infinite;
        }

        .invoice-intro-overlay {
            position: fixed;
            inset: 0;
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            background-color: var(--warna_4, #1f2937);
            background-image: var(--gradient-theme, none);
            opacity: 1;
            visibility: visible;
            transform: scale(1);
            transition: opacity .65s cubic-bezier(.22, 1, .36, 1), visibility .65s ease, transform .95s cubic-bezier(.22, 1, .36, 1);
        }

        .invoice-intro-overlay[data-state="paid"] {
            background:
                radial-gradient(circle at top, rgba(255, 255, 255, .08), transparent 30%),
                linear-gradient(180deg, #059669 0%, #047857 100%);
        }

        .invoice-intro-overlay[data-state="expired"] {
            background-color: var(--warna_4, #1f2937);
            background-image: var(--gradient-theme, none);
        }

        .invoice-intro-overlay[data-state="failed"] {
            background-color: var(--warna_4, #1f2937);
            background-image: var(--gradient-theme, none);
        }

        .invoice-intro-overlay.is-hiding {
            opacity: 0;
            visibility: hidden;
            transform: translateY(-3%) scale(1.035);
        }

        .invoice-intro-overlay.is-visible {
            opacity: 1;
            visibility: visible;
            transform: scale(1);
        }

        .invoice-intro-card {
            width: min(100%, 68rem);
            text-align: center;
            color: #fff;
            opacity: 0;
            transform: translateY(28px) scale(.965);
            transition: opacity .82s cubic-bezier(.22, 1, .36, 1), transform 1.05s cubic-bezier(.22, 1, .36, 1);
        }

        .invoice-intro-card--glass {
            border-radius: 1.25rem;
            background: linear-gradient(140deg, rgba(255, 255, 255, .18), rgba(255, 255, 255, .08));
            border: 1px solid rgba(255, 255, 255, .24);
            box-shadow: 0 20px 45px rgba(2, 6, 23, .2);
            backdrop-filter: blur(14px) saturate(120%);
            -webkit-backdrop-filter: blur(14px) saturate(120%);
        }

        .invoice-intro-card--glass {
            padding: 1.5rem 1.25rem 1.6rem;
        }

        .invoice-intro-overlay.is-visible .invoice-intro-card {
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        .invoice-intro-overlay.is-hiding .invoice-intro-card {
            opacity: 0;
            transform: translateY(-18px) scale(.985);
        }

        .invoice-intro-stage-custom {
            position: relative;
            width: min(88vw, 29rem);
            height: min(88vw, 29rem);
            margin: 0 auto 2rem;
            pointer-events: none;
            filter: drop-shadow(0 28px 58px rgba(2, 6, 23, .32));
            will-change: transform, opacity;
        }

        .invoice-intro-lottie-shell {
            width: min(88vw, 30rem);
            margin: 0 auto 2rem;
            pointer-events: none;
            filter: drop-shadow(0 28px 58px rgba(2, 6, 23, .28));
            will-change: transform, opacity;
        }

        .invoice-intro-lottie-shell lottie-player {
            width: 100%;
            height: auto;
            min-height: 18rem;
            background: transparent;
        }

        .invoice-intro-overlay.is-visible .invoice-intro-stage-custom {
            animation: invoiceIntroStageFloat 3.2s ease-in-out 1.8s infinite;
        }

        .invoice-intro-overlay.is-visible .invoice-intro-lottie-shell {
            animation: invoiceIntroStageFloat 3.2s ease-in-out .4s infinite;
        }

        .invoice-intro-overlay[data-state="paid"].is-visible .invoice-intro-stage-custom {
            animation-duration: 3.8s;
        }

        .invoice-intro-overlay[data-state="expired"].is-visible .invoice-intro-stage-custom,
        .invoice-intro-overlay[data-state="failed"].is-visible .invoice-intro-stage-custom {
            animation-duration: 3s;
        }

        .invoice-intro-orb,
        .invoice-intro-ring,
        .invoice-intro-card-icon,
        .invoice-intro-arrow,
        .invoice-intro-chip {
            position: absolute;
            left: 50%;
            top: 50%;
            opacity: 0;
            transform: translate(-50%, -50%) scale(.86);
        }

        .invoice-intro-orb {
            width: 76%;
            height: 76%;
            border-radius: 999px;
            z-index: 1;
            background:
                radial-gradient(circle at 30% 25%, rgba(255, 255, 255, .38), rgba(255, 255, 255, 0) 52%),
                linear-gradient(145deg, rgba(16, 185, 129, .95), rgba(22, 163, 74, .9));
            box-shadow:
                inset 0 -14px 30px rgba(0, 0, 0, .16),
                0 20px 45px rgba(2, 6, 23, .26);
        }

        .invoice-intro-ring {
            width: 92%;
            height: 92%;
            border-radius: 999px;
            border: 2px dashed rgba(255, 255, 255, .4);
            z-index: 0;
        }

        .invoice-intro-card-icon {
            width: 34%;
            height: 24%;
            top: 42%;
            left: 42%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 1rem;
            z-index: 3;
            color: #fff;
            background: linear-gradient(165deg, rgba(30, 41, 59, .95), rgba(15, 23, 42, .92));
            border: 1px solid rgba(255, 255, 255, .22);
            box-shadow: 0 16px 26px rgba(15, 23, 42, .32);
        }

        .invoice-intro-card-icon svg {
            width: 1.8rem;
            height: 1.8rem;
        }

        .invoice-intro-arrow {
            width: 22%;
            height: 22%;
            top: 50%;
            left: 59%;
            z-index: 4;
            color: rgba(255, 255, 255, .95);
            filter: drop-shadow(0 8px 18px rgba(2, 6, 23, .28));
        }

        .invoice-intro-arrow svg {
            width: 100%;
            height: 100%;
        }

        .invoice-intro-chip {
            top: 64%;
            left: 59%;
            z-index: 5;
            padding: .55rem .9rem;
            border-radius: 999px;
            background: rgba(15, 23, 42, .38);
            border: 1px solid rgba(255, 255, 255, .2);
            color: rgba(255, 255, 255, .95);
            font-size: .74rem;
            font-weight: 800;
            letter-spacing: .11em;
            text-transform: uppercase;
            backdrop-filter: blur(4px);
        }

        .invoice-intro-overlay.is-visible .invoice-intro-orb {
            animation: invoiceIntroOrbIn .95s cubic-bezier(.16, 1, .3, 1) .05s forwards;
        }

        .invoice-intro-overlay.is-visible .invoice-intro-ring {
            animation: invoiceIntroRingIn .9s cubic-bezier(.22, 1, .36, 1) .32s forwards,
                invoiceIntroRingSpin 6s linear 1.4s infinite;
        }

        .invoice-intro-overlay.is-visible .invoice-intro-card-icon {
            animation: invoiceIntroCardPop .82s cubic-bezier(.22, 1, .36, 1) .55s forwards;
        }

        .invoice-intro-overlay.is-visible .invoice-intro-arrow {
            animation: invoiceIntroArrowMove .86s cubic-bezier(.22, 1, .36, 1) 1.05s forwards;
        }

        .invoice-intro-overlay.is-visible .invoice-intro-chip {
            animation: invoiceIntroChipIn .72s cubic-bezier(.22, 1, .36, 1) 1.35s forwards,
                invoiceIntroChipPulse 1.15s ease-in-out 2.2s infinite;
        }

        .invoice-intro-status {
            display: inline-flex;
            align-items: center;
            gap: .7rem;
            margin-bottom: 1.05rem;
            padding: .7rem 1rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, .14);
            border: 1px solid rgba(255, 255, 255, .18);
            backdrop-filter: blur(10px);
            box-shadow: 0 20px 40px rgba(15, 23, 42, .18);
        }

        .invoice-intro-status-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, .16);
        }

        .invoice-intro-status-icon svg {
            width: 1.05rem;
            height: 1.05rem;
        }

        .invoice-intro-status-text {
            font-size: .88rem;
            font-weight: 800;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, .95);
        }

        .invoice-toast {
            position: fixed;
            top: 1.25rem;
            left: 1.25rem;
            z-index: 9998;
            display: inline-flex;
            align-items: flex-start;
            gap: .8rem;
            max-width: min(92vw, 26rem);
            padding: .95rem 1rem;
            border-radius: 1rem;
            background: rgba(15, 23, 42, .94);
            border: 1px solid rgba(255, 255, 255, .08);
            box-shadow: 0 20px 50px rgba(2, 6, 23, .28);
            backdrop-filter: blur(12px);
            color: #fff;
            opacity: 0;
            visibility: hidden;
            transform: translate3d(-18px, -18px, 0);
            transition: opacity .45s ease, transform .55s cubic-bezier(.22, 1, .36, 1), visibility .45s ease;
        }

        .invoice-toast.is-visible {
            opacity: 1;
            visibility: visible;
            transform: translate3d(0, 0, 0);
        }

        .invoice-toast.is-hiding {
            opacity: 0;
            visibility: hidden;
            transform: translate3d(-10px, -14px, 0);
        }

        .invoice-toast__icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.2rem;
            height: 2.2rem;
            border-radius: 999px;
            background: rgba(59, 130, 246, .18);
            color: #bfdbfe;
            flex-shrink: 0;
        }

        .invoice-toast[data-tone="paid"] .invoice-toast__icon,
        .invoice-toast[data-tone="success"] .invoice-toast__icon {
            background: rgba(34, 197, 94, .16);
            color: #bbf7d0;
        }

        .invoice-toast[data-tone="expired"] .invoice-toast__icon,
        .invoice-toast[data-tone="failed"] .invoice-toast__icon {
            background: rgba(244, 63, 94, .16);
            color: #fecdd3;
        }

        .invoice-toast__icon svg {
            width: 1rem;
            height: 1rem;
        }

        .invoice-toast__title {
            font-size: .9rem;
            font-weight: 800;
            color: #fff;
        }

        .invoice-toast__body {
            margin-top: .2rem;
            font-size: .8rem;
            line-height: 1.55;
            color: rgba(226, 232, 240, .82);
        }

        .invoice-intro-title {
            margin: 0;
            font-size: clamp(2rem, 4vw, 3.4rem);
            line-height: 1.04;
            font-weight: 800;
            letter-spacing: -.04em;
        }

        .invoice-intro-subtitle {
            margin: 1rem auto 0;
            max-width: 38rem;
            font-size: 1.02rem;
            line-height: 1.7;
            color: rgba(255, 255, 255, .9);
        }

        .invoice-page-shell {
            opacity: 0;
            visibility: hidden;
            transform: translateY(32px) scale(.985);
            transition: opacity .7s cubic-bezier(.22, 1, .36, 1), visibility .7s ease, transform .95s cubic-bezier(.22, 1, .36, 1);
        }

        .invoice-page-shell.is-ready {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .invoice-panel {
            position: relative;
            overflow: hidden;
            border-radius: 1rem;
        }

        .invoice-panel::after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: inherit;
            border: 1px solid rgba(255, 255, 255, .05);
            pointer-events: none;
        }

        .invoice-qr-frame {
            position: relative;
            box-shadow: 0 18px 50px rgba(15, 23, 42, .28);
            border-radius: 0.75rem;
        }

        .invoice-qr-frame::before {
            content: '';
            position: absolute;
            inset: -2px;
            border-radius: 0.75rem;
            background: linear-gradient(135deg, rgba(59, 130, 246, .42), rgba(34, 197, 94, .18), rgba(251, 191, 36, .34));
            z-index: -1;
            opacity: .85;
            animation: invoiceBorderPulse 3.2s ease-in-out infinite;
        }

        .invoice-amount-pop {
            transform-origin: right center;
        }

        .invoice-amount-pop.is-visible {
            animation: invoiceScaleIn .7s cubic-bezier(.22, 1, .36, 1) .18s both;
        }

        .invoice-step-active {
            animation: invoiceBreath 2s ease-in-out infinite;
        }

        .invoice-fallback-card {
            border-radius: 1rem;
            background: linear-gradient(180deg, rgba(255, 255, 255, .02), rgba(255, 255, 255, .01));
        }

        .invoice-shell {
            position: relative;
        }

        .invoice-shell::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            width: min(72rem, 92vw);
            height: 24rem;
            transform: translateX(-50%);
            background:
                radial-gradient(circle at 18% 15%, rgba(59, 130, 246, .12), transparent 24%),
                radial-gradient(circle at 82% 10%, rgba(34, 197, 94, .1), transparent 22%),
                radial-gradient(circle at 55% 65%, rgba(251, 191, 36, .08), transparent 28%);
            pointer-events: none;
            filter: blur(8px);
            z-index: 0;
        }

        .invoice-shell>* {
            position: relative;
            z-index: 1;
        }

        .invoice-hero-block {
            padding: 1.25rem 1.5rem;
            border-radius: 1.5rem;
            background:
                linear-gradient(140deg, rgba(255, 255, 255, .05), rgba(255, 255, 255, .015)),
                rgba(15, 23, 42, .22);
            border: 1px solid rgba(255, 255, 255, .06);
            box-shadow: 0 26px 60px rgba(2, 6, 23, .18);
        }

        .invoice-hero-title {
            line-height: 1.02;
            letter-spacing: -.03em;
        }

        .invoice-hero-description {
            max-width: 40rem;
            color: rgba(255, 255, 255, .78);
        }

        .invoice-status-banner {
            position: relative;
            overflow: hidden;
            padding: 4.25rem 1rem 3.25rem;
            text-align: center;
            border-bottom: 0;
        }

        .invoice-status-banner::before {
            content: '';
            position: absolute;
            inset: 0;
            background: transparent;
            z-index: 0;
        }

        .invoice-status-banner[data-tone="paid"]::before {
            background: transparent;
        }

        .invoice-status-banner[data-tone="expired"]::before {
            background: transparent;
        }

        .invoice-status-banner[data-tone="failed"]::before {
            background: transparent;
        }

        .invoice-status-banner .container {
            position: relative;
            z-index: 1;
            display: flex;
            justify-content: center;
        }

        .invoice-status-banner__panel {
            width: min(100%, 56rem);
            padding: 1.5rem 1.25rem;
            border-radius: 1.25rem;
            background:
                linear-gradient(180deg, rgba(255, 255, 255, .03), rgba(255, 255, 255, .012)),
                rgba(15, 23, 42, .18);
            border: 1px solid rgba(255, 255, 255, .12);
            box-shadow: 0 20px 45px rgba(2, 6, 23, .16);
            backdrop-filter: blur(10px) saturate(112%);
            -webkit-backdrop-filter: blur(10px) saturate(112%);
        }

        .invoice-status-banner__icon {
            width: 4.5rem;
            height: 4.5rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            margin-bottom: 1.35rem;
            background: rgba(15, 23, 42, .18);
            border: 1px solid rgba(255, 255, 255, .26);
            box-shadow: 0 14px 36px rgba(15, 23, 42, .22);
            animation: invoiceHeroFloat 3.2s ease-in-out infinite;
        }

        .invoice-status-banner__icon svg {
            width: 1.9rem;
            height: 1.9rem;
            color: #fff;
        }

        .invoice-status-banner__title {
            color: #fff;
            margin: 0;
            font-size: clamp(1.9rem, 3.4vw, 3rem);
            line-height: 1.06;
            letter-spacing: -.03em;
            font-weight: 800;
        }

        .invoice-status-banner__description {
            margin: .9rem auto 0;
            max-width: 44rem;
            color: rgba(255, 255, 255, .9);
            font-size: 1rem;
            line-height: 1.65;
        }

        .invoice-expiry-card {
            padding: 1rem 1.15rem;
            border-radius: 1.1rem;
            background: linear-gradient(180deg, rgba(244, 63, 94, .08), rgba(255, 255, 255, .02));
            border: 1px solid rgba(244, 63, 94, .16);
            box-shadow: 0 18px 36px rgba(15, 23, 42, .14);
        }

        .invoice-expiry-chip {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 14rem;
            border-radius: 999px;
            padding: .8rem 1.35rem;
            font-weight: 700;
            letter-spacing: .02em;
            background: linear-gradient(135deg, rgba(244, 63, 94, .92), rgba(249, 115, 22, .82));
            box-shadow: 0 14px 28px rgba(244, 63, 94, .18);
        }

        .invoice-expiry-chip.is-expired {
            background: linear-gradient(135deg, rgba(107, 114, 128, .92), rgba(75, 85, 99, .82));
            box-shadow: 0 14px 28px rgba(107, 114, 128, .16);
        }

        .invoice-expiry-meta {
            margin-top: .6rem;
            font-size: .76rem;
            color: rgba(255, 255, 255, .62);
        }

        .invoice-divider-block {
            border-top: 1px solid rgba(255, 255, 255, .06);
            border-bottom: 1px solid rgba(255, 255, 255, .06);
        }

        .invoice-detail-card {
            padding: 1.25rem;
            background:
                linear-gradient(180deg, rgba(255, 255, 255, .03), rgba(255, 255, 255, .012));
            border: 1px solid rgba(255, 255, 255, .06);
            box-shadow: 0 30px 55px rgba(2, 6, 23, .14);
        }

        .invoice-thumbnail-frame {
            border-radius: 1rem;
            box-shadow: 0 16px 36px rgba(2, 6, 23, .26);
        }

        .invoice-payment-card {
            padding: 1.25rem;
            background:
                linear-gradient(180deg, rgba(255, 255, 255, .03), rgba(255, 255, 255, .012));
            border: 1px solid rgba(255, 255, 255, .06);
            box-shadow: 0 30px 55px rgba(2, 6, 23, .14);
        }

        .invoice-payment-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            padding-bottom: .85rem;
            margin-bottom: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, .06);
        }

        .invoice-method-pill {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            padding: .45rem .8rem;
            border-radius: 999px;
            background: rgba(59, 130, 246, .12);
            color: #bfdbfe;
            font-size: .8rem;
            font-weight: 700;
            letter-spacing: .02em;
        }

        .invoice-copy-button {
            border-radius: .85rem;
            background: rgba(255, 255, 255, .045);
            border: 1px solid rgba(255, 255, 255, .08);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .03);
            transition: transform .18s ease, background-color .18s ease, border-color .18s ease;
        }

        .invoice-copy-button:hover {
            transform: translateY(-1px);
            background: rgba(255, 255, 255, .065);
            border-color: rgba(255, 255, 255, .14);
        }

        .invoice-status-row {
            align-items: center;
        }

        .invoice-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 6.75rem;
            padding: .35rem .7rem;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .05em;
            text-transform: uppercase;
            border: 1px solid transparent;
            transition: transform .25s ease, background-color .25s ease, border-color .25s ease, color .25s ease;
        }

        .invoice-badge-warning {
            color: #fef3c7;
            background: rgba(245, 158, 11, .14);
            border-color: rgba(245, 158, 11, .3);
        }

        .invoice-badge-info {
            color: #dbeafe;
            background: rgba(59, 130, 246, .14);
            border-color: rgba(59, 130, 246, .28);
        }

        .invoice-badge-danger {
            color: #ffe4e6;
            background: rgba(244, 63, 94, .14);
            border-color: rgba(244, 63, 94, .28);
        }

        .invoice-badge-success {
            color: #dcfce7;
            background: rgba(34, 197, 94, .14);
            border-color: rgba(34, 197, 94, .28);
        }

        .invoice-pay-button {
            position: relative;
            overflow: hidden;
            border-radius: 1rem;
            box-shadow: 0 16px 36px rgba(59, 130, 246, .24);
        }

        .invoice-pay-button::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .18), transparent);
            transform: translateX(-140%);
            animation: invoiceButtonSweep 3.2s linear infinite;
            pointer-events: none;
        }

        .invoice-total-box {
            padding: 1rem 1.15rem;
            border-radius: 1.1rem;
            background: linear-gradient(135deg, rgba(59, 130, 246, .12), rgba(255, 255, 255, .02));
            border: 1px solid rgba(59, 130, 246, .18);
            box-shadow: 0 18px 40px rgba(2, 6, 23, .16);
        }

        .invoice-progress-card {
            padding: 1.1rem 1.15rem;
            border-radius: 1.1rem;
            background: linear-gradient(180deg, rgba(255, 255, 255, .03), rgba(255, 255, 255, .012));
            border: 1px solid rgba(255, 255, 255, .06);
        }

        .invoice-stepper {
            width: 100%;
        }

        .invoice-stepper-track {
            position: relative;
            height: 4px;
            border-radius: 999px;
            background: rgba(148, 163, 184, .22);
            overflow: hidden;
        }

        .invoice-stepper-fill {
            position: absolute;
            inset: 0 auto 0 0;
            width: 0;
            border-radius: inherit;
            background: linear-gradient(90deg, #22c55e, #3b82f6);
            transition: width 1s cubic-bezier(.22, 1, .36, 1);
        }

        .invoice-stepper[data-theme="expired"] .invoice-stepper-fill {
            background: linear-gradient(90deg, #22c55e, #ec4899);
        }

        .invoice-stepper[data-theme="failed"] .invoice-stepper-fill {
            background: linear-gradient(90deg, #22c55e, #ef4444);
        }

        #invoicePageShell.is-ready .invoice-stepper-fill {
            width: var(--progress-width, 25%);
        }

        .invoice-stepper-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 1.25rem;
            margin-top: -1rem;
        }

        .invoice-stepper-item {
            position: relative;
            padding-top: .9rem;
        }

        .invoice-stepper-node {
            width: 2.5rem;
            height: 2.5rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            border: 2px solid rgba(148, 163, 184, .32);
            background: #1f2937;
            color: rgba(226, 232, 240, .88);
            box-shadow: 0 10px 24px rgba(2, 6, 23, .24);
        }

        .invoice-stepper-item.is-complete .invoice-stepper-node {
            background: #16a34a;
            border-color: rgba(34, 197, 94, .5);
            color: #fff;
        }

        .invoice-stepper-item.is-current .invoice-stepper-node {
            --invoice-step-pulse: rgba(96, 165, 250, .32);
            background: #2563eb;
            border-color: rgba(96, 165, 250, .5);
            color: #fff;
            box-shadow: 0 0 0 10px rgba(59, 130, 246, .12);
            animation: invoiceStepPulse 1.8s ease-in-out infinite;
        }

        .invoice-stepper-item.is-current-danger .invoice-stepper-node {
            --invoice-step-pulse: rgba(244, 114, 182, .28);
            background: #db2777;
            border-color: rgba(244, 114, 182, .48);
            color: #fff;
            box-shadow: 0 0 0 10px rgba(236, 72, 153, .12);
            animation: invoiceStepPulse 1.8s ease-in-out infinite;
        }

        .invoice-stepper-item.is-waiting .invoice-stepper-node {
            background: rgba(31, 41, 55, .85);
            color: rgba(203, 213, 225, .75);
        }

        .invoice-stepper-node svg {
            width: 1.1rem;
            height: 1.1rem;
        }

        .invoice-stepper-title {
            margin-top: 1rem;
            font-size: .98rem;
            font-weight: 700;
            color: #fff;
        }

        .invoice-stepper-item.is-complete .invoice-stepper-title {
            color: #4ade80;
        }

        .invoice-stepper-item.is-current .invoice-stepper-title {
            color: #93c5fd;
        }

        .invoice-stepper-item.is-current-danger .invoice-stepper-title {
            color: #f9a8d4;
        }

        .invoice-stepper-description {
            margin-top: .3rem;
            color: rgba(226, 232, 240, .72);
            font-size: .78rem;
            line-height: 1.5;
        }

        .invoice-summary-box {
            border-radius: 1rem;
            background: linear-gradient(180deg, rgba(255, 255, 255, .03), rgba(255, 255, 255, .012));
            border: 1px solid rgba(255, 255, 255, .06);
            box-shadow: 0 20px 45px rgba(2, 6, 23, .14);
        }

        .invoice-warning-box {
            border-radius: 1rem;
            border-left-width: 6px;
            box-shadow: 0 18px 40px rgba(2, 6, 23, .12);
        }

        .invoice-status-live {
            animation: invoiceStatusPulse .8s cubic-bezier(.22, 1, .36, 1);
        }

        .invoice-qr-scanline {
            position: absolute;
            inset: 0;
            overflow: hidden;
            pointer-events: none;
        }

        .invoice-qr-scanline::after {
            content: '';
            position: absolute;
            left: 8%;
            right: 8%;
            height: 12px;
            top: -18%;
            border-radius: 999px;
            background: linear-gradient(180deg, rgba(59, 130, 246, 0), rgba(59, 130, 246, .55), rgba(59, 130, 246, 0));
            filter: blur(6px);
            animation: invoiceScanline 2.8s ease-in-out infinite;
        }

        @keyframes invoiceFadeUp {
            from {
                opacity: 0;
                transform: translateY(22px) scale(0.985);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes invoiceScaleIn {
            from {
                opacity: 0;
                transform: scale(.92);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        @keyframes invoiceIntroOrbIn {
            0% {
                opacity: 0;
                transform: translate(-50%, -50%) scale(.2) rotate(-16deg);
            }

            60% {
                opacity: 1;
                transform: translate(-50%, -50%) scale(1.06) rotate(4deg);
            }

            100% {
                opacity: 1;
                transform: translate(-50%, -50%) scale(1) rotate(0);
            }
        }

        @keyframes invoiceIntroRingIn {
            0% {
                opacity: 0;
                transform: translate(-50%, -50%) scale(.7) rotate(-20deg);
            }

            100% {
                opacity: 1;
                transform: translate(-50%, -50%) scale(1) rotate(0);
            }
        }

        @keyframes invoiceIntroRingSpin {
            0% {
                transform: translate(-50%, -50%) rotate(0deg);
            }

            100% {
                transform: translate(-50%, -50%) rotate(360deg);
            }
        }

        @keyframes invoiceIntroCardPop {
            0% {
                opacity: 0;
                transform: translate(-50%, 10%) scale(.76) rotate(-10deg);
            }

            100% {
                opacity: 1;
                transform: translate(-50%, -50%) scale(1) rotate(0);
            }
        }

        @keyframes invoiceIntroArrowMove {
            0% {
                opacity: 0;
                transform: translate(-25%, -50%) scale(.5) rotate(-8deg);
            }

            100% {
                opacity: 1;
                transform: translate(-50%, -50%) scale(1);
            }
        }

        @keyframes invoiceIntroChipIn {
            0% {
                opacity: 0;
                transform: translate(-50%, -20%) scale(.6);
            }

            100% {
                opacity: 1;
                transform: translate(-50%, -50%) scale(1);
            }
        }

        @keyframes invoiceIntroChipPulse {
            0%,
            100% {
                transform: translate(-50%, -50%) scale(1);
            }

            40% {
                transform: translate(-50%, -50%) scale(.92);
            }

            70% {
                transform: translate(-50%, -50%) scale(1.04);
            }
        }

        @keyframes invoiceIntroStageFloat {
            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        @keyframes invoiceGlowFloat {
            0%,
            100% {
                transform: translate3d(0, 0, 0);
            }

            50% {
                transform: translate3d(18px, 10px, 0);
            }
        }

        @keyframes invoiceBorderPulse {
            0%,
            100% {
                opacity: .62;
                transform: scale(1);
            }

            50% {
                opacity: 1;
                transform: scale(1.015);
            }
        }

        @keyframes invoiceBreath {
            0%,
            100% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(234, 179, 8, .35);
            }

            50% {
                transform: scale(1.05);
                box-shadow: 0 0 0 10px rgba(234, 179, 8, 0);
            }
        }

        @keyframes invoiceButtonSweep {
            0% {
                transform: translateX(-140%);
            }

            100% {
                transform: translateX(140%);
            }
        }

        @keyframes invoiceScanline {
            0% {
                top: -18%;
                opacity: 0;
            }

            18% {
                opacity: .95;
            }

            50% {
                top: 48%;
                opacity: .75;
            }

            100% {
                top: 118%;
                opacity: 0;
            }
        }

        @keyframes invoiceStatusPulse {
            0% {
                transform: scale(.9);
                opacity: .2;
            }

            60% {
                transform: scale(1.06);
                opacity: 1;
            }

            100% {
                transform: scale(1);
            }
        }

        @keyframes invoiceStepPulse {
            0%,
            100% {
                transform: scale(1);
                box-shadow: 0 0 0 0 var(--invoice-step-pulse, rgba(96, 165, 250, .32));
            }

            50% {
                transform: scale(1.08);
                box-shadow: 0 0 0 14px rgba(96, 165, 250, 0);
            }
        }

        @keyframes invoiceHeroFloat {
            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-8px);
            }
        }

        @media (max-width: 1279px) {
            .invoice-intro-overlay {
                padding: 1.4rem .9rem;
            }

            .invoice-intro-card {
                width: min(100%, 52rem);
            }

            .invoice-intro-card--glass {
                padding: 1.1rem 1rem 1.25rem;
            }

            .invoice-intro-stage-custom {
                width: min(56vw, 18rem);
                height: min(56vw, 18rem);
                margin-bottom: 1.1rem;
            }

            .invoice-intro-lottie-shell {
                width: min(56vw, 19rem);
                margin-bottom: 1rem;
            }

            .invoice-intro-status {
                margin-bottom: .75rem;
                padding: .58rem .9rem;
            }

            .invoice-intro-title {
                font-size: clamp(1.85rem, 3.5vw, 2.5rem);
            }

            .invoice-intro-subtitle {
                margin-top: .7rem;
                max-width: 32rem;
                font-size: .95rem;
                line-height: 1.55;
            }
        }

        @media (max-width: 1279px) and (max-height: 700px) {
            .invoice-intro-stage-custom {
                width: min(42vw, 12.5rem);
                height: min(42vw, 12.5rem);
                margin-bottom: .65rem;
            }

            .invoice-intro-ring,
            .invoice-intro-chip {
                display: none;
            }

            .invoice-intro-card-icon {
                width: 40%;
                height: 30%;
                top: 45%;
                left: 46%;
            }

            .invoice-intro-arrow {
                width: 20%;
                height: 20%;
                top: 50%;
                left: 55%;
            }

            .invoice-intro-lottie-shell {
                width: min(42vw, 13.5rem);
                margin-bottom: .6rem;
            }

            .invoice-intro-status {
                margin-bottom: .5rem;
            }

            .invoice-intro-status-icon {
                width: 1.9rem;
                height: 1.9rem;
            }

            .invoice-intro-status-text {
                font-size: .74rem;
                letter-spacing: .09em;
            }

            .invoice-intro-title {
                margin-bottom: .35rem;
                font-size: clamp(1.55rem, 3vw, 2rem);
            }

            .invoice-intro-subtitle {
                max-width: 30rem;
                font-size: .88rem;
                line-height: 1.45;
            }
        }

        @media (min-width: 1280px) and (max-height: 760px) {
            .invoice-intro-overlay {
                padding: .95rem .85rem;
            }

            .invoice-intro-card {
                width: min(100%, 50rem);
            }

            .invoice-intro-card--glass {
                padding: .9rem .95rem 1rem;
                border-radius: 1rem;
            }

            .invoice-intro-stage-custom {
                width: min(34vw, 13rem);
                height: min(34vw, 13rem);
                margin-bottom: .55rem;
            }

            .invoice-intro-ring,
            .invoice-intro-chip {
                display: none;
            }

            .invoice-intro-card-icon {
                width: 40%;
                height: 30%;
                top: 45%;
                left: 46%;
            }

            .invoice-intro-arrow {
                width: 20%;
                height: 20%;
                left: 55%;
                top: 50%;
            }

            .invoice-intro-lottie-shell {
                width: min(34vw, 14rem);
                margin-bottom: .55rem;
            }

            .invoice-intro-status {
                margin-bottom: .42rem;
                padding: .5rem .78rem;
            }

            .invoice-intro-status-icon {
                width: 1.9rem;
                height: 1.9rem;
            }

            .invoice-intro-status-text {
                font-size: .73rem;
                letter-spacing: .085em;
            }

            .invoice-intro-title {
                margin-bottom: .3rem;
                font-size: clamp(1.5rem, 3vw, 2rem);
            }

            .invoice-intro-subtitle {
                margin-top: .45rem;
                max-width: 32rem;
                font-size: .88rem;
                line-height: 1.4;
            }
        }

        @media (max-width: 767px) {
            .invoice-hero-block,
            .invoice-detail-card,
            .invoice-payment-card,
            .invoice-summary-box,
            .invoice-progress-card {
                border-radius: 1.1rem;
            }

            .invoice-status-banner {
                padding-top: 3.4rem;
                padding-bottom: 2.6rem;
            }

            .invoice-status-banner__panel {
                padding: 1.05rem .85rem;
            }

            .invoice-status-banner__icon {
                width: 3.9rem;
                height: 3.9rem;
                margin-bottom: 1rem;
            }

            .invoice-status-banner__title {
                font-size: 1.8rem;
            }

            .invoice-status-banner__description {
                font-size: .9rem;
                line-height: 1.55;
            }

            .invoice-expiry-chip {
                min-width: 100%;
            }

            .invoice-stepper-grid {
                grid-template-columns: 1fr;
                margin-top: 1rem;
            }

            .invoice-stepper-track {
                display: none;
            }

            .invoice-intro-stage-custom {
                width: min(58vw, 8.5rem);
                height: min(58vw, 8.5rem);
                margin-bottom: .9rem;
            }

            .invoice-intro-orb {
                width: 58%;
                height: 58%;
            }

            .invoice-intro-ring,
            .invoice-intro-chip {
                display: none;
            }

            .invoice-intro-card-icon {
                width: 42%;
                height: 31%;
                top: 45%;
                left: 46%;
                border-radius: .75rem;
            }

            .invoice-intro-arrow {
                width: 18%;
                height: 18%;
                left: 54%;
                top: 50%;
            }

            .invoice-intro-card--glass {
                width: min(100%, 22rem);
                margin-inline: auto;
                padding: .95rem .8rem 1.05rem;
                border-radius: 1rem;
                background:
                    linear-gradient(180deg, rgba(255, 255, 255, .04), rgba(255, 255, 255, .016)),
                    rgba(15, 23, 42, .18);
                border-color: rgba(255, 255, 255, .12);
                box-shadow: 0 12px 24px rgba(2, 6, 23, .2);
            }

            .invoice-intro-overlay {
                padding: .8rem .65rem;
            }

            .invoice-intro-lottie-shell {
                width: min(62vw, 11rem);
                margin-bottom: .95rem;
                filter: none;
            }

            .invoice-intro-stage-custom {
                filter: none;
            }

            .invoice-intro-status {
                margin-bottom: .55rem;
                padding: .46rem .72rem;
            }

            .invoice-intro-status-text {
                font-size: .68rem;
                letter-spacing: .08em;
            }

            .invoice-intro-title {
                font-size: clamp(1.65rem, 7.8vw, 2.1rem);
                line-height: 1.08;
                margin-bottom: .5rem;
            }

            .invoice-intro-subtitle {
                max-width: 100%;
                font-size: .98rem;
                line-height: 1.45;
            }
        }

        @media (max-width: 1024px) {
            .invoice-intro-card--glass {
                backdrop-filter: blur(8px) saturate(110%);
                -webkit-backdrop-filter: blur(8px) saturate(110%);
            }

            .invoice-intro-stage-custom,
            .invoice-intro-lottie-shell {
                filter: none;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .invoice-intro-overlay,
            .invoice-page-shell,
            .invoice-animate,
            .invoice-amount-pop,
            .invoice-step-active,
            .invoice-hero-glow::before,
            .invoice-qr-frame::before,
            .invoice-pay-button::before,
            .invoice-qr-scanline::after,
            .invoice-status-live,
            .invoice-intro-orb,
            .invoice-intro-ring,
            .invoice-intro-card-icon,
            .invoice-intro-arrow,
            .invoice-intro-chip {
                animation: none !important;
                opacity: 1 !important;
                transform: none !important;
            }
        }

        @media print {
            .invoice-intro-overlay {
                display: none !important;
            }

            .invoice-page-shell {
                opacity: 1 !important;
                visibility: visible !important;
                transform: none !important;
            }

            .invoice-animate,
            .invoice-amount-pop {
                animation: none !important;
                opacity: 1 !important;
                transform: none !important;
            }

            .invoice-hero-glow::before,
            .invoice-qr-frame::before,
            .invoice-panel::after,
            .invoice-shell::before,
            .invoice-pay-button::before,
            .invoice-qr-scanline::after {
                display: none !important;
            }
        }
    </style>
@endsection


@section('content')

    @php
        $paymentCode = Str::upper((string) ($data->metode_pembayaran ?? ''));
        $paymentValue = (string) ($data->no_pembayaran ?? '');
        $paymentStatus = Str::lower(trim((string) ($data->status_pembayaran ?? '')));
        $orderStatus = Str::lower(trim((string) ($data->status_pembelian ?? '')));
        $isPaymentUrl = filter_var($paymentValue, FILTER_VALIDATE_URL) !== false;
        $isQrImage = (
            str_starts_with($paymentValue, 'data:image/') ||
            preg_match('/\.(png|jpe?g|webp|svg)(\?.*)?$/i', $paymentValue) === 1
        );
        $isQrMethod = in_array($paymentCode, [
            'QRIS',
            '11',
            '17',
            '23',
            'QRISREALTIME',
            'SP',
            'QRISC',
            'QRISOP',
            'QRIS_CUSTOM',
            'QRIS2',
            'QRIS2_OFFLINE',
            'QRIS2_RECURRING',
        ], true);
        $showQrImage = $data->status_pembayaran == 'Belum Lunas' &&
            $isQrMethod &&
            $paymentValue !== '';
        $dynamicQrSource = 'https://api.qrserver.com/v1/create-qr-code/?size=320x320&data=' . urlencode($paymentValue);
        $resolvedQrImageUrl = $isQrImage ? $paymentValue : $dynamicQrSource;
        $showPayButton = $data->status_pembayaran == 'Belum Lunas' && $isPaymentUrl && !$showQrImage;
        $showCopyPaymentNumber = !$isPaymentUrl && in_array($paymentCode, [
            'ALFAMRT', 'INDOMARET', 'PERMATAVAA', 'BNCVA', 'BSIVA', 'DANAMONVA', 'CIMBVA', 'PERMATAVA',
            'MANDIRIVA', 'BNIVA', 'BCAVA', 'BC', 'M2', 'VA', 'I1', 'B1', 'BT', 'A1', 'NC', 'BR', 'S1',
            'DM', 'BV', 'IR', 'FT', 'BRIVA', 'DUITKU',
        ], true);

        $heroEyebrow = 'Terima Kasih!';
        $heroTitle = 'Harap lengkapi pembayaran.';
        $heroDescription = 'Pesanan kamu ' . $data->id_pembelian . ' menunggu pembayaran sebelum dikirim.';
        $expiryLabel = 'Pesanan ini akan kedaluwarsa pada';
        $expiryMeta = 'Hitung mundur berjalan otomatis selama invoice masih aktif.';

        if (in_array($paymentStatus, ['paid', 'lunas', 'success'], true)) {
            if (in_array($orderStatus, ['sukses', 'success'], true)) {
                $heroTitle = 'Transaksi berhasil diselesaikan.';
                $heroDescription = 'Pesanan kamu ' . $data->id_pembelian . ' sudah berhasil diproses dan selesai.';
            } elseif (in_array($orderStatus, ['proses', 'processing', 'pending'], true)) {
                $heroTitle = 'Pembayaran sudah diterima.';
                $heroDescription = 'Pesanan kamu ' . $data->id_pembelian . ' sedang diproses oleh sistem dan provider.';
            } else {
                $heroTitle = 'Pembayaran sudah diterima.';
                $heroDescription = 'Pesanan kamu ' . $data->id_pembelian . ' sudah masuk dan sedang menunggu update status transaksi.';
            }

            $expiryLabel = 'Pembayaran diterima pada invoice ini';
            $expiryMeta = 'Invoice sudah dibayar dan tidak lagi menghitung mundur.';
        } elseif ($paymentStatus === 'expired') {
            $heroEyebrow = 'Perhatian';
            $heroTitle = 'Invoice sudah kedaluwarsa.';
            $heroDescription = 'Batas pembayaran untuk pesanan ' . $data->id_pembelian . ' telah habis. Silakan buat transaksi baru jika masih diperlukan.';
            $expiryLabel = 'Batas waktu pembayaran telah berakhir';
            $expiryMeta = 'Invoice ini sudah melewati waktu pembayaran.';
        } elseif (in_array($orderStatus, ['gagal', 'batal', 'failed', 'cancelled'], true)) {
            $heroEyebrow = 'Informasi Transaksi';
            $heroTitle = 'Transaksi tidak dapat diselesaikan.';
            $heroDescription = 'Pesanan kamu ' . $data->id_pembelian . ' mengalami kendala. Silakan cek detail status transaksi di bawah.';
        }

        $introState = 'pending';
        $introTitle = 'Menunggu Pembayaran';
        $introSubtitle = 'Invoice sedang disiapkan. Mohon selesaikan pembayaran agar transaksi dapat dilanjutkan.';
        $introIcon = 'clock';
        $stepperTheme = 'pending';
        $stepperProgress = '24%';
        $progressSteps = [
            [
                'title' => 'Transaksi Dibuat',
                'description' => 'Pesanan berhasil dibuat.',
                'state' => 'complete',
                'icon' => 'check',
            ],
            [
                'title' => 'Pembayaran',
                'description' => 'Menunggu pembayaran pelanggan.',
                'state' => 'current',
                'icon' => 'payment',
            ],
            [
                'title' => 'Sedang Diproses',
                'description' => 'Transaksi belum masuk ke provider.',
                'state' => 'waiting',
                'icon' => 'process',
            ],
            [
                'title' => 'Transaksi Selesai',
                'description' => 'Menunggu transaksi selesai.',
                'state' => 'waiting',
                'icon' => 'done',
            ],
        ];

        if (in_array($paymentStatus, ['paid', 'lunas', 'success'], true)) {
            $progressSteps[1]['state'] = 'complete';
            $progressSteps[1]['description'] = 'Pembayaran berhasil diterima.';
            $stepperTheme = 'paid';

            if (in_array($orderStatus, ['sukses', 'success'], true)) {
                $introState = 'paid';
                $introTitle = 'Transaksi Berhasil';
                $introSubtitle = 'Pembayaran berhasil diterima dan transaksi telah selesai diproses.';
                $introIcon = 'check';
                $stepperProgress = '100%';
                $progressSteps[2]['state'] = 'complete';
                $progressSteps[2]['description'] = 'Provider berhasil memproses transaksi.';
                $progressSteps[3]['state'] = 'complete';
                $progressSteps[3]['description'] = 'Transaksi berhasil diselesaikan.';
            } else {
                $introState = 'paid';
                $introTitle = 'Pembayaran Diterima';
                $introSubtitle = 'Pembayaran berhasil diterima. Sistem sedang menyelesaikan proses transaksi.';
                $introIcon = 'check';
                $stepperProgress = '71%';
                $progressSteps[2]['state'] = 'current';
                $progressSteps[2]['description'] = 'Pembelian sedang dalam proses provider.';
            }
        } elseif ($paymentStatus === 'expired') {
            $introState = 'expired';
            $introTitle = 'Pembayaran Kedaluwarsa';
            $introSubtitle = 'Batas waktu pembayaran telah berakhir. Silakan lakukan pembelian ulang jika masih diperlukan.';
            $introIcon = 'x';
            $stepperTheme = 'expired';
            $stepperProgress = '46%';
            $progressSteps[1]['state'] = 'current-danger';
            $progressSteps[1]['description'] = 'Batas pembayaran telah berakhir.';
        } elseif (in_array($orderStatus, ['gagal', 'batal', 'failed', 'cancelled'], true)) {
            $introState = 'failed';
            $introTitle = 'Transaksi Gagal';
            $introSubtitle = 'Transaksi tidak dapat diselesaikan. Silakan cek detail invoice untuk informasi lebih lanjut.';
            $introIcon = 'warning';
            $stepperTheme = 'failed';
            $stepperProgress = '72%';
            $progressSteps[1]['state'] = 'complete';
            $progressSteps[1]['description'] = 'Pembayaran berhasil diterima.';
            $progressSteps[2]['state'] = 'current-danger';
            $progressSteps[2]['description'] = 'Provider gagal menyelesaikan transaksi.';
        }

        $introDuration = match ($introState) {
            'expired' => 4700,
            'paid' => 4300,
            'failed' => 4500,
            default => 4000,
        };

        $introBadgeText = match ($introState) {
            'expired' => 'Pembayaran Kedaluwarsa',
            'paid' => 'Pembayaran Diterima',
            'failed' => 'Transaksi Gagal',
            default => 'Menunggu Pembayaran',
        };

        $introUsesLottie = in_array($introState, ['expired', 'failed'], true);
        $introLottieSrc = $introUsesLottie
            ? asset('assets/invoice-intro/lottie/' . ($introState === 'expired' ? 'expired.json' : 'failed.json'))
            : null;

        $toastTone = 'pending';
        $toastTitle = 'Pembelian berhasil dibuat';
        $toastMessage = 'Silakan lakukan pembayaran untuk melanjutkan proses transaksi.';

        if ($paymentStatus === 'expired') {
            $toastTone = 'expired';
            $toastTitle = 'Pembayaran kedaluwarsa';
            $toastMessage = 'Batas waktu pembayaran telah berakhir. Silakan buat invoice baru jika masih diperlukan.';
        } elseif ($orderStatus === 'failed') {
            $toastTone = 'failed';
            $toastTitle = 'Transaksi gagal diproses';
            $toastMessage = 'Provider belum dapat menyelesaikan pesanan. Silakan cek detail invoice atau hubungi admin.';
        } elseif (in_array($paymentStatus, ['paid', 'lunas', 'success'], true) && in_array($orderStatus, ['sukses', 'success'], true)) {
            $toastTone = 'success';
            $toastTitle = 'Transaksi berhasil';
            $toastMessage = 'Pembayaran dan transaksi sudah selesai diproses.';
        } elseif (in_array($paymentStatus, ['paid', 'lunas', 'success'], true)) {
            $toastTone = 'paid';
            $toastTitle = 'Pembayaran diterima';
            $toastMessage = 'Pembayaran berhasil diterima. Sistem sedang memproses pesanan kamu.';
        }
    @endphp

    <div id="invoiceEntryToast" class="invoice-toast print:hidden" data-tone="{{ $toastTone }}">
        <span class="invoice-toast__icon" aria-hidden="true">
            @if (in_array($toastTone, ['paid', 'success'], true))
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
            @elseif (in_array($toastTone, ['expired', 'failed'], true))
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            @else
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            @endif
        </span>
        <span>
            <div class="invoice-toast__title">{{ $toastTitle }}</div>
            <div class="invoice-toast__body">{{ $toastMessage }}</div>
        </span>
    </div>
    <div id="invoiceIntroOverlay" class="invoice-intro-overlay is-visible print:hidden" data-state="{{ $introState }}" data-duration="{{ $introDuration }}">
        <div class="invoice-intro-card invoice-intro-card--glass">
            @if ($introUsesLottie)
                <div class="invoice-intro-lottie-shell" aria-hidden="true">
                    <lottie-player
                        id="invoiceIntroLottie"
                        src="{{ $introLottieSrc }}"
                        background="transparent"
                        speed="1"
                        autoplay
                    ></lottie-player>
                </div>
            @else
                <div id="invoiceIntroStageCustom" class="invoice-intro-stage-custom" aria-hidden="true">
                    <span class="invoice-intro-orb"></span>
                    <span class="invoice-intro-ring"></span>
                    <span class="invoice-intro-card-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M3.75 6.75h16.5A1.5 1.5 0 0121.75 8.25v7.5a1.5 1.5 0 01-1.5 1.5H3.75a1.5 1.5 0 01-1.5-1.5v-7.5a1.5 1.5 0 011.5-1.5zM6 15h.008v.008H6V15zm3 0h2.25" />
                        </svg>
                    </span>
                    <span class="invoice-intro-arrow">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 12.75L12 17.25l4.5-4.5M12 6.75v10.5" />
                        </svg>
                    </span>
                    <span class="invoice-intro-chip">Invoice</span>
                </div>
            @endif
            <div class="invoice-intro-status">
                <span class="invoice-intro-status-icon" aria-hidden="true">
                    @if ($introIcon === 'check')
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                    @elseif ($introIcon === 'x')
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    @elseif ($introIcon === 'warning')
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9.303 3.376c.866 1.5-.217 3.374-1.95 3.374H4.647c-1.733 0-2.816-1.874-1.95-3.374L10.05 3.378c.866-1.5 3.034-1.5 3.9 0l7.353 12.748zM12 16.5h.008v.008H12V16.5z" />
                        </svg>
                    @else
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    @endif
                </span>
                <span class="invoice-intro-status-text">{{ $introBadgeText }}</span>
            </div>
            <h2 class="invoice-intro-title">{{ $introTitle }}</h2>
            <p class="invoice-intro-subtitle">{{ $introSubtitle }}</p>
        </div>
    </div>

    <div
        id="invoicePageShell"
        class="invoice-page-shell print:!text-slate-800"
        style="opacity:0;visibility:hidden;transform:translateY(32px) scale(.985);"
    >
        @include('../navbar')
        <section class="invoice-status-banner invoice-animate print:hidden" data-tone="{{ $introState }}">
            <div class="container">
                <div class="invoice-status-banner__panel">
                    <span class="invoice-status-banner__icon" aria-hidden="true">
                        @if ($introIcon === 'check')
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                        @elseif ($introIcon === 'x')
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        @elseif ($introIcon === 'warning')
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9.303 3.376c.866 1.5-.217 3.374-1.95 3.374H4.647c-1.733 0-2.816-1.874-1.95-3.374L10.05 3.378c.866-1.5 3.034-1.5 3.9 0l7.353 12.748zM12 16.5h.008v.008H12V16.5z" />
                            </svg>
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        @endif
                    </span>
                    <h1 class="invoice-status-banner__title">{{ $heroTitle }}</h1>
                    <p class="invoice-status-banner__description">{!! nl2br(e($heroDescription)) !!}</p>
                </div>
            </div>
        </section>
        <div class="invoice-shell container py-10 print:py-8 md:py-8">
            <div class="invoice-animate invoice-animate-delay-1 mt-6 flex flex-col items-end justify-between gap-8 print:flex-row md:flex-row">
                <dl class="invoice-expiry-card w-full text-left text-sm font-medium md:w-auto">
                    <dt class="text-white print:text-slate-800">{{ $expiryLabel }}</dt>
                    <dd class="mt-2 text-primary-500">
                        <div id="invoiceExpiryCountdown"
                            class="invoice-expiry-chip rounded-md bg-rose-500 px-4 py-2 text-center text-white print:p-0 print:text-left print:text-slate-800"
                            data-expired-at="{{ $expiredIso }}"
                            data-status="{{ $data->status_pembayaran }}">
                            {{ $expired }}</div>
                        <div id="invoiceExpiryMeta" class="invoice-expiry-meta">{{ $expiryMeta }}</div>
                    </dd>
                </dl>
            </div>
            <div class="invoice-progress-card invoice-animate invoice-animate-delay-2 mt-6 flex flex-col items-start gap-4 pt-4">
                <h3 class="text-sm font-semibold">Progress Transaksi</h3>
                <div class="invoice-stepper" data-theme="{{ $stepperTheme }}" style="--progress-width: {{ $stepperProgress }};">
                    <div class="invoice-stepper-track">
                        <div class="invoice-stepper-fill"></div>
                    </div>
                    <div class="invoice-stepper-grid">
                        @foreach ($progressSteps as $step)
                            <div class="invoice-stepper-item is-{{ $step['state'] }}">
                                <span class="invoice-stepper-node">
                                    @if ($step['icon'] === 'check')
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                        </svg>
                                    @elseif ($step['icon'] === 'payment')
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M3.75 6.75h16.5A1.5 1.5 0 0121.75 8.25v7.5a1.5 1.5 0 01-1.5 1.5H3.75a1.5 1.5 0 01-1.5-1.5v-7.5a1.5 1.5 0 011.5-1.5zM6 15h.008v.008H6V15zm3 0h2.25" />
                                        </svg>
                                    @elseif ($step['icon'] === 'process')
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    @else
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m6 2.25a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    @endif
                                </span>
                                <div class="invoice-stepper-title">{{ $step['title'] }}</div>
                                <div class="invoice-stepper-description">{{ $step['description'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="invoice-divider-block invoice-animate invoice-animate-delay-3 my-8 border-y border-murky-600 py-8">
                <div class="grid grid-cols-2 gap-8">
                    <div class="invoice-detail-card invoice-panel rounded-2xl col-span-2 flex gap-8 lg:col-span-1">
                        <div
                            class="invoice-thumbnail-frame relative mt-2 aspect-[4/6] h-32 flex-none overflow-hidden rounded-lg bg-murky-600 object-cover object-center print:hidden sm:h-56 md:mt-0 md:block">
                            <img alt="{{ $namas }}" fetchpriority="high" decoding="async" data-nimg="fill"
                                class="object-cover object-center" sizes="100vw" src="{{ asset($thumbnails) }}"
                                style="position: absolute; height: 100%; width: 100%; inset: 0px; color: transparent;" />
                        </div>
                        <div>
                            <h3 class="text-lg font-medium text-white print:text-sm print:text-slate-800">
                                <span href="" style="outline: none;">{{ $namas }}</span>
                            </h3>
                            <p class="text-sm">{{ $data->layanan }}</p>
                            <div>

                                @if ($data->tipe_transaksi == 'joki')
                                    <div class="mt-8 text-sm font-medium text-murky-200 print:text-slate-800">
                                        <div class="grid grid-cols-3 gap-4 pb-2">
                                            <div class="text-white print:text-slate-800">Email :</div>
                                            <div class="col-span-2">
                                                <p class="break-words">Censored</p>
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-3 gap-4 pb-2">
                                            <div class="text-white print:text-slate-800">Password :</div>
                                            <div class="col-span-2">
                                                <p class="break-words">Censored</p>
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-3 gap-4 pb-2">
                                            <div class="text-white print:text-slate-800">Login Via :</div>
                                            <div class="col-span-2">
                                                <p class="break-words">{{ $data->loginvia_joki }}</p>
                                            </div>
                                        </div>
                                        @if($data->nickname_joki)
                                        <div class="grid grid-cols-3 gap-4 pb-2">
                                            <div class="text-white print:text-slate-800">NIckname :</div>
                                            <div class="col-span-2">
                                                <p class="break-words">{{ $data->nickname_joki }}</p>
                                            </div>
                                        </div>
                                        @endif
                                        <div class="grid grid-cols-3 gap-4 pb-2">
                                            <div class="text-white print:text-slate-800">Request :</div>
                                            <div class="col-span-2">
                                                <p class="break-words">{{ $data->request_joki }}</p>
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-3 gap-4 pb-2">
                                            <div class="text-white print:text-slate-800">Catatan :</div>
                                            <div class="col-span-2">
                                                <p class="break-words">{{ $data->catatan_joki }}</p>
                                            </div>
                                        </div>
                                    </div>
                                @elseif($data->tipe_transaksi == 'jokigendong')
                                    <div class="mt-8 text-sm font-medium text-murky-200 print:text-slate-800">
                                        <div class="grid grid-cols-3 gap-4 pb-2">
                                            <div class="text-white print:text-slate-800">Catatan :</div>
                                            <div class="col-span-2">
                                                <p class="break-words">{{ $data->catatan_joki }}</p>
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-3 gap-4 pb-2">
                                            <div class="text-white print:text-slate-800">Role :</div>
                                            <div class="col-span-2">
                                                <p class="break-words">{{ $data->loginvia_joki }}</p>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="mt-8 text-sm font-medium text-murky-200 print:text-slate-800">
                                        @if($data->nickname)
                                        <div class="grid grid-cols-3 gap-4 pb-2">
                                            <div class="text-white print:text-slate-800">Nickname</div>
                                            <div class="col-span-2">
                                                <p class="break-words">{{ $data->nickname }}</p>
                                            </div>
                                        </div>
                                        @endif
                                        <div class="grid grid-cols-3 gap-4 pb-2">
                                            <div class="text-white print:text-slate-800">ID</div>
                                            <div class="col-span-2">
                                                <p class="break-words">{{ $data->user_id }}
                                                    {{ $data->zone != null ? '(' . $data->zone . ')' : '' }}</p>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-span-2 row-span-3 lg:col-span-1">
                        <div class="invoice-payment-card invoice-panel rounded-2xl w-full flex-1 print:pt-0 md:flex-auto md:pt-0">
                            <dl class="gap-x-8 text-sm">
                                <div class="w-full">
                                    <div class="invoice-payment-heading">
                                        <dt class="text-lg font-medium text-white print:text-sm print:text-slate-800">Metode
                                            Pembayaran</dt>
                                        <span class="invoice-method-pill">{{ $metode_name }}</span>
                                    </div>
                                    <dd class="text-murky-200">
                                        <div class="flex items-start space-x-4 print:text-slate-800">
                                            <div class="text-sm text-white">{{ $metode_name }}</div>
                                        </div>
                                        @if ($showCopyPaymentNumber)
                                            <div
                                                class="col-span-3 flex items-center text-white print:text-slate-800 md:col-span-4 mt-3 mb-2">
                                                No Pembayaran</div>
                                            <div class="col-span-5 text-white print:text-slate-800 md:col-span-4">
                                                <button type="button"
                                                    class="invoice-copy-button flex items-center space-x-2 rounded-md border border-murky-400 bg-murky-600 px-2.5 py-1 hover:bg-murky-700 print:hidden"
                                                    onclick="copyNoPembayaranToClipboard()">
                                                    <div class="max-w-[172px] truncate md:w-auto md:max-w-none"
                                                        id="noPembayaran">{{ $data->no_pembayaran }}</div>
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                        aria-hidden="true" class="h-5 w-4">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184">
                                                        </path>
                                                    </svg>
                                                </button>
                                                <span class="hidden print:block"></span>
                                            </div>
                                        @endif
                                    </dd>
                                    <div
                                        class="mt-8 grid w-full grid-cols-8 gap-4 border-t border-murky-600 pt-8 text-left text-murky-200 print:border-slate-200 print:text-slate-800 md:gap-x-2">
                                        <div
                                            class="col-span-3 flex items-center text-white print:text-slate-800 md:col-span-4">
                                            Nomor Invoice</div>
                                        <div class="col-span-5 text-white print:text-slate-800 md:col-span-4">
                                            <button type="button"
                                                class="flex items-center space-x-2 rounded-md border border-murky-400 bg-murky-600 px-2.5 py-1 hover:bg-murky-700 print:hidden"
                                                onclick="copyToClipboard()">
                                                <div class="max-w-[172px] truncate md:w-auto md:max-w-none"
                                                    id="invoicePembelian">{{ $data->id_pembelian }}</div>
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                    aria-hidden="true" class="h-5 w-4">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184">
                                                    </path>
                                                </svg>
                                            </button>
                                            <span class="hidden print:block"></span>
                                        </div>
                                        <!--conditional status pembelian & pembayaran-->
                                        @php
                                            $statuscolor = '';

                                            if ($data->status_pembelian == 'Pending') {
                                                $statuscolor = 'yellow';
                                            } elseif (
                                                $data->status_pembelian == 'Sukses' ||
                                                $data->status_pembelian == 'Success'
                                            ) {
                                                $statuscolor = 'green';
                                            } elseif ($data->status_pembelian == 'Proses' || $data->status_pembelian == 'Processing') {
                                                $statuscolor = 'cyan';
                                            } else {
                                                $statuscolor = 'rose';
                                            }
                                        @endphp
                                        <div class="invoice-status-row col-span-3 text-white print:text-slate-800 md:col-span-4">Status Transaksi</div>
                                        <div class="col-span-5 md:col-span-4">
                                            @if ($data->status_pembelian == 'Pending')
                                                <span id="invoiceStatusPembelian" class="invoice-badge invoice-badge-warning">Pending</span>
                                            @elseif($data->status_pembelian == 'Proses' || $data->status_pembelian == 'Processing')
                                                <span id="invoiceStatusPembelian" class="invoice-badge invoice-badge-info">Process</span>
                                            @elseif($data->status_pembelian == 'Batal' || $data->status_pembelian == 'Gagal')
                                                <span id="invoiceStatusPembelian" class="invoice-badge invoice-badge-danger">Cancelled</span>
                                            @elseif($data->status_pembelian == 'Sukses' || $data->status_pembelian == 'Success')
                                                <span id="invoiceStatusPembelian" class="invoice-badge invoice-badge-success">Success</span>
                                            @endif
                                        </div>
                                        @php
                                            $pembayarancolor = '';

                                            if ($data->status_pembayaran == 'Belum Lunas') {
                                                $pembayarancolor = 'rose';
                                            } elseif (
                                                $data->status_pembayaran == 'PAID' ||
                                                $data->status_pembayaran == 'Lunas'
                                            ) {
                                                $pembayarancolor = 'cyan';
                                            } else {
                                                $pembayarancolor = 'rose';
                                            }
                                        @endphp
                                        <div class="invoice-status-row col-span-3 text-white print:text-slate-800 md:col-span-4">Status
                                            Pembayaran</div>
                                        <div class="col-span-5 md:col-span-4">
                                            @if ($data->status_pembayaran == 'Belum Lunas')
                                                <span id="badge-unpaid" class="invoice-badge invoice-badge-danger">Unpaid</span>
                                            @elseif($data->status_pembayaran == 'PAID' || $data->status_pembayaran == 'Lunas')
                                                <span id="badge-unpaid" class="invoice-badge invoice-badge-success">Paid</span>
                                            @else
                                                <span id="badge-unpaid" class="invoice-badge invoice-badge-warning">Expired</span>
                                            @endif
                                        </div>
                                        @php
                                            $snValue = $data->voucher ?: $data->keterangan_sn;
                                        @endphp
                                        @if ($snValue)
                                            <div
                                                class="col-span-3 flex items-center text-white print:text-slate-800 md:col-span-4">
                                                Keterangan / SN</div>
                                            <div class="col-span-5 text-white print:text-slate-800 md:col-span-4">
                                                <button onclick="copyToClipboardsn()" type="button"
                                                    class="invoice-copy-button flex items-center space-x-2 rounded-md border border-murky-400 bg-murky-600 px-2.5 py-1 hover:bg-murky-700 print:hidden">
                                                    <div class="max-w-[172px] truncate md:w-auto md:max-w-none"
                                                        id="sn">{{ $snValue }}</div>
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                        aria-hidden="true" class="h-5 w-4">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184">
                                                        </path>
                                                    </svg>
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </dl>
                            @if ($data->status_pembayaran == 'Belum Lunas')
                                @if ($showQrImage)
                                    <div
                                        class="invoice-qr-frame invoice-animate invoice-animate-delay-3 relative mt-8 flex h-64 w-64 items-center justify-center overflow-hidden rounded-lg bg-white sm:h-56 sm:w-56">
                                        <div class="invoice-qr-scanline"></div>
                                        <div id="qris-payment">
                                            <center><img id="qrisPaymentImage" src="{{ $resolvedQrImageUrl }}" width="200"
                                                    alt="Kode QR Pembayaran"></center>
                                        </div>
                                    </div>
                                    @if ($isPaymentUrl && !$isQrImage)
                                        <a class="invoice-animate invoice-animate-delay-4 mt-2 inline-flex items-center justify-center rounded-md bg-primary-500 px-4 py-2 text-xs font-medium text-white duration-300 hover:bg-primary-400 w-64 sm:w-56"
                                            target="_blank" href="{{ $paymentValue }}" rel="noopener noreferrer">
                                            Buka Link Pembayaran
                                        </a>
                                    @endif
                                @elseif($showPayButton)
                                    <a class="invoice-animate invoice-animate-delay-3" target="_blank" href="{{ $data->no_pembayaran }}"><button
                                            class="invoice-pay-button mt-8 inline-flex items-center justify-center rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white duration-300 hover:bg-primary-400 disabled:cursor-not-allowed disabled:opacity-75 w-full space-x-2 pr-3 sm:w-auto"
                                            type="button"><span>Bayar Sekarang</span><svg
                                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                stroke-width="1.5" stroke="currentColor" aria-hidden="true"
                                                class="h-4 w-4">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25">
                                                </path>
                                            </svg></button></a>
                                @else
                                    <div class="max-w-[172px] truncate md:w-auto md:max-w-none">
                                        <span id="hargaPembayaran"></span>
                                    </div>
                                @endif


                                @if ($showQrImage)
                                    <button
                                        class="invoice-animate invoice-animate-delay-4 inline-flex items-center justify-center rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white duration-300 hover:bg-primary-400 disabled:cursor-not-allowed disabled:opacity-75 mt-2 w-64 py-1 !text-xs print:hidden sm:w-56"
                                        type="button" onclick="downloadQRCode()">
                                        Unduh Kode QR / Screenshoot
                                    </button>
                                @endif
                            @endif
                            @if (
                                $data->status_pembelian == 'Sukses' ||
                                    $data->status_pembelian == 'Success' ||
                                    $data->status_pembelian == 'Processing' ||
                                    $data->status_pembelian == 'Proses')
                                <div class="pt-8 print:hidden">
                                    <form id="myForm"
                                        action="{{ route('rating.pembelian', ['order' => $data->id_pembelian]) }}"
                                        method="POST">
                                        @csrf
                                        <div class="font-semibold">Tinggalkan ulasan untuk transaksi ini.</div>
                                        <div class="flex items-center star-rating">
                                            <span class="fa fa-star-o" data-rating="1"></span>
                                            <span class="fa fa-star-o" data-rating="2"></span>
                                            <span class="fa fa-star-o" data-rating="3"></span>
                                            <span class="fa fa-star-o" data-rating="4"></span>
                                            <span class="fa fa-star-o" data-rating="5"></span>
                                            <input type="hidden" name="bintang" class="rating-value" value="0" />
                                        </div>
                                        <input type="hidden" name="kategori_nama" value="{{ $namas }}">
                                        <div>
                                            <label for="pesanTextArea"
                                                class="flex items-center justify-between text-sm font-medium leading-6 text-white">
                                                <div>Tambahkan ulasan Kamu</div>
                                            </label>
                                            <div class="my-2 flex flex-wrap gap-1">
                                                <!-- Tambahkan elemen di sini jika diperlukan -->
                                            </div>
                                            <div class="mt-2">
                                                <textarea rows="4" id="pesanTextArea" placeholder="Tulis review kamu disini ..."
                                                    class="block w-full rounded-md border-0 text-black py-1.5 text-sm leading-6 shadow-sm  focus:ring-2 focus:ring-inset focus:ring-primary-500"
                                                    name="comment"></textarea>
                                            </div>
                                        </div>
                                        <div class="flex-shrink-0 mt-2">
                                            <button id="melpa"
                                                class="inline-flex items-center justify-center rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-text-color-foreground transition-colors duration-300 hover:bg-primary-400 disabled:cursor-not-allowed disabled:opacity-75"
                                                type="submit">Kirim</button>
                                        </div>
                                    </form>

                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="invoice-animate invoice-animate-delay-3 col-span-2 col-start-1 row-start-2 lg:col-span-1">

                        <button
                            class="flex w-full justify-between rounded-lg bg-murky-800 px-4 py-2 text-left text-sm font-medium text-white duration-200 ease-in-out hover:bg-murky-800 focus:outline-none"
                            id="toggleButton" type="button" aria-expanded="true" data-headlessui-state="open"
                            aria-controls="headlessui-disclosure-panel-:r6r:">
                            <span>Rincian Pembayaran</span>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                                aria-hidden="true" class="rotate-180 transform h-5 w-5 text-white">
                                <path fill-rule="evenodd"
                                    d="M14.77 12.79a.75.75 0 01-1.06-.02L10 8.832 6.29 12.77a.75.75 0 11-1.08-1.04l4.25-4.5a.75.75 0 011.08 0l4.25 4.5a.75.75 0 01-.02 1.06z"
                                    clip-rule="evenodd"></path>
                            </svg>
                        </button>
                        <div id="dropdownContent" class="pt-4 text-sm text-murky-200 hidden">
                            <div class="invoice-summary-box rounded-lg bg-murky-800 p-4">
                                <dl class="space-y-4 text-sm">
                                    <div class="flex justify-between">
                                        <dt class="font-medium text-white">Harga</dt>
                                        <dd class="flex flex-col text-murky-200 print:text-slate-800"><span>Rp&nbsp;
                                                {{ number_format($data->harga_pembayaran, 0, ',', '.') }},-</span></dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="font-medium text-white">Jumlah</dt>
                                        <dd class="text-murky-200 print:text-slate-800">1x</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="font-medium text-white">Metode Pembayaran</dt>
                                        <dd class="text-murky-200 print:text-slate-800">{{ $metode_name }}</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="font-medium text-white">No Invoice</dt>
                                        <dd class="text-murky-200 print:text-slate-800">{{ $data->id_pembelian }}</dd>
                                    </div>
                                    <div class="h-px w-full bg-murky-400"></div>
                                    <div class="flex justify-between">
                                        <dt class="font-medium text-white">Subtotal</dt>
                                        <dd class="text-murky-200 print:text-slate-800">Rp&nbsp;
                                            {{ number_format($data->harga_pembayaran, 0, ',', '.') }},-</dd>
                                    </div>

                                </dl>
                            </div>
                        </div>

                        <div class="invoice-total-box invoice-amount-pop mb-8 mt-4 flex items-center justify-between text-primary-500">
                            <dt class="text-xl font-bold text-white print:text-sm md:text-2xl">Total Harga</dt>
                            <dd class="font-semibold text-white print:text-slate-800">
                                <button type="button"
                                    class="invoice-copy-button flex items-center space-x-2 rounded-md border border-murky-400 bg-murky-600 px-2.5 py-1 hover:bg-murky-700 text-xl text-primary-500 print:hidden md:text-2xl"
                                    id="copyButton">
                                    <div class="max-w-[172px] truncate md:w-auto md:max-w-none">
                                        Rp.
                                        <span
                                            id="hargaPembayaran">{{ number_format($data->harga_pembayaran, 0, ',', '.') }},-</span>
                                    </div>
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-5 w-4">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184">
                                        </path>
                                    </svg>
                                </button>
                            </dd>
                        </div>
                        @if ($data->status_pembayaran == 'Belum Lunas')
                            <div class="invoice-warning-box border-l-4 border-yellow-300 bg-yellow-100 p-4 print:hidden">
                                <div>
                                    <div class="text-yellow-800 print:hidden">
                                        <p>Gunakan <strong>Ewallet </strong>atau <strong>aplikasi mobile banking</strong>
                                            yang tersedia scan QRIS</p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        (() => {
            let invoiceAnimationBooted = false;
            let invoiceIntroDismissed = false;

            function bootInvoiceAnimations() {
                if (invoiceAnimationBooted) {
                    return;
                }

                const animatedElements = document.querySelectorAll('.invoice-animate, .invoice-amount-pop');

                if (!animatedElements.length) {
                    return;
                }

                invoiceAnimationBooted = true;

                window.requestAnimationFrame(() => {
                    window.setTimeout(() => {
                        animatedElements.forEach((element) => {
                            element.classList.add('is-visible');
                        });
                    }, 90);
                });
            }

            let introTimer = null;
            let toastTimer = null;
            let introStartedAt = 0;
            let introMinDuration = 3200;
            const INTRO_HIDE_DURATION_MS = 750;

            function hidePageShell() {
                const pageShell = document.getElementById('invoicePageShell');

                if (!pageShell) {
                    return;
                }

                pageShell.style.opacity = '0';
                pageShell.style.visibility = 'hidden';
                pageShell.style.transform = 'translateY(32px) scale(.985)';
                pageShell.classList.remove('is-ready');
            }

            function showPageShell() {
                const pageShell = document.getElementById('invoicePageShell');

                if (!pageShell) {
                    return;
                }

                pageShell.style.removeProperty('opacity');
                pageShell.style.removeProperty('visibility');
                pageShell.style.removeProperty('transform');
                pageShell.classList.add('is-ready');
            }

            function dismissIntroOverlay() {
                if (invoiceIntroDismissed) {
                    return;
                }

                const elapsed = Date.now() - introStartedAt;
                if (elapsed < introMinDuration) {
                    if (introTimer) {
                        window.clearTimeout(introTimer);
                    }

                    introTimer = window.setTimeout(dismissIntroOverlay, introMinDuration - elapsed + 24);
                    return;
                }

                invoiceIntroDismissed = true;

                const introOverlay = document.getElementById('invoiceIntroOverlay');

                if (introOverlay) {
                    introOverlay.classList.add('is-hiding');
                    window.setTimeout(() => {
                        introOverlay.style.display = 'none';
                        showPageShell();
                    }, INTRO_HIDE_DURATION_MS);
                    return;
                }

                showPageShell();
            }

            function showEntryToast() {
                const toast = document.getElementById('invoiceEntryToast');

                if (!toast) {
                    return;
                }

                toast.classList.remove('is-hiding');
                toast.classList.add('is-visible');

                if (toastTimer) {
                    window.clearTimeout(toastTimer);
                }

                toastTimer = window.setTimeout(() => {
                    toast.classList.remove('is-visible');
                    toast.classList.add('is-hiding');
                }, 3200);
            }

            function prepareIntroOverlay() {
                const introOverlay = document.getElementById('invoiceIntroOverlay');
                const toast = document.getElementById('invoiceEntryToast');
                const lottiePlayer = document.getElementById('invoiceIntroLottie');
                const customIntroStage = document.getElementById('invoiceIntroStageCustom');

                invoiceIntroDismissed = false;

                if (introTimer) {
                    window.clearTimeout(introTimer);
                    introTimer = null;
                }

                if (introOverlay) {
                    introOverlay.style.display = '';
                    introOverlay.classList.remove('is-hiding');
                    introOverlay.classList.add('is-visible');
                }

                hidePageShell();

                if (toast) {
                    toast.classList.remove('is-visible', 'is-hiding');
                }

                if (toastTimer) {
                    window.clearTimeout(toastTimer);
                    toastTimer = null;
                }

                if (customIntroStage) {
                    const customParts = customIntroStage.querySelectorAll(
                        '.invoice-intro-orb, .invoice-intro-ring, .invoice-intro-card-icon, .invoice-intro-arrow, .invoice-intro-chip'
                    );

                    customParts.forEach((part) => {
                        part.style.animation = 'none';
                    });

                    void customIntroStage.offsetWidth;

                    customParts.forEach((part) => {
                        part.style.animation = '';
                    });
                }

                if (lottiePlayer) {
                    try {
                        if (typeof lottiePlayer.stop === 'function') {
                            lottiePlayer.stop();
                        }

                        window.setTimeout(() => {
                            if (typeof lottiePlayer.play === 'function') {
                                lottiePlayer.play();
                            }
                        }, 60);
                    } catch (error) {
                        console.debug('Invoice intro lottie replay skipped:', error);
                    }
                }

                const rawIntroDuration = Number(introOverlay?.dataset.duration || 0);
                const safeIntroDuration = Number.isFinite(rawIntroDuration) && rawIntroDuration > 0 ? rawIntroDuration : 3600;
                const isFromOrderPage = /\/(order|ordered|checkout)/i.test(document.referrer || '');
                introMinDuration = isFromOrderPage ? Math.max(safeIntroDuration, 3800) : Math.max(safeIntroDuration, 3200);
                introStartedAt = Date.now();

                introTimer = window.setTimeout(dismissIntroOverlay, introMinDuration);
                window.setTimeout(showEntryToast, 260);
            }

            prepareIntroOverlay();

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', bootInvoiceAnimations, { once: true });
            } else {
                bootInvoiceAnimations();
            }
            window.addEventListener('pageshow', (event) => {
                if (event && event.persisted) {
                    prepareIntroOverlay();
                }
            });

            const toggleButton = document.getElementById('toggleButton');
            const dropdownContent = document.getElementById('dropdownContent');

            if (toggleButton && dropdownContent) {
                toggleButton.addEventListener('click', function() {
                    const expanded = toggleButton.getAttribute('aria-expanded') === 'true' || false;
                    toggleButton.setAttribute('aria-expanded', !expanded);
                    dropdownContent.classList.toggle('hidden');
                });
            }

        })();
    </script>
    <script>
        function downloadQRCode() {
            var qrImage = document.getElementById("qrisPaymentImage");
            var qrCodeUrl = qrImage && qrImage.src ? qrImage.src : "{{ $data->no_pembayaran }}";

            var downloadLink = document.createElement("a");
            downloadLink.href = qrCodeUrl;
            downloadLink.download = "qrcode-{{ $data->id_pembelian }}.png";

            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
        }
    </script>
    <script>
        const copyButton = document.getElementById("copyButton");
        const hargaPembayaran = document.getElementById("hargaPembayaran");

        if (copyButton && hargaPembayaran) {
            copyButton.addEventListener("click", function() {
                const inputElement = document.createElement("input");
                inputElement.value = hargaPembayaran.textContent;

                document.body.appendChild(inputElement);
                inputElement.select();
                inputElement.setSelectionRange(0, 99999);

                document.execCommand("copy");
                document.body.removeChild(inputElement);

                toastr.options = {
                    "closeButton": false,
                    "debug": false,
                    "newestOnTop": true,
                    "progressBar": false,
                    "positionClass": "toast-top-right",
                    "preventDuplicates": false,
                    "onclick": null,
                    "showDuration": "50",
                    "hideDuration": "1000",
                    "timeOut": "5000",
                    "extendedTimeOut": "1000",
                    "showEasing": "swing",
                    "hideEasing": "linear",
                    "showMethod": "show",
                    "hideMethod": "hide"
                }
                toastr.success('{{ $data->harga_pembayaran }}</br>successfully copied to the clipboard!');
            });
        }


        function print_invoice() {
            var printContents = document.getElementById('invoice').innerHTML;
            var originalContents = document.body.innerHTML;
            document.body.innerHTML = printContents;
            window.print();
            document.body.innerHTML = originalContents;
            window.onafterprint = function() {
                location.reload()
            }
        }
    </script>



    <script>
        function copyNoPembayaranToClipboard() {
            const noPembayaranValue = document.getElementById('noPembayaran').innerText;
            navigator.clipboard.writeText(noPembayaranValue);
            toastr.success('successfully copied to the clipboard!');
        }

        function copyToClipboard() {
            const invoiceValue = document.getElementById('invoicePembelian').innerText;
            navigator.clipboard.writeText(invoiceValue);

            toastr.success('successfully copied to the clipboard!');
        }


        function copyToClipboardsn() {
            const invoiceValue = document.getElementById('sn').innerText;
            navigator.clipboard.writeText(invoiceValue);

            toastr.success('successfully copied to the clipboard!');
        }
    </script>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
    <script>
        $(document).ready(function() {
            var $star_rating = $('.star-rating .fa');

            var SetRatingStar = function() {
                return $star_rating.each(function() {
                    if (parseInt($star_rating.siblings('input.rating-value').val()) >= parseInt($(this)
                            .data('rating'))) {
                        $(this).removeClass('fa-star-o').addClass('fa-star');
                    } else {
                        $(this).removeClass('fa-star').addClass('fa-star-o');
                    }
                });
            };

            $star_rating.on('click', function() {
                $star_rating.siblings('input.rating-value').val($(this).data('rating'));
                SetRatingStar();
            });

            const myForm = document.getElementById('myForm');
            const buttonKirim = document.getElementById('melpa');
            const pesanTextArea = document.getElementById('pesanTextArea');

            if (pesanTextArea) {
                pesanTextArea.value = "Proses topup nya cepat dan harga nya murah banget!";

                pesanTextArea.addEventListener('focus', function() {
                    if (pesanTextArea.value === "Proses topup nya cepat dan harga nya murah banget!") {
                        pesanTextArea.value = "";
                    }
                });

                pesanTextArea.addEventListener('blur', function() {
                    if (pesanTextArea.value === "") {
                        pesanTextArea.value = "Proses topup nya cepat & harga nya murah banget!";
                    }
                });
            }

            function handleSubmit(e) {
                e.preventDefault();
                if (!myForm || !buttonKirim) {
                    return;
                }
                const formData = new FormData(myForm);
                fetch(myForm.action, {
                    method: 'POST',
                    body: formData
                }).then(function(response) {
                    if (response.ok) {
                        Swal.fire({
                            icon: 'success',
                            text: 'Terima kasih telah memberikan testimoni!',
                        }).then(function() {
                            buttonKirim.removeEventListener('click', handleSubmit);
                            buttonKirim.disabled = true;
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            text: 'Gagal menyimpan testimoni',
                        });
                    }
                }).catch(function(error) {
                    Swal.fire({
                        icon: 'error',
                        text: 'Gagal menyimpan testimoni',
                    });
                });
            }

            if (myForm && buttonKirim) {
                buttonKirim.addEventListener('click', handleSubmit);
            }
        });
    </script>






    @include('../footer')

    @push('custom_script')
    <script>
        (() => {
            const orderId = @json($data->id_pembelian);
            const url = @json(route('ajax.status', ':order')).replace(':order', orderId);
            let currentStatusPembelian = @json($data->status_pembelian);
            let currentStatusPembayaran = @json($data->status_pembayaran);
            let pollingTimer = null;
            let pageIsUnloading = false;
            const paymentBadge = document.getElementById('badge-unpaid');
            const orderBadge = document.getElementById('invoiceStatusPembelian');

            function applyBadgeState(element, config) {
                if (!element || !config) {
                    return;
                }

                element.classList.remove(
                    'invoice-badge-warning',
                    'invoice-badge-info',
                    'invoice-badge-danger',
                    'invoice-badge-success',
                    'invoice-status-live'
                );
                element.classList.add(config.className, 'invoice-status-live');
                element.textContent = config.label;

                window.setTimeout(() => {
                    element.classList.remove('invoice-status-live');
                }, 900);
            }

            function mapOrderStatus(status) {
                const normalized = String(status || '').toLowerCase().trim();

                if (normalized === 'pending') {
                    return { label: 'Pending', className: 'invoice-badge-warning' };
                }

                if (normalized === 'proses' || normalized === 'processing') {
                    return { label: 'Process', className: 'invoice-badge-info' };
                }

                if (normalized === 'sukses' || normalized === 'success') {
                    return { label: 'Success', className: 'invoice-badge-success' };
                }

                if (normalized === 'batal' || normalized === 'gagal' || normalized === 'failed' || normalized === 'cancelled') {
                    return { label: 'Cancelled', className: 'invoice-badge-danger' };
                }

                return null;
            }

            function mapPaymentStatus(status) {
                const normalized = String(status || '').toLowerCase().trim();

                if (normalized === 'paid' || normalized === 'lunas') {
                    return { label: 'Paid', className: 'invoice-badge-success' };
                }

                if (normalized === 'belum lunas' || normalized === 'unpaid') {
                    return { label: 'Unpaid', className: 'invoice-badge-danger' };
                }

                return { label: 'Expired', className: 'invoice-badge-warning' };
            }

            async function pollTransactionStatus() {
                if (pageIsUnloading || document.visibilityState === 'hidden') {
                    return;
                }

                try {
                    const response = await fetch(url, {
                        method: 'GET',
                        credentials: 'same-origin',
                        cache: 'no-store',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (!response.ok) {
                        return;
                    }

                    const data = await response.json();

                    if (!data.success) {
                        return;
                    }

                    if (data.status_pembelian !== currentStatusPembelian || data.status_pembayaran !== currentStatusPembayaran) {
                        applyBadgeState(orderBadge, mapOrderStatus(data.status_pembelian));
                        applyBadgeState(paymentBadge, mapPaymentStatus(data.status_pembayaran));
                        pageIsUnloading = true;
                        window.setTimeout(() => {
                            window.location.reload();
                        }, 650);
                    }
                } catch (error) {
                    if (!pageIsUnloading) {
                        console.debug('Invoice status polling skipped:', error);
                    }
                }
            }

            pollingTimer = window.setInterval(pollTransactionStatus, 5000);

            window.addEventListener('beforeunload', () => {
                pageIsUnloading = true;
                if (pollingTimer) {
                    window.clearInterval(pollingTimer);
                }
            });

            document.addEventListener('visibilitychange', () => {
                if (!pageIsUnloading && document.visibilityState === 'visible') {
                    pollTransactionStatus();
                }
            });
        })();
    </script>
    <script>
        (() => {
            const countdownElement = document.getElementById('invoiceExpiryCountdown');
            const countdownMeta = document.getElementById('invoiceExpiryMeta');
            const paymentBadge = document.getElementById('badge-unpaid');

            if (!countdownElement) {
                return;
            }

            const expiredAt = countdownElement.dataset.expiredAt;
            const paymentStatus = String(countdownElement.dataset.status || '').toLowerCase().trim();
            const targetTime = Date.parse(expiredAt);

            if (Number.isNaN(targetTime)) {
                countdownElement.textContent = 'Tidak tersedia';
                countdownElement.classList.add('is-expired');
                if (countdownMeta) {
                    countdownMeta.textContent = 'Batas pembayaran tidak tersedia.';
                }
                return;
            }

            const setExpiredState = () => {
                countdownElement.textContent = 'Pembayaran kedaluwarsa';
                countdownElement.classList.add('is-expired');

                if (countdownMeta) {
                    countdownMeta.textContent = 'Batas waktu pembayaran telah habis.';
                }

                if (paymentBadge) {
                    paymentBadge.classList.remove('invoice-badge-danger', 'invoice-badge-success', 'invoice-badge-info');
                    paymentBadge.classList.add('invoice-badge-warning', 'invoice-status-live');
                    paymentBadge.textContent = 'Expired';

                    window.setTimeout(() => {
                        paymentBadge.classList.remove('invoice-status-live');
                    }, 900);
                }
            };

            if (['paid', 'lunas', 'success'].includes(paymentStatus)) {
                countdownElement.textContent = 'Pembayaran diterima';
                if (countdownMeta) {
                    countdownMeta.textContent = 'Invoice sudah dibayar dan tidak lagi menghitung mundur.';
                }
                return;
            }

            if (paymentStatus === 'expired') {
                setExpiredState();
                return;
            }

            function formatRemaining(ms) {
                const totalSeconds = Math.max(0, Math.floor(ms / 1000));
                const hours = Math.floor(totalSeconds / 3600);
                const minutes = Math.floor((totalSeconds % 3600) / 60);
                const seconds = totalSeconds % 60;

                return [
                    String(hours).padStart(2, '0'),
                    String(minutes).padStart(2, '0'),
                    String(seconds).padStart(2, '0'),
                ].join(':');
            }

            function renderCountdown() {
                const remaining = targetTime - Date.now();

                if (remaining <= 0) {
                    setExpiredState();
                    return true;
                }

                countdownElement.textContent = formatRemaining(remaining);
                return false;
            }

            renderCountdown();
            const countdownTimer = window.setInterval(() => {
                if (renderCountdown()) {
                    window.clearInterval(countdownTimer);
                }
            }, 1000);
        })();
    </script>
    @endpush




@endsection
