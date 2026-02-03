@extends('template.template')
@section('custom_style')
<style>
    
</style>
@endsection
@section('content')
@include('../navbar')
<style>
    .bg-gradient-index {
        --gradient-theme-index: linear-gradient(to top, var(--warna_2) 0%, var(--warna_1) 100%);
        background-image: var(--gradient-theme-index);
    }
</style>
<section id="hero" class="relative mb-4 bg-transparent py-4 shadow-2xl ">
    <div class="hero-swiper swiper  container ">
        <div id="heroo"  class="swiper-wrapper ">
            @foreach($banner as $key => $data)
            <div class="swiper-slide">
                <img src="{{ asset($data->path) }}" class="w-full h-auto object-cover  rounded-3xl "/>
            </div>
            @endforeach
        </div>
        <div class="swiper-pagination"></div>
        <div class="hidden items-center justify-end space-x-2 md:flex">
        </div>
    </div>
  
    <div class="absolute inset-0 -z-10">
        <div class="area">
               <ul class="circles bg-gradient-index">
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
    
    @foreach ($positions as $position)
        <span class="absolute left-1/2 top-1/2 h-1 w-1 rotate-[215deg] animate-meteor-effect rounded-[9999px] bg-white shadow-[0_0_0_1px_#ffffff10] before:absolute before:top-1/2 before:h-[1px] before:w-[80px] before:-translate-y-[0%] before:transform before:bg-gradient-to-r before:from-white before:to-transparent before:content-['']"
            style="top: -20px; left: {{ $position['left'] }}px; animation-delay: {{ $position['delay'] }}s; animation-duration: {{ $position['duration'] }}s;"></span>
    @endforeach
</section>

 </ul>
        </div>
    </div>
