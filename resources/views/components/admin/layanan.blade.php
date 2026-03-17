@extends('layouts.master')
@section('title')
    {{ $config->judul_web }} - Kelola Layanan
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
                        <li class="breadcrumb-item"><a href="{{ url('produk') }}">Produk</a></li>
                        <li class="breadcrumb-item active">Layanan</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    @if (session('success'))
        <div class="alert alert-success" role="alert">
            {{ session('success') }}
        </div>
    @endif

    @if (session('info'))
        <div class="alert alert-info" role="alert">
            {{ session('info') }}
        </div>
    @endif

    @if (session('danger'))
        <div class="alert alert-danger" role="alert">
            {{ session('danger') }}
        </div>
    @endif

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Tambah Layanan</h5>
                </div>
                <div class="mb-3">
                    <div class="card-body">
                        <div class="clearfix">

                            <form method="POST" action="{{ route('sync.produk.get.post') }}">
                                @csrf
                                <div class="m-2">

                                    <button type="submit"
                                        class="btn btn-success bg-gradient btn-label waves-effect waves-light rounded-pill inline-flex"
                                        style="float: left;">
                                        <i class="ri-loop-left-line label-icon align-middle rounded-pill fs-16 me-2"
                                            style="float: left;"></i> Sync Harga Digiflazz
                                    </button>

                                    {{-- <button type="submit"
                                        class="btn btn-sm btn-outline-primary inline-flex items-center justify-center rounded-4 px-3 py-2.7 hvrbutton"
                                        style="float: right; border-radius:6px;">
                                        <span style="display: flex;">
                                            <i class="ri-loop-left-line"></i> Sync Price Digiflazz
                                        </span>
                                    </button> --}}

                                </div>
                            </form>

