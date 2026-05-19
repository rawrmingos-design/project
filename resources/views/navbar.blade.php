@php
    $publicAuthUser = Auth::user();
    $publicAllowedRoles = ['Member', 'Platinum', 'Gold', 'Admin'];
    $publicHasMemberMenu = $publicAuthUser && in_array((string) $publicAuthUser->role, $publicAllowedRoles, true);

    $publicDisplayName = Str::title((string) ($publicAuthUser?->name ?: $publicAuthUser?->username ?: 'Member'));
    $publicAvatarFallback = 'https://ui-avatars.com/api/?color=FFFFFF&background=50a7ff&name=' . urlencode($publicDisplayName);
    $publicAvatarCandidate = trim((string) ($publicAuthUser?->google_avatar ?? ''));

    if ($publicAvatarCandidate !== '' && ! str_starts_with($publicAvatarCandidate, 'http://') && ! str_starts_with($publicAvatarCandidate, 'https://')) {
        $publicAvatarCandidate = '/' . ltrim($publicAvatarCandidate, '/');
    }

    $publicAvatarUrl = $publicAvatarCandidate !== '' ? $publicAvatarCandidate : $publicAvatarFallback;
    $publicLogoHeaderRaw = trim((string) ($config->logo_header ?? ''));
    $publicLogoHeader = '';
    if ($publicLogoHeaderRaw !== '') {
        $publicLogoHeader = Str::startsWith($publicLogoHeaderRaw, ['http://', 'https://', '//'])
            ? $publicLogoHeaderRaw
            : asset(ltrim($publicLogoHeaderRaw, '/'));
    }
    $publicDropdownMenu = [
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'Pengaturan', 'href' => route('editProfile')],
    ];

    $publicDashboardMenu = [
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'Riwayat Transaksi', 'href' => route('riwayat')],
        ['label' => 'Riwayat Deposit', 'href' => route('reload')],
        ['label' => 'Afiliasi', 'href' => route('affiliate')],
        ['label' => 'Pengaturan', 'href' => route('editProfile')],
    ];
@endphp

<style>
    .blurred-navbar {
        -webkit-backdrop-filter: blur(5px);
        backdrop-filter: blur(5px);
        background-color: var(--warna_2);
    } 

    .bg-melpa-100 {
        --tw-bg-opacity: 1;
        background-color: var(--warna_2); /* sebelumnya var(--warna_1) */
    }
    .border-murky-800 {
        --tw-border-opacity: 1;
        border-color: var(--warna_3);
    }
    .bg-primary-x {
        --tw-bg-opacity: 1;
        background-color:  var(--warna_2);
    }
    .text-primary-300 {
        color: var(--warna_2);
    }
    .py-1 a:hover {
        background-color: var(--warna_3);

        transition: background-color 0.3s ease;
    }
    .border-primary-500 {
        --tw-border-opacity: 1;
        border-color: var(--warna_3);
    }
    .blur {
        backdrop-filter: blur(6px); 
        -webkit-backdrop-filter: blur(6px); 
    }
    .hover\:bg-murky-700:hover {
        background-color:var(--warna_3); 
    }

    .navbar-link,
    .navbar-nav > .nav-link,
    .navbar-nav > .nav-item > .nav-link {
        transition: background-color 0.3s, color 0.3s;
    }
    .navbar-link:hover,
    .navbar-nav > .nav-link:hover,
    .navbar-nav > .nav-item > .nav-link:hover,
    .navbar-link.active,
    .navbar-nav > .nav-link.active,
    .navbar-nav > .nav-item > .nav-link.active {
        background-color: var(--warna_2) !important;
        color: #fff !important;
    }

    .public-nav-avatar {
        width: 28px;
        height: 28px;
        border-radius: 9999px;
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.2);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.08);
        flex-shrink: 0;
    }

    .public-nav-avatar__image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
</style>
<header class="sticky z-40 w-full flex-none backdrop-blur duration-500 ease-in-out print:hidden top-0 mt-0" style="z-index:50">

<div class="container flex h-16 items-center justify-between gap-4">
            <div class="flex items-center justify-start">
                <a href="/" style="outline:none">
                    <span class="sr-only">{{ $config->judul_web }}.</span>
                <img alt="{{ $config->judul_web }}." fetchpriority="high" width="1000" height="1000" decoding="async" data-nimg="1" class="h-9 w-auto lg:h-10" src="{{ $publicLogoHeader }}" style="color:transparent"></a>
            </div>
            <div class="flex flex-1 items-center justify-end gap-2">
                <div class="relative w-full hidden md:flex">
    <div class="relative w-full space-y-2">
        <input 
            type="text"
            class="inline-block h-9 w-full rounded-md bg-muted/50 pl-9 text-sm focus:border-primary-500 focus:outline-none focus:ring-primary-500 placeholder:text-white focus:outline-none disabled:cursor-not-allowed disabled:opacity-75"
            placeholder="Cari Game, Item, Voucher" 
            id="searchProdsdekstop" 
            role="combobox" 
            name="q" 
            aria-expanded="false" 
            aria-autocomplete="list" 
            aria-controls="combobox-options-:r4i:" 
            tabindex="0"
            aria-label="Cari produk">
        <ul class="resultsearchdekstop absolute top-full left-0 w-full max-h-80 scroll-py-2 divide-y divide-gray-500 divide-opacity-10 overflow-y-auto bg-primary-x backdrop-blur shadow-md rounded-md z-30">
        </ul>
    </div>
</div>

            
               <button x-on:click="isSearchModalOpen = true" 
    type="button"
    class="text-sm lg:hidden font-medium text-white border border-secondary-600 outline-none duration-300 hover:bg-secondary-500 focus:bg-secondary-500 focus-visible:bg-secondary-500 h-9 rounded-xl px-3 py-2"
   
