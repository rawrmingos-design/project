@extends('template.template')

@section('custom_style')

@endsection

@section('content')

@include('../navbar')

 <div class="container grid grid-cols-8 gap-8 pt-8 sm:pt-16">
        <div class="col-span-1 hidden sm:block md:col-span-2">
            @include('components.sidebar-dashboard')
        </div>
        <div class="col-span-8 sm:col-span-7 sm:col-start-2 md:col-span-7 md:col-start-3">
            <div class="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-3">

                    
                    <!-- Profile Card -->
                    <div class="rounded-xl border border-gray-700 bg-gray-800 p-6 relative overflow-hidden group">
                        <div class="absolute top-0 right-0 p-4 opacity-10 transition-opacity group-hover:opacity-20">
                            <i class="fa fa-user-circle text-9xl text-white"></i>
                        </div>
                        <div class="relative z-10 flex flex-col items-center text-center">
                            <div class="relative mb-4">
                                <img src="https://ui-avatars.com/api/?color=FFFFFF&background=50a7ff&name={{Str::title(Auth()->user()->name)}}" alt="{{Str::title(Auth()->user()->username)}}" class="h-24 w-24 rounded-full border-4 border-gray-700 shadow-lg object-cover">
                                <span class="absolute bottom-1 right-1 h-5 w-5 rounded-full bg-green-500 border-2 border-gray-800" title="Online"></span>
                            </div>
                            <h2 class="text-xl font-bold text-white">{{Str::title(Auth()->user()->name)}}</h2>
                            <p class="text-gray-400 text-sm mb-4">{{Str::title(Auth()->user()->username)}}</p>
                            <span class="inline-flex items-center rounded-full bg-primary-500/10 px-3 py-1 text-xs font-medium text-primary-500 ring-1 ring-inset ring-primary-500/20 mb-6">
                                {{Str::title(Auth()->user()->role)}}
                            </span>
                            
                            <a href="{{ route('editProfile') }}" class="w-full rounded-lg bg-gray-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-600 transition-all flex items-center justify-center gap-2 mb-4">
                                <i class="fa fa-pencil"></i> Edit Profil
                            </a>

                            <div class="w-full rounded-lg bg-gray-900/50 p-3 border border-gray-700/50 text-center">
                                <p class="text-xs text-gray-500 mb-1">Saldo Anda</p>
                                <p class="text-xl font-bold text-primary-500 mb-3">Rp {{ number_format(Auth::user()->balance, 0, ',', '.') }}</p>
                                @if(Auth::user()->isAffiliateActive())
                                    {{-- Active affiliates cannot deposit, show withdrawal instead --}}
                                    <a href="{{ route('withdrawal') }}" class="w-full rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 transition-all flex items-center justify-center gap-2">
                                        <i class="fa fa-arrow-down"></i> Tarik Komisi
                                    </a>
                                @else
                                    <a href="{{ route('deposit') }}" class="w-full rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 transition-all flex items-center justify-center gap-2">
                                        <i class="fa fa-plus-circle"></i> Top Up Saldo
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Level & Affiliate Card -->
                    <div class="lg:col-span-2 rounded-xl border border-gray-700 bg-gray-800 p-6 flex flex-col justify-between relative overflow-hidden">
                        
                        <!-- Level Section -->
                        <div class="mb-6 relative z-10">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h3 class="text-lg font-bold text-white">Status Akun</h3>
                                    <p class="text-xs text-gray-400 mt-0.5">Tingkatan member berdasarkan transaksi</p>
                                </div>
                                <div class="text-right">
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-primary-500/10 px-3 py-1 text-sm font-semibold text-primary-500 ring-1 ring-inset ring-primary-500/20">
                                        <i class="fa fa-trophy"></i> {{ $tier_current }}
                                    </span>
                                </div>
                            </div>

                            <div class="bg-gray-900/50 rounded-xl p-4 border border-gray-700/50 shadow-inner">
                                <div class="flex items-center justify-between text-xs mb-2">
                                    <span class="font-medium text-gray-300">Progress Level</span>
                                    <span class="font-bold text-primary-500">{{ $tier_count }} / {{ $tier_target }} Trx</span>
                                </div>
                                
                                <div class="relative w-full h-2.5 bg-gray-800 rounded-full overflow-hidden border border-gray-700/50 border-t-black/20">
                                    <div class="absolute top-0 left-0 h-full bg-gradient-to-r from-primary-600 to-primary-400 rounded-full transition-all duration-700 ease-out" style="width: {{ $tier_progress }}%">
                                    </div>
                                </div>
                                
                                <div class="flex items-center justify-between text-[11px] mt-2 text-gray-500">
                                    <span>Sekarang: <strong>{{ $tier_current }}</strong></span>
                                    <span>Next Level: <strong class="text-gray-300">{{ $tier_next }}</strong></span>
                                </div>
                            </div>
                        </div>

                        <!-- Affiliate Section (Conditional based on status) -->
                        <div class="relative z-10 pt-6 border-t border-gray-700">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h3 class="text-lg font-bold text-white">Afiliasi</h3>
                                    <p class="text-xs text-gray-400">Program komisi untuk member aktif</p>
                                </div>
                                @if(Auth::user()->isAffiliateActive())
                                <a href="{{ route('affiliate') }}" class="text-xs text-primary-500 hover:text-primary-400 flex items-center gap-1 transition-colors">
                                    Lihat Detail <i class="fa fa-arrow-right"></i>
                                </a>
                                @endif
                            </div>

                            @if(Auth::user()->isAffiliateActive())
                                {{-- ACTIVE: Show referral code & commission summary --}}
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div class="rounded-lg bg-gray-900/50 p-3 border border-gray-700/50">
                                        <p class="text-xs text-gray-500 mb-1">Kode Referral</p>
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm font-mono font-medium text-white truncate">{{ $referral_code }}</span>
                                            <button onclick="navigator.clipboard.writeText('{{ $referral_code }}')" class="text-gray-400 hover:text-white transition-colors" title="Copy">
                                                <i class="fa fa-copy"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="rounded-lg bg-primary-500/10 p-3 border border-primary-500/20">
                                        <p class="text-xs text-primary-200 mb-1">Total Komisi</p>
                                        <p class="text-lg font-bold text-primary-500">Rp {{ number_format($total_commission, 0, ',', '.') }}</p>
                                    </div>
                                </div>

                            @elseif(Auth::user()->isAffiliatePending())
                                {{-- PENDING: Show waiting message --}}
                                <div class="rounded-lg bg-yellow-500/10 border border-yellow-500/20 p-4 flex items-center gap-3">
                                    <i class="fa fa-hourglass-half text-yellow-400 text-xl flex-shrink-0"></i>
                                    <div>
                                        <p class="text-sm font-medium text-yellow-300">Permintaan Sedang Diproses</p>
                                        <p class="text-xs text-yellow-400/70 mt-0.5">Admin sedang meninjau permintaan Anda.</p>
                                    </div>
                                </div>

                            @elseif(Auth::user()->affiliate_status === 'rejected')
                                {{-- REJECTED: Show rejection notice --}}
                                <div class="rounded-lg bg-red-500/10 border border-red-500/20 p-4 flex items-center gap-3">
                                    <i class="fa fa-times-circle text-red-400 text-xl flex-shrink-0"></i>
                                    <div>
                                        <p class="text-sm font-medium text-red-300">Permintaan Ditolak</p>
                                        <p class="text-xs text-red-400/70 mt-0.5">Hubungi admin untuk informasi lebih lanjut.</p>
                                    </div>
                                </div>

                            @else
                                {{-- INACTIVE: Show join CTA with Modal --}}
                                <div x-data="{ openAffiliateModal: false }" class="rounded-lg bg-gray-900/50 border border-gray-700/50 p-4 text-center">
                                    <p class="text-xs text-gray-400 mb-3">Ajak teman &amp; dapatkan komisi dari setiap transaksi mereka!</p>
                                    
                                    <!-- Trigger Button -->
                                    <button @click="openAffiliateModal = true" type="button" class="inline-flex items-center gap-2 rounded-md bg-primary-600/80 px-4 py-2 text-xs font-semibold text-white hover:bg-primary-500 transition-all">
                                        <i class="fa fa-handshake-o"></i> Bergabung Sekarang
                                    </button>

                                    <!-- Modal Overlay (Menggunakan x-teleport agar menutupi dan mem-blur navbar) -->
                                    <template x-teleport="body">
                                        <div x-show="openAffiliateModal" style="display: none; z-index: 9999;" class="fixed inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                                            <div style="padding-top: 150px;" class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                                                
                                                <!-- Background overlay -->
                                                <div x-show="openAffiliateModal" 
                                                     x-transition:enter="ease-out duration-300" 
                                                     x-transition:enter-start="opacity-0" 
                                                     x-transition:enter-end="opacity-100" 
                                                     x-transition:leave="ease-in duration-200" 
                                                     x-transition:leave-start="opacity-100" 
                                                     x-transition:leave-end="opacity-0" 
                                                     style="backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);"
                                                     class="fixed inset-0 bg-gray-900/90 transition-opacity" aria-hidden="true"></div>

                                                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                                                <!-- Modal Panel -->
                                            <div x-show="openAffiliateModal" 
                                                 @click.away="openAffiliateModal = false"
                                                 x-transition:enter="ease-out duration-300" 
                                                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                                                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                                                 x-transition:leave="ease-in duration-200" 
                                                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
                                                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                                                 class="inline-block align-bottom bg-gray-800 rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full border border-gray-700">
                                                
                                                <div class="bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                                    <div class="sm:flex sm:items-start">
                                                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-yellow-500/10 sm:mx-0 sm:h-10 sm:w-10 ring-1 ring-yellow-500/20">
                                                            <i class="fa fa-exclamation-triangle text-yellow-500"></i>
                                                        </div>
                                                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                                            <h3 class="text-lg leading-6 font-medium text-white" id="modal-title">
                                                                Peringatan Bergabung Afiliasi
                                                            </h3>
                                                            <div class="mt-4 text-sm text-gray-400 text-left space-y-3">
                                                                <p>Sebelum bergabung, pastikan Anda memahami konsekuensi berikut:</p>
                                                                <ul class="list-disc pl-5 space-y-2 text-gray-300">
                                                                    <li><strong class="text-white">Tidak Bisa Top Up:</strong> Tombol Top Up Saldo akan dinonaktifkan. Anda tidak bisa lagi mengisi saldo secara manual menggunakan transfer/payment gateway.</li>
                                                                    <li><strong class="text-white">Fungsi Saldo Berubah:</strong> Saldo akun Anda murni hanya akan bertambah dari hasil <span class="text-primary-400 font-semibold">komisi referral</span> transaksi bawahan Anda.</li>
                                                                    <li><strong class="text-white">Penggunaan Saldo:</strong> Saldo komisi tersebut tetap bisa Anda gunakan untuk <span class="text-green-400 font-semibold">transaksi/beli produk</span> (seperti Diamond ML, dll) di website, atau ditarik (Withdrawal) ke bank/e-wallet.</li>
                                                                </ul>
                                                                <p class="mt-4 text-yellow-400 text-xs italic">Tindakan ini tidak dapat dibatalkan secara otomatis (Anda harus menghubungi CS).</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="bg-gray-900/50 px-4 py-4 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-700">
                                                    <form action="{{ route('affiliate') }}" method="GET" class="w-full sm:w-auto sm:ml-3">
                                                        <input type="hidden" name="action" value="request">
                                                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-500 focus:outline-none sm:text-sm transition-colors">
                                                            Ya, Saya Paham & Setuju
                                                        </button>
                                                    </form>
                                                    <button @click="openAffiliateModal = false" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-600 shadow-sm px-4 py-2 bg-gray-800 text-base font-medium text-gray-300 hover:bg-gray-700 hover:text-white focus:outline-none sm:mt-0 sm:w-auto sm:text-sm transition-colors">
                                                        Batal
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            @endif
                        </div>
                    </div>

                </div>

                <!-- Stats Grid -->
                <div class="mt-8">
                    <h2 class="text-lg font-bold text-white mb-4">Ringkasan Hari Ini</h2>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        
                        <!-- Total Transaksi -->
                        <div class="rounded-xl border border-gray-700 bg-gray-800 shadow-sm hover:border-gray-600 transition-all group flex flex-col justify-between" style="padding: 20px;">
                            <div class="flex flex-col">
                                <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Total Transaksi</p>
                                <p class="mt-2 text-2xl font-bold text-white group-hover:text-primary-400 transition-colors">
                                    {{ $banyak_pembelian }}
                                </p>
                            </div>
                            <div class="flex items-center justify-end">
                                <div class="rounded-lg bg-gray-700 p-2 text-gray-400 group-hover:text-white transition-colors">
                                    <i class="fa fa-shopping-cart"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Total Pembelian -->
                        <div class="rounded-xl border border-gray-700 bg-gray-800 shadow-sm hover:border-gray-600 transition-all group flex flex-col justify-between" style="padding: 20px;">
                            <div class="flex flex-col">
                                <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Total Pembelian</p>
                                <p class="mt-2 text-2xl font-bold text-white group-hover:text-primary-400 transition-colors">
                                    Rp {{ number_format($total_pembelian, 0, ',', '.') }}
                                </p>
                            </div>
                            <div class="flex items-center justify-end">
                                <div class="rounded-lg bg-green-500/10 p-2 text-green-500 group-hover:bg-green-500/20 transition-colors">
                                    <i class="fa fa-wallet"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Menunggu -->
                        <div class="rounded-xl border border-gray-700 bg-gray-800 shadow-sm hover:border-gray-600 transition-all group flex flex-col justify-between" style="padding: 20px;">
                            <div class="flex flex-col">
                                <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Menunggu</p>
                                <p class="mt-2 text-2xl font-bold text-white group-hover:text-yellow-400 transition-colors">
                                    {{ $banyak_pembelian_pending }}
                                </p>
                            </div>
                            <div class="flex items-center justify-end">
                                <div class="rounded-lg bg-yellow-500/10 p-2 text-yellow-500 group-hover:bg-yellow-500/20 transition-colors">
                                    <i class="fa fa-clock-o"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Sukses -->
                        <div class="rounded-xl border border-gray-700 bg-gray-800 shadow-sm hover:border-gray-600 transition-all group flex flex-col justify-between" style="padding: 20px;">
                            <div class="flex flex-col">
                                <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Sukses</p>
                                <p class="mt-2 text-2xl font-bold text-white group-hover:text-emerald-400 transition-colors">
                                    {{ $banyak_pembelian_success }}
                                </p>
                            </div>
                            <div class="flex items-center justify-end">
                                <div class="rounded-lg bg-emerald-500/10 p-2 text-emerald-500 group-hover:bg-emerald-500/20 transition-colors">
                                    <i class="fa fa-check-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


            <div class="mt-8">
                <div class="rounded-xl border border-gray-700 bg-gray-800 p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-lg font-bold text-white">Riwayat Transaksi Terbaru</h2>
                        <a href="{{ route('riwayat') }}" class="text-sm text-primary-500 hover:text-primary-400 font-medium">Lihat Semua &rarr;</a>
                    </div>
                
                    @if(count($data) > 0)
                        <div class="overflow-hidden rounded-lg border border-gray-700">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-700 text-left text-sm">
                                    <thead class="bg-gray-900/50 text-gray-400">
                                        <tr>
                                            <th scope="col" class="px-4 py-3 font-medium">Nomor Invoice</th>
                                            <th scope="col" class="px-4 py-3 font-medium">Item</th>
                                            <th scope="col" class="px-4 py-3 font-medium">Keterangan</th>
                                            <th scope="col" class="px-4 py-3 font-medium">Harga</th>
                                            <th scope="col" class="px-4 py-3 font-medium">Tanggal</th>
                                            <th scope="col" class="px-4 py-3 font-medium">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-700 bg-gray-800">
                                        @foreach($data as $pesanan)
                                            @if($pesanan->tipe_transaksi !== 'joki')
                                                @php
                                                    $zone = $pesanan->zone != null ? "-".$pesanan->zone : "";
                                                    $status = $pesanan->status;
                                                    $label_pesanan = '';

                                                    if ($status == 'Success' || $status == 'Sukses') {
                                                        $label_pesanan = 'bg-green-500/10 text-green-500 ring-green-500/20';
                                                        $status = 'Success';
                                                    } elseif ($status == 'Pending' || $status == 'pending') {
                                                        $label_pesanan = 'bg-yellow-500/10 text-yellow-500 ring-yellow-500/20';
                                                    } elseif ($status == 'Proses' || $status == 'Process') {
                                                        $label_pesanan = 'bg-blue-500/10 text-blue-500 ring-blue-500/20';
                                                        $status = 'Process';
                                                    } else {
                                                        $label_pesanan = 'bg-red-500/10 text-red-500 ring-red-500/20';
                                                    }
                                                @endphp 
                                                <tr class="hover:bg-gray-700/50 transition-colors">
                                                    <td class="whitespace-nowrap px-4 py-4 font-medium text-white">
                                                        <a href="{{ ENV('APP_URL') }}/id/invoices/{{ $pesanan->order_id }}" class="hover:text-primary-400 hover:underline text-primary-500">
                                                            #{{ $pesanan->order_id }}
                                                        </a>
                                                    </td>
                                                    <td class="whitespace-nowrap px-4 py-4 text-gray-300">
                                                        <div class="flex items-center gap-2">
                                                            <div class="h-8 w-8 rounded bg-gray-700 flex items-center justify-center">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                                                </svg>
                                                            </div>
                                                            {{ $pesanan->layanan }}
                                                        </div>
                                                    </td>
                                                    <td class="whitespace-nowrap px-4 py-4 text-gray-300">
                                                        {{ $pesanan->user_id }} {{ $zone }}
                                                    </td>
                                                    <td class="whitespace-nowrap px-4 py-4 font-medium text-white">
                                                        Rp {{ number_format($pesanan->harga, 0, ',', '.') }}
                                                    </td>
                                                    <td class="whitespace-nowrap px-4 py-4 text-xs text-gray-400">
                                                        {{ $pesanan->created_at }}
                                                    </td>
                                                    <td class="whitespace-nowrap px-4 py-4">
                                                        <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $label_pesanan }}">
                                                            {{ $status }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center py-12 text-center">
                            <div class="rounded-full bg-gray-700 p-4">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                </svg>
                            </div>
                            <h3 class="mt-4 text-sm font-semibold text-white">Belum ada transaksi</h3>
                            <p class="mt-1 text-sm text-gray-400">Transaksi yang Anda lakukan akan muncul di sini.</p>
                            <a href="/" class="mt-6 rounded-md bg-primary-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600">
                                Mulai Belanja
                            </a>
                        </div>
                    @endif
                </div>
            </div>
            </div>
        </div>
    </div>
    </div>
    




    </div>

@include('../footer')

@push('custom_script')

@endpush




@endsection