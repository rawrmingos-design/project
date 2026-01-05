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
                                    <img src="{{asset('assets/'.$category->thumbnail)}}" class="aspect-square h-14 w-14 rounded-lg !object-cover !object-center ring-1 ring-murky-600 md:h-20 md:w-20 md:rounded-xl" alt="{{$category->nama}}" />
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
                        class="tabs-container flex overflow-x-auto scroll-smooth"><button class="tab-button whitespace-nowrap melpazoom rounded-xl border border-secondary-600 px-4 py-2 text-sm text-text-color shadow-xl outline-none duration-300 hover:bg-secondary-500 focus:bg-secondary-500 focus-visible:bg-secondary-500 bg-transparent"
                            id="headlessui-tabs-tab-:topup:" role="tab" type="button" aria-selected="false" tabindex="0" data-headlessui-state="false" aria-controls="headlessui-tabs-panel-:topup:" data-tabs-toggle="#headlessui-tabs-panel-topup"> 🎮Top Up Games </button>
                        <button
                            class="tab-button whitespace-nowrap melpazoom rounded-xl border border-secondary-600 px-4 py-2 text-sm text-text-color shadow-xl outline-none duration-300 hover:bg-secondary-500 focus:bg-secondary-500 focus-visible:bg-secondary-500 bg-transparent"
                            id="headlessui-tabs-tab-:mobilelegends:" role="tab" type="button" aria-selected="false" tabindex="0" data-headlessui-state="false" aria-controls="headlessui-tabs-panel-:mobilelegends:" data-tabs-toggle="#headlessui-tabs-panel-mobilelegends">
                        ✨Specialist Mobile Legends </button><button class="tab-button whitespace-nowrap melpazoom rounded-xl border border-secondary-600 px-4 py-2 text-sm text-text-color shadow-xl outline-none duration-300 hover:bg-secondary-500 focus:bg-secondary-500 focus-visible:bg-secondary-500 bg-transparent"
                                id="headlessui-tabs-tab-:app:" role="tab" type="button" aria-selected="false" tabindex="0" data-headlessui-state="false" aria-controls="headlessui-tabs-panel-:app:" data-tabs-toggle="#headlessui-tabs-panel-app"> 📲App Premium </button>
                            <button
                                class="tab-button whitespace-nowrap melpazoom rounded-xl border border-secondary-600 px-4 py-2 text-sm text-text-color shadow-xl outline-none duration-300 hover:bg-secondary-500 focus:bg-secondary-500 focus-visible:bg-secondary-500 bg-transparent"
                                id="headlessui-tabs-tab-:pulsa:" role="tab" type="button" aria-selected="false" tabindex="0" data-headlessui-state="false" aria-controls="headlessui-tabs-panel-:pulsa:" data-tabs-toggle="#headlessui-tabs-panel-pulsa"> 📞Pulsa & Data </button><button class="tab-button whitespace-nowrap melpazoom rounded-xl border border-secondary-600 px-4 py-2 text-sm text-text-color shadow-xl outline-none duration-300 hover:bg-secondary-500 focus:bg-secondary-500 focus-visible:bg-secondary-500 bg-transparent"
                                    id="headlessui-tabs-tab-:voucher:" role="tab" type="button" aria-selected="false" tabindex="0" data-headlessui-state="false" aria-controls="headlessui-tabs-panel-:voucher:" data-tabs-toggle="#headlessui-tabs-panel-voucher"> 🏷Voucher </button></div>
                <div
                    class="block lg:hidden"><button id="scrollRight" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-xs font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-card text-primary-foreground hover:bg-primary/90 h-9 w-9"
                        type="button"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-right h-4 w-4"><path d="m9 18 6-6-6-6"></path></svg></button></div>
            </div>
                    
                        <div class="my-8">
                           
                               <div id="headlessui-tabs-panel-topup" role="tabpanel" aria-labelledby="headlessui-tabs-tab-topup" data-tabs-content style="display: none;">
 <div class="grid grid-cols-3 gap-4 sm:grid-cols-4 sm:gap-x-6 sm:gap-y-8 lg:grid-cols-5 xl:grid-cols-6" id="game">
    @foreach($kategori as $category)
        @if($category->tipe == "game" || $category->tipe == "populer")
        <a href="{{ url('/id/' . $category->kode) }}" class="melpazoom">
                                        <div class="group category-item relative overflow-hidden rounded-xl bg-muted transition-transform duration-300 ease-in-out hover:shadow-2xl hover:ring-2 hover:ring-primary-500 hover:ring-offset-2 hover:ring-offset-murky-900"
                                            style="display: block;">
                                           <!-- event -->
                                            <div class="absolute -right-[5px] -top-[5px]" style="top:-1px;"><svg width="4062" height="550"
                                                    viewBox="0 0 4062 550" fill="none" xmlns="http://www.w3.org/2000/svg" class="h-6 w-auto">
                                                    <path
                                                        d="M150.875 35.2392C159.905 31.8379 169.379 29.7613 179.003 29.0741L582.631 0.254668C588.889 -0.192139 595.174 -0.0490795 601.405 0.681951L927.048 38.8876C934.028 39.7065 941.075 39.7874 948.071 39.1289L1341.58 2.09315C1359.05 0.44869 1376.65 3.43145 1392.6 10.7418L1472.23 47.2234C1487.84 54.3775 1505.03 57.3896 1522.15 55.9696L1999.28 16.3859C2005.6 15.8614 2011.96 15.9397 2018.27 16.6199L2486.82 67.1436L3006.12 77.5007C3011.2 77.6021 3016.29 77.3155 3021.33 76.6434L3479.17 15.5973H3697.05C3708.42 15.5973 3719.72 17.5396 3730.45 21.3403L3870.06 70.8146C3882.8 75.3268 3894.48 82.3722 3904.42 91.5256L4058.59 233.565C4064.13 238.668 4062.26 247.829 4055.16 250.345L3992.29 272.635C3991 273.093 3989.81 273.81 3988.81 274.744L3891.61 365.321C3890.55 366.306 3888.83 365.879 3888.35 364.514L3848.28 249.159C3847.32 246.395 3845.19 244.19 3842.47 243.126L3722.12 196.141C3709.71 191.294 3696.27 189.669 3683.06 191.415C3659.83 194.485 3639.16 207.711 3626.65 227.517L3512.2 408.643C3508.06 415.19 3498.37 414.752 3494.84 407.859L3441.93 304.511C3421.91 265.413 3365.59 266.722 3347.41 306.707C3346.15 309.489 3342.78 310.607 3340.1 309.134L3146.7 202.794C3124.09 190.364 3095.96 194.585 3078 213.104C3069.08 222.294 3063.51 234.206 3062.16 246.937L3047.35 386.528C3047.17 388.24 3045.06 388.938 3043.89 387.669L2931.54 265.188C2917.3 249.66 2898.51 239.029 2877.87 234.81L2748.34 208.339C2746.52 207.967 2744.63 208.11 2742.88 208.751L2714.72 219.107C2690 228.195 2669.86 246.668 2658.68 270.514L2585.63 426.341C2585.01 427.665 2583.75 428.58 2582.3 428.764C2561.06 431.458 2541.11 418 2535.66 397.297L2499.13 258.654C2498.33 255.606 2496.14 253.115 2493.22 251.932L2250.51 153.663C2210.71 137.55 2165.07 148.64 2137.1 181.219L2095.82 229.302C2093.7 231.782 2090.43 232.981 2087.2 232.469C2051.75 226.846 2016.19 241.867 1995.5 271.201L1801.84 545.772C1798.02 551.194 1790.07 551.456 1785.9 546.298L1680.38 415.87C1665.27 397.203 1643.96 384.593 1620.33 380.345L1423.48 344.962C1421.42 344.592 1419.53 343.584 1418.07 342.082L1318.59 239.525C1292.01 212.126 1252.16 202.247 1215.86 214.061L1114.39 247.09C1101.39 251.318 1087.17 249.149 1076.03 241.24C1066.2 234.26 1053.9 231.709 1042.1 234.203L1030.35 236.689C1016.16 239.69 1001.38 238.246 988.041 232.554L837.043 168.14C831.144 165.624 824.435 169.231 823.282 175.539L782.203 400.221C780.248 410.911 765.047 411.25 762.619 400.657L725.369 238.19C713.437 186.146 662.723 152.657 610.174 162.12L548.549 173.219C498 182.322 462.568 228.239 466.578 279.444L467.879 296.053C468.747 307.129 453.609 311.149 448.866 301.102L406.21 210.735C392.214 181.084 364.237 160.467 331.771 155.88L301.049 151.539C254.64 144.981 214.228 183.369 218.393 230.053C218.93 236.071 212.321 240.084 207.229 236.831L4.61844 107.418C-2.32095 102.985 -1.22927 92.5346 6.47634 89.632L150.875 35.2392Z"
                                                        fill="#98C4E7"></path>
                                                    <path
                                                        d="M150.87 35.2565C159.903 31.8526 169.381 29.7744 179.009 29.0866L582.627 0.255376C588.887 -0.191815 595.176 -0.0486358 601.409 0.683022L927.044 38.9042C934.027 39.7238 941.076 39.8047 948.076 39.1457L1341.57 2.09539C1359.05 0.449569 1376.65 3.43463 1392.61 10.7504L1472.22 47.2406C1487.84 54.4 1505.04 57.4144 1522.16 55.9933L1999.27 16.3937C2005.6 15.8687 2011.96 15.9471 2018.27 16.6279L2486.82 67.173L3006.11 77.5345C3011.2 77.636 3016.29 77.3491 3021.33 76.6764L3479.17 15.6044H3697.04C3708.42 15.6044 3719.72 17.5482 3730.45 21.3519L3870.06 70.8445C3882.79 75.3593 3894.48 82.4082 3904.42 91.5655L4058.6 233.673C4064.14 238.776 4062.26 247.935 4055.17 250.451L3992.92 272.527C3991.23 273.129 3989.4 273.262 3987.63 272.911L3882.38 251.989C3859.37 247.415 3835.49 251.076 3814.9 262.33L3753.55 295.877C3748.49 298.645 3742.15 296.569 3739.7 291.345L3726.3 262.741C3706.36 220.177 3659.48 197.243 3613.64 207.633L3576.55 216.04C3565.35 218.579 3553.8 219.169 3542.4 217.787L3311.93 189.836H3048.61C3026.04 189.836 3004.13 197.47 2986.45 211.496L2853.24 317.183C2849.38 320.249 2843.85 320.038 2840.23 316.686L2806.53 285.477C2773.35 254.749 2723.67 250.116 2685.38 274.177L2675.13 280.615C2672.76 282.109 2669.86 282.534 2667.15 281.787L2576.58 256.776C2559.13 251.959 2540.71 251.966 2523.27 256.797L2466.01 272.654C2464.14 273.172 2462.17 273.135 2460.32 272.549L2415.16 258.218C2388.44 249.738 2359.38 252.865 2335.08 266.836L2294.8 289.991C2291.6 291.827 2287.65 291.758 2284.53 289.81L2082 163.7C2081.57 163.435 2081.19 163.11 2080.86 162.734C2060.84 139.977 2025.56 139.458 2004.89 161.616L1813.74 366.5C1809.38 371.167 1801.83 370.615 1798.2 365.364L1746.85 291.073C1705.5 231.24 1616.13 234.168 1578.78 296.58L1563.03 322.9C1560.07 327.846 1553.55 329.272 1548.8 326.013L1372.84 205.433C1351.66 190.921 1325.72 185.109 1300.37 189.201L1162.44 211.471C1160.84 211.73 1159.19 211.593 1157.65 211.073L967.643 146.926C948.061 140.315 926.912 139.934 907.105 145.835L805.731 176.036C790.743 180.501 774.917 181.388 759.524 178.627L704.022 168.672C684.56 165.181 664.493 167.947 646.701 176.573L632.546 183.436C622.662 188.228 611.446 189.526 600.729 187.117L565.288 179.15C553.119 176.415 541.553 185.67 541.553 198.143C541.553 210.258 530.605 219.43 518.677 217.308L164.491 154.289L14.8691 105.535C5.88452 102.608 5.5984 90.002 14.4409 86.6697L150.87 35.2565Z"
                                                        fill="#D1E6F7"></path>
                                                    <path
                                                        d="M150.884 35.2036C159.908 31.8077 169.375 29.7346 178.992 29.0485L582.639 0.253482C588.891 -0.192527 595.172 -0.049723 601.397 0.680008L927.058 38.8534C934.031 39.6709 941.071 39.7516 948.062 39.0943L1341.6 2.08881C1359.06 0.447158 1376.64 3.42514 1392.59 10.724L1472.24 47.1882C1487.84 54.3311 1505.02 57.3384 1522.13 55.9209L1999.29 16.3702C2005.6 15.8466 2011.95 15.9248 2018.26 16.6037L2486.82 67.083L3006.12 77.4309C3011.2 77.5322 3016.28 77.246 3021.32 76.5751L3479.17 15.583H3697.06C3708.43 15.583 3719.72 17.522 3730.43 21.3165L3870.07 70.7528C3882.8 75.2596 3894.48 82.2977 3904.42 91.4429L4038.82 215.155C4046.63 222.342 4039.34 235.141 4029.17 232.09L3703.98 134.474C3689.95 130.265 3675.18 129.186 3660.69 131.314L3456.39 161.33C3448.47 162.494 3440.43 162.702 3432.45 161.948L2860.04 107.86C2851.07 107.013 2842.03 107.381 2833.16 108.955L2258.88 210.876C2256.33 211.33 2253.7 210.772 2251.54 209.322L2087.5 98.7436C2067.28 85.1087 2042.76 79.3374 2018.58 82.5159L1338.5 171.895C1329.97 173.016 1321.33 173.032 1312.8 171.943L872.519 115.747C866.45 114.973 860.323 114.757 854.215 115.102L183.23 153.024C170.845 153.724 158.438 152.114 146.643 148.275L14.9086 105.407C5.91892 102.482 5.6329 89.8686 14.4808 86.5388L150.884 35.2036Z"
                                                        fill="white"></path>
                                                </svg></div>
                                            <!-- end event -->
                                           <div class="w-full aspect-square"><img alt="{{ $category->nama }}" fetchpriority="high" decoding="async" data-nimg="1"
                                                    class="h-full w-full object-cover object-center"
                                                    srcset="{{ asset('assets/' . $category->thumbnail) }}"
                                                    src="{{ asset('assets/' . $category->thumbnail) }}" style="color: transparent;"></div>
                                            <div class="bg-weji neverzoom py-2">
                                                <div class="flex flex-col px-3 py-1">
                                                    <h2 class="truncate text-sm font-semibold text-foreground sm:text-base">{{ $category->nama }}</h2>
                                                    <p class="truncate text-xxs text-foreground sm:text-xs">{{ $category->sub_nama }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </a>                                            
        @endif
    @endforeach
</div>

<div class="text-center mt-3" id="buttonContainer2">
                        <div class="justify-content-center"><button class="inline-flex items-center justify-center melpazoom rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white duration-300 hover:bg-murky-800/75 disabled:cursor-not-allowed disabled:opacity-75 lg:border lg:border-murky-800/75 lg:bg-transparent lg:hover:bg-murky-800"
                                id="showAllButton2" aria-label="Tampilkan Lainnya" type="button">Tampilkan Lainnya...</button></div>
                    </div>
</div>

<div id="headlessui-tabs-panel-mobilelegends" role="tabpanel" aria-labelledby="headlessui-tabs-tab-mobilelegends" data-tabs-content style="display: none;">
                                    <div class="grid grid-cols-3 gap-4 sm:grid-cols-4 sm:gap-x-6 sm:gap-y-8 lg:grid-cols-5 xl:grid-cols-6 " >
                                         @foreach($kategori as $category)
                                       @if($category->tipe == "giftskin" || $category->tipe == "joki" || $category->tipe == "jokigendong" || $category->tipe == "vilogml")
                                        <a href="{{ url('/id/' . $category->kode) }}" class="melpazoom">
                                         <div class="group category-item relative transform overflow-hidden rounded-xl bg-muted duration-300 ease-in-out hover:rotate-3 hover:shadow-2xl hover:ring-2 hover:ring-primary-500 hover:ring-offset-2 hover:ring-offset-background" >                                            
                                            <div class="w-full aspect-square"><img alt="{{ $category->nama }}" fetchpriority="high" decoding="async" data-nimg="1"
                                                    class="h-full w-full object-cover object-center"
                                                    srcset="{{ asset('assets/' . $category->thumbnail) }}"
                                                    src="{{ asset('assets/' . $category->thumbnail) }}" style="color: transparent;"></div>
                                            <div class="bg-weji neverzoom py-2">
                                                <div class="flex flex-col px-3 py-1">
                                                    <h2 class="truncate text-sm font-semibold text-foreground sm:text-base">{{ $category->nama }}</h2>
                                                    <p class="truncate text-xxs text-foreground sm:text-xs">{{ $category->sub_nama }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </a>                                   
          @endif
        @endforeach
                                    </div>
                                </div>                                
                                
                                <div id="headlessui-tabs-panel-emoney" role="tabpanel" aria-labelledby="headlessui-tabs-tab-emoney" data-tabs-content style="display: none;">
                                    <div class="grid grid-cols-3 gap-4 sm:grid-cols-4 sm:gap-x-6 sm:gap-y-8 lg:grid-cols-5 xl:grid-cols-6 " >
                                         @foreach($kategori as $category)
                                      @if($category->tipe == "emoney")
                                      <a href="{{ url('/id/' . $category->kode) }}" class="melpazoom">
                                        <a href="{{ url('/id/' . $category->kode) }}" class="melpazoom">
                                         <div class="group category-item relative transform overflow-hidden rounded-xl bg-muted duration-300 ease-in-out hover:rotate-3 hover:shadow-2xl hover:ring-2 hover:ring-primary-500 hover:ring-offset-2 hover:ring-offset-background" >                                            
                                            <div class="w-full aspect-square"><img alt="{{ $category->nama }}" fetchpriority="high" decoding="async" data-nimg="1"
                                                    class="h-full w-full object-cover object-center"
                                                    srcset="{{ asset('assets/' . $category->thumbnail) }}"
                                                    src="{{ asset('assets/' . $category->thumbnail) }}" style="color: transparent;"></div>
                                            <div class="bg-weji neverzoom py-2">
                                                <div class="flex flex-col px-3 py-1">
                                                    <h2 class="truncate text-sm font-semibold text-foreground sm:text-base">{{ $category->nama }}</h2>
                                                    <p class="truncate text-xxs text-foreground sm:text-xs">{{ $category->sub_nama }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
          @endif
        @endforeach
                                    </div>
                                </div>
                                <div id="headlessui-tabs-panel-app" role="tabpanel" aria-labelledby="headlessui-tabs-tab-app" data-tabs-content style="display: none;">
                                    <div class="grid grid-cols-3 gap-4 sm:grid-cols-4 sm:gap-x-6 sm:gap-y-8 lg:grid-cols-5 xl:grid-cols-6 " >
                                         @foreach($kategori as $category)
                                      @if($category->tipe == "app")
                                        <a href="{{ url('/id/' . $category->kode) }}" class="melpazoom">
                                        <div class="group category-item relative overflow-hidden rounded-xl bg-muted transition-transform duration-300 ease-in-out hover:shadow-2xl hover:ring-2 hover:ring-primary-500 hover:ring-offset-2 hover:ring-offset-murky-900"
                                            style="display: block;">
                                           <!-- event -->
                                            <div class="absolute -right-[5px] -top-[5px]" style="top:-1px;"><svg width="4062" height="550"
                                                    viewBox="0 0 4062 550" fill="none" xmlns="http://www.w3.org/2000/svg" class="h-6 w-auto">
                                                    <path
                                                        d="M150.875 35.2392C159.905 31.8379 169.379 29.7613 179.003 29.0741L582.631 0.254668C588.889 -0.192139 595.174 -0.0490795 601.405 0.681951L927.048 38.8876C934.028 39.7065 941.075 39.7874 948.071 39.1289L1341.58 2.09315C1359.05 0.44869 1376.65 3.43145 1392.6 10.7418L1472.23 47.2234C1487.84 54.3775 1505.03 57.3896 1522.15 55.9696L1999.28 16.3859C2005.6 15.8614 2011.96 15.9397 2018.27 16.6199L2486.82 67.1436L3006.12 77.5007C3011.2 77.6021 3016.29 77.3155 3021.33 76.6434L3479.17 15.5973H3697.05C3708.42 15.5973 3719.72 17.5396 3730.45 21.3403L3870.06 70.8146C3882.8 75.3268 3894.48 82.3722 3904.42 91.5256L4058.59 233.565C4064.13 238.668 4062.26 247.829 4055.16 250.345L3992.29 272.635C3991 273.093 3989.81 273.81 3988.81 274.744L3891.61 365.321C3890.55 366.306 3888.83 365.879 3888.35 364.514L3848.28 249.159C3847.32 246.395 3845.19 244.19 3842.47 243.126L3722.12 196.141C3709.71 191.294 3696.27 189.669 3683.06 191.415C3659.83 194.485 3639.16 207.711 3626.65 227.517L3512.2 408.643C3508.06 415.19 3498.37 414.752 3494.84 407.859L3441.93 304.511C3421.91 265.413 3365.59 266.722 3347.41 306.707C3346.15 309.489 3342.78 310.607 3340.1 309.134L3146.7 202.794C3124.09 190.364 3095.96 194.585 3078 213.104C3069.08 222.294 3063.51 234.206 3062.16 246.937L3047.35 386.528C3047.17 388.24 3045.06 388.938 3043.89 387.669L2931.54 265.188C2917.3 249.66 2898.51 239.029 2877.87 234.81L2748.34 208.339C2746.52 207.967 2744.63 208.11 2742.88 208.751L2714.72 219.107C2690 228.195 2669.86 246.668 2658.68 270.514L2585.63 426.341C2585.01 427.665 2583.75 428.58 2582.3 428.764C2561.06 431.458 2541.11 418 2535.66 397.297L2499.13 258.654C2498.33 255.606 2496.14 253.115 2493.22 251.932L2250.51 153.663C2210.71 137.55 2165.07 148.64 2137.1 181.219L2095.82 229.302C2093.7 231.782 2090.43 232.981 2087.2 232.469C2051.75 226.846 2016.19 241.867 1995.5 271.201L1801.84 545.772C1798.02 551.194 1790.07 551.456 1785.9 546.298L1680.38 415.87C1665.27 397.203 1643.96 384.593 1620.33 380.345L1423.48 344.962C1421.42 344.592 1419.53 343.584 1418.07 342.082L1318.59 239.525C1292.01 212.126 1252.16 202.247 1215.86 214.061L1114.39 247.09C1101.39 251.318 1087.17 249.149 1076.03 241.24C1066.2 234.26 1053.9 231.709 1042.1 234.203L1030.35 236.689C1016.16 239.69 1001.38 238.246 988.041 232.554L837.043 168.14C831.144 165.624 824.435 169.231 823.282 175.539L782.203 400.221C780.248 410.911 765.047 411.25 762.619 400.657L725.369 238.19C713.437 186.146 662.723 152.657 610.174 162.12L548.549 173.219C498 182.322 462.568 228.239 466.578 279.444L467.879 296.053C468.747 307.129 453.609 311.149 448.866 301.102L406.21 210.735C392.214 181.084 364.237 160.467 331.771 155.88L301.049 151.539C254.64 144.981 214.228 183.369 218.393 230.053C218.93 236.071 212.321 240.084 207.229 236.831L4.61844 107.418C-2.32095 102.985 -1.22927 92.5346 6.47634 89.632L150.875 35.2392Z"
                                                        fill="#98C4E7"></path>
                                                    <path
                                                        d="M150.87 35.2565C159.903 31.8526 169.381 29.7744 179.009 29.0866L582.627 0.255376C588.887 -0.191815 595.176 -0.0486358 601.409 0.683022L927.044 38.9042C934.027 39.7238 941.076 39.8047 948.076 39.1457L1341.57 2.09539C1359.05 0.449569 1376.65 3.43463 1392.61 10.7504L1472.22 47.2406C1487.84 54.4 1505.04 57.4144 1522.16 55.9933L1999.27 16.3937C2005.6 15.8687 2011.96 15.9471 2018.27 16.6279L2486.82 67.173L3006.11 77.5345C3011.2 77.636 3016.29 77.3491 3021.33 76.6764L3479.17 15.6044H3697.04C3708.42 15.6044 3719.72 17.5482 3730.45 21.3519L3870.06 70.8445C3882.79 75.3593 3894.48 82.4082 3904.42 91.5655L4058.6 233.673C4064.14 238.776 4062.26 247.935 4055.17 250.451L3992.92 272.527C3991.23 273.129 3989.4 273.262 3987.63 272.911L3882.38 251.989C3859.37 247.415 3835.49 251.076 3814.9 262.33L3753.55 295.877C3748.49 298.645 3742.15 296.569 3739.7 291.345L3726.3 262.741C3706.36 220.177 3659.48 197.243 3613.64 207.633L3576.55 216.04C3565.35 218.579 3553.8 219.169 3542.4 217.787L3311.93 189.836H3048.61C3026.04 189.836 3004.13 197.47 2986.45 211.496L2853.24 317.183C2849.38 320.249 2843.85 320.038 2840.23 316.686L2806.53 285.477C2773.35 254.749 2723.67 250.116 2685.38 274.177L2675.13 280.615C2672.76 282.109 2669.86 282.534 2667.15 281.787L2576.58 256.776C2559.13 251.959 2540.71 251.966 2523.27 256.797L2466.01 272.654C2464.14 273.172 2462.17 273.135 2460.32 272.549L2415.16 258.218C2388.44 249.738 2359.38 252.865 2335.08 266.836L2294.8 289.991C2291.6 291.827 2287.65 291.758 2284.53 289.81L2082 163.7C2081.57 163.435 2081.19 163.11 2080.86 162.734C2060.84 139.977 2025.56 139.458 2004.89 161.616L1813.74 366.5C1809.38 371.167 1801.83 370.615 1798.2 365.364L1746.85 291.073C1705.5 231.24 1616.13 234.168 1578.78 296.58L1563.03 322.9C1560.07 327.846 1553.55 329.272 1548.8 326.013L1372.84 205.433C1351.66 190.921 1325.72 185.109 1300.37 189.201L1162.44 211.471C1160.84 211.73 1159.19 211.593 1157.65 211.073L967.643 146.926C948.061 140.315 926.912 139.934 907.105 145.835L805.731 176.036C790.743 180.501 774.917 181.388 759.524 178.627L704.022 168.672C684.56 165.181 664.493 167.947 646.701 176.573L632.546 183.436C622.662 188.228 611.446 189.526 600.729 187.117L565.288 179.15C553.119 176.415 541.553 185.67 541.553 198.143C541.553 210.258 530.605 219.43 518.677 217.308L164.491 154.289L14.8691 105.535C5.88452 102.608 5.5984 90.002 14.4409 86.6697L150.87 35.2565Z"
                                                        fill="#D1E6F7"></path>
                                                    <path
                                                        d="M150.884 35.2036C159.908 31.8077 169.375 29.7346 178.992 29.0485L582.639 0.253482C588.891 -0.192527 595.172 -0.049723 601.397 0.680008L927.058 38.8534C934.031 39.6709 941.071 39.7516 948.062 39.0943L1341.6 2.08881C1359.06 0.447158 1376.64 3.42514 1392.59 10.724L1472.24 47.1882C1487.84 54.3311 1505.02 57.3384 1522.13 55.9209L1999.29 16.3702C2005.6 15.8466 2011.95 15.9248 2018.26 16.6037L2486.82 67.083L3006.12 77.4309C3011.2 77.5322 3016.28 77.246 3021.32 76.5751L3479.17 15.583H3697.06C3708.43 15.583 3719.72 17.522 3730.43 21.3165L3870.07 70.7528C3882.8 75.2596 3894.48 82.2977 3904.42 91.4429L4038.82 215.155C4046.63 222.342 4039.34 235.141 4029.17 232.09L3703.98 134.474C3689.95 130.265 3675.18 129.186 3660.69 131.314L3456.39 161.33C3448.47 162.494 3440.43 162.702 3432.45 161.948L2860.04 107.86C2851.07 107.013 2842.03 107.381 2833.16 108.955L2258.88 210.876C2256.33 211.33 2253.7 210.772 2251.54 209.322L2087.5 98.7436C2067.28 85.1087 2042.76 79.3374 2018.58 82.5159L1338.5 171.895C1329.97 173.016 1321.33 173.032 1312.8 171.943L872.519 115.747C866.45 114.973 860.323 114.757 854.215 115.102L183.23 153.024C170.845 153.724 158.438 152.114 146.643 148.275L14.9086 105.407C5.91892 102.482 5.6329 89.8686 14.4808 86.5388L150.884 35.2036Z"
                                                        fill="white"></path>
                                                </svg></div>
                                            <!-- end event -->
                                           <div class="w-full aspect-square"><img alt="{{ $category->nama }}" fetchpriority="high" decoding="async" data-nimg="1"
                                                    class="h-full w-full object-cover object-center"
                                                    srcset="{{ asset('assets/' . $category->thumbnail) }}"
                                                    src="{{ asset('assets/' . $category->thumbnail) }}" style="color: transparent;"></div>
                                            <div class="bg-weji neverzoom py-2">
                                                <div class="flex flex-col px-3 py-1">
                                                    <h2 class="truncate text-sm font-semibold text-foreground sm:text-base">{{ $category->nama }}</h2>
                                                    <p class="truncate text-xxs text-foreground sm:text-xs">{{ $category->sub_nama }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
          @endif
        @endforeach
                                    </div>
                                </div>                                
                                                                                    
                                <div id="headlessui-tabs-panel-pulsa" role="tabpanel" aria-labelledby="headlessui-tabs-tab-pulsa" data-tabs-content style="display: none;">
                                              <div class="grid grid-cols-3 gap-4 sm:grid-cols-4 sm:gap-x-6 sm:gap-y-8 lg:grid-cols-5 xl:grid-cols-6" >
                                         @foreach($kategori as $category)
            @if($category->tipe == "pulsa")
                                       <a href="{{ url('/id/' . $category->kode) }}" class="melpazoom">
                                         <div class="group category-item relative transform overflow-hidden rounded-xl bg-muted duration-300 ease-in-out hover:rotate-3 hover:shadow-2xl hover:ring-2 hover:ring-primary-500 hover:ring-offset-2 hover:ring-offset-background" >                                            
                                            <div class="w-full aspect-square"><img alt="{{ $category->nama }}" fetchpriority="high" decoding="async" data-nimg="1"
                                                    class="h-full w-full object-cover object-center"
                                                    srcset="{{ asset('assets/' . $category->thumbnail) }}"
                                                    src="{{ asset('assets/' . $category->thumbnail) }}" style="color: transparent;"></div>
                                            <div class="bg-weji neverzoom py-2">
                                                <div class="flex flex-col px-3 py-1">
                                                    <h2 class="truncate text-sm font-semibold text-foreground sm:text-base">{{ $category->nama }}</h2>
                                                    <p class="truncate text-xxs text-foreground sm:text-xs">{{ $category->sub_nama }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
          @endif
        @endforeach
                                    </div>
                                </div>

                                <div id="headlessui-tabs-panel-voucher" role="tabpanel" aria-labelledby="headlessui-tabs-tab-voucher" data-tabs-content style="display: none;">
                                              <div class="grid grid-cols-3 gap-4 sm:grid-cols-4 sm:gap-x-6 sm:gap-y-8 lg:grid-cols-5 xl:grid-cols-6">
                                         @foreach($kategori as $category)
            @if($category->tipe == "voucher")
                                        <a href="{{ url('/id/' . $category->kode) }}" class="melpazoom">
                                         <div class="group category-item relative transform overflow-hidden rounded-xl bg-muted duration-300 ease-in-out hover:rotate-3 hover:shadow-2xl hover:ring-2 hover:ring-primary-500 hover:ring-offset-2 hover:ring-offset-background" >                                            
                                            <div class="w-full aspect-square"><img alt="{{ $category->nama }}" fetchpriority="high" decoding="async" data-nimg="1"
                                                    class="h-full w-full object-cover object-center"
                                                    srcset="{{ $category->thumbnail }}"
                                                    src="{{ $category->thumbnail }}" style="color: transparent;"></div>
                                            <div class="bg-weji neverzoom py-2">
                                                <div class="flex flex-col px-3 py-1">
                                                    <h2 class="truncate text-sm font-semibold text-foreground sm:text-base">{{ $category->nama }}</h2>
                                                    <p class="truncate text-xxs text-foreground sm:text-xs">{{ $category->sub_nama }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
          @endif
        @endforeach
                                    </div>
                                </div>
                                <div id="headlessui-tabs-panel-entertaiment" role="tabpanel"  aria-labelledby="headlessui-tabs-tab-entertaiment" data-tabs-content style="display: none;">
                                    <div class="grid grid-cols-3 gap-4 sm:grid-cols-4 sm:gap-x-6 sm:gap-y-8 lg:grid-cols-5 xl:grid-cols-6" ></div>
                                </div>                            
                        </div>
                    </div>
                    </div>
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
