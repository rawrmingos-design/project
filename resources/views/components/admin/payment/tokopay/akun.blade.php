@extends('main-admin')

@section('content')
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informasi Akun</title>
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>
 <div class="container-xxl flex-grow-1 container-p-y">
    <div class="col-lg-12 mb-4 order-0">
    </div>
                  @if(isset($data))
               <div class=" mt-4">
        <h4 class="page-title">Informasi Akun Tokopay</h4>
    <div class="row mt-2">
        <div class="col-md-6 col-xl-3 mb-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-grow-1">
                            <span class="text-muted text-uppercase fs-12 fw-bold">Nama Toko</span>
                            <h3 class="mb-0">{{ $data['nama_toko'] }}</h3>
                        </div>
                        <div class="align-self-center flex-shrink-0">
                            <span class="icon-lg icon-dual-primary" data-feather="shopping-bag"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3 mb-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-grow-1">
                            <span class="text-muted text-uppercase fs-12 fw-bold">Saldo Tersedia</span>
                            <h3 class="mb-0">Rp {{ number_format($data['saldo_tersedia'], 0, ',', '.') }}</h3>
                        </div>
                        <div class="align-self-center flex-shrink-0">
                            <span class="icon-lg icon-dual-success" data-feather="shopping-bag"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3 mb-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-grow-1">
                            <span class="text-muted text-uppercase fs-12 fw-bold">Saldo Tertahan</span>
                            <h3 class="mb-0">Rp {{ number_format($data['saldo_tertahan'], 0, ',', '.') }}</h3>
                        </div>
                        <div class="align-self-center flex-shrink-0">
                            <span class="icon-lg icon-dual-info" data-feather="shopping-bag"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
      @elseif(isset($error))
            <p class="text-red-500">{{ $error }}</p>
        @else
            <p>Data tidak tersedia.</p>
        @endif
</div>
@endsection
