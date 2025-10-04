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
                        <a class="group flex items-center gap-3 rounded-md bg-gradient-to-r to-transparent px-3 py-2 text-sm font-medium text-white from-primary-500"
                            style="outline:none" href="/id/dashboard">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" aria-hidden="true" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25">
                                </path>
                            </svg>
                            <span class="hidden truncate md:block">Dashboard</span>
                        </a>
                        <a class="group flex items-center gap-3 rounded-md bg-gradient-to-r to-transparent px-3 py-2 text-sm font-medium text-white hover:from-murky-700"
                            style="outline:none" href="{{ route('riwayat') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" aria-hidden="true" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="hidden truncate md:block">Transaksi</span>
                        </a>
                
                        
                    </div>
                    <div class="w-full pt-4 ">
                       <form action="{{ route('logout') }}" method="POST" id="logout">
                            @csrf                        
                            <button type="submit" class="flex w-full items-center gap-3 rounded-md bg-gradient-to-r px-3 py-2 text-sm font-medium text-rose-500 hover:from-murky-700">
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
        <div class="col-span-8 sm:col-span-7 sm:col-start-2 md:col-span-7 md:col-start-3">
            <div class="grid grid-cols-1 gap-8 lg:gap-8 xl:grid-cols-2">
                <div
                    class="col-span-1 flex flex-col justify-center rounded-lg border border-murky-600 bg-murky-700 p-6 lg:col-span-2">
                    <div>
                        <h4 class="sr-only">Level</h4>
                        <p class="text-sm font-semibold text-white">Tingkat Akun Anda</p>
                        <div class="mt-6" aria-hidden="true">
                            <div class="relative">
                                
        
   @if(Auth::check())
    @if(Auth()->user()->role == 'Member')
        <div class="overflow-hidden rounded-full bg-slate-100">
            <div class="h-2 rounded-full bg-primary-500" style="width: 0%;"></div>
        </div>
        <div class="absolute top-1/2 -mt-2 -ml-2 flex h-4 w-4 items-center justify-center rounded-full bg-white shadow ring-2 ring-primary-500" style="left: 0%; margin-left: -2px;">
            <div class="h-4 w-4 flex items-center justify-center rounded-full bg-white shadow ring-2 ring-primary-500">
                <div class="h-1.5 w-1.5 rounded-full bg-primary-500 ring-1 ring-inset ring-murky-900/5"></div>
            </div>
        </div>
    @elseif(Auth()->user()->role == 'Platinum')
        <div class="overflow-hidden rounded-full bg-slate-100">
            <div class="h-2 rounded-full bg-primary-500" style="width: 50%;"></div>
        </div>
        <div class="absolute top-1/2 -mt-2 -ml-2 flex h-4 w-4 items-center justify-center rounded-full bg-white shadow ring-2 ring-primary-500" style="left: 50%; margin-left: -2px;">
            <div class="h-4 w-4 flex items-center justify-center rounded-full bg-white shadow ring-2 ring-primary-500">
                <div class="h-1.5 w-1.5 rounded-full bg-primary-500 ring-1 ring-inset ring-murky-900/5"></div>
            </div>
        </div>
    @elseif(Auth()->user()->role == 'Gold')
        <div class="overflow-hidden rounded-full bg-slate-100">
            <div class="h-2 rounded-full bg-primary-500" style="width: 100%;"></div>
        </div>
        <div class="absolute top-1/2 -mt-2 -ml-2 flex h-4 w-4 items-center justify-center rounded-full bg-white shadow ring-2 ring-primary-500" style="left: 100%; margin-left: -2px;">
            <div class="h-4 w-4 flex items-center justify-center rounded-full bg-white shadow ring-2 ring-primary-500">
                <div class="h-1.5 w-1.5 rounded-full bg-primary-500 ring-1 ring-inset ring-murky-900/5"></div>
            </div>
        </div>
    @endif
@endif

    
    
