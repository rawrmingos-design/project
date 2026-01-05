@extends('layouts.master')
@section('title') {{ $config->judul_web }} - Dashboard Admin @endsection
@section('css')
    <link href="{{ URL::asset('assets/admin/libs/aos/aos.css') }}" rel="stylesheet">

@endsection
@section('content')
    <!-- Breadcrumb -->
    <div class="row">
      <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between bg-galaxy-transparent">
          <h4 class="mb-sm-0">Dashboard</h4>
          <div class="page-title-right">
            <ol class="breadcrumb m-0">
              <li class="breadcrumb-item">
                <a href="javascript: void(0);">Dashboards</a>
              </li>
              <li class="breadcrumb-item active">Dashboard</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
        <div class="col">
            <div class="h-100">
                <div class="row mb-3 pb-1">
                    <div class="col-12">
                        <div class="d-flex align-items-lg-center flex-lg-row flex-column">
                            <div class="flex-grow-1">
                                <h4 class="fs-16 mb-1">Hello welcome back, {{ Str::title(Auth()->user()->username) }}!</h4>
                                <p class="text-muted mb-0">This is what happens to your store today.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <h4 class="page-title">Laporan Harian</h4>
        <div class="col-xl-3 col-md-6">
          <!-- card -->
          <div class="card card-animate">
            <div class="card-body">
              <div class="d-flex align-items-center">
                <div class="flex-grow-1 overflow-hidden">
                  <p class="text-uppercase fw-medium text-muted text-truncate mb-0"> Pembelian Hari Ini</p>
                </div>
                {{-- <div class="flex-shrink-0">
                  <h5 class="text-success fs-14 mb-0">
                    <i class="ri-arrow-right-up-line fs-13 align-middle"></i> +16.24 %
                  </h5>
                </div> --}}
              </div>
              <div class="d-flex align-items-end justify-content-between mt-4">
                <div>
                  <h4 class="fs-22 fw-semibold ff-secondary mb-4">Rp. <span>{{ number_format($total_pembelian, 0, '.', '.') }}</span></h4>
                  <p class="mt-2 text-sm ">Jumlah Pembelian {{ $banyak_pembelian }}x </p>
                  {{-- <a href="" class="text-decoration-underline">View net earnings</a> --}}
                </div>
                <div class="avatar-sm flex-shrink-0">
                  <span class="avatar-title bg-primary-subtle rounded fs-3">
                    <i class="bx bx-dollar-circle text-primary"></i>
                  </span>
                </div>
              </div>
            </div>
            <!-- end card body -->
          </div>
          <!-- end card -->
        </div>
        <div class="col-xl-3 col-md-6">
            <!-- card -->
            <div class="card card-animate">
              <div class="card-body">
                <div class="d-flex align-items-center">
                  <div class="flex-grow-1 overflow-hidden">
                    <p class="text-uppercase fw-medium text-muted text-truncate mb-0"> Pembelian Sukses Hari Ini</p>
                  </div>
                  {{-- <div class="flex-shrink-0">
                    <h5 class="text-success fs-14 mb-0">
                      <i class="ri-arrow-right-up-line fs-13 align-middle"></i> +16.24 %
                    </h5>
                  </div> --}}
                </div>
                <div class="d-flex align-items-end justify-content-between mt-4">
                  <div>
                    <h4 class="fs-22 fw-semibold ff-secondary mb-4">Rp. <span data-target="{{ number_format($total_pembelian_success, '0', '.', '.') }}">{{ number_format($total_pembelian_success, '0', '.', '.') }}</span></h4>
                    <p class="mt-2 text-sm ">Jumlah Pembelian {{ $banyak_pembelian_success }}x </p>
                    {{-- <a href="" class="text-decoration-underline">View net earnings</a> --}}
                  </div>
                  <div class="avatar-sm flex-shrink-0">
                    <span class="avatar-title bg-success-subtle rounded fs-3">
                      <i class="bx bx-check-circle text-success"></i>
                    </span>
                  </div>
                </div>
              </div>
              <!-- end card body -->
            </div>
            <!-- end card -->
        </div>
        <!-- end col -->
        <div class="col-xl-3 col-md-6">
            <!-- card -->
            <div class="card card-animate">
              <div class="card-body">
                <div class="d-flex align-items-center">
                  <div class="flex-grow-1 overflow-hidden">
                    <p class="text-uppercase fw-medium text-muted text-truncate mb-0"> Pembelian Pending Hari Ini</p>
                  </div>
                  {{-- <div class="flex-shrink-0">
                    <h5 class="text-success fs-14 mb-0">
                      <i class="ri-arrow-right-up-line fs-13 align-middle"></i> +16.24 %
                    </h5>
                  </div> --}}
                </div>
                <div class="d-flex align-items-end justify-content-between mt-4">
                  <div>
                    <h4 class="fs-22 fw-semibold ff-secondary mb-4">Rp. <span data-target="{{ number_format($total_pembelian_pending, '0', '.', '.') }}">{{ number_format($total_pembelian_pending, '0', '.', '.') }}</span></h4>
                    <p class="mt-2 text-sm ">Jumlah Pembelian {{ $banyak_pembelian_pending }}x </p>
                    {{-- <a href="" class="text-decoration-underline">View net earnings</a> --}}
                  </div>
                  <div class="avatar-sm flex-shrink-0">
                    <span class="avatar-title bg-warning-subtle rounded fs-3">
                      <i class="bx bx-time-five text-warning"></i>
                    </span>
                  </div>
                </div>
              </div>
              <!-- end card body -->
            </div>
            <!-- end card -->
        </div>
        <!-- end col -->
        <div class="col-xl-3 col-md-6">
            <!-- card -->
            <div class="card card-animate">
              <div class="card-body">
                <div class="d-flex align-items-center">
                  <div class="flex-grow-1 overflow-hidden">
                    <p class="text-uppercase fw-medium text-muted text-truncate mb-0"> Pembelian Gagal Hari Ini</p>
                  </div>
                  {{-- <div class="flex-shrink-0">
                    <h5 class="text-success fs-14 mb-0">
                      <i class="ri-arrow-right-up-line fs-13 align-middle"></i> +16.24 %
                    </h5>
                  </div> --}}
                </div>
                <div class="d-flex align-items-end justify-content-between mt-4">
                  <div>
                    <h4 class="fs-22 fw-semibold ff-secondary mb-4">Rp. <span  data-target="{{ number_format($total_pembelian_batal, '0', '.', '.') }}">{{ number_format($total_pembelian_batal, '0', '.', '.') }}</span></h4>
                    <p class="mt-2 text-sm ">Jumlah Pembelian {{ $banyak_pembelian_batal }}x </p>
                    {{-- <a href="" class="text-decoration-underline">View net earnings</a> --}}
                  </div>
                  <div class="avatar-sm flex-shrink-0">
                    <span class="avatar-title bg-danger-subtle rounded fs-3">
                      <i class="bx bx-x-circle text-danger"></i>
                    </span>
                  </div>
                </div>
              </div>
              <!-- end card body -->
            </div>
            <!-- end card -->
        </div>
        <!-- end col -->
        <h4 class="page-title">Seluruh Laporan</h4>
        <div class="col-xl-3 col-md-6">
          <!-- card -->
          <div class="card card-animate">
            <div class="card-body">
              <div class="d-flex align-items-center">
                <div class="flex-grow-1 overflow-hidden">
                  <p class="text-uppercase fw-medium text-muted text-truncate mb-0"> Semua Pembelian</p>
                </div>
                {{-- <div class="flex-shrink-0">
                  <h5 class="text-success fs-14 mb-0">
                    <i class="ri-arrow-right-up-line fs-13 align-middle"></i> +16.24 %
                  </h5>
                </div> --}}
              </div>
              <div class="d-flex align-items-end justify-content-between mt-4">
                <div>
                  <h4 class="fs-22 fw-semibold ff-secondary mb-4">Rp. <span  data-target="{{ number_format($total_keseluruhan_pembelian, '0', '.', '.') }}">{{ number_format($total_keseluruhan_pembelian, '00', '.', '.') }}</span></h4>
                  <p class="mt-2 text-sm ">Jumlah Pembelian {{ $banyak_keseluruhan_pembelian }}x </p>
                  {{-- <a href="" class="text-decoration-underline">View net earnings</a> --}}
                </div>
                <div class="avatar-sm flex-shrink-0">
                  <span class="avatar-title bg-primary-subtle rounded fs-3">
                    <i class="bx bx-cart-alt text-primary"></i>
                  </span>
                </div>
              </div>
            </div>
            <!-- end card body -->
          </div>
          <!-- end card -->
        </div>
        <div class="col-xl-3 col-md-6">
            <!-- card -->
            <div class="card card-animate">
              <div class="card-body">
                <div class="d-flex align-items-center">
                  <div class="flex-grow-1 overflow-hidden">
                    <p class="text-uppercase fw-medium text-muted text-truncate mb-0"> Semua Pembelian Sukses</p>
                  </div>
                  {{-- <div class="flex-shrink-0">
                    <h5 class="text-success fs-14 mb-0">
                      <i class="ri-arrow-right-up-line fs-13 align-middle"></i> +16.24 %
                    </h5>
                  </div> --}}
                </div>
                <div class="d-flex align-items-end justify-content-between mt-4">
                  <div>
                    <h4 class="fs-22 fw-semibold ff-secondary mb-4">Rp. <span  data-target="{{ number_format($total_keseluruhan_pembelian_berhasil, '0', '.', '.') }}">{{ number_format($total_keseluruhan_pembelian_berhasil, '0', '.', '.') }}</span></h4>
                    <p class="mt-2 text-sm ">Jumlah Pembelian {{ $banyak_keseluruhan_pembelian_berhasil }}x </p>
                    {{-- <a href="" class="text-decoration-underline">View net earnings</a> --}}
                  </div>
                  <div class="avatar-sm flex-shrink-0">
                    <span class="avatar-title bg-success-subtle rounded fs-3">
                      <i class="bx bx-check-circle text-success"></i>
                    </span>
                  </div>
                </div>
              </div>
              <!-- end card body -->
            </div>
            <!-- end card -->
        </div>
        <!-- end col -->
        <div class="col-xl-3 col-md-6">
            <!-- card -->
            <div class="card card-animate">
              <div class="card-body">
                <div class="d-flex align-items-center">
                  <div class="flex-grow-1 overflow-hidden">
                    <p class="text-uppercase fw-medium text-muted text-truncate mb-0"> Semua Pembelian Pending</p>
                  </div>
                  {{-- <div class="flex-shrink-0">
                    <h5 class="text-success fs-14 mb-0">
                      <i class="ri-arrow-right-up-line fs-13 align-middle"></i> +16.24 %
                    </h5>
                  </div> --}}
                </div>
                <div class="d-flex align-items-end justify-content-between mt-4">
                  <div>
                    <h4 class="fs-22 fw-semibold ff-secondary mb-4">Rp. <span  data-target="{{ number_format($total_keseluruhan_pembelian_pending, '0', '.', '.') }}">{{ number_format($total_keseluruhan_pembelian_pending, '0', '.', '.') }}</span></h4>
                    <p class="mt-2 text-sm ">Jumlah Pembelian {{ $banyak_keseluruhan_pembelian_pending }}x </p>
                    {{-- <a href="" class="text-decoration-underline">View net earnings</a> --}}
                  </div>
                  <div class="avatar-sm flex-shrink-0">
                    <span class="avatar-title bg-warning-subtle rounded fs-3">
                      <i class="bx bx-time-five text-warning"></i>
                    </span>
                  </div>
                </div>
              </div>
              <!-- end card body -->
            </div>
            <!-- end card -->
        </div>
        <!-- end col -->
        <div class="col-xl-3 col-md-6">
            <!-- card -->
            <div class="card card-animate">
              <div class="card-body">
                <div class="d-flex align-items-center">
                  <div class="flex-grow-1 overflow-hidden">
                    <p class="text-uppercase fw-medium text-muted text-truncate mb-0"> Semua Pembelian Gagal</p>
                  </div>
                  {{-- <div class="flex-shrink-0">
                    <h5 class="text-success fs-14 mb-0">
                      <i class="ri-arrow-right-up-line fs-13 align-middle"></i> +16.24 %
                    </h5>
                  </div> --}}
                </div>
                <div class="d-flex align-items-end justify-content-between mt-4">
                  <div>
                    <h4 class="fs-22 fw-semibold ff-secondary mb-4">Rp. <span  data-target="{{ number_format($total_keseluruhan_pembelian_batal, '0', '.', '.') }}">{{ number_format($total_keseluruhan_pembelian_batal, '0', '.', '.') }}</span></h4>
                    <p class="mt-2 text-sm ">Jumlah Pembelian {{ $banyak_keseluruhan_pembelian_batal }}x </p>
                    {{-- <a href="" class="text-decoration-underline">View net earnings</a> --}}
                  </div>
                  <div class="avatar-sm flex-shrink-0">
                    <span class="avatar-title bg-danger-subtle rounded fs-3">
                      <i class="bx bx-x-circle text-danger"></i>
                    </span>
                  </div>
                </div>
              </div>
              <!-- end card body -->
            </div>
            <!-- end card -->
        </div>
        <!-- end col -->
        <h4 class="page-title">Keseluruhan Keuntungan</h4>
        <div class="col-xl-12 col-md-6">
            <!--card-->
            <div class="card card-animate">
              <div class="card-body">
                <div class="d-flex align-items-center">
                  <div class="flex-grow-1 overflow-hidden">
                    <p class="text-uppercase fw-medium text-muted text-truncate mb-0"> Keuntungan Hari Ini</p>
                  </div>
                  {{-- <div class="flex-shrink-0">
                    <h5 class="text-success fs-14 mb-0">
                      <i class="ri-arrow-right-up-line fs-13 align-middle"></i> +16.24 %
                    </h5>
                  </div> --}}
                </div>
                <div class="d-flex align-items-end justify-content-between mt-4">
                  <div>
                    <h4 class="fs-22 fw-semibold ff-secondary mb-4">Rp. {{ number_format($today_profit, '0','.','.') }}</h4>
                    <p class="mt-2 text-sm ">Total Keuntungan Pada 
                        <script>
                            document.write(new Date().toLocaleDateString());
                        </script>
                    </p>
                    {{-- <a href="" class="text-decoration-underline">View net earnings</a> --}}
                  </div>
                  <div class="avatar-sm flex-shrink-0">
                    <span class="avatar-title bg-info-subtle rounded fs-3">
                      <i class="bx bx-line-chart text-info"></i>
                    </span>
                  </div>
                </div>
              </div>
              <!-- end card body -->
            </div>
            <!--end card-->
            <!--card-->
            <div class="card card-animate">
              <div class="card-body">
                <div class="d-flex align-items-center">
                  <div class="flex-grow-1 overflow-hidden">
                    <p class="text-uppercase fw-medium text-muted text-truncate mb-0"> Keuntungan Kemarin</p>
                  </div>
                  {{-- <div class="flex-shrink-0">
                    <h5 class="text-success fs-14 mb-0">
                      <i class="ri-arrow-right-up-line fs-13 align-middle"></i> +16.24 %
                    </h5>
                  </div> --}}
                </div>
                <div class="d-flex align-items-end justify-content-between mt-4">
                  <div>
                    <h4 class="fs-22 fw-semibold ff-secondary mb-4">Rp. {{ number_format($yesterday_profit, '0','.','.') }}</h4>
                    <p class="mt-2 text-sm ">Total Keuntungan Pada
                        <script>
                            let yesterday = new Date();
                            yesterday.setDate(yesterday.getDate() - 1); // Mengurangi satu hari
                            document.write(yesterday.toLocaleDateString());
                        </script>
                    </p>
                    {{-- <a href="" class="text-decoration-underline">View net earnings</a> --}}
                  </div>
                  <div class="avatar-sm flex-shrink-0">
                    <span class="avatar-title bg-info-subtle rounded fs-3">
                      <i class="bx bx-line-chart text-info"></i>
                    </span>
                  </div>
                </div>
              </div>
              <!-- end card body -->
            </div>
            <!--end card-->
            <!-- card -->
            <div class="card card-animate">
              <div class="card-body">
                <div class="d-flex align-items-center">
                  <div class="flex-grow-1 overflow-hidden">
                    <p class="text-uppercase fw-medium text-muted text-truncate mb-0"> Keuntungan Keseluruhan</p>
                  </div>
                  {{-- <div class="flex-shrink-0">
                    <h5 class="text-success fs-14 mb-0">
                      <i class="ri-arrow-right-up-line fs-13 align-middle"></i> +16.24 %
                    </h5>
                  </div> --}}
                </div>
                <div class="d-flex align-items-end justify-content-between mt-4">
                  <div>
                    <h4 class="fs-22 fw-semibold ff-secondary mb-4">Rp. {{ number_format($keuntungan_bersih, '0','.','.') }}</h4>
                    <p class="mt-2 text-sm ">Total Keuntungan Tahun 
                        <script>
                            document.write(new Date().getFullYear());
                        </script>
                    </p>
                    {{-- <a href="" class="text-decoration-underline">View net earnings</a> --}}
                  </div>
                  <div class="avatar-sm flex-shrink-0">
                    <span class="avatar-title bg-info-subtle rounded fs-3">
                      <i class="bx bx-line-chart text-info"></i>
                    </span>
                  </div>
                </div>
              </div>
              <!-- end card body -->
            </div>
            <!-- end card -->
            
        </div>
        <!-- end col -->
    </div>
    <div class="row">
        <!-- Grafik Pesanan -->
        <div class="col-xl-8 col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Grafik Transaksi (Last 7 Days)</h4>
                </div>
                <div class="card-body">
                    <div id="grafik-pesanan"></div>
                </div>
            </div>
        </div>
    
        <!-- Total User, Game, dan Services -->
        <div class="col-xl-4 col-md-8">
            <!-- Total User -->
            <div class="card mb-3">
                <div class="card-header d-flex align-items-center">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-info-subtle rounded fs-3">
                            <i class="bx bx-user-circle bx-tada-hover text-info"></i>
                        </span>
                    </div>
                    <div class="ms-3">
                        <h5 class="mb-0">Jumlah Users</h5>
                    </div>
                </div>
                <div class="card-body">
                    <h3>{{ $total_users }}</h3>
                </div>
            </div>
    
            <!-- Total Kategori Game -->
            <div class="card mb-3">
                <div class="card-header d-flex align-items-center">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-success-subtle rounded fs-3">
                            <i class='bx bx-joystick bx-tada-hover text-success'></i>
                        </span>
                    </div>
                    <div class="ms-3">
                        <h5 class="mb-0">Jumlah Kategori</h5>
                    </div>
                </div>
                <div class="card-body">
                    <h3>{{ $total_games }}</h3>
                </div>
            </div>
    
            <!-- Total Layanan Tersedia -->
            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-primary-subtle rounded fs-3">
                            <i class='bx bx-briefcase-alt-2 bx-tada-hover text-primary'></i>
                        </span>
                    </div>
                    <div class="ms-3">
                        <h5 class="mb-0">Jumlah Layanan Aktif</h5>
                    </div>
                </div>
                <div class="card-body">
                    <h3>{{ $total_services }}</h3>
                </div>
            </div>
        </div>
    </div>
    
    @section('script-bottom')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var options = {
                chart: {
                    type: 'area',
                    height: 350,
                    toolbar: {
                        show: false
                    }
                },
                series: [{
                    name: 'Number of Orders',
                    data: {!! $grafik_pesanan !!}
                }],
                xaxis: {
                    type: 'category',
                    title: {
                        text: 'Date'
                    },
                    labels: {
                        formatter: function (value) {
                            return new Date(value).toLocaleDateString();
                        }
                    }
                },
                yaxis: {
                    title: {
                        text: 'Number of Orders'
                    }
                },
                colors: ['#3ec767'],
                stroke: {
                    curve: 'smooth',
                    width: 2
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.7,
                        opacityTo: 0.9,
                        stops: [0, 90, 100]
                    }
                },
                grid: {
                    borderColor: '#eef0f2',
                },
                tooltip: {
                    theme: 'dark'
                }
            };
    
            var chart = new ApexCharts(document.querySelector("#grafik-pesanan"), options);
            chart.render();
        });
    </script>
    @endsection

@endsection
