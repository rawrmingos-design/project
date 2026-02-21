<aside class="sticky top-20 print:hidden">
    <nav class="h-full content-start lg:grid lg:content-between">
        <div class="space-y-4">
            {{-- Dashboard --}}
            <a class="group flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium text-white {{ request()->routeIs('dashboard') ? 'bg-gradient-to-r from-primary-500 to-transparent' : 'hover:from-murky-700 hover:bg-gradient-to-r' }}"
                style="outline:none" href="{{ route('dashboard') }}">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" aria-hidden="true" class="h-5 w-5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25">
                    </path>
                </svg>
                <span class="hidden truncate md:block">Dashboard</span>
            </a>

            {{-- Riwayat Transaksi --}}
            <a class="group flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium text-white {{ request()->routeIs('riwayat') ? 'bg-gradient-to-r from-primary-500 to-transparent' : 'hover:from-murky-700 hover:bg-gradient-to-r' }}"
                style="outline:none" href="{{ route('riwayat') }}">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" aria-hidden="true" class="h-5 w-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="hidden truncate md:block">Riwayat Transaksi</span>
            </a>

            {{-- Riwayat Deposit (NEW) --}}
            <a class="group flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium text-white {{ request()->routeIs('reload') ? 'bg-gradient-to-r from-primary-500 to-transparent' : 'hover:from-murky-700 hover:bg-gradient-to-r' }}"
                style="outline:none" href="{{ route('reload') }}">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-5 w-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 9m18 0V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v3"></path>
                </svg>
                <span class="hidden truncate md:block">Riwayat Deposit</span>
            </a>

            {{-- Afiliasi (Harus Aktif/Pending) --}}
            @if(Auth::user()->affiliate_status !== 'inactive' && Auth::user()->affiliate_status !== null)
            <a class="group flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium text-white {{ request()->routeIs('affiliate') ? 'bg-gradient-to-r from-primary-500 to-transparent' : 'hover:from-murky-700 hover:bg-gradient-to-r' }}"
                style="outline:none" href="{{ route('affiliate') }}">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" aria-hidden="true" class="h-5 w-5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z">
                    </path>
                </svg>
                <span class="hidden truncate md:block">Afiliasi</span>
            </a>
            @endif

        </div>
        <div class="w-full pt-4 ">
            <form action="{{ route('logout') }}" method="POST" id="logout">
                @csrf
                <button type="submit"
                    class="flex w-full items-center gap-3 rounded-md bg-gradient-to-r px-3 py-2 text-sm font-medium text-rose-500 hover:from-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" aria-hidden="true" class="h-5 w-5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75">
                        </path>
                    </svg>
                    <span class="hidden md:block">Keluar</span>
                </button>
            </form>
        </div>
    </nav>
</aside>
