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
            // Determine if current page is homepage/beranda
            $isHomepage = request()->is('/') || request()->is('id');

            // Use homepage-specific footer description if:
            // 1. We're on homepage/beranda
            // 2. Homepage footer is enabled
            // 3. Homepage footer description is not empty
            if ($isHomepage && ($config->aktif_footer_beranda ?? false) && !empty(trim((string) ($config->deskripsi_footer_beranda ?? '')))) {
                $footerDescription = trim((string) $config->deskripsi_footer_beranda);
            } else {
                // Fallback to default description for all other pages
                $footerDescription = trim((string) ($config->deskripsi_web ?? ''));
            }
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
                            @php($docsUrl = app(\App\Services\PublicSiteConfigService::class)->docsUrl())
                            @if($docsUrl)
                                <li>
                                    <a href="{{ $docsUrl }}" class="flex space-x-3 text-sm leading-6 text-text-color hover:text-primary-200" target="_blank" rel="noopener noreferrer" style="outline: none;">
                                        <span>Dokumentasi API</span>
                                    </a>
                                </li>
                            @endif
                           
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
                            <li>
                                <a href="{{ !$config ? '' : $config->url_wa }}" class="flex items-center gap-2 text-sm leading-6 text-text-color hover:text-primary-200" target="_blank" rel="noopener noreferrer" style="outline: none;">
                                    <svg style="width:16px;height:16px;flex-shrink:0" viewBox="0 0 24 24" aria-hidden="true"><path fill="#25D366" d="M20.52 3.48A11.79 11.79 0 0 0 12.1 0C5.55 0 .23 5.32.23 11.86c0 2.09.55 4.13 1.58 5.93L.13 24l6.36-1.67a11.88 11.88 0 0 0 5.61 1.43h.01c6.54 0 11.86-5.32 11.86-11.86 0-3.17-1.23-6.15-3.45-8.42Z"/><path fill="#fff" d="M12.1 21.75h-.01a9.84 9.84 0 0 1-5.02-1.38l-.36-.22-3.77.99 1-3.67-.24-.38a9.82 9.82 0 0 1-1.51-5.23c0-5.45 4.44-9.89 9.91-9.89 2.65 0 5.13 1.03 7 2.9a9.82 9.82 0 0 1 2.9 7.02c0 5.46-4.44 9.86-9.9 9.86Zm5.43-7.39c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.17-.17.2-.35.22-.65.07-.3-.15-1.26-.46-2.4-1.48-.89-.79-1.49-1.77-1.66-2.07-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.18.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.67-1.61-.92-2.21-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.08-.79.37-.27.3-1.04 1.02-1.04 2.48s1.07 2.88 1.22 3.08c.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.69.63.71.23 1.36.2 1.87.12.57-.08 1.76-.72 2.01-1.41.25-.7.25-1.29.17-1.42-.07-.13-.27-.2-.57-.35Z"/></svg>
                                    <span>WhatsApp</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ !$config ? '' : $config->url_ig }}" class="flex items-center gap-2 text-sm leading-6 text-text-color hover:text-primary-200" target="_blank" rel="noopener noreferrer" style="outline: none;">
                                    <svg style="width:16px;height:16px;flex-shrink:0" viewBox="0 0 24 24" aria-hidden="true"><defs><linearGradient id="ig-footer-gradient" x1="0" x2="1" y1="1" y2="0"><stop offset="0" stop-color="#FEDA75"/><stop offset=".35" stop-color="#FA7E1E"/><stop offset=".65" stop-color="#D62976"/><stop offset="1" stop-color="#4F5BD5"/></linearGradient></defs><rect width="24" height="24" rx="6" fill="url(#ig-footer-gradient)"/><path fill="#fff" d="M12 7.1a4.9 4.9 0 1 0 0 9.8 4.9 4.9 0 0 0 0-9.8Zm0 8.08a3.18 3.18 0 1 1 0-6.36 3.18 3.18 0 0 1 0 6.36Zm6.25-8.28a1.14 1.14 0 1 1-2.28 0 1.14 1.14 0 0 1 2.28 0Z"/><path fill="#fff" d="M12 3.6c2.28 0 2.55.01 3.45.05.83.04 1.28.18 1.58.3.4.15.68.34.98.64.3.3.49.58.64.98.12.3.26.75.3 1.58.04.9.05 1.17.05 3.45s-.01 2.55-.05 3.45c-.04.83-.18 1.28-.3 1.58-.15.4-.34.68-.64.98-.3.3-.58.49-.98.64-.3.12-.75.26-1.58.3-.9.04-1.17.05-3.45.05s-2.55-.01-3.45-.05c-.83-.04-1.28-.18-1.58-.3a2.64 2.64 0 0 1-.98-.64 2.64 2.64 0 0 1-.64-.98c-.12-.3-.26-.75-.3-1.58C5.01 13.15 5 12.88 5 10.6s.01-2.55.05-3.45c.04-.83.18-1.28.3-1.58.15-.4.34-.68.64-.98.3-.3.58-.49.98-.64.3-.12.75-.26 1.58-.3.9-.04 1.17-.05 3.45-.05Zm0-1.54c-2.32 0-2.61.01-3.52.05-.91.04-1.53.19-2.07.4-.56.22-1.04.51-1.51.98-.47.47-.76.95-.98 1.51-.21.54-.36 1.16-.4 2.07-.04.91-.05 1.2-.05 3.52s.01 2.61.05 3.52c.04.91.19 1.53.4 2.07.22.56.51 1.04.98 1.51.47.47.95.76 1.51.98.54.21 1.16.36 2.07.4.91.04 1.2.05 3.52.05s2.61-.01 3.52-.05c.91-.04 1.53-.19 2.07-.4.56-.22 1.04-.51 1.51-.98.47-.47.76-.95.98-1.51.21-.54.36-1.16.4-2.07.04-.91.05-1.2.05-3.52s-.01-2.61-.05-3.52c-.04-.91-.19-1.53-.4-2.07a4.18 4.18 0 0 0-.98-1.51 4.18 4.18 0 0 0-1.51-.98c-.54-.21-1.16-.36-2.07-.4-.91-.04-1.2-.05-3.52-.05Z"/></svg>
                                    <span>Instagram</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ !$config ? '' : $config->url_fb }}" class="flex items-center gap-2 text-sm leading-6 text-text-color hover:text-primary-200" target="_blank" rel="noopener noreferrer" style="outline: none;">
                                    <svg style="width:16px;height:16px;flex-shrink:0" viewBox="0 0 24 24" aria-hidden="true"><path fill="#1877F2" d="M24 12.07C24 5.4 18.63 0 12 0S0 5.4 0 12.07C0 18.1 4.39 23.1 10.13 24v-8.44H7.08v-3.49h3.05V9.41c0-3.03 1.79-4.7 4.53-4.7 1.31 0 2.68.23 2.68.23v2.97h-1.51c-1.49 0-1.96.93-1.96 1.89v2.27h3.33l-.53 3.49h-2.8V24C19.61 23.1 24 18.1 24 12.07Z"/></svg>
                                    <span>Facebook</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ !$config ? '' : $config->url_tiktok }}" class="flex items-center gap-2 text-sm leading-6 text-text-color hover:text-primary-200" target="_blank" rel="noopener noreferrer" style="outline: none;">
                                    <svg style="width:16px;height:16px;flex-shrink:0" viewBox="0 0 24 24" aria-hidden="true"><path fill="#111827" d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64c.3 0 .6.05.88.14V9.4a6.34 6.34 0 0 0-.88-.06A6.33 6.33 0 0 0 5 20.14a6.34 6.34 0 0 0 10.86-4.43V8.78a8.21 8.21 0 0 0 4.8 1.54V6.88c-.36 0-.72-.06-1.07-.19Z"/></svg>
                                    <span>TikTok</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ !$config ? '' : $config->url_youtube }}" class="flex items-center gap-2 text-sm leading-6 text-text-color hover:text-primary-200" target="_blank" rel="noopener noreferrer" style="outline: none;">
                                    <svg style="width:16px;height:16px;flex-shrink:0" viewBox="0 0 24 24" aria-hidden="true"><path fill="#FF0000" d="M23.5 6.2a3 3 0 0 0-2.1-2.13C19.55 3.56 12 3.56 12 3.56s-7.55 0-9.4.5A3 3 0 0 0 .5 6.2 31.2 31.2 0 0 0 0 12a31.2 31.2 0 0 0 .5 5.8 3 3 0 0 0 2.1 2.13c1.85.5 9.4.5 9.4.5s7.55 0 9.4-.5a3 3 0 0 0 2.1-2.13A31.2 31.2 0 0 0 24 12a31.2 31.2 0 0 0-.5-5.8Z"/><path fill="#fff" d="M9.75 15.57V8.43L16 12l-6.25 3.57Z"/></svg>
                                    <span>YouTube</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ !$config ? '' : ($config->url_discord ?? '') }}" class="flex items-center gap-2 text-sm leading-6 text-text-color hover:text-primary-200" target="_blank" rel="noopener noreferrer" style="outline: none;">
                                    <svg style="width:16px;height:16px;flex-shrink:0" viewBox="0 0 24 24" aria-hidden="true"><path fill="#5865F2" d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057 19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028 14.09 14.09 0 0 0 1.226-1.994.076.076 0 0 0-.041-.106 13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.892.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.956-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.955-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.946 2.418-2.157 2.418z"/></svg>
                                    <span>Discord</span>
                                </a>
                            </li>
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