>
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-5 w-5 sm:h-4 sm:w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"></path></svg>
                           
            </button>
                <div class="flex items-center justify-center">
                    <button class="inline-flex items-center justify-center whitespace-nowrap transition-all rounded-xl shadow-sm border border-secondary-600 outline-none text-sm font-medium ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50  bg-transparent hover:bg-accent/75 hover:text-accent-foreground h-9 px-3 py-2" type="button" fdprocessedid="kwwugn">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <g clip-path="url(#clip0_2722_24355)">
                                <path d="M12 24C18.6274 24 24 18.6274 24 12C24 5.37258 18.6274 0 12 0C5.37258 0 0 5.37258 0 12C0 18.6274 5.37258 24 12 24Z" fill="#F0F0F0"></path>
                                <path d="M0 12C0 5.37262 5.37262 0 12 0C18.6274 0 24 5.37262 24 12" fill="#D80027"></path>
                            </g>
                            <defs>
                                <clipPath id="clip0_2722_24355">
                                    <rect width="24" height="24" fill="white"></rect>
                                </clipPath>
                            </defs>
                        </svg> 
                       
                    </button>
                </div>
                <div class="hidden lg:flex lg:items-center lg:justify-end gap-2">
                         @if($publicHasMemberMenu)
                            
                            
                        <div
                            class="relative inline-block text-left"
                            x-data="{
  open: false,
  toggle() {
    if (this.open) {
      this.close();
      return;
    }
    this.$refs.button.focus();
    this.open = true;
  },
  close(focusAfter) {
    if (!this.open) return;
    this.open = false;
    if (focusAfter) focusAfter.focus();
  }
}
"
                            x-on:keydown.escape.prevent.stop="close($refs.button)"
                            x-on:focusin.window="! $refs.panel.contains($event.target) &amp;&amp; close()"
                            x-id="['languange-dropdown']"
                        >
                            <div>
                                <button
                                    x-ref="button"
                                    x-on:click="toggle()"
                                    :aria-expanded="open"
                                    :aria-controls="$id('languange-dropdown')"
                                    type="button"
                                    class="inline-flex w-full items-center justify-center justify-center gap-x-1 rounded-full border border-secondary-600 px-2 py-2 text-sm font-semibold uppercase text-white hover:bg-murky-800 focus:bg-murky-800" 
                                    aria-expanded="false"
                                    aria-controls="languange-dropdown-1">
                                    <div class="flex items-center space-x-2">
                                          <span class="public-nav-avatar">
                                              <img
                                                  src="{{ $publicAvatarUrl }}"
                                                  alt="{{ $publicDisplayName }}"
                                                  class="public-nav-avatar__image"
                                                  onerror="this.onerror=null;this.src='{{ $publicAvatarFallback }}';"
                                              />
                                          </span>
                                          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" class="h-5 w-5">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"></path>
                        </svg>
                        </div>
                                </button>
                            </div>
                            <div
                                x-ref="panel"
                                x-show="open"
                                x-transition.origin.top.right=""
                                x-on:click.outside="close($refs.button)"
                                :id="$id('languange-dropdown')"
                                style="display: none;"
                                class="absolute right-0 z-10 mt-2 w-48 origin-top-right divide-y divide-gray-100 rounded-lg bg-secondary  shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none transform opacity-100 scale-100"
                                id="languange-dropdown-1"
                            >
                                
                                 <div class="px-4 py-3" role="none">
                            <p class="text-sm" role="none">Telah masuk sebagai</p>
                            <p class="truncate text-sm font-medium text-white" role="none">
                                {{Str::title(Auth()->user()->username)}}
                            </p>
                        </div>
                                <div class="py-1" role="none">
                                    <a
                                        class="text-murky-100 flex w-full items-center space-x-2 px-4 py-2 text-sm"
                                        id="headlessui-menu-item-:r17:"
                                        role="menuitem"
                                        tabindex="-1"
                                        data-headlessui-state=""
                                        href="{{ route('dashboard') }}"
                                        style="outline: none;"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-4 w-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 9m18 0V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v3"></path>
                                        </svg>
                                        <span>Rp {{ number_format((int) (Auth::user()->balance ?? 0), 0, ',', '.') }}</span>
                                    </a>
                                    @foreach($publicDropdownMenu as $menuItem)
                                        <a class="text-murky-100 flex w-full items-center space-x-2 px-4 py-2 text-sm" role="menuitem" tabindex="-1" href="{{ $menuItem['href'] }}" style="outline: none;">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-4 w-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5h10M9 12h10M9 19h10M4.5 5h.01M4.5 12h.01M4.5 19h.01"></path>
                                            </svg>
                                            <span>{{ $menuItem['label'] }}</span>
                                        </a>
                                    @endforeach
                                </div>
                                
                        <div class="py-1" role="none">
                            <a>
                                <form action="{{ route('logout') }}" method="POST" id="logout">
                                    @csrf
                                    <button type="submit" class="text-murky-100 flex w-full items-center space-x-2 px-4 py-2 text-left text-sm" id="headlessui-menu-item-logout" role="menuitem" tabindex="-1" data-headlessui-state="">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-4 w-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"></path>
                                        </svg>
                                        <span>Keluar</span>
                                    </button>
                                </form>
                            </a>
                        </div>
                            </div>
                        </div>    
                            
                            @endif
                        </div>
                <div>
                    
                </div>
                
                        
            
                <button
                        type="button"
                        class="rounded-xl bg-primary-500 p-2 text-white lg:hidden"
                        x-data="{ usedKeyboard: false }"
                        @keydown.window.tab="usedKeyboard = true"
                        role="button"
                        @click="$dispatch('open-menu', { open: true })"
                        :class="{ 'focus:outline-none': !usedKeyboard } "
                    >
                        <span class="sr-only">Open menu</span>
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path clip-rule="evenodd" fill-rule="evenodd" d="M2 4.75A.75.75 0 012.75 4h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 4.75zm0 10.5a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5a.75.75 0 01-.75-.75zM2 10a.75.75 0 01.75-.75h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 10z"></path></svg>
                    </button>
                
        </div>
    </div>

