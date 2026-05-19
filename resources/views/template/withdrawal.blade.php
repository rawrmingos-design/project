@extends('template.template')

@section('custom_style')
@endsection

@section('content')
@include('../navbar')

@php
    $currentBalance = (int) round((float) (Auth::user()->balance ?? 0));
    $isWithdrawalDisabled = $hasRequestedToday || $currentBalance < 10000;
    $withdrawalSubmitLabel = $hasRequestedToday
        ? 'Sudah ditarik hari ini'
        : ($currentBalance < 10000 ? 'Saldo belum mencukupi' : 'Kirim Permintaan');
@endphp

<x-dashboard-shell
    page-class="public-affiliate-page public-affiliate-withdrawal-page"
    header-title="Pembayaran Afiliasi"
    header-description="Tarik komisi affiliate kamu ke rekening atau e-wallet yang valid."
    header-class="public-dashboard-page-header--affiliate"
>
    @if (session('success'))
        <div class="public-affiliate-notice is-success">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
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

    <nav class="public-affiliate-tabs" aria-label="Tab afiliasi">
        <a href="{{ route('affiliate') }}">Riwayat</a>
        <a href="{{ route('withdrawal') }}" class="is-active">Pembayaran</a>
    </nav>

    <section class="public-affiliate-overview public-withdrawal-balance-grid">
        <article class="public-affiliate-overview__card is-highlight">
            <p>Saldo Saat Ini</p>
            <strong>Rp {{ number_format($currentBalance, 0, ',', '.') }}</strong>
            <span>Nominal komisi yang sudah masuk ke akun kamu.</span>
        </article>
    </section>

    <section class="public-withdrawal-form-card">
        <header class="public-withdrawal-form-card__header">
            <h2>Form Penarikan</h2>
            <p>Isi data tujuan pembayaran dengan benar. Permintaan hanya bisa 1 kali per hari.</p>
        </header>

        <form
            id="withdrawal-form"
            action="{{ route('process.withdrawal') }}"
            method="POST"
            class="public-withdrawal-form"
            data-withdrawal-form
            data-max-balance="{{ $currentBalance }}"
        >
            @csrf

            <div class="public-withdrawal-form__grid">
                <label>
                    <span>Nama Bank / E-Wallet</span>
                    <select name="bank_destination" required>
                        <option value="">Pilih tujuan</option>
                        @foreach (['BCA','BNI','BRI','MANDIRI','DANA','OVO','GOPAY','SHOPEEPAY'] as $bank)
                            <option value="{{ $bank }}" @selected(old('bank_destination') === $bank)>{{ $bank }}</option>
                        @endforeach
                    </select>
                    @error('bank_destination')
                        <small>{{ $message }}</small>
                    @enderror
                </label>

                <label>
                    <span>Nomor Rekening / HP</span>
                    <input
                        type="text"
                        name="account_number"
                        inputmode="numeric"
                        value="{{ old('account_number') }}"
                        placeholder="Contoh: 62812xxxx / 1234567890"
                        required
                    >
                    @error('account_number')
                        <small>{{ $message }}</small>
                    @enderror
                </label>

                <label class="is-full">
                    <span>Nama Pemilik Rekening</span>
                    <input
                        type="text"
                        name="account_name"
                        value="{{ old('account_name') }}"
                        placeholder="Sesuai nama pemilik rekening/e-wallet"
                        required
                    >
                    @error('account_name')
                        <small>{{ $message }}</small>
                    @enderror
                </label>

                <label class="is-full">
                    <div class="mb-1 flex items-center justify-between gap-2">
                        <span>Jumlah Penarikan (Min. Rp 10.000)</span>
                        <button
                            type="button"
                            data-withdrawal-max-btn
                            class="inline-flex h-7 items-center rounded-full border border-white/20 px-3 text-xs font-semibold text-white transition hover:border-orange-400/40 hover:bg-orange-500/10 disabled:cursor-not-allowed disabled:opacity-50"
                            @disabled($isWithdrawalDisabled)
                        >
                            Max Saldo
                        </button>
                    </div>
                    <input
                        type="number"
                        min="10000"
                        max="{{ $currentBalance }}"
                        name="amount"
                        value="{{ old('amount') }}"
                        placeholder="10000"
                        required
                    >
                    <small>Maksimal penarikan: Rp {{ number_format($currentBalance, 0, ',', '.') }}</small>
                    @error('amount')
                        <small>{{ $message }}</small>
                    @enderror
                </label>
            </div>

            <div class="public-withdrawal-form__actions">
                <p>
                    @if($hasRequestedToday)
                        Kamu sudah melakukan penarikan hari ini. Coba lagi besok.
                    @elseif($currentBalance < 10000)
                        Saldo minimal untuk melakukan penarikan adalah Rp 10.000.
                    @else
                        Pastikan data rekening benar agar proses verifikasi admin berjalan cepat.
                    @endif
                </p>

                <button
                    type="submit"
                    data-withdrawal-submit
                    data-default-label="{{ $withdrawalSubmitLabel }}"
                    data-loading-label="Memproses..."
                    data-locked="{{ $isWithdrawalDisabled ? '1' : '0' }}"
                    @disabled($isWithdrawalDisabled)
                >
                    {{ $withdrawalSubmitLabel }}
                </button>
            </div>
        </form>
    </section>

    <section class="public-dashboard-table public-dashboard-table--history">
        <div class="public-affiliate-history__header">
            <h2>Riwayat Penarikan</h2>
        </div>
        <div class="public-dashboard-table__shell">
            @if($withdrawals->count() > 0)
                <table class="public-dashboard-table__table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Tujuan</th>
                            <th>Jumlah</th>
                            <th>Biaya Admin</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($withdrawals as $row)
                            @php
                                $statusRaw = strtolower(trim((string) ($row->status ?? 'pending')));
                                $statusTone = 'pending';
                                $statusLabel = Str::title((string) ($row->status ?? 'Pending'));

                                if (in_array($statusRaw, ['success', 'sukses', 'berhasil', 'paid'], true)) {
                                    $statusTone = 'success';
                                    $statusLabel = 'Success';
                                } elseif (in_array($statusRaw, ['proses', 'processing', 'process', 'diproses'], true)) {
                                    $statusTone = 'processing';
                                    $statusLabel = 'Process';
                                } elseif (in_array($statusRaw, ['gagal', 'failed', 'cancelled', 'canceled', 'ditolak', 'rejected'], true)) {
                                    $statusTone = 'failed';
                                    $statusLabel = 'Failed';
                                }
                            @endphp
                            <tr>
                                <td>{{ optional($row->created_at)->format('d M Y, H:i') ?: '-' }}</td>
                                <td>{{ $row->rekening ?: '-' }}</td>
                                <td>Rp {{ number_format((int) ($row->total_transfer ?? 0), 0, ',', '.') }}</td>
                                <td>Rp {{ number_format((int) ($row->biaya_admin ?? 0), 0, ',', '.') }}</td>
                                <td>
                                    <span class="public-dashboard-table__badge public-dashboard-table__badge--{{ $statusTone }}">
                                        {{ $statusLabel }}
                                    </span>
                                    @if(!empty($row->bukti_transfer))
                                        <a class="public-withdrawal-proof-link" href="{{ asset($row->bukti_transfer) }}" target="_blank" rel="noopener noreferrer">Bukti</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="public-dashboard-table__empty">Belum ada riwayat penarikan.</div>
            @endif
        </div>

        @if(method_exists($withdrawals, 'hasPages') && $withdrawals->hasPages())
            <div class="public-affiliate-pagination">
                <span>Halaman {{ $withdrawals->currentPage() }} dari {{ $withdrawals->lastPage() }}</span>
                <div class="flex items-center gap-2">
                    @if($withdrawals->onFirstPage())
                        <span class="is-disabled">Sebelumnya</span>
                    @else
                        <a href="{{ $withdrawals->previousPageUrl() }}">Sebelumnya</a>
                    @endif

                    @if($withdrawals->hasMorePages())
                        <a href="{{ $withdrawals->nextPageUrl() }}">Berikutnya</a>
                    @else
                        <span class="is-disabled">Berikutnya</span>
                    @endif
                </div>
            </div>
        @endif
    </section>
