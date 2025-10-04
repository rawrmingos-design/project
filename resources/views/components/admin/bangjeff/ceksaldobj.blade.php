@extends('main-admin')

@section('content')
 <div class="container-xxl flex-grow-1 container-p-y">
    <div class="col-lg-12 mb-4 order-0">
    </div>
                
               <div class=" mt-4">
        <h4 class="page-title">Profile akun Bangjeff</h4>
    <div class="row mt-2">
        <div class="col-md-6 col-xl-3 mb-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-grow-1">
                            <span class="text-muted text-uppercase fs-12 fw-bold">Nama</span>
                            <h3 class="mb-0">BANGJEFF</h3>
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
                            <span class="text-muted text-uppercase fs-12 fw-bold">ROLE</span>
                            <h3 class="mb-0">GOLD</h3>
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
                            <span class="text-muted text-uppercase fs-12 fw-bold">SALDO</span>
                            <h3 class="mb-0">Rp. {{ number_format($saldo, '0', '.', ',') }}</h3>
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
</div>
@endsection
