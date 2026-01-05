@extends('layouts.master')
@section('title')
    {{ $config->judul_web }} - Sliders
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
                <h4 class="mb-sm-0">Konfigurasi</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item active">Konfigurasi</li>
                        <li class="breadcrumb-item"><a href="{{ url('berita') }}">Banner & Pop Up</a></li>
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

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Tambah Banner / Pop Up</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('berita.post') }}" method="POST" enctype="multipart/form-data" id="berita"
                        class="needs-validation" novalidate>
                        @csrf
                        <div class="form-group">
                            <div class="mb-3 row">
                                <label class="col-lg-2 col-form-label" for="example-fileinput">Gambar</label>
                                <div class="col-lg-10">
                                    <input type="file" class="form-control" name="banner" required>
                                </div>
                                <div class="invalid-feedback">
                                    Harus menyertakan gambar.
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label class="col-lg-2 col-form-label">Deskripsi</label>
                                <div class="col-lg-10">
                                    <textarea name="deskripsi" id="editor" required class="form-control d-none"></textarea>
                                </div>
                                <div class="invalid-feedback">
                                    Deskripsi harus diisi.
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label class="col-lg-2 col-form-label">Tipe</label>
                                <div class="col-lg-10">
                                    <select class="form-control"name="tipe" required>
                                        <option value="banner">Banner</option>
                                        <option value="popupp">Popup</option>

                                        <!--<option value="popup">Popup</option>-->
                                    </select>
                                </div>
                                <div class="invalid-feedback">
                                    Type is required.
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary bg-gradient waves-effect waves-light">Simpan</button>
                                <button type="reset"
                                    class="btn btn-dark bg-gradient waves-effect waves-light">Reset</button>
                            </div>
                        </div><!--end col-->
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="header-title mt-0 mb-1">Banner / Pop Up</h4>
                </div>
                <div class="card-body">
                    <div id="loadingSpinner" class="text-center">
                        <div class="spinner-grow text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                    <div class="table-responsive d-none" id="dataTableContainer">
                        <table id="berita-table"
                            class="table table-bordered nowrap table-striped align-middle dataTable no-footer dtr-inline collapsed"
                            style="width: 100%;">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Gambar</th>
                                    <th>Tipe</th>
                                    <th>Deskripsi</th>
                                    <th>Tanggal</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($berita as $data)
                                    <tr>
                                        <td>{{ $data->id }}</td>
                                        <td>
                                            <img src="{{ asset($data->path) }}" alt="Thumbnail kosweb" width="250"
                                                height="75" style="border-radius: 10px;">
                                        </td>

                                        <td>{{ $data->tipe }}</td>
                                        <td>
                                            @php
                                                $deskripsiSingkat = Str::limit(
                                                    strip_tags($data->deskripsi),
                                                    100,
                                                    '...',
                                                );
                                            @endphp
                                            {!! $deskripsiSingkat !!}
                                            <a href="#" class="lihat-selengkapnya"
                                                data-deskripsi="{!! htmlspecialchars($data->deskripsi) !!}">See More</a>
                                        </td>
                                        <td>{{ $data->created_at }}</td>

                                        <td>
                                            <div class="dropdown d-inline-block">
                                                <button class="btn btn-soft-secondary btn-sm" type="button"
                                                    data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="ri-more-fill align-middle"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <!-- Edit Action -->
                                                    {{-- <li>
                                                        <a href="javascript:void(0);" class="dropdown-item"
                                                            onclick="confirmEdit('{{ $data->id }}', '{{ route('berita.edit', [$data->id]) }}')">
                                                            <i class="ri-pencil-fill align-bottom me-2 text-muted"></i>
                                                            Edit
                                                        </a>
                                                    </li> --}}
                                                    <!-- Delete Action -->
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
        $(document).ready(function() {
            setTimeout(function() {
                $('#loadingSpinner').addClass('d-none');
                $('#dataTableContainer').removeClass('d-none');
                $('#berita-table').DataTable();
            }, 500); // Adjust the delay as needed
        });
    </script>
    <script src="https://cdn.ckeditor.com/ckeditor5/36.0.1/classic/ckeditor.js"></script>
    <script>
        ClassicEditor
            .create(document.querySelector('#editor'))
            .catch(error => {
                console.error(error);
            });
    </script>
    <script>
        document.getElementById('berita').addEventListener('submit', function(e) {
            const editorData = document.querySelector('#editor').nextSibling.querySelector('.ck-editor__editable')
                .innerText;
            if (!editorData.trim()) {
                e.preventDefault(); // Mencegah form submit

            }
        });
    </script>
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
        function openDeleteModal(dataId) {
            // Buat URL berdasarkan ID pengguna
            const deleteUrl = `/berita/hapus/${dataId}`;

            // Set URL ke tombol konfirmasi delete di modal
            document.getElementById('confirmDeleteButton').setAttribute('href', deleteUrl);

            // Tampilkan modal
            const deleteModal = new bootstrap.Modal(document.getElementById('removeItemModal'));
            deleteModal.show();
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