</div>
                            <div class="mt-6 grid grid-cols-3 text-sm font-medium text-white/50">
                                <div class="inline-flex flex-col gap-2 text-center text-primary-500">
                                    <div>
                                        <span
                                            class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium capitalize ring-1 ring-inset bg-blue-400/10 text-blue-400 ring-blue-400/30">Member</span>
                                    </div>
                                    <span>Rp&nbsp;100.000</span>
                                </div>
                                <div class="inline-flex flex-col gap-2 text-center text-primary-500">
                                    <div>
                                        <span
                                            class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium capitalize ring-1 ring-inset bg-energy-yellow-400/10 text-energy-yellow-400 ring-energy-yellow-400/20">Platinum</span>
                                    </div>
                                    <span>Rp&nbsp;5.000.000</span>
                                </div>
                                <div class="inline-flex flex-col gap-2 text-center text-primary-500">
                                    <div>
                                        <span
                                            class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium capitalize ring-1 ring-inset bg-primary-400/10 text-primary-400 ring-primary-400/20">Gold</span>
                                    </div>
                                    <span>Rp&nbsp;10.000.000</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="pt-6">
                        <div class="col-span-full rounded-md bg-murky-600 p-4 text-white">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" aria-hidden="true"
                                        class="h-5 w-5 text-yellow-400">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z">
                                        </path>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-white">Selamat</h3>
                                    <div class="mt-2 text-sm">
                                        <p>Akun Keanggotaan Anda Adalah {{Str::title(Auth()->user()->role)}}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="rounded-lg border border-murky-600 bg-murky-700 p-6">
                    <figure class="flex items-center justify-between">
                        <figcaption class="flex items-center justify-start space-x-3.5 text-left">
                            <img src="https://ui-avatars.com/api/?color=FFFFFF&amp;background=50a7ff&amp;name={{Str::title(Auth()->user()->name)}}"
                                class="h-14 w-14 rounded-full" alt="" />
                            <div>
                                <div class="flex items-center gap-x-2 pb-1 font-semibold text-white">
                                    <span>{{Str::title(Auth()->user()->name)}}</span>
                                    <span>({{Str::title(Auth()->user()->username)}})</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" aria-hidden="true"
                                        class="h-5 w-5 hidden">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z">
                                        </path>
                                    </svg>
                                </div>
                                                                <span
                                    class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium capitalize ring-1 ring-inset bg-blue-400/10 text-blue-400 ring-blue-400/30">{{Str::title(Auth()->user()->role)}}</span>
                                
                            </div>
                        </figcaption>
                        <a class="flex items-center justify-center rounded-md bg-murky-600 p-2 outline-none"
                            href="/id/settings">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" aria-hidden="true" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z">
                                </path>
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </a>
                    </figure>
                    <div class="mt-6 flex items-center space-x-2 border-t border-murky-600 pt-6">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" aria-hidden="true" class="h-5 w-5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z">
                            </path>
                        </svg>
                        <span>+{{ Auth::user()->no_wa }}</span>
                    </div>
                </div>
                {{-- Semua tampilan saldo dan deposit di dashboard disembunyikan sementara --}}
                {{-- <div>
                    <div class="rounded-lg border border-murky-600 bg-murky-700 p-6">
                        <div class="flex flex-col items-start justify-between gap-4 sm:flex-row">
                            <div>
                                <p class="text-sm font-medium">Saldo Anda</p>
                                <h3 class="mt-1 text-[24px] font-bold text-primary-500 lg:text-[26px]">Rp&nbsp;{{ number_format(Auth::user()->balance, 0, ',', '.') }}
                                </h3>
                            </div>
                            <div class="flex items-center justify-center space-x-2">
                                <a class="rounded-md bg-murky-600 p-2 outline-none" href="{{ route('reload') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-5 w-5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </a>
                                <a class="inline-flex items-center justify-center rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white duration-300 hover:bg-primary-400 outline-none"
                                    href="{{ route('deposit') }}">
                                    Top up
                                </a>
                            </div>
                        </div>
                    </div>
                </div> --}}
                <style>
                    .custom-color-yellow {
                        background-color: #ffcc00 !important;
                        /* Darker shade of yellow */
                    }

                    .custom-color-blue {
                        background-color: #4682B4 !important;
                        /* Light blue */
                    }

                    .custom-color-green {
                        background-color: #00FF7F !important;
                        /* Light green */
                    }

                    .custom-color-red {
                        background-color: #ff0000 !important;
                        /* Rose red */
                    }
                </style>
