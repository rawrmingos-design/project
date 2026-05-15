@extends('template.template')

@section('custom_style')
@endsection

@section('content')
@include('../navbar')

@php
    $minimumDeposit = 10000;
    $methods = collect($pay_method ?? []);
    $recentDeposits = collect($data ?? [])->take(6);
    $defaultMethod = $methods->firstWhere('tipe', 'qris') ?? $methods->first();
    $defaultPhone = old('no_telfon', Auth::user()->whatsapp ?? '');
@endphp

<x-dashboard-shell
    page-class="public-deposit-page"
    main-class="public-deposit-main"
    header-title="Top Up Saldo"
    header-description="Isi saldo akun kamu dengan metode pembayaran yang tersedia."
    header-class="public-dashboard-page-header--deposit"
>
    @if(session('success'))
        <div class="public-affiliate-notice is-success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="public-affiliate-notice is-error">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="public-affiliate-notice is-error">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="public-deposit-overview-card">
        <div>
            <p class="public-deposit-overview-card__label">Saldo Saat Ini</p>
            <strong class="public-deposit-overview-card__amount">
                Rp {{ number_format((int) (Auth::user()->balance ?? 0), 0, ',', '.') }}
            </strong>
        </div>
        <a href="{{ route('reload') }}" class="public-dashboard-button">Riwayat Deposit</a>
    </section>

    <form action="{{ route('deposit.store') }}" method="POST" id="topup-form">
        @csrf
        <input type="hidden" id="selected_method" name="metode" value="{{ old('metode', $defaultMethod?->code ?? '') }}">
        <input type="hidden" id="no_pembayaran" name="no_pembayaran" value="{{ $defaultPhone }}">

        <div class="public-deposit-grid">
            <section class="public-deposit-form-card">
                <div class="public-deposit-form-card__section">
                    <h2>1. Nominal Deposit</h2>
                    <label>
                        <span>Jumlah Deposit</span>
                        <input
                            id="deposit_amount"
                            type="number"
                            min="{{ $minimumDeposit }}"
                            name="jumlah"
                            value="{{ old('jumlah', '') }}"
                            placeholder="Minimal Rp {{ number_format($minimumDeposit, 0, ',', '.') }}"
                            required
                        >
                    </label>
                    <label>
                        <span>Nomor WhatsApp Aktif</span>
                        <input
                            id="deposit_phone"
                            type="text"
                            inputmode="numeric"
                            name="no_telfon"
                            value="{{ $defaultPhone }}"
                            placeholder="Contoh: 62812xxxx"
                            required
                        >
                    </label>
                </div>

                <div class="public-deposit-form-card__section">
                    <h2>2. Pilih Metode Pembayaran</h2>
                    <div class="public-deposit-method-grid" id="deposit_method_grid">
                        @forelse($methods as $method)
                            @php
                                $isActive = old('metode', $defaultMethod?->code ?? '') === $method->code;
                                $methodImage = trim((string) ($method->images ?? ''));
                                $feePercent = (float) ($method->fee_percent ?? 0);
                                $fixedFee = (float) ($method->fix_fee ?? 0);
                                $typeLabel = strtoupper((string) ($method->tipe ?? 'metode'));
                            @endphp
                            <button
                                type="button"
                                class="public-deposit-method-card js-deposit-method-card {{ $isActive ? 'is-active' : '' }}"
                                data-method="{{ $method->code }}"
                                data-fee-percent="{{ $feePercent }}"
                                data-fixed-fee="{{ $fixedFee }}"
                            >
                                <div class="public-deposit-method-card__head">
                                    <span>{{ $method->name }}</span>
                                    <small>{{ $typeLabel }}</small>
                                </div>
                                @if($methodImage !== '')
                                    <img src="{{ asset($methodImage) }}" alt="{{ $method->name }}" loading="lazy" decoding="async">
                                @endif
                                <p class="public-deposit-method-card__note">
                                    Biaya {{ rtrim(rtrim(number_format($feePercent, 2, '.', ''), '0'), '.') }}%
                                    + Rp {{ number_format((int) $fixedFee, 0, ',', '.') }}
                                </p>
                                <strong data-role="method-total">Isi nominal dulu</strong>
                            </button>
                        @empty
                            <p class="public-deposit-history-card__empty">Metode pembayaran belum tersedia.</p>
                        @endforelse
                    </div>
                </div>

                <div class="public-deposit-summary">
                    <div class="public-deposit-summary__row">
                        <span>Nominal</span>
                        <strong id="summary_nominal">Rp 0</strong>
                    </div>
                    <div class="public-deposit-summary__row">
                        <span>Biaya</span>
                        <strong id="summary_fee">Rp 0</strong>
                    </div>
                    <div class="public-deposit-summary__row is-total">
                        <span>Total Pembayaran</span>
                        <strong id="summary_total">Rp 0</strong>
                    </div>
                    <button id="deposit_submit" type="submit" class="public-deposit-summary__submit">
                        Top Up Sekarang
                    </button>
                </div>
            </section>

            <aside class="public-deposit-history-card">
                <h2>Riwayat Terbaru</h2>
                @if($recentDeposits->isNotEmpty())
                    <ul class="public-deposit-history-card__list">
                        @foreach($recentDeposits as $item)
                            @php
                                $statusRaw = strtolower(trim((string) ($item->status ?? 'pending')));
                                $statusTone = 'pending';
                                $statusLabel = Str::title((string) ($item->status ?? 'Pending'));

                                if (in_array($statusRaw, ['success', 'sukses', 'berhasil', 'paid'], true)) {
                                    $statusTone = 'success';
                                    $statusLabel = 'Success';
                                } elseif (in_array($statusRaw, ['failed', 'gagal', 'cancelled', 'batal'], true)) {
                                    $statusTone = 'failed';
                                    $statusLabel = 'Failed';
                                } elseif (in_array($statusRaw, ['process', 'processing', 'diproses', 'proses'], true)) {
                                    $statusTone = 'processing';
                                    $statusLabel = 'Process';
                                }
                            @endphp
                            <li>
                                <div>
                                    <a href="{{ route('deposit.invoice', $item->order_id) }}">{{ $item->order_id }}</a>
                                    <p>{{ $item->metode ?: '-' }}</p>
                                    <small>{{ optional($item->created_at)->format('Y-m-d H:i:s') ?: '-' }}</small>
                                </div>
                                <div class="public-deposit-history-card__meta">
                                    <strong>Rp {{ number_format((int) ($item->jumlah ?? 0), 0, ',', '.') }}</strong>
                                    <span class="public-dashboard-table__badge public-dashboard-table__badge--{{ $statusTone }}">
                                        {{ $statusLabel }}
                                    </span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="public-deposit-history-card__empty">Belum ada transaksi deposit.</p>
                @endif
            </aside>
        </div>
    </form>
