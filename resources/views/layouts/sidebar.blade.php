<!-- ========== App Menu ========== -->
<div class="app-menu navbar-menu">
    <!-- LOGO -->
    <div class="navbar-brand-box">
        <!-- Dark Logo-->
        <a href="{{ url('/') }}" target="_blank" class="logo logo-dark">
            <span class="logo-sm">
                <img src="{{ !$config ? '' : $config->logo_header }}" alt="{{ $config->judul_web }}" height="22">
            </span>
            <span class="logo-lg">
                <img src="{{ !$config ? '' : $config->logo_header }}" alt="{{ $config->judul_web }}" height="22">17">
            </span>
        </a>
        <!-- Light Logo-->
        <a href="{{ url('/') }}" target="_blank" class="logo logo-light">
            <span class="logo-sm">
                <img src="{{ !$config ? '' : $config->logo_header }}" alt="{{ $config->judul_web }}" height="22">
            </span>
            <span class="logo-lg">
                <img src="{{ !$config ? '' : $config->logo_header }}" alt="{{ $config->judul_web }}" height="22">17">
            </span>
        </a>
        <button type="button" class="btn btn-sm p-0 fs-20 header-item float-end btn-vertical-sm-hover" id="vertical-hover">
            <i class="ri-record-circle-line"></i>
        </button>
    </div>

    <div class="dropdown sidebar-user m-1 rounded">
        <button type="button" class="btn material-shadow-none" id="page-header-user-dropdown" data-bs-toggle="dropdown"
            aria-haspopup="true" aria-expanded="false">
            <span class="d-flex align-items-center gap-2">
                <img class="rounded header-profile-user" src="assets/admin/images/users/avatar-1.jpg" alt="{{ $config->judul_web }}" height="22"> }}">
                <span class="text-start">
                    <span class="d-block fw-medium sidebar-user-name-text">{{ Str::title(Auth()->user()->username) }}</span>
                    <span class="d-block fs-14 sidebar-user-name-sub-text"><i
                            class="ri ri-circle-fill fs-10 text-success align-baseline"></i> <span
                            class="align-middle">Online</span></span>
                </span>
            </span>
        </button>
        <div class="dropdown-menu dropdown-menu-end">
            <!-- item-->
            <h6 class="dropdown-header">Welcome {{ Str::title(Auth()->user()->username) }}!</h6>
            <a class="dropdown-item" href="#"><i
                    class="mdi mdi-account-circle text-muted fs-16 align-middle me-1"></i> <span
                    class="align-middle">Profile</span></a>
            {{-- <a class="dropdown-item" href="apps-chat.html"><i
                    class="mdi mdi-message-text-outline text-muted fs-16 align-middle me-1"></i> <span
                    class="align-middle">Messages</span></a>
            <a class="dropdown-item" href="apps-tasks-kanban.html"><i
                    class="mdi mdi-calendar-check-outline text-muted fs-16 align-middle me-1"></i> <span
                    class="align-middle">Taskboard</span></a>
            <a class="dropdown-item" href="pages-faqs.html"><i
                    class="mdi mdi-lifebuoy text-muted fs-16 align-middle me-1"></i> <span
                    class="align-middle">Help</span></a> --}}
            <div class="dropdown-divider"></div>
            <a class="dropdown-item" href=""><i
                    class="mdi mdi-wallet text-muted fs-16 align-middle me-1"></i> <span class="align-middle">Balance :
                    <b>Rp {{ number_format(Auth::user()->balance, 0, ',', '.') }}</b></span></a>
            <a class="dropdown-item" href="{{ url('setting/web') }}"><span
                    class="badge bg-success-subtle text-success mt-1 float-end">New</span><i
                    class="mdi mdi-cog-outline text-muted fs-16 align-middle me-1"></i> <span
                    class="align-middle">Settings</span></a>
            {{-- <a class="dropdown-item" href="auth-lockscreen-basic.html"><i
                    class="mdi mdi-lock text-muted fs-16 align-middle me-1"></i> <span
                    class="align-middle">Lock screen</span></a> --}}
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class='dropdown-item'>
                    <i class="mdi mdi-logout text-muted fs-16 align-middle me-1"></i>
                    <span class="align-middle" data-key="t-logout">Logout</span>
                </button>
            </form>
        </div>
    </div>
    <div id="scrollbar">
        <div class="container-fluid">
            <div id="two-column-menu">
            </div>
            <ul class="navbar-nav" id="navbar-nav">

               <li class="menu-title"><span data-key="t-menu">Menu</span></li>
               <!-- Dashboard -->
               <!-- Dashboard -->
                <li class="nav-item">
                    <a class="nav-link menu-link {{ Request::is('dashboard') ? 'active' : '' }}" 
                    href="{{ route('dashboard') }}">
                        <i class="ri-dashboard-2-line"></i> 
                        <span data-key="t-widgets">Dashboards</span>
                    </a>
                </li>

                <!-- Pesanan -->
                <li class="nav-item">
                    <a class="nav-link menu-link {{ Request::is('pesanan*') || Request::is('data/joki*') || Request::is('data/giftskin*') ? 'active' : '' }}" 
                    href="#sidebarDashboards" data-bs-toggle="collapse" role="button" 
                    aria-expanded="{{ Request::is('pesanan*') || Request::is('data/joki*') || Request::is('data/giftskin*') ? 'true' : 'false' }}" 
                    aria-controls="sidebarDashboards">
                        <i class="ri-shopping-cart-2-line"></i> 
                        <span data-key="t-dashboards">Kelola Pesanan</span>
                    </a>
                    <div class="collapse menu-dropdown {{ Request::is('pesanan*') || Request::is('data/joki*') || Request::is('data/giftskin*') ? 'show' : '' }}" 
                        id="sidebarDashboards">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="{{ route('pesanan') }}" 
                                class="nav-link {{ Request::is('pesanan') ? 'active' : '' }}">Semua Pesanan</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ url('data/joki') }}" 
                                class="nav-link {{ Request::is('data/joki') ? 'active' : '' }}">Pesanan Joki</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ url('data/giftskin') }}" 
                                class="nav-link {{ Request::is('data/giftskin') ? 'active' : '' }}">Pesanan Gift Skin</a>
                            </li>
                        </ul>
                    </div>
                </li>

                 <!-- Provider -->
                <li class="nav-item">
                    <a class="nav-link menu-link {{ Request::is('digiflazz*') || Request::is('bangjeff*') || Request::is('topupedia*') ? 'active' : '' }}" href="#sidebarProvider" data-bs-toggle="collapse"
                        role="button" aria-expanded="{{ Request::is('digiflazz*') || Request::is('bangjeff*') || Request::is('topupedia*') ? 'active' : '' }}" aria-controls="sidebarProvider">
                        <i class="ri-fire-line"></i> <span data-key="t-provider">Provider</span>
                    </a>
                    <div class="collapse menu-dropdown {{ Request::is('digiflazz*') || Request::is('bangjeff*') || Request::is('topupedia*') ? 'show' : '' }}" id="sidebarProvider">
                        <ul class="nav nav-sm flex-column">
                            
                            <li class="nav-item">
                                <a href="#sidebarDigi" class="nav-link {{ Request::is('digiflazz*') ? 'active' : '' }}" data-bs-toggle="collapse"
                                    role="button" aria-expanded="{{ Request::is('digiflazz*') ? 'active' : '' }}" aria-controls="sidebarDigi"
                                    data-key="digiflazz"> Digiflazz
                                </a>
                                <div class="collapse menu-dropdown {{ Request::is('digiflazz*') ? 'show' : '' }}" id="sidebarDigi">
                                    <ul class="nav nav-sm flex-column">
                                        <li class="nav-item">
                                            <a href="{{ route('digiflazz.prices') }}" class="nav-link {{ Request::is('digiflazz*') ? 'active' : '' }}" data-key="cekproduk">
                                                Cek Produk </a>
                                        </li>
                                    </ul>
                                </div>
                            </li>
                            <li class="nav-item">
                                <a href="#sidebarBangJeff" class="nav-link {{ Request::is('bangjeff*') ? 'active' : '' }}" data-bs-toggle="collapse"
                                    role="button" aria-expanded="false" aria-controls="sidebarBangJeff"
                                    data-key="t-bangjeff"> Bangjeff
                                </a>
                                <div class="collapse menu-dropdown {{ Request::is('bangjeff*') ? 'show' : '' }}" id="sidebarBangJeff">
                                    <ul class="nav nav-sm flex-column">
                                        <li class="nav-item">
                                            <a href="{{ route('bangjeff.balance') }}" class="nav-link {{ Request::is('bangjeff/balance') ? 'active' : '' }}" data-key="ceksaldo">
                                                Cek Saldo </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ route('bangjeff.product') }}" class="nav-link {{ Request::is('bangjeff/product') ? 'active' : '' }}" data-key="cekproduk">
                                                Cek Produk </a>
                                        </li>
                                    </ul>
                                </div>
                            </li>
                            <li class="nav-item">
                                <a href="#sidebarTopuPedia" class="nav-link {{ Request::is('topupedia*') ? 'active' : '' }}" data-bs-toggle="collapse"
                                    role="button" aria-expanded="false" aria-controls="sidebarTopuPedia"
                                    data-key="t-opupedia"> Topupedia
                                </a>
                                <div class="collapse menu-dropdown {{ Request::is('topupedia*') ? 'show' : '' }}" id="sidebarTopuPedia">
                                    <ul class="nav nav-sm flex-column">
                                        <li class="nav-item">
                                            <a href="{{ route('topupedia.balance') }}" class="nav-link {{ Request::is('topupedia/balance') ? 'active' : '' }}" data-key="ceksaldo">
                                                Cek Saldo </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ route('topupedia.product') }}" class="nav-link {{ Request::is('topupedia/product') ? 'active' : '' }}" data-key="ceksaldo">
                                                Cek Produk </a>
                                        </li>
                                    </ul>
                                </div>
                            </li>
                        </ul>
                    </div>
                </li>

                <!-- Member -->
                <li class="nav-item">
                    <a class="nav-link menu-link {{ Request::is('user-deposit*') || Request::is('member*') ? 'active' : '' }}" 
                    href="#sidebarMembers" data-bs-toggle="collapse" role="button" 
                    aria-expanded="{{ Request::is('user-deposit*') || Request::is('member*') ? 'active' : '' }}" 
                    aria-controls="sidebarMembers">
                        <i class="ri-group-line"></i>
                        <span data-key="t-member">Member</span>
                    </a>
                    <div class="collapse menu-dropdown {{ Request::is('user-deposit*') || Request::is('member*') ? 'show' : '' }}" 
                        id="sidebarMembers">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="{{ url('member') }}" 
                                class="nav-link {{ Request::is('member*') ? 'active' : '' }}">Kelola Members</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ url('user-deposit') }}" 
                                class="nav-link {{ Request::is('user-deposit*') ? 'active' : '' }}">Kelola Deposit</a>
                            </li>
                            
                        </ul>
                    </div>
                </li>

                <li class="menu-title"><span data-key="t-menu">Settings</span></li>
                
                <!-- Konfigurasi -->
                <li class="nav-item">
                    <a class="nav-link menu-link {{ Request::is('berita*') || Request::is('method*') || Request::is('setting/web*') ? 'active' : '' }}" 
                    href="#sidebarKonfigurasi" data-bs-toggle="collapse" role="button" 
                    aria-expanded="{{ Request::is('berita*') || Request::is('method*') || Request::is('setting/web*') ? 'active' : '' }}" 
                    aria-controls="sidebarKonfigurasi">
                        <i class="ri-settings-line"></i>
                        <span data-key="t-config">Konfigurasi</span>
                    </a>
                    <div class="collapse menu-dropdown {{ Request::is('berita*') || Request::is('method*') || Request::is('setting/web*') ? 'show' : '' }}" 
                        id="sidebarKonfigurasi">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="{{ url('berita') }}" 
                                class="nav-link {{ Request::is('berita*') ? 'active' : '' }}">Banner & Popup</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ url('method') }}" 
                                class="nav-link {{ Request::is('method*') ? 'active' : '' }}">Pembayaran</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ url('setting/web') }}" 
                                class="nav-link {{ Request::is('setting/web*') ? 'active' : '' }}">Website</a>
                            </li>
                        </ul>
                    </div>
                </li>

                <li class="menu-title"><span data-key="t-menu">Produk</span></li>

                <!-- Product -->
                <li class="nav-item">
                    <a class="nav-link menu-link {{ Request::is('kategori*') || Request::is('layanan*') || Request::is('paket*') ? 'active' : '' }}" 
                    href="#sidebarProducts" data-bs-toggle="collapse" role="button" 
                    aria-expanded="{{ Request::is('kategori*') || Request::is('layanan*') || Request::is('paket*') ? 'active' : '' }}" 
                    aria-controls="sidebarProducts">
                    <i class="ri-box-3-line"></i>
                        <span data-key="t-product">Produk</span>
                    </a>
                    <div class="collapse menu-dropdown {{ Request::is('kategori*') || Request::is('layanan*') || Request::is('paket*') ? 'show' : '' }}" 
                        id="sidebarProducts">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="{{ url('kategori') }}" 
                                class="nav-link {{Request::is('kategori*') ? 'active' : '' }}">Kategori</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ url('layanan') }}" 
                                class="nav-link {{ Request::is('layanan*') ? 'active' : '' }}">Layanan</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ url('paket') }}" 
                                class="nav-link {{ Request::is('paket*') ? 'active' : '' }}">Paket Layanan</a>
                            </li>
                        </ul>
                    </div>
                </li>

                <!-- Rating -->
                <li class="nav-item">
                    <a class="nav-link menu-link {{ Request::is('rating-customer*') ? 'active' : '' }}" 
                    href="#sidebarRatings" data-bs-toggle="collapse" role="button" 
                    aria-expanded="{{ Request::is('rating-customer*') ? 'active' : '' }}" 
                    aria-controls="sidebarRatings">
                    <i class='bx bxs-star' ></i>
                        <span data-key="t-rating">Rating & Ulasan</span>
                    </a>
                    <div class="collapse menu-dropdown {{ Request::is('rating-customer*') ? 'show' : '' }}" 
                        id="sidebarRatings">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="{{ route('rating-customer') }}" 
                                class="nav-link {{ Request::is('rating-customer*') ? 'active' : '' }}">Kelola Ratings</a>
                            </li>
                            
                        </ul>
                    </div>
                </li>

                <!-- Voucher -->
                <li class="nav-item">
                    <a class="nav-link menu-link {{ Request::is('voucher*') ? 'active' : '' }}" 
                    href="#sidebarVouchers" data-bs-toggle="collapse" role="button" 
                    aria-expanded="{{ Request::is('voucher*') ? 'active' : '' }}" 
                    aria-controls="sidebarVouchers">
                    <i class='bx bxs-discount'></i>
                        <span data-key="t-voucher">Voucher</span>
                    </a>
                    <div class="collapse menu-dropdown {{ Request::is('voucher*') ? 'show' : '' }}" 
                        id="sidebarVouchers">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="{{ url('voucher') }}" 
                                class="nav-link {{ Request::is('voucher*') ? 'active' : '' }}">Kelola Voucher</a>
                            </li>
                            
                        </ul>
                    </div>
                </li>

            </ul>
        </div>
        <!-- Sidebar -->
    </div>

    <div class="sidebar-background"></div>
</div>
<!-- Left Sidebar End -->
<!-- Vertical Overlay-->
<div class="vertical-overlay"></div>