<div class="col-span-1 lg:col-span-2">
    <h1 class="pb-4 text-lg font-semibold">Jumlah Transaksi Hari Ini</h1>
    <div class="grid grid-cols-2 gap-4 xl:grid-cols-10 xl:gap-8">
        <div class="flex flex-col items-center justify-center rounded-lg border border-murky-600 bg-murky-700 p-6 col-span-2 xl:col-span-4">
            <div class="flex items-center justify-center text-4xl font-semibold">{{ $banyak_pembelian }}</div>
            <div class="pt-4 text-sm font-medium">Total Transaksi</div>
        </div>
        <div class="flex flex-col items-center justify-center rounded-lg border border-murky-600 bg-murky-700 p-6 col-span-2 xl:col-span-4">
            <div class="flex items-center justify-center text-4xl font-semibold">Rp {{ number_format($total_pembelian, 0, ',', '.') }}</div>
            <div class="pt-4 text-sm font-medium">Total Penjualan</div>
        </div>
        <div class="flex flex-col items-center justify-center rounded-lg border border-murky-600 bg-murky-700 p-6 custom-color-yellow">
            <div class="flex items-center justify-center text-4xl font-semibold">{{ $banyak_pembelian_pending }}</div>
            <div class="pt-4 text-sm font-medium">Menunggu</div>
        </div>
        <div class="flex flex-col items-center justify-center rounded-lg border border-murky-600 bg-murky-700 p-6 custom-color-blue">
            <div class="flex items-center justify-center text-4xl font-semibold">{{ $banyak_pembelian - $banyak_pembelian_success - $banyak_pembelian_batal - $banyak_pembelian_pending }}</div>
            <div class="pt-4 text-sm font-medium">Dalam Proses</div>
        </div>
        <div class="flex flex-col items-center justify-center rounded-lg border border-murky-600 bg-murky-700 p-6 custom-color-green">
            <div class="flex items-center justify-center text-4xl font-semibold">{{ $banyak_pembelian_success }}</div>
            <div class="pt-4 text-sm font-medium">Sukses</div>
        </div>
        <div class="flex flex-col items-center justify-center rounded-lg border border-murky-600 bg-murky-700 p-6 custom-color-red">
            <div class="flex items-center justify-center text-4xl font-semibold">{{ $banyak_pembelian_batal }}</div>
            <div class="pt-4 text-sm font-medium">Gagal</div>
        </div>
    </div>