</x-dashboard-shell>

@include('../footer')

@push('custom_script')
<script>
    (function () {
        const minimumAmount = {{ (int) $minimumDeposit }};
        const amountInput = document.getElementById('deposit_amount');
        const phoneInput = document.getElementById('deposit_phone');
        const phonePaymentInput = document.getElementById('no_pembayaran');
        const methodInput = document.getElementById('selected_method');
        const submitButton = document.getElementById('deposit_submit');
        const methodCards = Array.from(document.querySelectorAll('.js-deposit-method-card'));
        const summaryNominal = document.getElementById('summary_nominal');
        const summaryFee = document.getElementById('summary_fee');
        const summaryTotal = document.getElementById('summary_total');

        function toRupiah(value) {
            const safeValue = Number.isFinite(value) ? Math.max(0, value) : 0;
            return `Rp ${new Intl.NumberFormat('id-ID').format(Math.ceil(safeValue))}`;
        }

        function calculateFee(amount, percent, fixedFee) {
            if (amount <= 0) {
                return 0;
            }
            return Math.ceil((amount * (percent / 100)) + fixedFee);
        }

        function getSelectedCard() {
            return methodCards.find((card) => card.classList.contains('is-active')) || null;
        }

        function setActiveCard(cardToActivate) {
            methodCards.forEach((card) => {
                card.classList.toggle('is-active', card === cardToActivate);
            });

            if (cardToActivate) {
                methodInput.value = cardToActivate.dataset.method || '';
            }
        }

        function updateSummary() {
            const amount = Number(amountInput?.value || 0);
            const selectedCard = getSelectedCard();
            const percent = Number(selectedCard?.dataset.feePercent || 0);
            const fixedFee = Number(selectedCard?.dataset.fixedFee || 0);
            const fee = calculateFee(amount, percent, fixedFee);
            const total = amount + fee;
            const phoneValue = String(phoneInput?.value || '').trim();

            if (summaryNominal) summaryNominal.textContent = toRupiah(amount);
            if (summaryFee) summaryFee.textContent = toRupiah(fee);
            if (summaryTotal) summaryTotal.textContent = toRupiah(total);
            if (phonePaymentInput) phonePaymentInput.value = phoneValue;

            methodCards.forEach((card) => {
                const methodPercent = Number(card.dataset.feePercent || 0);
                const methodFixed = Number(card.dataset.fixedFee || 0);
                const methodFee = calculateFee(amount, methodPercent, methodFixed);
                const methodTotal = amount + methodFee;
                const totalLabel = card.querySelector('[data-role="method-total"]');

                if (!totalLabel) return;

                totalLabel.textContent = amount > 0 ? toRupiah(methodTotal) : 'Isi nominal dulu';
            });

            const isReady = Boolean(selectedCard) && amount >= minimumAmount && phoneValue.length >= 8;
            if (submitButton) {
                submitButton.disabled = !isReady;
            }
        }

        if (methodCards.length > 0) {
            const activeFromMarkup = methodCards.find((card) => card.classList.contains('is-active'));
            setActiveCard(activeFromMarkup || methodCards[0]);

            methodCards.forEach((card) => {
                card.addEventListener('click', () => {
                    setActiveCard(card);
                    updateSummary();
                });
            });
        }

        if (amountInput) {
            amountInput.addEventListener('input', updateSummary);
        }

        if (phoneInput) {
            phoneInput.addEventListener('input', updateSummary);
        }

        updateSummary();
    })();
</script>
@endpush
@endsection
