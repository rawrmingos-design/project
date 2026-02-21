@extends('template.template')

@section('custom_style')

@endsection

@section('content')
@include('../navbar')
<div class="container grid grid-cols-8 gap-8 pt-8 sm:pt-16">
    <div class="col-span-1 hidden sm:block md:col-span-2">
        <aside class="sticky top-20 print:hidden">
            <nav class="h-full content-start lg:grid lg:content-between">
                <div class="space-y-4">
                    <a class="group flex items-center gap-3 rounded-md bg-gradient-to-r to-transparent px-3 py-2 text-sm font-medium text-white hover:from-gray-700"
                        style="outline:none" href="/id/dashboard">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" aria-hidden="true" class="h-5 w-5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25">
                            </path>
                        </svg>
                        <span class="hidden truncate md:block">Dashboard</span>
                    </a>
                    <a class="group flex items-center gap-3 rounded-md bg-gradient-to-r to-transparent px-3 py-2 text-sm font-medium text-white hover:from-gray-700"
                        style="outline:none" href="{{ route('riwayat') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" aria-hidden="true" class="h-5 w-5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="hidden truncate md:block">Transaksi</span>
                    </a>
                    <a class="group flex items-center gap-3 rounded-md bg-gradient-to-r to-transparent px-3 py-2 text-sm font-medium text-white hover:from-gray-700"
                        style="outline:none" href="{{ route('affiliate') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" aria-hidden="true" class="h-5 w-5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z">
                            </path>
                        </svg>
                        <span class="hidden truncate md:block">Afiliasi</span>
                    </a>
            
                    
                </div>
                <div class="w-full pt-4 ">
                   <form action="{{ route('logout') }}" method="POST" id="logout">
                        @csrf                        
                        <button type="submit" class="flex w-full items-center gap-3 rounded-md bg-gradient-to-r px-3 py-2 text-sm font-medium text-rose-500 hover:from-gray-700">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-5 w-5">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"
                                ></path>
                            </svg>
                            <span class="hidden md:block">Keluar</span>
                        </button>
                    </form>
                </div>
            </nav>
        </aside>
    </div>

    <!-- Main Content (Deposit Form) -->
    <div class="col-span-8 sm:col-span-7 sm:col-start-2 md:col-span-6 md:col-start-3">
        
        <!-- Success/Error Messages -->
        @if(session('success'))
        <div class="mb-6 rounded-lg bg-green-500/10 border border-green-500/20 p-4 text-green-500 animate-fade-in-down">
            <div class="flex items-center gap-3">
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <p class="text-sm font-medium">{{ session('success') }}</p>
            </div>
        </div>
        @endif

         @if ($errors->any())
        <div class="mb-6 rounded-lg bg-red-500/10 border border-red-500/20 p-4 text-red-500 animate-fade-in-down">
             <div class="flex items-center gap-3">
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <ul class="text-sm font-medium list-disc list-inside">
                     @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif

        <form action="{{ route('deposit.store') }}" method="POST" id="topup-form" class="space-y-8">
            @csrf
            <input type="hidden" id="selected_method" name="metode">

            <!-- User Balance Card -->
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-gray-800 to-gray-900 border border-gray-700 p-6 shadow-xl">
                <div class="absolute top-0 right-0 -mr-16 -mt-16 h-40 w-40 rounded-full bg-primary-500/10 blur-3xl"></div>
                 <div class="flex flex-col sm:flex-row items-center justify-between gap-4 relative z-10">
                    <div>
                        <p class="text-sm font-medium text-gray-400">Saldo Tersedia</p>
                        <h3 class="mt-1 text-3xl font-bold text-white tracking-tight">Rp {{ number_format(Auth::user()->balance, 0, ',', '.') }}</h3>
                    </div>
                     <a href="{{ route('reload') }}" class="inline-flex items-center gap-2 rounded-lg bg-gray-700/50 px-4 py-2.5 text-sm font-medium text-white hover:bg-gray-700 transition-colors border border-gray-600">
                         <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Riwayat Deposit
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Column 1: Input Data -->
                <div class="lg:col-span-1 space-y-8">
                    <!-- Step 1 Card -->
                    <div class="rounded-2xl bg-gray-800 border border-gray-700 p-6 shadow-lg">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary-600 font-bold text-white">1</div>
                            <h3 class="text-lg font-bold text-white">Nominal Deposit</h3>
                        </div>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="mb-2 block text-xs font-medium text-gray-300">Jumlah Deposit (Min Rp 10.000)</label>
                                <div class="relative">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <span class="text-gray-400">Rp</span>
                                    </div>
                                    <input type="number" style="padding-left: 40px; color: #000;" name="jumlah" class="block w-full rounded-lg border-gray-600 bg-gray-50 pl-10 text-sm text-gray-900 placeholder-gray-500 focus:border-primary-500 focus:ring-primary-500" placeholder="0" required min="10000">
                                </div>
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-medium text-gray-300">Nomor WhatsApp (Aktif)</label>
                                <div class="relative">
                                     <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 text-gray-400">
                                            <path fill-rule="evenodd" d="M2 3.5A1.5 1.5 0 013.5 2h1.148a1.5 1.5 0 011.465 1.175l.716 3.223a1.5 1.5 0 01-1.052 1.767l-.933.267c-.41.117-.643.555-.48.95a11.542 11.542 0 006.254 6.254c.395.163.833-.07.95-.48l.267-.933a1.5 1.5 0 011.767-1.052l3.223.716A1.5 1.5 0 0118 15.352V16.5a1.5 1.5 0 01-1.5 1.5H15c-1.149 0-2.263-.15-3.326-.43A13.022 13.022 0 012.43 8.326 13.019 13.019 0 012 5V3.5z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <input type="number" style="padding-left: 40px; color: #000;" name="no_telfon" class="block w-full rounded-lg border-gray-600 bg-gray-50 pl-10 text-sm text-gray-900 placeholder-gray-500 focus:border-primary-500 focus:ring-primary-500" placeholder="08xxx" value="{{ Auth::user()->whatsapp ?? '' }}" required>
                                </div>
                            </div>
                        </div>
                    </div>

                     <!-- Instructions Card (Moved here for better flow) -->
                    <div class="rounded-2xl bg-gray-800 border border-gray-700 p-6 shadow-lg">
                        <div class="flex items-center gap-3 mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <h3 class="text-lg font-bold text-white">Panduan Deposit</h3>
                        </div>
                         <ul class="list-decimal list-inside space-y-2 text-sm text-gray-300">
                            <li>Isi nominal deposit yang diinginkan (Min. Rp 10.000).</li>
                            <li>Masukkan nomor WhatsApp yang aktif.</li>
                            <li>Pilih metode pembayaran yang tersedia.</li>
                            <li>Klik tombol "Top Up Sekarang".</li>
                            <li>Lakukan pembayaran sesuai instruksi.</li>
                            <li>Saldo akan masuk otomatis setelah pembayaran berhasil.</li>
                        </ul>
                    </div>
                </div>

                <!-- Column 2: Payment Methods -->
                <div class="lg:col-span-2">
                    <div class="rounded-2xl bg-gray-800 border border-gray-700 p-6 shadow-lg">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary-600 font-bold text-white">2</div>
                            <h3 class="text-lg font-bold text-white">Pilih Metode Pembayaran</h3>
                        </div>

                         <!-- Method Grid -->
                         <div class="grid grid-cols-2 sm:grid-cols-3 gap-4" x-data="{ paymentSelected: '' }">
                             @foreach($pay_method as $p)
                            @if($p->tipe == 'qris')
                            <div 
                                class="payment-method relative group cursor-pointer overflow-hidden rounded-xl border transition-all duration-200"
                                x-bind:class="{ 'border-primary-500 bg-primary-500/10 ring-1 ring-primary-500': paymentSelected === '{{$p->code}}', 'border-gray-600 bg-gray-700/50 hover:border-gray-500': paymentSelected !== '{{$p->code}}' }"
                                @click="paymentSelected = '{{$p->code}}'; document.getElementById('selected_method').value = '{{$p->code}}'"
                                id="{{$p->code}}"
                                data-fee-percent="{{ $p->fee_percent ?? 0 }}"
                                data-fix-fee="{{ $p->fix_fee ?? 0 }}"
                            >
                                <div class="p-4 flex flex-col h-full justify-between gap-4">
                                    <!-- Header: Name -->
                                    <div class="flex items-start justify-between">
                                        <span class="text-xs font-bold text-white uppercase tracking-wider">{{ $p->name }}</span>
                                        @if($p->tipe == 'qris')
                                        <span class="inline-flex rounded-full bg-blue-500/20 px-1.5 py-0.5 text-[10px] font-bold text-blue-400">QRIS</span>
                                        @endif
                                    </div>

                                    <!-- Image -->
                                     <div class="h-8 w-full flex items-center justify-start">
                                        <img src="{{ asset($p->images) }}" alt="{{ $p->name }}" class="h-full max-w-[80%] object-contain object-left" loading="lazy">
                                    </div>

                                     <!-- Price Calculation -->
                                     <div class="pt-2 border-t border-white/5">
                                         <span class="block text-[10px] text-gray-400">Total Pembayaran</span>
                                         <div class="text-sm font-bold text-primary-400 showHarga" id="{{$p->code}}"></div>
                                     </div>
                                </div>

                                <!-- Selected Indicator -->
                                <div x-show="paymentSelected === '{{$p->code}}'" class="absolute -right-1 -top-1">
                                    <span class="flex h-5 w-5 items-center justify-center rounded-bl-lg rounded-tr-lg bg-primary-500 text-white">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3 h-3">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </span>
                                </div>
                            </div>
                            @endif
                            @endforeach
                         </div>
                    </div>

                    <button type="submit" class="mt-8 w-full rounded-xl bg-gradient-to-r from-primary-600 to-primary-500 px-8 py-4 text-base font-bold text-white shadow-lg shadow-primary-500/25 transition-all hover:bg-gradient-to-r hover:from-primary-500 hover:to-primary-400 hover:shadow-primary-500/40 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 focus:ring-offset-gray-900">
                         <span class="flex items-center justify-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5 animate-pulse">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 01-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 006.16-12.12A14.98 14.98 0 009.631 8.41m5.96 5.96a14.926 14.926 0 01-5.841 2.58m-.119-8.54a6 6 0 00-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 00-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 01-2.448-2.448 14.9 14.9 0 01.06-.312m-2.24 2.39a4.493 4.493 0 00-1.757 4.306 4.493 4.493 0 004.306-1.758M16.5 9a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
                            </svg>
                            Top Up Sekarang
                        </span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@include('../footer')

