<style>
 .bg-primary-400 { background-color: var(--warna_2); } /* sebelumnya var(--warna_3) */
 .rounded-\[9999px\], .rounded-full { border-radius: 9999px; } .w-1\/4 { width: 25%; } .h-1 { height: .25rem; } .bg-murky-9000 { --tw-bg-opacity: 1; background-color: #28282A; } .hover\:animate-bounce:hover { animation: bounce 1s; } .to-secondary { --tw-gradient-to: #3f3f3f; } .from-70\% { --tw-gradient-from-position: 70%; } .from-transparent { --tw-gradient-from: transparent var(--tw-gradient-from-position); --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to); } .bg-gradient-to-b { position: relative; background-image: linear-gradient(to bottom, var(--tw-gradient-stops)); } .bg-gradient-to-b::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%; filter: blur(0px); z-index: 1; } .mt-12 { margin-top: 3rem; } .overlay-content { position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; justify-content: center; align-items: center; z-index: 2; color: white; font-size: 2rem; } .text-followus-foreground { color: #fffdfd; } .text-xl { font-size: 1.25rem; line-height: 1.75rem; } .bg-followus-background { background-color: #737373; } .rounded-full { border-radius: 9999px; } .justify-center { justify-content: center; } .items-center { align-items: center; } .w-11 { width: 2.75rem; } .h-11 { height: 2.75rem; } .mb-mp { margin-bottom: 4rem; } 

        </style>
        <div class="mt-12 bg-gradient-to-b from-transparent from-70% to-secondary"><div class="overlay-content"></div><img src="{{ asset($config ? $config->logo_footer : '') }}" alt="{{ $config->judul_web }}" width="100" loading="lazy" decoding="async" fetchpriority="low" style="width: 100%; height: auto;" class="object-cover object-bottom"></div>
<footer class="bg-murky-9000 text-text-color " >
    
    <h2 id="footer-heading" class="sr-only">Footer</h2>
    <div class="container pb-8">
     <div class="pt-4 text-center">
        <p class="text-center text-sm leading-6">{{ !$config ? '' : $config->deskripsi_web }}</p>
    </div>
        <div class="xl:grid xl:grid-cols-2 xl:gap-8">
            <div class="mt-16 grid grid-cols-2 gap-8 xl:col-span-2">
                <div class="md:grid md:grid-cols-2 md:gap-8">
                    <div>
                        <div class="relative">
                            <h3 class="text-sm font-bold leading-6 text-text-color">Kemitraan</h3>
                            <div class="h-1 w-1/4 rounded-full bg-primary-400"></div>
                        </div>
                        <ul role="list" class="mt-6 space-y-4">
                            <li>
                                <a href="{{ !$config ? '' : $config->url_wa }}" class="flex space-x-3 text-sm leading-6 text-text-color hover:text-primary-200" target="_blank" rel="noopener noreferrer" style="outline: none;">
                                   
                                    <span>Daftar Reseller </span>
                                </a>
                            </li>
                            <li>
                                <a href="https://wa.me/6285792464508" class="flex space-x-3 text-sm leading-6 text-text-color hover:text-primary-200" target="_blank" rel="noopener noreferrer" style="outline: none;">
                                   
                                    <span>Web Top Up </span>
                                </a>
                            </li>
                            <li>
                                @php
                                    $docsUrl = '#';
                                    try {
                                        if (Route::has('reseller.docs')) {
                                            $docsUrl = route('reseller.docs');
                                        } elseif (env('DOCS_URL')) {
                                            $docsUrl = env('DOCS_URL');
                                        }
                                    } catch (\Exception $e) {
                                        $docsUrl = env('DOCS_URL', '#');
                                    }
                                @endphp
                                <a href="{{ $docsUrl }}" class="flex space-x-3 text-sm leading-6 text-text-color hover:text-primary-200" target="_blank" rel="noopener noreferrer" style="outline: none;">
                                   
                                    <span>Documentation APi </span>
                                </a>
                            </li>
                           
                        </ul>
                    </div>
                    <div class="mt-10 md:mt-0">
                        <div class="relative">
                            <h3 class="text-sm font-bold leading-6 text-text-color">Peta Situs</h3>
                            <div class="h-1 w-1/4 rounded-full bg-primary-400"></div>
                        </div>
                        <ul role="list" class="mt-6 space-y-4">
                            <li><a class="text-sm leading-6 text-text-color hover:text-primary-200" href="/id" style="outline: none;">Beranda</a></li>
                            <li><a class="text-sm leading-6 text-text-color hover:text-primary-200" href="{{ route('cari') }}" style="outline: none;">Cek Transaksi</a></li>
                            <li><a class="text-sm leading-6 text-text-color hover:text-primary-200" href="/id/price-list" style="outline: none;">Daftar Harga</a></li>
                            <li><a class="text-sm leading-6 text-text-color hover:text-primary-200" href="{{ !$config ? '' : $config->url_wa }}" style="outline: none;">Hubungi Kami</a></li>                            
                        </ul>
                    </div>
                </div>
                <div class="md:grid md:grid-cols-2 md:gap-8">
                    <div>
                        <div class="relative">
                                    <h3 class="text-sm font-bold leading-6 text-text-color">Rekomendasi Topup</h3>
                                    <div class="h-1 w-1/4 rounded-full bg-primary-400"></div>
                                </div>
                                <ul role="list" class="mt-6 space-y-4">
                                    <li><a href="/id/mobile-legends" class="text-sm leading-6 text-text-color hover:text-primary-200" target="_blank" rel="noopener noreferrer" style="outline: none;">Mobile Legends</a></li>
                                    <li><a href="/id/honor-of-kings" class="text-sm leading-6 text-text-color hover:text-primary-200" target="_blank" rel="noopener noreferrer" style="outline: none;">Honor Of Kings</a></li>
                                    <li><a href="/id/free-fire" class="text-sm leading-6 text-text-color hover:text-primary-200" target="_blank" rel="noopener noreferrer" style="outline: none;">Free Fire</a></li>
                                </ul>
                    </div>
                    <div class="mt-10 md:mt-0">
                        <div class="relative">
                            <h3 class="text-sm font-bold leading-6 text-text-color">Legalitas & Support</h3>
                            <div class="h-1 w-1/4 rounded-full bg-primary-400"></div>
                        </div>
                        <ul role="list" class="mt-6 space-y-4">
                            <li><a href="{{ !$config ? '' : $config->url_wa }}" class="text-sm leading-6 text-text-color hover:text-primary-200" target="_blank" rel="noopener noreferrer" style="outline: none;">WhatsApp</a></li>
                            <li><a href="{{ !$config ? '' : $config->url_ig }}" class="text-sm leading-6 text-text-color hover:text-primary-200" target="_blank" rel="noopener noreferrer" style="outline: none;">Instagram</a></li>
                            <li><a class="text-sm leading-6 text-text-color hover:text-primary-200" href="{{ route('policy') }}" style="outline: none;">Kebijakan Pribadi</a></li>
                            <li><a class="text-sm leading-6 text-text-color hover:text-primary-200" href="{{ route('terms') }}" style="outline: none;">Syarat & Ketentuan</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-16 border-t border-bg-color pt-8 sm:mt-20 lg:mt-24 text-center">
            <p class="text-xs leading-5 text-text-color">© {{ date("Y") }} {{ $config->judul_web }}. All rights reserved.</p>
            <p class="text-xs leading-5 text-text-color">{{ $config->judul_web }}.</p></div>
    </div>
</footer>
 
  <a
    href="{{ !$config ? '' : $config->url_wa }}"
    class="fixed bottom-0 left-4 z-50 inline-flex items-center space-x-2.5 rounded-t-lg bg-primary-500 pt-3 pb-2 pl-3 pr-3 uppercase text-white ring-4 ring-orange-200/20 duration-300 ease-in-out hover:bg-primary-600 hover:animate-bounce md:pr-5"
    style="outline: none; transition: all 0.3s ease-in-out;"
    target="_blank"
    rel="noopener noreferrer"
>
    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
        <path d="M4 14v-3a8 8 0 1 1 16 0v3"></path>
        <path d="M18 19c0 1.657 -2.686 3 -6 3"></path>
        <path d="M4 14a2 2 0 0 1 2 -2h1a2 2 0 0 1 2 2v3a2 2 0 0 1 -2 2h-1a2 2 0 0 1 -2 -2v-3z"></path>
        <path d="M15 14a2 2 0 0 1 2 -2h1a2 2 0 0 1 2 2v3a2 2 0 0 1 -2 2h-1a2 2 0 0 1 -2 -2v-3z"></path>
    </svg>
    <span class="hidden text-xs font-medium md:inline">CS {{ $config->judul_web }}</span>
</a>

<!-- Customer Service Chat End -->
