@extends('layouts.master')
@section('title')
    {{ ENV('APP_NAME') }} - Kelola Kategori
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
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
@endsection
@section('content')
    <!-- Breadcrumb -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between bg-galaxy-transparent">
                <h4 class="mb-sm-0">Produk</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ url('kategori') }}">Produk</a></li>
                        <li class="breadcrumb-item active">Kelola Kategori</li>
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
        <div class="col-xxl-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Tambah Kategori</h5>
                </div>
                <div class="card-body">
                    {{-- <form action="{{ route('kategori.post') }}" method="POST" enctype="multipart/form-data" id="kategori" class="row g-3">
                        @csrf
                        <div class="col-md-6">
                            <label for="nama" class="form-label">Nama Kategori</label>
                            <input type="text" class="form-control @error('nama') is-invalid @enderror"
                            value="{{ old('nama') }}" name="nama">
                        @error('nama')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="sub_nama" class="form-label">Sub Name (brand)</label>
                            <input type="text" class="form-control @error('sub_nama') is-invalid @enderror"
                                    value="{{ old('sub_nama') }}" name="sub_nama">
                                @error('sub_nama')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                        </div>
                        <div class="col-md-12">
                            <label for="kode" class="form-label">Code (Check ID)</label>
                           <input type="text" class="form-control @error('kode') is-invalid @enderror"
                                    value="{{ old('kode') }}" name="kode">
                                @error('kode')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                        </div>
                        <div class="col-md-12">
                            <label for="deskripsi_game" class="form-label">Deskripsi Kategori</label>
                            <textarea name="deskripsi_game" id="deskripsi_game" class="form-control d-none" required></textarea>
                            <div class="invalid-feedback">
                                Game description is required.
                            </div>
                        </div>
                        <div class="col-md-12">
                            <label for="deskripsi_field" class="form-label">Description Field</label>
                            <textarea class="form-control @error('deskripsi_field') is-invalid @enderror" name="deskripsi_field">{{ old('deskripsi_field') }}</textarea>
                            @error('deskripsi_field')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        <div class="col-md-12">
                            <label for="deskripsi_field" class="form-label">Target Kategori</label>
                            <div id="fieldsContainer"
                                class="flex flex-col gap-2 p-4 border border-secondary-400 rounded-lg">
                                <div class="field-group">
                                    <div class="flex justify-between">
                                        <div class="field-title">Target 1</div>
                                    </div>
                                    <div class="mt-4">
                                        <input class="form-control" placeholder="Title" name="inputs_1_title">
                                    </div>
                                    <div class="mt-4">
                                        <input class="form-control" placeholder="Name" name="inputs_1_name">
                                    </div>
                                    <div class="mt-4 mb-4">
                                        <select class="form-control" name="inputs_1_type" required>
                                            <option>Select Input Type</option>
                                            <option value="text">Text</option>
                                            <option value="number">Number</option>
                                            <option value="password">Password</option>
                                            <option value="select">Select</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-2">
                                <button id="removeFieldBtn"
                                    class="btn rounded-pill btn-danger bg-gradient waves-effect waves-light"
                                    style="float: right;" type="button">Hapus Target</button>
                                <button id="addMoreBtn"
                                    class="btn rounded-pill btn-info bg-gradient waves-effect waves-light"
                                    style="float: right;" type="button">Tambah Target</button>

                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="thumbnail" class="form-label">Logo Kategori</label>
                            <input type="file" class="form-control" name="thumbnail">
                            @error('thumbnail')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="banner" class="form-label">Banner</label>
                            <input type="file" class="form-control" id="banner" name="thumbnail" required>
                            <div class="invalid-feedback">
                                Banner must be uploaded.
                            </div>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Tipe</label>
                            <select class="form-select" name="tipe" required>
                                <option value="" disabled>--Pilih Tipe--</option>
                                <option value='populer'>Populer</option>
                                <option value='game'>Games Topup</option>
                                <option value='app'>App Premium</option>
                                <option value='pulsa'>Pulsa & Data</option>
                                <option value='voucher'>Voucher</option>
                                <option value='joki'>JOKI MLBB</option>
                                <option value='jokigendong'>JOKI Gendong</option>
                                <option value='giftskin'>Gift Skins</option>
                                <option value='vilogml'>ML Vilog</option>

                                <!--<option value="gift_skin">Gift Skin MLBB</option>-->
                                <!--<option value='e-money'>E-Money</option>-->
                                <!--<option value='streamingapp'>Streaming APP</option>-->
                            </select>
                            @error('tipe')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                        </div>

                        <div class="col-12">
                            <div class="text-end">
                                <button type="submit"
                                    class="btn btn-primary bg-gradient waves-effect waves-light">Tambah</button>
                                <button type="reset"
                                    class="btn btn-dark bg-gradient waves-effect waves-light">Ulangi</button>
                            </div>
                        </div>
                    </form> --}}
                    <form class="row g-3" action="{{ route('kategori.post') }}" method="POST" enctype="multipart/form-data"
                        id="kategori">
                        @csrf
                        <div class="col-md-6">
                            <label class="form-label" for="example-fileinput">Name</label>
                            <input type="text" class="form-control @error('nama') is-invalid @enderror"
                                value="{{ old('nama') }}" name="nama">
                            @error('nama')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="example-fileinput">Sub Name (brand)</label>
                            <input type="text" class="form-control @error('sub_nama') is-invalid @enderror"
                                value="{{ old('sub_nama') }}" name="sub_nama">
                            @error('sub_nama')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        <div class="col-md-12">
                            <label class="form-label" for="example-fileinput">Code (Check ID)</label>
                            <input type="text" class="form-control @error('kode') is-invalid @enderror"
                                value="{{ old('kode') }}" name="kode">
                            @error('kode')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        <!--<div class="mb-3 row">-->
                        <!--    <label class="col-lg-2 col-form-label" for="example-fileinput">Brand</label>-->
                        <!--    <div class="col-lg-10">-->
                        <!--        <input type="text" class="form-control @error('brand') is-invalid @enderror" value="{{ old('brand') }}" name="brand">-->
                        <!--        @error('brand')
        -->
                            <!--        <div class="invalid-feedback">-->
                            <!--            {{ $message }}-->
                            <!--        </div>-->
                            <!--
    @enderror-->
                        <!--    </div>-->
                        <!--</div>-->
                        {{-- <div class="mb-3 row">
                            <label class="col-lg-2 col-form-label" for="example-fileinput">Game Description</label>
                            <div class="col-lg-10">
                                <textarea id="summernote" class="form-control @error('deskripsi_game') is-invalid @enderror" name="deskripsi_game">{{ old('deskripsi_game') }}</textarea>
                                @error('deskripsi_game')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                                @enderror
                            </div>
                        </div> --}}
                        <div class="col-md-12">
                            <label class="form-label" for="example-fileinput">Deskripsi Kategori</label>
                            <!-- Hidden Textarea -->
                            <textarea name="deskripsi_game" id="deskripsi_game" class="form-control d-none"></textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label" for="example-fileinput">Deskripsi Target</label>
                            <textarea rows="3" class="form-control @error('deskripsi_field') is-invalid @enderror" name="deskripsi_field">{{ old('deskripsi_field') }}</textarea>
                            @error('deskripsi_field')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        <div class="col-md-12">
                            <label for="deskripsi_field" class="form-label">Target Kategori</label>
                            <div id="fieldsContainer"
                                class="flex flex-col gap-2 p-4 border border-secondary-400 rounded-lg">
                                <div class="field-group">
                                    <div class="flex justify-between">
                                        <div class="field-title">Target 1</div>
                                    </div>
                                    <div class="mt-4">
                                        <input class="form-control" placeholder="Title" name="inputs_1_title">
                                    </div>
                                    <div class="mt-4">
                                        <input class="form-control" placeholder="Name" name="inputs_1_name">
                                    </div>
                                    <div class="mt-4 mb-4">
                                        <select class="form-control" name="inputs_1_type" required>
                                            <option>Select Input Type</option>
                                            <option value="text">Text</option>
                                            <option value="number">Number</option>
                                            <option value="password">Password</option>
                                            <option value="select">Select</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-2">
                                <button id="removeFieldBtn"
                                    class="btn rounded-pill btn-danger bg-gradient waves-effect waves-light"
                                    style="float: right;" type="button">Hapus Target</button>
                                <button id="addMoreBtn"
                                    class="btn rounded-pill btn-info bg-gradient waves-effect waves-light"
                                    style="float: right;" type="button">Tambah Target</button>

                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="example-fileinput">Logo Kategori</label>
                            <input type="file" class="form-control" name="thumbnail">
                            @error('thumbnail')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="example-fileinput">Banner</label>
                            <input type="file" class="form-control" name="banner">
                            @error('banner')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        <div class="col-md-12">
                            <label class="form-label" for="example-fileinput">Type</label>
                            <select class="form-select" name="tipe">
                                <option value='populer'>Populer</option>
                                <option value='game'>Games Topup</option>
                                <option value='app'>App Premium</option>
                                <option value='pulsa'>Other Region Topup</option>
                                <option value='voucher'>Voucher</option>
                                <option value='joki'>JOKI MLBB</option>
                                <option value='jokigendong'>JOKI Gendong</option>
                                <option value='giftskin'>Gift Skins</option>
                                <option value='vilogml'>ML Vilog</option>

                                <!--<option value="gift_skin">Gift Skin MLBB</option>-->
                                <!--<option value='other'>Other Region Topup</option>-->
                                <!--<option value='streamingapp'>Streaming APP</option>-->
                            </select>
                            @error('tipe')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        <div class="col-12">
                            <div class="text-end">
                                <button type="submit"
                                    class="btn btn-primary bg-gradient waves-effect waves-light">Simpan</button>
                                <button type="reset"
                                    class="btn btn-dark bg-gradient waves-effect waves-light">Kembali</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xxl-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Daftar Kategori</h5>
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
                            style="width: 100%;" id="kategori-table">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Gambar</th>
                                    <th>Name</th>
                                    <th>Sub Name</th>
                                    <th>Code</th>
                                    <!--<th>Brand</th>-->
                                    <!--<th>Deskripsi Game</th>-->
                                    <!--<th>Deskripsi Field User ID & Zone ID</th>-->
                                    <th>Status</th>
                                    <!--<th>Banner</th>-->
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($data as $datas)
                                    @php
                                        $label_pesanan = '';
                                        if ($datas->status == 'active') {
                                            $label_pesanan = 'success';
                                        } elseif ($datas->status == 'unactive') {
                                            $label_pesanan = 'danger';
                                        }
                                    @endphp
                                    <tr>
                                        <th scope="row">{{ $datas->id }}</th>
                                        <td>
                                            <img src="{{ asset($datas->thumbnail) }}" width="80" height="80"
                                                class="img-thumbnail">
                                        </td>
                                        <td>{{ $datas->nama }}
                                          <div style="margin-top: 5px; font-size: 0.9em; color: #6c757d;">
                                                {{ $datas->created_at }}
                                            </div>
                                        </td>
                                        <td>{{ $datas->sub_nama }}</td>
                                        <td>{{ $datas->kode }}</td>
                                        <!--<td>{{ $datas->brand }}</td>-->
                                        <!--<td>{{ $datas->deskripsi_game }}</td>-->
                                        <!--<td>{{ $datas->deskripsi_field }}</td>-->
                                        <td>
                                            <div class="btn-group">
                                                <button type="button"
                                                    class="btn btn-{{ $label_pesanan }}">{{ $datas->status }}</button>
                                                <button type="button"
                                                    class="btn btn-{{ $label_pesanan }} dropdown-toggle dropdown-toggle-split"
                                                    data-bs-toggle="dropdown" aria-expanded="false">
                                                    <span class="visually-hidden">Toggle Dropdown</span>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item"
                                                            href="/kategori-status/{{ $datas->id }}/unactive">unactive</a>
                                                    </li>
                                                    <li><a class="dropdown-item"
                                                            href="/kategori-status/{{ $datas->id }}/active">active</a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                        <!--<td>-->
                                        <!--    <img src="{{ asset($datas->banner) }}"  width="100" height="50" style="border-radius: 10px;">-->
                                        <!--</td>-->
                                        {{-- <td>
                                            <a href="javascript:;"
                                                onclick="modal('{{ $datas->nama }}', '{{ route('kategori.detail', [$datas->id]) }}')"
                                                class="btn-sm btn-info mb-3">Edit</a>
                                            <br>
                                            <br>
                                            <a class="btn-sm btn-danger mt-2"
                                                href="/kategori/hapus/{{ $datas->id }}">Delete</a>
                                        </td> --}}
                                        <td>
                                            <div class="dropdown d-inline-block">
                                                <button class="btn btn-soft-secondary btn-sm" type="button"
                                                    data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="ri-more-fill align-middle"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <!-- Edit Action -->
                                                    <li>
                                                        <a href="javascript:void(0);" class="dropdown-item"
                                                            onclick="modal('{{ $datas->nama }}', '{{ route('kategori.detail', [$datas->id]) }}')">
                                                            <i class="ri-pencil-fill align-bottom me-2 text-muted"></i>
                                                            Edit
                                                        </a>
                                                    </li>
                                                    <!-- Delete Action -->
                                                    <li>
                                                        <a href="javascript:void(0);" class="dropdown-item"
                                                            onclick="openDeleteModal('{{ $datas->id }}')">
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
                    </div>
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

    <!-- Modal Delete -->
    <div id="removeItemModal" class="modal fade zoomIn" tabindex="-1" aria-modal="true" role="dialog">
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
    <script src="https://cdn.ckeditor.com/ckeditor5/36.0.1/classic/ckeditor.js"></script>

    <script>
        // Inisialisasi CKEditor
        document.addEventListener('DOMContentLoaded', function() {
            ClassicEditor
                .create(document.querySelector('#deskripsi_game'))
                .then(editor => {
                    console.log('CKEditor berhasil diinisialisasi:', editor);
                })
                .catch(error => {
                    console.error('CKEditor error:', error);
                });
        });

        // Validasi Form CKEditor
        document.getElementById('kategori').addEventListener('submit', function(e) {
            const editorData = document.querySelector('#deskripsi_game').closest('.ck-editor').querySelector(
                '.ck-editor__editable').innerText;
            if (!editorData.trim()) {
                e.preventDefault(); // Mencegah form submit
                alert('Game description is required!');
            }
        });

        function openDeleteModal(dataId) {
            // Buat URL berdasarkan ID pengguna
            const deleteUrl = `/kategori/hapus/${dataId}`;

            // Set URL ke tombol konfirmasi delete di modal
            document.getElementById('confirmDeleteButton').setAttribute('href', deleteUrl);

            // Tampilkan modal
            const deleteModal = new bootstrap.Modal(document.getElementById('removeItemModal'));
            deleteModal.show();
        }
    </script>
    <script>
        $(document).ready(function() {
            setTimeout(function() {
                $('#loadingSpinner').addClass('d-none');
                $('#dataTableContainer').removeClass('d-none');
                $('#kategori-table').DataTable();
            }, 500); // Adjust the delay as needed
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.14.0/Sortable.min.js"></script>
    <script>
        var table = document.getElementById('kategori-table').getElementsByTagName('tbody')[0];
        new Sortable(table, {
            animation: 150,
            ghostClass: 'bg-light',
            handle: 'th',
            onEnd: function(evt) {
                // Callback ketika pengurutan selesai
                var item = evt.item; // Item yang diurutkan (baris tabel)
                // Lakukan pembaruan atau operasi lain setelah pengurutan selesai
            }
        });
    </script>

    <script>
        $(document).ready(function() {
            setTimeout(function() {
                $('#loadingSpinnerKategori').addClass('d-none');
                $('#kategoriTableContainer').removeClass('d-none');
                $('#kategori-table').DataTable();
            }, 500); // Adjust the delay as needed
        });
    </script>

    <script type="text/javascript">
        $(document).ready(function() {
            $('.table').DataTable({});
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
                    $('#summernotee').summernote({
                        height: 200,
                        minHeight: null,
                        maxHeight: null,
                        focus: true
                    });
                },
                error: function() {
                    $('#modal-detail-title').html(name);
                    $('#modal-detail-body').html('There is an error...');
                }
            });
            myModal.show();
        }
    </script>

    <script>
        let fieldCount = 1;
        const addMoreBtn = document.getElementById('addMoreBtn');
        const removeFieldBtn = document.getElementById('removeFieldBtn');
        const fieldsContainer = document.getElementById('fieldsContainer');

        // Fungsi untuk menambahkan field baru
        addMoreBtn.addEventListener('click', () => {
            fieldCount++;

            const newFieldGroup = document.createElement('div');
            newFieldGroup.classList.add('field-group');

            newFieldGroup.innerHTML = `
            <div class="flex justify-between">
                <div class="field-title">Target ${fieldCount}</div>
            </div>
            <div class="mt-4">
                <input class="form-control" placeholder="Title" name="inputs_${fieldCount}_title">
            </div>
            <div class="mt-4">
                <input class="form-control" placeholder="Name" name="inputs_${fieldCount}_name">
            </div>
            <div class="mt-4 mb-4">
                <select class="form-control" name="inputs_${fieldCount}_type">
                    <option>Select Input Type</option>
                    <option value="text">Text</option>
                    <option value="number">Number</option>
                    <option value="password">Password</option>
                    <option value="select">Select</option>
                </select>
            </div>
        `;

            fieldsContainer.appendChild(newFieldGroup);
            updateFieldTitles();
        });

        // Fungsi untuk menghapus field terakhir
        removeFieldBtn.addEventListener('click', () => {
            const fieldGroups = fieldsContainer.querySelectorAll('.field-group');
            if (fieldGroups.length > 1) {
                fieldGroups[fieldGroups.length - 1].remove();
                fieldCount--;
                updateFieldTitles();
                showToast('Target successfully deleted!', 'success');
            } else {
                showToast('There must be at least one target!', 'error');
            }
        });

        // Fungsi untuk memperbarui urutan Target setelah penghapusan
        function updateFieldTitles() {
            const fieldGroups = fieldsContainer.querySelectorAll('.field-group');
            fieldGroups.forEach((field, index) => {
                const titleDiv = field.querySelector('.field-title');
                titleDiv.textContent = `Target ${index + 1}`;

                field.querySelector('input[name^="inputs_"]').setAttribute('name', `inputs_${index + 1}_title`);
                field.querySelectorAll('input[name^="inputs_"]')[1].setAttribute('name',
                    `inputs_${index + 1}_name`);
                field.querySelector('select[name^="inputs_"]').setAttribute('name', `inputs_${index + 1}_type`);
            });
        }

        // Fungsi untuk menampilkan Toastify
        function showToast(message, type) {
            Toastify({
                text: message,
                duration: 3000,
                gravity: "top",
                position: "right",
                backgroundColor: type === 'success' ? "#28a745" : "#dc3545",
                stopOnFocus: true,
            }).showToast();
        }
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
