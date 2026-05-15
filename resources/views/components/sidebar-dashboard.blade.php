@php
    $currentPath = '/' . ltrim((string) request()->path(), '/');

    $isDashboardActive = request()->routeIs('dashboard') || str_starts_with($currentPath, '/id/settings');
    $isTransactionsActive = request()->routeIs('riwayat');
    $isMutationActive = request()->routeIs('reload');
    $isAffiliateActive = request()->routeIs('affiliate') || request()->routeIs('withdrawal');
@endphp

<aside class="public-dashboard-side lg:sticky lg:top-24">
    <nav class="public-dashboard-side__nav flex flex-col gap-2" aria-label="Menu dashboard">
        <a href="{{ route('dashboard') }}" class="public-dashboard-side__link flex items-center gap-3 rounded-xl border border-white/10 bg-zinc-800/40 px-3 py-2.5 text-sm font-semibold text-zinc-100 transition hover:border-orange-400/40 hover:bg-orange-500/10 {{ $isDashboardActive ? 'is-active border-orange-400/50 bg-gradient-to-r from-orange-500/60 to-orange-500/10 text-white' : '' }}">
            <span class="public-dashboard-side__icon inline-flex h-4 w-4 shrink-0 items-center justify-center" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" width="16" height="16" class="h-4 w-4">
                    <rect x="3" y="3" width="7" height="7" rx="1.4" stroke="currentColor" stroke-width="1.5" />
                    <rect x="14" y="3" width="7" height="7" rx="1.4" stroke="currentColor" stroke-width="1.5" />
                    <rect x="3" y="14" width="7" height="7" rx="1.4" stroke="currentColor" stroke-width="1.5" />
                    <rect x="14" y="14" width="7" height="7" rx="1.4" stroke="currentColor" stroke-width="1.5" />
                </svg>
            </span>
            <span class="public-dashboard-side__label truncate">Dashboard</span>
        </a>

        <a href="{{ route('riwayat') }}" class="public-dashboard-side__link flex items-center gap-3 rounded-xl border border-white/10 bg-zinc-800/40 px-3 py-2.5 text-sm font-semibold text-zinc-100 transition hover:border-orange-400/40 hover:bg-orange-500/10 {{ $isTransactionsActive ? 'is-active border-orange-400/50 bg-gradient-to-r from-orange-500/60 to-orange-500/10 text-white' : '' }}">
            <span class="public-dashboard-side__icon inline-flex h-4 w-4 shrink-0 items-center justify-center" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" width="16" height="16" class="h-4 w-4">
                    <path d="M12 7v5l3 2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.5" />
                </svg>
            </span>
            <span class="public-dashboard-side__label truncate">Riwayat Transaksi</span>
        </a>

        <a href="{{ route('reload') }}" class="public-dashboard-side__link flex items-center gap-3 rounded-xl border border-white/10 bg-zinc-800/40 px-3 py-2.5 text-sm font-semibold text-zinc-100 transition hover:border-orange-400/40 hover:bg-orange-500/10 {{ $isMutationActive ? 'is-active border-orange-400/50 bg-gradient-to-r from-orange-500/60 to-orange-500/10 text-white' : '' }}">
            <span class="public-dashboard-side__icon inline-flex h-4 w-4 shrink-0 items-center justify-center" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" width="16" height="16" class="h-4 w-4">
                    <path d="m17 8 3 3-3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M20 11H9a4 4 0 0 0-4 4v1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    <path d="m7 16-3-3 3-3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M4 13h11a4 4 0 0 0 4-4V8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </span>
            <span class="public-dashboard-side__label truncate">Riwayat Deposit</span>
        </a>

        <a href="{{ route('affiliate') }}" class="public-dashboard-side__link flex items-center gap-3 rounded-xl border border-white/10 bg-zinc-800/40 px-3 py-2.5 text-sm font-semibold text-zinc-100 transition hover:border-orange-400/40 hover:bg-orange-500/10 {{ $isAffiliateActive ? 'is-active border-orange-400/50 bg-gradient-to-r from-orange-500/60 to-orange-500/10 text-white' : '' }}">
            <span class="public-dashboard-side__icon inline-flex h-4 w-4 shrink-0 items-center justify-center" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" width="16" height="16" class="h-4 w-4">
                    <path d="M12 14a5 5 0 1 0-5-5 5 5 0 0 0 5 5Z" stroke="currentColor" stroke-width="1.5" />
                    <path d="M4 21a8 8 0 0 1 16 0" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                </svg>
            </span>
            <span class="public-dashboard-side__label truncate">Afiliasi</span>
        </a>

        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="public-dashboard-side__link public-dashboard-side__link--logout flex w-full items-center gap-3 rounded-xl border border-white/10 bg-zinc-800/40 px-3 py-2.5 text-sm font-semibold text-rose-300 transition hover:border-rose-400/45 hover:bg-rose-500/10 hover:text-rose-200">
                <span class="public-dashboard-side__icon inline-flex h-4 w-4 shrink-0 items-center justify-center" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" width="16" height="16" class="h-4 w-4">
                        <path d="M14 8V5a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-3" stroke="currentColor" stroke-width="1.5" />
                        <path d="M20 12H9m8-4 4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </span>
                <span class="public-dashboard-side__label truncate">Keluar</span>
            </button>
        </form>
    </nav>
</aside>
