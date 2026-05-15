@extends('template.template')

@section('custom_style')
@endsection

@section('content')

@include('../navbar')

@php
    $dashboardUser = Auth::user();
    $dashboardDisplayName = Str::title((string) ($dashboardUser?->name ?: $dashboardUser?->username ?: 'Member'));
    $dashboardAvatarFallback = 'https://ui-avatars.com/api/?color=FFFFFF&background=50a7ff&name=' . urlencode($dashboardDisplayName);
    $dashboardAvatarCandidate = trim((string) ($dashboardUser?->google_avatar ?? ''));

    if ($dashboardAvatarCandidate !== '' && ! str_starts_with($dashboardAvatarCandidate, 'http://') && ! str_starts_with($dashboardAvatarCandidate, 'https://')) {
        $dashboardAvatarCandidate = '/' . ltrim($dashboardAvatarCandidate, '/');
    }

    $dashboardAvatarUrl = $dashboardAvatarCandidate !== '' ? $dashboardAvatarCandidate : $dashboardAvatarFallback;
    $dashboardPhone = trim((string) ($dashboardUser?->no_wa ?? '')) ?: '---';

    $legacyPeriodStats = is_array($period_stats ?? null) ? $period_stats : [];
    $legacyDefaultPeriod = $period_default ?? '30d';
    $legacyActivePeriodKey = array_key_exists($legacyDefaultPeriod, $legacyPeriodStats)
        ? $legacyDefaultPeriod
        : array_key_first($legacyPeriodStats);

    $legacyFallbackPeriod = [
        'label' => 'Hari ini',
        'totalTransactions' => (int) $banyak_pembelian,
        'totalSales' => (int) $total_pembelian,
        'waiting' => (int) $banyak_pembelian_pending,
        'processing' => (int) ($banyak_pembelian_proses ?? 0),
        'success' => (int) $banyak_pembelian_success,
        'failed' => (int) $banyak_pembelian_batal,
    ];

    $legacyActivePeriod = $legacyActivePeriodKey
        ? ($legacyPeriodStats[$legacyActivePeriodKey] ?? $legacyFallbackPeriod)
        : $legacyFallbackPeriod;

    $legacyPeriodButtonLabels = [
        '1d' => 'Hari ini',
        '7d' => '7 Hari',
        '30d' => '30 Hari',
    ];
@endphp

