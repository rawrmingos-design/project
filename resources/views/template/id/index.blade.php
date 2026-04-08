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
    
    @keyframes fire-flicker {
        0%, 100% { transform: scale(1) rotate(-8deg); }
        50% { transform: scale(1.15) rotate(8deg); text-shadow: 0 0 10px rgba(249, 115, 22, 0.6); }
    }
    .animate-fire {
        display: inline-block;
        animation: fire-flicker 0.8s ease-in-out infinite;
        transform-origin: bottom center;
    }
</style>
<section id="hero" class="relative mb-4 bg-transparent py-4 shadow-2xl ">
    <div class="hero-swiper swiper  container ">
        <div id="heroo"  class="swiper-wrapper ">
            @foreach($banner as $key => $data)
            <div class="swiper-slide">
                <x-optimized-image
                    :src="$data->path"
                    profile="banner"
                    alt="Banner {{ $key + 1 }}"
                    sizes="(min-width: 1024px) 1200px, 100vw"
                    loading="{{ $key === 0 ? 'eager' : 'lazy' }}"
                    fetchpriority="{{ $key === 0 ? 'high' : null }}"
                    class="w-full h-auto object-cover rounded-3xl"
                />
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


            <div class="flex flex-col pb-8 pt-4 sm:p-0 md:pt-8">
            {{-- Flashsale Section - Livewire Lazy Load --}}
            @livewire('home.flashsale', [], key('flashsale'))
          <div id="content-melpa"  class="container ">   
          <div class="mb-5">
              <h3 class="text-lg font-semibold leading-relaxed tracking-wider flex">
             {{-- Lottie player disabled due to CDN 403 errors --}}
             {{-- <lottie-player 
            src="https://lottie.host/105ce5c3-7e93-4dbc-bfc8-6e4c816320e5/8kDtYqEr0W.json" 
            speed="1" 
            style="width: 25px; height: 25px;" 
            loop 
            autoplay 
            direction="1" 
            mode="normal">
        </lottie-player> --}}
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-flame text-orange-500 mr-2 animate-fire inline-block" style="width: 20px; height: 20px;"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg> POPULER!
        </h3>
         <p class="pl-6 text-xs">Beberapa produk yang paling populer saat ini.</p>
      </div>
            <div class="grid grid-cols-2 gap-3 md:gap-4 lg:grid-cols-3 mt-3">            
          @foreach($kategori as $category)
                @if($category->tipe == "populer")
                @for ($i = 0; $i < 0.01; $i+= 0.1)                
                <a href="{{url('/id')}}/{{$category->kode}}" class="melpaSlideUp" style="animation-delay: {{$i}}s;">
                        <div class="bg-title-product flex items-center gap-x-1.5 rounded-2xl p-1.5 duration-300 ease-in-out hover:shadow-2xl hover:ring-2 hover:ring-primary-500 hover:ring-offset-2 hover:ring-offset-murky-800 md:gap-x-3 md:rounded-2xl md:p-3">                            
                                    <x-optimized-image :src="$category->thumbnail" profile="thumbnail" alt="{{ $category->nama }}" sizes="80px" width="80" height="80" class="aspect-square h-14 w-14 rounded-lg !object-cover !object-center ring-1 ring-murky-600 md:h-20 md:w-20 md:rounded-xl" />
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

                 {{-- Category Tabs Section - Livewire Lazy Load --}}
                 @livewire('home.category-tabs', [], key('category-tabs'))

{{-- Article Recommendation Section - Livewire Lazy Load --}}
@livewire('home.articles', [], key('articles'))

@php
    $homePopupEnabled = (bool) ($config->home_popup_enabled ?? true);
    $shouldRenderHomepagePopup = $homePopupEnabled && isset($popup) && $popup;
@endphp

@if($shouldRenderHomepagePopup)
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
<style>
/* Custom Style untuk Popup agar tidak bergantung full pada Tailwind */
.popup-backdrop {
    position: fixed;
    top: 0; right: 0; bottom: 0; left: 0;
    background-color: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(4px);
    transition: opacity 0.3s ease;
    z-index: 50;
}
.popup-container {
    position: fixed;
    top: 0; right: 0; bottom: 0; left: 0;
    z-index: 51;
    overflow-y: auto;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}
