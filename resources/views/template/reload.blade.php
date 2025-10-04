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
                        <a class="group flex items-center gap-3 rounded-md bg-gradient-to-r to-transparent px-3 py-2 text-sm font-medium text-white hover:from-murky-700" style="outline: none;" href="{{ route('dashboard') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-5 w-5">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"
                                ></path>
                            </svg>
                            <span class="hidden truncate md:block">Dashboard</span>
                        </a>
                        <a class="group flex items-center gap-3 rounded-md  px-3 py-2 text-sm font-medium text-white from-primary-500" style="outline: none;" href="{{ route('riwayat') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="hidden truncate md:block">Transaksi</span>
                        </a>
                        <!--<a class="group flex items-center gap-3 rounded-md bg-gradient-to-r to-transparent px-3 py-2 text-sm font-medium text-white hover:from-murky-700" style="outline: none;" href="{{ route('deposit') }}">-->
                        <!--    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-5 w-5">-->
                        <!--        <path-->
                        <!--            stroke-linecap="round"-->
                        <!--            stroke-linejoin="round"-->
                        <!--            d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 9m18 0V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v3"-->
                        <!--        ></path>-->
                        <!--    </svg>-->
                        <!--    <span class="hidden truncate md:block">Riwayat Deposit</span>-->
                        <!--</a>-->
                        <!--<a class="group flex items-center gap-3 rounded-md bg-gradient-to-r to-transparent px-3 py-2 text-sm font-medium text-white hover:from-murky-700" style="outline: none;" href="/id/dashboard/mutation">-->
                        <!--    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-5 w-5">-->
                        <!--        <path-->
                        <!--            stroke-linecap="round"-->
                        <!--            stroke-linejoin="round"-->
                        <!--            d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"-->
                        <!--        ></path>-->
                        <!--    </svg>-->
                        <!--    <span class="hidden truncate md:block">Riwayat Mutasi</span>-->
                        <!--</a>-->
                        <!--<a class="group flex items-center gap-3 rounded-md bg-gradient-to-r to-transparent px-3 py-2 text-sm font-medium text-white hover:from-murky-700" style="outline: none;" href="/id/dashboard/report">-->
                        <!--    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-5 w-5">-->
                        <!--        <path-->
                        <!--            stroke-linecap="round"-->
                        <!--            stroke-linejoin="round"-->
                        <!--            d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25M9 16.5v.75m3-3v3M15 12v5.25m-4.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"-->
                        <!--        ></path>-->
                        <!--    </svg>-->
                        <!--    <span class="hidden truncate md:block">Laporan</span>-->
                        <!--</a>-->
                    </div>
                    <div class="w-full pt-4">
                        <button type="button" class="flex w-full items-center gap-3 rounded-md bg-gradient-to-r px-3 py-2 text-sm font-medium text-rose-500 hover:from-murky-700">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-5 w-5">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"
                                ></path>
                            </svg>
                            <span class="hidden md:block">Keluar</span>
                        </button>
                    </div>
                </nav>
            </aside>
        </div>
        <div class="col-span-8 sm:col-span-7 sm:col-start-2 md:col-span-7 md:col-start-3">
            <div class="pb-8 sm:flex sm:items-center">
                <div class="sm:flex-auto">
                    <h1 class="text-base font-semibold leading-6 text-white">Riwayat Transaksi Deposit</h1>
                    <p class="mt-2 text-sm text-murky-200">Menampilkan data riwayat transaksi yang telah Anda lakukan selama periode.</p>
                </div>
            </div>
        <!--    <form id="filterForm" method="GET" action="">-->
        <!--    <div class="space-y-4">-->
        <!--        <div class="grid gap-4">-->
        <!--            <div class="flex flex-col gap-2 rounded-xl border border-murky-600 p-4 md:p-6">-->
        <!--                <div class="grid grid-cols-2 gap-4 md:grid-cols-4">-->
        <!--                    <div class="flex flex-col gap-2">-->
        <!--                        <div class="text-xs">Status</div>-->
        <!--                        <select-->
        <!--                            name="status" id="status" onchange="submitForm()"-->
        <!--                            class="relative block w-full appearance-none rounded-none border border-murky-600 bg-murky-700 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 !rounded-md !border-0 !bg-murky-200 !text-murky-800 !placeholder-murky-800 accent-murky-800 !ring-0 placeholder:text-xs focus:!border-transparent focus:!bg-white focus:!ring-transparent"-->
        <!--                        >-->
        <!--                            <option value="">Semua</option>-->
        <!--                            <option value="Menunggu">Menunggu</option>-->
        <!--                            <option value="Pending">Pending</option>-->
        <!--                            <option value="Success">Success</option>-->
        <!--                            <option value="Batal">Batal</option>-->
        <!--                        </select>-->
        <!--                    </div>-->
                            
        <!--                    <div class="flex flex-col gap-2">-->
        <!--                        <div class="text-xs">Tanggal Mulai</div>-->
        <!--                        <div class="flex flex-col items-start">-->
        <!--                            <input-->
        <!--                                type="date" name="tanggal_mulai" onchange="submitForm()" value=""-->
        <!--                                class="relative block w-full appearance-none rounded-none border border-murky-600 bg-murky-700 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 !rounded-md !border-0 !bg-murky-200 !text-murky-800 !placeholder-murky-800 accent-murky-800 !ring-0 placeholder:text-xs focus:!border-transparent focus:!bg-white focus:!ring-transparent !rounded-md"-->
        <!--                                type="date"-->
        <!--                                placeholder="Tanggal Mulai"-->
                                        
        <!--                            />-->
        <!--                        </div>-->
        <!--                    </div>-->
        <!--                    <div class="flex flex-col gap-2">-->
        <!--                        <div class="text-xs">Tanggal Akhir</div>-->
        <!--                        <div class="flex flex-col items-start">-->
        <!--                            <input-->
        <!--                                name="tanggal_akhir" onchange="submitForm()" value=""-->
        <!--                                class="relative block w-full appearance-none rounded-none border border-murky-600 bg-murky-700 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 !rounded-md !border-0 !bg-murky-200 !text-murky-800 !placeholder-murky-800 accent-murky-800 !ring-0 placeholder:text-xs focus:!border-transparent focus:!bg-white focus:!ring-transparent !rounded-md"-->
        <!--                                type="date"-->
        <!--                                placeholder="Tanggal Akhir"-->
        <!--                            />-->
        <!--                        </div>-->
        <!--                    </div>-->
        <!--                    <div class="flex flex-col gap-2">-->
        <!--                        <div class="text-xs">Cari</div>-->
        <!--                        <div class="flex flex-col items-start">-->
        <!--                            <input-->
        <!--                                name="order_id" onchange="submitForm()" value=""-->
        <!--                                class="relative block w-full appearance-none rounded-none border border-murky-600 bg-murky-700 px-3 py-2 text-xs text-white placeholder-murky-200 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-75 !rounded-md !border-0 !bg-murky-200 !text-murky-800 !placeholder-murky-800 accent-murky-800 !ring-0 placeholder:text-xs focus:!border-transparent focus:!bg-white focus:!ring-transparent !rounded-md"-->
        <!--                                type="text"-->
        <!--                                placeholder="trxid"-->
        <!--                            />-->
        <!--                        </div>-->
        <!--                    </div>-->
        <!--                </div>-->
        <!--            </div>-->
        <!--        </div>-->
        <!--        <div class="flex items-center justify-end gap-x-4">-->
        <!--            <button-->
        <!--                type="button"-->
        <!--                disabled=""-->
        <!--                class="inline-flex w-full items-center justify-center rounded-md border border-murky-500 bg-murky-600 px-4 py-2 text-xs hover:bg-murky-700 disabled:cursor-not-allowed disabled:opacity-75 md:w-auto"-->
        <!--            >-->
        <!--                Download CSV-->
        <!--            </button>-->
        <!--            <button-->
        <!--                type="button"-->
        <!--                disabled=""-->
        <!--                class="inline-flex w-full items-center justify-center rounded-md border border-murky-500 bg-murky-600 px-4 py-2 text-xs hover:bg-murky-700 disabled:cursor-not-allowed disabled:opacity-75 md:w-auto"-->
        <!--            >-->
        <!--                Download XLSX-->
        <!--            </button>-->
        <!--            <select  name="entries" -->
        <!--id="entries" -->
        <!--onchange="document.getElementById('filterForm').submit()"  class="inline-flex w-full cursor-pointer items-center justify-center rounded-md border border-murky-500 bg-murky-600 py-2 text-xs ring-inset hover:bg-murky-700 focus:ring-2 focus:ring-primary-500 md:w-auto">-->
        <!--             <option value="5" >5 Entries</option>-->
        <!--<option value="10" >10 Entries</option>-->
        <!--<option value="25" >25 Entries</option>-->
        <!--<option value="50" >50 Entries</option>-->
        <!--<option value="100" >100 Entries</option>-->
        <!--            </select>-->
        <!--        </div>-->
        <!--        </form>-->
                {{-- Semua tampilan riwayat deposit disembunyikan sementara --}}
                <div class="-mx-4 overflow-x-auto ring-1 ring-murky-600 sm:mx-0 sm:rounded-lg">
                   <table class="min-w-full divide-y divide-murky-600">
    <thead>
        <tr>
            <th scope="col" colspan="1" class="table-cell px-3 py-3.5 text-left text-xs font-semibold text-white first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&:nth-last-child(2)]:table-cell">
                <div class="cursor-pointer select-none flex whitespace-nowrap items-center justify-between">Nomor Invoice</div>
            </th>
            <th scope="col" colspan="1" class="table-cell px-3 py-3.5 text-left text-xs font-semibold text-white first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&:nth-last-child(2)]:table-cell">
                <div class="">ID Trx</div>
            </th>
            <th scope="col" colspan="1" class="table-cell px-3 py-3.5 text-left text-xs font-semibold text-white first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&:nth-last-child(2)]:table-cell">
                <div class="">Metode</div>
            </th>
            <th scope="col" colspan="1" class="table-cell px-3 py-3.5 text-left text-xs font-semibold text-white first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&:nth-last-child(2)]:table-cell">
                <div class="cursor-pointer select-none flex whitespace-nowrap items-center justify-between">Jumlah</div>
            </th>
            <th scope="col" colspan="1" class="table-cell px-3 py-3.5 text-left text-xs font-semibold text-white first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&:nth-last-child(2)]:table-cell">
                <div class="cursor-pointer select-none flex whitespace-nowrap items-center justify-between">Tanggal</div>
            </th>
            <th scope="col" colspan="1" class="table-cell px-3 py-3.5 text-left text-xs font-semibold text-white first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&:nth-last-child(2)]:table-cell">
                <div class="">Status</div>
            </th>
        </tr>
    </thead>
    <tbody id="data-body">
        @forelse($data as $pesanan)
            @if($pesanan->tipe_transaksi !== 'joki')
                @php
                    $zone = $pesanan->zone != null ? "-".$pesanan->zone : "";
                    $label_pesanan = '';
                    if ($pesanan->status == "Pending" || $pesanan->status == "Menunggu Pembayaran" || $pesanan->status == "Waiting") {
                        $label_pesanan = 'yellow-300';
                    } elseif ($pesanan->status == "Processing" || $pesanan->status == 'Process') {
                        $label_pesanan = 'sky-600';
                    } elseif ($pesanan->status == "Success" || $pesanan->status == 'Sukses') {
                        $label_pesanan = 'emerald-200';
                    } else {
                        $label_pesanan = 'rose-300';
                    }
                @endphp
                <tr class="table-row">
                    <td class="table-cell px-3 py-3.5 text-left text-xs font-medium text-white first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&:nth-last-child(2)]:table-cell">
                        <a href="{{url('')}}/id/deposit/{{ $pesanan->order_id }}">
                            <div class="whitespace-nowrap text-primary-500">{{ $pesanan->order_id }}</div>
                        </a>
                    </td>
                    <td class="table-cell px-3 py-3.5 text-left text-xs font-medium text-white first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&:nth-last-child(2)]:table-cell">
                        <div class="whitespace-nowrap">n/a</div>
                    </td>
                    <td class="table-cell px-3 py-3.5 text-left text-xs font-medium text-white first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&:nth-last-child(2)]:table-cell">
                        <div class="whitespace-nowrap">
                            <button type="button" class="flex items-center space-x-2 rounded-md border border-murky-400 bg-murky-600 px-2.5 py-1 hover:bg-murky-700">
                                <div class="max-w-[172px] truncate md:w-auto md:max-w-none !max-w-[8rem]">QRIS DEPOSIT</div>
                            </button>
                        </div>
                    </td>
                    <td class="table-cell px-3 py-3.5 text-left text-xs font-medium text-white first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&:nth-last-child(2)]:table-cell">
                        <div class="whitespace-nowrap">Rp&nbsp;{{ number_format($pesanan->jumlah, 0, ',', '.') }}</div>
                    </td>
                    <td class="table-cell px-3 py-3.5 text-left text-xs font-medium text-white first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&:nth-last-child(2)]:table-cell">
                        <div class="whitespace-nowrap"><span>{{ $pesanan->created_at }}</span></div>
                    </td>
                    <td class="table-cell px-3 py-3.5 text-left text-xs font-medium text-white first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&:nth-last-child(2)]:table-cell">
                        <div class="whitespace-nowrap">
                            @if ($pesanan->status == 'Progress' || $pesanan->status == 'Process')
                                <span class="inline-flex rounded-sm px-2 text-xs font-semibold leading-5 print:p-0 bg-{{ $label_pesanan }} text-white">{{ $pesanan->status }}</span>
                            @else
                                <span class="inline-flex rounded-sm px-2 text-xs font-semibold leading-5 print:p-0 bg-{{ $label_pesanan }} text-emerald-900">{{ $pesanan->status }}</span>
                            @endif
                        </div>
                    </td>
                </tr>
            @endif
        @empty
            <tr>
                <td colspan="6" class="py-24 px-4 text-center sm:px-6">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="mx-auto h-12 w-12 text-white">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"></path>
                    </svg>
                    <h3 class="mt-2 font-semibold text-white">Data tidak ditemukan!</h3>
                    <p class="mt-1 text-sm text-murky-300">Tidak ada aktifitasi data.</p>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

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