<div class="border-b border-murky-600"></div>

<nav class="container hidden w-full items-center justify-between print:hidden lg:flex h-16">

     <div class="flex h-full gap-3">
        <a class="relative z-10 -mb-px flex items-center space-x-2 border-b-2 pt-px text-sm font-medium transition-colors duration-200 ease-out border-transparent hover:border-primary-500 hover:text-primary-300 {{ Request::is('id') ? 'border-primary-500 text-primary-300' : '' }}" style="outline: none;" href="/id">
            <svg width="20" height="20" viewBox="0 0 25 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M16.5209 6.87109H8.47891C5.67599 6.87109 3.91895 8.8558 3.91895 11.6636V16.206C3.91895 19.0148 5.66724 20.9985 8.47891 20.9985H16.5199C19.3316 20.9985 21.0818 19.0148 21.0818 16.206V11.6636C21.0818 8.8558 19.3316 6.87109 16.5209 6.87109Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path><path opacity="0.4" d="M9.38477 11.1445C9.45871 11.8684 9.79338 12.5173 10.275 13.0096C10.8509 13.5748 11.639 13.928 12.5019 13.928C14.116 13.928 15.443 12.7119 15.6191 11.1445" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path><path opacity="0.4" d="M16.3702 6.87115C16.3702 4.7337 14.6375 3 12.4991 3C10.3616 3 8.62891 4.7337 8.62891 6.87115" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg>
            <span>Beranda</span>
        </a>

        <a class="relative z-10 -mb-px flex items-center space-x-2 border-b-2 pt-px text-sm transition-colors duration-200 ease-out border-transparent hover:border-primary-500 hover:text-primary-300 {{ Request::route() && Request::route()->getName() == 'cari' ? 'border-primary-500 text-primary-300' : '' }}" style="outline: none;" href="{{ route('cari') }}">
    <svg width="20" height="20" viewBox="0 0 25 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M21.129 10.3728L21.1106 8.05801C21.1038 5.1517 19.2891 3.10258 16.3799 3.10939L8.05695 3.12982C5.15647 3.13664 3.34282 5.19353 3.34963 8.10082L3.36812 15.9421C3.37493 18.8485 5.18955 20.8976 8.09879 20.8908L12.0121 20.8713" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path><path fill-rule="evenodd" clip-rule="evenodd" d="M19.6179 18.5669C18.3705 19.8143 16.3477 19.8143 15.1003 18.5669C13.852 17.3195 13.852 15.2967 15.1003 14.0493C16.3477 12.8019 18.3705 12.8019 19.6179 14.0493C20.8653 15.2967 20.8653 17.3195 19.6179 18.5669Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path><path d="M19.6172 18.5669L21.3483 20.2978" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path><path opacity="0.4" d="M15.0331 3.11035L15.0497 9.69846L12.2523 8.78677L9.43747 9.715L9.42969 3.13468" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg>
    <span>Cek Transaksi</span>
</a>
        <a class="relative z-10 -mb-px flex items-center space-x-2 border-b-2 pt-px text-sm font-medium transition-colors duration-200 ease-out border-transparent hover:border-primary-500 hover:text-primary-300 {{ Request::route()->getName() == 'price' ? 'border-primary-500 text-primary-300 font-medium text-sm' : '' }}"
    style="outline: none;"
    href="{{ route('price') }}"
>
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
  <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
</svg>

    <span>Daftar Harga</span>
</a>
        <a class="relative z-10 -mb-px flex items-center space-x-2 border-b-2 pt-px text-sm font-medium transition-colors duration-200 ease-out border-transparent hover:border-primary-500 hover:text-primary-300 {{ Request::route()->getName() == 'leaderboardd' ? 'border-primary-500 text-primary-300' : '' }}"
    style="outline: none;"
    href="{{ route('leaderboardd') }}"
>
    <svg width="20" height="20" viewBox="0 0 25 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9.67637 14.6445H5.44301C4.66172 14.6445 4.02832 15.2779 4.02832 16.0592V19.5852C4.02832 20.3636 4.66464 20.9999 5.44301 20.9999H19.5578C20.3361 20.9999 20.9725 20.3636 20.9725 19.5852V17.9419C20.9725 17.1606 20.3391 16.5272 19.5578 16.5272H15.3244" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path><path opacity="0.4" fill-rule="evenodd" clip-rule="evenodd" d="M12.8887 3.2372L13.5357 4.53124C13.5999 4.6587 13.7225 4.74627 13.8646 4.7667L15.3133 4.97492C15.5478 5.00605 15.7132 5.22108 15.6821 5.45653C15.6694 5.55188 15.6247 5.6414 15.5546 5.70853L14.5067 6.71555C14.4036 6.81284 14.3569 6.95587 14.3812 7.09597L14.6293 8.51747C14.6673 8.75488 14.5057 8.97768 14.2683 9.01563C14.1759 9.0312 14.0796 9.01466 13.9959 8.97087L12.7019 8.3005C12.5744 8.23434 12.4236 8.23434 12.2961 8.3005L11.0011 8.97185C10.789 9.08374 10.5254 9.00298 10.4135 8.79087C10.3687 8.7072 10.3531 8.61088 10.3677 8.51747L10.6158 7.09695C10.6402 6.95684 10.5935 6.81381 10.4903 6.71555L9.44148 5.7095C9.27121 5.54507 9.26635 5.27362 9.43078 5.10237C9.49791 5.03329 9.58742 4.98756 9.68277 4.97492L11.1315 4.7667C11.2736 4.74724 11.3962 4.6587 11.4604 4.53124L12.1093 3.2372C12.2203 3.02218 12.4859 2.93753 12.7009 3.04942C12.7817 3.09126 12.8468 3.15645 12.8887 3.2372Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path><path d="M15.3238 20.9994V13.0006C15.3238 12.2193 14.6904 11.5859 13.9091 11.5859H11.0905C10.3121 11.5859 9.67578 12.2223 9.67578 13.0006V20.9994" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg>
    <span>Leaderboard</span>