@push('custom_script')
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const jumlahInput = document.querySelector('input[name="jumlah"]');

        if(jumlahInput){
            jumlahInput.addEventListener('input', function(event) {
                const nilaiJumlah = event.target.value;
                const flexEndElements = document.querySelectorAll('.showHarga');

                if (nilaiJumlah === '' || nilaiJumlah < 0) {
                    flexEndElements.forEach(function(element) {
                        element.textContent = '';
                    });
                } else {
                    flexEndElements.forEach(function(element) {
                        const paymentCode = element.id;
                        const parentDiv = document.getElementById(paymentCode);
                        let total = 0;

                        if (parentDiv) {
                            const feePercent = parseFloat(parentDiv.getAttribute('data-fee-percent')) || 0;
                            const fixFee = parseFloat(parentDiv.getAttribute('data-fix-fee')) || 0;
                            
                            const fee = (parseFloat(nilaiJumlah) * (feePercent / 100)) + fixFee;
                            total = parseFloat(nilaiJumlah) + fee;
                        } else {
                            total = parseFloat(nilaiJumlah);
                        }
                        
                        element.textContent = formatRupiah(Math.ceil(total));
                    });
                }
            });
        }
    });

    function formatRupiah(angka) {
        var reverse = angka.toString().split('').reverse().join(''),
            ribuan = reverse.match(/\d{1,3}/g);
        ribuan = ribuan.join('.').split('').reverse().join('');
        return 'Rp ' + ribuan;
    }
</script>
@endpush
@endsection