</section>


            <div class="flex flex-col gap-y-8 pb-8 pt-4 sm:p-0 md:pt-8">
            @if($flashsale->count() > 0)
            <div class="container">
              <div class="rounded-2xl bg-muted/50">
                <div class="px-4 pb-3 pt-4">
                    <h3 class="flex items-center space-x-4 text-foreground">
                         <div class="text-lg font-semibold uppercase leading-relaxed tracking-wider flex items-center">
                        <lottie-player 
                            src="https://lottie.host/72527c22-6566-4eda-b453-dc61dd77ef2b/rt3d8phYjG.json" 
                            speed="1" 
                            style="width: 25px; height: 30px;" 
                            loop 
                            autoplay 
                            direction="1" 
                            mode="normal">
                        </lottie-player>
                         FLASHSALE
                    </div>
                           <div class="flex items-center gap-1 text-sm capitalize">
                                    <div class="fs-countdown ml-3">
                                        <div class="time" id="hours"></div>
                                        <div class="separator">:</div>
                                        <div class="time" id="minutes"></div>
                                        <div class="separator">:</div>
                                        <div class="time" id="seconds"></div>
                              </div>
                        </div>
                    </h3>
                    <p class="pl-6 text-xs text-foreground">Pesan sekarang! Persediaan terbatas.</p>
                </div>
                    <div class="relative flex h-full w-full flex-col items-center justify-center overflow-hidden pb-2 pt-1">
                        <div
                            class="group flex overflow-hidden p-2 [--gap:1rem] [gap:var(--gap)] flex-row container [--duration:20s]">
                            <div data-run-marquee="true" data-run-marquee-vertical="false" class="flex shrink-0 justify-around [gap:var(--gap)] data-[run-marquee-vertical=true]:animate-marquee-vertical data-[run-marquee=true]:animate-marquee data-[run-marquee]:flex-row data-[run-marquee-vertical=true]:flex-col group-hover:[animation-play-state:paused]">
                                <div class=" flex">
                                    <div
                                        class="assdafsdvsvasgdsgsdgwgreragwgwrgeargwrgergegsvdsDVSVcsdvdszvsbwtergerg43t34f34343ff34g34gG2">
                                        <div id="special_deals">
                                            <div class="list swiper-wrapper marquee-content">
                                                @for ($i = 0; $i < $flashsale->count(); $i++)
                                                    @foreach ($flashsale as $fs)
                                                        @php
                                                            $discount = round(
                                                                (($fs->harga - $fs->harga_flash_sale) / $fs->harga) *
                                                                    100,
                                                            );
                                                        @endphp
                                                        <a class="swiper-slide-link"
                                                            href="{{ url('/id') }}/{{ $fs->kode_game }}">
                                                            <div class="item relative" data-item-theme="0722">
                                                                <div class="popular-tag-container">
                                                                    <div class="popular-tag-content">
                                                                        <div class="rate">{{ $fs->kategori->nama }}</div>
                                                                    </div>
                                                                    <div class="popular-tag-overlay"></div>
                                                                </div>
                                                                <img alt=""
                                                                    class="flash-sale-img lazyloaded rounded-lg"
                                                                    src="{{ asset($fs->gmr_thumb) }}" />
                                                                <div class="T truncatee">
                                                                    <h2 class="sku text-white text-center">
                                                                        <figcaption
                                                                            class="text-sm font-medium text-foreground">
                                                                            {{ $fs->judul_flash_sale }}</figcaption>
                                                                    </h2>
                                                                    @php
                                                                        $total_stok = 100; // Asumsi total stok awal
                                                                        $progress =
                                                                            ($fs->sisa_stok / $total_stok) * 100;
                                                                    @endphp
                                                                    <div class="bar">
                                                                        <div class="progress"
                                                                            style="width: 100%; background-color: #e7e6e63f ">
                                                                            <div class="progress-bar"
                                                                                style="width: {{ $progress }}%; background-color: var(--warna_3); height: 100%;">
                                                                            </div>
                                                                        </div>

                                                                        <span class="progress-text">Tersisa:
                                                                            {{ $fs->sisa_stok }}</span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="promo">
                                                                <div class="rate">Rp.
                                                                    {{ number_format($fs->harga_flash_sale, 0, '.', ',') }}
                                                                </div>
                                                                <div class="price">
                                                                    <b><del class="red-line-through">Rp.
                                                                            {{ number_format($fs->harga, 0, '.', ',') }}</del></b>
                                                                    <figcaption class="text-sm font-bold">HEMAT Rp
                                                                        {{ number_format($fs->harga - $fs->harga_flash_sale, 0, '.', ',') }}
                                                                    </figcaption>
                                                                </div>
                                                            </div>
                                                        </a>
                                                    @endforeach
                                                @endfor
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
@endif  
          <div id="content-melpa"  class="container ">   
          <div class="mb-5">
              <h3 class="text-lg font-semibold leading-relaxed tracking-wider flex">
             <lottie-player 
            src="https://lottie.host/105ce5c3-7e93-4dbc-bfc8-6e4c816320e5/8kDtYqEr0W.json" 
            speed="1" 
            style="width: 25px; height: 25px;" 
            loop 
            autoplay 
            direction="1" 
            mode="normal">
        </lottie-player> POPULER!
        </h3>
         <p class="pl-6 text-xs">Beberapa produk yang paling populer saat ini.</p>
      </div>
            <div class="grid grid-cols-2 gap-3 md:gap-4 lg:grid-cols-3 mt-3">            
          @foreach($kategori as $category)
                @if($category->tipe == "populer")
                @for ($i = 0; $i < 0.01; $i+= 0.1)                
                <a href="{{url('/id')}}/{{$category->kode}}" class="melpaSlideUp" style="animation-delay: {{$i}}s;">
                        <div class="bg-title-product flex items-center gap-x-1.5 rounded-2xl p-1.5 duration-300 ease-in-out hover:shadow-2xl hover:ring-2 hover:ring-primary-500 hover:ring-offset-2 hover:ring-offset-murky-800 md:gap-x-3 md:rounded-2xl md:p-3">                            
                                    <img src="{{asset($category->thumbnail)}}" class="aspect-square h-14 w-14 rounded-lg !object-cover !object-center ring-1 ring-murky-600 md:h-20 md:w-20 md:rounded-xl" alt="{{$category->nama}}" />
                            <div
                                class="relative flex w-full flex-col">
                                <h2 class="w-[100px] truncate text-xxs font-semibold sm:w-[200px] md:w-[275px] md:text-base"> {{$category->nama}} </h2>
                                <p class="text-xxs md:text-sm">{{$category->sub_nama}}</p>
                        </div>
                </div>
                </a>
             @endfor
            @endif
        @endforeach
       
       </div>
     </div>

                 <section id="mobile-game" class="relative w-full overflow-hidden pb-6 md:pb-8 lg:pb-10 bg-secondary-950 ">
 
                    <div class="container mx-auto">
                        
                        <div class="flex items-center gap-2">
                    <div class="block lg:hidden"><button id="scrollLeft" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-xs font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-card text-primary-foreground hover:bg-primary/90 h-9 w-9"
                            type="button"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-left h-4 w-4"><path d="m15 18-6-6 6-6"></path></svg></button></div>
                    <div
                        class="tabs-container flex overflow-x-auto scroll-smooth">                        @foreach ($categoryTypes as $type)
                            <button
                                class="tab-button whitespace-nowrap melpazoom rounded-xl border border-secondary-600 px-4 py-2 text-sm text-text-color shadow-xl outline-none duration-300 hover:bg-secondary-500 focus:bg-secondary-500 focus-visible:bg-secondary-500 bg-transparent"
                                id="headlessui-tabs-tab-:{{ $type->slug }}:" role="tab" type="button" aria-selected="false"
                                tabindex="0" data-headlessui-state="false"
                                aria-controls="headlessui-tabs-panel-:{{ $type->slug }}:"
                                data-tabs-toggle="#headlessui-tabs-panel-{{ $type->slug }}">
                                {{ $type->name }}
                            </button>
                        @endforeach</div>
                <div
                    class="block lg:hidden"><button id="scrollRight" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-xs font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-card text-primary-foreground hover:bg-primary/90 h-9 w-9"
                        type="button"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-right h-4 w-4"><path d="m9 18 6-6-6-6"></path></svg></button></div>
            </div>
                    
                        <div class="my-8">
                                                          @include('template.id.components.dynamic_tabs')

                                
                                

                                
                                                                                    

                            
                        </div>
                    </div>
                    </div>
                    </div>                   