</a>
   <div class="relative flex" x-data="{ open: false }" x-on:keydown.escape.prevent.stop="open = false" x-on:focusin.window="if (! $refs.panel.contains(event.target)) open = false" x-id="['dropdown-button']">
    <div class="relative flex">
        <button
            x-ref="button"
            x-on:click="open = !open"
            :aria-expanded="open.toString()"
            :aria-controls="$id('dropdown-button')"
            type="button"
            class="relative z-10 -mb-px flex items-center space-x-2 border-b-2 pt-px bg-transparent text-sm font-medium outline-none transition-colors duration-200 ease-out border-transparent hover:border-primary-500 hover:text-primary-300"
        >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-5 w-5">
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M15.75 15.75V18m-7.5-6.75h.008v.008H8.25v-.008zm0 2.25h.008v.008H8.25V13.5zm0 2.25h.008v.008H8.25v-.008zm0 2.25h.008v.008H8.25V18zm2.498-6.75h.007v.008h-.007v-.008zm0 2.25h.007v.008h-.007V13.5zm0 2.25h.007v.008h-.007v-.008zm0 2.25h.007v.008h-.007V18zm2.504-6.75h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V13.5zm0 2.25h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V18zm2.498-6.75h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V13.5zM8.25 6h7.5v2.25h-7.5V6zM12 2.25c-1.892 0-3.758.11-5.593.322C5.307 2.7 4.5 3.65 4.5 4.757V19.5a2.25 2.25 0 002.25 2.25h10.5a2.25 2.25 0 002.25-2.25V4.757c0-1.108-.806-2.057-1.907-2.185A48.507 48.507 0 0012 2.25z"
                ></path>
            </svg>
            <span>Kalkulator</span>
        </button>
    </div>
    <div
        x-ref="panel"
        x-show="open"
        x-transition.origin.top
        x-on:click.outside="open = false"
        :id="$id('dropdown-button')"
        style="display: none;"
        class="absolute -left-[130%] z-10 mt-[4.25rem] w-screen max-w-[360px] transform px-2 sm:px-0 opacity-100 translate-y-0"
    >
        <div class="overflow-hidden rounded-lg shadow-lg">
            <div class="relative grid gap-6 bg-murky-800 px-2 py-3 sm:gap-8 sm:p-6">
                <a
                    class="-m-3 flex items-start rounded-lg p-3 transition duration-150 ease-in-out hover:bg-murky-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
                    href="{{ route('hitungwr') }}"
                    style="outline: none;"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-6 w-6 flex-shrink-0 text-white">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M15.75 15.75V18m-7.5-6.75h.008v.008H8.25v-.008zm0 2.25h.008v.008H8.25V13.5zm0 2.25h.008v.008H8.25v-.008zm0 2.25h.008v.008H8.25V18zm2.498-6.75h.007v.008h-.007v-.008zm0 2.25h.007v.008h-.007V13.5zm0 2.25h.007v.008h-.007v-.008zm0 2.25h.007v.008h-.007V18zm2.504-6.75h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V13.5zm0 2.25h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V18zm2.498-6.75h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V13.5zM8.25 6h7.5v2.25h-7.5V6zM12 2.25c-1.892 0-3.758.11-5.593.322C5.307 2.7 4.5 3.65 4.5 4.757V19.5a2.25 2.25 0 002.25 2.25h10.5a2.25 2.25 0 002.25-2.25V4.757c0-1.108-.806-2.057-1.907-2.185A48.507 48.507 0 0012 2.25z"
                        ></path>
                    </svg>
                    <div class="ml-4">
                        <p class="text-base font-medium text-white">Win Rate</p>
                        <p class="text-mukry-200 mt-1 text-sm">Digunakan untuk menghitung total jumlah match yang harus ditempuh untuk mencapai target win rate yang diinginkan.</p>
                    </div>
                </a>
                <a
                    class="-m-3 flex items-start rounded-lg p-3 transition duration-150 ease-in-out hover:bg-murky-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
                    href="{{ route('hitungpointmw') }}"
                    style="outline: none;"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-6 w-6 flex-shrink-0 text-white">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M15.75 15.75V18m-7.5-6.75h.008v.008H8.25v-.008zm0 2.25h.008v.008H8.25V13.5zm0 2.25h.008v.008H8.25v-.008zm0 2.25h.008v.008H8.25V18zm2.498-6.75h.007v.008h-.007v-.008zm0 2.25h.007v.008h-.007V13.5zm0 2.25h.007v.008h-.007v-.008zm0 2.25h.007v.008h-.007V18zm2.504-6.75h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V13.5zm0 2.25h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V18zm2.498-6.75h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V13.5zM8.25 6h7.5v2.25h-7.5V6zM12 2.25c-1.892 0-3.758.11-5.593.322C5.307 2.7 4.5 3.65 4.5 4.757V19.5a2.25 2.25 0 002.25 2.25h10.5a2.25 2.25 0 002.25-2.25V4.757c0-1.108-.806-2.057-1.907-2.185A48.507 48.507 0 0012 2.25z"
                        ></path>
                    </svg>
                    <div class="ml-4">
                        <p class="text-base font-medium text-white">Magic Wheel</p>
                        <p class="text-mukry-200 mt-1 text-sm">Digunakan untuk mengetahui total maksimal diamond yang dibutuhkan untuk mendapatkan skin Legends.</p>
                    </div>
                </a>
                <a
                    class="-m-3 flex items-start rounded-lg p-3 transition duration-150 ease-in-out hover:bg-murky-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
                    href="{{ route('hitungpointzodiac') }}"
                    style="outline: none;"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-6 w-6 flex-shrink-0 text-white">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M15.75 15.75V18m-7.5-6.75h.008v.008H8.25v-.008zm0 2.25h.008v.008H8.25V13.5zm0 2.25h.008v.008H8.25v-.008zm0 2.25h.008v.008H8.25V18zm2.498-6.75h.007v.008h-.007v-.008zm0 2.25h.007v.008h-.007V13.5zm0 2.25h.007v.008h-.007v-.008zm0 2.25h.007v.008h-.007V18zm2.504-6.75h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V13.5zm0 2.25h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V18zm2.498-6.75h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V13.5zM8.25 6h7.5v2.25h-7.5V6zM12 2.25c-1.892 0-3.758.11-5.593.322C5.307 2.7 4.5 3.65 4.5 4.757V19.5a2.25 2.25 0 002.25 2.25h10.5a2.25 2.25 0 002.25-2.25V4.757c0-1.108-.806-2.057-1.907-2.185A48.507 48.507 0 0012 2.25z"
                        ></path>
                    </svg>
                    <div class="ml-4">
                        <p class="text-base font-medium text-white">Zodiac</p>
                        <p class="text-mukry-200 mt-1 text-sm">Digunakan untuk mengetahui total diamond maksimal yang dibutuhkan untuk mendapatkan skin Zodiacs.</p>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>
        <div class="ml-auto flex h-full items-center justify-end gap-2 {{ $publicHasMemberMenu ? 'hidden' : '' }}">
                    

                        <div class="desktop-only">
                        </div>
                        <div class="lg:flex lg:items-center lg:justify-end gap-2 space-x-2">
                         @if($publicHasMemberMenu)
                            
                            
                        <div
                            class="relative inline-block text-left"
                            x-data="{
  open: false,
  toggle() {
    if (this.open) {
      this.close();
      return;
    }
    this.$refs.button.focus();
    this.open = true;
  },
  close(focusAfter) {
    if (!this.open) return;
    this.open = false;
    if (focusAfter) focusAfter.focus();
  }
}
"
                            x-on:keydown.escape.prevent.stop="close($refs.button)"
                            x-on:focusin.window="! $refs.panel.contains($event.target) &amp;&amp; close()"
                            x-id="['languange-dropdown']"
                        >
                            <div>
                                <button
                                    x-ref="button"
                                    x-on:click="toggle()"
                                    :aria-expanded="open"
                                    :aria-controls="$id('languange-dropdown')"
                                    type="button"
                                    class="inline-flex w-full items-center justify-center justify-center gap-x-1 rounded-full border border-secondary-600 px-2 py-2 text-sm font-semibold uppercase text-white hover:bg-murky-800 focus:bg-murky-800" 
                                    aria-expanded="false"
                                    aria-controls="languange-dropdown-1">
                                    <div class="flex items-center space-x-2">
                                          <span class="public-nav-avatar">
                                              <img
                                                  src="{{ $publicAvatarUrl }}"
                                                  alt="{{ $publicDisplayName }}"
                                                  class="public-nav-avatar__image"
                                                  onerror="this.onerror=null;this.src='{{ $publicAvatarFallback }}';"
                                              />
                                          </span>
                                          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" class="h-5 w-5">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"></path>
                        </svg>
                        </div>
                                </button>
                            </div>
                            <div
                                x-ref="panel"
                                x-show="open"
                                x-transition.origin.top.right=""
                                x-on:click.outside="close($refs.button)"
                                :id="$id('languange-dropdown')"
                                style="display: none;"
                                class="absolute right-0 z-10 mt-2 w-48 origin-top-right divide-y divide-gray-100 rounded-lg bg-secondary  shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none transform opacity-100 scale-100"
                                id="languange-dropdown-1"
                            >
                                
                                 <div class="px-4 py-3" role="none">
                            <p class="text-sm" role="none">Telah masuk sebagai</p>
                            <p class="truncate text-sm font-medium text-white" role="none">
                                {{Str::title(Auth()->user()->username)}}
                            </p>
                        </div>
                                <div class="py-1" role="none">
                                    <a
                                        class="text-murky-100 flex w-full items-center space-x-2 px-4 py-2 text-sm"
                                        id="headlessui-menu-item-:r17-mobile:"
                                        role="menuitem"
                                        tabindex="-1"
                                        data-headlessui-state=""
                                        href="{{ route('dashboard') }}"
                                        style="outline: none;"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-4 w-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 9m18 0V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v3"></path>
                                        </svg>
                                        <span>Rp {{ number_format((int) (Auth::user()->balance ?? 0), 0, ',', '.') }}</span>
                                    </a>
                                    @foreach($publicDropdownMenu as $menuItem)
                                        <a class="text-murky-100 flex w-full items-center space-x-2 px-4 py-2 text-sm" role="menuitem" tabindex="-1" href="{{ $menuItem['href'] }}" style="outline: none;">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-4 w-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5h10M9 12h10M9 19h10M4.5 5h.01M4.5 12h.01M4.5 19h.01"></path>
                                            </svg>
                                            <span>{{ $menuItem['label'] }}</span>
                                        </a>
                                    @endforeach
                                </div>
                                
                        <div class="py-1" role="none">
                            <a>
                                <form action="{{ route('logout') }}" method="POST" id="logout">
                                    @csrf
                                    <button type="submit" class="text-murky-100 flex w-full items-center space-x-2 px-4 py-2 text-left text-sm" id="headlessui-menu-item-logout" role="menuitem" tabindex="-1" data-headlessui-state="">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-4 w-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"></path>
                                        </svg>
                                        <span>Keluar</span>
                                    </button>
                                </form>
                            </a>
                        </div>
                            </div>
                        </div>    
                            
    @else
                        
                        </div>
                        
                        <div class="flex h-full  justify-end gap-2">
            <a class="relative inline-flex h-full items-center gap-2 text-sm font-medium" href="{{ route('login') }}" style="outline:none">
                <svg width="18" height="18" viewBox="0 0 25 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M14.791 12.1207H2.75" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                    <path d="M11.8643 9.20471L14.7923 12.1207L11.8643 15.0367" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                    <path opacity="0.4" d="M7.25879 7.62988C7.58879 4.04988 8.92879 2.74988 14.2588 2.74988C21.3598 2.74988 21.3598 5.05988 21.3598 11.9999C21.3598 18.9399 21.3598 21.2499 14.2588 21.2499C8.92879 21.2499 7.58879 19.9499 7.25879 16.3699" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg><span>Masuk</span></a>
            <a class="relative inline-flex h-full  items-center gap-2 text-sm font-medium" href="{{ route('register') }}" style="outline:none">
                <svg width="18" height="18" viewBox="0 0 24 24" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                    <title>Iconly/Two-tone/Add User</title>
                    <g id="Iconly/Two-tone/Add-User" stroke="none" stroke-width="1.5" fill="none" fill-rule="evenodd" stroke-linecap="round" stroke-linejoin="round">
                        <g id="Add-User" transform="translate(2.000000, 2.000000)" stroke="currentColor" stroke-width="1.5">
                            <path d="M7.8766,13.2062 C4.0326,13.2062 0.7496,13.7872 0.7496,16.1152 C0.7496,18.4432 4.0126,19.0452 7.8766,19.0452 C11.7216,19.0452 15.0036,18.4632 15.0036,16.1362 C15.0036,13.8092 11.7416,13.2062 7.8766,13.2062 Z" id="Stroke-1"></path>
                            <path d="M7.8766,9.8859 C10.3996,9.8859 12.4446,7.8409 12.4446,5.3179 C12.4446,2.7949 10.3996,0.7499 7.8766,0.7499 C5.3546,0.7499 3.30957019,2.7949 3.30957019,5.3179 C3.3006,7.8319 5.3306,9.8769 7.8456,9.8859 L7.8766,9.8859 Z" id="Stroke-3" opacity="0.400000006"></path>
                            <line x1="17.2037" y1="6.6691" x2="17.2037" y2="10.6791" id="Stroke-5"></line>
                            <line x1="19.2496" y1="8.674" x2="15.1596" y2="8.674" id="Stroke-7"></line>
                        </g>
                    </g>
                </svg><span>Daftar</span></a>
        </div>
                        
                        @endif
                        </div>
                        </div>
            
            
        </nav>
        
        </header>
                <!-- Drawer Menu -->
               
        <div
            x-data="{
                open: false,
                usedKeyboard: false,
                init() {
                    this.$watch('open', (value) => {
                        if (value && this.$refs.closeButton) {
                            this.$refs.closeButton.focus();
                        }

                        document.body.classList.toggle('h-screen', value);
                        document.body.classList.toggle('overflow-hidden', value);
                    });
                },
            }"
            x-cloak
            @open-menu.window="open = $event.detail.open"
            @keydown.window.tab="usedKeyboard = true"
            @keydown.escape="open = false"
            x-init="init()"
        >
         <div x-show.transition.opacity.duration.500="open" @click="open = false" class="fixed z-40 inset-0 bg-opacity-25 blur" style="display: none;"></div>

            <div class="fixed inset-0 z-50 transition duration-300 left-0 top-0 transform w-full max-w-xs h-screen bg-secondary pb-12 shadow-xl overflow-y-auto -translate-x-full" :class="{ 'translate-x-0': open, '-translate-x-full': !open }">
                <div class="relative flex w-full flex-col">
                    <div class="flex flex-row-reverse items-center justify-between p-4">
                           <button type="button" class="-m-2 inline-flex items-center justify-center rounded-md p-2 text-murky-400 hover:ring-primary-500 hover:ring-offset-2" @click="open = false" x-ref="closeButton" :class="{'focus:outline-none': !usedKeyboard}" tabindex="0">
                            <span class="sr-only">Close menu</span>
                             <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-6 w-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                        <button type="button" class="absolute right-4 top-4 rounded-lg opacity-70 ring-offset-white transition-opacity hover:opacity-100  focus:outline-none disabled:pointer-events-none data-[state=open]:bg-slate-100 dark:data-[state=open]:bg-slate-800 text-red-500 text-2xl bg-primary-500 p-1.5"  @click="open = false" x-ref="closeButton" :class="{'focus:outline-none': !usedKeyboard}" tabindex="0"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x h-4 w-4"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg><span class="sr-only">Close</span></button>
                        <div class="flex">
                            <a href="/" style="outline: none;">
                                <span class="sr-only"> {{ $config->judul_web }}.</span>
                                <img src="{{ $publicLogoHeader }}"  class="h-7 w-auto" width="100" height="43" style="color: transparent;" alt="{{ $config->judul_web }}." />
                            </a>
                        </div>
                    </div>
                    <div class="space-y-2 border-y border-murky-800 p-4">
                        <div>
                            <a class="group flex items-center justify-between rounded-md py-2 px-4 font-medium text-white hover:bg-murky-700" href="{{url('')}}" style="outline: none;">
                                <span>Beranda</span>
                                <svg width="20" height="20" viewBox="0 0 25 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M16.5209 6.87109H8.47891C5.67599 6.87109 3.91895 8.8558 3.91895 11.6636V16.206C3.91895 19.0148 5.66724 20.9985 8.47891 20.9985H16.5199C19.3316 20.9985 21.0818 19.0148 21.0818 16.206V11.6636C21.0818 8.8558 19.3316 6.87109 16.5209 6.87109Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path><path opacity="0.4" d="M9.38477 11.1445C9.45871 11.8684 9.79338 12.5173 10.275 13.0096C10.8509 13.5748 11.639 13.928 12.5019 13.928C14.116 13.928 15.443 12.7119 15.6191 11.1445" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path><path opacity="0.4" d="M16.3702 6.87115C16.3702 4.7337 14.6375 3 12.4991 3C10.3616 3 8.62891 4.7337 8.62891 6.87115" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                            </a>
                        </div>
                        <div>
                            <a class="group flex items-center justify-between rounded-md py-2 px-4 font-medium text-white hover:bg-murky-700" href="{{ route('cari') }}" style="outline: none;">
                                <span>Cek Transaksi</span>
                                <svg width="20" height="20" viewBox="0 0 25 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M21.129 10.3728L21.1106 8.05801C21.1038 5.1517 19.2891 3.10258 16.3799 3.10939L8.05695 3.12982C5.15647 3.13664 3.34282 5.19353 3.34963 8.10082L3.36812 15.9421C3.37493 18.8485 5.18955 20.8976 8.09879 20.8908L12.0121 20.8713" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path><path fill-rule="evenodd" clip-rule="evenodd" d="M19.6179 18.5669C18.3705 19.8143 16.3477 19.8143 15.1003 18.5669C13.852 17.3195 13.852 15.2967 15.1003 14.0493C16.3477 12.8019 18.3705 12.8019 19.6179 14.0493C20.8653 15.2967 20.8653 17.3195 19.6179 18.5669Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path><path d="M19.6172 18.5669L21.3483 20.2978" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path><path opacity="0.4" d="M15.0331 3.11035L15.0497 9.69846L12.2523 8.78677L9.43747 9.715L9.42969 3.13468" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                            </a>
                        </div>
                        <div>
                            <a class="group flex items-center justify-between rounded-md py-2 px-4 font-medium text-white hover:bg-murky-700" href="{{ route('price') }}" style="outline: none;">
                                <span>Daftar Harga</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
  <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
