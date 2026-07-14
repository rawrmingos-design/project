<style>
 .bg-primary-400 { background-color: var(--warna_2); } /* sebelumnya var(--warna_3) */
 .rounded-\[9999px\], .rounded-full { border-radius: 9999px; } .w-1\/4 { width: 25%; } .h-1 { height: .25rem; } .bg-murky-9000 { --tw-bg-opacity: 1; background-color: #28282A; } .hover\:animate-bounce:hover { animation: bounce 1s; } .to-secondary { --tw-gradient-to: #3f3f3f; } .from-70\% { --tw-gradient-from-position: 70%; } .from-transparent { --tw-gradient-from: transparent var(--tw-gradient-from-position); --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to); } .bg-gradient-to-b { position: relative; background-image: linear-gradient(to bottom, var(--tw-gradient-stops)); } .bg-gradient-to-b::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%; filter: blur(0px); z-index: 1; } .mt-12 { margin-top: 3rem; } .overlay-content { position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; justify-content: center; align-items: center; z-index: 2; color: white; font-size: 2rem; } .text-followus-foreground { color: #fffdfd; } .text-xl { font-size: 1.25rem; line-height: 1.75rem; } .bg-followus-background { background-color: #737373; } .rounded-full { border-radius: 9999px; } .justify-center { justify-content: center; } .items-center { align-items: center; } .w-11 { width: 2.75rem; } .h-11 { height: 2.75rem; } .mb-mp { margin-bottom: 4rem; }
 .footer-seo-rich { width: min(100%, 64rem); margin: 0 auto; padding-top: 1rem; text-align: left; }
 .footer-seo-rich__content { position: relative; max-height: none; overflow: visible; color: #dfe9fb; font-size: .875rem; line-height: 1.8; }
 .footer-seo-rich.is-collapsible .footer-seo-rich__content { max-height: 8.25rem; overflow: hidden; transition: max-height .24s ease; }
 .footer-seo-rich.is-expanded .footer-seo-rich__content { max-height: none; overflow: visible; }
 .footer-seo-rich.is-collapsible:not(.is-expanded) .footer-seo-rich__content::after { content: ''; position: absolute; right: 0; bottom: 0; left: 0; height: 3.5rem; pointer-events: none; background: linear-gradient(to bottom, rgba(40, 40, 42, 0), #28282A); }
 .footer-seo-rich__content p { margin: 0 0 .75rem; }
 .footer-seo-rich__content h2, .footer-seo-rich__content h3 { margin: 1rem 0 .5rem; color: #fff; font-weight: 700; line-height: 1.35; }
 .footer-seo-rich__content h2 { font-size: 1rem; }
 .footer-seo-rich__content h3 { font-size: .95rem; }
 .footer-seo-rich__content ul, .footer-seo-rich__content ol { margin: .5rem 0 .75rem; padding-left: 1.25rem; }
 .footer-seo-rich__content ul { list-style: disc; }
 .footer-seo-rich__content ol { list-style: decimal; }
 .footer-seo-rich__content li + li { margin-top: .25rem; }
 .footer-seo-rich__content a { color: var(--warna_3); text-decoration: underline; text-underline-offset: 3px; }
 .footer-seo-rich__toggle { display: flex; margin: .85rem auto 0; border: 0; background: transparent; color: var(--warna_3); cursor: pointer; font-size: .82rem; font-weight: 700; line-height: 1.4; }
 .footer-seo-rich__toggle:hover { color: #fff; }

        </style>
        <div class="mt-12 bg-gradient-to-b from-transparent from-70% to-secondary"><div class="overlay-content"></div><img src="{{ asset($config ? $config->logo_footer : '') }}" alt="{{ $config->judul_web }}" width="100" loading="lazy" decoding="async" fetchpriority="low" style="width: 100%; height: auto;" class="object-cover object-bottom"></div>
<footer class="bg-murky-9000 text-text-color " >
    
    <h2 id="footer-heading" class="sr-only">Footer</h2>
    <div class="container pb-8">
        @php
            $footerDescription = trim((string) ($config->deskripsi_web ?? ''));
        @endphp
        @if($footerDescription !== '')
            <section class="footer-seo-rich" data-footer-seo>
                <div class="footer-seo-rich__content" data-footer-seo-content>
                    @safeHtml($footerDescription)
                </div>
                <button class="footer-seo-rich__toggle" type="button" data-footer-seo-toggle hidden aria-expanded="false">
                    Baca selengkapnya
                </button>
            </section>
        @endif
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
                                   
                                    <span>Gabung Kemitraan </span>
                                </a>
                            </li>
                            @unless ((bool) config('tenancy.disabled', true))
                                <li>
                                    <a href="/id/reseller-topup" class="flex space-x-3 text-sm leading-6 text-text-color hover:text-primary-200" style="outline: none;">

                                        <span>Reseller Topup </span>
                                    </a>
                                </li>
                            @endunless
                            <li>
                                @php
                                    $docsUrl = '#';
                                    try {
                                        if (Route::has('docs.index')) {
                                            $docsUrl = route('docs.index');
                                        } elseif (env('DOCS_URL')) {
                                            $docsUrl = env('DOCS_URL');
                                        }
                                    } catch (\Exception $e) {
                                        $docsUrl = env('DOCS_URL', '#');
                                    }
                                @endphp
                                <a href="{{ $docsUrl }}" class="flex space-x-3 text-sm leading-6 text-text-color hover:text-primary-200" target="_blank" rel="noopener noreferrer" style="outline: none;">
                                   
                                    <span>Dokumentasi API </span>
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

<script>
    (function () {
        var collapsedHeight = 132;
        var blocks = document.querySelectorAll('[data-footer-seo]');

        blocks.forEach(function (block) {
            var content = block.querySelector('[data-footer-seo-content]');
            var toggle = block.querySelector('[data-footer-seo-toggle]');

            if (!content || !toggle) {
                return;
            }

            var updateToggle = function () {
                var shouldCollapse = content.scrollHeight > collapsedHeight;

                block.classList.toggle('is-collapsible', shouldCollapse);
                toggle.hidden = !shouldCollapse;

                if (!shouldCollapse) {
                    block.classList.remove('is-expanded');
                    toggle.setAttribute('aria-expanded', 'false');
                    toggle.textContent = 'Baca selengkapnya';
                }
            };

            var scheduleUpdate = function () {
                window.requestAnimationFrame(updateToggle);
            };

            toggle.addEventListener('click', function () {
                var expanded = block.classList.toggle('is-expanded');
                toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                toggle.textContent = expanded ? 'Tutup' : 'Baca selengkapnya';
            });

            scheduleUpdate();
            window.addEventListener('load', scheduleUpdate);
            window.addEventListener('resize', scheduleUpdate);
            window.setTimeout(scheduleUpdate, 250);
        });
    })();
</script>

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
