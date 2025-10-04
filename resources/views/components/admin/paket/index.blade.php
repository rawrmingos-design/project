@extends('layouts.master')
@section('title')
    {{ ENV('APP_NAME') }} - Paket Layanan
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
                <h4 class="mb-sm-0">Products</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item active">Products</li>
                        <li class="breadcrumb-item"><a href="{{ url('paket') }}">Service Packages</a></li>
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
    @if (session('error'))
        <div class="alert alert-danger" role="alert">
            {{ session('error') }}
        </div>
    @endif
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Add Service Packages</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('paket-layanan.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3 row">
                            <label class="col-lg-2 col-form-label" for="example-fileinput">Packages</label>
                            <div class="col-lg-10">
                                <select class="form-control @error('paket_id') is-invalid @enderror" name="paket_id">
                                    <option value="" selected disabled>--Select Package--</option>
                                    @foreach ($pakets as $paket)
                                        <option value="{{ $paket->id }}"
                                            {{ old('paket_id') == $paket->id ? 'selected' : '' }}>
                                            {{ $paket->nama }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('paket_id')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-lg-2 col-form-label">Category</label>
                            <div class="col-lg-10">
                                <select class="form-select" onchange="get_layanan(this.value)">
                                    <option value="" selected disabled>--Select Category--</option>
                                    @foreach ($kategoris as $item)
                                        <option value="{{ $item->id }}">{{ $item->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label class="col-lg-2 col-form-label">Service</label>
                            <div class="col-lg-10" id="layanan-container">
                                <!-- Dynamic content will be loaded here -->
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-lg-2 col-form-label">Icon</label>
                            <div class="col-lg-10">
                                <input type="file" class="form-control @error('product_logo') is-invalid @enderror"
                                    name="product_logo">
                                @error('product_logo')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary bg-gradient waves-effect waves-light"
                                    onclick="confirmTambahLayananPaket()">Save</button>
                                <button type="reset"
                                    class="btn btn-dark bg-gradient waves-effect waves-light">Reset</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Create New Package</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('paket.store') }}" method="POST">
                        @csrf
                        <div class="mb-3 row">
                            <label class="col-lg-2 col-form-label">Name</label>
                            <div class="col-lg-10">
                                <input id="summermell" type="text"
                                    class="form-control @error('nama') is-invalid @enderror" name="nama"
                                    placeholder="Enter Package Name" value="{{ old('nama') }}">
                                @error('nama')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary bg-gradient waves-effect waves-light"
                                    onclick="confirmTambahPaket()">Save</button>
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
                    <h4 class="header-title mt-0 mb-1">Package List</h4>
                </div>
                <div class="card-body">
                    <div id="loadingSpinner" class="text-center">
                        <div class="spinner-grow text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                    <div class="table-responsive d-none" id="dataTableContainer">
                        <table id="list-paket"
                            class="table table-bordered nowrap table-striped align-middle dataTable no-footer dtr-inline collapsed"
                            style="width: 100%;">
                            <thead>
                                <tr>
                                    <th style="width:50px">#</th>
                                    <th>Package</th>
                                    <th>Service</th>
                                    <th style="width: 200px">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($pakets as $paket)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $paket->nama }}</td>
                                        <td>
                                            <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                                data-bs-target="#kategoriModal{{ $paket->id }}">{{ $paket->layanan->count() }}
                                                Service
                                            </button>
                                        </td>
                                        {{-- <td class="d-flex gap-2">
                                            <a href="javascript:;" class="btn btn-primary"
                                                onclick="confirmEdit('{{ $paket->id }}')">
                                                Edit
                                            </a>

                                            <form action="{{ route('paket.destroy', $paket->id) }}" method="POST"
                                                id="deleteForm{{ $paket->id }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn btn-danger"
                                                    onclick="confirmDelete({{ $paket->id }})">Wipe
                                                </button>
                                            </form>
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
                                                            onclick="confirmEdit('{{ $paket->id }}')">
                                                            <i class="ri-pencil-fill align-bottom me-2 text-muted"></i>
                                                            Edit
                                                        </a>
                                                    </li>
                                                    <!-- Delete Action -->
                                                    <li>
                                                        <form action="{{ route('paket.destroy', $paket->id) }}" method="POST"
                                                            id="deleteForm{{ $paket->id }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="button" class="dropdown-item"
                                                                onclick="confirmDelete({{ $paket->id }})"><i class="ri-delete-bin-fill align-bottom me-2 text-muted"></i>
                                                                Delete
                                                            </button>
                                                        </form>
                                                        
                                                        {{-- <a href="javascript:void(0);" class="dropdown-item"
                                                            onclick="confirmDelete({{ $paket->id }})">
                                                            <i class="ri-delete-bin-fill align-bottom me-2 text-muted"></i>
                                                            Delete
                                                        </a> --}}
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


    @foreach ($pakets as $paket)
        <div class="modal fade" id="editModal{{ $paket->id }}" tabindex="-1"
            aria-labelledby="editModalLabel{{ $paket->id }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel{{ $paket->id }}">Edit Package</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Form Edit -->
                        <form action="{{ route('paket.update', $paket->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="mb-3">
                                <label for="editNama{{ $paket->id }}" class="form-label">Package Name</label>
                                <input type="text" class="form-control" id="editNama{{ $paket->id }}"
                                    name="nama" value="{{ $paket->nama }}">
                            </div>
                            <button type="submit" class="btn btn-primary">Save</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="kategoriModal{{ $paket->id }}" tabindex="-1"
            aria-labelledby="kategoriModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="kategoriModalLabel">{{ $paket->nama }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="table-responsive">
                            <form action="{{ route('paket-layanan.destroy') }}" method="post" id="bulkDeleteForm">
                                @csrf
                                @method('DELETE')
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Category</th>
                                            <th>Service</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($paket->layanan->groupBy('kategori_id') as $k => $l)
                                            <tr>
                                                <td>{{ isset($l->first()->kategori->nama) ? $l->first()->kategori->nama : 'undefined' }}
                                                </td>
                                                <td>
                                                    <ul>
                                                        @foreach ($l as $item)
                                                            <li class="d-flex ml-3">
                                                                <div class="form-check form-check-primary mb-">
                                                                    <input class="form-check-input" type="checkbox" name="layanan_ids[]" value="{{ $item->id }}"> <span>{{ $item->layanan }}</span>
                                                                </div>
                                                                
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                <button type="submit" class="btn btn-danger mt-3">Delete Selected</button>
                            </form>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Closed</button>
                    </div>
                </div>
            </div>
        </div>


    @endforeach
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
    document.getElementById('bulkDeleteForm').addEventListener('submit', function(event) {
    event.preventDefault(); // Mencegah submit langsung
    const modal = new bootstrap.Modal(document.getElementById('removeItemModal'));
    modal.show();

    document.getElementById('confirmDeleteButton').onclick = function () {
        document.getElementById('bulkDeleteForm').submit();
    };
});

</script>
    <script>
        $(document).ready(function() {
            setTimeout(function() {
                $('#loadingSpinner').addClass('d-none');
                $('#dataTableContainer').removeClass('d-none');
                $('#list-paket').DataTable();
            }, 500); // Adjust the delay as needed
        });

    </script>
    <!-- Pastikan SweetAlert sudah di-load sebelum menggunakan script ini -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Fungsi konfirmasi sebelum menghapus paket
        function confirmDelete(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to return this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete!',
                cancelButtonText: 'Canceled'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Lakukan penghapusan
                    document.getElementById('deleteForm' + id).submit();
                }
            });
        }


        // Fungsi konfirmasi sebelum edit (opsional)
        function confirmEdit(paketId) {
            swal.fire({
                title: 'Are you sure?',
                text: "You will change this data!",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, edit!',
                cancelButtonText: 'Cancelled'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Tampilkan modal edit setelah konfirmasi
                    $('#editModal' + paketId).modal('show');
                }
            });
        }

        // Fungsi konfirmasi sebelum menambah paket
        function confirmTambahPaket() {
            swal.fire({
                title: 'Are you sure?',
                text: "You will add a new package!",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, add more!',
                cancelButtonText: 'Cancelled'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Lanjutkan submit
                    document.querySelector('form[action="{{ route('paket.store') }}"]').submit();
                }
            });
        }
        // Fungsi konfirmasi sebelum menambah layanan paket
        function confirmTambahLayananPaket() {
            swal.fire({
                title: 'Are you sure?',
                text: "You will add services to this package!",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, add more!',
                cancelButtonText: 'Cancelled'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Lanjutkan submit
                    document.querySelector('form[action="{{ route('paket-layanan.store') }}"]').submit();
                }
            });
        }
    </script>



    <script type="text/javascript">
        function get_layanan(kategori_id) {
            let layananContainer = $('#layanan-container');
            $.ajax({
                url: "{{ route('paket-layanan.get-layanan') }}",
                method: "POST",
                headers: {
                    'X-CSRF-TOKEN': "{{ csrf_token() }}"
                },
                data: {
                    kategori_id: kategori_id
                },
                beforeSend: function() {
                    layananContainer.html('Taking Service...');
                },
                success: function(response) {
                    let data = response.data;
                    data.sort((a, b) => a.harga - b.harga);
                    layananContainer.empty();

                    if (data.length > 0) {
                        // Tambahkan checkbox Check All & Uncheck All
                        layananContainer.append(`
                    <div class="mb-2">
                        <input type="checkbox" id="checkAll" class="form-check-input"> 
                        <label for="checkAll" class="form-check-label">Check All</label>
                        <input type="checkbox" id="uncheckAll" class="form-check-input ms-3"> 
                        <label for="uncheckAll" class="form-check-label">Uncheck All</label>
                    </div>
                `);
                    }

                    // Tambahkan daftar layanan
                    $.each(data, function(index, item) {
                        layananContainer.append(`
                    <div class="form-check">
                        <input class="form-check-input layanan-checkbox" type="checkbox" name="layanan_id[]" value="${item.id}" id="layanan_${item.id}">
                        <label class="form-check-label" for="layanan_${item.id}">${item.layanan}</label>
                    </div>
                `);
                    });

                    // Event listener untuk Check All
                    $('#checkAll').on('change', function() {
                        $('.layanan-checkbox').prop('checked', this.checked);
                    });

                    // Event listener untuk Uncheck All
                    $('#uncheckAll').on('change', function() {
                        $('.layanan-checkbox').prop('checked', false);
                    });
                },
                error: function(response) {
                    let res = JSON.parse(response.responseText);
                    layananContainer.html('<div class="text-danger">' + res.message + '</div>');
                }
            });
        }

        function clearSelectedLayanan() {
            $('input[type="checkbox"]:checked').each(function() {
                $(this).prop('checked', false);
            });
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
