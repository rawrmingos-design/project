<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
   <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- SEO Meta Tags -->
    <meta name="theme-color" content="#575757">
    <meta property="og:title" content="{{ isset($title) ? $title : ($config ? $config->judul_web : '') }}">
    <meta property="og:type" content="website">
    <meta property="og:description" content="{{ isset($meta_description) ? $meta_description : ($config ? $config->deskripsi_web : '') }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ isset($thumbnail) ? $thumbnail : ($config ? $config->logo_favicon : '') }}">
    <meta name="title" content="{{ isset($title) ? $title : ($config ? $config->judul_web : '') }}">
    <meta name="keywords" content="{{ isset($keywords) ? $keywords : ($config ? $config->keywords : '') }}">
    <meta name="description" content="{{ isset($meta_description) ? $meta_description : ($config ? $config->deskripsi_web : '') }}">
    <meta name="author" content="{{ $config ? $config->judul_web : '' }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="canonical" href="{{url('')}}">
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="{{ asset($config ? $config->logo_favicon : 'assets/logo/favicon.webp') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset($config ? $config->logo_favicon : 'assets/logo/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset($config ? $config->logo_favicon : 'assets/logo/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset($config ? $config->logo_favicon : 'assets/logo/favicon-16x16.png') }}">
    <link rel="manifest" href="{{ asset($config ? $config->logo_favicon : 'assets/logo/site.webmanifest') }}">
    
    <!-- Livewire Styles -->
    @livewireStyles
    
    <!-- Title -->
    <title>{{ isset($title) ? $title : ($config ? $config->judul_web : '') }}</title>
    
    <!-- Schema Markup -->
    @if(isset($schema_markup) && $schema_markup)
        {!! $schema_markup !!}
    @endif

    <!-- Analytics & Tracking -->
    @if(isset($config))
        @if($config->google_tag_manager_id)
            <!-- Google Tag Manager -->
            <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
            new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
            j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
            'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
            })(window,document,'script','dataLayer','{{ $config->google_tag_manager_id }}');</script>
        @endif

        @if($config->google_analytics_id)
            <!-- Google Analytics 4 -->
            <script async src="https://www.googletagmanager.com/gtag/js?id={{ $config->google_analytics_id }}"></script>
            <script>
                window.dataLayer = window.dataLayer || [];
                function gtag(){dataLayer.push(arguments);}
                gtag('js', new Date());
                gtag('config', '{{ $config->google_analytics_id }}');
            </script>
        @endif

        @if($config->facebook_pixel_id)
            <!-- Facebook Pixel -->
            <script>
                !function(f,b,e,v,n,t,s)
                {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
                n.callMethod.apply(n,arguments):n.queue.push(arguments)};
                if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
                n.queue=[];t=b.createElement(e);t.async=!0;
                t.src=v;s=b.getElementsByTagName(e)[0];
                s.parentNode.insertBefore(t,s)}(window, document,'script',
                'https://connect.facebook.net/en_US/fbevents.js');
                fbq('init', '{{ $config->facebook_pixel_id }}');
                fbq('track', 'PageView');
            </script>
            <noscript><img height="1" width="1" style="display:none"
                src="https://www.facebook.com/tr?id={{ $config->facebook_pixel_id }}&ev=PageView&noscript=1"
            /></noscript>
        @endif
    @endif
    
    <!-- Stylesheets and Fonts -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <link rel="stylesheet" href="https://unpkg.com/placeholder-loading/dist/css/placeholder-loading.min.css">
   
     <style> 
           
    
    @import  url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;700&display=swap');
    :root {
        --warna_1: <?= $config->warna1; ?>;
        --warna_2: <?= $config->warna2; ?>;
        --warna_3: <?= $config->warna3; ?>;
        --warna_4: <?= $config->warna4; ?>;         
    } 
    .bg-weji { 
        --tw-bg-opacity: 1;
        background-color: var(--warna_4);
        --tw-text-opacity: 1;
        color: rgb(255 255 255/var(--tw-text-opacity));
        background-image: url(https://cdn.bangjeff.com/meta/background.png);
        background-repeat: repeat-x, no-repeat; 
        background-position: top; 
        background-size: clamp(20rem, 80em, 100%) auto, cover; 
    } 
        
   .prose :where(ol > li):not(:where([class~=not-prose] *))::marker {
    font-weight: 400;
    color: var(--warna_1) !important;
}
    </style>  
    <link rel="stylesheet" href="{{ asset('/assets/css/pjojikhhoyutyrtd.css') }}">
    <link rel="stylesheet" href="{{ asset('/assets/css/barrsopaosocas.css') }}">
    <link rel="stylesheet" href="{{ asset('/assets/css/owihdagowdhqo.css') }}">
    


      
    </head>
   
@yield('custom_style')

    <body class="bg-gradient-theme text-white antialiased" :class="{ 'overflow-hidden': isSearchModalOpen }" x-data="{ 'isSearchModalOpen': false }" x-on:keydown.escape="isSearchModalOpen=false">
    
    @if(isset($config) && $config->google_tag_manager_id)
        <!-- Google Tag Manager (noscript) -->
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $config->google_tag_manager_id }}"
        height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
        <!-- End Google Tag Manager (noscript) -->
    @endif
    <div class="relative z-50" role="dialog" tabindex="-1" x-show="isSearchModalOpen" x-on:click.away="isSearchModalOpen = false" x-cloak x-transition>
        <div class="fixed inset-0 z-50 overflow-hidden p-4 py-20 sm:py-20 sm:px-6 md:p-20">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-25 transition-opacity opacity-100" x-show="isSearchModalOpen" x-cloak x-on:click="isSearchModalOpen=false"></div>
            <div class="mx-auto max-w-2xl transform divide-y divide-gray-500 divide-opacity-10 overflow-hidden rounded-md bg-murky-700 bg-opacity-80 shadow-2xl ring-1 ring-black ring-opacity-5 backdrop-blur backdrop-filter transition-all opacity-100 scale-100"
                id="dialog-panel-:r4g:" data-state="open">
                <div class="relative"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="pointer-events-none absolute left-4 top-3.5 h-5 w-5 text-white text-opacity-40"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd"></path></svg>
                    <form><input class="h-12 w-full border-0 bg-transparent pl-11 pr-4 text-white focus:ring-0 sm:text-sm" placeholder="Cari Game, item, Voucher" id="searchProds" role="combobox" type="text" name="q" aria-expanded="false" aria-autocomplete="list"
                            value="" aria-controls="combobox-options-:r4i:" tabindex="0"></form>
                </div>
                <ul class="resultsearch max-h-80 scroll-py-2 divide-y divide-gray-500 divide-opacity-10 overflow-y-auto">
                    <div class="flex flex-col gap-2 items-center justify-center py-5" id="lottie-container"><span class="text-base text-center opacity-70 py-4">Belum Ada Produk Yang Dicari</span></div>
                </ul>
            </div>
        </div>
    </div>
    <main class="relative">
        <div id="app">
        @yield('content')
    </div>
  </main>
    
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>   
    {{-- Alpine.js already included by Livewire, no need to load separately --}}
    {{-- <script src="//cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js" defer nonce="YOUR_GENERATED_NONCE"></script> --}}
    {{-- <script src="//cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer nonce="YOUR_GENERATED_NONCE"></script> --}}
    <script>
        $.ajaxSetup({
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content")
            }
        });
    </script>
    <script>
        var delay = function() {
            var e = 0;
            return function(r, a) {
                clearTimeout(e), e = setTimeout(r, a)
            }
        }();
        $("#searchProds").keyup(function() {
            let e = $(this).val();
            e.length < 1 ? ($(".resultsearch").removeClass("show"), $(".resultsearch li").remove()) : delay(function() {
                $.ajax({
                    url: "{{ url('/id/cari/index') }}",
                    method: "POST",
                    data: {
                        data: e
                    },
                    beforeSend: function() {
                        $(".resultsearch li").remove()
                    },
                    success: function(e) {
                        $(".resultsearch").append(e), $(".resultsearch").addClass("show")
                    }
                })
            }, 100)
        });
    </script>
    <script>
        document.getElementById('searchProds').addEventListener('input', function() {
            var lottieContainer = document.getElementById('lottie-container');
            if (lottieContainer) {
                lottieContainer.style.display = 'none';
            }
        });
    </script>
    <script src="{{ asset('/assets/js/oo324ddod2323sd2dd.js') }}"></script>

    {{-- Livewire Scripts - Required for Livewire components to work --}}
    @livewireScripts

     @stack('custom_script')
    </body>
</html>