</div>


            </div>
            <div class="col-span-1 lg:col-span-2 mt-2">
                <h1 class="pb-4 text-lg font-semibold">Riwayat Transaksi Terbaru Hari Ini</h1>
                <div class="space-y-4">
                     @if(count($data) > 0)
                <div class="-mx-4 overflow-x-auto ring-1 ring-murky-600 sm:mx-0 sm:rounded-lg">
                    <table class="min-w-full divide-y divide-murky-600">
                        <thead>
                            <tr>
                                <th
                                    scope="col"
                                    colspan="1"
                                    class="table-cell px-3 py-3.5 text-left text-xs font-semibold text-white first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&amp;:nth-last-child(2)]:table-cell"
                                >
                                    <div class="cursor-pointer select-none flex whitespace-nowrap items-center justify-between">Nomor Invoice</div>
                                </th>
                                <th
                                    scope="col"
                                    colspan="1"
                                    class="table-cell px-3 py-3.5 text-left text-xs font-semibold text-white first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&amp;:nth-last-child(2)]:table-cell"
                                >
                                    <div class="">ID Trx</div>
                                </th>
                                <th
                                    scope="col"
                                    colspan="1"
                                    class="table-cell px-3 py-3.5 text-left text-xs font-semibold text-white first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&amp;:nth-last-child(2)]:table-cell"
                                >
                                    <div class="">Item</div>
                                </th>
                                <th
                                    scope="col"
                                    colspan="1"
                                    class="table-cell px-3 py-3.5 text-left text-xs font-semibold text-white first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&amp;:nth-last-child(2)]:table-cell"
                                >
                                    <div class="">Inputan / ID</div>
                                </th>
                                <th
                                    scope="col"
                                    colspan="1"
                                    class="table-cell px-3 py-3.5 text-left text-xs font-semibold text-white first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&amp;:nth-last-child(2)]:table-cell"
                                >
                                    <div class="cursor-pointer select-none flex whitespace-nowrap items-center justify-between">Harga</div>
                                </th>
                                <th
                                    scope="col"
                                    colspan="1"
                                    class="table-cell px-3 py-3.5 text-left text-xs font-semibold text-white first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&amp;:nth-last-child(2)]:table-cell"
                                >
                                    <div class="cursor-pointer select-none flex whitespace-nowrap items-center justify-between">Tanggal</div>
                                </th>
                                <th
                                    scope="col"
                                    colspan="1"
                                    class="table-cell px-3 py-3.5 text-left text-xs font-semibold text-white first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&amp;:nth-last-child(2)]:table-cell"
                                >
                                    <div class="">Status</div>
                                </th>
                            </tr>
                        </thead>
                    @foreach($data as $pesanan)
    @if($pesanan->tipe_transaksi !== 'joki')
        @php
            $zone = $pesanan->zone != null ? "-".$pesanan->zone : "";
            $status = $pesanan->status;
            $label_pesanan = '';

            if ($status == 'Success' || $status == 'Sukses') {
                $label_pesanan = 'emerald-200';
                $status = 'Success';
            } elseif ($status == 'Pending' || $status == 'pending') {
                $label_pesanan = 'yellow-300';
            } elseif ($status == 'Proses' || $status == 'Process') {
                $label_pesanan = 'sky-600';
                $status = 'Process';
            } else {
                $label_pesanan = 'rose-300';
            }
        @endphp 
        <tbody>
            <tr>
                <td class="table-cell px-3 py-3.5 text-left text-xs font-medium text-text-color first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&amp;:nth-last-child(2)]:table-cell !text-text-color">
                    <div class="whitespace-nowrap">
                        <a class="whitespace-nowrap text-primary-500" href="{{ ENV('APP_URL') }}/id/invoices/{{ $pesanan->order_id }}" style="outline: none;">{{ $pesanan->order_id }}</a>
                    </div>
                </td>
                <td class="table-cell px-3 py-3.5 text-left text-xs font-medium text-text-color first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&amp;:nth-last-child(2)]:table-cell !text-text-color">
                    <div class="whitespace-nowrap">n/a</div>
                </td>
                <td class="table-cell px-3 py-3.5 text-left text-xs font-medium text-text-color first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&amp;:nth-last-child(2)]:table-cell !text-text-color">
                    <div class="whitespace-nowrap">{{ $pesanan->layanan }}</div>
                </td>
                <td class="table-cell px-3 py-3.5 text-left text-xs font-medium text-text-color first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&amp;:nth-last-child(2)]:table-cell !text-text-color">
                    <div class="whitespace-nowrap">
                        <button type="button" class="border-murky-400 bg-murky-600 hover:bg-murky-700 flex items-center space-x-2 rounded-md border px-2.5 py-1">
                            <div class="max-w-[172px] truncate md:w-auto md:max-w-none !max-w-[8rem]">{{ $pesanan->user_id }} - {{ $pesanan->zone }}</div>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-5 w-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184"></path>
                            </svg>
                        </button>
                    </div>
                </td>
                <td class="table-cell px-3 py-3.5 text-left text-xs font-medium text-text-color first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&amp;:nth-last-child(2)]:table-cell !text-text-color">
                    <div class="whitespace-nowrap">
                        <span>Rp&nbsp;{{ number_format($pesanan->harga, 0, ',', '.') }}</span>
                    </div>
                </td>
                <td class="table-cell px-3 py-3.5 text-left text-xs font-medium text-text-color first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&amp;:nth-last-child(2)]:table-cell !text-text-color">
                    <div class="whitespace-nowrap">{{ $pesanan->created_at }}</div>
                </td>
                 <td class="table-cell px-3 py-3.5 text-left text-xs font-medium text-white first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&amp;:nth-last-child(2)]:table-cell">
        <div class="whitespace-nowrap">
            @if ($status == 'Progress' || $status == 'Process')
                <span class="inline-flex rounded-sm px-2 text-xs font-semibold leading-5 print:p-0 bg-{{ $label_pesanan }} text-white">{{ $status }}</span>
            @else
                <span class="inline-flex rounded-sm px-2 text-xs font-semibold leading-5 print:p-0 bg-{{ $label_pesanan }} text-emerald-900">{{ $status }}</span>
            @endif
        </div>
    </td>
            </tr>
    @else
        @foreach($joki as $jokis)
            @if($jokis->order_id == $pesanan->order_id)
                @php
                    $zone = $pesanan->zone != null ? "-".$pesanan->zone : "";
                    $status = $jokis->status_joki;
                    $label_pesanan = '';

                    if ($status == 'Sukses') {
                        $label_pesanan = 'emerald-200';
                    } else {
                        $label_pesanan = 'rose-300';
                    }
                @endphp  
                <tr>
                    <td class="table-cell px-3 py-3.5 text-left text-xs font-medium text-text-color first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&amp;:nth-last-child(2)]:table-cell !text-text-color">
                        <div class="whitespace-nowrap">{{ $pesanan->created_at }}</div>
                    </td>
                    <td class="table-cell px-3 py-3.5 text-left text-xs font-medium text-text-color first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&amp;:nth-last-child(2)]:table-cell !text-text-color">
                        <div class="whitespace-nowrap">
                            <span class="inline-flex rounded-sm px-2 text-xs font-semibold leading-5 print:p-0 bg-sky-600 text-white">{{ $pesanan->created_at }}</span>
                        </div>
                    </td>
                </tr>
            @endif
        @endforeach
    @endif