</x-dashboard-shell>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('[data-withdrawal-form]');
    if (!form) {
        return;
    }

    const bankDestinationField = form.querySelector('select[name="bank_destination"]');
    const accountNumberField = form.querySelector('input[name="account_number"]');
    const accountNameField = form.querySelector('input[name="account_name"]');
    const amountField = form.querySelector('input[name="amount"]');
    const maxButton = form.querySelector('[data-withdrawal-max-btn]');
    const submitButton = form.querySelector('[data-withdrawal-submit]');

    if (!submitButton || !amountField) {
        return;
    }

    const lockedByBusinessRule = submitButton.dataset.locked === '1';
    const maxBalance = Number(form.dataset.maxBalance || 0);
    const minimumAmount = Number(amountField.min || 10000);
    let isSubmitting = false;

    const isFilled = (field) => {
        if (!field) {
            return false;
        }

        return String(field.value || '').trim().length > 0;
    };

    const getAmount = () => {
        const parsed = Number(amountField.value || 0);
        return Number.isFinite(parsed) ? parsed : 0;
    };

    const canSubmit = () => {
        if (lockedByBusinessRule || isSubmitting) {
            return false;
        }

        if (!isFilled(bankDestinationField) || !isFilled(accountNumberField) || !isFilled(accountNameField)) {
            return false;
        }

        const amount = getAmount();
        if (amount < minimumAmount) {
            return false;
        }

        return amount <= maxBalance;
    };

    const syncSubmitState = () => {
        const disabled = !canSubmit();
        submitButton.disabled = disabled;
        submitButton.setAttribute('aria-disabled', disabled ? 'true' : 'false');

        if (!isSubmitting) {
            submitButton.textContent = submitButton.dataset.defaultLabel || 'Kirim Permintaan';
        }
    };

    const applyMaxBalance = () => {
        if (lockedByBusinessRule || !maxButton || maxButton.disabled) {
            return;
        }

        amountField.value = String(maxBalance);
        amountField.dispatchEvent(new Event('input', { bubbles: true }));
        amountField.dispatchEvent(new Event('change', { bubbles: true }));
        amountField.focus();
    };

    [bankDestinationField, accountNumberField, accountNameField, amountField].forEach((field) => {
        if (!field) {
            return;
        }

        field.addEventListener('input', syncSubmitState);
        field.addEventListener('change', syncSubmitState);
    });

    if (maxButton) {
        maxButton.addEventListener('click', applyMaxBalance);
    }

    form.addEventListener('submit', (event) => {
        if (!canSubmit()) {
            event.preventDefault();
            syncSubmitState();
            return;
        }

        if (isSubmitting) {
            event.preventDefault();
            return;
        }

        isSubmitting = true;
        submitButton.disabled = true;
        submitButton.setAttribute('aria-disabled', 'true');
        submitButton.textContent = submitButton.dataset.loadingLabel || 'Memproses...';
    });

    syncSubmitState();
});
</script>

@include('../footer')
@endsection
