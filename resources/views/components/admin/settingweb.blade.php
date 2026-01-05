@extends('layouts.master')
@section('title')
    {{ $config->judul_web }} - Settings Web
@endsection
@section('css')
    <style>
        #loading-spinner {
            margin: 20px auto;
        }
    </style>
    <!--datatable css-->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet" type="text/css" />
    <!--datatable responsive css-->
    <link href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap.min.css" rel="stylesheet"
        type="text/css" />
    <link href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css" rel="stylesheet" type="text/css" />
@endsection
@section('content')

    <!-- Breadcrumb -->
    <div class="row g-4">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between bg-galaxy-transparent">
                <h4 class="mb-sm-0">Konfigurasi</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ url('/setting/web') }}">Konfigurasi</a></li>
                        <li class="breadcrumb-item active">Website</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <!-- Tabs Navigation dengan nav-tabs-custom -->
    <ul class="nav nav-tabs nav-justified nav-border-top nav-border-top-primary mb-3" id="configTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="website-tab" data-bs-toggle="tab" data-bs-target="#website" type="button"
                role="tab" aria-controls="website" aria-selected="true">
                <i class="ri-settings-line"></i> Konfigurasi Website
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="api-tab" data-bs-toggle="tab" data-bs-target="#api" type="button" role="tab"
                aria-controls="api" aria-selected="false">
                <i class="ri-fire-line"></i> Konfigurasi API
            </button>
        </li>
    </ul>


    <!-- Tabs Content -->
    <div class="tab-content" id="configTabsContent">
        <!-- Tab 1: Konfigurasi Website -->
        <div class="tab-pane fade show active" id="website" role="tabpanel" aria-labelledby="website-tab">
            <div class="row">
                <!-- Konfigurasi Website -->
                <div class="col-xxl-12">
                    <div class="card">
                        <form action="{{ url('/setting/web') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="card-header">
                                <h5 class="card-title mb-0">Konfigurasi Website</h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-4">
                                    <div class="col-lg-12">
                                        <div>
                                            <label for="web-title-Input" class="form-label">Nama Website<span
                                                    class="text-danger">*</span></label>
                                            <input type="text" class="form-control" value="{{ $web->judul_web }}"
                                                name="judul_web">
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <div>
                                            <label for="deskripsi_web" class="form-label">Deskripsi Website<span
                                                    class="text-danger">*</span></label>
                                            <textarea class="form-control" id="deskripsi_web" rows="3" name="deskripsi_web">{{ $web->deskripsi_web }}</textarea>
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <div>
                                            <label for="keywords" class="form-label">Keywords Website<span
                                                    class="text-danger">*</span></label>
                                            <textarea class="form-control" id="keywords" rows="3" name="keywords">{{ $web->keywords }}</textarea>
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <div>
                                            <label for="logo-header" class="form-label">Logo Header<span
                                                    class="text-danger">*</span></label>
                                            <img width="100" src="{{ asset($web->logo_header) }}" alt="WeJizy">
                                            <input type="file" class="form-control" name="logo_header">
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <div>
                                            <label for="logo-footer" class="form-label">Footer<span
                                                    class="text-danger">*</span></label>
                                            <img width="100" src="{{ asset($web->logo_footer) }}" alt="WeJizy">
                                            <input type="file" class="form-control" name="logo_footer">
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <div>
                                            <label for="web-title-Input" class="form-label">fav Icon<span
                                                    class="text-danger">*</span></label>
                                            <img width="100" src="{{ asset($web->logo_favicon) }}" alt="WeJizy">
                                            <input type="file" class="form-control" name="logo_favicon">
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div>
                                            <label for="vancancy-Input" class="form-label">URL Whatsapp <span
                                                    class="text-danger">*</span></label>
                                            <input type="url" class="form-control" value="{{ $web->url_wa }}"
                                                name="url_wa">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div>
                                            <label for="vancancy-Input" class="form-label">URL Instagram <span
                                                    class="text-danger">*</span></label>
                                            <input type="url" class="form-control" value="{{ $web->url_ig }}"
                                                name="url_ig">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div>
                                            <label for="vancancy-Input" class="form-label">URL TiktTok <span
                                                    class="text-danger">*</span></label>
                                            <input type="url" class="form-control" value="{{ $web->url_tiktok }}"
                                                name="url_tiktok">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div>
                                            <label for="vancancy-Input" class="form-label">URL YouTube <span
                                                    class="text-danger">*</span></label>
                                            <input type="url" class="form-control" value="{{ $web->url_youtube }}"
                                                name="url_youtube">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div>
                                            <label for="vancancy-Input" class="form-label">URL Facebook <span
                                                    class="text-danger">*</span></label>
                                            <input type="url" class="form-control" value="{{ $web->url_fb }}"
                                                name="url_fb">
                                        </div>
                                    </div>

                                    <div class="col-lg-12">
                                        <div class="hstack justify-content-end gap-2">
                                            <button type="submit"
                                                class="btn btn-primary bg-gradient waves-effect waves-light">Simpan</button>
                                            <button type="reset"
                                                class="btn btn-dark bg-gradient waves-effect waves-light">Ulangi</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Konfigurasi Prefik -->
                
                <div class="col-xxl-6">
                    <div class="card">
                        <form action="{{ url('/setting/prefik') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="card-header">
                                <h5 class="card-title mb-0">Konfigurasi Order Prefix</h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-4">
                                    <div class="col-lg-12">
                                        <label class="form-label">Order Prefik<span
                                                class="text-danger">*</span></label>
                                        <div class="form-icon right">
                                            <input type="text" class="form-control"
                                                value="{{$web->order_prefik}}" name="order_prefik">
                                            </div>
                                    </div>
                                    <div class="col-lg-12 text-end">
                                        <div class="hstack justify-content-end gap-2">
                                            <button type="submit"
                                                class="btn btn-primary bg-gradient waves-effect waves-light">Simpan</button>
                                            <button type="reset"
                                                class="btn btn-dark bg-gradient waves-effect waves-light">Ulangi</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Konfigurasi Warna -->
                <div class="col-xxl-12">
                    <div class="card">
                        <form action="{{ url('/setting/warnaweb') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="card-header">
                                <h5 class="card-title mb-0">Konfigurasi Warna Website</h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-4">
                                    <div class="col-lg-12">
                                        <div>
                                            <label for="warna1" class="form-label">Warna 1</label>
                                            <input type="color" class="form-control form-control-color w-100"
                                                id="colorbackground" value="{{ $web->warna1 }}" name="warna1">
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <div>
                                            <label for="warna1" class="form-label">Warna 2</label>
                                            <input type="color" class="form-control form-control-color w-100"
                                                id="colorInputmel" value="{{ $web->warna2 }}" name="warna2">
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <div>
                                            <label for="warna1" class="form-label">Warna 3</label>
                                            <input type="color" class="form-control form-control-color w-100"
                                                id="colorInputtih" value="{{ $web->warna3 }}" name="warna3">
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <div>
                                            <label for="warna1" class="form-label">Warna 4</label>
                                            <input type="color" class="form-control form-control-color w-100"
                                                id="colorInputt" value="{{ $web->warna4 }}" name="warna4">
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <div class="hstack justify-content-end gap-2">
                                            <button type="submit"
                                                class="btn btn-primary bg-gradient waves-effect waves-light">Simpan</button>
                                            <button type="reset"
                                                class="btn btn-dark bg-gradient waves-effect waves-light">Ulangi</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 2: Konfigurasi API -->
        <div class="tab-pane fade" id="api" role="tabpanel" aria-labelledby="api-tab">

            <div class="row g-4">
                
                <div class="col-xxl-6">
                    <div class="card">
                        <form action="{{ url('/setting/tripay') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="card-header">
                                <h5 class="card-title mb-0">Konfigurasi API TriPay</h5>
                                <span class="text-danger">URL CALLBACK: {{ ENV('APP_URL') }}/wejizy/tripay/callback</span>
                            </div>
                            <div class="card-body">
                                <div class="row g-4">
                                    <div class="col-lg-12">
                                        <label class="form-label">TriPay Merchant Code<span
                                                class="text-danger">*</span></label>
                                        <input type="text" class="form-control"
                                            value="{{$web->tripay_merchant_code}}" name="tripay_merchant_code">
                                    </div>
                                    <div class="col-lg-12">
                                        <label class="form-label">TriPay APi<span
                                                class="text-danger">*</span></label>
                                        <div class="form-icon right">
                                            <input type="password" class="form-control"
                                                value="{{$web->tripay_api}}" name="tripay_api"
                                                id="tripayapi">
                                            <span onclick="toggleVisibility('tripayapi', this)"
                                                style="cursor: pointer;">
                                                <h4><i class="ri-eye-off-line"></i></h4>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <label class="form-label">TriPay Private Key<span
                                                class="text-danger">*</span></label>
                                        <div class="form-icon right">
                                            <input type="password" class="form-control"
                                                value="{{$web->tripay_private_key}}" name="tripay_private_key"
                                                id="tripayprivate">
                                            <span onclick="toggleVisibility('tripayprivate', this)"
                                                style="cursor: pointer;">
                                                <h4><i class="ri-eye-off-line"></i></h4>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-lg-12 text-end">
                                        <div class="hstack justify-content-end gap-2">
                                            <button type="submit"
                                                class="btn btn-primary bg-gradient waves-effect waves-light">Simpan</button>
                                            <button type="reset"
                                                class="btn btn-dark bg-gradient waves-effect waves-light">Reset</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="col-xxl-6">
                    <div class="card">
                        <form action="{{ url('/setting/digiflazz') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="card-header">
                                <h5 class="card-title mb-0">Konfigurasi API Digiflazz (Buyer)</h5>
                                <span class="text-danger">URL WEBHOOK: {{ ENV('APP_URL') }}/wejizy/digi/payload</span>
                                <span class="text-danger">SECRET CODE: WEJIZYSEC18</span>
                            </div>
                            <div class="card-body">
                                <div class="row g-4">
                                    <div class="col-lg-12">
                                        <label class="form-label">Username Digiflazz<span
                                                class="text-danger">*</span></label>
                                        <input type="text" class="form-control" value="{{ $web->username_digi }}"
                                            name="username_digi">
                                    </div>
                                    <div class="col-lg-12">
                                        <label class="form-label">Api Key Digiflazz<span
                                                class="text-danger">*</span></label>
                                        <div class="form-icon right">
                                            <input type="password" class="form-control" value="{{ $web->api_key_digi }}"
                                                name="api_key_digi" id="digiApiKey">
                                            <span onclick="toggleVisibility('digiApiKey', this)" style="cursor: pointer;">
                                                <h4><i class="ri-eye-off-line"></i></h4>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="col-lg-12 text-end">
                                        <div class="hstack justify-content-end gap-2">
                                            <button type="submit"
                                                class="btn btn-primary bg-gradient waves-effect waves-light">Simpan</button>
                                            <button type="reset"
                                                class="btn btn-dark bg-gradient waves-effect waves-light">Reset</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-xxl-6 mt-2">
                    <div class="card">
                        <form action="{{ url('/setting/apigames') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="card-header">
                                <h5 class="card-title mb-0">Konfigurasi ApiGames</h5>
                                {{-- <span class="text-danger">URL CALLBACK: {{ ENV('APP_URL') }}/digi/payload</span> --}}
                            </div>
                            <div class="card-body">
                                <div class="row g-4">
                                    <div class="col-lg-12">
                                        <label class="form-label">Apigames Merchant<span
                                                class="text-danger">*</span></label>
                                        <input type="text" class="form-control" value="{{ $web->apigames_merchant }}"
                                            name="apigames_merchant">
                                    </div>
                                    <div class="col-lg-12">
                                        <label class="form-label">Apigames Secret Key<span
                                                class="text-danger">*</span></label>
                                        <div class="form-icon right">
                                            <input type="password" class="form-control"
                                                value="{{ $web->apigames_secret }}" name="apigames_secret"
                                                id="apigamesKey">
                                            <span onclick="toggleVisibility('apigamesKey', this)"
                                                style="cursor: pointer;">
                                                <h4><i class="ri-eye-off-line"></i></h4>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-lg-12 text-end">
                                        <div class="hstack justify-content-end gap-2">
                                            <button type="submit"
                                                class="btn btn-primary bg-gradient waves-effect waves-light">Simpan</button>
                                            <button type="reset"
                                                class="btn btn-dark bg-gradient waves-effect waves-light">Reset</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-xxl-6 mt-2">
                    <div class="card">
                        <form action="{{ url('/setting/vip') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="card-header">
                                <h5 class="card-title mb-0">Konfigurasi Vip Reseller</h5>
                                {{-- <span class="text-danger">URL CALLBACK: {{ ENV('APP_URL') }}/digi/payload</span> --}}
                            </div>
                            <div class="card-body">
                                <div class="row g-4">
                                    <div class="col-lg-12">
                                        <label class="form-label">VIP Api ID<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" value="{{ $web->vip_apiid }}"
                                            name="vip_apiid">
                                    </div>
                                    <div class="col-lg-12">
                                        <label class="form-label">VIP Api Key<span class="text-danger">*</span></label>
                                        <div class="form-icon right">
                                            <input type="password" class="form-control" value="{{ $web->vip_apikey }}"
                                                name="vip_apikey" id="vipApiKey">
                                            <span onclick="toggleVisibility('vipApiKey', this)" style="cursor: pointer;">
                                                <h4><i class="ri-eye-off-line"></i></h4>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-lg-12 text-end">
                                        <div class="hstack justify-content-end gap-2">
                                            <button type="submit"
                                                class="btn btn-primary bg-gradient waves-effect waves-light">Simpan</button>
                                            <button type="reset"
                                                class="btn btn-dark bg-gradient waves-effect waves-light">Reset</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="col-lg-12 mt-2">
                    <div class="card">
                        <form action="{{ url('/setting/wagateway') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="card-header">
                                <h5 class="card-title mb-0">Configuration Fonnte</h5>
                                {{-- <span class="text-danger">URL CALLBACK: {{ ENV('APP_URL') }}/digi/payload</span> --}}
                            </div>
                            <div class="card-body">
                                <div class="row g-4">
                                    <!--<div class="col-lg-12">-->
                                    <!--    <label class="form-label">No. Admin<span class="text-danger">*</span></label>-->
                                    <!--    <input type="text" class="form-control" value="{{ $web->nomor_admin }}"-->
                                    <!--        name="nomor_admin" id="nomor" value="1">-->
                                    <!--</div>-->
                                    <div class="col-lg-12">
                                        <label class="form-label">Whatsapp Number<span
                                                class="text-danger">*</span></label>
                                        <input type="text" class="form-control" value="{{ $web->wa_number }}"
                                            name="wa_number" id="nomor">
                                    </div>
                                    <div class="col-lg-12">
                                        <label class="form-label">Whatsapp Key<span class="text-danger">*</span></label>
                                        <div class="form-icon right">
                                            <input type="password" class="form-control" value="{{ $web->wa_key }}"
                                                name="wa_key" id="wAKey">
                                            <span onclick="toggleVisibility('wAKey', this)" style="cursor: pointer;">
                                                <h4><i class="ri-eye-off-line"></i></h4>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-lg-12 text-end">
                                        <div class="hstack justify-content-end gap-2">
                                            <button type="submit"
                                                class="btn btn-primary bg-gradient waves-effect waves-light">Save</button>
                                            <button type="reset"
                                                class="btn btn-dark bg-gradient waves-effect waves-light">Reset</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
@section('script')
    <!-- JavaScript Toggle Visibility -->
    <script>
        function toggleVisibility(id, el) {
            const input = document.getElementById(id);
            const icon = el.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('ri-eye-off-line', 'ri-eye-line');
            } else {
                input.type = 'password';
                icon.classList.replace('ri-eye-line', 'ri-eye-off-line');
            }
        }
    </script>

    <script>
        const nomorInputs = document.querySelectorAll("#nomor");

        nomorInputs.forEach(input => {
            input.addEventListener("input", function() {
                let nomor = this.value;
                if (nomor.startsWith("08")) {
                    nomor = "62" + nomor.slice(1);
                    this.value = nomor;
                }
            });
        });
    </script>
@endsection
