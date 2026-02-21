@extends('template.template')

@section('custom_style')
@endsection

@section('content')

@include('../navbar')

<div class="container grid grid-cols-8 gap-8 pt-8 sm:pt-16">
    <div class="col-span-1 hidden sm:block md:col-span-2">
        @include('components.sidebar-dashboard')
    </div>

    <!-- Main Content -->
    <div class="col-span-8 sm:col-span-7 sm:col-start-2 md:col-span-7 md:col-start-3">
        <div class="pb-8 sm:flex sm:items-center">
            <div class="sm:flex-auto">
                <h1 class="text-base font-semibold leading-6 text-white">Program Afiliasi</h1>
                <p class="mt-2 text-sm text-murky-200">Ajak teman dan dapatkan komisi dari setiap transaksi mereka.</p>
            </div>
        </div>

        @if(Auth::user()->isAffiliateInactive())
        <!-- State 1: Inactive (Request Button) -->
        <div class="bg-gray-900/30 rounded-lg border border-gray-700 p-8 text-center">
            <div class="mx-auto h-16 w-16 rounded-full bg-primary-500/10 flex items-center justify-center mb-4 border border-primary-500/20">
                <i class="fas fa-handshake text-2xl text-primary-500"></i>
            </div>
            <h2 class="text-xl font-semibold text-white mb-2">Bergabung dengan Program Afiliasi</h2>
            <p class="text-gray-400 mb-6 max-w-lg mx-auto">
                Dapatkan penghasilan tambahan dengan mereferensikan teman Anda. 
                Nikmati komisi menarik dari setiap transaksi yang dilakukan oleh referral Anda.
            </p>
            
            <form action="{{ route('user.affiliate') }}" method="GET">
                <input type="hidden" name="action" value="request">
                <button type="submit" class="inline-flex items-center gap-2 rounded-md bg-primary-600 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 transition-all">
                    <span>Ajukan Permintaan Sekarang</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>
        </div>

        @elseif(Auth::user()->isAffiliatePending())
        <!-- State 2: Pending (Waiting Approval) -->
        <div class="bg-gray-900/30 rounded-lg border border-yellow-500/30 p-8 text-center relative overflow-hidden">
            <div class="absolute top-0 right-0 p-4 opacity-10">
                <i class="fas fa-clock text-9xl text-yellow-500"></i>
            </div>
            
            <div class="relative z-10">
                <div class="mx-auto h-16 w-16 rounded-full bg-yellow-500/10 flex items-center justify-center mb-4 border border-yellow-500/20">
                    <i class="fas fa-hourglass-half text-2xl text-yellow-500"></i>
                </div>
                <h2 class="text-xl font-semibold text-white mb-2">Permintaan Sedang Diproses</h2>
                <p class="text-gray-400 mb-6 max-w-lg mx-auto">
                    Terima kasih telah mengajukan permintaan. Admin kami sedang meninjau permohonan Anda. 
                    Silakan cek kembali secara berkala status akun Anda.
                </p>
                <span class="inline-flex items-center rounded-md bg-yellow-400/10 px-3 py-1 text-sm font-medium text-yellow-400 ring-1 ring-inset ring-yellow-400/20">
                    Status: Pending
                </span>
            </div>
        </div>

        @elseif(Auth::user()->affiliate_status === 'rejected')
         <!-- State 4: Rejected -->
         <div class="bg-gray-900/30 rounded-lg border border-red-500/30 p-8 text-center">
            <div class="mx-auto h-16 w-16 rounded-full bg-red-500/10 flex items-center justify-center mb-4 border border-red-500/20">
                <i class="fas fa-times text-2xl text-red-500"></i>
            </div>
            <h2 class="text-xl font-semibold text-white mb-2">Permintaan Ditolak</h2>
            <p class="text-gray-400 mb-6 max-w-lg mx-auto">
                Maaf, permintaan afiliasi Anda belum dapat kami setujui saat ini. 
                Silakan hubungi admin untuk informasi lebih lanjut.
            </p>
        </div>

        @else
        <!-- State 3: Active (Dashboard) -->
        <!-- Top Section: Stats & Code -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
            <!-- Referral Code -->
            <div class="rounded-lg bg-gray-900/50 p-6 border border-gray-700/50 relative group">
                <p class="text-sm text-gray-500 mb-2">Kode Referral Anda</p>
                <div class="flex items-center justify-between bg-black/30 p-3 rounded-lg border border-gray-700">
                    <span class="text-xl font-mono font-medium text-white truncate">{{ $referral_code }}</span>
                    <div class="flex gap-2">
                        <button onclick="navigator.clipboard.writeText('{{ $referral_code }}'); alert('Kode disalin!')"
                            class="p-2 text-gray-400 hover:text-white transition-colors rounded-md hover:bg-gray-700"
                            title="Copy Code">
                            <i class="fa fa-copy"></i>
                        </button>
                        <button onclick="navigator.clipboard.writeText('{{ url('/register?ref=' . $referral_code) }}'); alert('Link disalin!')"
                            class="p-2 text-primary-500 hover:text-primary-400 transition-colors rounded-md hover:bg-gray-700"
                            title="Copy Link">
                            <i class="fa fa-share-alt"></i>
                        </button>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-2">
                    Link: <span class="text-gray-400">{{ url('/register?ref=' . $referral_code) }}</span>
                </p>
            </div>

            <!-- Total Commission -->
            <div class="rounded-lg bg-primary-500/10 p-6 border border-primary-500/20 flex flex-col justify-center">
                <p class="text-sm text-primary-200 mb-1">Total Komisi Diterima</p>
                <p class="text-3xl font-bold text-primary-500">Rp {{ number_format($total_commission, 0, ',', '.') }}
                </p>
                <p class="text-xs text-primary-300/70 mt-2">Komisi dicairkan ke saldo akun secara otomatis.</p>
            </div>
        </div>

        <!-- History Table -->
        <div class="bg-gray-900/30 rounded-lg border border-gray-700 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-700">
                <h3 class="text-sm font-semibold text-white">Riwayat Komisi</h3>
            </div>
            
            @if(count($affiliate_history) > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-700">
                    <thead class="bg-gray-800">
                        <tr>
                            <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-gray-300">Waktu</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-gray-300">Dari (Downlink)</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-gray-300">Order ID</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-gray-300">Jumlah</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-gray-300">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700 bg-transparent">
                        @foreach($affiliate_history as $history)
                        <tr>
                            <td class="whitespace-nowrap px-3 py-4 text-xs text-gray-400">
                                {{ $history->created_at->format('d M Y H:i') }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-xs text-white">
                                {{ $history->downlink->username ?? 'Unknown' }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-xs text-primary-400">
                                {{ $history->order_id }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-xs font-medium text-emerald-400">
                                + Rp {{ number_format($history->amount, 0, ',', '.') }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-xs">
                                <span class="inline-flex items-center rounded-md bg-emerald-400/10 px-2 py-1 text-xs font-medium text-emerald-400 ring-1 ring-inset ring-emerald-400/20">
                                    Sukses
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="px-4 py-3 border-t border-gray-700">
                {{ $affiliate_history->links() }}
            </div>
            
            @else
            <div class="py-12 flex flex-col items-center justify-center text-center">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="h-12 w-12 text-gray-600 mb-3">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h3 class="text-sm font-medium text-white">Belum ada komisi</h3>
                <p class="text-xs text-gray-500 mt-1">Bagikan kode referral Anda untuk mulai mendapatkan komisi.</p>
            </div>
            @endif
        </div>
        @endif

    </div>
</div>

@include('../footer')

@endsection
