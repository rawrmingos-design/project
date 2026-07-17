@extends('template.template')

@section('custom_style')


<style>
    .btn:disabled{background:#8ba4b1;border-color:#8ba4b1}

    .deposit-intro-overlay {
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem;
        background:
            radial-gradient(circle at top, rgba(255, 255, 255, .1), transparent 30%),
            linear-gradient(180deg, #facc15 0%, #eab308 100%);
        opacity: 0;
        visibility: hidden;
        transform: scale(1.03);
        transition: opacity .65s cubic-bezier(.22, 1, .36, 1), visibility .65s ease, transform .95s cubic-bezier(.22, 1, .36, 1);
    }

    .deposit-intro-overlay[data-state="paid"] {
        background:
            radial-gradient(circle at top, rgba(255, 255, 255, .08), transparent 30%),
            linear-gradient(180deg, #059669 0%, #047857 100%);
    }

    .deposit-intro-overlay[data-state="expired"] {
        background:
            radial-gradient(circle at top, rgba(255, 255, 255, .08), transparent 30%),
            linear-gradient(180deg, #9d174d 0%, #831843 100%);
    }

    .deposit-intro-overlay.is-visible {
        opacity: 1;
        visibility: visible;
        transform: scale(1);
    }

    .deposit-intro-overlay.is-hiding {
        opacity: 0;
        visibility: hidden;
        transform: translateY(-3%) scale(1.035);
    }

    .deposit-intro-card {
        width: min(100%, 68rem);
        text-align: center;
        color: #fff;
        opacity: 0;
        transform: translateY(28px) scale(.965);
        transition: opacity .82s cubic-bezier(.22, 1, .36, 1), transform 1.05s cubic-bezier(.22, 1, .36, 1);
    }

    .deposit-intro-overlay.is-visible .deposit-intro-card {
        opacity: 1;
        transform: translateY(0) scale(1);
    }

    .deposit-intro-overlay.is-hiding .deposit-intro-card {
        opacity: 0;
        transform: translateY(-18px) scale(.985);
    }

    .deposit-intro-stage {
        position: relative;
        width: min(88vw, 34rem);
        aspect-ratio: 1 / 1;
        margin: 0 auto 2.2rem;
        pointer-events: none;
        filter: drop-shadow(0 36px 65px rgba(2, 6, 23, .34));
    }

    .deposit-intro-lottie-shell {
        width: min(88vw, 30rem);
        margin: 0 auto 2rem;
        pointer-events: none;
        filter: drop-shadow(0 28px 58px rgba(2, 6, 23, .28));
    }

    .deposit-intro-lottie-shell lottie-player {
        width: 100%;
        height: auto;
        min-height: 18rem;
        background: transparent;
    }

    .deposit-intro-overlay.is-visible .deposit-intro-stage {
        animation: depositIntroStageFloat 3.4s ease-in-out 2.2s infinite;
    }

    .deposit-intro-overlay.is-visible .deposit-intro-lottie-shell {
        animation: depositIntroStageFloat 3.2s ease-in-out .4s infinite;
    }

    .deposit-intro-overlay[data-state="paid"].is-visible .deposit-intro-stage {
        animation-duration: 3.9s;
    }

    .deposit-intro-overlay[data-state="expired"].is-visible .deposit-intro-stage {
        animation-duration: 3s;
    }

    .deposit-intro-overlay[data-state="expired"].is-visible .deposit-intro-lottie-shell {
        animation-duration: 3s;
    }

    .deposit-intro-asset {
        position: absolute;
        left: 50%;
        top: 50%;
        display: block;
        user-select: none;
        opacity: 0;
        transform: translate(-50%, -50%) scale(.72);
        transform-origin: center;
    }

    .deposit-intro-asset--circle {
        width: 78%;
        z-index: 1;
    }

    .deposit-intro-asset--calc {
        width: 40%;
        top: 48%;
        left: 43%;
        z-index: 3;
    }

    .deposit-intro-asset--slider {
        width: 47%;
        top: 48%;
        left: 63%;
        z-index: 4;
    }

    .deposit-intro-asset--card {
        width: 54%;
        top: 63%;
        left: 55%;
        z-index: 2;
    }

    .deposit-intro-asset--tap {
        width: 13%;
        top: 53%;
        left: 43%;
        z-index: 5;
        filter: drop-shadow(0 14px 22px rgba(15, 23, 42, .22));
    }

    .deposit-intro-overlay.is-visible .deposit-intro-asset--circle {
        animation: depositIntroCircleIn .95s cubic-bezier(.16, 1, .3, 1) .02s forwards;
    }

    .deposit-intro-overlay.is-visible .deposit-intro-asset--calc {
        animation: depositIntroCalcIn .88s cubic-bezier(.22, 1, .36, 1) .42s forwards;
    }

    .deposit-intro-overlay.is-visible .deposit-intro-asset--slider {
        animation: depositIntroSliderIn .88s cubic-bezier(.22, 1, .36, 1) 1.16s forwards;
    }

    .deposit-intro-overlay.is-visible .deposit-intro-asset--card {
        animation: depositIntroCardIn .92s cubic-bezier(.22, 1, .36, 1) .78s forwards;
    }

    .deposit-intro-overlay.is-visible .deposit-intro-asset--tap {
        animation: depositIntroTapIn .72s cubic-bezier(.22, 1, .36, 1) 1.55s forwards,
            depositIntroTapPulse .82s ease-in-out 1.95s 3;
    }

    .deposit-intro-status {
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

    .deposit-intro-status-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.25rem;
        height: 2.25rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, .16);
    }

    .deposit-intro-status-icon svg {
        width: 1.05rem;
        height: 1.05rem;
    }

    .deposit-intro-status-text {
        font-size: .88rem;
        font-weight: 800;
        letter-spacing: .12em;
        text-transform: uppercase;
        color: rgba(255, 255, 255, .95);
    }

    .deposit-intro-title {
        margin: 0;
        font-size: clamp(2rem, 4vw, 3.4rem);
        line-height: 1.04;
        font-weight: 800;
        letter-spacing: -.04em;
    }

    .deposit-intro-subtitle {
        margin: 1rem auto 0;
        max-width: 38rem;
        font-size: 1.02rem;
        line-height: 1.7;
        color: rgba(255, 255, 255, .9);
    }

    @keyframes depositIntroCircleIn {
        0% { opacity: 0; transform: translate(-50%, -50%) scale(.2) rotate(-16deg); }
        60% { opacity: 1; transform: translate(-50%, -50%) scale(1.06) rotate(4deg); }
        100% { opacity: 1; transform: translate(-50%, -50%) scale(1) rotate(0); }
    }

    @keyframes depositIntroCalcIn {
        0% { opacity: 0; transform: translate(-50%, -22%) scale(.62) rotate(-12deg); }
        100% { opacity: 1; transform: translate(-50%, -50%) scale(1) rotate(0); }
    }

    @keyframes depositIntroSliderIn {
        0% { opacity: 0; transform: translate(-6%, -50%) scale(.7) rotate(8deg); }
        100% { opacity: 1; transform: translate(-50%, -50%) scale(1) rotate(0); }
    }

    @keyframes depositIntroCardIn {
        0% { opacity: 0; transform: translate(-50%, 10%) scale(.76) rotate(-10deg); }
        100% { opacity: 1; transform: translate(-50%, -50%) scale(1) rotate(0); }
    }

    @keyframes depositIntroTapIn {
        0% { opacity: 0; transform: translate(-50%, -50%) scale(.4); }
        100% { opacity: 1; transform: translate(-50%, -50%) scale(1); }
    }

    @keyframes depositIntroTapPulse {
        0%,100% { transform: translate(-50%, -50%) scale(1); }
        40% { transform: translate(-50%, -50%) scale(.86); }
        70% { transform: translate(-50%, -50%) scale(1.06); }
    }

    @keyframes depositIntroStageFloat {
        0%,100% { transform: translateY(0); }
        50% { transform: translateY(-10px); }
    }

    .deposit-page-shell {
        padding-bottom: 5rem;
        opacity: 0;
        transform: translateY(32px) scale(.985);
        transition: opacity .7s cubic-bezier(.22, 1, .36, 1), transform .95s cubic-bezier(.22, 1, .36, 1);
    }

    .deposit-page-shell.is-ready {
        opacity: 1;
        transform: translateY(0) scale(1);
    }

    .deposit-expiry-card {
        border: 1px solid rgba(251, 191, 36, 0.3);
        border-radius: 18px;
        background: linear-gradient(135deg, rgba(30, 41, 59, 0.95), rgba(15, 23, 42, 0.98));
        box-shadow: 0 24px 60px rgba(2, 6, 23, 0.35);
        width: 100%;
        max-width: 380px;
        padding: 1rem 1.15rem;
    }

    .deposit-expiry-label {
        font-size: 0.76rem;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        color: rgba(148, 163, 184, 0.92);
    }

    .deposit-expiry-countdown {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 220px;
        padding: 0.9rem 1.2rem;
        border-radius: 14px;
        background: linear-gradient(135deg, #f97316, #ef4444);
        color: #fff;
        font-size: 1.15rem;
        font-weight: 800;
        letter-spacing: 0.04em;
        box-shadow: 0 18px 42px rgba(239, 68, 68, 0.28);
    }

    .deposit-expiry-countdown.is-expired {
        background: linear-gradient(135deg, #334155, #0f172a);
        box-shadow: none;
    }

    .deposit-expiry-meta {
        margin-top: 0.65rem;
        font-size: 0.92rem;
        color: rgba(226, 232, 240, 0.9);
    }

    .deposit-hero-row {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .deposit-print-actions {
        margin-top: .15rem;
        flex-shrink: 0;
    }

    .deposit-content-grid {
        display: grid;
        grid-template-columns: repeat(12, minmax(0, 1fr));
        gap: 1.5rem;
    }

    .deposit-info-card {
        border: 1px solid rgba(148, 163, 184, 0.22);
        border-radius: 14px;
        background: rgba(15, 23, 42, 0.35);
        padding: 1rem 1.1rem;
    }

    .deposit-info-card__label {
        font-size: .78rem;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: rgba(148, 163, 184, 0.94);
    }

    .deposit-info-card__value {
        margin-top: .45rem;
        font-size: 1.02rem;
        font-weight: 700;
        color: #fff;
        word-break: break-word;
    }

    .deposit-panel {
        border: 1px solid rgba(71, 85, 105, 0.45);
        border-radius: 1.25rem;
        background: rgba(15, 23, 42, 0.34);
        padding: 1rem;
    }

    .deposit-panel-title {
        font-size: 1rem;
        font-weight: 700;
        color: #fff;
    }

    .deposit-detail-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: .75rem;
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid rgba(71, 85, 105, 0.45);
    }

    .deposit-detail-row {
        display: grid;
        grid-template-columns: 1fr;
        gap: .25rem;
    }

    .deposit-detail-label {
        font-size: .78rem;
        color: rgba(148, 163, 184, 0.96);
    }

    .deposit-detail-value {
        font-size: .95rem;
        color: #fff;
        font-weight: 600;
        word-break: break-word;
    }

    .deposit-summary-toggle {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-radius: .75rem;
        border: 1px solid rgba(71, 85, 105, 0.55);
        background: rgba(30, 41, 59, 0.62);
        padding: .65rem .9rem;
        color: #fff;
        font-size: .9rem;
        font-weight: 600;
    }

    .deposit-summary-toggle:hover {
        background: rgba(30, 41, 59, 0.84);
    }

    .deposit-summary-panel {
        margin-top: .75rem;
        border-radius: .85rem;
        border: 1px solid rgba(71, 85, 105, 0.45);
        background: rgba(15, 23, 42, 0.54);
        padding: .9rem;
    }

    .deposit-summary-panel__row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .5rem;
        color: rgba(226, 232, 240, 0.96);
        font-size: .9rem;
    }

    .deposit-summary-panel__row + .deposit-summary-panel__row {
        margin-top: .7rem;
    }

    .deposit-summary-divider {
        margin: .75rem 0;
        height: 1px;
        background: rgba(71, 85, 105, 0.55);
    }

    .deposit-total-row {
        margin-top: 1rem;
        border: 1px solid rgba(249, 115, 22, 0.45);
        border-radius: 1rem;
        background: rgba(15, 23, 42, 0.35);
        padding: .9rem 1rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        flex-wrap: wrap;
    }

    .deposit-status-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: .375rem;
        padding: .15rem .5rem;
        font-size: .75rem;
        font-weight: 600;
        line-height: 1.2;
    }

    .deposit-status-badge--danger {
        background: rgb(253 164 175);
        color: rgb(136 19 55);
    }

    .deposit-status-badge--success {
        background: rgb(167 243 208);
        color: rgb(6 95 70);
    }

    .deposit-status-badge--neutral {
        background: rgb(203 213 225);
        color: rgb(30 41 59);
    }

    .deposit-warning-box {
        margin-top: 1rem;
        border-left: 4px solid rgb(253 224 71);
        background: rgb(254 249 195);
        border-radius: .35rem;
        padding: .75rem .9rem;
    }

    .deposit-qr-panel {
        margin-top: 1.5rem;
        border: 1px solid rgba(71, 85, 105, 0.55);
        border-radius: .85rem;
        background: rgba(30, 41, 59, 0.78);
        padding: 1rem;
    }

    .deposit-qr-panel__title {
        margin-bottom: .9rem;
        text-align: center;
        font-size: .92rem;
        font-weight: 700;
        color: #fff;
    }

    .deposit-qr-panel__code-wrap {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: .75rem;
        background: #fff;
        padding: .55rem;
    }

    .deposit-qr-panel__meta {
        margin-top: .6rem;
        font-size: .72rem;
        color: rgba(148, 163, 184, 0.95);
        word-break: break-all;
    }
    
    .container-image {
    width: 150px;
    height: 200px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: left;
    margin-bottom: 10px;
  }

  .container-image b {
    width: 100%;
  }

  .container-image img {
    max-width: 100%;
    max-height: 100%;
    border-radius: 10px;
  }

    @media (min-width: 768px) {
    .deposit-detail-row {
        grid-template-columns: 10rem 1fr;
        gap: .7rem;
        align-items: center;
    }

    .deposit-content-grid > .deposit-col {
        grid-column: span 6 / span 6;
    }
    }

    @media (max-width: 767px) {
    .deposit-print-actions {
        width: 100%;
    }

    .deposit-print-actions button {
        width: 100%;
    }

    .deposit-expiry-card {
        max-width: 100%;
    }

    .deposit-expiry-countdown {
        width: 100%;
        min-width: 0;
    }

    .deposit-content-grid > .deposit-col {
        grid-column: span 12 / span 12;
    }

    .deposit-intro-stage {
        width: min(88vw, 22rem);
        margin-bottom: 1.5rem;
    }

    .deposit-intro-lottie-shell {
        width: min(88vw, 20rem);
        margin-bottom: 1.25rem;
    }

    .deposit-intro-status {
        margin-bottom: .8rem;
        padding: .6rem .85rem;
    }

    .deposit-intro-status-text {
        font-size: .72rem;
        letter-spacing: .1em;
    }
  }
</style>


@endsection



@if(session('success'))
    <script>
        $(document).ready(function () {
            toastr.success('{{ session('success') }}');
        });
    </script>
@endif

@section('content')

@include('../navbar')

@php
    $depositPaymentStatus = strtolower(trim((string) ($data->status_pembayaran ?? '')));
    $depositHeroEyebrow = 'Terima Kasih!';
    $depositHeroTitle = 'Harap lengkapi pembayaran.';
    $depositHeroDescription = 'Deposit kamu ' . $data->id_pembelian . ' menunggu pembayaran sebelum diproses.';
    $depositExpiryLabel = 'Sisa waktu pembayaran';
    $depositExpiryMeta = 'Batas pembayaran: ' . $expired->timezone(config('app.timezone'))->format('d/m/Y H:i');

    if (in_array($depositPaymentStatus, ['paid', 'lunas', 'success'], true)) {
        $depositHeroTitle = 'Deposit sudah selesai.';
        $depositHeroDescription = 'Deposit kamu ' . $data->id_pembelian . ' telah dibayar dan saldo sedang diproses oleh sistem.';
        $depositExpiryLabel = 'Status invoice deposit';
        $depositExpiryMeta = 'Invoice deposit ini sudah dibayar.';
    } elseif ($depositPaymentStatus === 'expired') {
        $depositHeroEyebrow = 'Perhatian';
        $depositHeroTitle = 'Invoice deposit sudah kedaluwarsa.';
        $depositHeroDescription = 'Batas pembayaran untuk deposit ' . $data->id_pembelian . ' telah habis.';
        $depositExpiryLabel = 'Batas waktu pembayaran';
        $depositExpiryMeta = 'Invoice deposit ini sudah melewati masa aktif pembayaran.';
    }

    $depositIntroState = 'pending';
    $depositIntroTitle = 'Menunggu Pembayaran';
    $depositIntroSubtitle = 'Invoice deposit sedang disiapkan. Mohon selesaikan pembayaran untuk melanjutkan proses deposit.';
    $depositIntroIcon = 'clock';
    $depositIntroDuration = 4300;

    if (in_array($depositPaymentStatus, ['paid', 'lunas', 'success'], true)) {
        $depositIntroState = 'paid';
        $depositIntroTitle = 'Deposit Diterima';
        $depositIntroSubtitle = 'Pembayaran deposit berhasil diterima. Sistem sedang menyelesaikan penambahan saldo.';
        $depositIntroIcon = 'check';
        $depositIntroDuration = 4300;
    } elseif ($depositPaymentStatus === 'expired') {
        $depositIntroState = 'expired';
        $depositIntroTitle = 'Pembayaran Kedaluwarsa';
        $depositIntroSubtitle = 'Batas waktu pembayaran deposit telah berakhir. Silakan buat invoice deposit baru jika masih diperlukan.';
        $depositIntroIcon = 'x';
        $depositIntroDuration = 4700;
    }

    $depositIntroBadgeText = match ($depositIntroState) {
        'expired' => 'Pembayaran Kedaluwarsa',
        'paid' => 'Deposit Diterima',
        default => 'Menunggu Pembayaran',
    };

    $depositIntroLottieSequence = [];
    $depositIntroUsesLottie = false;
    $depositIntroLottieSrc = null;

    if ($depositIntroState === 'pending') {
        foreach (['First.json', 'Second.json'] as $candidateFile) {
            $candidatePath = public_path('assets/invoice-intro/lottie/' . $candidateFile);

            if (is_file($candidatePath)) {
                $depositIntroLottieSequence[] = asset('assets/invoice-intro/lottie/' . rawurlencode($candidateFile));
            }
        }

        if (!empty($depositIntroLottieSequence)) {
            $depositIntroUsesLottie = true;
            $depositIntroLottieSrc = $depositIntroLottieSequence[0];
        }
    } elseif ($depositIntroState === 'expired' && is_file(public_path('assets/invoice-intro/lottie/expired.json'))) {
        $depositIntroUsesLottie = true;
        $depositIntroLottieSrc = asset('assets/invoice-intro/lottie/expired.json');
    }
@endphp

<div id="depositIntroOverlay" class="deposit-intro-overlay is-visible print:hidden" data-state="{{ $depositIntroState }}" data-duration="{{ $depositIntroDuration }}">
    <div class="deposit-intro-card">
        @if ($depositIntroUsesLottie)
            <div class="deposit-intro-lottie-shell" aria-hidden="true">
                <lottie-player
                    id="depositIntroLottie"
                    src="{{ $depositIntroLottieSrc }}"
                    data-sequence="{{ implode('|', $depositIntroLottieSequence) }}"
                    background="transparent"
                    speed="1"
                    autoplay
                ></lottie-player>
            </div>
        @else
            <div class="deposit-intro-stage" aria-hidden="true">
                <img src="{{ asset('assets/invoice-intro/intro-image-1.png') }}" alt="" class="deposit-intro-asset deposit-intro-asset--circle">
                <img src="{{ asset('assets/invoice-intro/intro-image-2.png') }}" alt="" class="deposit-intro-asset deposit-intro-asset--calc">
                <img src="{{ asset('assets/invoice-intro/intro-image-3.png') }}" alt="" class="deposit-intro-asset deposit-intro-asset--slider">
                <img src="{{ asset('assets/invoice-intro/intro-image-4.png') }}" alt="" class="deposit-intro-asset deposit-intro-asset--card">
                <img src="{{ asset('assets/invoice-intro/button-click.png') }}" alt="" class="deposit-intro-asset deposit-intro-asset--tap">
            </div>
        @endif
        <div class="deposit-intro-status">
            <span class="deposit-intro-status-icon" aria-hidden="true">
                @if ($depositIntroIcon === 'check')
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                @elseif ($depositIntroIcon === 'x')
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                @else
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                @endif
            </span>
            <span class="deposit-intro-status-text">{{ $depositIntroBadgeText }}</span>
        </div>
        <h2 class="deposit-intro-title">{{ $depositIntroTitle }}</h2>
        <p class="deposit-intro-subtitle">{{ $depositIntroSubtitle }}</p>
    </div>
</div>

<main class="deposit-page-shell relative mt-5 p-2" id="invoice">
    <div class=" print:!text-slate-800">
        <div class="container py-12 print:py-8 md:py-8">
            <div class="deposit-hero-row">
                <div class="max-w-3xl">
                    <h1 class="text-base font-medium text-primary-500">{{ $depositHeroEyebrow }}</h1>
                    <p class="mt-2 text-4xl font-bold tracking-tight">{{ $depositHeroTitle }}</p>
                    <p class="mt-2 text-base text-murky-200">{{ $depositHeroDescription }}</p>
                </div>
                <div class="deposit-print-actions print:hidden">
                    <button
                        class="inline-flex items-center justify-center rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white duration-300 hover:bg-primary-400 disabled:cursor-not-allowed disabled:opacity-75 space-x-2"
                        onclick="print_invoice()"
                        id="printInvoiceButton"
                        type="button"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-4 w-4">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z"
                            ></path>
                        </svg>
                        <span>Unduh Invoice</span>
                    </button>
                </div>
            </div>
            <div class="mt-8">
                <dl class="deposit-expiry-card text-left text-sm font-medium">
                    <dt class="deposit-expiry-label">{{ $depositExpiryLabel }}</dt>
                    <dd class="mt-3 text-primary-500">
                        <div
                            id="depositExpiryCountdown"
                            class="deposit-expiry-countdown"
                            data-expired-at="{{ $expiredIso }}"
                            data-status="{{ $data->status_pembayaran }}"
                        >
                            --:--:--
                        </div>
                        <div class="deposit-expiry-meta">
                            {{ $depositExpiryMeta }}
                        </div>
                    </dd>
                </dl>
            </div>
            <div class="my-8 border-y border-murky-600 py-8">
                <div class="deposit-content-grid">
                    <div class="deposit-col">
                        <div class="deposit-panel">
                            <div class="deposit-panel-title">Informasi Deposit</div>
                            <div class="deposit-detail-grid">
                                <div class="deposit-detail-row">
                                    <div class="deposit-detail-label">No. Invoice</div>
                                    <div class="deposit-detail-value">{{ $data->id_pembelian }}</div>
                                </div>
                                <div class="deposit-detail-row">
                                    <div class="deposit-detail-label">Tanggal Buat</div>
                                    <div class="deposit-detail-value">{{ \Illuminate\Support\Carbon::parse($data->created_at)->format('d-m-Y H:i:s') }}</div>
                                </div>
                                <div class="deposit-detail-row">
                                    <div class="deposit-detail-label">Layanan</div>
                                    <div class="deposit-detail-value">{{ $data->layanan ?? 'Top Up Saldo' }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button
                                class="deposit-summary-toggle"
                                id="depositSummaryToggle"
                                type="button"
                                aria-expanded="false"
                                aria-controls="depositSummaryPanel"
                            >
                                <span>Rincian Pembayaran</span>
                                <svg xmlns="http://www.w3.org/2000/svg" id="depositSummaryChevron" class="h-5 w-5 transition-transform duration-200" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M14.77 12.79a.75.75 0 01-1.06-.02L10 8.832 6.29 12.77a.75.75 0 11-1.08-1.04l4.25-4.5a.75.75 0 011.08 0l4.25 4.5a.75.75 0 01-.02 1.06z" clip-rule="evenodd"></path>
                                </svg>
                            </button>
                            <div id="depositSummaryPanel" class="deposit-summary-panel hidden">
                                <div class="deposit-summary-panel__row">
                                    <span>Harga</span>
                                    <span>Rp {{ number_format($data->harga_pembayaran, 0, ',', '.') }},-</span>
                                </div>
                                <div class="deposit-summary-panel__row">
                                    <span>Jumlah</span>
                                    <span>1x</span>
                                </div>
                                <div class="deposit-summary-panel__row">
                                    <span>Metode Pembayaran</span>
                                    <span>{{ Str::upper((string) $data->metode_pembayaran) }}</span>
                                </div>
                                <div class="deposit-summary-divider"></div>
                                <div class="deposit-summary-panel__row">
                                    <span>Subtotal</span>
                                    <span>Rp {{ number_format($data->harga_pembayaran, 0, ',', '.') }},-</span>
                                </div>
                                <div class="deposit-summary-panel__row">
                                    <span>Biaya</span>
                                    <span>Rp 0</span>
                                </div>
                            </div>
                        </div>

                        <div class="deposit-total-row text-primary-500">
                            <dt class="text-xl font-bold text-white print:text-sm md:text-2xl">Total Pembayaran</dt>
                            <dd class="font-semibold text-white print:text-slate-800">
                                <button type="button" id="copyButton" class="flex items-center space-x-2 rounded-md border border-murky-400 bg-murky-600 px-2.5 py-1 hover:bg-murky-700 text-xl text-primary-500 print:hidden md:text-2xl" >
                                    <div class="max-w-[172px] truncate md:w-auto md:max-w-none">
                                        Rp.
                                        <span id="hargaPembayaran">{{ number_format($data->harga_pembayaran, 0, ',','.') }},-</span>
                                    </div>
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-5 w-4">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184"
                                        ></path>
                                    </svg>
                                </button>
                            </dd>
                        </div>
                    </div>

                    <div class="deposit-col">
                        <div class="deposit-panel">
                            <div class="deposit-panel-title">Metode Pembayaran</div>
                            <div class="mt-2 text-sm font-semibold text-white">{{ Str::upper((string) $data->metode_pembayaran) }}</div>
                            <div class="deposit-detail-grid">
                                <div class="deposit-detail-row">
                                    <div class="deposit-detail-label">Nomor Invoice</div>
                                    <div class="deposit-detail-value">
                                        <button type="button" id="copyButton1" class="flex items-center space-x-2 rounded-md border border-murky-400 bg-murky-600 px-2.5 py-1 hover:bg-murky-700 print:hidden" onclick="copyToClipboard('copyButton1')">
                                            <div class="max-w-[172px] truncate md:w-auto md:max-w-none">{{ $data->id_pembelian }}</div>
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-5 w-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <div class="deposit-detail-row">
                                    <div class="deposit-detail-label">Status Pembayaran</div>
                                    <div class="deposit-detail-value">
                                        @if($data->status_pembayaran == "Belum Lunas")
                                            <span id="badge-unpaid" class="deposit-status-badge deposit-status-badge--danger">Unpaid</span>
                                        @elseif($data->status_pembayaran == "PAID" || $data->status_pembayaran == "Lunas")
                                            <span id="badge-unpaid" class="deposit-status-badge deposit-status-badge--success">Paid</span>
                                        @else
                                            <span id="badge-unpaid" class="deposit-status-badge deposit-status-badge--neutral">Expired</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="deposit-detail-row">
                                    <div class="deposit-detail-label">Pesan</div>
                                    <div class="deposit-detail-value">
                                        @if($data->status_pembayaran == "Belum Lunas")
                                        Menunggu pembayaran deposit saldo
                                        @elseif($data->status_pembayaran == "PAID" || $data->status_pembayaran == "Lunas")
                                        Saldo berhasil ditambahkan pada {{ $data->updated_at }}. Diproses oleh sistem.
                                        @elseif($data->status_pembayaran == "Expired")
                                        Pembayaran deposit telah kedaluwarsa.
                                        @else
                                            Expired
                                        @endif
                                    </div>
                                </div>
                            </div>

                            @php
                                $paymentCode = Str::upper((string) ($data->metode_pembayaran ?? ''));
                                $paymentValue = (string) ($data->no_pembayaran ?? '');
                                $methodTypeLower = \Illuminate\Support\Str::lower(\App\Models\Method::normalizeTipe((string) ($data->metode_tipe ?? '')));

                                $isQrMethod = $methodTypeLower === 'qris' || str_contains($methodTypeLower, 'qris') || in_array($paymentCode, [
                                    "QRIS", "QRISC", "QRIS2", "QRISOP", "SP", "SQ"
                                ], true);
                            @endphp

                            @if($isQrMethod)
                            <div class="deposit-qr-panel relative flex flex-col items-center justify-center">
                                <h3 class="deposit-qr-panel__title">Scan QRIS / Lanjut Bayar</h3>
                                <div id="qris-payment" class="w-full flex justify-center">
                                    @if(str_contains($data->no_pembayaran, 'duitku') || str_contains($data->no_pembayaran, 'sandbox.duitku.com') || str_contains($data->no_pembayaran, 'passport.duitku.com'))
                                         <a href="{{ $data->no_pembayaran }}" target="_blank" class="w-full inline-flex items-center justify-center rounded-md bg-blue-600 px-4 py-3 text-sm font-bold text-white shadow-lg transition-all duration-300 hover:bg-blue-700 hover:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                            Buka Halaman Pembayaran
                                         </a>
                                    @elseif(filter_var($data->no_pembayaran, FILTER_VALIDATE_URL))
                                         <a href="{{ $data->no_pembayaran }}" target="_blank">
                                            <span class="deposit-qr-panel__code-wrap">
                                                <img src="{{ $data->no_pembayaran }}" alt="QRIS Code" class="mx-auto max-w-[200px]">
                                            </span>
                                         </a>
                                    @else
                                         <div class="text-center">
                                            <span class="deposit-qr-panel__code-wrap">
                                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ $data->no_pembayaran }}" alt="QRIS Code" class="mx-auto">
                                            </span>
                                            <p class="deposit-qr-panel__meta font-mono max-w-[220px] mx-auto">{{ $data->no_pembayaran }}</p>
                                         </div>
                                    @endif
                                </div>
                            </div>
                            @endif

                            @if(Str::upper($data->metode_pembayaran) == "SHOPEEPAY" || Str::upper($data->metode_pembayaran) == "OVOPUSH" || Str::upper($data->metode_pembayaran) == "DANA" || Str::upper($data->metode_pembayaran) == "LINKAJA" || Str::upper($data->metode_pembayaran) == "11" || Str::upper($data->metode_pembayaran) == "17" || Str::upper($data->metode_pembayaran) == "23")
                            <a href="{{$data->no_pembayaran}}" target="_blank">
                                <button class="mt-6 inline-flex items-center justify-center rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white duration-300 hover:bg-primary-400 disabled:cursor-not-allowed disabled:opacity-75 w-full space-x-2 pr-3 sm:w-auto" type="button">
                                    <span>Klik di sini untuk melakukan pembayaran</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-4 w-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"></path>
                                    </svg>
                                </button>
                            </a>
                            @endif
                        </div>

                        <div class="deposit-warning-box print:hidden">
                            @if($isQrMethod)
                            <div class="text-yellow-800">
                                <p>Gunakan <strong>Ewallet </strong>atau <strong>aplikasi mobile banking</strong> yang tersedia scan QRIS</p>
                            </div>
                            @elseif(Str::upper($data->metode_pembayaran) == "BRIVA" || Str::upper($data->metode_pembayaran) == "BCAVA" || Str::upper($data->metode_pembayaran) == "BNIVA" || Str::upper($data->metode_pembayaran) == "MANDIRIVA" || Str::upper($data->metode_pembayaran) == "PERMATAVA" || Str::upper($data->metode_pembayaran) == "CIMBVA" || Str::upper($data->metode_pembayaran) == "DANAMONVA" || Str::upper($data->metode_pembayaran) == "BSIVA")
                            <div class="text-yellow-800">
                                <p>Gunakan <strong>aplikasi mobile banking</strong> untuk melakukan pembayaran</p>
                            </div>
                            @elseif(Str::upper($data->metode_pembayaran) == "INDOMARET")
                            <div class="text-yellow-800">
                                <p>Silahkan tunjukkan <strong>nomor pembayaran </strong> ke kasir indomaret agar pesanan dapat diproses</p>
                            </div>
                            @elseif(Str::upper($data->metode_pembayaran) == "ALFAMART")
                            <div class="text-yellow-800">
                                <p>Silahkan tunjukkan <strong>nomor pembayaran </strong> ke kasir alfamart agar pesanan dapat diproses</p>
                            </div>
                            @else
                            <div class="text-yellow-800">
                                <p>Gunakan Aplikasi <strong>Ewallet </strong> untuk melakukan pembayaran</p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
                           

<script>
    (() => {
        let introDismissed = false;
        let introTimer = null;
        let introLottieStartTimer = null;
        let introLottieSequenceTimer = null;

        function dismissDepositIntro() {
            if (introDismissed) {
                return;
            }

            introDismissed = true;

            const introOverlay = document.getElementById('depositIntroOverlay');
            const pageShell = document.querySelector('.deposit-page-shell');

            if (pageShell) {
                pageShell.classList.add('is-ready');
            }

            if (introOverlay) {
                introOverlay.classList.add('is-hiding');
                window.setTimeout(() => {
                    introOverlay.style.display = 'none';
                }, 750);
            }
        }

        function prepareDepositIntro() {
            const introOverlay = document.getElementById('depositIntroOverlay');
            const pageShell = document.querySelector('.deposit-page-shell');
            const lottiePlayer = document.getElementById('depositIntroLottie');

            introDismissed = false;

            if (introTimer) {
                window.clearTimeout(introTimer);
                introTimer = null;
            }

            if (introOverlay) {
                introOverlay.style.display = '';
                introOverlay.classList.remove('is-hiding');
                introOverlay.classList.add('is-visible');
            }

            if (pageShell) {
                pageShell.classList.remove('is-ready');
            }

            if (lottiePlayer) {
                try {
                    if (typeof lottiePlayer.stop === 'function') {
                        lottiePlayer.stop();
                    }

                    const introState = String(introOverlay?.dataset.state || '').toLowerCase();
                    const rawSequence = String(lottiePlayer.dataset.sequence || '');
                    const sequence = rawSequence.split('|').map((item) => item.trim()).filter(Boolean);
                    const introDuration = Number(introOverlay?.dataset.duration || 4300);

                    const playLottieSource = (src, loopMode = false) => {
                        if (!src) {
                            return;
                        }

                        if (typeof lottiePlayer.load === 'function') {
                            lottiePlayer.load(src);
                        } else {
                            lottiePlayer.setAttribute('src', src);
                        }

                        lottiePlayer.setAttribute('loop', loopMode ? 'true' : 'false');

                        if (typeof lottiePlayer.stop === 'function') {
                            lottiePlayer.stop();
                        }

                        introLottieStartTimer = window.setTimeout(() => {
                            if (typeof lottiePlayer.play === 'function') {
                                lottiePlayer.play();
                            }
                        }, 60);
                    };

                    if (introState === 'pending' && sequence.length > 0) {
                        playLottieSource(sequence[0], false);

                        if (sequence.length > 1) {
                            introLottieSequenceTimer = window.setTimeout(() => {
                                playLottieSource(sequence[1], true);
                            }, Math.max(180, Math.round(introDuration / 2)));
                        }
                    } else {
                        introLottieStartTimer = window.setTimeout(() => {
                            if (typeof lottiePlayer.play === 'function') {
                                lottiePlayer.play();
                            }
                        }, 60);
                    }
                } catch (error) {
                    console.debug('Deposit intro lottie replay skipped:', error);
                }
            }

            const introDuration = Number(introOverlay?.dataset.duration || 2800);
            introTimer = window.setTimeout(dismissDepositIntro, introDuration);
        }

        prepareDepositIntro();
        window.addEventListener('pageshow', function (event) {
            if (event && event.persisted) {
                prepareDepositIntro();
            }
        });

        window.addEventListener('pagehide', function () {
            if (introTimer) {
                window.clearTimeout(introTimer);
                introTimer = null;
            }

            if (introLottieStartTimer) {
                window.clearTimeout(introLottieStartTimer);
                introLottieStartTimer = null;
            }

            if (introLottieSequenceTimer) {
                window.clearTimeout(introLottieSequenceTimer);
                introLottieSequenceTimer = null;
            }
        });
    })();
</script>

<script>
    (function () {
        const toggleButton = document.getElementById('depositSummaryToggle');
        const content = document.getElementById('depositSummaryPanel');
        const chevron = document.getElementById('depositSummaryChevron');

        if (!toggleButton || !content) {
            return;
        }

        toggleButton.addEventListener('click', function () {
            const isHidden = content.classList.contains('hidden');

            content.classList.toggle('hidden');
            toggleButton.setAttribute('aria-expanded', String(isHidden));

            if (chevron) {
                chevron.classList.toggle('rotate-180', isHidden);
            }
        });
    })();
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
            // inputElement.setSelectionRange(0, 99999); 

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
            toastr.success('Total pembayaran berhasil disalin!');
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
    (function () {
        const countdownEl = document.getElementById('depositExpiryCountdown');

        if (!countdownEl) {
            return;
        }

        const rawExpiry = countdownEl.dataset.expiredAt;
        const paymentStatus = String(countdownEl.dataset.status || '').toLowerCase();
        const expiryTime = rawExpiry ? new Date(rawExpiry).getTime() : NaN;

        if (!Number.isFinite(expiryTime)) {
            countdownEl.textContent = 'Tidak tersedia';
            countdownEl.classList.add('is-expired');
            return;
        }

        if (['lunas', 'paid', 'success'].includes(paymentStatus)) {
            countdownEl.textContent = 'Pembayaran diterima';
            return;
        }

        if (paymentStatus === 'expired') {
            countdownEl.textContent = 'Pembayaran kedaluwarsa';
            countdownEl.classList.add('is-expired');
            return;
        }

        const renderCountdown = function () {
            const now = Date.now();
            const diff = expiryTime - now;

            if (diff <= 0) {
                countdownEl.textContent = 'Pembayaran kedaluwarsa';
                countdownEl.classList.add('is-expired');
                return true;
            }

            const totalSeconds = Math.floor(diff / 1000);
            const hours = Math.floor(totalSeconds / 3600);
            const minutes = Math.floor((totalSeconds % 3600) / 60);
            const seconds = totalSeconds % 60;

            countdownEl.textContent = [
                String(hours).padStart(2, '0'),
                String(minutes).padStart(2, '0'),
                String(seconds).padStart(2, '0'),
            ].join(':');

            return false;
        };

        renderCountdown();

        const countdownInterval = window.setInterval(function () {
            if (renderCountdown()) {
                window.clearInterval(countdownInterval);
            }
        }, 1000);
    })();
</script>

<script>
    $(document).ready(function() {
        let orderId = "{{ $data->id_pembelian }}";
        let isProcessing = false;
        let pollInterval = null;

        function checkStatus() {
            if (document.hidden || isProcessing) return;
            isProcessing = true;

            $.ajax({
                url: "{{ route('ajax.deposit-status', ['order' => ':order']) }}".replace(':order', orderId),
                method: "GET",
                success: function(response) {
                    if (response.success) {
                        let statusPembayaran = response.status_pembayaran;
                        let statusDeposit = response.status_deposit;

                        console.log("Status Check:", statusPembayaran, statusDeposit);

                        if (statusPembayaran === 'Expired') {
                            toastr.warning('Batas waktu pembayaran telah habis. Halaman akan dimuat ulang...');
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        } else if (statusPembayaran === 'Lunas' || statusPembayaran === 'PAID' || statusPembayaran === 'Success' || statusDeposit === 'Success') {
                            toastr.success('Pembayaran berhasil! Halaman akan dimuat ulang...');
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        }
                    }
                },
                complete: function() {
                    isProcessing = false;
                }
            });
        }

        // Poll every 3 seconds
        pollInterval = setInterval(checkStatus, 3000);

        window.addEventListener('beforeunload', function () {
            if (pollInterval) {
                clearInterval(pollInterval);
            }
        });
    });
</script>






@include('../footer')

@push('custom_script')



@endpush




@endsection