<form method="POST" action="{{ route('synctopupedia.produk.get.post') }}">
                                @csrf
                                <div class="m-2">

                                    <button type="submit"
                                        class="btn btn-success bg-gradient btn-label waves-effect waves-light rounded-pill inline-flex"
                                        style="float: left;">
                                        <i class="ri-loop-left-line label-icon align-middle rounded-pill fs-16 me-2"
                                            style="float: left;"></i> Sync Harga topupedia
                                    </button>

                                    {{-- <button type="submit"
                                        class="btn btn-sm btn-outline-primary inline-flex items-center justify-center rounded-4 px-3 py-2.7 hvrbutton"
                                        style="float: right; border-radius:6px;">
                                        <span style="display: flex;">
                                            <i class="ri-loop-left-line"></i> Sync Price Digiflazz
                                        </span>
                                    </button> --}}

                                </div>
                            </form>
                              <form method="POST" action="{{ route('syncmoogold.produk.get.post') }}">
                                @csrf
                                <div class="m-2">

                                    <button type="submit"
                                        class="btn btn-success bg-gradient btn-label waves-effect waves-light rounded-pill inline-flex"
                                        style="float: left;">
                                        <i class="ri-loop-left-line label-icon align-middle rounded-pill fs-16 me-2"
                                            style="float: left;"></i> Sync Harga Moogold
                                    </button>

                                    {{-- <button type="submit"
                                        class="btn btn-sm btn-outline-primary inline-flex items-center justify-center rounded-4 px-3 py-2.7 hvrbutton"
                                        style="float: right; border-radius:6px;">
                                        <span style="display: flex;">
                                            <i class="ri-loop-left-line"></i> Sync Harga Moogold
                                        </span>
                                    </button> --}}

                                </div>
                            </form>                       
                             

                            <a href="{{ route('produk.get') }}" class="m-2">
                                <button type="submit"
                                    class="btn btn-warning bg-gradient btn-label waves-effect waves-light rounded-pill inline-flex"
                                    style="float: left;">
                                    <i class="ri-hand-coin-line label-icon align-middle rounded-pill fs-16 me-2"
                                        style="float: left;"></i> Get Product
                                </button>
                                {{-- <button type="submit"
                                    class="btn btn-sm btn-outline-primary inline-flex items-center justify-center rounded-4 px-3 py-2.7 hvrbutton"
                                    style="float: right; border-radius:6px;">
                                    <span style="display: flex; align-items: center;">
                                        <i class="fab fa-get-pocket"></i> Get Product
                                    </span>
                                </button> --}}
                            </a>

                        </div>
                    </div>
                </div>
                <div class="card-body">
                    
                    <form id="addServiceForm" action="{{ route('layanan.post') }}" method="POST"
                        enctype="multipart/form-data"> @csrf <div class="mb-3 row">
                            <label class="col-lg-2 col-form-label" for="example-fileinput">Nama Layanan</label>
                            <div class="col-lg-10">
                                <input type="text" class="form-control @error('nama') is-invalid @enderror"
                                    value="{{ old('nama') }}" name="nama" placeholder="Nama Layanan"> @error('nama')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-lg-2 col-form-label">Kategori</label>
                            <div class="col-lg-10">
                                <select class="form-select" name="kategori">
                                    <option>--Pilih Kategori--</option>
                                    @foreach ($kategoris as $kategori)
                                        <option value="{{ $kategori->id }}">{{ $kategori->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-lg-2 col-form-label">Provider</label>
                            <div class="col-lg-10">
                                <select class="form-select" name="provider" required>
                                    <option disabled>--Pilih Provider--</option>
                                    <option value="digiflazz">DigiFlazz - Digiflazz.com</option>
                                    <option value="vip">VIP - Vipreseller.co.id</option>
                                    <option value="apigames">ApiGames - Apigames.id</option>
                                    <option value="bangjeff">BJ - Bangjeff.com</option>
                                    <option value="topupedia">TP - Topupedia.com</option>
                                    <option value="yezzpay">YP - Yezzpay.com</option>
                                    <option value="elitedias">ED - Elitedias.com</option>
                                    <option value="moogold">MG - Moogold.com</option>
                                    <option value="gameshop">GSZ - gameshop.zsdzw.com</option>
                                    <option value="strleyashop">Strleyashop</option>
                                    <option value="joki">Joki MLBB</option>
                                    <option value="jokigendong">Joki Gendong</option>
                                    <option value="vilogml">ML Vilog</option>
                                    <option value="giftskin">Gift Skin</option>
                                    <option value="manual">Manual</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="" class="col-lg-2 col-form-label">Provider ID</label>
                            <div class="col-lg-10">
                                <input type="text" class="form-control @error('provider_id') is-invalid @enderror"
                                    value="{{ old('provider_id') }}" name="provider_id" placeholder="ML10">
                                @error('provider_id')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="harga" class="col-lg-1 col-form-label">Harga Modal</label>
                            <div class="col-lg-5">
                                <input step="0.01" type="number" class="form-control @error('harga') is-invalid @enderror"
                                    value="{{ old('harga') }}" name="harga" id="harga" placeholder="10000">
                                @error('harga')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <label for="harga_member" class="col-lg-1 col-form-label">Harga Member / Publik</label>
                            <div class="col-lg-5">
                                <input step="0.01" type="number" class="form-control @error('harga_member') is-invalid @enderror"
                                    value="{{ old('harga_member') }}" name="harga_member" id="harga_member"
                                    placeholder="10000">
                                @error('harga_member')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="harga_platinum" class="col-lg-1 col-form-label">Harga Platinum</label>
                            <div class="col-lg-5">
                                <input step="0.01" type="number" class="form-control @error('harga_platinum') is-invalid @enderror"
                                    value="{{ old('harga_platinum') }}" name="harga_platinum" id="harga_platinum"
                                    placeholder="10000">
                                @error('harga_platinum')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <label for="harga_gold" class="col-lg-1 col-form-label">Harga Gold</label>
                            <div class="col-lg-5">
                                <input step="0.01" type="number" class="form-control @error('harga_gold') is-invalid @enderror"
                                    value="{{ old('harga_gold') }}" name="harga_gold" id="harga_gold"
                                    placeholder="10000">
                                @error('harga_gold')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <div class="col-lg-12">
                                <small class="text-muted">Harga modal diisi dari harga provider/seller. Harga Member / Publik, Platinum, dan Gold diisi sebagai harga jual final.</small>
                            </div>
                        </div>

                        <small style="color:red; ">*Aktifkan Jika Ingin Membuat Flash Sale</small>
                        <div class="mb-3 row">
                            <label class="col-lg-2 col-form-label">Flash Sale?</label>
                            <div class="col-lg-10">
                                <select class="form-select" name="flash_sale">
                                    <option value="0">No</option>
                                    <option value="1">Yes</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-lg-2 col-form-label" for="example-fileinput">Nama Flash Sale</label>
                            <div class="col-lg-10">
                                <input type="text"
                                    class="form-control @error('judul_flash_sale') is-invalid @enderror"
                                    value="{{ old('judul_flash_sale') }}" name="judul_flash_sale">
                                @error('judul_flash_sale')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-lg-2 col-form-label" for="example-fileinput">Harga Flash Sale</label>
                            <div class="col-lg-10">
                                <input type="number"
                                    class="form-control @error('harga_flash_sale') is-invalid @enderror" value="0"
                                    name="harga_flash_sale" id="harga_flash_sale"> @error('harga_flash_sale')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-lg-2 col-form-label" for="example-fileinput">Jumlah Flash Sale</label>
                            <div class="col-lg-10">
                                <input type="number"
                                    class="form-control @error('stock_flash_sale') is-invalid @enderror" value="0"
                                    name="stock_flash_sale" id="stock_flash_sale"> @error('stock_flash_sale')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>
                        <script>
                            document.querySelectorAll('input[type="number"]').forEach(function(input) {
                                input.addEventListener('input', function() {
                                    if (this.value < 0) {
                                        this.value = 0;
                                    }
                                });
                            });
                        </script>
                        <div class="mb-3 row">
                            <label class="col-lg-2 col-form-label" for="example-fileinput">Expired Flash Sale</label>
                            <div class="col-lg-10">
                                <input type="datetime-local"
                                    class="form-control @error('expired_flash_sale') is-invalid @enderror"
                                    value="{{ old('expired_flash_sale') }}" name="expired_flash_sale"
                                    data-provider="flatpickr" data-date-format="d.m.y" data-enable-time>
                                @error('expired_flash_sale')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>
                        <div class="text-end">
                            <button type="submit"
                                class="btn btn-primary bg-gradient waves-effect waves-light">Simpan</button>
                            <button type="reset"
                                class="btn btn-dark bg-gradient waves-effect waves-light">Reset</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Daftar Layanan</h5>
                </div>
                <div class="card-body">
                    <div id="loadingSpinner" class="text-center">
                        <div class="spinner-grow text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                    <div class="table-responsive d-none" id="dataTableContainer">
                        <table
                            class="table table-bordered nowrap table-striped align-middle dataTable no-footer dtr-inline collapsed"
                            id="layanan-table">
                            <thead class="table-light">
                                <tr>
                                    <th>
                                    <input type="checkbox" id="select-all" />
                                </th>
                                    <th>#</th>
                                    <th>Kategori</th>
                                    <th>Layanan</th>
                                    <th>Provider</th>
                                    <th>PID</th>
                                    <th>Harga Modal</th>
                                    <th>Harga Member / Publik</th>
                                    <th>Harga Platinum</th>
                                    <th>Harga Gold</th>
                                    <th>Margin Member</th>
                                    <th>Margin Platinum</th>
                                    <th>Margin Gold</th>
                                    <th>Flash Sale Price</th>
                                    <th>Flash Sale?</th>
                                    <th>Flash Sale Title</th>
                                    <th>Expired Flash Sale</th>
                                    <th>Status</th>                                   
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($datas as $data)
                                    @php
                                        $label_pesanan = '';
                                        if ($data->status == 'available') {
                                            $label_pesanan = 'success';
                                        } elseif ($data->status == 'unavailable') {
                                            $label_pesanan = 'danger';
                                        }
                                    @endphp
                                    <tr>
                                        <td>
                                    <input type="checkbox" class="item-checkbox" value="{{ $data->id }}" />
                                </td>
                                        <th scope="row">{{ $data->id }}</th>
                                        <td>{{ $data->nama_kategori }}</td>
                                        <td>{{ $data->layanan }}
                                            <div style="margin-top: 5px; font-size: 0.9em; color: #6c757d;">
                                                {{ $data->created_at }}
                                            </div>
                                        </td>
                                        <td>{{ $data->provider }}</td>
                                        <td>{{ $data->provider_id }}</td>
                                        <td>Rp. {{ number_format($data->harga, 0, '.', ',') }}</td>
                                        <td>Rp. {{ number_format($data->harga_member, 0, '.', ',') }}</td>
                                        <td>Rp. {{ number_format($data->harga_platinum, 0, '.', ',') }}</td>
                                        <td>Rp. {{ number_format($data->harga_gold, 0, '.', ',') }}</td>
                                        <td>Rp. {{ number_format(max($data->harga_member - $data->harga, 0), 0, '.', ',') }}</td>
                                        <td>Rp. {{ number_format(max($data->harga_platinum - $data->harga, 0), 0, '.', ',') }}</td>
                                        <td>Rp. {{ number_format(max($data->harga_gold - $data->harga, 0), 0, '.', ',') }}</td>
                                        <td>Rp. {{ number_format($data->harga_flash_sale, 0, '.', ',') }}</td>
                                        <td>{{ $data->is_flash_sale == 0 ? 'No' : 'Yes' }}</td>
                                        <td>{{ $data->judul_flash_sale }}</td>
                                        <td>{{ $data->expired_flash_sale }}</td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button"
                                                    class="btn btn-{{ $label_pesanan }}">{{ $data->status }}</button>
                                                <button type="button"
                                                    class="btn btn-{{ $label_pesanan }} dropdown-toggle dropdown-toggle-split"
                                                    data-bs-toggle="dropdown" aria-expanded="false">
                                                    <span class="visually-hidden">Toggle Dropdown</span>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li><a class="dropdown-item" href="javascript:;"
                                                            onclick="changeStatus('{{ $data->id }}', 'available')">available</a>
                                                    </li>
                                                    <li><a class="dropdown-item" href="javascript:;"
                                                            onclick="changeStatus('{{ $data->id }}', 'unavailable')">unavailable</a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                        {{-- <td>
                                            <a href="javascript:;"
                                                onclick="modal('{{ $data->layanan }}', '{{ route('layanan.detail', [$data->id]) }}')"
                                                class="btn btn-info mb-1">Edit</a>
                                            <a href="javascript:;" onclick="confirmDelete('{{ $data->id }}')"
                                                class="btn btn-danger">Delete</a>
                                        </td> --}}

                                        <td>
                                            <div class="dropdown d-inline-block">
                                                <button class="btn btn-soft-secondary btn-sm" type="button"
                                                    data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="ri-more-fill align-middle"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a href="javascript:void(0);" class="dropdown-item"
                                                            onclick="modal('{{ $data->layanan }}', '{{ route('layanan.detail', [$data->id]) }}')">
                                                            <i class="ri-pencil-fill align-bottom me-2 text-muted"></i>
                                                            Edit
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a href="javascript:void(0);" class="dropdown-item"
                                                            onclick="openDeleteModal('{{ $data->id }}')">
                                                            <i class="ri-delete-bin-fill align-bottom me-2 text-muted"></i>
                                                            Delete
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <button id="bulk-delete-btn" class="btn btn-danger" onclick="confirmBulkDelete()">Hapus Terpilih</button>
                    
                    <script>
                        document.getElementById('select-all').addEventListener('click', function() {
                            const checkboxes = document.querySelectorAll('.item-checkbox');
                            checkboxes.forEach(checkbox => {
                                checkbox.checked = this.checked;
                            });
                        });
                    
                        function confirmBulkDelete() {
                            const selected = Array.from(document.querySelectorAll('.item-checkbox:checked')).map(checkbox => checkbox.value);
                            if (selected.length > 0) {
                                if (confirm('Are you sure you want to delete the selected services?')) {
                                    fetch('{{ route('layanan.bulkDelete') }}', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                        },
                                        body: JSON.stringify({ ids: selected })
                                    })
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.success) {
                                            alert(data.success);
                                            location.reload();
                                        } else {
                                            alert(data.error);
                                        }
                                    })
                                    .catch(error => console.error('Error:', error));
                                }
                            } else {
                                alert('Please select a service to delete.');
                            }
                        }
                    </script>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal untuk Edit -->
    <div class="modal fade zoomIn" id="modal-detail" tabindex="-1" aria-labelledby="modal-detail-title" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-detail-title">Detail Layanan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modal-detail-body">
                    <!-- Konten akan dimuat di sini melalui AJAX -->
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Delete -->
    <div id="removeItemModal" class="modal fade flip" tabindex="-1" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        id="btn-close"></button>
                </div>
                <div class="modal-body">
                    <div class="mt-2 text-center">
                        <lord-icon src="https://cdn.lordicon.com/gsqxdxog.json" trigger="loop"
                            colors="primary:#f7b84b,secondary:#f06548" style="width:100px;height:100px">
                        </lord-icon>
                        <div class="mt-4 pt-2 fs-15 mx-4 mx-sm-5">
                            <h4>Are you sure?</h4>
                            <p class="text-muted mx-4 mb-0">Are you sure you want to remove this user?</p>
                        </div>
                    </div>
                    <div class="d-flex gap-2 justify-content-center mt-4 mb-2">
                        <button type="button" class="btn w-sm btn-light" data-bs-dismiss="modal">Close</button>
                        <a id="confirmDeleteButton" href="#" class="btn w-sm btn-danger">Yes, Delete It!</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        (function() {
            'use strict'
            // Fetch all the forms we want to apply custom Bootstrap validation styles to
            var forms = document.querySelectorAll('.needs-validation')

            // Loop over them and prevent submission
            Array.prototype.slice.call(forms)
                .forEach(function(form) {
                    form.addEventListener('submit', function(event) {
                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        form.classList.add('was-validated')
                    }, false)
                })
        })()
    </script>
    <script>
        $(document).ready(function() {
            setTimeout(function() {
                $('#loadingSpinner').addClass('d-none');
                $('#dataTableContainer').removeClass('d-none');
                $('#layanan-table').DataTable();
            }, 500); // Adjust the delay as needed
        });
    </script>

    <script type="text/javascript">
        function openDeleteModal(dataId) {
            // Buat URL berdasarkan ID pengguna
            const deleteUrl = `/layanan/hapus/${dataId}`;

            // Set URL ke tombol konfirmasi delete di modal
            document.getElementById('confirmDeleteButton').setAttribute('href', deleteUrl);

            // Tampilkan modal
            const deleteModal = new bootstrap.Modal(document.getElementById('removeItemModal'));
            deleteModal.show();
        }

        function changeStatus(id, status) {
            Swal.fire({
                title: 'Change Service Status',
                text: "Are you sure you want to change the status of this service to " + status + "?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, change!',
                cancelButtonText: 'Cancelled'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '/layanan-status/' + id + '/' + status;
                }
            });
        }

        function confirmAddService() {
            Swal.fire({
                title: 'Add Service',
                text: "Are you sure you want to add this service?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, Add!',
                confirmButtonText: 'Cancelled'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('addServiceForm').submit();
                }
            });
        }

        function confirmDelete(id) {
            Swal.fire({
                title: 'Delete Service',
                text: "Are you sure you want to delete this service?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancelled'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '/layanan/hapus/' + id;
                }
            });
        }

        $(document).ready(function() {
            $('.table').DataTable();
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

    </script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"
        integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>

    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="{{ URL::asset('assets/admin/js/pages/datatables.init.js') }}"></script>
@endsection