<section class="public-dashboard-page">
    <div class="public-shell">
        <div class="public-dashboard">
            @include('components.sidebar-dashboard')

            <main class="public-dashboard-main">
                <article class="public-dashboard-alert">
                    <div class="public-dashboard-alert__copy">
                        <h2>Tingkatkan keamanan!</h2>
                        <p>
                            Gunakan fitur 2FA agar akun kamu lebih aman.
                            <a href="{{ route('editProfile') }}">Klik di sini</a>
                            untuk melakukan pengaturan!
                        </p>
                    </div>
                    <span class="public-dashboard-alert__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M12 3 5 6v6c0 4.3 2.9 8.2 7 9 4.1-.8 7-4.7 7-9V6l-7-3Z" stroke="currentColor" stroke-width="1.8"></path>
                            <path d="m9.5 12 1.8 1.8 3.5-3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                    </span>
                </article>

                <section class="public-dashboard-headcards">
                    <article class="public-dashboard-card public-dashboard-card--profile">
                        <div class="public-dashboard-profile">
                            <img
                                src="{{ $dashboardAvatarUrl }}"
                                alt="{{ $dashboardDisplayName }}"
                                class="public-dashboard-profile__avatar"
                                onerror="this.onerror=null;this.src='{{ $dashboardAvatarFallback }}';"
                            >
                            <div class="public-dashboard-profile__copy">
                                <h3>{{ Str::title((string) ($dashboardUser?->name ?? '-')) }}</h3>
                                <span>{{ Str::title((string) ($dashboardUser?->role ?? 'Member')) }}</span>
                            </div>
                            <a href="{{ route('editProfile') }}" class="public-dashboard-profile__settings" aria-label="Buka pengaturan akun">
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z" stroke="currentColor" stroke-width="1.6"></path>
                                    <path d="m19.4 15-1.1-.6a7.7 7.7 0 0 0 0-4.8l1.1-.6a1 1 0 0 0 .4-1.4l-1-1.8a1 1 0 0 0-1.3-.4l-1.1.6a7.6 7.6 0 0 0-4.1-2.4V2.5a1 1 0 0 0-1-1h-2a1 1 0 0 0-1 1v1.3a7.6 7.6 0 0 0-4.1 2.4l-1.1-.6a1 1 0 0 0-1.3.4l-1 1.8a1 1 0 0 0 .4 1.4l1.1.6a7.7 7.7 0 0 0 0 4.8l-1.1.6a1 1 0 0 0-.4 1.4l1 1.8a1 1 0 0 0 1.3.4l1.1-.6a7.6 7.6 0 0 0 4.1 2.4v1.3a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1v-1.3a7.6 7.6 0 0 0 4.1-2.4l1.1.6a1 1 0 0 0 1.3-.4l1-1.8a1 1 0 0 0-.4-1.4Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></path>
                                </svg>
                            </a>
                        </div>
                        <div class="public-dashboard-profile__meta">
                            <span>{{ $dashboardPhone }}</span>
                        </div>
                    </article>

                    <article class="public-dashboard-card public-dashboard-card--credits">
                        <div class="public-dashboard-credits">
                            <p class="public-dashboard-credits__label">KoinKredits</p>
                            <p class="public-dashboard-credits__amount">
                                {{ number_format((int) ($dashboardUser?->balance ?? 0), 0, ',', '.') }} <strong>Koin</strong>
                            </p>
                        </div>
                        <div class="public-dashboard-credits__actions">
                            @if($dashboardUser && method_exists($dashboardUser, 'isAffiliateActive') && $dashboardUser->isAffiliateActive())
                                <a href="{{ route('withdrawal') }}" class="public-dashboard-button public-dashboard-button--primary">Redeem</a>
                            @else
                                <a href="{{ route('deposit') }}" class="public-dashboard-button public-dashboard-button--primary">Top Up</a>
                            @endif
                        </div>
                    </article>
                </section>

                <section class="public-dashboard-stats" id="legacy-dashboard-stats" data-period-stats='@json($legacyPeriodStats)' data-period-default="{{ $legacyActivePeriodKey ?: '1d' }}">
                    <h2>Ringkasan Transaksi</h2>
                    <p class="public-dashboard-stats__period">
                        <span id="legacy-dashboard-period-label">Periode aktif: {{ $legacyActivePeriod['label'] ?? 'Hari ini' }}</span>
                    </p>

                    @if(count($legacyPeriodStats) > 1)
                        <div class="public-dashboard-stats__switch" role="tablist" aria-label="Pilih periode ringkasan transaksi">
                            @foreach($legacyPeriodButtonLabels as $periodKey => $periodLabel)
                                @if(array_key_exists($periodKey, $legacyPeriodStats))
                                    <button
                                        type="button"
                                        role="tab"
                                        data-period-key="{{ $periodKey }}"
                                        aria-selected="{{ ($legacyActivePeriodKey === $periodKey) ? 'true' : 'false' }}"
                                        class="public-dashboard-stats__switch-btn {{ ($legacyActivePeriodKey === $periodKey) ? 'is-active' : '' }}"
                                    >
                                        {{ $periodLabel }}
                                    </button>
                                @endif
                            @endforeach
                        </div>
                    @endif

                    <div class="public-dashboard-stats__row public-dashboard-stats__row--main">
                        <article class="public-dashboard-stat public-dashboard-stat--neutral">
                            <strong id="legacy-stat-total-transactions">{{ number_format((int) ($legacyActivePeriod['totalTransactions'] ?? 0), 0, ',', '.') }}</strong>
                            <span>Total Transaksi</span>
                        </article>
                        <article class="public-dashboard-stat public-dashboard-stat--neutral">
                            <strong id="legacy-stat-total-sales">{{ number_format((int) ($legacyActivePeriod['totalSales'] ?? 0), 0, ',', '.') }}</strong>
                            <span>Total Penjualan</span>
                        </article>
                    </div>

                    <div class="public-dashboard-stats__row public-dashboard-stats__row--status">
                        <article class="public-dashboard-stat public-dashboard-stat--warning">
                            <strong id="legacy-stat-waiting">{{ number_format((int) ($legacyActivePeriod['waiting'] ?? 0), 0, ',', '.') }}</strong>
                            <span>Menunggu</span>
                        </article>
                        <article class="public-dashboard-stat public-dashboard-stat--info">
                            <strong id="legacy-stat-processing">{{ number_format((int) ($legacyActivePeriod['processing'] ?? 0), 0, ',', '.') }}</strong>
                            <span>Dalam Proses</span>
                        </article>
                        <article class="public-dashboard-stat public-dashboard-stat--success">
                            <strong id="legacy-stat-success">{{ number_format((int) ($legacyActivePeriod['success'] ?? 0), 0, ',', '.') }}</strong>
                            <span>Sukses</span>
                        </article>
                        <article class="public-dashboard-stat public-dashboard-stat--danger">
                            <strong id="legacy-stat-failed">{{ number_format((int) ($legacyActivePeriod['failed'] ?? 0), 0, ',', '.') }}</strong>
                            <span>Gagal</span>
                        </article>
                    </div>
                </section>

                <section class="public-dashboard-table">
                    <div class="flex items-center justify-between">
                        <h2>Riwayat Transaksi Terbaru</h2>
                        <a href="{{ route('riwayat') }}" class="public-dashboard-table__invoice-link">Lihat Semua &rarr;</a>
                    </div>

                    <div class="public-dashboard-table__shell">
                        @if(count($data) > 0)
                            <div class="overflow-x-auto">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Nomor Invoice</th>
                                            <th>ID Trx</th>
                                            <th>Item</th>
                                            <th>User Input</th>
                                            <th>Harga</th>
                                            <th>Tanggal</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($data as $pesanan)
                                            @php
                                                $statusRaw = strtolower(trim((string) ($pesanan->status ?? '')));
                                                $statusTone = 'failed';
                                                $statusLabel = Str::title((string) ($pesanan->status ?? 'Gagal'));

                                                if (in_array($statusRaw, ['sukses', 'success'], true)) {
                                                    $statusTone = 'success';
                                                    $statusLabel = 'Success';
                                                } elseif (in_array($statusRaw, ['pending', 'menunggu'], true)) {
                                                    $statusTone = 'pending';
                                                    $statusLabel = 'Pending';
                                                } elseif (in_array($statusRaw, ['proses', 'process', 'processing', 'diproses'], true)) {
                                                    $statusTone = 'processing';
                                                    $statusLabel = 'Process';
                                                }

                                                $userInput = trim(implode(' - ', array_filter([
                                                    (string) ($pesanan->user_id ?? ''),
                                                    (string) ($pesanan->zone ?? ''),
                                                ], fn ($item) => trim($item) !== '')));

                                                $createdAt = $pesanan->created_at instanceof \Carbon\CarbonInterface
                                                    ? $pesanan->created_at->format('Y-m-d H:i:s')
                                                    : (string) ($pesanan->created_at ?? '-');
                                            @endphp
                                            <tr>
                                                <td>
                                                    <a href="{{ url('/id/invoices/' . $pesanan->order_id) }}" class="public-dashboard-table__invoice-link">
                                                        {{ $pesanan->order_id }}
                                                    </a>
                                                </td>
                                                <td>n/a</td>
                                                <td>{{ $pesanan->layanan ?: '-' }}</td>
                                                <td>{{ $userInput !== '' ? $userInput : '-' }}</td>
                                                <td>Rp {{ number_format((int) ($pesanan->harga ?? 0), 0, ',', '.') }}</td>
                                                <td>{{ $createdAt }}</td>
                                                <td>
                                                    <span class="public-dashboard-table__badge public-dashboard-table__badge--{{ $statusTone }}">
                                                        {{ $statusLabel }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="public-dashboard-table__empty">
                                Belum ada transaksi.
                            </div>
                        @endif
                    </div>
                </section>
            </main>
        </div>
    </div>
</section>

@include('../footer')

@push('custom_script')
<script>
    (function () {
        const root = document.getElementById('legacy-dashboard-stats');
        if (!root) return;

        let periodStats = {};
        try {
            periodStats = JSON.parse(root.dataset.periodStats || '{}');
        } catch (error) {
            periodStats = {};
        }

        const buttons = Array.from(root.querySelectorAll('[data-period-key]'));
        const periodLabel = document.getElementById('legacy-dashboard-period-label');

        const formatNumber = (value) => new Intl.NumberFormat('id-ID').format(Number(value || 0));

        const setStatValue = (elementId, value) => {
            const element = document.getElementById(elementId);
            if (element) {
                element.textContent = formatNumber(value);
            }
        };

        const activatePeriod = (periodKey) => {
            const payload = periodStats?.[periodKey];
            if (!payload) return;

            if (periodLabel) {
                periodLabel.textContent = `Periode aktif: ${payload.label || '-'}`;
            }

            setStatValue('legacy-stat-total-transactions', payload.totalTransactions);
            setStatValue('legacy-stat-total-sales', payload.totalSales);
            setStatValue('legacy-stat-waiting', payload.waiting);
            setStatValue('legacy-stat-processing', payload.processing);
            setStatValue('legacy-stat-success', payload.success);
            setStatValue('legacy-stat-failed', payload.failed);

            buttons.forEach((button) => {
                const isActive = button.dataset.periodKey === periodKey;
                button.classList.toggle('is-active', isActive);
                button.setAttribute('aria-selected', isActive ? 'true' : 'false');
            });
        };

        const defaultKey = root.dataset.periodDefault || buttons[0]?.dataset.periodKey || null;
        if (defaultKey) {
            activatePeriod(defaultKey);
        }

        buttons.forEach((button) => {
            button.addEventListener('click', () => {
                activatePeriod(button.dataset.periodKey);
            });
        });
    })();
</script>
@endpush

@endsection
