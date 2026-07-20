@extends('template.template')

@section('content')
@include('../navbar')

    <section id="history" >
<div class="space-y-10">
	<div class="relative overflow-hidden shadow-2xl">
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
		<div class="absolute inset-0 z-10 bg-transparent">
		</div>
			<div class="container relative z-20 py-12 ">
			     @if($logoheader && $logoheader->isi)
			         <img src="{{ url($logoheader->isi) }}" width="100" height="100" class="mx-auto h-32 w-auto" style="color: transparent;" alt="Logo Header"/>
			     @endif
			<h2 class="mx-auto max-w-2xl text-center text-3xl font-bold tracking-tight text-white sm:text-4xl">Leaderboard!</h2>
                        <div class="mt-6 mx-auto max-w-2xl text-center">    <span class="text-baser">Top 10 pembelian terbanyak! Data real dari transaksi sukses di platform kami.</span></div>
            </div>
	</div>
</div>
</section>
<section id="leaderboard" class="relative pb-12">
   <div class="container">
      <!--<p class="mx-auto mt-6 max-w-3xl text-center text-lg leading-8 text-murky-400">Berikut ini adalah daftar 10 pembelian terbanyak yang dilakukan oleh pelanggan kami. Data ini diambil dari sistem kami dan selalu diperbaharui.</p>-->
     <div class="isolate mx-auto mt-10 grid max-w-7xl grid-cols-1 gap-8 md:grid-cols-3">
    <!-- Top 10 Hari Ini -->
    <div>
        <div class="flex justify-center mb-4">
            <h2 class="inline-flex items-center gap-2 rounded-full bg-primary-500/10 border border-primary-500/20 px-4 py-1.5 text-sm font-semibold text-primary-400 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                Top 10 Hari Ini
            </h2>
        </div>

        <div class="rounded-2xl bg-secondary shadow-xl ring-1 ring-white/5 p-4 sm:p-6">
            <ul class="space-y-4 text-sm leading-6 text-white">
                @foreach($top10Today as $index => $item)
                    @if ($item->account_identifier)
                        <li class="relative flex items-center justify-between gap-x-4 p-3 rounded-xl transition-all duration-300 {{ $index < 3 ? 'bg-primary-500/10 border border-primary-500/20' : 'hover:bg-murky-800 border border-transparent hover:border-white/5' }}">
                            <div class="flex items-center gap-x-4">
                                <span class="flex items-center justify-center w-6 font-bold {{ $index == 0 ? 'text-yellow-400 text-lg' : ($index == 1 ? 'text-gray-300 text-lg' : ($index == 2 ? 'text-amber-600 text-lg' : 'text-murky-400')) }}">
                                    @if($index == 0) 🥇 @elseif($index == 1) 🥈 @elseif($index == 2) 🥉 @else #{{ $index + 1 }} @endif
                                </span>
                                <div class="relative">
                                    <img src="https://ui-avatars.com/api/?name={{ urlencode($item->account_identifier) }}&background=random&color=fff&size=40" alt="{{ $item->account_identifier }}" class="h-10 w-10 rounded-full {{ $index < 3 ? 'ring-2 ring-primary-500 focus:ring-offset-2 focus:ring-offset-secondary' : 'ring-1 ring-white/10' }}">
                                    @if($index < 3)
                                        <div class="absolute -bottom-1 -right-1 h-3.5 w-3.5 rounded-full bg-green-500 ring-2 ring-secondary animate-pulse"></div>
                                    @endif
                                </div>
                                <div class="font-medium {{ $index < 3 ? 'text-primary-100' : 'text-gray-200' }}">{{ $item->account_identifier }}</div>
                            </div>
                            <div class="text-right flex flex-col items-end">
                                <span class="font-bold text-primary-400">Rp {{ number_format($item->total_harga, 0, '.', '.') }}</span>
                                <span class="text-[10px] text-murky-400 uppercase tracking-wider">{{ $item->transaction_count }}x Top Up</span>
                            </div>
                        </li>
                    @endif
                @endforeach
                @if(count($top10Today) == 0)
                    <li class="py-10 text-center text-murky-400 italic">Belum ada top up hari ini. Jadilah yang pertama!</li>
                @endif
            </ul>
        </div>
    </div>

    <!-- Top 10 Minggu Ini -->
    <div>
        <div class="flex justify-center mb-4">
            <h2 class="inline-flex items-center gap-2 rounded-full bg-primary-500/10 border border-primary-500/20 px-4 py-1.5 text-sm font-semibold text-primary-400 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                Top 10 Minggu Ini
            </h2>
        </div>

        <div class="rounded-2xl bg-secondary shadow-xl ring-1 ring-white/5 p-4 sm:p-6">
            <ul class="space-y-4 text-sm leading-6 text-white">
                @foreach($top10ThisWeek as $index => $item)
                    @if ($item->account_identifier)
                        <li class="relative flex items-center justify-between gap-x-4 p-3 rounded-xl transition-all duration-300 {{ $index < 3 ? 'bg-primary-500/10 border border-primary-500/20' : 'hover:bg-murky-800 border border-transparent hover:border-white/5' }}">
                            <div class="flex items-center gap-x-4">
                                <span class="flex items-center justify-center w-6 font-bold {{ $index == 0 ? 'text-yellow-400 text-lg' : ($index == 1 ? 'text-gray-300 text-lg' : ($index == 2 ? 'text-amber-600 text-lg' : 'text-murky-400')) }}">
                                    @if($index == 0) 🥇 @elseif($index == 1) 🥈 @elseif($index == 2) 🥉 @else #{{ $index + 1 }} @endif
                                </span>
                                <div class="relative">
                                    <img src="https://ui-avatars.com/api/?name={{ urlencode($item->account_identifier) }}&background=random&color=fff&size=40" alt="{{ $item->account_identifier }}" class="h-10 w-10 rounded-full {{ $index < 3 ? 'ring-2 ring-primary-500 focus:ring-offset-2 focus:ring-offset-secondary' : 'ring-1 ring-white/10' }}">
                                </div>
                                <div class="font-medium {{ $index < 3 ? 'text-primary-100' : 'text-gray-200' }}">{{ $item->account_identifier }}</div>
                            </div>
                            <div class="text-right flex flex-col items-end">
                                <span class="font-bold text-primary-400">Rp {{ number_format($item->total_harga, 0, '.', '.') }}</span>
                                <span class="text-[10px] text-murky-400 uppercase tracking-wider">{{ $item->transaction_count }}x Top Up</span>
                            </div>
                        </li>
                    @endif
                @endforeach
                @if(count($top10ThisWeek) == 0)
                    <li class="py-10 text-center text-murky-400 italic">Data minggu ini belum tersedia.</li>
                @endif
            </ul>
        </div>
    </div>

    <!-- Top 10 Bulan Ini -->
    <div>
        <div class="flex justify-center mb-4">
            <h2 class="inline-flex items-center gap-2 rounded-full bg-primary-500/10 border border-primary-500/20 px-4 py-1.5 text-sm font-semibold text-primary-400 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                Top 10 Bulan Ini
            </h2>
        </div>

        <div class="rounded-2xl bg-secondary shadow-xl ring-1 ring-white/5 p-4 sm:p-6">
            <ul class="space-y-4 text-sm leading-6 text-white">
                @foreach($top10ThisMonth as $index => $item)
                    @if ($item->account_identifier)
                         <li class="relative flex items-center justify-between gap-x-4 p-3 rounded-xl transition-all duration-300 {{ $index < 3 ? 'bg-primary-500/10 border border-primary-500/20' : 'hover:bg-murky-800 border border-transparent hover:border-white/5' }}">
                            <div class="flex items-center gap-x-4">
                                <span class="flex items-center justify-center w-6 font-bold {{ $index == 0 ? 'text-yellow-400 text-lg' : ($index == 1 ? 'text-gray-300 text-lg' : ($index == 2 ? 'text-amber-600 text-lg' : 'text-murky-400')) }}">
                                    @if($index == 0) 🥇 @elseif($index == 1) 🥈 @elseif($index == 2) 🥉 @else #{{ $index + 1 }} @endif
                                </span>
                                <div class="relative">
                                    <img src="https://ui-avatars.com/api/?name={{ urlencode($item->account_identifier) }}&background=random&color=fff&size=40" alt="{{ $item->account_identifier }}" class="h-10 w-10 rounded-full {{ $index < 3 ? 'ring-2 ring-primary-500 focus:ring-offset-2 focus:ring-offset-secondary' : 'ring-1 ring-white/10' }}">
                                </div>
                                <div class="font-medium {{ $index < 3 ? 'text-primary-100' : 'text-gray-200' }}">{{ $item->account_identifier }}</div>
                            </div>
                            <div class="text-right flex flex-col items-end">
                                <span class="font-bold text-primary-400">Rp {{ number_format($item->total_harga, 0, '.', '.') }}</span>
                                <span class="text-[10px] text-murky-400 uppercase tracking-wider">{{ $item->transaction_count }}x Top Up</span>
                            </div>
                        </li>
                    @endif
                @endforeach
                @if(count($top10ThisMonth) == 0)
                   <li class="py-10 text-center text-murky-400 italic">Data bulan ini belum tersedia.</li>
                @endif
            </ul>
        </div>
    </div>
</div>

   </div>
</section>




@include('../footer')

@endsection