</section>

<!-- Article Recommendation Section -->
<section class="relative w-full overflow-hidden pb-16 pt-8 bg-transparent">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Section Header -->
        <div class="mb-10 flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
                <h3 class="text-2xl md:text-3xl font-black uppercase italic tracking-tighter text-white flex items-center gap-3">
                    <span class="text-primary-500 text-4xl">
                        <i class="fa fa-bolt"></i>
                    </span>
                    Berita & Artikel
                </h3>
                <p class="text-gray-400 text-sm mt-2 max-w-lg">
                    Dapatkan informasi terbaru seputar update game, promo eksklusif, dan tips & trik terbaik.
                </p>
            </div>
            <a href="{{ url('/artikel') }}" class="group flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-6 py-2.5 text-sm font-semibold text-white transition-all hover:bg-primary-600 hover:border-primary-500">
                Lihat Semua
                <i class="fa fa-arrow-right transition-transform group-hover:translate-x-1"></i>
            </a>
        </div>

        @if(isset($articles) && $articles->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach($articles as $article)
            <a href="{{ url('/artikel/' . $article->slug) }}" class="group relative block h-full">
                <!-- Card Container -->
                <div class="relative h-full overflow-hidden rounded-3xl bg-secondary-900 border border-white/5 transition-all duration-500 hover:-translate-y-2 hover:shadow-[0_0_40px_-10px_rgba(var(--warna_1_rgb),0.3)] hover:border-primary-500/30">
                    
                    <!-- Image Wrapper -->
                    <div class="aspect-[16/9] w-full overflow-hidden relative">
                        <!-- Badge -->
                        <div class="absolute top-4 left-4 z-20">
                            <span class="inline-flex items-center gap-1.5 rounded-lg bg-black/60 backdrop-blur-md px-3 py-1.5 text-xs font-bold text-white border border-white/10">
                                <i class="fa fa-calendar text-primary-400"></i>
                                {{ $article->created_at->format('d M Y') }}
                            </span>
                        </div>
                        
                        <!-- Image -->
                        <img src="{{ asset($article->thumbnail) }}" alt="{{ $article->title }}" class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-110 group-hover:rotate-1">
                        
                        <!-- Overlay Gradient -->
                        <div class="absolute inset-0 bg-gradient-to-b from-transparent via-secondary-950/20 to-secondary-950/90"></div>
                    </div>

                    <!-- Content -->
                    <div class="relative p-6 -mt-10 z-10">
                         <!-- Glass Effect Background for Text -->
                        <div class="absolute inset-0 bg-gradient-to-b from-transparent to-secondary-900/90 z-0"></div>
                        
                        <div class="relative z-10">
                             <!-- Tags (Optional) -->
                             <!-- <div class="mb-3 flex flex-wrap gap-2 text-xs font-semibold text-primary-400 uppercase tracking-wider">
                                <span>GAMES</span> • <span>UPDATE</span>
                            </div> -->

                            <h4 class="mb-3 text-xl font-bold leading-tight text-white transition-colors group-hover:text-primary-400 line-clamp-2">
                                {{ $article->title }}
                            </h4>
                            
                            <p class="mb-5 text-sm leading-relaxed text-gray-400 line-clamp-2">
                                {{ \Illuminate\Support\Str::limit(strip_tags($article->content), 120) }}
                            </p>

                            <div class="flex items-center gap-2 text-sm font-bold text-white border-t border-white/5 pt-4">
                                <span class="group-hover:text-primary-400 transition-colors">Baca Selengkapnya</span>
                                <i class="fa fa-arrow-right text-xs text-primary-500 transition-transform -rotate-45 group-hover:rotate-0 group-hover:translate-x-1"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
        @else
        <div class="flex flex-col items-center justify-center py-20 text-center rounded-3xl border border-dashed border-white/10 bg-white/5">
            <div class="mb-4 rounded-full bg-white/5 p-6">
                <i class="fa fa-newspaper-o text-4xl text-gray-600"></i>
            </div>
            <h4 class="text-lg font-bold text-white">Belum ada artikel terbaru</h4>
            <p class="text-sm text-gray-500 mt-1">Nantikan update menarik dari kami segera.</p>
        </div>
        @endif
    </div>
</section>

<style>
            .bg-black\/80 {
                background-color: #000000cc;
            }

            .bg-accent {
                background-color: hsl(0deg 0% 26.44%);
            }

            .transition-all {
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }

            .scale-95 {
                transform: scale(0.95);
            }

            .scale-100 {
                transform: scale(1);
            }
        </style>       
<div id="popupp" x-data="{ open: false, dontShowAgain: localStorage.getItem('dontShowPopup') === 'true' }" x-init="if (!dontShowAgain) { setTimeout(() => open = true, 100) }" @keydown.escape.window="open = false"
        @click.outside="open = false" class="relative z-50 font-sans" x-cloak>

        <div class="fixed inset-0 bg-black/80 transition-opacity" x-show="open"
            x-transition:enter="transition-opacity ease-out duration-500" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-in duration-500"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" x-cloak @click="open = false">
        </div>

        <div class="fixed inset-0 z-10 overflow-y-auto" x-show="open" x-cloak>
            <div class="flex min-h-full items-center justify-center p-4 text-center sm:items-center sm:p-0">
                <div @click.outside="open = false"
                    class="relative w-full transform overflow-hidden rounded-2xl bg-accent text-left shadow-xl transition-all sm:my-8 sm:max-w-3xl"
                    x-show="open" x-transition:enter="transition-all ease-out duration-500"
                    x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition-all ease-in duration-500"
                    x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">

                    <div class="absolute right-0 top-0 z-40 block pr-4 pt-4"><button type="button" @click="open = false"
                            class="rounded-md bg-murky-500 text-white ring-1 focus:ring-primary-400 focus:ring-offset-2 focus:ring-offset-murky-800"><span
                                class="sr-only">Close</span><svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12">
                                </path>
                            </svg></button></div>

                    <div class="w-full pb-4">
                        <div><img alt="" fetchpriority="high" width="0" height="0" decoding="async"
                                class="object-center" sizes="100vw"
                                srcset="{{ isset($popup->path) ? asset($popup->path) : '' }}"
                                src="{{ isset($popup->path) ? asset($popup->path) : '' }}"
                                style="color: transparent; width: 100%; height: auto;" /></div>

                        <div class="relative flex flex-col items-center pt-4 text-foreground">
                            <div class="flex w-full items-center justify-center px-4">
                                <div class="col-span-2 -mt-3 flex w-full flex-col items-center justify-center !text-xxs">
                                    <h2 class="max-w-xl pt-1 text-center text-sm font-semibold">PENGUMUMAN</h2>
                                </div>
                            </div>
                            
                            
                            <!-- Gunakan x-html untuk menampilkan HTML mentah dari deskripsi -->
                            <div class="prose prose-sm px-4 pb-4 text-xs text-white">
                                <p class="text-center" x-html="{!! isset($popup->deskripsi) ? $popup->deskripsi : 'Selamat datang di ' . htmlspecialchars($config->judul_web , ENT_QUOTES, 'UTF-8') . ' Selamat berbelanja.' !!}"></p>
                            </div>

                            <div class="flex w-full items-center justify-start px-4 pb-2">
                                <div class="flex items-center">
                                    <input type="checkbox" id="dontShowPopup"
                                        class="h-4 w-4 cursor-pointer rounded border bg-background text-primary focus:ring-primary focus:ring-offset-background"
                                        x-model="dontShowAgain"
                                        @change="localStorage.setItem('dontShowPopup', dontShowAgain ? 'true' : 'false'); if(dontShowAgain) { open = false }">
                                    <label
                                        class="block text-xs font-medium text-foreground ml-3 block select-none text-sm text-foreground !text-xxs !ml-2">Jangan Tampilkan Lagi</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
        <script src="{{ asset('/assets/js/kbwdiasuwasdw.js') }}"></script>

@include('../footer')
@push('custom_script')
<script>
function updateTimer() {
@foreach($flashsale as $fs)@php
$expiredFlashSale = new DateTime($fs->expired_flash_sale);
$formattedDate = $expiredFlashSale->format('Y-m-d H:i:s');
@endphp
var countDownDate=new Date("{{ $formattedDate }}").getTime(),x=setInterval(function(){var t=new Date().getTime(),e=countDownDate-t;e>0?(document.getElementById("hours").textContent=Math.floor(e%864e5/36e5).toString().padStart(2,"0"),document.getElementById("minutes").textContent=Math.floor(e%36e5/6e4).toString().padStart(2,"0"),document.getElementById("seconds").textContent=Math.floor(e%6e4/1e3).toString().padStart(2,"0")):(clearInterval(x),document.getElementById("hours").textContent="00",document.getElementById("minutes").textContent="00",document.getElementById("seconds").textContent="00",document.getElementById("expired_time_flash_sale").textContent="Waktu sudah habis!")},1e3);
@endforeach
}document.addEventListener("DOMContentLoaded", function() {updateTimer();});
</script>

 
@endpush




@endsection
