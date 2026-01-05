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
			     <img src="{{url('')}}{{ !$config ? '' : $config->logo_header }}" width="100" height="100" class="mx-auto h-32 w-auto" style="color: transparent;" alt="{{ $config->judul_web }}"/>
			<h2 class="mx-auto max-w-2xl text-center text-3xl font-bold tracking-tight text-white sm:text-4xl">Leaderboard!</h2>
                        <div class="mt-6 mx-auto max-w-2xl text-center">    <span class="text-baser">Reseller, saatnya jadi juara! Raih posisi Top 10 di leaderboard Top Up sebanyak mungkin dan dapatkan hadiah menarik! Tingkatkan penjualanmu sekarang!</span></div>
            </div>
	</div>
</div>
</section>
<section id="leaderboard" class="relative pb-12">
   <div class="container">
      <!--<p class="mx-auto mt-6 max-w-3xl text-center text-lg leading-8 text-murky-400">Berikut ini adalah daftar 10 pembelian terbanyak yang dilakukan oleh pelanggan kami. Data ini diambil dari sistem kami dan selalu diperbaharui.</p>-->
     <div class="isolate mx-auto mt-10 grid max-w-md grid-cols-1 gap-8 md:grid-cols-2 lg:mx-0 lg:max-w-none xl:grid-cols-3">
    <div>
        <div class="flex justify-center">
            <h2 class="ml-3 inline-flex rounded-md text-center bg-murky-700 shadow py-1 px-4 text-xs leading-6">Top 10 - Hari Ini</h2>
        </div>

        <div class="relative rounded-md bg-murky-800 p-6">
            <ul class="space-y-3 text-sm leading-6 text-white">
                @foreach($top10Today as $index => $item)
                    @if ($item->username)
                        <li class="flex items-center justify-between gap-x-3">
                            <div>{{ $index + 1 }}. {{ $item->username }} @if($index == 0)🏆 @elseif($index == 1) @elseif($index == 2) @endif</div>
                            <div>Rp&nbsp;{{ number_format($item->total_harga, 0, '.', ',') }}</div>
                        </li>
                    @endif
                @endforeach
            </ul>
        </div>
    </div>

    <div>
        <div class="flex justify-center">
            <h2 class="ml-3 inline-flex rounded-md text-center bg-murky-700 shadow py-1 px-4 text-xs leading-6">Top 10 - Minggu Ini</h2>
        </div>

        <div class="relative rounded-md bg-murky-800 p-6">
            <ul class="space-y-3 text-sm leading-6 text-white">
                @foreach($top10ThisWeek as $index => $item)
                    @if ($item->username)
                        <li class="flex items-center justify-between gap-x-3">
                            <div>{{ $index + 1 }}. {{ $item->username }} @if($index == 0)🏆 @elseif($index == 1) @elseif($index == 2) @endif</div>
                            <div>Rp&nbsp;{{ number_format($item->total_harga, 0, '.', ',') }}</div>
                        </li>
                    @endif
                @endforeach
            </ul>
        </div>
    </div>

    <div>
        <div class="flex justify-center">
            <h2 class="ml-3 inline-flex rounded-md text-center bg-murky-700 shadow py-1 px-4 text-xs leading-6">Top 10 - Bulan Ini</h2>
        </div>

        <div class="relative rounded-md bg-murky-800 p-6 ">
            <ul class="space-y-3 text-sm leading-6 text-white">
                @foreach($top10ThisMonth as $index => $item)
                    @if ($item->username)
                        <li class="flex items-center justify-between gap-x-3">
                            <div>{{ $index + 1 }}. {{ $item->username }} @if($index == 0)🏆 @elseif($index == 1) @elseif($index == 2) @endif</div>
                            <div>Rp&nbsp;{{ number_format($item->total_harga, 0, '.', ',') }}</div>
                        </li>
                    @endif
                @endforeach
            </ul>
        </div>
    </div>
</div>

   </div>
</section>




@include('../footer')

@endsection