</svg>

                            </a>
                        </div>
                        <div>
                            <a class="group flex items-center justify-between rounded-md py-2 px-4 font-medium text-white hover:bg-murky-700" href="{{ route('leaderboardd') }}" style="outline: none;">
                                <span>Leaderboard</span>
                                <svg width="20" height="20" viewBox="0 0 25 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9.67637 14.6445H5.44301C4.66172 14.6445 4.02832 15.2779 4.02832 16.0592V19.5852C4.02832 20.3636 4.66464 20.9999 5.44301 20.9999H19.5578C20.3361 20.9999 20.9725 20.3636 20.9725 19.5852V17.9419C20.9725 17.1606 20.3391 16.5272 19.5578 16.5272H15.3244" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path><path opacity="0.4" fill-rule="evenodd" clip-rule="evenodd" d="M12.8887 3.2372L13.5357 4.53124C13.5999 4.6587 13.7225 4.74627 13.8646 4.7667L15.3133 4.97492C15.5478 5.00605 15.7132 5.22108 15.6821 5.45653C15.6694 5.55188 15.6247 5.6414 15.5546 5.70853L14.5067 6.71555C14.4036 6.81284 14.3569 6.95587 14.3812 7.09597L14.6293 8.51747C14.6673 8.75488 14.5057 8.97768 14.2683 9.01563C14.1759 9.0312 14.0796 9.01466 13.9959 8.97087L12.7019 8.3005C12.5744 8.23434 12.4236 8.23434 12.2961 8.3005L11.0011 8.97185C10.789 9.08374 10.5254 9.00298 10.4135 8.79087C10.3687 8.7072 10.3531 8.61088 10.3677 8.51747L10.6158 7.09695C10.6402 6.95684 10.5935 6.81381 10.4903 6.71555L9.44148 5.7095C9.27121 5.54507 9.26635 5.27362 9.43078 5.10237C9.49791 5.03329 9.58742 4.98756 9.68277 4.97492L11.1315 4.7667C11.2736 4.74724 11.3962 4.6587 11.4604 4.53124L12.1093 3.2372C12.2203 3.02218 12.4859 2.93753 12.7009 3.04942C12.7817 3.09126 12.8468 3.15645 12.8887 3.2372Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path><path d="M15.3238 20.9994V13.0006C15.3238 12.2193 14.6904 11.5859 13.9091 11.5859H11.0905C10.3121 11.5859 9.67578 12.2223 9.67578 13.0006V20.9994" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                            </a>
                        <div class="space-y-2" x-data="{ reportsOpen: false }">
                            <button class="group flex w-full items-center justify-between bg-transparent rounded-md py-2 px-4 font-medium text-white hover:bg-murky-700" type="button" aria-expanded="false" @click="reportsOpen = !reportsOpen">
                                <span>Kalkulator</span>
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24  24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-5 w-5">
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M15.75 15.75V18m-7.5-6.75h.008v.008H8.25v-.008zm0 2.25h.008v.008H8.25V13.5zm0 2.25h.008v.008H8.25v-.008zm0 2.25h.008v.008H8.25V18zm2.498-6.75h.007v.008h-.007v-.008zm0 2.25h.007v.008h-.007V13.5zm0 2.25h.007v.008h-.007v-.008zm0 2.25h.007v.008h-.007V18zm2.504-6.75h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V13.5zm0 2.25h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V18zm2.498-6.75h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V13.5zM8.25 6h7.5v2.25h-7.5V6zM12 2.25c-1.892 0-3.758.11-5.593.322C5.307 2.7 4.5 3.65 4.5 4.757V19.5a2.25 2.25 0 002.25 2.25h10.5a2.25 2.25 0 002.25-2.25V4.757c0-1.108-.806-2.057-1.907-2.185A48.507 48.507 0 0012 2.25z"
                ></path>
            </svg>
                            </button>
                            <div class="ml-8 space-y-1 transform transition duration-300 ease-in-out" x-cloak x-show="reportsOpen" x-collapse x-collapse.duration.500ms>
                                <a href="{{ route('hitungwr') }}" class="group flex w-full items-center justify-between rounded-md py-2 px-4 font-medium text-white hover:bg-murky-700" style="outline: none;">
                                    Win Rate
                                </a>
                                <a href="{{ route('hitungpointmw') }}" class="group flex w-full items-center justify-between rounded-md py-2 px-4 font-medium text-white hover:bg-murky-700" style="outline: none;">
                                    Magic Wheel
                                </a>
                                <a href="{{ route('hitungpointzodiac') }}" class="group flex w-full items-center justify-between rounded-md py-2 px-4 font-medium text-white hover:bg-murky-700" style="outline: none;">
                                    Zodiac
                                </a>
                            </div>
                        </div>
                    </div>
                   <div class="space-y-2 p-4">
    @if($publicHasMemberMenu)
            <div>
                    @foreach($publicDashboardMenu as $menuItem)
                        <a class="group flex items-center justify-between rounded-md py-2 px-4 font-medium text-white hover:bg-murky-700 outline-none" href="{{ $menuItem['href'] }}">
                            <span>{{ $menuItem['label'] }}</span>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="hidden h-5 w-5 group-hover:block">
                                <path fill-rule="evenodd" d="M5 10a.75.75 0 01.75-.75h6.638L10.23 7.29a.75.75 0 111.04-1.08l3.5 3.25a.75.75 0 010 1.08l-3.5 3.25a.75.75 0 11-1.04-1.08l2.158-1.96H5.75A.75.75 0 015 10z" clip-rule="evenodd"></path>
                            </svg>
                        </a>
                    @endforeach
                    <form action="{{ route('logout') }}" method="POST" id="logout">
    @csrf 
    <button type="submit" class="group flex w-full items-center justify-between bg-transparent rounded-md py-2 px-4 font-medium text-white hover:bg-murky-700">
        <span>Keluar</span>
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="hidden h-5 w-5 group-hover:block">
            <path fill-rule="evenodd" d="M5 10a.75.75 0 01.75-.75h6.638L10.23 7.29a.75.75 0 111.04-1.08l3.5 3.25a.75.75 0 010 1.08l-3.5 3.25a.75.75 0 11-1.04-1.08l2.158-1.96H5.75A.75.75 0 015 10z" clip-rule="evenodd"></path>
        </svg>
    </button>
