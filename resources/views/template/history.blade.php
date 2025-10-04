@extends('template.template')

@section('custom_style')


<style>
.table-row:nth-child(even) {
    background-color: var(--warna_1); 
}
th {
    background-color: var(--warna_1); 
}
.rose-300 {
    background-color:#fda4af; 
}
.text-rose-800 {
    --tw-text-opacity: 1;
    color: #9f1239;
}
.from-murky-800 {
    --tw-gradient-from: var(--warna_2) var(--tw-gradient-from-position);
    --tw-gradient-to: var(--warna_1) var(--tw-gradient-to-position);
    --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to);
}
</style>
@endsection


@section('content')

@include('../navbar')


@if(session('error'))
    <script>
        toastr.error('{{ session('error') }}', 'Error');
    </script>
@endif

@if ($errors->any())
    <script>
        @foreach ($errors->all() as $error)
            toastr.error('{{ $error }}', 'Error');
        @endforeach
    </script>
@endif

          

    <section id="history" >
<div class="space-y-10">
	<div class="relative overflow-hidden bg-murky-900 shadow-2xl">
		<div class="absolute z-20 h-full w-full">
			 <div class="area">
            <ul class="circles">
     
     <section class="relative flex items-center overflow-hidden bg-secondary/50 px-4 py-m lg:min-h-[521.96px]">
   @php
    $positions = [
        ['left' => 1130, 'delay' => 0.686975, 'duration' => 8],
        ['left' => -350, 'delay' => 0.670151, 'duration' => 8],
        ['left' => 563, 'delay' => 0.632454, 'duration' => 9],
        ['left' => -969, 'delay' => 0.524996, 'duration' => 5],
        ['left' => -1153, 'delay' => 0.460272, 'duration' => 8],
        ['left' => -560, 'delay' => 0.223791, 'duration' => 6],
        ['left' => -1287, 'delay' => 0.406558, 'duration' => 4],
        ['left' => 211, 'delay' => 0.475533, 'duration' => 6],
        ['left' => -63, 'delay' => 0.394929, 'duration' => 5],
        ['left' => -112, 'delay' => 0.78249, 'duration' => 2],
        ['left' => 946, 'delay' => 0.353787, 'duration' => 5],
        ['left' => 275, 'delay' => 0.309607, 'duration' => 5],
        ['left' => 1216, 'delay' => 0.35162, 'duration' => 8],
        ['left' => -210, 'delay' => 0.413144, 'duration' => 7],
        ['left' => -842, 'delay' => 0.395388, 'duration' => 6],
        ['left' => -323, 'delay' => 0.582248, 'duration' => 4],
        ['left' => 278, 'delay' => 0.710367, 'duration' => 4],
        ['left' => -736, 'delay' => 0.564896, 'duration' => 6],
        ['left' => -800, 'delay' => 0.206357, 'duration' => 7],
        ['left' => -1118, 'delay' => 0.628613, 'duration' => 9],
        ['left' => 1361, 'delay' => 0.529785, 'duration' => 7],
        ['left' => -11, 'delay' => 0.64863, 'duration' => 6],
        ['left' => -678, 'delay' => 0.701722, 'duration' => 3],
        ['left' => -170, 'delay' => 0.366231, 'duration' => 5],
        ['left' => 946, 'delay' => 0.521904, 'duration' => 7],
        ['left' => 1364, 'delay' => 0.484818, 'duration' => 9],
        ['left' => 943, 'delay' => 0.502043, 'duration' => 3],
        ['left' => 1296, 'delay' => 0.577243, 'duration' => 7],
        ['left' => 1273, 'delay' => 0.273317, 'duration' => 5],
        ['left' => -1306, 'delay' => 0.556245, 'duration' => 7],
        ['left' => -360, 'delay' => 0.344508, 'duration' => 5],
        ['left' => 306, 'delay' => 0.332693, 'duration' => 6],
        ['left' => 312, 'delay' => 0.250245, 'duration' => 9],
        ['left' => 649, 'delay' => 0.607517, 'duration' => 2],
        ['left' => 13, 'delay' => 0.379304, 'duration' => 6],
        ['left' => 1269, 'delay' => 0.586079, 'duration' => 5],
        ['left' => -798, 'delay' => 0.675148, 'duration' => 4],
        ['left' => 1199, 'delay' => 0.515393, 'duration' => 6],
        ['left' => 304, 'delay' => 0.799655, 'duration' => 8],
            ];
        @endphp
        
        @for ($i = 0; $i < count($positions); $i++)
            @php
                $left = $positions[$i]['left'];
                $delay = $positions[$i]['delay'];
                $duration = $positions[$i]['duration'];
            @endphp
            <span class="absolute left-1/2 top-1/2 h-1 w-1 rotate-[215deg] animate-meteor-effect rounded-[9999px] bg-white shadow-[0_0_0_1px_#ffffff10] before:absolute before:top-1/2 before:h-[1px] before:w-[80px] before:-translate-y-[0%] before:transform before:bg-gradient-to-r before:from-white before:to-transparent before:content-['']"
                style="top: -20px; left: {{ $left }}px; animation-delay: {{ $delay }}s; animation-duration: {{ $duration }}s;"></span>
        @endfor

               </section>
            </ul>
        </div>
	    	</div>
          <div class="absolute inset-0 z-10 bg-gradient-to-r from-murky-800 via-murky-800/75 to-murky-800/75 sm:to-transparent">
        		</div>
        			<form action="{{ route('cari.post') }}" method="POST" class="container relative z-20 py-12 ">
        		     @csrf
        		     <img
                        src="{{url('')}}{{ !$config ? '' : $config->logo_header }}"
                        width="100"
                        height="100"
                        class="mx-auto h-32 w-auto"
                        style="color: transparent;"
                        alt="Logo"
                      />
        			<h2 class="mx-auto max-w-2xl text-center text-3xl font-bold tracking-tight text-white sm:text-4xl">Cari pesanan kamu!</h2>
        			<p class="mt-6 mx-auto max-w-2xl text-center">Lacak transaksi kamu dengan cara memasukkan Nomor Invoice dibawah ini:</p>
        			<div class="mt-6 mx-auto max-w-2xl text-center">
        				<label for="invoice" class="block text-xs font-medium text-white mb-2 text-left">Masukan Nomor Invoice Kamu</label>
        				<div class="flex flex-col items-start">
        					<input class="PhoneInputInput relative block w-full appearance-none rounded-md border-0 bg-murky-200 px-3 py-2 text-xs text-murky-800 placeholder-murky-800 focus:z-10 focus:border-transparent focus:outline-none focus:ring-transparent focus:bg-white" type="text"  placeholder="WJxxxxx" name="id" required></div>
        			</div>
        			<div class="mt-6 flex items-center justify-center gap-x-6 ">
        				<button class="inline-flex items-center justify-center rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white duration-300 hover:bg-primary-400 disabled:cursor-not-allowed disabled:opacity-75 space-x-2 " type="submit"><svg xmlns="http://www.w3.org/2000/svg" viewbox="0 0 20 20" fill="currentColor" aria-hidden="true" class="h-5 w-5"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd"></path></svg><span>Cari Transaksi</span></button>
        			</div>
        		</form>
        	</div>
        </div>
        </section>
        <div class="container max-w-2xl text-center mt-5">
        	<div class="sm:flex-auto">
        		<h1 class="text-xl font-semibold text-white ">10 Transaksi Terakhir</h1>
        		<p class="mt-2 max-w-2xl text-sm text-white">
        			Ini adalah 10 transaksi terakhir dari semua pengguna. Informasi yang tersedia mencakup tanggal transaksi, kode invoice, layanan, price, dan status.
        		</p>
        	</div>
        </div>
        
        <div class="container">
        	<div class="space-y-4">
        		<div class="-mx-4 overflow-x-auto ring-1 ring-murky-600 sm:mx-0 sm:rounded-lg mt-5">
        		 <table class="min-w-full divide-y divide-murky-600">
            <thead>
                <tr>
                    <th scope="col" colspan="1" class="table-cell px-3 py-3.5 text-left text-xs font-semibold text-white first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&:nth-last-child(2)]:table-cell">
                        <div class="">Date</div>
                    </th>
                    <th scope="col" colspan="1" class="table-cell px-3 py-3.5 text-left text-xs font-semibold text-white first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&:nth-last-child(2)]:table-cell">
                        <div class="cursor-pointer select-none flex whitespace-nowrap items-center justify-between">Invoice</div>
                    </th>
                    <th scope="col" colspan="1" class="table-cell px-3 py-3.5 text-left text-xs font-semibold text-white first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&:nth-last-child(2)]:table-cell">
                        <div class="cursor-pointer select-none flex whitespace-nowrap items-center justify-between">Layanan</div>
                    </th>
                    <th scope="col" colspan="1" class="table-cell px-3 py-3.5 text-left text-xs font-semibold text-white first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&:nth-last-child(2)]:table-cell">
                        <div class="cursor-pointer select-none flex whitespace-nowrap items-center justify-between">Price</div>
                    </th>
                    <th scope="col" colspan="1" class="table-cell px-3 py-3.5 text-left text-xs font-semibold text-white first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&:nth-last-child(2)]:table-cell">
                        <div class="">Status</div>
                    </th>
                </tr>
            </thead>
            <tbody id="data-body">
                @forelse ($pembelians as $pembelian)
                    @php
                        $status = $pembelian->status;
                        if ($status == 'Success' || $status == 'Sukses') {
                            $label = 'emerald-200';
                            $status = 'Success';
                        } elseif ($status == 'Pending' || $status == 'pending') {
                            $label = 'yellow-300';
                        } elseif ($status == 'Proses' || $status == 'Process') {
                            $label = 'sky-600';
                            $status = 'Process';
                        } elseif ($status == 'Batal' || $status == 'Gagal') { 
                            $label = 'rose-300'; 
                            $status = 'Cancelled'; 
                        } else {
                            $label = 'rose-300';
                        }
                    @endphp
                    <tr class="table-row">
                        <td class="table-cell px-3 py-3.5 text-left text-xs font-medium text-white first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&:nth-last-child(2)]:table-cell">
                            <div class="whitespace-nowrap">{{ $pembelian->created_at }}</div>
                        </td>
                        <td class="table-cell px-3 py-3.5 text-left text-xs font-medium text-white first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&:nth-last-child(2)]:table-cell">
                            <div class="whitespace-nowrap">{{ substr($pembelian->order_id, 0, 2) . '*******' . substr($pembelian->order_id, -3) }}</div>
                        </td>
                        <td class="table-cell px-3 py-3.5 text-left text-xs font-medium text-white first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&:nth-last-child(2)]:table-cell">
                            <div class="whitespace-nowrap">{{ $pembelian->layanan }}</div>
                        </td>
                        <td class="table-cell px-3 py-3.5 text-left text-xs font-medium text-white first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&:nth-last-child(2)]:table-cell">
                            <div class="whitespace-nowrap"><span>Rp&nbsp;{{ number_format($pembelian->harga, 0, ',', '.') }}</span></div>
                        </td>
                        <td class="table-cell px-3 py-3.5 text-left text-xs font-medium text-white first:table-cell first:pl-4 sm:first:pl-6 first:pr-4 last:relative last:table-cell sm:last:pr-6 [&:nth-last-child(2)]:table-cell">
                            <div class="whitespace-nowrap">
                                @if ($status == 'Progress' || $status == 'Process')
                                    <span class="inline-flex  rounded-xl px-2 text-xs font-semibold leading-5 print:p-0 bg-{{ $label }} text-white">{{ $status }}</span>
                                @elseif ($status == 'Batal' || $status == 'Cancelled' )
                                    <span class="inline-flex  rounded-xl px-2 text-xs font-semibold leading-5 print:p-0 bg-{{ $label }} text-rose-800">{{ $status }}</span>
                                @else
                                    <span class="inline-flex  rounded-xl px-2 text-xs font-semibold leading-5 print:p-0 bg-{{ $label }} text-emerald-900">{{ $status }}</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-24 px-4 text-center sm:px-6">
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
@include('../footer')
@push('custom_script')
@endpush
@endsection
