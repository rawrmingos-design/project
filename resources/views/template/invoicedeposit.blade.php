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

  @media (max-width: 767px) {
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
    $depositIntroDuration = 4000;

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

    $depositIntroUsesLottie = $depositIntroState === 'expired';
    $depositIntroLottieSrc = $depositIntroUsesLottie
        ? asset('assets/invoice-intro/lottie/expired.json')
        : null;
@endphp

<div id="depositIntroOverlay" class="deposit-intro-overlay is-visible print:hidden" data-state="{{ $depositIntroState }}" data-duration="{{ $depositIntroDuration }}">
    <div class="deposit-intro-card">
        @if ($depositIntroUsesLottie)
            <div class="deposit-intro-lottie-shell" aria-hidden="true">
                <lottie-player
                    id="depositIntroLottie"
                    src="{{ $depositIntroLottieSrc }}"
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
            <div class="flex flex-col-reverse items-end justify-between gap-8 print:mt-0 print:flex-row print:items-start print:gap-0 md:mt-0 md:flex-row md:items-start md:gap-0">
                <div class="max-w-3xl">
                    <h1 class="text-base font-medium text-primary-500">{{ $depositHeroEyebrow }}</h1>
                    <p class="mt-2 text-4xl font-bold tracking-tight">{{ $depositHeroTitle }}</p>
                    <p class="mt-2 text-base text-murky-200">{{ $depositHeroDescription }}</p>
                </div>
            </div>
            <div class="mt-8 flex flex-col items-end justify-between gap-8 print:flex-row md:flex-row">
                <dl class="w-full text-left text-sm font-medium md:w-auto deposit-expiry-card p-5">
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
                <div class="absolute top-4 right-4 print:hidden md:static mt-3">
                    <button
                        class="inline-flex items-center justify-center rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white duration-300 hover:bg-primary-400 disabled:cursor-not-allowed disabled:opacity-75 flex items-center space-x-2"
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
            <div class="my-8 border-y border-murky-600 py-8">
                <div class="grid grid-cols-2 gap-8">
                    <div class="col-span-2 flex gap-8 lg:col-span-1">
                   
                        <div>
                            <h3 class="text-lg font-medium text-white print:text-sm print:text-slate-800"><a href="" style="outline: none;"> </a></h3>
                            <p class="text-sm">{{ $data->layanan }}</p>
                            <div>
                                <div class="mt-8 text-sm font-medium text-murky-200 print:text-slate-800">
                                    <div class="grid grid-cols-3 gap-4 pb-2">
                                        <div class="text-white print:text-slate-800">Deposit Dengan No Invoice {{ $data->id_pembelian }} </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-span-2 row-span-3 lg:col-span-1">
                        <div class="w-full flex-1 print:pt-0 md:flex-auto md:pt-0">
                            <dl class="gap-x-8 text-sm">
                                <div class="w-full">
                                    <dt class="text-lg font-medium text-white print:text-sm print:text-slate-800">Metode Pembayaran</dt>
                                    <dd class="text-murky-200">
                                        <div class="flex items-start space-x-4 print:text-slate-800">
                                            <div class="text-sm text-white">QRIS DEPOSIT</div></div>
                                    </dd>
                                    <div class="mt-8 grid w-full grid-cols-8 gap-4 border-t border-murky-600 pt-8 text-left text-murky-200 print:border-slate-200 print:text-slate-800 md:gap-x-2">
                                    <div class="col-span-3 flex items-center text-white print:text-slate-800 md:col-span-4">Nomor Invoice</div>
                                    <div class="col-span-5 text-white print:text-slate-800 md:col-span-4">
                                        <button type="button" id="copyButton1" class="flex items-center space-x-2 rounded-md border border-murky-400 bg-murky-600 px-2.5 py-1 hover:bg-murky-700 print:hidden" onclick="copyToClipboard('copyButton1')">
                                            <div class="max-w-[172px] truncate md:w-auto md:max-w-none">{{ $data->id_pembelian }}</div>
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-5 w-4">
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184"
                                                ></path>
                                            </svg>
                                        </button>
                                        <span class="hidden print:block"></span>
                                    </div>
                                        <!--conditional status pembelian & pembayaran-->
                                        @php
                                            $statuscolor = '';
                                            
                                            if($data->status_pembelian == "Pending"){
                                                $statuscolor = 'yellow';
                                            } elseif($data->status_pembelian == "Sukses" || $data->status_pembelian == "Success"){
                                                $statuscolor = 'green';
                                            } elseif($data->status_pembelian == "Proses"){
                                                $statuscolor = 'cyan';
                                            } else {
                                                $statuscolor = 'rose';
                                            }
                                        @endphp
                                        <!--<div class="col-span-3 text-white print:text-slate-800 md:col-span-4">Status Transaksi</div>-->
                                        <!--<div class="col-span-5 md:col-span-4"><span class="inline-flex rounded-sm px-2 text-xs font-semibold leading-5 print:p-0 bg-{{$statuscolor}}-300 text-{{$statuscolor}}-800">-->
                                        <!--    @if($data->status_pembelian == "Pending")-->
                                        <!--        Pending-->
                                        <!--    @elseif($data->status_pembelian == "Proses")-->
                                        <!--        Processing-->
                                        <!--    @elseif($data->status_pembelian == "Sukses" || $data->status_pembelian == "Success")-->
                                        <!--        Sukses-->
                                        <!--    @endif-->
                                        <!--</span></div>-->
                                         @php
                                            $pembayarancolor = '';
                                            
                                            if($data->status_pembayaran == "Belum Lunas"){
                                                $pembayarancolor = 'rose';
                                            } elseif($data->status_pembayaran == "Success" || $data->status_pembayaran == "Lunas"){
                                                $pembayarancolor = 'green';
                                            }else {
                                                $pembayarancolor = 'rose';
                                            }
                                        @endphp
                                        <div class="col-span-3 text-white print:text-slate-800 md:col-span-4">Status Pembayaran</div>
                                        <div class="col-span-5 md:col-span-4"><span id="badge-unpaid" class="inline-flex rounded-sm px-2 text-xs font-semibold leading-5 print:p-0 bg-{{$pembayarancolor}}-300 text-{{$pembayarancolor}}-800">
                                        @if($data->status_pembayaran == "Belum Lunas")
                                               <div class="whitespace-nowrap"> <span class="inline-flex rounded-sm px-2 text-xs font-semibold leading-5 print:p-0 bg-rose-300 text-emerald-900">Unpaid</span> </div>
                                            @elseif($data->status_pembayaran == "PAID" || $data->status_pembayaran == "Lunas")
                                                <td class="table-cell px-3 py-3.5 text-left text-xs font-medium text-white first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&amp;:nth-last-child(2)]:table-cell">
                                                   <div class="whitespace-nowrap">
                                                        <span class="inline-flex rounded-sm px-2 text-xs font-semibold leading-5 print:p-0 bg-emerald-200 text-emerald-900">Paid</span>
                                                    </div>
                                                </td>
                                            @elseif($data->status_pembayaran == "Expired")
                                                <div class="whitespace-nowrap"> <span class="inline-flex rounded-sm px-2 text-xs font-semibold leading-5 print:p-0 bg-slate-300 text-slate-900">Expired</span> </div>
                                            @else
                                                <div class="whitespace-nowrap"> <span class="inline-flex rounded-sm px-2 text-xs font-semibold leading-5 print:p-0 bg-rose-300 text-emerald-900">Expired</span> </div>
                                            @endif
                                        </span></div>
                                        <div class="col-span-3 text-white print:text-slate-800 md:col-span-4">Pesan</div>
                                        <div class="col-span-5 md:col-span-4">
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
                                        @if($data->voucher !== null)
                                        <div class="col-span-3 flex items-center text-white print:text-slate-800 md:col-span-4">Kode Voucher / SN</div>
                                        <div class="col-span-5 text-white print:text-slate-800 md:col-span-4">
                                            <button type="button" class="flex items-center space-x-2 rounded-md border border-murky-400 bg-murky-600 px-2.5 py-1 hover:bg-murky-700 print:hidden">
                                                <div class="max-w-[172px] truncate md:w-auto md:max-w-none">{{ $data->voucher }}</div>
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-5 w-4">
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184"
                                                    ></path>
                                                </svg>
                                            </button>
                                            <span class="hidden print:block"></span>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </dl>
                            @if(Str::upper($data->metode_pembayaran) == "QRIS" || Str::upper($data->metode_pembayaran) == "QRISC" || Str::upper($data->metode_pembayaran) == "QRIS2" || Str::upper($data->metode_pembayaran) == "QRISOP" || Str::upper($data->metode_pembayaran) == "SP" || Str::upper($data->metode_pembayaran) == "SQ")
                            <div class="relative mt-8 flex flex-col items-center justify-center rounded-lg bg-white p-4">
                                <h3 class="mb-4 text-gray-800 font-bold">Scan QRIS / Lanjut Bayar</h3>
                                <div id="qris-payment" class="w-full flex justify-center">
                                    {{-- Logic: If URL contains 'duitku', it's a payment page -> Show Button --}}
                                    @if(str_contains($data->no_pembayaran, 'duitku') || str_contains($data->no_pembayaran, 'sandbox.duitku.com') || str_contains($data->no_pembayaran, 'passport.duitku.com'))
                                         <a href="{{ $data->no_pembayaran }}" target="_blank" class="w-full inline-flex items-center justify-center rounded-md bg-blue-600 px-4 py-3 text-sm font-bold text-white shadow-lg transition-all duration-300 hover:bg-blue-700 hover:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                            Buka Halaman Pembayaran
                                         </a>
                                    {{-- Logic: If it is a URL but NOT Duitku, it's likely a QR Image (TriPay/TokoPay) -> Show Image --}}
                                    @elseif(filter_var($data->no_pembayaran, FILTER_VALIDATE_URL))
                                         <a href="{{ $data->no_pembayaran }}" target="_blank">
                                            <img src="{{ $data->no_pembayaran }}" alt="QRIS Code" class="mx-auto mb-2 max-w-[200px]">
                                         </a>
                                    {{-- Logic: If not a URL, it is a Raw QR String -> Generate QR Image --}}
                                    @else
                                         <div class="text-center">
                                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ $data->no_pembayaran }}" alt="QRIS Code" class="mx-auto mb-2">
                                            <p class="mt-2 text-xs text-gray-400 font-mono break-all max-w-[200px] mx-auto">{{ $data->no_pembayaran }}</p>
                                         </div>
                                    @endif
                                </div>
                            </div>
                            @endif
                            
                            @if(Str::upper($data->metode_pembayaran) == "SHOPEEPAY" || Str::upper($data->metode_pembayaran) == "OVOPUSH" || Str::upper($data->metode_pembayaran) == "DANA" || Str::upper($data->metode_pembayaran) == "LINKAJA" || Str::upper($data->metode_pembayaran) == "11" || Str::upper($data->metode_pembayaran) == "17" || Str::upper($data->metode_pembayaran) == "23")
                            <a href="{{$data->no_pembayaran}}" target="_blank">
                                <button class="mt-8 inline-flex items-center justify-center rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white duration-300 hover:bg-primary-400 disabled:cursor-not-allowed disabled:opacity-75 w-full space-x-2 pr-3 sm:w-auto" type="button">
                                    <span>Klik di sini untuk melakukan pembayaran</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-4 w-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"></path>
                                    </svg>
                                </button>
                            </a>
                            @endif
                        </div>
                    </div>
                    <div class="col-span-2 col-start-1 row-start-2 lg:col-span-1">
                    
                       <div class="mb-8 mt-4 flex items-center justify-between text-primary-500">
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
                        <div class="border-l-4 border-yellow-300 bg-yellow-100 p-4 print:hidden">
                            <div>
                                
                             @if(Str::upper($data->metode_pembayaran) == "QRIS" || Str::upper($data->metode_pembayaran) == "QRISC" || Str::upper($data->metode_pembayaran) == "QRIS2" || Str::upper($data->metode_pembayaran) == "QRISOP" || Str::upper($data->metode_pembayaran) == "SP" )
                                <div class="text-yellow-800 print:hidden">
                                    <p>Gunakan <strong>Ewallet </strong>atau <strong>aplikasi mobile banking</strong> yang tersedia scan QRIS</p>
                                </div>
                                @elseif(Str::upper($data->metode_pembayaran) == "BRIVA" || Str::upper($data->metode_pembayaran) == "BCAVA" || Str::upper($data->metode_pembayaran) == "BNIVA" || Str::upper($data->metode_pembayaran) == "MANDIRIVA" || Str::upper($data->metode_pembayaran) == "PERMATAVA" || Str::upper($data->metode_pembayaran) == "CIMBVA" || Str::upper($data->metode_pembayaran) == "DANAMONVA" || Str::upper($data->metode_pembayaran) == "BSIVA")
                                 <div class="text-yellow-800 print:hidden">
                                    <p>Gunakan <strong>aplikasi mobile banking</strong> untuk melakukan pembayaran</p>
                                </div>
                                @elseif(Str::upper($data->metode_pembayaran) == "INDOMARET")
                                <div class="text-yellow-800 print:hidden">
                                    <p>Silahkan tunjukkan <strong>nomor pembayaran </strong> ke kasir indomaret agar pesanan dapat diproses</p>
                                </div>
                                @elseif(Str::upper($data->metode_pembayaran) == "ALFAMART")
                                <div class="text-yellow-800 print:hidden">
                                    <p>Silahkan tunjukkan <strong>nomor pembayaran </strong> ke kasir alfamart agar pesanan dapat diproses</p>
                                </div>
                                @else
                                <div class="text-yellow-800 print:hidden">
                                    <p>Gunakan Aplikasi <strong>Ewallet </strong> untuk melakukan pembayaran</p>
                                </div>
                                @endif
                            </div>
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

                    window.setTimeout(() => {
                        if (typeof lottiePlayer.play === 'function') {
                            lottiePlayer.play();
                        }
                    }, 60);
                } catch (error) {
                    console.debug('Deposit intro lottie replay skipped:', error);
                }
            }

            const introDuration = Number(introOverlay?.dataset.duration || 2800);
            introTimer = window.setTimeout(dismissDepositIntro, introDuration);
        }

        prepareDepositIntro();
        window.addEventListener('pageshow', prepareDepositIntro);
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
            toastr.success('No pembayaran berhasil disalin!');
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