</form>
                    
                    <div>
                </div>
            </div> 
            
            
          
    
    
<div>
    </div>

    @else
        <div>
            <a class="group flex items-center justify-between rounded-md py-2 px-4 font-medium text-white hover:bg-murky-700 outline-none" href="{{ route('login') }}">
                <span>Masuk</span>
                <svg width="18" height="18" viewBox="0 0 25 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M14.791 12.1207H2.75" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                    <path d="M11.8643 9.20471L14.7923 12.1207L11.8643 15.0367" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                    <path opacity="0.4" d="M7.25879 7.62988C7.58879 4.04988 8.92879 2.74988 14.2588 2.74988C21.3598 2.74988 21.3598 5.05988 21.3598 11.9999C21.3598 18.9399 21.3598 21.2499 14.2588 21.2499C8.92879 21.2499 7.58879 19.9499 7.25879 16.3699" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
            </a>
        </div>
        <div>
            <a class="group flex items-center justify-between rounded-md py-2 px-4 font-medium text-white hover:bg-murky-700 outline-none" href="{{ route('register') }}">
                <span>Daftar</span>
                <svg width="18" height="18" viewBox="0 0 24 24" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                    <title>Iconly/Two-tone/Add User</title>
                    <g id="Iconly/Two-tone/Add-User" stroke="none" stroke-width="1.5" fill="none" fill-rule="evenodd" stroke-linecap="round" stroke-linejoin="round">
                        <g id="Add-User" transform="translate(2.000000, 2.000000)" stroke="currentColor" stroke-width="1.5">
                            <path d="M7.8766,13.2062 C4.0326,13.2062 0.7496,13.7872 0.7496,16.1152 C0.7496,18.4432 4.0126,19.0452 7.8766,19.0452 C11.7216,19.0452 15.0036,18.4632 15.0036,16.1362 C15.0036,13.8092 11.7416,13.2062 7.8766,13.2062 Z" id="Stroke-1"></path>
                            <path d="M7.8766,9.8859 C10.3996,9.8859 12.4446,7.8409 12.4446,5.3179 C12.4446,2.7949 10.3996,0.7499 7.8766,0.7499 C5.3546,0.7499 3.30957019,2.7949 3.30957019,5.3179 C3.3006,7.8319 5.3306,9.8769 7.8456,9.8859 L7.8766,9.8859 Z" id="Stroke-3" opacity="0.400000006"></path>
                            <line x1="17.2037" y1="6.6691" x2="17.2037" y2="10.6791" id="Stroke-5"></line>
                            <line x1="19.2496" y1="8.674" x2="15.1596" y2="8.674" id="Stroke-7"></line>
                        </g>
                    </g>
                </svg>
            </a>
        </div>
    @endif
</div>

                    
                </div>
            </div>
        </div>
        <!-- Drawer Menu End -->