@endforeach
</tbody>

                        </table>
                    </div>
                    
                    
                    
                    
                    
                 @else
                <div class="-mx-4 overflow-x-auto ring-1 ring-murky-600 sm:mx-0 sm:rounded-lg">
                    <table class="min-w-full divide-y divide-murky-600">
                        <thead>
                            <tr>
                                <th
                                    scope="col"
                                    colspan="1"
                                    class="table-cell px-3 py-3.5 text-left text-xs font-semibold text-white first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&amp;:nth-last-child(2)]:table-cell"
                                >
                                    <div class="cursor-pointer select-none flex whitespace-nowrap items-center justify-between">Nomor Invoice</div>
                                </th>
                                <th
                                    scope="col"
                                    colspan="1"
                                    class="table-cell px-3 py-3.5 text-left text-xs font-semibold text-white first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&amp;:nth-last-child(2)]:table-cell"
                                >
                                    <div class="">ID Trx</div>
                                </th>
                                <th
                                    scope="col"
                                    colspan="1"
                                    class="table-cell px-3 py-3.5 text-left text-xs font-semibold text-white first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&amp;:nth-last-child(2)]:table-cell"
                                >
                                    <div class="">Item</div>
                                </th>
                                <th
                                    scope="col"
                                    colspan="1"
                                    class="table-cell px-3 py-3.5 text-left text-xs font-semibold text-white first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&amp;:nth-last-child(2)]:table-cell"
                                >
                                    <div class="">Inputan / ID</div>
                                </th>
                                <th
                                    scope="col"
                                    colspan="1"
                                    class="table-cell px-3 py-3.5 text-left text-xs font-semibold text-white first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&amp;:nth-last-child(2)]:table-cell"
                                >
                                    <div class="cursor-pointer select-none flex whitespace-nowrap items-center justify-between">Harga</div>
                                </th>
                                <th
                                    scope="col"
                                    colspan="1"
                                    class="table-cell px-3 py-3.5 text-left text-xs font-semibold text-white first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&amp;:nth-last-child(2)]:table-cell"
                                >
                                    <div class="cursor-pointer select-none flex whitespace-nowrap items-center justify-between">Tanggal</div>
                                </th>
                                <th
                                    scope="col"
                                    colspan="1"
                                    class="table-cell px-3 py-3.5 text-left text-xs font-semibold text-white first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&amp;:nth-last-child(2)]:table-cell"
                                >
                                    <div class="">Status</div>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                                           <tr>
                  <td colspan="7" class="py-24 px-4 text-center sm:px-6">
                     <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke-width="1.5" stroke="currentColor" aria-hidden="true"
                        class="mx-auto h-12 w-12 text-white">
                        <path stroke-linecap="round" stroke-linejoin="round"
                           d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z">
                        </path>
                     </svg>
                     <h3 class="mt-2 font-semibold text-white">Data tidak ditemukan!</h3>
                     <p class="mt-1 text-sm text-murky-300">Tidak ada aktifitasi data.</p>
                  </td>
               </tr>
                                       </tbody>
                        </table>
                    </div>
                   @endif
                    <div class="mt-4">
                
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