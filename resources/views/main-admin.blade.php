<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="utf-8" /><title>{{ !$config ? '' : $config->judul_web }}</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0"> <link rel="manifest" href="{{ url('manifest.json') }}"><meta name="theme-color" content="#ffffff"><meta name="csrf-token" content="{{ csrf_token() }}"><meta content="{{ !$config ? '' : $config->deskripsi_web }}" name="description" /><meta content="SURS" name="author" /><meta http-equiv="X-UA-Compatible" content="IE=edge" /><meta name="description" content="Dashboard admin " /><link rel="icon" type="image/x-icon" href="{{ ENV('APP_IMAGE') }}" /><link rel="preconnect" href="https://fonts.googleapis.com" /><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin /><link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet"/><link rel="stylesheet" href="../assets/vendor/fonts/boxicons.css" /><link rel="stylesheet" href="../assets/vendor/css/core.css" class="template-customizer-core-css" /><link rel="stylesheet" href="../assets/vendor/css/theme-default.css" class="template-customizer-theme-css" />

<link rel="stylesheet" href="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" /><link rel="stylesheet" href="../assets/vendor/libs/apex-charts/apex-charts.css" /><script src="../assets/vendor/js/helpers.js"></script><script src="../assets/js/config.js"></script><script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script><link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/morris.js/0.5.1/morris.css"><script src="https://code.jquery.com/jquery-3.4.1.slim.min.js" integrity="sha384-J6qa4849blE2+poT4WnyKhv5vZF5SrPo0iEjwBvKU7imGFAV0wwj1yYfoRSJoZ+n" crossorigin="anonymous"></script><link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.css" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
 <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<Style>.menu-vertical .menu-block,.menu-vertical .menu-item .menu-link{padding:.225rem 1rem}.bg-menu-theme .menu-inner>.menu-item.active:before{background:#494949}.bg-menu-theme .menu-sub>.menu-item.active>.menu-link:not(.menu-toggle):before{left:-.125rem;width:.875rem;height:.875rem}.bg-menu-theme .menu-sub>.menu-item>.menu-link:before{content:"";position:absolute;left:.375rem;width:.375rem;height:.375rem;border-radius:50%}.layout-wrapper:not(.layout-horizontal) .bg-menu-theme .menu-inner>.menu-item.active:before{content:"";position:absolute;right:0;width:.25rem;height:30px;border-radius:.375rem 0 0 .375rem}.menu-vertical,.menu-vertical .menu-block,.menu-vertical .menu-inner>.menu-header,.menu-vertical .menu-inner>.menu-item{width:15.25rem}

.bg-menu-theme .menu-link, .bg-menu-theme .menu-horizontal-prev, .bg-menu-theme .menu-horizontal-next {
    color: #ffffff;
}
.bg-menu-theme {
    background-color: #666666 !important;
    color: #ffffff;
}
.bg-footer-theme {
    background-color: #666666 !important;
    color: #ffffff;
}
.table-dark {
    --bs-table-bg: #8f8f8f;
    --bs-table-striped-bg: #626466;
    --bs-table-striped-color: #fff;
    --bs-table-active-bg: #9a9a9a;
    --bs-table-active-color: #fff;
    --bs-table-hover-bg: #8f8f8f;
    --bs-table-hover-color: #fff;
    color: #fff;
    border-color: #bdbebe;
}

.bg-navbar-theme .navbar-nav > .nav-link, .bg-navbar-theme .navbar-nav > .nav-item > .nav-link, .bg-navbar-theme .navbar-nav > .nav > .nav-item > .nav-link {
    color: #fefefe;
}

.bg-menu-theme .menu-item.open:not(.menu-item-closing) > .menu-toggle, .bg-menu-theme .menu-item.active > .menu-link {
    color: #abc0f2;
}
.bg-menu-theme .menu-inner > .menu-item.active > .menu-link {
    color: #eef70b;
    background-color: rgb(255 255 255 / 22%) !important;
}
body {
    margin: 0;
    font-family: var(--bs-body-font-family);
    font-size: var(--bs-body-font-size);
    font-weight: var(--bs-body-font-weight);
    line-height: var(--bs-body-line-height);
    color: var(--bs-body-color);
    text-align: var(--bs-body-text-align);
    background-color: #dbdbdb;
    -webkit-text-size-adjust: 100%;
    -webkit-tap-highlight-color: rgb(72 139 212 / 51%);
}
a.bg-primary:hover, a.bg-primary:focus {
  background-color: #a9a9a9 !important;
}

</Style>
</head>

  <body>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">
      <div class="layout-container">
        <!-- Menu -->

        <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
          <div class="app-brand demo">

          </div>

          <div class="menu-inner-shadow"></div>
            
                            

         <ul class="menu-inner py-1">
             
             
                            @auth
            <!-- Dashboard -->
            <li class="menu-item {{ request()->is('dashboard') ? 'active' : '' }}">
                <a href="{{ route('dashboard') }}" class="menu-link">
                    <i class="menu-icon fas fa-tachometer-alt"></i>
                    <div data-i18n="Analytics">Dashboard</div>
                </a>
            </li>
            
            <!-- Pesanan -->
            <li class="menu-item {{ request()->is('pesanan', 'data/joki') ? 'active' : '' }}">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon fas fa-shopping-cart"></i>
                    <div data-i18n="Layouts">Pesanan</div>
                </a>
            
                <ul class="menu-sub">
                    <li class="menu-item {{ request()->is('pesanan') ? 'active' : '' }}">
                        <a href="{{ route('pesanan') }}" class="menu-link">
                            <div data-i18n="Without menu">All Pesanan</div>
                        </a>
                    </li>
                    <li class="menu-item {{ request()->is('data/joki') ? 'active' : '' }}">
                        <a href="{{ url('/data/joki') }}" class="menu-link">
                            <div data-i18n="Without navbar">Pesanan JOKI & ML Vilog</div>
                        </a>
                    </li>
                    <li class="menu-item {{ request()->is('data/giftskin') ? 'active' : '' }}">
                        <a href="{{ url('/data/giftskin') }}" class="menu-link">
                            <div data-i18n="Without navbar">Pesanan Gift Skin</div>
                        </a>
                    </li>
                </ul>
            </li>
            
           <li class="menu-item {{ request()->is('digiflazz') ? 'active' : '' }} {{ request()->is('bangjeff') ? 'active' : '' }}">
    <a href="javascript:void(0);" class="menu-link menu-toggle">
        
                     <i class="menu-icon fas fa-fire"></i>
        <div data-i18n="Layouts">Provider</div>
    </a>

    <ul class="menu-sub">
        <li class="menu-item {{ request()->is('digiflazz/*') ? 'active' : '' }}">
    <a href="javascript:void(0);" class="menu-link menu-toggle">
        <div data-i18n="Without menu">Digiflazz</div>
    </a>
    <ul class="menu-sub">
        <li class="menu-item {{ request()->is('digiflazz/produk') ? 'active' : '' }}">
            <a href="{{ route('digiflazz.prices') }}" class="menu-link">
                <div data-i18n="Without menu">Cek Produk</div>
            </a>
        </li>
    </ul>
</li>

       <li class="menu-item {{ request()->is('bangjeff/*') ? 'active' : '' }}">
    <a href="javascript:void(0);" class="menu-link menu-toggle">
        <div data-i18n="Without menu">Bangjeff</div>
    </a>
    <ul class="menu-sub">
        <li class="menu-item {{ request()->is('bangjeff/balance') ? 'active' : '' }}">
            <a href="{{ route('bangjeff.balance') }}" class="menu-link">
                <div data-i18n="Without menu">Cek Saldo</div>
            </a>
        </li>
        <li class="menu-item {{ request()->is('bangjeff/product') ? 'active' : '' }}">
            <a href="{{ route('bangjeff.product') }}" class="menu-link">
                <div data-i18n="Without menu">Cek Produk</div>
            </a>
        </li>
    </ul>
</li>
       <li class="menu-item {{ request()->is('topupedia/*') ? 'active' : '' }}">
    <a href="javascript:void(0);" class="menu-link menu-toggle">
        <div data-i18n="Without menu">Topupedia</div>
    </a>
    <ul class="menu-sub">
        <li class="menu-item {{ request()->is('topupedia/balance') ? 'active' : '' }}">
            <a href="{{ route('topupedia.balance') }}" class="menu-link">
                <div data-i18n="Without menu">Cek Saldo</div>
            </a>
        </li>
        <li class="menu-item {{ request()->is('topupedia/product') ? 'active' : '' }}">
            <a href="{{ route('topupedia.product') }}" class="menu-link">
                <div data-i18n="Without menu">Cek Produk</div>
            </a>
        </li>
    </ul>
</li>

    </ul>
</li>
            <!-- Pengguna -->
            <li class="menu-item {{ request()->is('member') ? 'active' : '' }}">
                <a href="{{ route('member') }}" class="menu-link">
                     <i class="menu-icon fas fa-users"></i>
                    <div data-i18n="Analytics">Member</div>
                </a>
            </li>
            
            <!--<li class="menu-item {{ request()->is('rating-customer') ? 'active' : '' }}">-->
            <!--    <a href="{{ route('rating-customer') }}" class="menu-link">-->
            <!--        <i class="menu-icon tf-icons bx bx-star"></i>-->
            <!--        <div data-i18n="Analytics">Rating Customer</div>-->
            <!--    </a>-->
            <!--</li>-->
            
         
            
            
                    
                    
            <!-- Deposite -->
            <li class="menu-item {{ request()->routeIs('userdeposit', 'user-deposit') ? 'active' : '' }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon fas fa-piggy-bank"></i>
                <div data-i18n="Layouts">Deposit Member</div>
            </a>
            
            <ul class="menu-sub">
                <li class="menu-item {{ request()->routeIs('userdeposit') ? 'active' : '' }}">
                    <a href="{{ route('userdeposit') }}" class="menu-link">
                        <div data-i18n="Without menu">Lihat Deposit</div>
                    </a>
                </li>
            </ul>
            </li>
            
       
              <li class="menu-item {{ request()->is('kategori') ? 'active' : '' }}">
                <a href="{{ route('kategori') }}" class="menu-link">
                      <i class="menu-icon fas fa-tags"></i>
                    <div data-i18n="Analytics">Kategori</div>
                </a>
            </li>
            
            <!-- PRODUK -->
            <li class="menu-item {{ request()->routeIs('layanan') ? 'active' : '' }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon fas fa-box"></i>
                <div data-i18n="Form Elements">Produk</div>
            </a>
            <ul class="menu-sub">
                
                <li class="menu-item {{ request()->routeIs('layanan') ? 'active' : '' }}">
                    <a href="{{ route('layanan') }}" class="menu-link">
                        <div data-i18n="Basic Inputs">Layanan</div>
                    </a>
                </li>
                 <li class="menu-item {{ request()->routeIs('paket.index') ? 'active' : '' }}">
                    <a href="{{ route('paket.index') }}" class="menu-link">
                        <div data-i18n="Basic Inputs">Paket Layanan</div>
                    </a>
                </li>
               
            </ul>
            
             <li class="menu-item {{ request()->is('voucher') ? 'active' : '' }}">
                <a href="{{ route('voucher') }}" class="menu-link">
                      <i class="menu-icon fas fa-gift"></i>
                    <div data-i18n="Analytics">Voucher</div>
                </a>
            </li>
            
            </li>
               <!-- Pengaturan Web -->
                <li class="menu-item {{ request()->is('berita') ? 'active' : '' }}">
                <a href="{{ route('berita') }}" class="menu-link">
                     <i class="menu-icon fas fa-images"></i>
                    <div data-i18n="Analytics">Slider & Banner</div>
                </a>
            </li>
               
            
           <li class="menu-item {{ request()->is('tokopay') ? 'active' : '' }} {{ request()->is('bangjeff') ? 'active' : '' }}">
    <a href="javascript:void(0);" class="menu-link menu-toggle">
        
                
                     <i class="menu-icon fas fa-credit-card"></i>
        <div data-i18n="Layouts">Pembayaran</div>
    </a>

    <ul class="menu-sub">
         <li class="menu-item {{ request()->is('method') ? 'active' : '' }}">
                <a href="{{ route('method') }}" class="menu-link">
                     <i class="menu-icon fas fa-credit-card"></i>
                    <div data-i18n="Analytics">Tambah Pembayaran</div>
                </a>
            </li>
        <li class="menu-item {{ request()->is('tokopay/*') ? 'active' : '' }}">
    <a href="javascript:void(0);" class="menu-link menu-toggle">
        
                     <i class="menu-icon fas fa-credit-card"></i>
        <div data-i18n="Without menu">Tokopay</div>
    </a>
    <ul class="menu-sub">
        <li class="menu-item {{ request()->is('tokopay/informasi-akun') ? 'active' : '' }}">
            <a href="{{ route('informasi-akun') }}" class="menu-link">
                <div data-i18n="Without menu">Info Akun</div>
            </a>
        </li>
        <li class="menu-item {{ request()->is('tokopay/tarik-saldo') ? 'active' : '' }}">
            <a href="{{ route('tarik-saldo') }}" class="menu-link">
                <div data-i18n="Without menu">Tarik Saldo</div>
            </a>
        </li>
    </ul>
</li>


    </ul>
</li>

            <li class="menu-item {{ request()->is('rating-customer') ? 'active' : '' }}">
                <a href="{{ route('rating-customer') }}" class="menu-link">
                    
                     <i class="menu-icon fas fa-star"></i>
                    <div data-i18n="Analytics">Rating Customer</div>
                </a>
            </li>
<li class="menu-item {{ request()->is('news') ? 'active' : '' }}">
    <a href="#" id="menu-news" class="menu-link">
        <i class="menu-icon fas fa-newspaper"></i>
        <div data-i18n="News">News/Berita</div>
    </a>
</li>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('menu-news').addEventListener('click', function(event) {
            event.preventDefault(); // Prevent the default action (following the link)

            // Use SweetAlert (Swal) for showing a message
            Swal.fire({
                icon: 'info',
                title: 'Fitur Sedang Dalam Pengembangan',
                text: 'Maaf, fitur ini sedang dalam pengembangan.',
                confirmButtonText: 'OK'
            });
        });
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


                <li class="menu-item {{ request()->is('setting/web') ? 'active' : '' }}">
                <a href="{{ url('/setting/web') }}" class="menu-link">
                     <i class="menu-icon fas fa-cog"></i>
                    <div data-i18n="Analytics">Pengaturan Website</div>
                </a>
            </li>
               
            <!-- SETTING -->
           
            
            <!-- KONTAK -->
            <li class="menu-header small text-uppercase"><span class="menu-header-text">KONTAK KAMI</span></li>
            <li class="menu-item">
              <a
                href="https://wa.me/6288211236673"
                target="_blank"
                class="menu-link"
              >
                <i class="menu-icon tf-icons bx bx-support"></i>
                <div data-i18n="Support">Support</div>
              </a>
            </li>
              @endauth
          </ul>
        </aside>
        <!-- / Menu -->

        <!-- Layout container -->
        <div class="layout-page">
          <!-- Navbar -->

          <nav
            class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme bg-footer-theme"
            id="layout-navbar"
          >
            <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
              <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
                <i class="bx bx-menu bx-sm"></i>
              </a>
            </div>

            <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
             

              <ul class="navbar-nav flex-row align-items-center ms-auto">
                <!-- Place this tag where you want the button to render. -->
               

                <!-- User -->
                <li class="nav-item navbar-dropdown dropdown-user dropdown">
                    @auth
                  <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                   <div class="avatar avatar-online">
                  <img src="{{ ENV('APP_IMAGE') }}"  alt="" class="w-px-30 h-auto " />
                </div>
                  </a>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                      <a class="dropdown-item" href="#">
                        <div class="d-flex">
                          <div class="flex-shrink-0 me-3">
                            <div class="avatar avatar-online">
                              <img src="{{ ENV('APP_IMAGE') }}" alt class="w-px-40 h-auto rounded-circle" />
                            </div>
                          </div>
                          <div class="flex-grow-1">
                            <span class="fw-semibold d-block">{{ $config->judul_web }}</span>
                            <small class="text-muted">Admin</small>
                          </div>
                        </div>
                      </a>
                    </li>
                    <li>
                      <div class="dropdown-divider"></div>
                    </li>
                   
                    <li>
                      <a class="dropdown-item" href="{{ url('/setting/web') }}">
                        <i class="bx bx-cog me-2"></i>
                        <span class="align-middle">Settings</span>
                      </a>
                    </li>
                    <!--<li>-->
                    <!--  <a class="dropdown-item" href="#">-->
                    <!--    <span class="d-flex align-items-center align-middle">-->
                    <!--      <i class="flex-shrink-0 bx bx-credit-card me-2"></i>-->
                    <!--      <span class="flex-grow-1 align-middle">Billing</span>-->
                    <!--      <span class="flex-shrink-0 badge badge-center rounded-pill bg-danger w-px-20 h-px-20">1</span>-->
                    <!--    </span>-->
                    <!--  </a>-->
                    <!--</li>-->
                    <div class="dropdown-divider"></div>
                    <form method="POST" action="{{route ('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item notify-item">
                            <i class="bx bx-power-off me-2"></i><span>Keluar</span>
                        </button>
                    </form>
                  </ul>
                  @endauth
                </li>
                
              </ul>
              
            </div>
          </nav>

          <!-- / Navbar -->

          <!-- Content wrapper -->
          <div class="content-wrapper">
            <!-- Content -->
              @yield('content')
            <!-- / Content -->

            <!-- Footer -->
            <footer class="content-footer footer bg-footer-theme mt-5">
              <div class="container-xxl d-flex flex-wrap justify-content-between py-2 flex-md-row flex-column">
                <div class="mb-2 mb-md-0">
                  @
                  <script>
                    document.write(new Date().getFullYear());
                  </script>
                  Copyright
                  <a href="/" target="_blank" class="footer-link fw-bolder text-white">{{ $config->judul_web }}.</a>
                </div>
              </div>
            </footer>
            <!-- / Footer -->

            <div class="content-backdrop fade"></div>
          </div>
          <!-- Content wrapper -->
        </div>
        <!-- / Layout page -->
      </div>

      <!-- Overlay -->
      <div class="layout-overlay layout-menu-toggle"></div>
    </div>
    <!-- / Layout wrapper -->

  
    
    

    <!-- Core JS -->
    <!-- build:js assets/vendor/js/core.js -->
    <script src="../assets/vendor/libs/jquery/jquery.js"></script>
    <script src="../assets/vendor/libs/popper/popper.js"></script>
    <script src="../assets/vendor/js/bootstrap.js"></script>
    <script src="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>

    <script src="../assets/vendor/js/menu.js"></script>
    <!-- endbuild -->

    <!-- Vendors JS -->
    <script src="../assets/vendor/libs/apex-charts/apexcharts.js"></script>

    <!-- Main JS -->
    <script src="../assets/js/main.js"></script>



    <!-- third party js -->
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.0/jquery.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/raphael/2.1.0/raphael-min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/morris.js/0.5.1/morris.min.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script>
    <!-- third party js ends -->
    
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
    <script>
      $('#summernote').summernote({
        placeholder: 'Silahkan Isi',
        tabsize: 2,
        height: 120,
        toolbar: [
          ['style', ['style']],
          ['font', ['bold', 'underline', 'clear']],
          ['color', ['color']],
          ['para', ['ul', 'ol', 'paragraph']],
          ['table', ['table']],
          ['insert', ['link', 'picture', 'video']],
          ['view', ['fullscreen', 'codeview', 'help']]
        ]
      });
    </script>
    <script>
    $(document).ready(function() {
        $("#colorbackground").spectrum({
            preferredFormat: "hex",
            showInput: true,
            showInitial: true,
            showPalette: true,
            palette: [
                ["#ff0000", "#00ff00", "#0000ff"],
                ["#ffffff", "#000000", "#cccccc"]
            ],
            chooseText: "Pilih",
            cancelText: "Batal",
            change: function(color) {
                console.log("Warna terpilih:", color.toHexString());
            }
        });
        $("#colorInputmel").spectrum({
            preferredFormat: "hex",
            showInput: true,
            showInitial: true,
            showPalette: true,
            palette: [
                ["#ff0000", "#00ff00", "#0000ff"],
                ["#ffffff", "#000000", "#cccccc"]
            ],
            chooseText: "Pilih",
            cancelText: "Batal",
            change: function(color) {
                console.log("Warna terpilih:", color.toHexString());
            }
        });
        $("#colorInputtih").spectrum({
            preferredFormat: "hex",
            showInput: true,
            showInitial: true,
            showPalette: true,
            palette: [
                ["#ff0000", "#00ff00", "#0000ff"],
                ["#ffffff", "#000000", "#cccccc"]
            ],
            chooseText: "Pilih",
            cancelText: "Batal",
            change: function(color) {
                console.log("Warna terpilih:", color.toHexString());
            }
        });
        $("#colorInputt").spectrum({
            preferredFormat: "hex",
            showInput: true,
            showInitial: true,
            showPalette: true,
            palette: [
                ["#ff0000", "#00ff00", "#0000ff"],
                ["#ffffff", "#000000", "#cccccc"]
            ],
            chooseText: "Pilih",
            cancelText: "Batal",
            change: function(color) {
                console.log("Warna terpilih:", color.toHexString());
            }
        });
        $("#colorInputtt").spectrum({
            preferredFormat: "hex",
            showInput: true,
            showInitial: true,
            showPalette: true,
            palette: [
                ["#ff0000", "#00ff00", "#0000ff"],
                ["#ffffff", "#000000", "#cccccc"]
            ],
            chooseText: "Pilih",
            cancelText: "Batal",
            change: function(color) {
                console.log("Warna terpilih:", color.toHexString());
            }
        });
        $("#colorInputi").spectrum({
            preferredFormat: "hex",
            showInput: true,
            showInitial: true,
            showPalette: true,
            palette: [
                ["#ff0000", "#00ff00", "#0000ff"],
                ["#ffffff", "#000000", "#cccccc"]
            ],
            chooseText: "Pilih",
            cancelText: "Batal",
            change: function(color) {
                console.log("Warna terpilih:", color.toHexString());
            }
        });
    });
</script>



<!-- Load Spectrum CSS -->
<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/spectrum/1.8.1/spectrum.min.css">

<!-- Load Spectrum JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/spectrum/1.8.1/spectrum.min.js"></script>

  </body>
  
  
  
</html>