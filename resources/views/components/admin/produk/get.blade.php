@extends('layouts.master')
@section('title')
    {{ $config->judul_web }} - Get Produk
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
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between bg-galaxy-transparent">
                <h4 class="mb-sm-0">Produk</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ url('layanan') }}">Layanan</a></li>
                        <li class="breadcrumb-item active">Get Produk</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('success'))
        <div class="alert alert-success" role="alert">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger" role="alert">
            {{ session('error') }}
        </div>
    @endif

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Sync Product Automatic</h5>
                </div>
                <div class="card-body">

                    <form action="" method="POST" id="produkForm">
                        @csrf
                        <table class="table">

                            <div class="mb-3 row">
                                <label class="col-lg-2 col-form-label">Provider</label>
                                <div class="col-lg-10">
                                    <select class="form-select" name="provider">
                                        <option value="digiflazz">DigiFlazz</option>
                                        <option value="vip">VipReseller</option>
                                        <option value="topupedia">TopuPedia</option>
                                        <option value="bangjeff">Bangjeff</option>
                                        <option value="moogold">Moogold</option>                                                                                
                                    </select>
                                </div>
                            </div>
                            <div class="mb-2 row">
                                <label class="col-lg-2 col-form-label">Kategori</label>
                                <div class="col-lg-10">
                                    <select class="form-select" name="kategori" id="kategoriSelect">
                                        <option value="">--Pilih Kategori--</option>
                                        @foreach ($kategoris as $kategori)
                                            <option value="{{ $kategori->nama }}">{{ $kategori->nama }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>                         

                            <div class="mb-2 row profit-inputs" style="display: none;">
                                <label class="col-lg-2 col-form-label">Profit</label>
                                <div class="col-lg-10">
                                    <input type="number" step="0.01" class="form-control" value="{{ old('profit') }}"
                                        name="profit">
                                </div>

                            </div>

                            <div class="mb-2 row profit-inputs" style="display: none;">
                                <label class="col-lg-2 col-form-label">Profit Member</label>
                                <div class="col-lg-10">
                                    <input type="number" step="0.01" class="form-control"
                                        value="{{ old('profit_member') }}" name="profit_member">
                                </div>
                            </div>

                            <div class="mb-2 row profit-inputs" style="display: none;">
                                <label class="col-lg-2 col-form-label">Profit Platinum</label>
                                <div class="col-lg-10">
                                    <input type="number" step="0.01" class="form-control"
                                        value="{{ old('profit_platinum') }}" name="profit_platinum">
                                </div>

                            </div>

                            <div class="mb-2 row profit-inputs" style="display: none;">
                                <label class="col-lg-2 col-form-label">Profit Gold</label>
                                <div class="col-lg-10">
                                    <input type="number" step="0.01" class="form-control"
                                        value="{{ old('profit_gold') }}" name="profit_gold">
                                </div>
                            </div>
                        </table>
                        <div class="mb-2 row">
                            <label class="col-lg-2 col-form-label">Sync Product?</label>
                            <div class="col-lg-10">
                                <div class="form-check form-switch form-switch-md">
                                    <input class="form-check-input" type="checkbox" id="ubahRouteCheckbox"
                                        name="ubah_route">
                                </div>
                            </div>
                        </div>
                        <a href="{{ url('/layanan') }}"
                            class="btn btn-info bg-gradient waves-effect waves-light float-start">Kembali</a>
                        <div class="text-end">
                            <button class="btn btn-dark bg-gradient waves-effect waves-light" type="reset">Batal</button>
                            <button class="btn btn-primary bg-gradient waves-effect waves-light" type="submit"
                                name="tombol" value="submit">Simpan</button>
                            @if ($errors->has('message'))
                                <div class="alert alert-danger">
                                    {{ $errors->first('message') }}
                                </div>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" id="modal-detail"
        style="border-radius:7%">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="modal-detail-title"></h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modal-detail-body"></div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script>
        document.getElementById('ubahRouteCheckbox').addEventListener('change', function() {
            var formAction = this.checked ? "{{ route('sync.produk.get.post') }}" :
                "{{ route('produk.get.post') }";
            document.getElementById('produkForm').setAttribute('action', formAction);
        });
        const kategoriSelect = document.getElementById('kategoriSelect');
        const profitInputs = document.querySelectorAll('.profit-inputs');

        kategoriSelect.addEventListener('change', function() {
            const selectedValue = this.value;


            if (selectedValue) {
                profitInputs.forEach(inputRow => {
                    inputRow.style.display = 'block';
                });
            } else {
                profitInputs.forEach(inputRow => {
                    inputRow.style.display = 'none';
                });
            }
        });

        function modal(name, link) {
            var myModal = new bootstrap.Modal($('#modal-detail'))
            $.ajax({
                type: "GET",
                url: link,
                beforeSend: function() {
                    $('#modal-detail-title').html(name);
                    $('#modal-detail-body').html('Loading...');
                },
                success: function(result) {
                    $('#modal-detail-title').html(name);
                    $('#modal-detail-body').html(result);
                },
                error: function() {
                    $('#modal-detail-title').html(name);
                    $('#modal-detail-body').html('There is an error...');
                }
            });
            myModal.show();
        }
    </script>
    
@endsection