.popup-content {
    position: relative;
    width: 100%;
    max-width: 32rem; /* setara max-w-lg */
    background-color: #2a2a2a;
    border-radius: 1rem;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    overflow: hidden;
    transform: scale(0.95);
    opacity: 0;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.popup-show .popup-content {
    transform: scale(1);
    opacity: 1;
}
.popup-close-btn {
    position: absolute;
    top: 1rem; right: 1rem;
    z-index: 60;
    background-color: rgba(0, 0, 0, 0.5);
    color: white;
    border: none;
    border-radius: 50%;
    width: 2rem; height: 2rem;
    display: flex;
    align-items: center; justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
}
.popup-close-btn:hover {
    background-color: rgba(0, 0, 0, 0.8);
    transform: scale(1.1);
}
.popup-image-wrapper {
    position: relative;
    width: 100%;
    background-color: #000;
    display: flex;
    justify-content: center;
    align-items: center;
}
.popup-image {
    width: 100%;
    height: auto;
    max-height: 50vh;
    object-fit: contain;
}
.popup-image-fade {
    position: absolute;
    bottom: 0; left: 0; right: 0;
    height: 4rem;
    background: linear-gradient(to top, #2a2a2a, transparent);
    pointer-events: none;
}
.popup-body {
    padding: 1.5rem;
    color: #fff;
    text-align: center;
}
.popup-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background-color: rgba(var(--color-primary-rgb, 255, 255, 255), 0.1);
    border: 1px solid rgba(var(--color-primary-rgb, 255, 255, 255), 0.2);
    color: var(--color-primary, #fff);
    padding: 0.5rem 1rem;
    border-radius: 9999px;
    font-size: 0.875rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    margin-bottom: 1rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}
@keyframes gentlePulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.7; transform: scale(0.9); }
}
.icon-pulse {
    animation: gentlePulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
.popup-desc {
    font-size: 0.875rem;
    line-height: 1.5;
    color: #e5e5e5;
    margin-bottom: 1.5rem;
}
/* Membatasi ruang lingkup img didalam popup description apabila ada tag img dari CKeditor */
.popup-desc img {
    max-width: 100%;
    height: auto;
    border-radius: 0.5rem;
    margin: 0.5rem auto;
}
.popup-footer {
    border-top: 1px solid rgba(255, 255, 255, 0.05);
    padding-top: 1.25rem;
    display: flex;
    justify-content: center;
}
.custom-checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    cursor: pointer;
    background-color: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    transition: all 0.2s;
}
.custom-checkbox-label:hover {
    background-color: rgba(255, 255, 255, 0.1);
}
.custom-checkbox {
    appearance: none;
    width: 1.25rem; height: 1.25rem;
    background-color: rgba(0, 0, 0, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 0.25rem;
    position: relative;
    cursor: pointer;
    transition: all 0.2s;
}
.custom-checkbox:checked {
    background-color: var(--color-primary, #3b82f6);
    border-color: var(--color-primary, #3b82f6);
}
.custom-checkbox::after {
    content: '';
    position: absolute;
    top: 45%; left: 50%;
    width: 6px; height: 10px;
    border: solid white;
    border-width: 0 2px 2px 0;
    transform: translate(-50%, -50%) rotate(45deg) scale(0);
    transition: transform 0.2s;
}
.custom-checkbox:checked::after {
    transform: translate(-50%, -50%) rotate(45deg) scale(1);
}
.checkbox-text {
    font-size: 0.875rem;
    color: #a3a3a3;
    user-select: none;
}
.custom-checkbox-label:hover .checkbox-text {
    color: #e5e5e5;
}
</style>

<div id="popupp" x-data="{ open: false, dontShowAgain: localStorage.getItem('dontShowPopup') === 'true' }" 
     x-init="if (!dontShowAgain) { setTimeout(() => open = true, 500) }" 
     @keydown.escape.window="open = false"
     class="font-sans" x-cloak>

    <!-- Backdrop -->
    <div class="popup-backdrop" x-show="open" 
         x-transition:enter="transition-opacity ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" 
         x-transition:leave="transition-opacity ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
    </div>

    <!-- Container -->
    <div class="popup-container" x-show="open" @click.outside="open = false" x-bind:class="open ? 'popup-show' : ''">
        <div class="popup-content" @click.stop>
            
            <!-- Close Button -->
            <button type="button" @click="open = false" class="popup-close-btn">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width: 1rem; height: 1rem;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>

            <!-- Image Container -->
            @if(isset($popup->path) && $popup->path)
            <div class="popup-image-wrapper">
                <x-optimized-image :src="$popup->path" profile="popup" alt="Promo" sizes="(min-width: 768px) 720px, 100vw" loading="eager" fetchpriority="high" class="popup-image" />
                <div class="popup-image-fade"></div>
            </div>
            @else
            <!-- Spacer if no image -->
            <div style="height: 3rem; width: 100%;"></div>
            @endif

            <!-- Body -->
            <div class="popup-body">
                <!-- Header / Title -->
                <div style="display: flex; justify-content: center; margin-top: {{ (isset($popup->path) && $popup->path) ? '-2.5rem' : '0' }}; position: relative; z-index: 10;">
                    <div class="popup-badge text-primary border-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="icon-pulse" style="width: 1.25rem; height: 1.25rem;">
                            <path fill-rule="evenodd" d="M5.25 9a6.75 6.75 0 0 1 13.5 0v.75c0 2.123.8 4.057 2.118 5.52a.75.75 0 0 1-.297 1.206c-1.544.57-3.16.99-4.831 1.243a3.75 3.75 0 1 1-7.48 0 24.585 24.585 0 0 1-4.831-1.244.75.75 0 0 1-.298-1.205A8.217 8.217 0 0 0 5.25 9.75V9Zm4.502 8.9a2.25 2.25 0 1 0 4.496 0 25.057 25.057 0 0 1-4.496 0Z" clip-rule="evenodd" />
                        </svg>
                        <span>PENGUMUMAN</span>
                    </div>
                </div>
                
                <!-- Description -->
                <div class="popup-desc">
                    {!! isset($popup->deskripsi) ? $popup->deskripsi : 'Selamat datang di ' . htmlspecialchars($config->judul_web , ENT_QUOTES, 'UTF-8') . '. Selamat berbelanja.' !!}
                </div>

                <!-- Footer / Checkbox -->
                <div class="popup-footer">
                    <label for="dontShowPopup" class="custom-checkbox-label">
                        <input type="checkbox" id="dontShowPopup" class="custom-checkbox"
                            x-model="dontShowAgain"
                            @change="localStorage.setItem('dontShowPopup', dontShowAgain ? 'true' : 'false'); if(dontShowAgain) { open = false }">
                        <span class="checkbox-text">Jangan tampilkan info ini lagi</span>
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
        <script src="{{ asset('/assets/js/kbwdiasuwasdw.js') }}"></script>

@include('../footer')
@push('custom_script')
{{-- Flashsale countdown moved to Livewire component --}}

{{-- ===== Live Sales FOMO Toast (Home Page Only) ===== --}}
<style>
    .fomo-toast {
        position: fixed;
        bottom: 24px;
        right: 24px;
        z-index: 9999;
        width: 100%;
        max-width: 320px;
        transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .fomo-toast-hidden { transform: translateY(40px); opacity: 0; pointer-events: none; }
    .fomo-toast-visible { transform: translateY(0); opacity: 1; pointer-events: auto; }
    .fomo-toast-box {
        background-color: rgba(31, 41, 55, 0.95);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 16px;
        box-shadow: 0 20px 25px -5px rgba(0,0,0,0.5), 0 10px 10px -5px rgba(0,0,0,0.3);
        padding: 16px;
        display: flex;
        align-items: flex-start;
        gap: 12px;
        position: relative;
        overflow: hidden;
    }
    .fomo-shimmer {
        position: absolute;
        inset: 0;
        transform: translateX(-100%);
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.05), transparent);
        animation: fomo-shimmer-anim 3s infinite linear;
        pointer-events: none;
    }
    @keyframes fomo-shimmer-anim { 100% { transform: translateX(100%); } }
    .fomo-toast-img-wrapper { flex-shrink: 0; position: relative; }
    .fomo-toast-img {
        width: 48px; height: 48px;
        border-radius: 12px;
        object-fit: cover;
        border: 2px solid rgba(139, 92, 246, 0.5);
    }
    .fomo-toast-icon-wrapper {
        position: absolute; bottom: -4px; right: -4px;
        background-color: #22c55e;
        border-radius: 50%; padding: 2px;
        border: 2px solid #1f2937;
    }
    .fomo-toast-icon { width: 12px; height: 12px; color: white; }
    .fomo-toast-content { flex: 1; min-width: 0; padding-right: 12px; text-align: left; }
    .fomo-toast-subtitle { font-size: 11px; color: #9ca3af; margin: 0 0 2px; }
    .fomo-toast-title {
        font-size: 14px; font-weight: 700; color: #fff;
        line-height: 1.2; margin: 0 0 4px;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .fomo-toast-meta { display: flex; align-items: center; justify-content: space-between; margin-top: 4px; }
    .fomo-toast-name {
        font-size: 12px; font-weight: 600;
        color: var(--warna_1, #8b5cf6);
        margin: 0;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis; padding-right: 8px;
    }
    .fomo-toast-time { font-size: 10px; color: #6b7280; margin: 0; white-space: nowrap; }
    .fomo-toast-close {
        position: absolute; top: 12px; right: 12px;
        background: none; border: none; color: #9ca3af;
        cursor: pointer; padding: 4px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        transition: all 0.2s;
    }
    .fomo-toast-close:hover { color: #fff; background-color: rgba(255,255,255,0.1); }
    .fomo-toast-close svg { width: 16px; height: 16px; fill: none; stroke: currentColor; }
    @media (max-width: 640px) {
        .fomo-toast { bottom: 80px; right: 16px; width: calc(100% - 32px); }
    }
</style>

<div id="live-sales-toast" class="fomo-toast fomo-toast-hidden">
    <div class="fomo-toast-box">
        <div class="fomo-shimmer"></div>
        <div class="fomo-toast-img-wrapper">
            <img id="toast-image" src="" alt="Game" class="fomo-toast-img">
            <div class="fomo-toast-icon-wrapper">
                <svg class="fomo-toast-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
        </div>
        <div class="fomo-toast-content">
            <p class="fomo-toast-subtitle">Baru saja membeli</p>
            <p id="toast-item" class="fomo-toast-title"></p>
            <div class="fomo-toast-meta">
                <p id="toast-name" class="fomo-toast-name"></p>
                <p id="toast-time" class="fomo-toast-time"></p>
            </div>
        </div>
        <button onclick="hideLiveSalesToast()" class="fomo-toast-close">
            <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
    </div>
</div>

<script>
    (function() {
        let recentPurchases = [];
        let toastTimeout, hideTimeout;

        function fetchRecentPurchases() {
            fetch('/api/recent-purchases')
                .then(r => r.json())
                .then(data => {
                    if (data && data.length > 0) {
                        recentPurchases = data;
                        startToastCycle();
                    }
                })
                .catch(err => console.error('Toast fetch error:', err));
        }

        function showRandomToast() {
            if (!recentPurchases.length) return;
            const purchase = recentPurchases[Math.floor(Math.random() * recentPurchases.length)];
            const toast = document.getElementById('live-sales-toast');
            if (!toast) return;

            // Gunakan window.location.origin agar URL selalu benar di semua environment
            const imgUrl = purchase.image
                ? purchase.image
                : '{{ asset("assets/logo/favicon.webp") }}';

            document.getElementById('toast-image').src = imgUrl;
            document.getElementById('toast-item').innerText = purchase.item;
            document.getElementById('toast-name').innerText = purchase.name;
            document.getElementById('toast-time').innerText = purchase.time_ago;

            toast.classList.remove('fomo-toast-hidden');
            toast.classList.add('fomo-toast-visible');

            clearTimeout(hideTimeout);
            hideTimeout = setTimeout(() => {
                hideLiveSalesToast();
                clearTimeout(toastTimeout);
                toastTimeout = setTimeout(showRandomToast, 2000);
            }, 3500);
        }

        window.hideLiveSalesToast = function() {
            const toast = document.getElementById('live-sales-toast');
            if (toast) {
                toast.classList.remove('fomo-toast-visible');
                toast.classList.add('fomo-toast-hidden');
            }
        };

        function startToastCycle() {
            clearTimeout(toastTimeout);
            clearTimeout(hideTimeout);
            toastTimeout = setTimeout(showRandomToast, 2000);
        }

        document.addEventListener('DOMContentLoaded', () => {
            fetchRecentPurchases();
            setInterval(fetchRecentPurchases, 300000); // refresh setiap 5 menit
        });
    })();
</script>
{{-- ===== End Live Sales Toast ===== --}}

@endpush





@endsection
